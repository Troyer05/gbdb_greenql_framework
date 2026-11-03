<?php

declare(strict_types=1);

/**
 * Integration in die GBDB-Schreibpfade (Konzept Kapitel 09 und 24).
 *
 * insertData/editData/deleteData der Engine rufen diesen Interceptor für
 * GDP-verwaltete Instanzen auf. Ablauf je Operation, unter dem exklusiven
 * Speicher-Lock:
 *
 *   1. Policy prüfen (verbindlich, nicht nur in der UI).
 *   2. Intent mit dem Vorher-Zustand der betroffenen Datensätze sichern.
 *   3. Fachänderung über den bisherigen Engine-Pfad ausführen.
 *   4. Tatsächlichen Nachher-Zustand mit dem Vorher-Zustand vergleichen und
 *      daraus die Journalereignisse bilden („Journal = Realität“).
 *   5. Ereignisse anhängen, Quellsequenz vergeben, Intent entfernen.
 *
 * Bricht PHP zwischen 2 und 5 ab, gleicht recover() beim nächsten Zugriff den
 * Datenstand mit dem Intent ab. Es bleibt weder eine Änderung ohne
 * Journalereignis noch ein Ereignis ohne Änderung zurück. Im SQL-Betrieb
 * laufen 2–5 zusätzlich in einer SQL-Transaktion.
 */
final class GDPWriteInterceptor {
    private static int $applying = 0;
    private static int $maintenance = 0;
    private static int $depth = 0;
    private static int $group = 0;
    private static ?string $groupId = null;
    /** @var array<string, array{records: list<array>, entries: list<array>, group: string}> */
    private static array $pending = [];
    private static ?bool $hasData = null;

    /**
     * Schneller Vorabtest. Ohne GDP-Daten kostet er einen is_dir-Aufruf je Anfrage.
     */
    public static function engaged(string $instanceId) :bool {
        if (self::$applying > 0) {
            return false;
        }

        if (!GDPConfig::enabled()) {
            self::$hasData ??= is_dir(GDPStore::path('instances'));

            if (!self::$hasData) {
                return false;
            }
        }

        return GDPInstanceRegistry::replicated($instanceId);
    }

    /**
     * Replikations-Apply und Snapshot-Aktivierung: keine neuen Ursprungsereignisse.
     */
    public static function applying(callable $callback) :mixed {
        self::$applying++;

        try {
            return $callback();
        } finally {
            self::$applying--;
        }
    }

    /**
     * Administrativer Wartungsabschnitt für Strukturänderungen.
     */
    public static function maintenance(callable $callback) :mixed {
        self::$maintenance++;

        try {
            return $callback();
        } finally {
            self::$maintenance--;
        }
    }

    public static function assertStructureChange(string $instanceId, string $action, string $database, string $table) :void {
        if (self::$maintenance > 0 || self::$applying > 0) {
            return;
        }

        if (GDPPolicyEngine::mode(GDPPolicyEngine::resolve($instanceId, $database, $table)) === GDPWriteMode::LOCAL_ONLY) {
            return;
        }

        throw new GDPPolicyViolationException(
            GDPErrorCode::POLICY_VIOLATION,
            'Strukturänderungen an replizierten Tabellen nur über den GDP-Wartungsmodus (Schema-Version erhöhen, Replikate neu initialisieren).',
            ['instance' => $instanceId, 'action' => $action, 'table' => $database . '.' . $table]
        );
    }

    public static function assertInstanceRemovable(string $instanceId) :void {
        if (self::$maintenance === 0 && GDPInstanceRegistry::replicated($instanceId)) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Eine replizierte Instanz zuerst in der GDP-Verwaltung von der Replikation lösen.', ['instance' => $instanceId]);
        }
    }

    /**
     * Engine-Transaktionen (GBDB::begin/commit) bilden eine Transaktionsgruppe.
     */
    public static function beginGroup() :void {
        if (self::$group++ === 0) {
            self::$groupId = GDPCanonical::uuid7();
        }
    }

    public static function endGroup(bool $committed) :void {
        if (self::$group === 0) {
            return;
        }

        if (--self::$group > 0) {
            return;
        }

        try {
            if (!$committed) {
                // Die Engine hat ihre Snapshots zurückgespielt: nur verbliebene Nettoänderungen zählen.
                foreach (self::$pending as $instanceId => $pending) {
                    self::$pending[$instanceId]['entries'] = self::netEntries($instanceId, $pending['records'], $pending['group']);
                }
            }

            self::finalize();
        } finally {
            self::$groupId = null;
        }
    }

    public static function insert(string $instanceId, string $database, string $table, mixed $data, callable $local) :int {
        if (!is_array($data)) {
            return $local($data);
        }

        return self::capture($instanceId, static function () use ($instanceId, $database, $table, $data, $local) :int {
            $rule = GDPPolicyEngine::resolve($instanceId, $database, $table);
            $column = (string) ($rule['owner_column'] ?? '');

            if (GDPPolicyEngine::mode($rule) === GDPWriteMode::NODE_OWNER && $column !== '' && !isset($data[$column])) {
                $data[$column] = GDPNodeIdentity::id();
            }

            $decision = GDPPolicyEngine::decideLocal($instanceId, $database, $table, 'INSERT', [$data]);

            if (!$decision->journal) {
                return $local($data);
            }

            $decision->assert(['instance' => $instanceId, 'table' => $database . '.' . $table]);

            if (!isset($data['id']) || (int) $data['id'] <= 0) {
                $next = GBDBEngine::nextID($database, $table);

                if ($next <= 0) {
                    return $local($data);
                }

                $data['id'] = $next;
            }

            $id = (int) $data['id'];
            $record = self::prepare($instanceId, $database, $table, [$id]);
            $result = $local($data);
            self::settle($instanceId, $record);

            return $result;
        });
    }

    public static function update(string $instanceId, string $database, string $table, mixed $where, mixed $is, mixed $newData, callable $local) :bool {
        if (!is_array($newData)) {
            return $local($newData);
        }

        return self::capture($instanceId, static function () use ($instanceId, $database, $table, $where, $is, $newData, $local) :bool {
            $rows = self::matching($database, $table, $where, $is);
            $after = array_map(static fn(array $row) :array => array_replace($row, $newData), $rows);
            $decision = GDPPolicyEngine::decideLocal($instanceId, $database, $table, 'UPDATE', $rows, $after);

            if (!$decision->journal || $rows === []) {
                return $local($newData);
            }

            $decision->assert(['instance' => $instanceId, 'table' => $database . '.' . $table]);
            $record = self::prepare($instanceId, $database, $table, array_map(static fn(array $row) :int => (int) $row['id'], $rows));
            $result = $local($newData);
            self::settle($instanceId, $record);

            return $result;
        });
    }

    public static function delete(string $instanceId, string $database, string $table, mixed $where, mixed $is, callable $local) :bool {
        return self::capture($instanceId, static function () use ($instanceId, $database, $table, $where, $is, $local) :bool {
            $rows = self::matching($database, $table, $where, $is);
            $decision = GDPPolicyEngine::decideLocal($instanceId, $database, $table, 'DELETE', $rows);

            if (!$decision->journal || $rows === []) {
                return $local();
            }

            $decision->assert(['instance' => $instanceId, 'table' => $database . '.' . $table]);
            $record = self::prepare($instanceId, $database, $table, array_map(static fn(array $row) :int => (int) $row['id'], $rows));
            $result = $local();
            self::settle($instanceId, $record);

            return $result;
        });
    }

    /**
     * Gleiche Trefferlogik wie editData/deleteData der Engine.
     *
     * @return list<array>
     */
    private static function matching(string $database, string $table, mixed $where, mixed $is) :array {
        $rows = GBDBEngine::getData($database, $table);
        $result = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && isset($row['id'], $row[$where]) && $row[$where] == $is) {
                $result[] = $row;
            }
        }

        return $result;
    }

    private static function capture(string $instanceId, callable $work) :mixed {
        if (!isset(self::$pending[$instanceId])) {
            self::recover($instanceId);
            self::$pending[$instanceId] = ['records' => [], 'entries' => [], 'group' => self::$groupId ?? GDPCanonical::uuid7()];
        }

        self::$depth++;

        try {
            return $work();
        } catch (Throwable $exception) {
            // Auch ein abgebrochener Engine-Aufruf kann Daten verändert haben.
            foreach (self::$pending[$instanceId]['records'] as $record) {
                if (!$record['settled']) {
                    self::settle($instanceId, $record);
                }
            }

            throw $exception;
        } finally {
            self::$depth--;

            if (self::$depth === 0 && self::$group === 0) {
                self::finalize();
            }
        }
    }

    private static function intentFile(string $instanceId) :string {
        return GDPStore::path('instances', $instanceId, 'intent.json');
    }

    private static function writeIntent(string $instanceId) :void {
        GDPStore::writeJson(self::intentFile($instanceId), self::$pending[$instanceId], true);
    }

    /**
     * @param list<int> $ids
     */
    private static function prepare(string $instanceId, string $database, string $table, array $ids) :array {
        $before = [];

        foreach ($ids as $id) {
            $row = self::row($database, $table, $id);
            $before[(string) $id] = $row === null ? null : (int) ($row['_gbdb_version'] ?? 1);
        }

        $record = [
            'record_key' => count(self::$pending[$instanceId]['records']),
            'database' => $database,
            'table' => $table,
            'before' => $before,
            'settled' => false,
        ];
        self::$pending[$instanceId]['records'][] = $record;
        self::writeIntent($instanceId);

        return $record;
    }

    /**
     * Vergleicht Vorher- und Nachher-Zustand und hält die tatsächliche Änderung fest.
     */
    private static function settle(string $instanceId, array $record) :void {
        foreach (self::diff($instanceId, $record['database'], $record['table'], $record['before'], self::$pending[$instanceId]['group']) as $entry) {
            self::$pending[$instanceId]['entries'][] = $entry->toArray();
        }

        self::$pending[$instanceId]['records'][$record['record_key']]['settled'] = true;
        self::writeIntent($instanceId);
    }

    /**
     * @param array<string, ?int> $before
     * @return list<GDPJournalEntry>
     */
    private static function diff(string $instanceId, string $database, string $table, array $before, string $groupId) :array {
        $entries = [];
        $node = GDPNodeIdentity::id();
        $instance = GDPInstanceRegistry::get($instanceId) ?? [];
        $schema = (int) ($instance['schema_version'] ?? 1);
        $rule = GDPPolicyEngine::resolve($instanceId, $database, $table);
        $epoch = (int) ($rule['authority_epoch'] ?? 1);
        $transfer = $rule['transfer'] ?? null;

        if (is_array($transfer) && $transfer['from'] === $node) {
            $epoch = (int) $transfer['from_epoch'];
        }

        foreach ($before as $id => $previous) {
            $now = self::row($database, $table, (int) $id);
            $payload = $now === null ? null : self::payload($now);
            $version = $now === null ? 0 : (int) ($now['_gbdb_version'] ?? 1);

            if ($previous === null && $now !== null) {
                $entries[] = GDPJournalEntry::create($instanceId, $node, 'INSERT', $database, $table, (string) $id, $schema, $epoch, 0, $version, $payload, $groupId);
            } elseif ($previous !== null && $now === null) {
                $entries[] = GDPJournalEntry::create($instanceId, $node, 'DELETE', $database, $table, (string) $id, $schema, $epoch, $previous, $previous + 1, null, $groupId);
            } elseif ($previous !== null && $now !== null && $version !== $previous) {
                $entries[] = GDPJournalEntry::create($instanceId, $node, 'UPDATE', $database, $table, (string) $id, $schema, $epoch, $previous, $version, $payload, $groupId);
            }
        }

        return $entries;
    }

    /**
     * @return list<array>
     */
    private static function netEntries(string $instanceId, array $records, string $groupId) :array {
        $first = [];

        foreach ($records as $record) {
            foreach ($record['before'] as $id => $version) {
                $key = $record['database'] . "\0" . $record['table'] . "\0" . $id;
                $first[$key] ??= [$record['database'], $record['table'], (string) $id, $version];
            }
        }

        $entries = [];

        foreach ($first as [$database, $table, $id, $version]) {
            foreach (self::diff($instanceId, $database, $table, [$id => $version], $groupId) as $entry) {
                $entries[] = $entry->toArray();
            }
        }

        return $entries;
    }

    private static function finalize() :void {
        $pending = self::$pending;
        self::$pending = [];

        foreach ($pending as $instanceId => $state) {
            self::journal($instanceId, $state['entries'], false);
            GDPStore::delete(self::intentFile($instanceId));
        }
    }

    /**
     * @param list<array> $entries
     */
    private static function journal(string $instanceId, array $entries, bool $mayExist) :void {
        if ($entries === []) {
            return;
        }

        $isHub = GDPNodeIdentity::role() === GDPNodeRole::HUB && (GDPInstanceRegistry::get($instanceId)['hub_node_id'] ?? '') === GDPNodeIdentity::id();
        $single = count($entries) === 1 && self::$group === 0 && self::$groupId === null;
        $objects = [];

        foreach ($entries as $entry) {
            if ($single) {
                $entry['transaction_group_id'] = null;
            }

            // Nur nach einem Absturz kann ein Ereignis schon im Journal stehen.
            if ($mayExist && GDPJournal::containsOperation($instanceId, $isHub ? GDPJournal::HUB : GDPJournal::OUTBOX, $entry['operation_id'])) {
                continue;
            }

            $objects[] = GDPJournalEntry::fromArray($entry, false);
        }

        GDPJournal::appendLocal($instanceId, $objects, $isHub);

        if (!$isHub && $objects !== []) {
            GDPInstanceRegistry::setState($instanceId, GDPSyncState::PENDING);
        }
    }

    /**
     * Crash-Recovery: offenen Intent mit dem tatsächlichen Datenstand abgleichen.
     */
    public static function recover(string $instanceId) :void {
        $file = self::intentFile($instanceId);

        if (isset(self::$pending[$instanceId]) || !GDPStore::exists($file)) {
            return;
        }

        $intent = GDPStore::readJson($file);
        $records = (array) ($intent['records'] ?? []);
        $entries = (array) ($intent['entries'] ?? []);
        $group = (string) ($intent['group'] ?? GDPCanonical::uuid7());

        if (!GDPCanonical::isUuid($group)) {
            $group = GDPCanonical::uuid7();
        }

        foreach ($records as $record) {
            if (!($record['settled'] ?? false)) {
                foreach (self::diff($instanceId, (string) $record['database'], (string) $record['table'], (array) $record['before'], $group) as $entry) {
                    $entries[] = $entry->toArray();
                }
            }
        }

        self::journal($instanceId, $entries, true);
        GDPStore::delete($file);
        GDPAuditLogger::log('intent.recovered', ['instance' => $instanceId, 'records' => count($records), 'entries' => count($entries)]);
    }

    public static function hasPendingIntent(string $instanceId) :bool {
        return GDPStore::exists(self::intentFile($instanceId));
    }

    private static function row(string $database, string $table, int $id) :?array {
        $row = GBDBEngine::getData($database, $table, true, 'id', $id);

        return is_array($row) && $row !== [] ? $row : null;
    }

    /**
     * Fachliche Felder eines Datensatzes ohne id und interne Versionsspalten.
     */
    public static function payload(array $row) :array {
        $payload = [];

        foreach ($row as $key => $value) {
            if ($key !== 'id' && !(is_string($key) && str_starts_with($key, '_gbdb_'))) {
                $payload[(string) $key] = $value;
            }
        }

        return $payload;
    }
}

/**
 * Getrennter Apply-Pfad für replizierte Operationen (Kapitel 11).
 *
 * Die Operation behält origin_node_id und operation_id; sie wird nicht erneut
 * journalisiert. Jeder Schritt ist idempotent über einen Zustandsvergleich: eine
 * erneut gelieferte Operation wird erkannt, auch wenn der Duplikatmarker vor
 * einem Absturz nicht mehr geschrieben wurde.
 */
final class GDPApplier {
    /**
     * @return string applied oder duplicate
     */
    public static function apply(string $instanceId, GDPJournalEntry $entry) :string {
        return GBDBEngine::withInstance($instanceId, static fn() :string => self::applyInInstance($instanceId, $entry));
    }

    private static function applyInInstance(string $instanceId, GDPJournalEntry $entry) :string {
        if (GDPAppliedIndex::check($instanceId, $entry)) {
            return 'duplicate';
        }

        $instance = GDPInstanceRegistry::get($instanceId) ?? [];
        $context = ['instance' => $instanceId, 'operation_id' => $entry->operation_id, 'table' => $entry->database . '.' . $entry->table, 'record_id' => $entry->record_id];

        if ($entry->schema_version !== (int) ($instance['schema_version'] ?? 1)) {
            throw new GDPSchemaMismatchException(GDPErrorCode::SCHEMA_MISMATCH, 'Schema-Version der Operation passt nicht.', $context + ['expected' => (int) ($instance['schema_version'] ?? 1), 'received' => $entry->schema_version]);
        }

        $columns = array_values(array_filter(GBDBEngine::getKeys($entry->database, $entry->table), static fn($key) :bool => (string) $key !== 'id'));

        if ($columns === []) {
            throw new GDPSchemaMismatchException(GDPErrorCode::SCHEMA_MISMATCH, 'Zieltabelle fehlt auf diesem Knoten.', $context);
        }

        if ($entry->payload !== null && array_diff(array_keys($entry->payload), $columns) !== []) {
            throw new GDPSchemaMismatchException(GDPErrorCode::SCHEMA_MISMATCH, 'Operation enthält unbekannte Spalten.', $context + ['columns' => array_values(array_diff(array_keys($entry->payload), $columns))]);
        }

        $appendOnly = GDPPolicyEngine::mode(GDPPolicyEngine::resolve($instanceId, $entry->database, $entry->table)) === GDPWriteMode::APPEND_ONLY;
        $result = GDPWriteInterceptor::applying(static fn() :string => match ($entry->operation) {
            'INSERT' => self::insert($entry, $appendOnly, $context),
            'UPDATE' => self::update($entry, $context),
            'DELETE' => self::delete($entry, $context),
        });
        GDPAppliedIndex::mark($instanceId, $entry);

        return $result;
    }

    private static function current(GDPJournalEntry $entry) :?array {
        $row = GBDBEngine::getData($entry->database, $entry->table, true, 'id', (int) $entry->record_id);

        return is_array($row) && $row !== [] ? $row : null;
    }

    private static function samePayload(array $row, array $payload) :bool {
        return GDPCanonical::encode(GDPWriteInterceptor::payload($row)) === GDPCanonical::encode($payload);
    }

    private static function insert(GDPJournalEntry $entry, bool $appendOnly, array $context) :string {
        $data = $entry->payload;
        $data['_gbdb_version'] = $entry->new_version;

        if (!$appendOnly) {
            $existing = self::current($entry);

            if ($existing !== null) {
                if (self::samePayload($existing, $entry->payload)) {
                    return 'duplicate';
                }

                throw new GDPIntegrityException(GDPErrorCode::VERSION_CONFLICT, 'Datensatz existiert bereits mit anderem Inhalt.', $context);
            }

            $data['id'] = (int) $entry->record_id;
        }

        if (GBDBEngine::insertData($entry->database, $entry->table, $data) <= 0) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Replizierter Datensatz konnte nicht gespeichert werden (Constraint oder Speicherfehler).', $context);
        }

        return 'applied';
    }

    private static function update(GDPJournalEntry $entry, array $context) :string {
        $existing = self::current($entry);
        $version = $existing === null ? 0 : (int) ($existing['_gbdb_version'] ?? 1);

        if ($existing !== null && $version === $entry->new_version && self::samePayload($existing, $entry->payload)) {
            return 'duplicate';
        }

        if ($existing === null || $version !== $entry->previous_version) {
            throw new GDPIntegrityException(GDPErrorCode::VERSION_CONFLICT, 'Lokale Version passt nicht zur Vorversion der Operation (Lost Update verhindert).', $context + ['local_version' => $version, 'previous_version' => $entry->previous_version]);
        }

        if (!GBDBEngine::editData($entry->database, $entry->table, 'id', (int) $entry->record_id, $entry->payload)) {
            if (self::samePayload($existing, $entry->payload)) {
                return 'duplicate';
            }

            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Replizierte Änderung konnte nicht gespeichert werden.', $context);
        }

        return 'applied';
    }

    private static function delete(GDPJournalEntry $entry, array $context) :string {
        $existing = self::current($entry);

        if ($existing === null) {
            return 'duplicate';
        }

        $version = (int) ($existing['_gbdb_version'] ?? 1);

        if ($version !== $entry->previous_version) {
            throw new GDPIntegrityException(GDPErrorCode::VERSION_CONFLICT, 'Zu löschender Datensatz wurde inzwischen verändert.', $context + ['local_version' => $version]);
        }

        if (!GBDBEngine::deleteData($entry->database, $entry->table, 'id', (int) $entry->record_id) && self::current($entry) !== null) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Replizierte Löschung konnte nicht gespeichert werden.', $context);
        }

        return 'applied';
    }
}
