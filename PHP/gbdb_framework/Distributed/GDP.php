<?php

declare(strict_types=1);

/**
 * Öffentliche GDP-API (GBDB Distributed Protocol, GDP/1).
 *
 * GDP ist optional. Ohne Aktivierung in .framework.env.php (Bereich GDP)
 * verhalten sich alle GBDB-Aufrufe unverändert. Anwendungen schreiben weiter
 * über GBDB::insertData/editData/deleteData; Journal, Policies und Sync
 * übernimmt GDP. canWrite() dient der Anzeige, verbindlich prüft der Schreibpfad.
 */
final class GDP {
    private static bool $embeddedRegistered = false;

    public static function enabled() :bool {
        return GDPConfig::enabled();
    }

    private static function requireEnabled() :void {
        if (!GDPConfig::enabled()) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'GDP ist nicht aktiviert (GDP → enabled in .framework.env.php).');
        }
    }

    private static function requireHub() :void {
        self::requireEnabled();

        if (GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Diese Aktion ist nur auf dem Hub möglich.');
        }
    }

    private static function requireInstance(string $instanceId) :array {
        $instance = GDPInstanceRegistry::get($instanceId);

        if ($instance === null) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Instanz ist in GDP nicht registriert: ' . $instanceId);
        }

        return $instance;
    }

    /**
     * Initialisiert Identität und Zustand (idempotent).
     */
    public static function init() :array {
        self::requireEnabled();

        return GDPStore::transaction(static function () :array {
            GDPNodeIdentity::get();
            GDPStateManager::update([]);

            return self::getStatus();
        });
    }

    /**
     * Gesamtstatus: Verbindung, Sync und Storage getrennt (Kapitel 26).
     */
    public static function getStatus() :array {
        if (!GDPConfig::enabled() && !is_dir(GDPStore::path())) {
            return ['enabled' => false];
        }

        return GDPStore::read(static function () :array {
            $state = GDPStateManager::get();
            $identity = GDPNodeIdentity::get();
            $instances = [];

            foreach (GDPInstanceRegistry::all() as $instanceId => $instance) {
                $instances[$instanceId] = self::instanceStatus($instanceId, $instance);
            }

            return [
                'enabled' => GDPConfig::enabled(),
                'node_id' => $identity['node_id'],
                'display_name' => $identity['display_name'],
                'role' => GDPNodeIdentity::role()->value,
                'role_mismatch' => GDPNodeIdentity::roleMismatch(),
                'hub_url' => GDPNodeIdentity::role() === GDPNodeRole::HUB ? '' : GDPSyncEngine::hubUrl(),
                // Ohne Pairing gibt es keine Verbindung, die ausfallen könnte: "nicht gekoppelt" statt "offline".
                'connection_state' => GDPNodeIdentity::role() === GDPNodeRole::HUB ? GDPConnectionState::ONLINE->value : (GDPCredentials::hubKey() === null ? 'unpaired' : $state['connection_state']),
                'identity_drift' => GDPNodeIdentity::configDrift(),
                'identity_regenerable' => GDPNodeIdentity::regenerable(),
                'storage_state' => GDPStateManager::storage()->value,
                'preference' => $state['preference'],
                'sync_mode' => GDPConfig::syncMode(),
                'insecure_http' => GDPConfig::flag('allow_insecure_http'),
                'last_contact_at' => (int) $state['last_contact_at'],
                'last_successful_sync_at' => (int) $state['last_successful_sync_at'],
                'last_error_code' => $state['last_error_code'],
                'last_error_at' => (int) $state['last_error_at'],
                'backoff_until' => (int) $state['backoff_until'],
                'nodes' => count(GDPPeerRegistry::all()),
                'instances' => $instances,
                'failures' => array_slice($state['failures'], 0, 10),
            ];
        });
    }

    private static function instanceStatus(string $instanceId, array $instance) :array {
        $sequences = GDPJournal::sequences($instanceId);
        $isHub = ($instance['hub_node_id'] ?? '') === GDPNodeIdentity::id();
        $cursor = GDPCursorManager::get($instanceId, 'hub');
        $pending = $isHub ? 0 : max(0, (int) $sequences['source_sequence'] - (int) $cursor['accepted_source_sequence']);
        $peers = [];

        if ($isHub) {
            foreach (GDPCursorManager::all($instanceId) as $node => $peerCursor) {
                $peers[$node] = [
                    'accepted_source_sequence' => (int) ($peerCursor['accepted_source_sequence'] ?? 0),
                    'applied_hub_sequence' => (int) ($peerCursor['applied_hub_sequence'] ?? 0),
                    'lag' => max(0, (int) $sequences['hub_sequence'] - (int) ($peerCursor['applied_hub_sequence'] ?? 0)),
                    'last_confirmed_at' => (int) ($peerCursor['last_confirmed_at'] ?? 0),
                    'blocked' => $peerCursor['blocked'] ?? null,
                ];
            }
        }

        return [
            'instance_id' => $instanceId,
            'replication_enabled' => ($instance['replication_enabled'] ?? false) === true,
            'is_hub' => $isHub,
            'hub_node_id' => (string) ($instance['hub_node_id'] ?? ''),
            'sync_state' => (string) ($instance['sync_state'] ?? ''),
            'blocked' => $instance['blocked'] ?? null,
            'schema_version' => (int) ($instance['schema_version'] ?? 1),
            'policy_version' => (int) GDPPolicyEngine::document($instanceId)['policy_version'],
            'source_sequence' => (int) $sequences['source_sequence'],
            'hub_sequence' => $isHub ? (int) $sequences['hub_sequence'] : (int) ($instance['remote_hub_sequence'] ?? 0),
            'applied_hub_sequence' => $isHub ? (int) $sequences['hub_sequence'] : (int) $cursor['applied_hub_sequence'],
            'accepted_source_sequence' => (int) $cursor['accepted_source_sequence'],
            'pending_operations' => $pending,
            'journal_lag' => $isHub ? 0 : max(0, (int) ($instance['remote_hub_sequence'] ?? 0) - (int) $cursor['applied_hub_sequence']),
            'last_sync_at' => (int) ($instance['last_sync_at'] ?? 0),
            'current_snapshot_id' => (string) ($instance['current_snapshot_id'] ?? ''),
            'intent_pending' => GDPWriteInterceptor::hasPendingIntent($instanceId),
            'bootstrap_preflight' => $instance['bootstrap_preflight'] ?? null,
            'peers' => $peers,
        ];
    }

    public static function isOnline() :bool {
        return GDPConfig::enabled() && GDPStore::read(static fn() :bool => !GDPStateManager::offline());
    }

    /**
     * Synchron heißt: nichts ausstehend und der letzte bekannte Hub-Stand ist angewendet.
     * Ohne Cursor-Abgleich wird nie „synchron“ gemeldet (Kapitel 27).
     */
    public static function isSynchronized(string $instanceId) :bool {
        $status = self::getStatus()['instances'][$instanceId] ?? null;

        return is_array($status) && $status['sync_state'] === GDPSyncState::IDLE->value && $status['pending_operations'] === 0 && $status['journal_lag'] === 0 && $status['last_sync_at'] > 0;
    }

    public static function sync(?string $instanceId = null, int $budgetSeconds = 30) :array {
        self::requireEnabled();

        return GDPSyncEngine::run($instanceId, $budgetSeconds, true);
    }

    public static function canWrite(string $instanceId, string $database, string $table) :bool {
        return self::explainWrite($instanceId, $database, $table)['allowed'];
    }

    public static function explainWrite(string $instanceId, string $database, string $table) :array {
        if (!GDPInstanceRegistry::replicated($instanceId)) {
            return ['allowed' => true, 'replicated' => false, 'mode' => GDPWriteMode::LOCAL_ONLY->value, 'code' => null, 'reason' => 'Instanz wird nicht repliziert.'];
        }

        return GDPStore::read(static fn() :array => GDPPolicyEngine::decideLocal($instanceId, $database, $table, 'INSERT', [])->toArray());
    }

    public static function getNodes() :array {
        return GDPStore::read(static function () :array {
            $nodes = GDPPeerRegistry::all();

            foreach ($nodes as $nodeId => $node) {
                $nodes[$nodeId]['credentials'] = GDPNodeIdentity::role() === GDPNodeRole::HUB ? GDPCredentials::peerKeys((string) $nodeId) : [];
                $nodes[$nodeId]['instances'] = GDPNodeIdentity::role() === GDPNodeRole::HUB ? GDPGrants::instancesFor((string) $nodeId) : [];
            }

            return $nodes;
        });
    }

    public static function getReplicas(string $instanceId) :array {
        return GDPStore::read(static function () use ($instanceId) :array {
            $result = [];

            foreach (GDPGrants::all() as $grant) {
                if ($grant['instance_id'] !== $instanceId || ($grant['revoked_at'] ?? 0) !== 0) {
                    continue;
                }

                $node = GDPPeerRegistry::get($grant['node_id']) ?? [];
                $result[] = [
                    'replica_id' => 'replica-' . $instanceId . '-' . $grant['node_id'],
                    'node_id' => $grant['node_id'],
                    'display_name' => (string) ($node['display_name'] ?? $grant['node_id']),
                    'role' => (string) ($node['role'] ?? ''),
                    'status' => (string) ($node['status'] ?? ''),
                    'permissions' => $grant['permissions'],
                    'allowed_scope' => $grant['allowed_scope'],
                    'cursor' => GDPCursorManager::get($instanceId, $grant['node_id']),
                    'last_contact_at' => (int) ($node['last_contact_at'] ?? 0),
                ];
            }

            return $result;
        });
    }

    /**
     * Hub: logische Instanz zur Replikation freigeben. Vorhandene Daten bleiben unverändert.
     */
    public static function enableInstanceReplication(string $instanceId, array $options = []) :array {
        self::requireHub();

        if (!GDPCanonical::isIdentifier($instanceId) || GBDB::isReservedInstance($instanceId) || $instanceId === 'default') {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Interne oder ungültige Instanzen können nicht repliziert werden.');
        }

        if (!GBDB::existsInstance($instanceId)) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'GBDB-Instanz existiert nicht: ' . $instanceId);
        }

        return GDPStore::transaction(static function () use ($instanceId, $options) :array {
            $instance = GDPInstanceRegistry::save($instanceId, [
                'replication_enabled' => true,
                'hub_node_id' => GDPNodeIdentity::id(),
                'display_name' => (string) ($options['display_name'] ?? $instanceId),
                'schema_version' => max(1, (int) ($options['schema_version'] ?? (GDPInstanceRegistry::get($instanceId)['schema_version'] ?? 1))),
                'sync_state' => GDPSyncState::IDLE->value,
                'blocked' => null,
            ]);

            if (isset($options['default_mode'])) {
                GDPPolicyEngine::setDefaultMode($instanceId, GDPWriteMode::from((string) $options['default_mode']));
            }

            GDPAuditLogger::log('instance.replication_enabled', ['instance' => $instanceId]);

            return $instance;
        });
    }

    public static function disableInstanceReplication(string $instanceId) :void {
        self::requireEnabled();
        GDPStore::transaction(static function () use ($instanceId) :void {
            self::requireInstance($instanceId);
            GDPInstanceRegistry::save($instanceId, ['replication_enabled' => false, 'sync_state' => GDPSyncState::RESYNC->value]);

            if (GDPNodeIdentity::role() === GDPNodeRole::HUB) {
                GDPGrants::revokeInstance($instanceId);
            }

            GDPAuditLogger::log('instance.replication_disabled', ['instance' => $instanceId]);
        });
    }

    public static function setPolicy(string $instanceId, string $database, string $table, array $options) :array {
        self::requireHub();

        return GDPStore::transaction(static function () use ($instanceId, $database, $table, $options) :array {
            self::requireInstance($instanceId);

            return GDPPolicyEngine::setPolicy($instanceId, $database, $table, $options);
        });
    }

    public static function removePolicy(string $instanceId, string $database, string $table) :void {
        self::requireHub();
        GDPStore::transaction(static fn() => GDPPolicyEngine::removePolicy($instanceId, $database, $table));
    }

    public static function setDefaultPolicyMode(string $instanceId, GDPWriteMode $mode) :void {
        self::requireHub();
        GDPStore::transaction(static fn() => GDPPolicyEngine::setDefaultMode($instanceId, $mode));
    }

    public static function getPolicy(string $instanceId, string $database, string $table) :array {
        return GDPStore::read(static fn() :array => GDPPolicyEngine::resolve($instanceId, $database, $table));
    }

    public static function getPolicies(string $instanceId) :array {
        return GDPStore::read(static fn() :array => GDPPolicyEngine::document($instanceId));
    }

    /**
     * @return list<string> Fehlermeldungen, leer bei gültiger Policy.
     */
    public static function validatePolicy(string $instanceId, string $database, string $table, array $options) :array {
        return GDPPolicyEngine::validate($instanceId, $database, $table, $options);
    }

    public static function transferOwnership(string $instanceId, string $database, string $table, string $newOwner) :array {
        self::requireHub();

        return GDPStore::transaction(static fn() :array => GDPPolicyEngine::transferOwnership($instanceId, $database, $table, $newOwner));
    }

    /**
     * Nach Ablauf der alten Lease: Wechsel administrativ abschließen.
     */
    public static function forceCompleteTransfers(string $instanceId) :int {
        self::requireHub();

        return GDPStore::transaction(static fn() :int => GDPPolicyEngine::completeTransfers($instanceId, null, null, true));
    }

    public static function getPendingOperations(string $instanceId, int $limit = 100) :array {
        return GDPStore::read(static function () use ($instanceId, $limit) :array {
            $accepted = (int) GDPCursorManager::get($instanceId, 'hub')['accepted_source_sequence'];
            $read = GDPJournal::read($instanceId, GDPJournal::OUTBOX, $accepted + 1, max(1, min(1000, $limit)), 8388608);

            return array_map(static fn(GDPJournalEntry $entry) :array => $entry->summary(), $read['entries']);
        });
    }

    public static function getCheckpoints(string $instanceId) :array {
        return GDPStore::read(static fn() :array => ['sequences' => GDPJournal::sequences($instanceId), 'cursors' => GDPCursorManager::all($instanceId)]);
    }

    /**
     * Journalansicht für Diagnose und UI (ohne Nutzdaten).
     */
    public static function journal(string $instanceId, string $log, int $from, int $limit = 50, string $table = '') :array {
        $log = $log === GDPJournal::HUB ? GDPJournal::HUB : GDPJournal::OUTBOX;

        return GDPStore::read(static function () use ($instanceId, $log, $from, $limit, $table) :array {
            $filter = $table === '' ? null : static fn(GDPJournalEntry $entry) :bool => $entry->database . '.' . $entry->table === $table;
            $read = GDPJournal::read($instanceId, $log, max(1, $from), max(1, min(500, $limit)), 8388608, $filter);

            return ['entries' => array_map(static fn(GDPJournalEntry $entry) :array => $entry->summary(), $read['entries']), 'through' => $read['through'], 'more' => $read['more']];
        });
    }

    public static function createSnapshot(string $instanceId) :array {
        self::requireHub();

        return GDPSnapshotManager::create($instanceId);
    }

    /**
     * Replikat neu initialisieren. replaceLocal ersetzt abweichende Bestandsdaten
     * ausdrücklich – vorher entsteht automatisch eine Vollsicherung.
     */
    public static function bootstrapReplica(string $instanceId, bool $replaceLocal = false, int $budgetSeconds = 120) :array {
        self::requireEnabled();
        GDPStore::transaction(static function () use ($instanceId) :void {
            self::requireInstance($instanceId);
            GDPInstanceRegistry::unblock($instanceId, GDPSyncState::RESYNC);
            GDPAuditLogger::log('instance.resnapshot_requested', ['instance' => $instanceId]);
        });

        return GDPSyncEngine::run($instanceId, $budgetSeconds, true, $replaceLocal);
    }

    /**
     * Admin-Recovery für blockierte Instanzen: erneut versuchen oder neu initialisieren.
     */
    public static function resolveBlock(string $instanceId, string $action) :void {
        self::requireEnabled();
        GDPStore::transaction(static function () use ($instanceId, $action) :void {
            self::requireInstance($instanceId);
            $state = match ($action) {
                'retry' => GDPSyncState::PENDING,
                'resnapshot' => GDPSyncState::RESYNC,
                default => throw new InvalidArgumentException('Aktion muss retry oder resnapshot sein.'),
            };
            GDPInstanceRegistry::unblock($instanceId, $state);
            GDPAuditLogger::log('instance.unblocked', ['instance' => $instanceId, 'action' => $action]);
        });
    }

    /**
     * Hub: blockierten Replikationsstrom eines Knotens wieder freigeben.
     */
    public static function unblockPeerStream(string $instanceId, string $nodeId) :void {
        self::requireHub();
        GDPStore::transaction(static function () use ($instanceId, $nodeId) :void {
            GDPCursorManager::update($instanceId, $nodeId, ['blocked' => null]);
            GDPAuditLogger::log('stream.unblocked', ['instance' => $instanceId, 'node_id' => $nodeId]);
        });
    }

    /**
     * Strukturänderungen an replizierten Tabellen auf dem Hub. Die Schema-Version
     * steigt; Replikate melden SCHEMA_MISMATCH und werden neu initialisiert.
     */
    public static function schemaMaintenance(string $instanceId, callable $change) :mixed {
        self::requireHub();

        return GDPStore::transaction(static function () use ($instanceId, $change) :mixed {
            $instance = self::requireInstance($instanceId);
            $result = GDPWriteInterceptor::maintenance(static fn() :mixed => GBDB::withInstance($instanceId, $change));
            GDPInstanceRegistry::save($instanceId, ['schema_version' => (int) ($instance['schema_version'] ?? 1) + 1]);
            GDPAuditLogger::log('instance.schema_changed', ['instance' => $instanceId, 'schema_version' => (int) ($instance['schema_version'] ?? 1) + 1]);

            return $result;
        });
    }

    public static function createPairing(array $instances, string $role = 'replica', array $permissions = GDPGrants::PERMISSIONS, bool $requireApproval = false, ?array $scope = null) :array {
        self::requireHub();

        return GDPStore::transaction(static fn() :array => GDPPairing::create($instances, $role, $permissions, $requireApproval, $scope));
    }

    public static function pairingSessions() :array {
        return GDPPairing::sessions();
    }

    public static function revokePairing(string $pairingId) :void {
        self::requireHub();
        GDPStore::transaction(static fn() => GDPPairing::revoke($pairingId));
    }

    public static function pairWithHub(string $hubUrl, string $code, string $displayName = '') :array {
        self::requireEnabled();

        return GDPSyncEngine::pair($hubUrl, $code, $displayName);
    }

    public static function approveNode(string $nodeId) :void {
        self::requireHub();
        GDPStore::transaction(static function () use ($nodeId) :void {
            if (GDPPeerRegistry::get($nodeId) === null) {
                throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Unbekannter Knoten.');
            }

            GDPPeerRegistry::save($nodeId, ['status' => 'active', 'enabled' => true]);
            GDPAuditLogger::log('node.approved', ['node_id' => $nodeId]);
        });
    }

    /**
     * Knoten sperren: Schlüssel und Grants werden sofort widerrufen (Kapitel 20).
     */
    public static function unregisterNode(string $nodeId) :void {
        self::requireHub();
        GDPStore::transaction(static function () use ($nodeId) :void {
            GDPPeerRegistry::save($nodeId, ['enabled' => false, 'status' => 'revoked']);
            $grants = GDPGrants::revokeNode($nodeId);
            $keys = GDPCredentials::revokePeerKeys($nodeId);
            GDPAuditLogger::log('node.revoked', ['node_id' => $nodeId, 'grants' => $grants, 'keys' => $keys]);
        });
    }

    /**
     * Neue Knotenidentität, z. B. nach dem Kopieren einer Installation; nur ohne Kopplung und Replikate.
     */
    public static function regenerateIdentity() :array {
        return GDPNodeIdentity::regenerate();
    }

    public static function rotateCredential() :array {
        self::requireEnabled();

        return GDPSyncEngine::rotateCredential();
    }

    public static function forceLocalMode() :void {
        GDPStore::transaction(static fn() => GDPStateManager::setPreference(GDPOperationPreference::FORCE_LOCAL));
    }

    public static function preferCentralMode() :void {
        GDPStore::transaction(static fn() => GDPStateManager::setPreference(GDPOperationPreference::PREFER_CENTRAL));
    }

    public static function returnToAutoMode() :void {
        GDPStore::transaction(static fn() => GDPStateManager::setPreference(GDPOperationPreference::AUTO));
    }

    public static function auditLog(int $limit = 100) :array {
        return GDPStore::read(static fn() :array => GDPAuditLogger::recent($limit));
    }

    /**
     * Schneller Gesundheitsstatus ohne Netzwerkzugriff.
     */
    public static function getHealth() :array {
        $status = self::getStatus();

        if (($status['enabled'] ?? false) !== true) {
            return ['status' => 'disabled'];
        }

        $blocked = array_filter($status['instances'], static fn(array $instance) :bool => $instance['sync_state'] === GDPSyncState::BLOCKED->value);

        return [
            'status' => $blocked !== [] ? 'blocked' : match ($status['connection_state']) {
                'online' => 'ok',
                'unpaired' => 'unpaired',
                default => 'degraded',
            },
            'connection_state' => $status['connection_state'],
            'storage_state' => $status['storage_state'],
            'blocked_instances' => array_keys($blocked),
            'pending_operations' => array_sum(array_column($status['instances'], 'pending_operations')),
        ];
    }

    /**
     * Verbindungstest für die Oberfläche: Erreichbarkeit, Protokoll, Rolle, Identität, Uhrzeit und – falls
     * gekoppelt – eine signierte Anfrage. Auf dem Hub wird der eigene Endpunkt unter $hubEndpoint geprüft.
     *
     * @return array{url:string, ok:bool, latency_ms:int, checks:list<array{check:string, state:string, detail:string}>}
     */
    public static function testConnection(string $hubEndpoint = '') :array {
        $isHub = GDPNodeIdentity::role() === GDPNodeRole::HUB;
        [$ownId, $paired, $configured] = GDPStore::read(static fn() :array => [GDPNodeIdentity::id(), GDPCredentials::hubKey() !== null, GDPSyncEngine::hubUrl()]);
        $url = $isHub ? $hubEndpoint : $configured;
        $result = ['url' => $url, 'ok' => false, 'latency_ms' => 0, 'checks' => []];
        $add = static function (string $check, string $state, string $detail) use (&$result) :void {
            $result['checks'][] = ['check' => $check, 'state' => $state, 'detail' => $detail];
        };

        if ($url === '') {
            $add('Hub-URL', 'fail', 'Nicht gesetzt: in .framework.env.php unter GDP → hub_url eintragen oder beim Pairing angeben.');

            return $result;
        }

        $https = str_starts_with(strtolower($url), 'https://');
        $insecure = GDPConfig::flag('allow_insecure_http');
        $add('Transport', $https ? 'ok' : ($insecure ? 'warn' : 'fail'), $https ? 'HTTPS mit Zertifikatsprüfung.' : ($insecure ? 'Unverschlüsselt über allow_insecure_http – nur für lokale Tests.' : 'HTTP ist ohne allow_insecure_http gesperrt.'));
        $transport = new GDPHttpTransport($url);
        $started = hrtime(true);

        try {
            $health = $transport->checkHealth();
        } catch (Throwable $exception) {
            $add('Erreichbar', 'fail', $exception->getMessage());

            return $result;
        }

        $result['latency_ms'] = (int) round((hrtime(true) - $started) / 1e6);
        $add('Erreichbar', 'ok', 'gdp.php antwortet in ' . $result['latency_ms'] . ' ms.');
        $add('Protokoll', ($health['protocol'] ?? '') === GDPConfig::PROTOCOL ? 'ok' : 'fail', 'Gegenstelle spricht ' . (string) ($health['protocol'] ?? '?') . ', erwartet ' . GDPConfig::PROTOCOL . '.');
        $add('Rolle der Gegenstelle', ($health['role'] ?? '') === GDPNodeRole::HUB->value ? 'ok' : 'fail', ($health['role'] ?? '') === GDPNodeRole::HUB->value ? 'Hub.' : 'Unter der URL antwortet kein Hub, sondern „' . (string) ($health['role'] ?? '?') . '“.');
        $remoteId = (string) ($health['node_id'] ?? '');
        $remoteName = (string) ($health['display_name'] ?? '');

        if ($remoteId === '') {
            $add('Identität', 'warn', 'Die Gegenstelle meldet keine Knoten-ID (ältere Version).');
        } elseif ($isHub) {
            $add('Identität', $remoteId === $ownId ? 'ok' : 'fail', $remoteId === $ownId ? 'Die URL zeigt auf diesen Hub. Auf Replikaten als Hub-URL eintragen.' : 'Unter der URL antwortet ein anderer Knoten: ' . $remoteName . ' (' . $remoteId . ').');
        } else {
            $add('Identität', $remoteId !== $ownId ? 'ok' : 'fail', $remoteId !== $ownId ? 'Hub ' . $remoteName . ' (' . $remoteId . ').' : 'Hub und dieser Knoten haben dieselbe Knoten-ID ' . $ownId . ' – vermutlich eine kopierte Installation. In der Übersicht die Identität neu erzeugen.');
        }

        $skew = abs(time() - (int) ($health['server_time'] ?? time()));
        $allowed = (int) GDPConfig::get('clock_skew_seconds');
        $add('Uhrzeit', $skew <= $allowed ? 'ok' : 'fail', 'Abweichung ' . $skew . ' s, erlaubt ' . $allowed . ' s (signierte Anfragen prüfen den Zeitstempel).');

        if (!$isHub) {
            if (!$paired) {
                $add('Kopplung', 'warn', 'Noch nicht gekoppelt: auf dem Hub unter „Knoten & Pairing“ einen Pairing-Code erzeugen und hier eintragen.');
            } else {
                try {
                    $transport->call('handshake', ['instances' => []]);
                    $add('Kopplung', 'ok', 'Signierte Anfrage vom Hub angenommen, Antwort korrekt signiert.');
                } catch (GDPException $exception) {
                    $add('Kopplung', 'fail', $exception->errorCode()->value . ': ' . $exception->getMessage());
                }
            }
        }

        $result['ok'] = !in_array('fail', array_column($result['checks'], 'state'), true);

        return $result;
    }

    /**
     * Diagnose (Kapitel 27 H): Konfiguration, Verbindung, Schema, Journal-Integrität.
     */
    public static function getDiagnostics(bool $network = false) :array {
        $checks = [];
        $add = static function (string $name, bool $ok, string $detail) use (&$checks) :void {
            $checks[] = ['check' => $name, 'ok' => $ok, 'detail' => $detail];
        };
        $add('GDP aktiviert', GDPConfig::enabled(), GDPConfig::enabled() ? 'GDP → enabled = true' : 'In .framework.env.php unter GDP aktivieren.');

        if (!GDPConfig::enabled()) {
            return $checks;
        }

        $add('PHP-Version', PHP_VERSION_ID >= 80100, PHP_VERSION);
        $add('OpenSSL (AES-256-GCM)', function_exists('openssl_encrypt') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true), 'Für Pairing und Schlüsselrotation.');
        $add('HTTP-Client', function_exists('curl_init') || ini_get('allow_url_fopen'), function_exists('curl_init') ? 'cURL' : 'Streams (allow_url_fopen)');
        $add('TLS-Prüfung', !GDPConfig::flag('allow_insecure_http'), GDPConfig::flag('allow_insecure_http') ? 'allow_insecure_http ist aktiv – nur für lokale Tests!' : 'Nur HTTPS mit Zertifikatsprüfung.');
        $add('Sync-Modus', true, GDPConfig::syncMode() === 'embedded'
            ? 'Embedded: Sync läuft nur bei eingehenden Anfragen. Ohne Besucher findet keine Hintergrund-Synchronisation statt.'
            : (GDPConfig::syncMode() === 'worker' ? 'Worker: gdp_worker.php muss dauerhaft oder regelmäßig laufen.' : 'Manuell: nur über die Oberfläche oder GDP::sync().'));

        try {
            GDPStore::read(static function () use ($add) :void {
                $add('Rolle unverändert', !GDPNodeIdentity::roleMismatch(), 'Konfiguration: ' . GDPConfig::role()->value . ', Identität: ' . (string) GDPNodeIdentity::get()['role']);
                $add('Tresor lesbar', is_array(GDPVault::all()), 'Schlüssel liegen in .config/.runtime/gdp-vault.php.');
                $add('Speicher', GDPStateManager::storage() !== GDPStorageState::UNAVAILABLE, 'Aktiv: ' . GDPStateManager::storage()->value);

                if (GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
                    $url = GDPSyncEngine::hubUrl();
                    $add('Hub-URL', $url !== '', $url !== '' ? $url : 'Nicht gesetzt.');
                    $add('Hub-Schlüssel', GDPCredentials::hubKey() !== null, GDPCredentials::hubKey() !== null ? 'Gekoppelt.' : 'Noch kein Pairing.');
                }
            });
        } catch (Throwable $exception) {
            $add('Speicher', false, $exception->getMessage());
        }

        foreach (array_keys(GDPStore::read(static fn() :array => GDPInstanceRegistry::all())) as $instanceId) {
            try {
                $result = self::verifyJournal($instanceId);
                $add('Journal ' . $instanceId, $result['ok'], $result['detail']);
            } catch (GDPException $exception) {
                $add('Journal ' . $instanceId, false, $exception->errorCode()->value . ': ' . $exception->getMessage());
            }
        }

        if ($network && GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
            try {
                $health = (new GDPHttpTransport(GDPSyncEngine::hubUrl()))->checkHealth();
                $add('Verbindungstest', ($health['role'] ?? '') === GDPNodeRole::HUB->value, 'Hub antwortet mit ' . (string) ($health['protocol'] ?? '?') . ', Rolle ' . (string) ($health['role'] ?? '?'));
            } catch (Throwable $exception) {
                $add('Verbindungstest', false, $exception->getMessage());
            }
        }

        return $checks;
    }

    /**
     * Integritätsprüfung: Reihenfolge und Payload-Hashes aller verfügbaren Journaleinträge.
     */
    public static function verifyJournal(string $instanceId) :array {
        return GDPStore::read(static function () use ($instanceId) :array {
            $sequences = GDPJournal::sequences($instanceId);
            $checked = 0;

            foreach ([GDPJournal::OUTBOX => 'floor_outbox', GDPJournal::HUB => 'floor_hub'] as $log => $floor) {
                $from = (int) $sequences[$floor];

                do {
                    $read = GDPJournal::read($instanceId, $log, $from, 1000, 67108864);
                    $checked += count($read['entries']);
                    $from = $read['through'] + 1;
                } while ($read['more'] && $read['entries'] !== []);
            }

            return ['ok' => true, 'detail' => $checked . ' Einträge lückenlos, Hashes geprüft.', 'checked' => $checked];
        });
    }

    /**
     * Gemeinsamer Hintergrundzyklus für Embedded-Modus, Worker und externen Trigger.
     * Ein Prozess zur Zeit; Abbrüche verlieren nichts, da das Journal dauerhaft ist.
     */
    public static function runBackgroundCycle(int $budgetSeconds) :array {
        if (!GDPConfig::enabled()) {
            return ['skipped' => 'disabled'];
        }

        $lock = fopen(GBDBRuntime::lockDirectory() . '/gdp-cycle.lock', 'c+');

        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return ['skipped' => 'Ein anderer Synchronisationslauf ist aktiv.'];
        }

        try {
            GDPStore::transaction(static fn() => GDPStateManager::update(['embedded_last_run' => time()]));
            $report = GDPSyncEngine::run(null, $budgetSeconds, false);

            if (GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
                $report['housekeeping'] = GDPSyncEngine::housekeeping();
            }

            return $report;
        } catch (GDPException $exception) {
            GDPStore::transaction(static fn() => GDPStateManager::recordFailure($exception));

            return ['error' => $exception->toArray()];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Embedded-Modus (Kapitel 28 A): kein Cron nötig. Am Ende geeigneter Anfragen
     * läuft höchstens alle embedded_interval Sekunden ein kurzer Zyklus.
     */
    public static function bootEmbedded() :void {
        if (self::$embeddedRegistered || PHP_SAPI === 'cli' || !GDPConfig::enabled() || GDPConfig::syncMode() !== 'embedded') {
            return;
        }

        self::$embeddedRegistered = true;
        register_shutdown_function(static function () :void {
            try {
                if (defined('GDP_ENDPOINT') || connection_aborted() === 1) {
                    return;
                }

                $last = (int) GDPStore::read(static fn() :int => (int) GDPStateManager::get()['embedded_last_run']);

                if (time() - $last < GDPConfig::int('embedded_interval', 5, 86400)) {
                    return;
                }

                self::runBackgroundCycle(GDPConfig::int('embedded_budget_seconds', 1, 60));
            } catch (Throwable $exception) {
                error_log('[GDP] Embedded-Synchronisation übersprungen: ' . get_class($exception));
            }
        });
    }
}
