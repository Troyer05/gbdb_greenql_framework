<?php

trait GBDB_MVCCTrait {
    private static string $isolationLevel = 'read_committed';
    private static array $snapshots = [];

    private static function mvccFile(string $database, string $table): string {
        $file = self::makePath($database, $table, true);
        $dir = dirname($file) . '/.mvcc';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir . '/' . basename($file) . '.versions.jsonl';
    }

    /**
     * handles isolation level.
     *
     * @param string $level value.
     *
     * @return string result.
     */
    public static function isolationLevel(string $level): string {
        $level = strtolower(trim($level));

        if (!in_array($level, ['read_committed', 'repeatable_read', 'snapshot'], true)) {
            $level = 'read_committed';
        }

        self::$isolationLevel = $level;

        return self::$isolationLevel;
    }

    /**
     * handles begin snapshot.
     *
     * @return string result.
     */
    public static function beginSnapshot(): string {
        $id = 'snap_' . bin2hex(random_bytes(8));

        self::$snapshots[$id] = [
            'id' => $id,
            'ts' => microtime(true),
            'instance' => self::getInstance()
        ];

        return $id;
    }

    /**
     * handles end snapshot.
     *
     * @param string $snapshotId value.
     *
     * @return bool result.
     */
    public static function endSnapshot(string $snapshotId): bool {
        if (!isset(self::$snapshots[$snapshotId])) {
            return false;
        }

        unset(self::$snapshots[$snapshotId]);

        return true;
    }

    /**
     * handles transaction snapshots.
     *
     * @return array result.
     */
    public static function transactionSnapshots(): array {
        return array_values(self::$snapshots);
    }

    private static function nextRowVersion(array $row): int {
        return max((int)($row['_gbdb_version'] ?? 0), (int)($row['_gbdb_revision'] ?? 0)) + 1;
    }

    private static function writeRowVersion(
        string $database,
        string $table,
        int $rowId,
        string $action,
        array $before = [],
        array $after = []
    ): void {
        if ($rowId <= 0) {
            return;
        }

        $beforeVersion = self::nextRowVersion($before) - 1;
        $afterVersion = max($beforeVersion + 1, self::nextRowVersion($after));
        $entry = [
            'tx' => self::$txActive ? self::$txId : ('auto_' . bin2hex(random_bytes(6))),
            'instance' => self::getInstance(),
            'db' => $database,
            'table' => $table,
            'row_id' => $rowId,
            'action' => $action,
            'before_version' => $beforeVersion,
            'after_version' => $afterVersion,
            'before' => $before,
            'after' => $after,
            'committed_at' => microtime(true),
            'visible_from' => microtime(true)
        ];

        $entry['checksum'] = GBDBStorage::journalChecksum($entry);

        GBDBStorage::appendLine(
            self::mvccFile($database, $table),
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    /**
     * handles row versions.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $rowId value.
     *
     * @return array result.
     */
    public static function rowVersions(string $database, string $table, int $rowId): array {
        $file = self::mvccFile($database, $table);

        if (!is_file($file)) {
            return [];
        }

        $out = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (!is_array($entry) || (int)($entry['row_id'] ?? 0) !== $rowId) {
                continue;
            }

            $checksum = (string)($entry['checksum'] ?? '');

            if ($checksum !== '' && $checksum !== GBDBStorage::journalChecksum($entry)) {
                continue;
            }

            $out[] = $entry;
        }

        return $out;
    }

    private static function visibleRowAtSnapshot(string $database, string $table, array $row, float $snapshotTs): ?array {
        $created = (float)($row['_gbdb_visible_from'] ?? 0);

        if ($created <= 0 || $created <= $snapshotTs) {
            return $row;
        }

        $rowId = (int)($row['id'] ?? 0);

        if ($rowId <= 0) {
            return null;
        }

        $visible = null;

        foreach (self::rowVersions($database, $table, $rowId) as $version) {
            $ts = (float)($version['visible_from'] ?? $version['committed_at'] ?? 0);

            if ($ts <= $snapshotTs) {
                $after = $version['after'] ?? [];
                $visible = is_array($after) && !empty($after) ? $after : null;
            }

        }

        return $visible;
    }

    private static function applySnapshotVisibility(string $database, string $table, array $rows, ?string $snapshotId = null): array {
        if ($snapshotId === null || !isset(self::$snapshots[$snapshotId])) {
            return $rows;
        }

        $visibleTs = (float)self::$snapshots[$snapshotId]['ts'];
        $out = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['id']) || (int)$row['id'] <= 0) {
                $out[] = $row;
                continue;
            }

            $visible = self::visibleRowAtSnapshot($database, $table, $row, $visibleTs);

            if ($visible !== null) {
                $out[] = $visible;
            }

        }

        return array_values($out);
    }

    private static function applyVisibilityRules(array $rows, ?string $snapshotId = null): array {
        if ($snapshotId === null || !isset(self::$snapshots[$snapshotId])) {
            return $rows;
        }

        $visibleTs = (float)self::$snapshots[$snapshotId]['ts'];

        foreach ($rows as $i => $row) {
            if (!is_array($row) || !isset($row['id']) || (int)$row['id'] <= 0) {
                continue;
            }

            $created = (float)($row['_gbdb_visible_from'] ?? 0);

            if ($created > 0 && $created > $visibleTs) {
                unset($rows[$i]);
            }

        }

        return array_values($rows);
    }

    /**
     * handles get data snapshot.
     *
     * @param string $database value.
     * @param string $table value.
     * @param null|string $snapshotId value.
     * @param bool $filter value.
     * @param mixed $where value.
     * @param mixed $is value.
     *
     * @return mixed result.
     */
    public static function getDataSnapshot(
        string $database,
        string $table,
        ?string $snapshotId = null,
        bool $filter = false,
        mixed $where = '',
        mixed $is = ''
    ): mixed {
        $rows = self::getData($database, $table, false);

        if (!is_array($rows)) {
            return $rows;
        }

        $rows = self::applySnapshotVisibility($database, $table, $rows, $snapshotId);

        if ($filter) {
            foreach ($rows as $row) {
                if (is_array($row) && isset($row[$where]) && $row[$where] == $is) {
                    return $row;
                }

            }

            return [];
        }

        return $rows;
    }

    /**
     * handles optimistic edit data.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     * @param array $newData value.
     * @param int $expectedVersion value.
     *
     * @return bool result.
     */
    public static function optimisticEditData(
        string $database,
        string $table,
        mixed $where,
        mixed $is,
        array $newData,
        int $expectedVersion
    ): bool {
        $row = self::getData($database, $table, true, $where, $is);

        if (!is_array($row) || empty($row)) {
            return false;
        }

        $current = (int)($row['_gbdb_version'] ?? $row['_gbdb_revision'] ?? 0);

        if ($current !== $expectedVersion) {
            return false;
        }

        return self::editData($database, $table, $where, $is, $newData);
    }

    /**
     * handles pessimistic row lock.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $rowId value.
     * @param int $timeoutMs value.
     *
     * @return string|false result.
     */
    public static function pessimisticRowLock(string $database, string $table, int $rowId, int $timeoutMs = 0): string|false {
        return self::lockRow($database, $table, $rowId, 'write', $timeoutMs);
    }

    /**
     * handles cleanup row versions.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $keepSeconds value.
     *
     * @return array result.
     */
    public static function cleanupRowVersions(string $database, string $table, int $keepSeconds = 3600): array {
        $file = self::mvccFile($database, $table);

        if (!is_file($file)) {
            return [
                'ok' => true,
                'removed' => 0
            ];
        }

        $oldestSnapshot = time();

        foreach (self::$snapshots as $snapshot) {
            $oldestSnapshot = min($oldestSnapshot, (int)$snapshot['ts']);
        }

        $threshold = min(time() - max(1, $keepSeconds), $oldestSnapshot);
        $keep = [];
        $removed = 0;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $entry = json_decode($line, true);

            if (!is_array($entry)) {
                $removed++;
                continue;
            }

            if ((float)($entry['committed_at'] ?? 0) < $threshold) {
                $removed++;
                continue;
            }

            $keep[] = $line;
        }

        GBDBStorage::atomicWrite($file, empty($keep) ? '' : implode("\n", $keep) . "\n");

        return [
            'ok' => true,
            'removed' => $removed,
            'kept' => count($keep),
            'active_snapshots' => count(self::$snapshots)
        ];
    }

    /**
     * handles version chains.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function versionChains(string $database, string $table): array {
        $file = self::mvccFile($database, $table);

        if (!is_file($file)) {
            return [];
        }

        $chains = [];

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $entry = json_decode($line, true);

            if (!is_array($entry)) {
                continue;
            }

            $checksum = (string)($entry['checksum'] ?? '');

            if ($checksum !== '' && $checksum !== GBDBStorage::journalChecksum($entry)) {
                continue;
            }

            $rid = (int)($entry['row_id'] ?? 0);

            if ($rid <= 0) {
                continue;
            }

            if (!isset($chains[$rid])) {
                $chains[$rid] = [];
            }

            $chains[$rid][] = $entry;
        }

        ksort($chains, SORT_NUMERIC);

        return $chains;
    }

    /**
     * handles garbage collect versions.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $keepSeconds value.
     *
     * @return array result.
     */
    public static function garbageCollectVersions(string $database, string $table, int $keepSeconds = 3600): array {
        return self::cleanupRowVersions($database, $table, $keepSeconds);
    }

    /**
     * handles mvcc cleanup.
     *
     * @param int $keepSeconds value.
     *
     * @return array result.
     */
    public static function mvccCleanup(int $keepSeconds = 3600): array {
        $report = [
            'ok' => true,
            'tables' => [],
            'removed' => 0,
            'kept' => 0
        ];

        foreach (self::listDBs() as $database) {
            foreach (self::listTables($database) as $table) {
                $res = self::cleanupRowVersions($database, $table, $keepSeconds);
                $report['tables'][$database . '.' . $table] = $res;
                $report['removed'] += (int)($res['removed'] ?? 0);
                $report['kept'] += (int)($res['kept'] ?? 0);

                if (!($res['ok'] ?? false)) {
                    $report['ok'] = false;
                }

            }

        }

        return $report;
    }

    /**
     * handles test consistent reads.
     *
     * @return array result.
     */
    public static function testConsistentReads(): array {
        $oldInstance = self::getInstance();
        $instance = 'mvcc_test_' . bin2hex(random_bytes(4));

        self::setInstance($instance);
        self::createTable('mvcc', 'rows', ['name', 'value']);

        $id = self::insertData('mvcc', 'rows', [
            'name' => 'a',
            'value' => 1
        ]);

        $snapshot = self::beginSnapshot();
        $before = self::getDataSnapshot('mvcc', 'rows', $snapshot, true, 'id', $id);

        self::editData('mvcc', 'rows', 'id', $id, ['value' => 2]);

        $snapAfter = self::getDataSnapshot('mvcc', 'rows', $snapshot, true, 'id', $id);
        $committed = self::getData('mvcc', 'rows', true, 'id', $id);

        self::endSnapshot($snapshot);
        self::deleteInstance($instance, true);
        self::setInstance($oldInstance);

        return [
            'ok' => (($before['value'] ?? null) === ($snapAfter['value'] ?? null)) && (($committed['value'] ?? null) == 2),
            'snapshot_value_before' => $before['value'] ?? null,
            'snapshot_value_after_write' => $snapAfter['value'] ?? null,
            'committed_value' => $committed['value'] ?? null
        ];
    }

    /**
     * handles mvcc load test.
     *
     * @param int $writes value.
     *
     * @return array result.
     */
    public static function mvccLoadTest(int $writes = 100): array {
        $writes = max(1, min(5000, $writes));
        $oldInstance = self::getInstance();
        $instance = 'mvcc_load_' . bin2hex(random_bytes(4));

        self::setInstance($instance);
        self::createTable('mvcc', 'load_rows', [
            'n',
            'payload'
        ]);

        $start = microtime(true);

        for ($i = 1; $i <= $writes; $i++) {
            self::insertData('mvcc', 'load_rows', [
                'n' => $i,
                'payload' => 'row_' . $i
            ]);
        }

        $stats = self::mvccStats('mvcc', 'load_rows');
        $duration = microtime(true) - $start;

        self::deleteInstance($instance, true);
        self::setInstance($oldInstance);

        return [
            'ok' => true,
            'writes' => $writes,
            'seconds' => round($duration, 6),
            'versions' => (int)($stats['versions'] ?? 0)
        ];
    }

    /**
     * handles mvcc stats.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function mvccStats(string $database, string $table): array {
        $file = self::mvccFile($database, $table);
        $lines = is_file($file)
            ? (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
            : [];

        return [
            'ok' => true,
            'versions' => count($lines),
            'file' => $file,
            'isolation' => self::$isolationLevel,
            'active_snapshots' => count(self::$snapshots)
        ];
    }

}
