<?php
declare(strict_types=1);

trait GBDB_AdminOpsTrait {
    private static array $adminCache = [];
    private static array $adminCacheTags = [];

    private static function adminOpsDir(string $suffix = "", bool $ensure = true): string {
        $base = self::dbRootPath(".system/admin_ops", true);
        $path = rtrim($base, "/") . ($suffix !== "" ? "/" . ltrim($suffix, "/") : "");

        if ($ensure) {
            $dir = str_ends_with($path, "/") || pathinfo($path, PATHINFO_EXTENSION) === ""
                ? $path
                : dirname($path);

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

        }

        return $path;
    }

    private static function adminLog(string $type, array $payload = []): bool {
        $entry = [
            "type" => $type,
            "instance" => self::getInstance(),
            "payload" => $payload,
            "ts" => time()
        ];

        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false
            && GBDBStorage::appendLine(self::adminOpsDir("logs/" . date("Y-m") . ".log", true), $json . "\n");
    }

    private static function eachAdminTable(callable $callback, bool $allInstances = true): array {
        $old = self::getInstance();
        $instances = $allInstances ? self::listInstances() : [$old];

        if (empty($instances)) {
            $instances = [$old ?: "default"];
        }

        $out = [];

        foreach ($instances as $instance) {
            self::setInstance((string)$instance);

            foreach (self::listDBs() as $db) {
                foreach (self::listTables((string)$db) as $table) {
                    $out[] = $callback((string)$db, (string)$table, (string)$instance);
                }

            }

        }

        self::setInstance($old);

        return $out;
    }

    /**
     * handles repair mode.
     *
     * @param bool $repair value.
     * @param array $options value.
     *
     * @return array result.
     */
    public static function repairMode(bool $repair = false, array $options = []): array {
        $tables = self::eachAdminTable(function ($db, $table, $instance) use ($repair) {
            $checks = self::tableDeepCheck($db, $table);
            $fixed = false;

            if ($repair && !($checks["ok"] ?? false)) {
                $fixed = self::repairTable($db, $table);
            }

            return [
                "instance" => $instance,
                "database" => $db,
                "table" => $table,
                "checks" => $checks,
                "repaired" => $fixed
            ];
        }, (bool)($options["all_instances"] ?? true));

        $ok = true;

        foreach ($tables as $row) {
            if (!($row["checks"]["ok"] ?? false) && empty($row["repaired"])) {
                $ok = false;
            }

        }

        $report = [
            "ok" => $ok,
            "mode" => $repair ? "repair" : "check",
            "tables" => $tables,
            "created_at" => time()
        ];

        self::writeJsonConfig(self::adminOpsDir("reports/repair_mode_last.json", true), $report);

        self::adminLog("repair_mode", [
            "ok" => $ok,
            "repair" => $repair,
            "tables" => count($tables)
        ]);

        return $report;
    }

    /**
     * handles table deep check.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function tableDeepCheck(string $database, string $table): array {
        $checks = [
            "table_checksum" => self::verifyTableChecksum($database, $table),
            "row_checksum" => self::prepareRowChecksums($database, $table, false),
            "page_checksum" => self::verifyPageChecksums($database, $table),
            "index" => self::verifyIndexConsistency($database, $table),
            "foreign_keys" => self::verifyForeignKeys($database, $table),
            "schema" => self::verifySchemaCheck($database, $table),
            "wal" => self::verifyWalCheck($database, $table),
            "lock" => self::verifyLockCheck($database, $table),
            "storage" => self::verifyStorageCheck($database, $table)
        ];

        $ok = true;

        foreach ($checks as $check) {
            if (is_array($check) && ($check["ok"] ?? true) === false) {
                $ok = false;
            }

        }

        return [
            "ok" => $ok,
            "checks" => $checks,
            "checked_at" => time()
        ];
    }

    /**
     * handles verify table checksum.
     *
     * @param string $database value.
     * @param string $table value.
     * @param bool $updateMeta value.
     *
     * @return array result.
     */
    public static function verifyTableChecksum(string $database, string $table, bool $updateMeta = false): array {
        $file = self::makePath($database, $table);

        if (!is_file($file)) {
            return [
                "ok" => false,
                "error" => "table_not_found"
            ];
        }

        $append = self::appendFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $full = self::applyOps(self::ini($file), self::readAppendOps($append));
        $actual = GBDBStorage::checksum($full);
        $meta = self::readMeta($metaFile);
        $expected = (string)($meta["checksum"] ?? "");
        $ok = $expected === "" || hash_equals($expected, $actual);

        if (!$ok && $updateMeta) {
            $meta["checksum"] = $actual;
            self::writeMeta($metaFile, $meta);
            $ok = true;
        }

        return [
            "ok" => $ok,
            "expected" => $expected,
            "actual" => $actual,
            "rows" => max(0, count($full) - 1)
        ];
    }

    /**
     * handles prepare row checksums.
     *
     * @param string $database value.
     * @param string $table value.
     * @param bool $write value.
     *
     * @return array result.
     */
    public static function prepareRowChecksums(string $database, string $table, bool $write = true): array {
        $file = self::makePath($database, $table);

        if (!is_file($file)) {
            return [
                "ok" => false,
                "error" => "table_not_found"
            ];
        }

        $metaFile = self::metaFileForTable($database, $table);

        $rows = self::applyOps(
            self::ini($file),
            self::readAppendOps(self::appendFileForTable($database, $table))
        );

        $meta = self::readMeta($metaFile);
        $known = is_array($meta["row_checksums"] ?? null) ? $meta["row_checksums"] : [];
        $current = [];
        $mismatch = [];

        foreach ($rows as $i => $row) {
            if (!is_array($row) || ($i === 0 && self::isHeaderRow($row))) {
                continue;
            }

            $id = (string)($row["id"] ?? $i);
            $sum = GBDBStorage::checksum($row);
            $current[$id] = $sum;

            if (!$write && isset($known[$id]) && !hash_equals((string)$known[$id], $sum)) {
                $mismatch[] = $id;
            }

        }

        if ($write) {
            $meta["row_checksums"] = $current;
            $meta["row_checksums_at"] = time();

            self::writeMeta($metaFile, $meta);
        }

        return [
            "ok" => empty($mismatch),
            "prepared" => $write,
            "rows" => count($current),
            "mismatch" => $mismatch
        ];
    }

    /**
     * handles verify page checksums.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $chunkSize value.
     * @param bool $write value.
     *
     * @return array result.
     */
    public static function verifyPageChecksums(
        string $database,
        string $table,
        int $chunkSize = 500,
        bool $write = true
    ): array {
        $file = self::makePath($database, $table);

        if (!is_file($file)) {
            return [
                "ok" => false,
                "error" => "table_not_found"
            ];
        }

        $rows = self::applyOps(
            self::ini($file),
            self::readAppendOps(self::appendFileForTable($database, $table))
        );

        if (!empty($rows) && is_array($rows[0]) && self::isHeaderRow($rows[0])) {
            array_shift($rows);
        }

        $pages = [];

        foreach (array_chunk($rows, max(1, $chunkSize)) as $page => $chunk) {
            $pages[(string)$page] = GBDBStorage::checksum($chunk);
        }

        $metaFile = self::metaFileForTable($database, $table);
        $meta = self::readMeta($metaFile);
        $known = is_array($meta["page_checksums"] ?? null) ? $meta["page_checksums"] : [];
        $mismatch = [];

        foreach ($pages as $page => $sum) {
            if (isset($known[$page]) && !hash_equals((string)$known[$page], $sum)) {
                $mismatch[] = $page;
            }

        }

        if ($write) {
            $meta["page_checksums"] = $pages;
            $meta["page_checksum_chunk_size"] = $chunkSize;
            $meta["page_checksums_at"] = time();

            self::writeMeta($metaFile, $meta);
        }

        return [
            "ok" => empty($mismatch),
            "pages" => count($pages),
            "chunk_size" => $chunkSize,
            "mismatch" => $mismatch
        ];
    }

    /**
     * handles verify index consistency.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyIndexConsistency(string $database, string $table): array {
        return self::verifyIndexes($database, $table);
    }

    /**
     * handles verify foreign keys.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyForeignKeys(string $database, string $table): array {
        if (!method_exists(static::class, "checkOrphans")) {
            return [
                "ok" => true,
                "prepared" => true
            ];
        }

        $r = self::checkOrphans($database, $table);

        return [
            "ok" => empty($r["orphans"] ?? []),
            "report" => $r
        ];
    }

    /**
     * handles verify schema check.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifySchemaCheck(string $database, string $table): array {
        $keys = self::getKeys($database, $table);
        $schema = self::schemaTable($database, $table);
        $schemaCols = array_keys(is_array($schema["columns"] ?? null) ? $schema["columns"] : []);

        return [
            "ok" => empty(array_diff($schemaCols, $keys)),
            "table_keys" => $keys,
            "schema_columns" => $schemaCols,
            "missing_in_table" => array_values(array_diff($schemaCols, $keys)),
            "missing_in_schema" => array_values(array_diff(array_filter($keys, fn($k) => $k !== "id"), $schemaCols))
        ];
    }

    /**
     * handles verify wal check.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyWalCheck(string $database, string $table): array {
        $wal = self::appendFileForTable($database, $table) . ".wal";

        if (!is_file($wal)) {
            return [
                "ok" => true,
                "entries" => 0,
                "missing" => true
            ];
        }

        $bad = 0;
        $entries = 0;

        foreach (file($wal, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim((string)$line);

            if ($line === "") {
                continue;
            }

            $entries++;
            $decoded = GBDBStorage::decodeLine($line);
            $data = json_decode($decoded ?? $line, true);

            if (!is_array($data) || (empty($data["op"]) && empty($data["type"]))) {
                $bad++;
            }

        }

        return [
            "ok" => $bad === 0,
            "entries" => $entries,
            "invalid" => $bad,
            "file" => $wal
        ];
    }

    /**
     * handles verify lock check.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyLockCheck(string $database = "", string $table = ""): array {
        $files = [];

        if ($database !== "" && $table !== "") {
            $files[] = self::lockFileForTable($database, $table);
        } else {
            $files = glob(Vars::DB_PATH() . "**/*.lock") ?: [];
        }

        $stale = [];

        foreach ($files as $file) {
            if (is_file($file) && (int)@filemtime($file) < time() - 3600 && (int)@filesize($file) === 0) {
                $stale[] = $file;
            }

        }

        return [
            "ok" => empty($stale),
            "checked" => count($files),
            "stale" => $stale,
            "stats" => self::lockStats()
        ];
    }

    /**
     * handles verify storage check.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyStorageCheck(string $database, string $table): array {
        $file = self::makePath($database, $table);

        return is_file($file)
            ? GBDBStorage::verifyStorage($file)
            : [
                "ok" => false,
                "error" => "table_not_found"
            ];
    }

    /**
     * handles repair index.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function repairIndex(string $database, string $table): array {
        return self::repairIndexes($database, $table);
    }

    /**
     * handles repair wal.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function repairWal(string $database, string $table): array {
        return self::recoverTable($database, $table);
    }

    /**
     * handles repair relations.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function repairRelations(string $database, string $table): array {
        return method_exists(static::class, "repairConstraints")
            ? self::repairConstraints($database, $table)
            : [
                "ok" => true,
                "prepared" => true
            ];
    }

    /**
     * handles repair report.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function repairReport(string $database = "", string $table = ""): array {
        $r = $database !== "" && $table !== ""
            ? self::tableDeepCheck($database, $table)
            : self::repairMode(false);

        self::writeJsonConfig(self::adminOpsDir("reports/repair_report_last.json", true), $r);

        return $r;
    }

    /**
     * handles isolate corrupt files.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $reason value.
     *
     * @return array result.
     */
    public static function isolateCorruptFiles(string $database, string $table, string $reason = "manual"): array {
        $check = self::tableDeepCheck($database, $table);

        if (($check["ok"] ?? false) === true) {
            return [
                "ok" => true,
                "isolated" => false,
                "check" => $check
            ];
        }

        $file = self::makePath($database, $table);

        $target = self::adminOpsDir(
            "isolated/" . date("Ymd_His") . "_" . self::safeSegment($database) . "_" . self::safeSegment($table),
            true
        );

        if (!is_dir($target)) {
            @mkdir($target, 0777, true);
        }

        $copied = [];

        foreach (glob(dirname($file) . "/" . basename($file) . "*") ?: [] as $src) {
            $dst = $target . "/" . basename($src);

            if (@copy($src, $dst)) {
                $copied[] = $dst;
            }

        }

        self::writeJsonConfig($target . "/reason.json", [
            "reason" => $reason,
            "check" => $check,
            "created_at" => time()
        ]);

        return [
            "ok" => !empty($copied),
            "isolated" => true,
            "path" => $target,
            "files" => $copied,
            "check" => $check
        ];
    }

    /**
     * handles manual recovery tools.
     *
     * @return array result.
     */
    public static function manualRecoveryTools(): array {
        return [
            "ok" => true,
            "tools" => [
                "GBDB::repairMode(true)",
                "GBDB::recover()",
                "GBDB::recoverTable(\$db, \$table)",
                "GBDB::repairTable(\$db, \$table)",
                "GBDB::repairIndex(\$db, \$table)",
                "GBDB::isolateCorruptFiles(\$db, \$table)"
            ]
        ];
    }

    /**
     * handles auto compact.
     *
     * @param array|string $options value.
     * @param null|string $table value.
     *
     * @return array result.
     */
    public static function autoCompact(array|string $options = [], ?string $table = null): array {
        if (is_string($options)) {
            $db = $options;

            if ($table === null) {
                return [
                    "ok" => false,
                    "error" => "table_missing"
                ];
            }

            $meta = self::readMeta(self::metaFileForTable($db, $table));
            $ran = false;

            if (GBDBStorage::shouldCompact($meta, self::appendFileForTable($db, $table))) {
                $ran = self::compactTable($db, $table);
            }

            return [
                "ok" => true,
                "table" => $table,
                "compacted" => $ran
            ];
        }

        return self::autoMaintenance(array_replace(["compact" => true], $options));
    }

    /**
     * handles auto vacuum.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoVacuum(array $o = []): array {
        return self::autoMaintenance(array_replace(["vacuum" => true], $o));
    }

    /**
     * handles auto analyze.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoAnalyze(array $o = []): array {
        return self::refreshStats($o);
    }

    /**
     * handles auto index rebuild.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoIndexRebuild(array $o = []): array {
        return self::autoMaintenance(array_replace(["index_rebuild" => true], $o));
    }

    /**
     * handles auto index repair.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoIndexRepair(array $o = []): array {
        return self::autoMaintenance(array_replace(["index_repair" => true], $o));
    }

    /**
     * handles auto journal cleanup.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoJournalCleanup(array $o = []): array {
        return self::cleanupJournals((int)($o["days"] ?? 7));
    }

    /**
     * handles auto version cleanup.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoVersionCleanup(array $o = []): array {
        return self::cleanupVersions((int)($o["days"] ?? 30));
    }

    /**
     * handles auto orphan cleanup.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoOrphanCleanup(array $o = []): array {
        return self::autoMaintenance(array_replace(["orphan_cleanup" => true], $o));
    }

    /**
     * handles auto stats refresh.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoStatsRefresh(array $o = []): array {
        return self::refreshStats($o);
    }

    /**
     * handles auto maintenance.
     *
     * @param array $options value.
     *
     * @return array result.
     */
    public static function autoMaintenance(array $options = []): array {
        $done = self::eachAdminTable(function ($db, $table, $instance) use ($options) {
            $ops = [];

            if (!empty($options["compact"]) || !empty($options["vacuum"])) {
                $ops["compact"] = self::compactTable($db, $table);
            }

            if (!empty($options["index_rebuild"])) {
                $ops["index_rebuild"] = self::rebuildIndexes($db, $table);
            }

            if (!empty($options["index_repair"])) {
                $ops["index_repair"] = self::repairIndexes($db, $table);
            }

            if (!empty($options["orphan_cleanup"]) && method_exists(static::class, "repairOrphans")) {
                $ops["orphan_cleanup"] = self::repairOrphans(
                    $db,
                    $table,
                    (string)($options["orphan_mode"] ?? "report")
                );
            }

            $ops["stats"] = self::tableStats($db, $table);

            return [
                "instance" => $instance,
                "database" => $db,
                "table" => $table,
                "ops" => $ops
            ];
        }, (bool)($options["all_instances"] ?? true));

        self::adminLog("auto_maintenance", ["tables" => count($done)]);

        return [
            "ok" => true,
            "done" => $done,
            "created_at" => time()
        ];
    }

    /**
     * handles maintenance scheduler.
     *
     * @param array $config value.
     *
     * @return array result.
     */
    public static function maintenanceScheduler(array $config = []): array {
        $cfg = array_replace([
            "enabled" => true,
            "interval_minutes" => 60,
            "window" => [
                "start" => "02:00",
                "end" => "04:00"
            ],
            "tasks" => [
                "compact",
                "stats",
                "journal_cleanup"
            ],
            "updated_at" => time()
        ], $config);

        self::writeJsonConfig(self::adminOpsDir("maintenance/scheduler.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles maintenance window.
     *
     * @param null|string $start value.
     * @param null|string $end value.
     *
     * @return array result.
     */
    public static function maintenanceWindow(?string $start = null, ?string $end = null): array {
        $cfg = self::readJsonConfig(self::adminOpsDir("maintenance/scheduler.json", true), []);
        $w = is_array($cfg["window"] ?? null)
            ? $cfg["window"]
            : [
                "start" => "02:00",
                "end" => "04:00"
            ];

        if ($start !== null) {
            $w["start"] = $start;
        }

        if ($end !== null) {
            $w["end"] = $end;
        }

        $cfg["window"] = $w;
        $cfg["updated_at"] = time();

        self::writeJsonConfig(self::adminOpsDir("maintenance/scheduler.json", true), $cfg);

        return $w;
    }

    /**
     * handles maintenance logs.
     *
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function maintenanceLogs(int $limit = 100): array {
        $files = glob(self::adminOpsDir("logs", true) . "/*.log") ?: [];
        rsort($files, SORT_NATURAL);

        $rows = [];

        foreach ($files as $file) {
            foreach (array_reverse(file($file, FILE_IGNORE_NEW_LINES) ?: []) as $line) {
                $data = json_decode($line, true);

                if (is_array($data)) {
                    $rows[] = $data;
                }

                if (count($rows) >= $limit) {
                    break 2;
                }

            }

        }

        return [
            "ok" => true,
            "logs" => $rows,
            "limit" => $limit
        ];
    }

    /**
     * handles maintenance cli.
     *
     * @return array result.
     */
    public static function maintenanceCli(): array {
        return self::storeSystemPlan("maintenance_cli", [
            "type" => "maintenance_cli",
            "commands" => [
                "repair",
                "health",
                "compact",
                "backup",
                "stats"
            ],
            "status" => "prepared",
            "created_at" => time()
        ]);
    }

    /**
     * handles maintenance ui.
     *
     * @return array result.
     */
    public static function maintenanceUi(): array {
        return self::storeSystemPlan("maintenance_ui", [
            "type" => "maintenance_ui",
            "pages" => [
                "health",
                "repair",
                "metrics",
                "cache",
                "counters"
            ],
            "status" => "prepared",
            "created_at" => time()
        ]);
    }

    /**
     * handles delete by retention.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $column value.
     * @param int $days value.
     *
     * @return array result.
     */
    public static function deleteByRetention(
        string $database,
        string $table,
        string $column = "created_at",
        int $days = 365
    ): array {
        $rows = self::getData($database, $table);
        $limit = time() - max(1, $days) * 86400;
        $deleted = 0;

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || !isset($row["id"], $row[$column])) {
                continue;
            }

            $ts = is_numeric($row[$column])
                ? (int)$row[$column]
                : strtotime((string)$row[$column]);

            if ($ts > 0 && $ts < $limit && self::deleteData($database, $table, "id", $row["id"])) {
                $deleted++;
            }

        }

        return [
            "ok" => true,
            "deleted" => $deleted,
            "days" => $days,
            "column" => $column
        ];
    }

    /**
     * handles auto backup.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function autoBackup(array $o = []): array {
        return self::fullBackup((string)($o["target"] ?? ""));
    }

    /**
     * handles db health.
     *
     * @param bool $allInstances value.
     *
     * @return array result.
     */
    public static function dbHealth(bool $allInstances = true): array {
        $r = self::repairMode(false, ["all_instances" => $allInstances]);

        return [
            "ok" => $r["ok"],
            "tables" => count($r["tables"] ?? []),
            "report" => $r
        ];
    }

    /**
     * handles table stats.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function tableStats(string $database, string $table): array {
        $h = self::health($database, $table);

        return [
            "ok" => $h["ok"] ?? false,
            "rows" => $h["rows_real"] ?? 0,
            "append_ops" => $h["append_ops_real"] ?? 0,
            "warnings" => $h["warnings"] ?? [],
            "errors" => $h["errors"] ?? [],
            "meta" => $h["meta"] ?? []
        ];
    }

    /**
     * handles index stats.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function indexStats(string $database, string $table): array {
        $v = self::verifyIndexes($database, $table);

        return [
            "ok" => $v["ok"] ?? false,
            "count" => count($v["indexes"] ?? []),
            "indexes" => $v["indexes"] ?? [],
            "errors" => $v["errors"] ?? []
        ];
    }

    /**
     * handles query stats.
     *
     * @return array result.
     */
    public static function queryStats(): array {
        return self::readJsonConfig(self::adminOpsDir("metrics/query_stats.json", true), ["queries" => 0]);
    }

    /**
     * handles wal stats.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function walStats(string $database, string $table): array {
        return self::verifyWalCheck($database, $table);
    }

    /**
     * handles replication stats.
     *
     * @return array result.
     */
    public static function replicationStats(): array {
        return method_exists(static::class, "replicaLag")
            ? self::replicaLag()
            : [
                "ok" => true,
                "prepared" => true
            ];
    }

    /**
     * handles backup stats.
     *
     * @return array result.
     */
    public static function backupStats(): array {
        $dirs = glob(self::dbRootPath(".backups/*/*"), GLOB_ONLYDIR) ?: [];

        return [
            "ok" => true,
            "count" => count($dirs),
            "latest" => empty($dirs) ? null : max(array_map("filemtime", $dirs))
        ];
    }

    /**
     * handles error log.
     *
     * @param string $message value.
     * @param array $context value.
     *
     * @return array result.
     */
    public static function errorLog(string $message = "", array $context = []): array {
        if ($message !== "") {
            self::adminLog("error", [
                "message" => $message,
                "context" => $context
            ]);
        }

        return self::maintenanceLogs(100);
    }

    /**
     * handles audit log.
     *
     * @param string $action value.
     * @param array $payload value.
     *
     * @return array result.
     */
    public static function auditLog(string $action = "", array $payload = []): array {
        if ($action !== "") {
            self::adminLog("audit", [
                "action" => $action,
                "payload" => $payload
            ]);
        }

        return [
            "ok" => true,
            "prepared" => true,
            "action" => $action
        ];
    }

    /**
     * handles record query metric.
     *
     * @param string $type value.
     * @param float $ms value.
     * @param int $rowsScanned value.
     * @param int $rowsReturned value.
     * @param bool $fullScan value.
     * @param int $memory value.
     *
     * @return void result.
     */
    public static function recordQueryMetric(
        string $type,
        float $ms,
        int $rowsScanned = 0,
        int $rowsReturned = 0,
        bool $fullScan = false,
        int $memory = 0
    ): void {
        $file = self::adminOpsDir("metrics/query_stats.json", true);

        $s = self::readJsonConfig($file, [
            "queries" => 0,
            "total_ms" => 0.0,
            "samples" => [],
            "rows_scanned" => 0,
            "rows_returned" => 0,
            "full_scans" => 0
        ]);

        $s["queries"] = (int)$s["queries"] + 1;
        $s["total_ms"] = (float)$s["total_ms"] + $ms;
        $s["rows_scanned"] = (int)$s["rows_scanned"] + $rowsScanned;
        $s["rows_returned"] = (int)$s["rows_returned"] + $rowsReturned;

        if ($fullScan) {
            $s["full_scans"] = (int)$s["full_scans"] + 1;
        }

        $s["samples"][] = [
            "type" => $type,
            "ms" => $ms,
            "rows_scanned" => $rowsScanned,
            "rows_returned" => $rowsReturned,
            "full_scan" => $fullScan,
            "memory" => $memory,
            "ts" => time()
        ];

        $s["samples"] = array_slice($s["samples"], -500);

        self::writeJsonConfig($file, $s);
    }

    /**
     * handles performance metrics.
     *
     * @return array result.
     */
    public static function performanceMetrics(): array {
        $s = self::queryStats();
        $samples = is_array($s["samples"] ?? null) ? $s["samples"] : [];
        $times = array_map(fn($x) => (float)($x["ms"] ?? 0), $samples);

        sort($times);

        $avg = empty($times) ? 0 : array_sum($times) / count($times);

        $p = function ($pct) use ($times) {
            if (empty($times)) {
                return 0.0;
            }

            $i = (int)floor((count($times) - 1) * $pct);

            return (float)$times[$i];
        };

        return [
            "ok" => true,
            "queries" => (int)($s["queries"] ?? 0),
            "query_time_avg" => $avg,
            "query_time_p95" => $p(.95),
            "query_time_p99" => $p(.99),
            "rows_scanned" => (int)($s["rows_scanned"] ?? 0),
            "rows_returned" => (int)($s["rows_returned"] ?? 0),
            "full_table_scans" => (int)($s["full_scans"] ?? 0),
            "index_hit_rate" => self::indexHitRate(),
            "cache_hit_rate" => self::cacheHitRate()
        ];
    }

    /**
     * handles ops per minute.
     *
     * @return array result.
     */
    public static function opsPerMinute(): array {
        $samples = self::queryStats()["samples"] ?? [];
        $min = time() - 60;
        $n = 0;

        foreach ($samples as $s) {
            if ((int)($s["ts"] ?? 0) >= $min) {
                $n++;
            }

        }

        return [
            "ok" => true,
            "ops_per_minute" => $n
        ];
    }

    /**
     * handles query time average.
     *
     * @return float result.
     */
    public static function queryTimeAverage(): float {
        return (float)self::performanceMetrics()["query_time_avg"];
    }

    /**
     * handles query time percentiles.
     *
     * @return array result.
     */
    public static function queryTimePercentiles(): array {
        $m = self::performanceMetrics();

        return [
            "p95" => $m["query_time_p95"],
            "p99" => $m["query_time_p99"]
        ];
    }

    /**
     * handles memory usage per query.
     *
     * @return array result.
     */
    public static function memoryUsagePerQuery(): array {
        return array_map(
            fn($x) => [
                "memory" => (int)($x["memory"] ?? 0),
                "ts" => (int)($x["ts"] ?? 0)
            ],
            self::queryStats()["samples"] ?? []
        );
    }

    /**
     * handles rows scanned.
     *
     * @return int result.
     */
    public static function rowsScanned(): int {
        return (int)(self::queryStats()["rows_scanned"] ?? 0);
    }

    /**
     * handles rows returned.
     *
     * @return int result.
     */
    public static function rowsReturned(): int {
        return (int)(self::queryStats()["rows_returned"] ?? 0);
    }

    /**
     * handles full table scan count.
     *
     * @return int result.
     */
    public static function fullTableScanCount(): int {
        return (int)(self::queryStats()["full_scans"] ?? 0);
    }

    /**
     * handles index hit rate.
     *
     * @return float result.
     */
    public static function indexHitRate(): float {
        $s = self::queryStats();
        $q = max(1, (int)($s["queries"] ?? 0));

        return max(0.0, min(1.0, 1.0 - ((int)($s["full_scans"] ?? 0) / $q)));
    }

    /**
     * handles dashboard data.
     *
     * @return array result.
     */
    public static function dashboardData(): array {
        return [
            "health" => self::dbHealth(false),
            "metrics" => self::performanceMetrics(),
            "cache" => self::cacheStats(),
            "backup" => self::backupStats(),
            "alerts" => self::adminAlerts()
        ];
    }

    /**
     * handles admin alerts.
     *
     * @return array result.
     */
    public static function adminAlerts(): array {
        $alerts = [];
        $h = self::dbHealth(false);

        if (!($h["ok"] ?? true)) {
            $alerts[] = [
                "level" => "danger",
                "message" => "GBDB Health Check fehlgeschlagen"
            ];
        }

        return [
            "ok" => true,
            "alerts" => $alerts
        ];
    }

    /**
     * handles query safety.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function querySafety(array $o = []): array {
        return self::queryOptions($o);
    }

    /**
     * handles set max execution time.
     *
     * @param int $s value.
     *
     * @return array result.
     */
    public static function setMaxExecutionTime(int $s): array {
        return self::queryOptions(["timeout" => $s]);
    }

    /**
     * handles set max scan rows.
     *
     * @param int $rows value.
     *
     * @return array result.
     */
    public static function setMaxScanRows(int $rows): array {
        $cfg = self::readJsonConfig(self::adminOpsDir("safety.json", true), []);
        $cfg["max_scan_rows"] = max(1, $rows);

        self::writeJsonConfig(self::adminOpsDir("safety.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles set max export size.
     *
     * @param int $bytes value.
     *
     * @return array result.
     */
    public static function setMaxExportSize(int $bytes): array {
        $cfg = self::readJsonConfig(self::adminOpsDir("safety.json", true), []);
        $cfg["max_export_size"] = max(1024, $bytes);

        self::writeJsonConfig(self::adminOpsDir("safety.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles block expensive queries.
     *
     * @param bool $a value.
     *
     * @return array result.
     */
    public static function blockExpensiveQueries(bool $a = true): array {
        return self::queryOptions(["block_full_scan" => $a]);
    }

    /**
     * handles admin only heavy queries.
     *
     * @param bool $a value.
     *
     * @return array result.
     */
    public static function adminOnlyHeavyQueries(bool $a = true): array {
        $cfg = self::readJsonConfig(self::adminOpsDir("safety.json", true), []);
        $cfg["admin_only_heavy_queries"] = $a;

        self::writeJsonConfig(self::adminOpsDir("safety.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles query sandbox.
     *
     * @param array $c value.
     *
     * @return array result.
     */
    public static function querySandbox(array $c = []): array {
        $cfg = array_replace([
            "enabled" => true,
            "readonly" => true,
            "max_rows" => 1000,
            "created_at" => time()
        ], $c);

        self::writeJsonConfig(self::adminOpsDir("sandbox.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles rate limit query type.
     *
     * @param string $type value.
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function rateLimitQueryType(string $type, int $limit): array {
        $cfg = self::readJsonConfig(self::adminOpsDir("rate_limits.json", true), []);
        $cfg[self::safeSegment($type)] = max(1, $limit);

        self::writeJsonConfig(self::adminOpsDir("rate_limits.json", true), $cfg);

        return $cfg;
    }

    private static function adminCacheKey(string $type, array $payload): string {
        return $type . ":" . hash(
            "sha256",
            self::getInstance() . "|" . json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * handles cache set.
     *
     * @param string $key value.
     * @param mixed $value value.
     * @param int $ttl value.
     * @param array $tags value.
     *
     * @return bool result.
     */
    public static function cacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []): bool {
        self::$adminCache[$key] = [
            "value" => $value,
            "expires" => time() + max(1, $ttl),
            "hits" => 0,
            "tags" => $tags
        ];

        foreach ($tags as $tag) {
            self::$adminCacheTags[(string)$tag][$key] = true;
        }

        return self::fileCacheSet($key, $value, $ttl, $tags);
    }

    /**
     * handles cache get.
     *
     * @param string $key value.
     * @param mixed $default value.
     *
     * @return mixed result.
     */
    public static function cacheGet(string $key, mixed $default = null): mixed {
        if (isset(self::$adminCache[$key]) && (int)self::$adminCache[$key]["expires"] >= time()) {
            self::$adminCache[$key]["hits"]++;

            return self::$adminCache[$key]["value"];
        }

        return self::fileCacheGet($key, $default);
    }

    /**
     * handles cache invalidate.
     *
     * @param string $key value.
     *
     * @return void result.
     */
    public static function cacheInvalidate(string $key): void {
        unset(self::$adminCache[$key]);

        @unlink(self::adminOpsDir("cache/" . hash("sha256", $key) . ".json", true));
    }

    /**
     * handles cache invalidate tag.
     *
     * @param string $tag value.
     *
     * @return int result.
     */
    public static function cacheInvalidateTag(string $tag): int {
        $n = 0;

        foreach (array_keys(self::$adminCacheTags[$tag] ?? []) as $key) {
            self::cacheInvalidate($key);
            $n++;
        }

        return $n;
    }

    /**
     * handles row cache.
     *
     * @param string $db value.
     * @param string $table value.
     * @param mixed $id value.
     * @param int $ttl value.
     *
     * @return mixed result.
     */
    public static function rowCache(string $db, string $table, mixed $id, int $ttl = 60): mixed {
        $key = self::adminCacheKey("row", compact("db", "table", "id"));
        $v = self::cacheGet($key, null);

        if ($v !== null) {
            return $v;
        }

        $v = self::getData($db, $table, true, "id", $id);

        self::cacheSet($key, $v, $ttl, [
            "table:$db:$table",
            "row:$db:$table:$id"
        ]);

        return $v;
    }

    /**
     * handles table meta cache.
     *
     * @param string $db value.
     * @param string $table value.
     * @param int $ttl value.
     *
     * @return array result.
     */
    public static function tableMetaCache(string $db, string $table, int $ttl = 60): array {
        $key = self::adminCacheKey("meta", compact("db", "table"));
        $v = self::cacheGet($key, null);

        if (is_array($v)) {
            return $v;
        }

        $v = self::meta($db, $table);

        self::cacheSet($key, $v, $ttl, [
            "table:$db:$table",
            "meta"
        ]);

        return $v;
    }

    /**
     * handles schema cache.
     *
     * @param string $db value.
     * @param string $table value.
     * @param int $ttl value.
     *
     * @return array result.
     */
    public static function schemaCache(string $db, string $table, int $ttl = 60): array {
        $key = self::adminCacheKey("schema", compact("db", "table"));
        $v = self::cacheGet($key, null);

        if (is_array($v)) {
            return $v;
        }

        $v = self::schemaTable($db, $table);

        self::cacheSet($key, $v, $ttl, [
            "schema",
            "table:$db:$table"
        ]);

        return $v;
    }

    /**
     * handles index cache.
     *
     * @param string $db value.
     * @param string $table value.
     * @param int $ttl value.
     *
     * @return array result.
     */
    public static function indexCache(string $db, string $table, int $ttl = 60): array {
        $key = self::adminCacheKey("index", compact("db", "table"));
        $v = self::cacheGet($key, null);

        if (is_array($v)) {
            return $v;
        }

        $v = self::listIndexes($db, $table);

        self::cacheSet($key, $v, $ttl, [
            "index",
            "table:$db:$table"
        ]);

        return $v;
    }

    /**
     * handles query result cache.
     *
     * @param string $key value.
     * @param callable $cb value.
     * @param int $ttl value.
     * @param array $tags value.
     *
     * @return mixed result.
     */
    public static function queryResultCache(string $key, callable $cb, int $ttl = 60, array $tags = []): mixed {
        $v = self::cacheGet($key, null);

        if ($v !== null) {
            return $v;
        }

        $v = $cb();

        self::cacheSet($key, $v, $ttl, $tags);

        return $v;
    }

    /**
     * handles permission cache.
     *
     * @param string $k value.
     * @param mixed $v value.
     * @param int $ttl value.
     *
     * @return mixed result.
     */
    public static function permissionCache(string $k, mixed $v = null, int $ttl = 300): mixed {
        if ($v !== null) {
            self::cacheSet("perm:" . $k, $v, $ttl, ["permissions"]);
        }

        return self::cacheGet("perm:" . $k);
    }

    /**
     * handles fulltext cache.
     *
     * @param string $k value.
     * @param mixed $v value.
     * @param int $ttl value.
     *
     * @return mixed result.
     */
    public static function fulltextCache(string $k, mixed $v = null, int $ttl = 300): mixed {
        if ($v !== null) {
            self::cacheSet("fulltext:" . $k, $v, $ttl, ["fulltext"]);
        }

        return self::cacheGet("fulltext:" . $k);
    }

    /**
     * handles counter cache.
     *
     * @param string $k value.
     * @param mixed $v value.
     * @param int $ttl value.
     *
     * @return mixed result.
     */
    public static function counterCache(string $k, mixed $v = null, int $ttl = 300): mixed {
        if ($v !== null) {
            self::cacheSet("counter:" . $k, $v, $ttl, ["counter"]);
        }

        return self::cacheGet("counter:" . $k);
    }

    /**
     * handles cache ttl.
     *
     * @param string $key value.
     *
     * @return int result.
     */
    public static function cacheTtl(string $key): int {
        $v = self::$adminCache[$key] ?? null;

        return is_array($v)
            ? max(0, (int)$v["expires"] - time())
            : 0;
    }

    /**
     * handles cache stats.
     *
     * @return array result.
     */
    public static function cacheStats(): array {
        $hits = 0;

        foreach (self::$adminCache as $i) {
            $hits += (int)($i["hits"] ?? 0);
        }

        return [
            "ok" => true,
            "entries" => count(self::$adminCache),
            "hits" => $hits,
            "file_entries" => count(glob(self::adminOpsDir("cache", true) . "/*.json") ?: [])
        ];
    }

    /**
     * handles cache hit rate.
     *
     * @return float result.
     */
    public static function cacheHitRate(): float {
        $s = self::cacheStats();

        return min(1.0, (float)$s["hits"] / max(1, (int)$s["entries"]));
    }

    /**
     * handles cache warmup.
     *
     * @param array $items value.
     *
     * @return array result.
     */
    public static function cacheWarmup(array $items = []): array {
        $done = 0;

        foreach ($items as $item) {
            if (is_array($item) && isset($item["db"], $item["table"])) {
                self::tableMetaCache($item["db"], $item["table"]);
                self::schemaCache($item["db"], $item["table"]);
                $done++;
            }

        }

        return [
            "ok" => true,
            "warmed" => $done
        ];
    }

    /**
     * handles cache repair.
     *
     * @return array result.
     */
    public static function cacheRepair(): array {
        $bad = 0;

        foreach (glob(self::adminOpsDir("cache", true) . "/*.json") ?: [] as $file) {
            $d = json_decode((string)@file_get_contents($file), true);

            if (!is_array($d) || (int)($d["expires"] ?? 0) < time()) {
                @unlink($file);
                $bad++;
            }

        }

        return [
            "ok" => true,
            "removed" => $bad
        ];
    }

    /**
     * handles apcu cache prepared.
     *
     * @return array result.
     */
    public static function apcuCachePrepared(): array {
        return [
            "ok" => true,
            "available" => function_exists("apcu_fetch"),
            "driver" => "apcu",
            "status" => "prepared"
        ];
    }

    /**
     * handles redis adapter prepared.
     *
     * @param array $c value.
     *
     * @return array result.
     */
    public static function redisAdapterPrepared(array $c = []): array {
        self::writeJsonConfig(
            self::adminOpsDir("cache/redis.json", true),
            array_replace([
                "enabled" => false,
                "host" => "127.0.0.1",
                "port" => 6379
            ], $c)
        );

        return [
            "ok" => true,
            "driver" => "redis",
            "status" => "prepared"
        ];
    }

    /**
     * handles file cache set.
     *
     * @param string $key value.
     * @param mixed $value value.
     * @param int $ttl value.
     * @param array $tags value.
     *
     * @return bool result.
     */
    public static function fileCacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []): bool {
        $file = self::adminOpsDir("cache/" . hash("sha256", $key) . ".json", true);
        $json = json_encode([
            "key" => $key,
            "value" => $value,
            "expires" => time() + max(1, $ttl),
            "tags" => $tags
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false && GBDBStorage::atomicWrite($file, $json);
    }

    /**
     * handles file cache get.
     *
     * @param string $key value.
     * @param mixed $default value.
     *
     * @return mixed result.
     */
    public static function fileCacheGet(string $key, mixed $default = null): mixed {
        $file = self::adminOpsDir("cache/" . hash("sha256", $key) . ".json", true);
        $d = is_file($file)
            ? json_decode((string)@file_get_contents($file), true)
            : null;

        if (!is_array($d) || (int)($d["expires"] ?? 0) < time()) {
            return $default;
        }

        return $d["value"] ?? $default;
    }

    /**
     * handles cache tests.
     *
     * @return array result.
     */
    public static function cacheTests(): array {
        self::cacheSet("__test", ["ok" => true], 5, ["test"]);

        $ok = is_array(self::cacheGet("__test"));

        self::cacheInvalidate("__test");

        return [
            "ok" => $ok && self::cacheGet("__test") === null
        ];
    }

    /**
     * handles atomic increment.
     *
     * @param string $db value.
     * @param string $table value.
     * @param mixed $id value.
     * @param string $column value.
     * @param int|float $step value.
     *
     * @return array result.
     */
    public static function atomicIncrement(
        string $db,
        string $table,
        mixed $id,
        string $column,
        int|float $step = 1
    ): array {
        return self::atomicCounterChange($db, $table, $id, $column, $step);
    }

    /**
     * handles atomic decrement.
     *
     * @param string $db value.
     * @param string $table value.
     * @param mixed $id value.
     * @param string $column value.
     * @param int|float $step value.
     *
     * @return array result.
     */
    public static function atomicDecrement(
        string $db,
        string $table,
        mixed $id,
        string $column,
        int|float $step = 1
    ): array {
        return self::atomicCounterChange($db, $table, $id, $column, -abs($step));
    }

    private static function atomicCounterChange(
        string $db,
        string $table,
        mixed $id,
        string $column,
        int|float $step
    ): array {
        $row = self::getData($db, $table, true, "id", $id);

        if (!is_array($row) || empty($row)) {
            return [
                "ok" => false,
                "error" => "row_not_found"
            ];
        }

        $old = is_numeric($row[$column] ?? 0) ? $row[$column] + 0 : 0;
        $new = $old + $step;
        $ok = self::editData($db, $table, "id", $id, [$column => $new]);

        if ($ok) {
            self::cacheInvalidateTag("counter");
        }

        return [
            "ok" => $ok,
            "old" => $old,
            "new" => $new,
            "column" => $column
        ];
    }

    /**
     * handles compare and swap.
     *
     * @param string $db value.
     * @param string $table value.
     * @param mixed $id value.
     * @param string $column value.
     * @param mixed $expected value.
     * @param mixed $newValue value.
     *
     * @return array result.
     */
    public static function compareAndSwap(
        string $db,
        string $table,
        mixed $id,
        string $column,
        mixed $expected,
        mixed $newValue
    ): array {
        $row = self::getData($db, $table, true, "id", $id);

        if (!is_array($row) || empty($row)) {
            return [
                "ok" => false,
                "error" => "row_not_found"
            ];
        }

        if (($row[$column] ?? null) !== $expected) {
            return [
                "ok" => false,
                "swapped" => false,
                "current" => $row[$column] ?? null
            ];
        }

        $ok = self::editData($db, $table, "id", $id, [$column => $newValue]);

        return [
            "ok" => $ok,
            "swapped" => $ok,
            "old" => $expected,
            "new" => $newValue
        ];
    }

    /**
     * handles ensure counter table.
     *
     * @param string $db value.
     * @param string $table value.
     *
     * @return bool result.
     */
    public static function ensureCounterTable(string $db = "system", string $table = "counters"): bool {
        if (!in_array($db, self::listDBs(), true)) {
            self::createDatabase($db);
        }

        if (!in_array($table, self::listTables($db), true)) {
            return self::createTable($db, $table, [
                "scope",
                "name",
                "value",
                "updated_at"
            ]);
        }

        return true;
    }

    /**
     * handles counter value.
     *
     * @param string $scope value.
     * @param string $name value.
     * @param int|float $delta value.
     * @param string $db value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function counterValue(
        string $scope,
        string $name,
        int|float $delta = 0,
        string $db = "system",
        string $table = "counters"
    ): array {
        self::ensureCounterTable($db, $table);

        $key = self::safeSegment($scope) . ":" . self::safeSegment($name);
        $row = self::getData($db, $table, true, "name", $key);

        if (!is_array($row) || empty($row)) {
            $id = self::insertData($db, $table, [
                "scope" => $scope,
                "name" => $key,
                "value" => 0,
                "updated_at" => time()
            ]);

            $row = self::getData($db, $table, true, "id", $id);
        }

        if ($delta != 0) {
            return self::atomicIncrement($db, $table, $row["id"], "value", $delta);
        }

        return [
            "ok" => true,
            "value" => $row["value"] ?? 0,
            "row" => $row
        ];
    }

    /**
     * handles prepare sharded counters.
     *
     * @param int $shards value.
     *
     * @return array result.
     */
    public static function prepareShardedCounters(int $shards = 16): array {
        $cfg = [
            "enabled" => true,
            "shards" => max(1, $shards),
            "updated_at" => time()
        ];

        self::writeJsonConfig(self::adminOpsDir("counters/sharded.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles prepare distributed safe counters.
     *
     * @param array $c value.
     *
     * @return array result.
     */
    public static function prepareDistributedSafeCounters(array $c = []): array {
        $cfg = array_replace([
            "enabled" => false,
            "driver" => "local-lock",
            "status" => "prepared"
        ], $c);

        self::writeJsonConfig(self::adminOpsDir("counters/distributed.json", true), $cfg);

        return $cfg;
    }

    /**
     * handles like counter.
     *
     * @param string $o value.
     * @param int $d value.
     *
     * @return array result.
     */
    public static function likeCounter(string $o, int $d = 1): array {
        return self::counterValue("likes", $o, $d);
    }

    /**
     * handles comment counter.
     *
     * @param string $o value.
     * @param int $d value.
     *
     * @return array result.
     */
    public static function commentCounter(string $o, int $d = 1): array {
        return self::counterValue("comments", $o, $d);
    }

    /**
     * handles view counter.
     *
     * @param string $o value.
     * @param int $d value.
     *
     * @return array result.
     */
    public static function viewCounter(string $o, int $d = 1): array {
        return self::counterValue("views", $o, $d);
    }

    /**
     * handles follower counter.
     *
     * @param string $o value.
     * @param int $d value.
     *
     * @return array result.
     */
    public static function followerCounter(string $o, int $d = 1): array {
        return self::counterValue("followers", $o, $d);
    }

    /**
     * handles unread counter.
     *
     * @param string $o value.
     * @param int $d value.
     *
     * @return array result.
     */
    public static function unreadCounter(string $o, int $d = 1): array {
        return self::counterValue("unread", $o, $d);
    }

    /**
     * handles counter repair.
     *
     * @param string $db value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function counterRepair(string $db = "system", string $table = "counters"): array {
        self::ensureCounterTable($db, $table);

        $fixed = 0;

        foreach (self::getData($db, $table) ?: [] as $row) {
            if (!is_array($row) || !isset($row["id"])) {
                continue;
            }

            $v = is_numeric($row["value"] ?? null) ? $row["value"] + 0 : 0;

            if (($row["value"] ?? null) !== $v) {
                self::editData($db, $table, "id", $row["id"], [
                    "value" => $v,
                    "updated_at" => time()
                ]);

                $fixed++;
            }

        }

        return [
            "ok" => true,
            "fixed" => $fixed
        ];
    }

    /**
     * handles counter recalculation.
     *
     * @param string $db value.
     * @param string $table value.
     * @param string $groupColumn value.
     * @param string $scope value.
     *
     * @return array result.
     */
    public static function counterRecalculation(
        string $db,
        string $table,
        string $groupColumn,
        string $scope
    ): array {
        $counts = [];

        foreach (self::getData($db, $table) ?: [] as $row) {
            if (is_array($row) && isset($row[$groupColumn])) {
                $counts[(string)$row[$groupColumn]] = ($counts[(string)$row[$groupColumn]] ?? 0) + 1;
            }

        }

        foreach ($counts as $name => $value) {
            $cur = self::counterValue($scope, $name, 0);

            if (!empty($cur["row"]["id"])) {
                self::compareAndSwap(
                    "system",
                    "counters",
                    $cur["row"]["id"],
                    "value",
                    $cur["row"]["value"] ?? 0,
                    $value
                );
            }

        }

        return [
            "ok" => true,
            "scope" => $scope,
            "counts" => $counts
        ];
    }

    /**
     * handles cleanup journals.
     *
     * @param int $days value.
     *
     * @return array result.
     */
    public static function cleanupJournals(int $days = 7): array {
        return self::cleanupFilesByAge(["*.wal", "*.journal"], $days);
    }

    /**
     * handles cleanup versions.
     *
     * @param int $days value.
     *
     * @return array result.
     */
    public static function cleanupVersions(int $days = 30): array {
        return self::cleanupFilesByAge(["*.old", "*.bak"], $days);
    }

    private static function cleanupFilesByAge(array $patterns, int $days): array {
        $deleted = [];
        $limit = time() - max(1, $days) * 86400;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(Vars::DB_PATH(), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $file->getFilename()) && (int)$file->getMTime() < $limit && @unlink($file->getPathname())) {
                    $deleted[] = $file->getPathname();
                }

            }

        }

        return [
            "ok" => true,
            "deleted" => $deleted,
            "days" => $days
        ];
    }

    /**
     * handles refresh stats.
     *
     * @param array $o value.
     *
     * @return array result.
     */
    public static function refreshStats(array $o = []): array {
        $tables = self::eachAdminTable(
            fn($db, $table, $instance) => [
                "instance" => $instance,
                "database" => $db,
                "table" => $table,
                "stats" => self::tableStats($db, $table),
                "index" => self::indexStats($db, $table)
            ],
            (bool)($o["all_instances"] ?? true)
        );

        $r = [
            "ok" => true,
            "tables" => $tables,
            "created_at" => time()
        ];

        self::writeJsonConfig(self::adminOpsDir("reports/stats_last.json", true), $r);

        return $r;
    }

}
