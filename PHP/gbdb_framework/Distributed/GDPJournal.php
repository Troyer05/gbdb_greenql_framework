<?php

declare(strict_types=1);

/**
 * Unveränderliches Journalereignis (Konzept Kapitel 09).
 */
final class GDPJournalEntry {
    public const OPERATIONS = ['INSERT', 'UPDATE', 'DELETE'];

    private function __construct(private readonly array $data) {
    }

    /**
     * Neues lokales Ereignis; Quellsequenz und Hash vergibt das Journal.
     */
    public static function create(
        string $instanceId,
        string $originNodeId,
        string $operation,
        string $database,
        string $table,
        string $recordId,
        int $schemaVersion,
        int $authorityEpoch,
        int $previousVersion,
        int $newVersion,
        ?array $payload,
        ?string $transactionGroupId
    ) :self {
        return self::fromArray([
            'operation_id' => GDPCanonical::uuid7(),
            'instance_id' => $instanceId,
            'origin_node_id' => $originNodeId,
            'source_sequence' => 0,
            'hub_sequence' => null,
            'transaction_group_id' => $transactionGroupId,
            'operation' => $operation,
            'database' => $database,
            'table' => $table,
            'record_id' => $recordId,
            'schema_version' => $schemaVersion,
            'authority_epoch' => $authorityEpoch,
            'previous_version' => $previousVersion,
            'new_version' => $newVersion,
            'payload' => $payload,
            'payload_hash' => '',
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'accepted_at' => null,
        ], false);
    }

    /**
     * Prüft Struktur und – sofern verlangt – den Payload-Hash eines empfangenen Ereignisses.
     */
    public static function fromArray(array $data, bool $verifyHash = true) :self {
        $required = ['operation_id', 'instance_id', 'origin_node_id', 'source_sequence', 'operation', 'database', 'table', 'record_id', 'schema_version', 'authority_epoch', 'previous_version', 'new_version'];

        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Journalfeld fehlt: ' . $field);
            }
        }

        $valid = is_string($data['operation_id']) && GDPCanonical::isUuid($data['operation_id'])
            && is_string($data['instance_id']) && GDPCanonical::isIdentifier($data['instance_id'])
            && is_string($data['origin_node_id']) && GDPCanonical::isIdentifier($data['origin_node_id'])
            && is_int($data['source_sequence']) && $data['source_sequence'] >= 0
            && in_array($data['operation'], self::OPERATIONS, true)
            && is_string($data['database']) && preg_match('/^[a-zA-Z0-9_\-äöüÄÖÜß]{1,96}$/uD', $data['database'])
            && is_string($data['table']) && preg_match('/^[a-zA-Z0-9_\-äöüÄÖÜß]{1,96}$/uD', $data['table'])
            && is_string($data['record_id']) && preg_match('/^[A-Za-z0-9_.:-]{1,160}$/D', $data['record_id'])
            && is_int($data['schema_version']) && is_int($data['authority_epoch'])
            && is_int($data['previous_version']) && is_int($data['new_version'])
            && (!isset($data['hub_sequence']) || is_int($data['hub_sequence']))
            && (!isset($data['transaction_group_id']) || (is_string($data['transaction_group_id']) && GDPCanonical::isUuid($data['transaction_group_id'])))
            && (($data['payload'] ?? null) === null || is_array($data['payload']));

        if (!$valid) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültiges Journalereignis.', ['operation_id' => is_string($data['operation_id']) ? $data['operation_id'] : '']);
        }

        if (($data['operation'] === 'DELETE') !== (($data['payload'] ?? null) === null)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'DELETE trägt einen Tombstone ohne Payload, andere Operationen einen Payload.');
        }

        $data += ['hub_sequence' => null, 'transaction_group_id' => null, 'payload' => null, 'created_at' => '', 'accepted_at' => null, 'payload_hash' => ''];
        $entry = new self($data);

        if ($verifyHash && !hash_equals($entry->computeHash(), (string) $data['payload_hash'])) {
            throw new GDPIntegrityException(GDPErrorCode::DUPLICATE_PAYLOAD_MISMATCH, 'Payload-Hash des Ereignisses stimmt nicht.', ['operation_id' => $data['operation_id']]);
        }

        return $entry;
    }

    /**
     * Hash über alle Felder, die eine Operation fachlich festlegen. Hub-Zuordnung
     * und Annahmezeit gehören nicht dazu.
     */
    public function computeHash() :string {
        $basis = $this->data;
        unset($basis['hub_sequence'], $basis['accepted_at'], $basis['payload_hash']);

        return GDPCanonical::hash($basis);
    }

    public function withSourceSequence(int $sequence) :self {
        $data = $this->data;
        $data['source_sequence'] = $sequence;
        $entry = new self($data);
        $data['payload_hash'] = $entry->computeHash();

        return new self($data);
    }

    public function withHubSequence(int $sequence) :self {
        $data = $this->data;
        $data['hub_sequence'] = $sequence;
        $data['accepted_at'] = gmdate('Y-m-d\TH:i:s\Z');

        return new self($data);
    }

    public function __get(string $name) :mixed {
        if (!array_key_exists($name, $this->data)) {
            throw new OutOfRangeException('Unbekanntes Journalfeld: ' . $name);
        }

        return $this->data[$name];
    }

    public function toArray() :array {
        return $this->data;
    }

    /**
     * Kompakte Darstellung für UI und Diagnose, ohne Nutzdaten.
     */
    public function summary() :array {
        $data = $this->data;
        $data['payload'] = $data['payload'] === null ? null : '[' . count($data['payload']) . ' Felder]';

        return $data;
    }
}

/**
 * Append-only Journal in Segmenten fester Länge.
 *
 * Ein Replikat führt seinen Ausgang („outbox“) nach Quellsequenz, der Hub sein
 * Hub-Journal nach Hubsequenz. Zeile n eines Segments gehört zu genau einer
 * Sequenz; Lücken oder vertauschte Zeilen werden beim Lesen erkannt.
 */
final class GDPJournal {
    public const SEGMENT = 1000;
    public const OUTBOX = 'outbox';
    public const HUB = 'hub';

    private static function segmentFile(string $instanceId, string $log, int $segment) :string {
        return GDPStore::path('instances', $instanceId, $log, sprintf('%010d.log', $segment));
    }

    private static function sequenceFile(string $instanceId) :string {
        return GDPStore::path('instances', $instanceId, 'sequence.json');
    }

    private static function sequenceField(string $log) :string {
        return $log === self::HUB ? 'hub_sequence' : 'source_sequence';
    }

    /**
     * Höchste vergebene Sequenzen; heilt sich selbst, wenn ein Absturz zwischen
     * Journalzeile und Zählerdatei lag.
     *
     * @return array{source_sequence:int, hub_sequence:int, floor_outbox:int, floor_hub:int}
     */
    public static function sequences(string $instanceId) :array {
        $state = GDPStore::readJson(self::sequenceFile($instanceId), []) + ['source_sequence' => 0, 'hub_sequence' => 0, 'floor_outbox' => 1, 'floor_hub' => 1];
        $changed = false;

        foreach ([self::OUTBOX, self::HUB] as $log) {
            $field = self::sequenceField($log);
            $tail = self::tail($instanceId, $log, 1);

            if ($tail !== [] && (int) $tail[0]->{$field} > (int) $state[$field]) {
                $state[$field] = (int) $tail[0]->{$field};
                $changed = true;
            }
        }

        if ($changed) {
            GDPStore::writeJson(self::sequenceFile($instanceId), $state);
        }

        return $state;
    }

    private static function writeSequences(string $instanceId, array $state) :void {
        GDPStore::writeJson(self::sequenceFile($instanceId), $state);
    }

    /**
     * Hängt lokal entstandene Ereignisse an. Auf dem Hub erhalten sie zusätzlich
     * sofort ihre Hubsequenz.
     *
     * @param list<GDPJournalEntry> $entries
     * @return list<GDPJournalEntry>
     */
    public static function appendLocal(string $instanceId, array $entries, bool $isHub) :array {
        $state = self::sequences($instanceId);
        $written = [];

        foreach ($entries as $entry) {
            $entry = $entry->withSourceSequence(++$state['source_sequence']);

            if ($isHub) {
                $entry = $entry->withHubSequence(++$state['hub_sequence']);
                self::appendLine($instanceId, self::HUB, $entry->hub_sequence, $entry);
            } else {
                self::appendLine($instanceId, self::OUTBOX, $entry->source_sequence, $entry);
            }

            $written[] = $entry;
        }

        self::writeSequences($instanceId, $state);

        return $written;
    }

    /**
     * Übernimmt ein fremdes, bereits geprüftes Ereignis in das Hub-Journal.
     */
    public static function appendHub(string $instanceId, GDPJournalEntry $entry) :GDPJournalEntry {
        $state = self::sequences($instanceId);
        $entry = $entry->withHubSequence(++$state['hub_sequence']);
        self::appendLine($instanceId, self::HUB, $entry->hub_sequence, $entry);
        self::writeSequences($instanceId, $state);

        return $entry;
    }

    private static function appendLine(string $instanceId, string $log, int $sequence, GDPJournalEntry $entry) :void {
        $segment = intdiv($sequence - 1, self::SEGMENT) + 1;
        $file = self::segmentFile($instanceId, $log, $segment);
        $expected = ($sequence - 1) % self::SEGMENT;

        if (count(GDPStore::readLines($file)) !== $expected) {
            throw new GDPIntegrityException(GDPErrorCode::SEQUENCE_GAP, 'Journalsegment passt nicht zur Sequenz.', ['instance' => $instanceId, 'log' => $log, 'sequence' => $sequence]);
        }

        GDPStore::appendLine($file, GDPStore::seal(GDPCanonical::encode($entry->toArray())));
    }

    /**
     * Liest ab einer Sequenz lückenlos, begrenzt nach Anzahl und Bytes.
     *
     * @return array{entries: list<GDPJournalEntry>, through: int, more: bool}
     */
    public static function read(string $instanceId, string $log, int $from, int $limit, int $maxBytes, ?callable $filter = null) :array {
        $state = self::sequences($instanceId);
        $field = self::sequenceField($log);
        $head = (int) $state[$field];
        $floor = (int) $state[$log === self::HUB ? 'floor_hub' : 'floor_outbox'];
        $from = max(1, $from);

        if ($from < $floor) {
            throw new GDPCheckpointMismatchException(GDPErrorCode::SNAPSHOT_REQUIRED, 'Die angefragten Journaleinträge liegen vor der Retention-Grenze. Ein neuer Snapshot ist nötig.', ['instance' => $instanceId, 'from' => $from, 'floor' => $floor]);
        }

        $entries = [];
        $through = $from - 1;
        $bytes = 0;
        $lines = [];
        $loaded = 0;

        $lastGroup = null;

        for ($sequence = $from; $sequence <= $head; $sequence++) {
            $currentSegment = intdiv($sequence - 1, self::SEGMENT) + 1;

            if ($currentSegment !== $loaded) {
                $lines = GDPStore::readLines(self::segmentFile($instanceId, $log, $currentSegment));
                $loaded = $currentSegment;
            }

            $line = $lines[($sequence - 1) % self::SEGMENT] ?? null;

            if ($line === null) {
                throw new GDPIntegrityException(GDPErrorCode::SEQUENCE_GAP, 'Journalzeile fehlt.', ['instance' => $instanceId, 'log' => $log, 'sequence' => $sequence]);
            }

            $entry = self::decodeLine($line);

            if ((int) $entry->{$field} !== $sequence) {
                throw new GDPIntegrityException(GDPErrorCode::SEQUENCE_GAP, 'Journalreihenfolge ist beschädigt.', ['instance' => $instanceId, 'log' => $log, 'sequence' => $sequence]);
            }

            // Eine Transaktionsgruppe wird nie auf zwei Antworten verteilt.
            $continuesGroup = $lastGroup !== null && $entry->transaction_group_id === $lastGroup;
            $size = strlen($line);

            if (!$continuesGroup && (($entries !== [] && (count($entries) >= $limit || $bytes + $size > $maxBytes)) || $sequence - $from >= $limit * 20)) {
                break;
            }

            if ($filter === null || $filter($entry)) {
                $bytes += $size;
                $entries[] = $entry;
            }

            $lastGroup = $entry->transaction_group_id;

            $through = $sequence;
        }

        return ['entries' => $entries, 'through' => $through, 'more' => $through < $head];
    }

    /**
     * @return list<GDPJournalEntry>
     */
    public static function tail(string $instanceId, string $log, int $count) :array {
        $directory = GDPStore::path('instances', $instanceId, $log);
        $segments = array_values(array_filter(GDPStore::listDirectory($directory), static fn(string $name) :bool => (bool) preg_match('/^\d{10}\.log$/D', $name)));
        $result = [];

        for ($index = count($segments) - 1; $index >= 0 && count($result) < $count; $index--) {
            $lines = GDPStore::readLines($directory . '/' . $segments[$index]);

            for ($line = count($lines) - 1; $line >= 0 && count($result) < $count; $line--) {
                $result[] = self::decodeLine($lines[$line]);
            }
        }

        return $result;
    }

    public static function containsOperation(string $instanceId, string $log, string $operationId, int $window = 200) :bool {
        foreach (self::tail($instanceId, $log, $window) as $entry) {
            if ($entry->operation_id === $operationId) {
                return true;
            }
        }

        return false;
    }

    private static function decodeLine(string $line) :GDPJournalEntry {
        try {
            return GDPJournalEntry::fromArray(GDPCanonical::decode(GDPStore::unseal($line)));
        } catch (JsonException $exception) {
            throw new GDPIntegrityException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'Journalzeile ist beschädigt.', [], $exception);
        }
    }

    /**
     * Entfernt vollständige Segmente unterhalb einer sicheren Grenze (Kapitel 05, IX).
     */
    public static function compact(string $instanceId, string $log, int $safeThrough) :int {
        $state = self::sequences($instanceId);
        $floorField = $log === self::HUB ? 'floor_hub' : 'floor_outbox';
        $removed = 0;
        $segment = intdiv((int) $state[$floorField] - 1, self::SEGMENT) + 1;

        while ($segment * self::SEGMENT <= $safeThrough) {
            GDPStore::delete(self::segmentFile($instanceId, $log, $segment));
            $state[$floorField] = $segment * self::SEGMENT + 1;
            $removed++;
            $segment++;
        }

        if ($removed > 0) {
            self::writeSequences($instanceId, $state);
        }

        return $removed;
    }
}

/**
 * Fortschritt pro Gegenstelle: angenommene Quell- und angewendete Hubsequenzen.
 */
final class GDPCursorManager {
    private static function file(string $instanceId) :string {
        return GDPStore::path('instances', $instanceId, 'cursors.json');
    }

    public static function all(string $instanceId) :array {
        return GDPStore::readJson(self::file($instanceId), []);
    }

    public static function get(string $instanceId, string $peerNodeId) :array {
        return (self::all($instanceId)[$peerNodeId] ?? []) + [
            'accepted_source_sequence' => 0,
            'applied_hub_sequence' => 0,
            'last_confirmed_at' => 0,
        ];
    }

    public static function update(string $instanceId, string $peerNodeId, array $values) :array {
        $all = self::all($instanceId);
        $current = ($all[$peerNodeId] ?? []) + ['accepted_source_sequence' => 0, 'applied_hub_sequence' => 0, 'last_confirmed_at' => 0];
        $all[$peerNodeId] = array_replace($current, $values, ['last_confirmed_at' => time()]);
        GDPStore::writeJson(self::file($instanceId), $all);

        return $all[$peerNodeId];
    }
}

/**
 * Duplikatschutz für fremde Operationen (Kapitel 11): operation_id → payload_hash.
 */
final class GDPAppliedIndex {
    private static function file(string $instanceId, string $operationId) :string {
        return GDPStore::path('instances', $instanceId, 'applied', substr(hash('sha256', $operationId), 0, 2) . '.json');
    }

    public static function lookup(string $instanceId, string $operationId) :?array {
        $entry = GDPStore::readJson(self::file($instanceId, $operationId), [])[$operationId] ?? null;

        return is_array($entry) ? ['payload_hash' => (string) $entry[0], 'hub_sequence' => $entry[1], 'applied_at' => (int) $entry[2]] : null;
    }

    public static function mark(string $instanceId, GDPJournalEntry $entry) :void {
        $file = self::file($instanceId, $entry->operation_id);
        $all = GDPStore::readJson($file, []);
        $all[$entry->operation_id] = [$entry->payload_hash, $entry->hub_sequence, time()];
        GDPStore::writeJson($file, $all);
    }

    /**
     * Prüft eine eingehende Operation. true = bereits angewendet (identisch).
     */
    public static function check(string $instanceId, GDPJournalEntry $entry) :bool {
        $known = self::lookup($instanceId, $entry->operation_id);

        if ($known === null) {
            return false;
        }

        if (!hash_equals($known['payload_hash'], $entry->payload_hash)) {
            throw new GDPIntegrityException(GDPErrorCode::DUPLICATE_PAYLOAD_MISMATCH, 'Bekannte operation_id mit abweichendem Payload.', ['instance' => $instanceId, 'operation_id' => $entry->operation_id]);
        }

        return true;
    }

    public static function prune(string $instanceId, int $olderThan) :int {
        $removed = 0;
        $directory = GDPStore::path('instances', $instanceId, 'applied');

        foreach (GDPStore::listDirectory($directory) as $name) {
            $file = $directory . '/' . $name;
            $all = GDPStore::readJson($file, []);
            $kept = array_filter($all, static fn(array $entry) :bool => (int) $entry[2] >= $olderThan);

            if (count($kept) !== count($all)) {
                $removed += count($all) - count($kept);
                GDPStore::writeJson($file, $kept);
            }
        }

        return $removed;
    }
}
