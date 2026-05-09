<?php

trait GBDB_RecoveryTrait {
    private static bool $booted = false;

    private static function recoveryRoot(): string {
        $dir = dirname(Vars::DB_PATH()) . '/.system/recovery';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * handles boot.
     *
     * @return array result.
     */
    public static function boot(): array {
        if (self::$booted) {
            return [
                'ok' => true,
                'already_booted' => true
            ];
        }

        self::$booted = true;

        $report = self::recover(true);

        self::writeDirtyMarker();

        return $report;
    }

    /**
     * handles shutdown.
     *
     * @return void result.
     */
    public static function shutdown(): void {
        $file = self::recoveryRoot() . '/dirty.json';

        @unlink($file);

        GBDBStorage::atomicWrite(self::recoveryRoot() . '/clean.json', json_encode([
            'clean_at' => time(),
            'owner' => function_exists('gethostname') ? gethostname() . ':' . getmypid() : (string)getmypid()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
    }

    private static function writeDirtyMarker(): void {
        GBDBStorage::atomicWrite(self::recoveryRoot() . '/dirty.json', json_encode([
            'started_at' => time(),
            'pid' => getmypid(),
            'host' => function_exists('gethostname') ? gethostname() : 'host',
            'instance' => self::getInstance()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * handles dirty shutdown detected.
     *
     * @return bool result.
     */
    public static function dirtyShutdownDetected(): bool {
        return is_file(self::recoveryRoot() . '/dirty.json');
    }

    /**
     * handles recover.
     *
     * @param bool $auto value.
     *
     * @return array result.
     */
    public static function recover(bool $auto = false): array {
        $dirty = self::dirtyShutdownDetected();
        $report = [
            'ok' => true,
            'auto' => $auto,
            'dirty_shutdown' => $dirty,
            'started_at' => time(),
            'tables' => [],
            'replayed' => 0,
            'dangling' => 0,
            'rolled_back' => 0,
            'errors' => []
        ];

        $oldInstance = self::getInstance();
        $instances = self::listInstances();

        if (empty($instances)) {
            $instances = [$oldInstance ?: 'default'];
        }

        foreach ($instances as $instance) {
            self::setInstance($instance);

            foreach (self::listDBs() as $db) {
                foreach (self::listTables($db) as $table) {
                    $tableReport = self::recoverTable($db, $table);
                    $tableReport['instance'] = $instance;
                    $report['tables'][] = $tableReport;
                    $report['replayed'] += (int)($tableReport['wal']['replayed'] ?? 0);
                    $report['dangling'] += (int)($tableReport['wal']['dangling'] ?? 0);
                    $report['rolled_back'] += (int)($tableReport['rolled_back'] ?? 0);

                    if (!($tableReport['ok'] ?? false)) {
                        $report['ok'] = false;
                    }

                }

            }

        }

        self::setInstance($oldInstance);

        $report['finished_at'] = time();

        GBDBStorage::atomicWrite(
            self::recoveryRoot() . '/last_report.json',
            json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
        );

        return $report;
    }

    /**
     * handles recover table.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function recoverTable(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $appendFile = self::appendFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $rolledBack = 0;
        $ok = true;
        $errors = [];

        if (!is_file($file)) {
            return [
                'ok' => false,
                'db' => $database,
                'table' => $table,
                'error' => 'table_not_found'
            ];
        }

        $wal = GBDBStorage::recoverWal($appendFile);
        $half = self::detectHalfWrittenAppendOps($appendFile);

        if (!empty($half['truncated_removed'])) {
            $rolledBack += (int)$half['truncated_removed'];
        }

        $base = self::ini($file);
        $ops = self::readAppendOps($appendFile);
        $full = self::applyOps($base, $ops);
        $checksum = GBDBStorage::checksum($full);
        $meta = self::readMeta($metaFile);

        $meta['checksum'] = $checksum;
        $meta['append_ops'] = count($ops);
        $meta['recovered_at'] = time();
        $meta['recovery_dirty'] = self::dirtyShutdownDetected();

        if (!self::writeMeta($metaFile, $meta)) {
            $ok = false;
            $errors[] = 'meta_write_failed';
        }

        $verify = self::syncStorageForTable($database, $table, 'recovery');

        if (($verify['ok'] ?? true) === false) {
            $ok = false;
            $errors[] = 'storage_sync_failed';
        }

        GBDBStorage::journal(self::recoveryRoot() . '/operations.log', [
            'op' => 'recover_table',
            'db' => $database,
            'table' => $table,
            'wal' => $wal,
            'half' => $half,
            'ok' => $ok
        ]);

        return [
            'ok' => $ok,
            'db' => $database,
            'table' => $table,
            'wal' => $wal,
            'half_written' => $half,
            'rolled_back' => $rolledBack,
            'errors' => $errors
        ];
    }

    private static function detectHalfWrittenAppendOps(string $appendFile): array {
        if (!is_file($appendFile)) {
            return [
                'ok' => true,
                'truncated_removed' => 0
            ];
        }

        $lines = file($appendFile, FILE_IGNORE_NEW_LINES) ?: [];
        $valid = [];
        $invalidTail = 0;

        foreach ($lines as $line) {
            $line = trim((string)$line);

            if ($line === '') {
                continue;
            }

            $json = GBDBStorage::decodeLine($line);
            $op = $json === null ? null : json_decode($json, true);

            if (is_array($op) && isset($op['op'])) {
                $valid[] = $line;
                continue;
            }

            $invalidTail++;
        }

        if ($invalidTail > 0) {
            $payload = implode("\n", $valid);

            if ($payload !== '') {
                $payload .= "\n";
            }

            GBDBStorage::atomicWrite($appendFile, $payload);
        }

        return [
            'ok' => true,
            'truncated_removed' => $invalidTail
        ];
    }

    /**
     * handles recovery report.
     *
     * @return array result.
     */
    public static function recoveryReport(): array {
        $file = self::recoveryRoot() . '/last_report.json';
        $data = is_file($file) ? json_decode((string)@file_get_contents($file), true) : [];

        return is_array($data) ? $data : [];
    }

}
