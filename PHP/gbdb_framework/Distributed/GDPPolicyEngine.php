<?php

declare(strict_types=1);

/**
 * Ergebnis einer verbindlichen Schreibprüfung.
 */
final class GDPPolicyDecision {
    public function __construct(
        public readonly bool $allowed,
        public readonly bool $journal,
        public readonly GDPWriteMode $mode,
        public readonly array $rule,
        public readonly ?GDPErrorCode $code = null,
        public readonly string $reason = ''
    ) {
    }

    public function assert(array $context = []) :void {
        if (!$this->allowed) {
            throw new GDPPolicyViolationException($this->code ?? GDPErrorCode::POLICY_VIOLATION, $this->reason, $context + ['mode' => $this->mode->value]);
        }
    }

    public function toArray() :array {
        return [
            'allowed' => $this->allowed,
            'replicated' => $this->journal,
            'mode' => $this->mode->value,
            'code' => $this->code?->value,
            'reason' => $this->reason,
        ];
    }
}

/**
 * Policy Engine (Konzept Kapitel 12–14).
 *
 * Konflikte werden nicht nachträglich gemischt, sondern vorab durch genau eine
 * Schreibautorität je Bereich ausgeschlossen. Die Prüfung läuft im
 * Schreibpfad unter dem Speicher-Lock; canWrite() dient nur der Anzeige.
 */
final class GDPPolicyEngine {
    private const OPTION_KEYS = ['mode', 'owner_node_id', 'owner_column', 'offline_permission', 'offline_create', 'writers', 'lease_seconds', 'immutable'];

    private static function file(string $instanceId) :string {
        return GDPStore::path('instances', $instanceId, 'policies.json');
    }

    public static function document(string $instanceId) :array {
        return GDPStore::readJson(self::file($instanceId), []) + [
            'policy_version' => 1,
            'default_mode' => GDPWriteMode::CENTRAL_OWNER->value,
            'rules' => [],
        ];
    }

    /**
     * Replikate übernehmen das Policy-Dokument des Hubs unverändert.
     */
    public static function replaceDocument(string $instanceId, array $document) :void {
        if (!isset($document['policy_version'], $document['rules']) || !is_int($document['policy_version']) || !is_array($document['rules'])) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültiges Policy-Dokument vom Hub.');
        }

        foreach ($document['rules'] as $rule) {
            self::validateRule($rule);
        }

        GDPStore::writeJson(self::file($instanceId), [
            'policy_version' => $document['policy_version'],
            'default_mode' => GDPWriteMode::tryFrom((string) ($document['default_mode'] ?? ''))?->value ?? GDPWriteMode::CENTRAL_OWNER->value,
            'rules' => array_values($document['rules']),
            'received_at' => time(),
        ]);
    }

    /**
     * Prüft Optionen ohne zu speichern.
     *
     * @return list<string> Fehlermeldungen.
     */
    public static function validate(string $instanceId, string $database, string $table, array $options) :array {
        try {
            self::validateRule(self::buildRule($instanceId, $database, $table, $options, null));

            return [];
        } catch (GDPException $exception) {
            return [$exception->getMessage()];
        }
    }

    private static function buildRule(string $instanceId, string $database, string $table, array $options, ?array $current) :array {
        foreach (array_keys($options) as $key) {
            if (!in_array($key, self::OPTION_KEYS, true)) {
                throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Unbekannte Policy-Option: ' . $key);
            }
        }

        $mode = $options['mode'] ?? ($current['mode'] ?? null);
        $mode = $mode instanceof GDPWriteMode ? $mode : GDPWriteMode::tryFrom((string) $mode);

        if ($mode === null) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'mode muss central_owner, node_owner, append_only oder local_only sein.');
        }

        $writers = array_values(array_filter(array_map('strval', (array) ($options['writers'] ?? ($current['writers'] ?? []))), [GDPCanonical::class, 'isIdentifier']));

        return [
            'policy_id' => $current['policy_id'] ?? 'pol-' . bin2hex(random_bytes(6)),
            'instance_id' => $instanceId,
            'database' => $database,
            'table' => $table,
            'mode' => $mode->value,
            'owner_node_id' => (string) ($options['owner_node_id'] ?? ($current['owner_node_id'] ?? '')),
            'owner_column' => (string) ($options['owner_column'] ?? ($current['owner_column'] ?? '')),
            'offline_permission' => (bool) ($options['offline_permission'] ?? $options['offline_create'] ?? ($current['offline_permission'] ?? false)),
            'writers' => $writers,
            'lease_seconds' => max(0, min(31536000, (int) ($options['lease_seconds'] ?? ($current['lease_seconds'] ?? 0)))),
            'authority_epoch' => (int) ($current['authority_epoch'] ?? 1),
            'transfer' => $current['transfer'] ?? null,
            'changed_at' => time(),
        ];
    }

    private static function validateRule(array $rule) :void {
        $mode = GDPWriteMode::tryFrom((string) ($rule['mode'] ?? ''));
        $name = '/^(\*|[a-zA-Z0-9_\-äöüÄÖÜß]{1,96})$/uD';

        if ($mode === null || !preg_match($name, (string) ($rule['database'] ?? '')) || !preg_match($name, (string) ($rule['table'] ?? ''))) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Policy braucht einen gültigen Modus sowie Datenbank und Tabelle (oder *).');
        }

        if (($rule['database'] ?? '') === '*' && ($rule['table'] ?? '') !== '*') {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Eine Tabellenregel braucht eine konkrete Datenbank.');
        }

        if ($mode === GDPWriteMode::NODE_OWNER) {
            $owner = (string) ($rule['owner_node_id'] ?? '');
            $column = (string) ($rule['owner_column'] ?? '');

            if (($owner === '') === ($column === '')) {
                throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'NODE_OWNER braucht entweder owner_node_id (ganzer Bereich) oder owner_column (Eigentümer je Datensatz).');
            }

            if ($owner !== '' && !GDPCanonical::isIdentifier($owner)) {
                throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'owner_node_id ist ungültig.');
            }

            if ($column !== '' && !preg_match('/^[a-zA-Z0-9_\-äöüÄÖÜß]{1,96}$/uD', $column)) {
                throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'owner_column ist ungültig.');
            }
        }
    }

    /**
     * Legt eine Regel an oder ändert sie (nur auf dem Hub). Jede Änderung
     * erhöht policy_version; ein Eigentümerwechsel läuft über transferOwnership().
     */
    public static function setPolicy(string $instanceId, string $database, string $table, array $options) :array {
        $document = self::document($instanceId);
        $index = self::ruleIndex($document, $database, $table);
        $current = $index === null ? null : $document['rules'][$index];

        if ($current !== null && $current['mode'] === GDPWriteMode::NODE_OWNER->value && array_key_exists('owner_node_id', $options) && (string) $options['owner_node_id'] !== $current['owner_node_id']) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Der Eigentümer wird ausschließlich über einen Eigentümerwechsel mit Fencing geändert.');
        }

        if ($current !== null && ($current['transfer'] ?? null) !== null) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Während eines Eigentümerwechsels sind keine Policy-Änderungen möglich.');
        }

        $rule = self::buildRule($instanceId, $database, $table, $options, $current);

        if ($current !== null && $current['mode'] !== $rule['mode']) {
            // Ein Moduswechsel entzieht bisherige Schreibrechte: neue Autoritätsepoche.
            $rule['authority_epoch'] = (int) $current['authority_epoch'] + 1;
        }

        self::validateRule($rule);

        if ($index === null) {
            $document['rules'][] = $rule;
        } else {
            $document['rules'][$index] = $rule;
        }

        $document['policy_version']++;
        GDPStore::writeJson(self::file($instanceId), $document);
        GDPAuditLogger::log('policy.changed', ['instance' => $instanceId, 'database' => $database, 'table' => $table, 'mode' => $rule['mode'], 'policy_version' => $document['policy_version']]);

        return $rule;
    }

    public static function setDefaultMode(string $instanceId, GDPWriteMode $mode) :void {
        $document = self::document($instanceId);
        $document['default_mode'] = $mode->value;
        $document['policy_version']++;
        GDPStore::writeJson(self::file($instanceId), $document);
        GDPAuditLogger::log('policy.default_changed', ['instance' => $instanceId, 'mode' => $mode->value]);
    }

    public static function removePolicy(string $instanceId, string $database, string $table) :void {
        $document = self::document($instanceId);
        $index = self::ruleIndex($document, $database, $table);

        if ($index === null) {
            return;
        }

        if (($document['rules'][$index]['transfer'] ?? null) !== null) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Während eines Eigentümerwechsels nicht möglich.');
        }

        array_splice($document['rules'], $index, 1);
        $document['policy_version']++;
        GDPStore::writeJson(self::file($instanceId), $document);
        GDPAuditLogger::log('policy.removed', ['instance' => $instanceId, 'database' => $database, 'table' => $table]);
    }

    private static function ruleIndex(array $document, string $database, string $table) :?int {
        foreach ($document['rules'] as $index => $rule) {
            if ($rule['database'] === $database && $rule['table'] === $table) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Spezifischste Regel: Tabelle vor Datenbank (*) vor Instanz (* / *) vor Standard.
     */
    public static function resolve(string $instanceId, string $database, string $table) :array {
        if (str_starts_with($database, '_gbdb_') || $database === 'system') {
            return ['mode' => GDPWriteMode::LOCAL_ONLY->value, 'database' => $database, 'table' => $table, 'authority_epoch' => 0, 'implicit' => 'internal'];
        }

        $document = self::document($instanceId);

        foreach ([[$database, $table], [$database, '*'], ['*', '*']] as [$db, $tb]) {
            $index = self::ruleIndex($document, $db, $tb);

            if ($index !== null) {
                return $document['rules'][$index] + ['policy_version' => $document['policy_version']];
            }
        }

        return [
            'mode' => $document['default_mode'],
            'database' => $database,
            'table' => $table,
            'owner_node_id' => '',
            'owner_column' => '',
            'offline_permission' => false,
            'writers' => [],
            'lease_seconds' => 0,
            'authority_epoch' => 1,
            'transfer' => null,
            'implicit' => 'default',
            'policy_version' => $document['policy_version'],
        ];
    }

    public static function mode(array $rule) :GDPWriteMode {
        return GDPWriteMode::tryFrom((string) ($rule['mode'] ?? '')) ?? GDPWriteMode::CENTRAL_OWNER;
    }

    /**
     * Verbindliche lokale Schreibentscheidung.
     *
     * @param string $operation INSERT, UPDATE oder DELETE.
     * @param list<array> $rows Betroffene Datensätze (UPDATE/DELETE: vorher, INSERT: neu).
     * @param list<array> $after Neue Werte (UPDATE), sonst leer.
     */
    public static function decideLocal(string $instanceId, string $database, string $table, string $operation, array $rows, array $after = []) :GDPPolicyDecision {
        $rule = self::resolve($instanceId, $database, $table);
        $mode = self::mode($rule);
        $node = GDPNodeIdentity::id();
        $instance = GDPInstanceRegistry::get($instanceId) ?? [];
        $isHub = GDPNodeIdentity::role() === GDPNodeRole::HUB && ($instance['hub_node_id'] ?? '') === $node;

        if ($mode === GDPWriteMode::LOCAL_ONLY) {
            return new GDPPolicyDecision(true, false, $mode, $rule);
        }

        if (GDPInstanceRegistry::state($instanceId) === GDPSyncState::INITIALIZING && !$isHub) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Die Instanz ist noch nicht initialisiert (Snapshot-Bootstrap fehlt).');
        }

        if (GDPNodeIdentity::role() === GDPNodeRole::OBSERVER) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Ein Observer besitzt keine Schreibautorität für replizierte Daten.');
        }

        return match ($mode) {
            GDPWriteMode::CENTRAL_OWNER => $isHub
                ? new GDPPolicyDecision(true, true, $mode, $rule)
                : new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Dieser Bereich wird zentral verwaltet und ist auf Replikaten schreibgeschützt.'),
            GDPWriteMode::APPEND_ONLY => self::decideAppend($rule, $mode, $operation, $node, $isHub),
            GDPWriteMode::NODE_OWNER => self::decideOwner($rule, $mode, $node, $isHub, $rows, $after),
            default => new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Unbekannter Modus.'),
        };
    }

    private static function decideAppend(array $rule, GDPWriteMode $mode, string $operation, string $node, bool $isHub) :GDPPolicyDecision {
        if ($operation !== 'INSERT') {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'APPEND_ONLY erlaubt nur neue, unveränderliche Einträge.');
        }

        $writers = (array) ($rule['writers'] ?? []);

        if ($writers !== [] && !in_array($node, $writers, true) && !$isHub) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Dieser Knoten darf hier keine Einträge erzeugen.');
        }

        if (!$isHub && GDPStateManager::offline() && !($rule['offline_permission'] ?? false)) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Offline sind hier keine neuen Einträge erlaubt.');
        }

        return new GDPPolicyDecision(true, true, $mode, $rule);
    }

    private static function decideOwner(array $rule, GDPWriteMode $mode, string $node, bool $isHub, array $rows, array $after) :GDPPolicyDecision {
        if (($rule['transfer'] ?? null) !== null) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::LEASE_EXPIRED, 'Eigentümerwechsel läuft: Schreibzugriff bis zum Abschluss gesperrt.');
        }

        $column = (string) ($rule['owner_column'] ?? '');

        if ($column === '') {
            if ((string) $rule['owner_node_id'] !== $node) {
                return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Nur der Eigentümerknoten darf diesen Bereich verändern.');
            }
        } else {
            foreach ([...$rows, ...$after] as $row) {
                if (!is_array($row) || (string) ($row[$column] ?? '') !== $node) {
                    return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Dieser Datensatz gehört einem anderen Knoten (Spalte ' . $column . ').');
                }
            }
        }

        if ($isHub) {
            return new GDPPolicyDecision(true, true, $mode, $rule);
        }

        $lease = (int) ($rule['lease_seconds'] ?? 0);

        if ($lease > 0) {
            $expires = (int) (self::leases()[(string) ($rule['policy_id'] ?? '')] ?? 0);

            if ($expires <= time()) {
                return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::LEASE_EXPIRED, 'Die Schreib-Lease ist abgelaufen. Mit dem Hub synchronisieren.');
            }

            return new GDPPolicyDecision(true, true, $mode, $rule);
        }

        if (GDPStateManager::offline() && !($rule['offline_permission'] ?? false)) {
            return new GDPPolicyDecision(false, true, $mode, $rule, GDPErrorCode::POLICY_VIOLATION, 'Dieser Bereich ist offline schreibgesperrt.');
        }

        return new GDPPolicyDecision(true, true, $mode, $rule);
    }

    /**
     * Prüfung einer fremden Operation auf dem Hub (Kapitel 20: validate_policy_epoch).
     */
    public static function authorizeRemote(string $instanceId, GDPJournalEntry $entry, string $peerNodeId, ?array $existing) :void {
        $rule = self::resolve($instanceId, $entry->database, $entry->table);
        $mode = self::mode($rule);
        $context = ['instance' => $instanceId, 'operation_id' => $entry->operation_id, 'table' => $entry->database . '.' . $entry->table, 'mode' => $mode->value];
        $deny = static fn(string $reason, GDPErrorCode $code = GDPErrorCode::POLICY_VIOLATION) => throw new GDPPolicyViolationException($code, $reason, $context);

        if ($entry->origin_node_id !== $peerNodeId) {
            $deny('Ein Replikat darf nur eigene Ursprungsoperationen übertragen.', GDPErrorCode::AUTHORIZATION_DENIED);
        }

        match ($mode) {
            GDPWriteMode::LOCAL_ONLY => $deny('LOCAL_ONLY-Daten werden nicht repliziert.'),
            GDPWriteMode::CENTRAL_OWNER => $deny('Zentral verwaltete Daten dürfen nur auf dem Hub geändert werden.'),
            GDPWriteMode::APPEND_ONLY => null,
            GDPWriteMode::NODE_OWNER => null,
        };

        if ($mode === GDPWriteMode::APPEND_ONLY) {
            $writers = (array) ($rule['writers'] ?? []);

            if ($entry->operation !== 'INSERT') {
                $deny('APPEND_ONLY erlaubt nur INSERT.');
            }

            if ($writers !== [] && !in_array($peerNodeId, $writers, true)) {
                $deny('Knoten ist kein zugelassener Schreiber.');
            }

            return;
        }

        $transfer = $rule['transfer'] ?? null;
        $epochOk = $entry->authority_epoch === (int) $rule['authority_epoch']
            || (is_array($transfer) && $transfer['from'] === $peerNodeId && $entry->authority_epoch === (int) $transfer['from_epoch']);

        if (!$epochOk) {
            $deny('Veraltete Schreibautorität (authority_epoch).', GDPErrorCode::LEASE_EXPIRED);
        }

        $column = (string) ($rule['owner_column'] ?? '');

        if ($column === '') {
            $owner = is_array($transfer) ? (string) $transfer['from'] : (string) $rule['owner_node_id'];

            if ($owner !== $peerNodeId) {
                $deny('Knoten ist nicht Eigentümer dieses Bereichs.');
            }

            return;
        }

        foreach ([$existing, $entry->payload] as $row) {
            if (is_array($row) && (string) ($row[$column] ?? '') !== $peerNodeId) {
                $deny('Datensatz gehört einem anderen Knoten.');
            }
        }
    }

    /**
     * Auf dem Hub: Leases, die ein Replikat bei diesem Handshake erhält. Es
     * werden Laufzeiten in Sekunden übertragen, keine absoluten Zeitpunkte –
     * das Replikat rechnet ab dem Beginn seiner Anfrage (Uhrzeitdrift).
     *
     * @return array<string, int> policy_id → Sekunden
     */
    public static function issueLeases(string $instanceId, string $peerNodeId) :array {
        $leases = [];

        foreach (self::document($instanceId)['rules'] as $rule) {
            if ($rule['mode'] !== GDPWriteMode::NODE_OWNER->value || (int) $rule['lease_seconds'] <= 0 || ($rule['transfer'] ?? null) !== null) {
                continue;
            }

            if ($rule['owner_node_id'] === $peerNodeId || $rule['owner_column'] !== '') {
                $leases[$rule['policy_id']] = (int) $rule['lease_seconds'];
            }
        }

        return $leases;
    }

    /**
     * Auf dem Replikat gespeicherte Leases (lokale Ablaufzeiten).
     */
    public static function leases() :array {
        return GDPStore::readJson(GDPStore::path('leases.json'), []);
    }

    public static function storeLeases(array $leases) :void {
        $clean = [];

        foreach ($leases as $policyId => $expires) {
            if (is_string($policyId) && is_int($expires)) {
                $clean[$policyId] = $expires;
            }
        }

        GDPStore::writeJson(GDPStore::path('leases.json'), $clean);
    }

    /**
     * Eigentümerwechsel (Kapitel 12/20): neue Epoche, Fencing der alten Autorität.
     * Abgeschlossen wird er, sobald der alte Eigentümer alle Operationen übertragen
     * und die neue Policy gesehen hat – oder nach Ablauf seiner Lease per Admin.
     */
    public static function transferOwnership(string $instanceId, string $database, string $table, string $newOwner) :array {
        $document = self::document($instanceId);
        $index = self::ruleIndex($document, $database, $table);

        if ($index === null || $document['rules'][$index]['mode'] !== GDPWriteMode::NODE_OWNER->value || $document['rules'][$index]['owner_column'] !== '') {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Eigentümerwechsel nur für NODE_OWNER-Bereiche mit owner_node_id.');
        }

        if (!GDPCanonical::isIdentifier($newOwner)) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Ungültiger neuer Eigentümer.');
        }

        $rule = $document['rules'][$index];

        if (($rule['transfer'] ?? null) !== null) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Es läuft bereits ein Eigentümerwechsel.');
        }

        $document['policy_version']++;
        $rule['transfer'] = [
            'from' => $rule['owner_node_id'],
            'to' => $newOwner,
            'from_epoch' => (int) $rule['authority_epoch'],
            'started_at' => time(),
            'policy_version' => $document['policy_version'],
            'old_lease_expires_at' => time() + (int) $rule['lease_seconds'],
        ];
        $rule['authority_epoch'] = (int) $rule['authority_epoch'] + 1;
        $document['rules'][$index] = $rule;
        GDPStore::writeJson(self::file($instanceId), $document);
        GDPAuditLogger::log('policy.transfer_started', ['instance' => $instanceId, 'database' => $database, 'table' => $table, 'from' => $rule['transfer']['from'], 'to' => $newOwner]);

        return $rule;
    }

    /**
     * Schließt Wechsel ab, deren alter Eigentümer vollständig übergeben hat.
     *
     * @param array{local_head:int, accepted:int, policy_version:int}|null $peerState
     */
    public static function completeTransfers(string $instanceId, ?string $peerNodeId, ?array $peerState, bool $force = false) :int {
        $document = self::document($instanceId);
        $completed = 0;

        foreach ($document['rules'] as $index => $rule) {
            $transfer = $rule['transfer'] ?? null;

            if (!is_array($transfer)) {
                continue;
            }

            $handedOver = $peerNodeId === $transfer['from'] && $peerState !== null
                && $peerState['accepted'] >= $peerState['local_head'] && $peerState['policy_version'] >= (int) $transfer['policy_version'];
            $expired = $force && time() >= (int) $transfer['old_lease_expires_at'];

            if (!$handedOver && !$expired) {
                continue;
            }

            $rule['owner_node_id'] = $transfer['to'];
            $rule['transfer'] = null;
            $rule['changed_at'] = time();
            $document['rules'][$index] = $rule;
            $completed++;
            GDPAuditLogger::log('policy.transfer_completed', ['instance' => $instanceId, 'database' => $rule['database'], 'table' => $rule['table'], 'owner' => $rule['owner_node_id'], 'forced' => $expired && !$handedOver]);
        }

        if ($completed > 0) {
            $document['policy_version']++;
            GDPStore::writeJson(self::file($instanceId), $document);
        }

        return $completed;
    }
}
