<?php

trait GBDB_StorageTrait {

    private static function instancePath(bool $ensure = false): string {
        $instance = self::instanceName();

        if (Vars::crypt_data()) {
            $instanceToken = self::getInstanceToken($instance, $ensure);

            if ($instanceToken === null) {
                return Vars::DB_PATH() . "__missing__/";
            }

            $instance = $instanceToken;
        }

        return Vars::DB_PATH() . $instance . "/";
    }

    private static function makePath(string $database, string $table, bool $ensure = false): string {
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if (Vars::crypt_data()) {
            $dbToken = self::getDbToken($database, $ensure);
            $tbToken = self::getTableToken($database, $table, $ensure);

            if ($dbToken === null || $tbToken === null) {
                return Vars::DB_PATH() . "__missing__/" . "__missing__" . Vars::data_extension();
            }

            $database = $dbToken;
            $table = $tbToken;
        }

        return self::instancePath($ensure) . $database . "/" . $table . Vars::data_extension();
    }

    private static function ini(string $file): array {
        if (!is_file($file)) {
            return [];
        }

        $raw = @file_get_contents($file);

        if ($raw === false) {
            error_log("[GBDB] Konnte Datei nicht lesen: {$file}");

            return [];
        }

        if (Vars::crypt_data()) {
            $decoded = Crypt::decode($raw);

            if ($decoded === null) {
                error_log("[GBDB] Crypt::decode() fehlgeschlagen für: {$file}");

                return [];
            }

            $db = json_decode($decoded, true);
        } else {
            $db = json_decode($raw, true);
        }

        return is_array($db) ? $db : [];
    }

    private static function writeTable(string $file, array $db): bool {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $json = json_encode($db, Vars::jpretty());

        if ($json === false) {
            error_log("[GBDB] json_encode() fehlgeschlagen für: {$file}");

            return false;
        }

        $payload = Vars::crypt_data() ? Crypt::encode($json) : $json;

        return GBDBStorage::atomicWrite($file, $payload);
    }

    private static function lockFileForTable(string $database, string $table, bool $ensure = false): string {
        return self::makePath($database, $table, $ensure) . ".lock";
    }

    private static function metaFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);

            return $dir . "__meta__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);

        if ($tbToken === null) {
            return $dir . self::nameToken("__meta__|__missing__", "meta") . Vars::data_extension();
        }

        return $dir . self::nameToken("__meta__|" . $tbToken, "meta") . Vars::data_extension();
    }

    private static function appendFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);

            return $dir . "__append__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);

        if ($tbToken === null) {
            return $dir . self::nameToken("__append__|__missing__", "meta") . Vars::data_extension();
        }

        return $dir . self::nameToken("__append__|" . $tbToken, "meta") . Vars::data_extension();
    }

    private static function withTableLock(string $lockFile, callable $fn): mixed {
        $dir = dirname($lockFile);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $engineLock = method_exists(static::class, "acquireLock") ? self::acquireLock("table-file:" . $lockFile, "write") : false;

        if ($engineLock === false && method_exists(static::class, "acquireLock")) {
            error_log("[GBDB] Engine-Lock Timeout: {$lockFile}");

            return false;
        }

        $handle = @fopen($lockFile, "c+");

        if (!$handle) {
            if ($engineLock !== false) self::releaseLock($engineLock);
            error_log("[GBDB] Konnte Lockfile nicht öffnen: {$lockFile}");

            return false;
        }

        try {
            if (!@flock($handle, LOCK_EX)) {
                error_log("[GBDB] Konnte Lock nicht setzen: {$lockFile}");

                return false;
            }

            return $fn();
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);

            if ($engineLock !== false) self::releaseLock($engineLock);
        }

    }

    private static function readMeta(string $metaFile): array {
        $meta = self::ini($metaFile);

        if (isset($meta[0]) && is_array($meta[0])) {
            return GBDBStorage::normalizeMeta($meta[0]);
        }

        return GBDBStorage::normalizeMeta();
    }

    private static function writeMeta(string $metaFile, array $meta): bool {
        $meta = GBDBStorage::touchMeta($meta);

        return self::writeTable($metaFile, [$meta]);
    }

    private static function isHeaderRow(array $row): bool {
        return isset($row["id"]) && (int)$row["id"] === -1;
    }

    private static function ensureHeader(array &$tableData, array $cols): void {
        if (!empty($tableData) && isset($tableData[0]) && is_array($tableData[0])) {
            return;
        }

        $header = ["id" => -1];

        foreach ($cols as $col) {
            $col = (string)$col;

            if ($col === "" || $col === "id") {
                continue;
            }

            $header[$col] = "-header-";
        }

        $tableData = [$header];
    }

    private static function buildRowFromHeader(array $header, array $data, int $id): array {
        $row = [];

        foreach ($header as $col => $default) {
            if ($col === "id") {
                continue;
            }

            $row[$col] = array_key_exists($col, $data) ? $data[$col] : $default;
        }

        $now = microtime(true);
        $row["_gbdb_version"] = isset($data["_gbdb_version"]) ? (int)$data["_gbdb_version"] : 1;
        $row["_gbdb_revision"] = isset($data["_gbdb_revision"]) ? (int)$data["_gbdb_revision"] : 1;
        $row["_gbdb_visible_from"] = isset($data["_gbdb_visible_from"]) ? (float)$data["_gbdb_visible_from"] : $now;
        $row["id"] = $id;

        return $row;
    }

    private static function appendOp(string $appendFile, array $op): bool {
        $dir = dirname($appendFile);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $json = json_encode($op, 0);

        if ($json === false) {
            return false;
        }

        $line = Vars::crypt_data() ? Crypt::encode($json) : $json;
        $line .= "\n";

        $tx = "tx_" . bin2hex(random_bytes(8));

        if (!GBDBStorage::wal($appendFile, $op, "prepared", $tx)) {
            return false;
        }

        if (!GBDBStorage::appendLine($appendFile, $line)) {
            GBDBStorage::wal($appendFile, $op, "failed", $tx);

            return false;
        }

        GBDBStorage::wal($appendFile, $op, "committed", $tx);

        return true;
    }

    private static function readAppendOps(string $appendFile): array {
        GBDBStorage::recoverWal($appendFile);

        if (!is_file($appendFile)) {
            return [];
        }

        $handle = @fopen($appendFile, "r");

        if (!$handle) {
            return [];
        }

        $ops = [];

        try {
            while (!feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                $line = trim($line);

                if ($line === "") {
                    continue;
                }

                $json = $line;

                if (Vars::crypt_data()) {
                    $decoded = Crypt::decode($line);

                    if ($decoded === null) {
                        error_log("[GBDB] Append decode fehlgeschlagen: {$appendFile}");
                        continue;
                    }

                    $json = $decoded;
                }

                $op = json_decode($json, true);

                if (is_array($op) && isset($op["op"])) {
                    $ops[] = $op;
                }

            }

        } finally {
            @fclose($handle);
        }

        return $ops;
    }

    private static function applyOps(array $base, array $ops): array {
        if (empty($base) || empty($ops)) {
            return $base;
        }

        $header = isset($base[0]) && is_array($base[0]) && self::isHeaderRow($base[0]) ? $base[0] : [];
        $normalize = function (array $row) use (&$header): array {
            if (empty($header)) {
                return $row;
            }

            $tmp = [];

            foreach ($header as $key => $default) {
                if ($key === "id") {
                    continue;
                }

                $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
            }

            foreach ($row as $extraKey => $extraValue) {
                if (is_string($extraKey) && str_starts_with($extraKey, "_gbdb_") && $extraKey !== "id") {
                    $tmp[$extraKey] = $extraValue;
                }

            }

            if (isset($row["id"])) {
                $tmp["id"] = (int)$row["id"];
            }

            return $tmp;
        };

        $rowsById = [];
        $order = [];
        $seen = [];

        foreach ($base as $i => $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($i === 0 && self::isHeaderRow($row)) {
                continue;
            }

            if (!isset($row["id"])) {
                continue;
            }

            $id = (int)$row["id"];
            $rowsById[$id] = $normalize($row);

            if (!isset($seen[$id])) {
                $order[] = $id;
                $seen[$id] = true;
            }

        }

        foreach ($ops as $op) {
            if (!is_array($op)) {
                continue;
            }

            $type = (string)($op["op"] ?? "");

            if ($type === "ins" && isset($op["row"]) && is_array($op["row"])) {
                $row = $normalize($op["row"]);

                if (!isset($row["id"])) {
                    continue;
                }

                $id = (int)$row["id"];

                if (!isset($rowsById[$id]) && !isset($seen[$id])) {
                    $order[] = $id;
                    $seen[$id] = true;
                }

                $rowsById[$id] = isset($rowsById[$id]) && is_array($rowsById[$id])
                    ? $normalize(array_merge($rowsById[$id], $row))
                    : $row;

                continue;
            }

            if ($type === "upd" && isset($op["id"], $rowsById[(int)$op["id"]])) {
                if (!isset($op["set"]) || !is_array($op["set"])) {
                    continue;
                }

                $id = (int)$op["id"];

                foreach ($op["set"] as $key => $value) {
                    if ($key === "id") {
                        continue;
                    }

                    if (empty($header) || array_key_exists($key, $header) || array_key_exists($key, $rowsById[$id])) {
                        $rowsById[$id][$key] = $value;
                    }

                }

                $rowsById[$id] = $normalize($rowsById[$id]);
                continue;
            }

            if ($type === "del" && isset($op["id"])) {
                unset($rowsById[(int)$op["id"]]);
            }

        }

        $out = [];

        if (!empty($header)) {
            $out[] = $header;
        }

        foreach ($order as $id) {
            if (isset($rowsById[$id]) && is_array($rowsById[$id])) {
                $out[] = $rowsById[$id];
            }

        }

        return $out;
    }

    private static function syncStorageForTable(string $database, string $table, string $reason = "sync"): array {
        $file = self::makePath($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        if (!is_file($file)) {
            return ["ok" => false, "error" => "table_not_found"];
        }

        $base = self::ini($file);

        if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
            return ["ok" => false, "error" => "invalid_table"];
        }

        $meta = self::readMeta($metaFile);
        $deferReasons = ["insert" => true, "edit" => true, "delete" => true];
        $manifest = GBDBStorage::storageDir($file) . "/manifest.json";
        $appendOps = (int)($meta["append_ops"] ?? 0);
        $syncEveryOps = max(1, (int)(getenv("GBDB_STORAGE_SYNC_EVERY") ?: 32));
        $eagerSync = (string)(getenv("GBDB_EAGER_STORAGE_SYNC") ?: "") === "1";

        if (!$eagerSync && isset($deferReasons[$reason]) && is_file($manifest) && $appendOps > 0 && $appendOps < $syncEveryOps) {
            $meta["storage_pending"] = true;
            $meta["storage_pending_reason"] = $reason;
            $meta["storage_pending_ops"] = $appendOps;
            $meta["storage_pending_since"] = (int)($meta["storage_pending_since"] ?? time());
            self::writeMeta($metaFile, $meta);

            return [
                "ok" => true,
                "deferred" => true,
                "reason" => $reason,
                "append_ops" => $appendOps,
                "sync_every_ops" => $syncEveryOps
            ];
        }

        $full = self::applyOps($base, self::readAppendOps($appendFile));
        $sync = GBDBStorage::syncStorage($file, $full, $meta, $reason);

        if (($sync["ok"] ?? false) === true) {
            $meta["storage_format"] = GBDBStorage::STORAGE_FORMAT;
            $meta["page_size"] = $sync["page_size"] ?? ($meta["page_size"] ?? GBDBStorage::DEFAULT_PAGE_SIZE);
            $meta["storage_checksum"] = $sync["checksum"] ?? "";
            $meta["storage_verified_at"] = time();
            unset($meta["storage_pending"], $meta["storage_pending_reason"], $meta["storage_pending_ops"], $meta["storage_pending_since"]);
            self::writeMeta($metaFile, $meta);
        }

        return $sync;
    }

    /**
     * handles verify storage.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function verifyStorage(string $database, string $table): array {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return ["ok" => false, "errors" => ["table_not_found"], "warnings" => []];

        return GBDBStorage::verifyStorage($file);
    }

    /**
     * handles storage stats.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function storageStats(string $database, string $table): array {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return ["ok" => false, "error" => "table_not_found"];

        $dir = GBDBStorage::storageDir($file);
        $verify = GBDBStorage::verifyStorage($file);
        $meta = self::readMeta(self::metaFileForTable($database, $table));

        $bytes = 0;

        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

            foreach ($it as $item) {
                if ($item->isFile()) $bytes += (int)$item->getSize();
            }

        }

        return [
            "ok" => (bool)($verify["ok"] ?? false),
            "dir" => $dir,
            "bytes" => $bytes,
            "meta" => $meta,
            "verify" => $verify
        ];
    }

}
