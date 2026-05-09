<?php

class GBDBStorage {
    private const SNAPSHOT_DIR = ".snapshots";
    private const WAL_SUFFIX = ".wal";
    private const JOURNAL_ROTATE_BYTES = 1048576;
    private const INDEX_PREFIX = "__idx2__";
    public const STORAGE_FORMAT = 4;
    public const PAGE_4K = 4096;
    public const PAGE_8K = 8192;
    public const PAGE_16K = 16384;
    public const DEFAULT_PAGE_SIZE = self::PAGE_8K;
    public const DEFAULT_CHUNK_ROWS = 128;

    /**
     * writes data to file in atomic way
     * @param string $file
     * @param string $payload
     * @return bool
     */
    public static function atomicWrite(string $file, string $payload): bool {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $tmp = $file . "." . getmypid() . "." . uniqid("tmp_", true);
        $handle = @fopen($tmp, "wb");

        if (!$handle) {
            error_log("[GBDBStorage] Konnte Temp-Datei nicht öffnen: {$tmp}");

            return false;
        }

        $ok = false;

        try {
            if (@flock($handle, LOCK_EX)) {
                $written = @fwrite($handle, $payload);

                if ($written !== false && $written === strlen($payload)) {
                    @fflush($handle);

                    if (function_exists("fsync")) {
                        @fsync($handle);
                    }

                    $ok = true;
                }

                @flock($handle, LOCK_UN);
            }

        } finally {
            @fclose($handle);
        }

        if (!$ok) {
            @unlink($tmp);
            error_log("[GBDBStorage] Konnte Temp-Datei nicht vollständig schreiben: {$tmp}");

            return false;
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            error_log("[GBDBStorage] Konnte {$tmp} nicht nach {$file} verschieben");

            return false;
        }

        self::syncDir($dir);

        return true;
    }

    /**
     * adds a line safely to a file
     * @param string $file
     * @param string $line
     * @return bool
     */
    public static function appendLine(string $file, string $line): bool {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $handle = @fopen($file, "ab");

        if (!$handle) {
            error_log("[GBDBStorage] Konnte Append-Datei nicht öffnen: {$file}");

            return false;
        }

        $ok = false;

        try {
            if (@flock($handle, LOCK_EX)) {
                $written = @fwrite($handle, $line);

                if ($written !== false && $written === strlen($line)) {
                    @fflush($handle);

                    if (function_exists("fsync")) {
                        @fsync($handle);
                    }

                    $ok = true;
                }

                @flock($handle, LOCK_UN);
            }

        } finally {
            @fclose($handle);
        }

        return $ok;
    }

    /**
     * creates a WAL operation
     * @param string $appendFile
     * @param array $op
     * @param string $state
     * @param string $tx
     * @return bool
     */
    public static function wal(string $appendFile, array $op, string $state, string $tx): bool {
        $entry = [
            "tx" => $tx,
            "state" => $state,
            "op" => $op,
            "ts" => time()
        ];
        $entry["checksum"] = self::journalChecksum($entry);

        $json = json_encode($entry, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        $line = self::encodeLine($json) . "\n";

        return self::appendLine($appendFile . self::WAL_SUFFIX, $line);
    }

    /**
     * creates a cehck sum for Journal-/WAL- entrys
     * @param array $entry
     * @return string
     */
    public static function journalChecksum(array $entry): string {
        unset($entry["checksum"]);
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash("sha256", $json === false ? "" : $json);
    }

    /**
     * writes a generic journal entry with checksum and rotation
     * @param string $file
     * @param array $entry
     * @return bool
     */
    public static function journal(string $file, array $entry): bool {
        $entry["ts"] = (int)($entry["ts"] ?? time());
        $entry["checksum"] = self::journalChecksum($entry);
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) return false;

        if (is_file($file) && (int)@filesize($file) >= self::JOURNAL_ROTATE_BYTES) {
            @rename($file, $file . "." . date("Ymd_His") . ".old");
        }

        return self::appendLine($file, self::encodeLine($json) . "\n");
    }

    /**
     * reads journal and checks checksum
     * @param string $file
     * @return array{entries: array, invalid: int, ok: bool}
     */
    public static function readJournal(string $file): array {
        if (!is_file($file)) return ["ok" => true, "entries" => [], "invalid" => 0];

        $handle = @fopen($file, "r");

        if (!$handle) return ["ok" => false, "entries" => [], "invalid" => 0];

        $entries = [];
        $invalid = 0;

        try {
            while (!feof($handle)) {
                $line = fgets($handle);

                if ($line === false) break;
                $json = self::decodeLine($line);

                if ($json === null) continue;
                $entry = json_decode($json, true);

                if (!is_array($entry)) { $invalid++; continue; }
                $checksum = (string)($entry["checksum"] ?? "");

                if ($checksum !== "" && $checksum !== self::journalChecksum($entry)) $invalid++;
                $entries[] = $entry;
            }

        } finally { @fclose($handle); }

        return ["ok" => $invalid === 0, "entries" => $entries, "invalid" => $invalid];
    }

    /**
     * creates journal line by db-config
     * @param string $json
     * @return string
     */
    public static function encodeLine(string $json): string {
        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            return Crypt::encode($json);
        }

        return $json;
    }

    /**
     * standard metadata for tables
     * @param array $meta
     * @return array{append_ops: int, checksum: string, chunk_size: int, constraints: array, created_at: int, deleted_rows: int, indexes: array, last_compaction: int, last_id: int, last_snapshot: int, page_size: mixed, rows: int, schema_version: int, storage_checksum: string, storage_format: int, storage_last_repair: int, storage_verified_at: int, updated_at: int, version: int}
     */
    public static function normalizeMeta(array $meta = []): array {
        $now = time();

        $defaults = [
            "last_id" => 0,
            "rows" => 0,
            "append_ops" => 0,
            "deleted_rows" => 0,
            "version" => 0,
            "schema_version" => 1,
            "indexes" => [],
            "constraints" => [],
            "checksum" => "",
            "created_at" => $now,
            "updated_at" => $now,
            "last_compaction" => 0,
            "last_snapshot" => 0,
            "storage_format" => 4,
            "page_size" => self::DEFAULT_PAGE_SIZE,
            "chunk_size" => self::DEFAULT_CHUNK_ROWS,
            "storage_checksum" => "",
            "storage_verified_at" => 0,
            "storage_last_repair" => 0
        ];

        $meta = array_merge($defaults, $meta);

        if (!is_array($meta["indexes"])) {
            $meta["indexes"] = [];
        }

        if (!is_array($meta["constraints"])) {
            $meta["constraints"] = [];
        }

        return $meta;
    }

    /**
     * updates meta data before writing to file
     * @param array $meta
     * @param bool $bumpVersion
     * @return array{append_ops: int, checksum: string, chunk_size: int, constraints: array, created_at: int, deleted_rows: int, indexes: array, last_compaction: int, last_id: int, last_snapshot: int, page_size: mixed, rows: int, schema_version: int, storage_checksum: string, storage_format: int, storage_last_repair: int, storage_verified_at: int, updated_at: int, version: int}
     */
    public static function touchMeta(array $meta, bool $bumpVersion = true): array {
        $meta = self::normalizeMeta($meta);
        $meta["updated_at"] = time();

        if ($bumpVersion) {
            $meta["version"] = (int)($meta["version"] ?? 0) + 1;
        }

        return $meta;
    }

    /**
     * checks if a table should be compromized/optimized
     * @param array $meta
     * @param string $appendFile
     * @return bool
     */
    public static function shouldCompact(array $meta, string $appendFile): bool {
        $meta = self::normalizeMeta($meta);
        $appendOps = (int)($meta["append_ops"] ?? 0);
        $rows = max(1, (int)($meta["rows"] ?? 0));
        $appendSize = is_file($appendFile) ? (int)@filesize($appendFile) : 0;

        if ($appendOps <= 0) return false;

        if ($appendOps >= 100) return true;

        if ($appendOps >= max(25, (int)ceil($rows * 0.20))) return true;

        if ($appendSize >= 1024 * 1024) return true;

        return false;
    }

    /**
     * creates checksum for table data
     * @param array $rows
     * @return string
     */
    public static function checksum(array $rows): string {
        $json = json_encode($rows, JSON_UNESCAPED_UNICODE);

        return hash("sha256", $json === false ? "" : $json);
    }

    /**
     * gets index-path of a column
     * @param string $dataFile
     * @param string $column
     * @return string
     */
    public static function indexFile(string $dataFile, string $column): string {
        $column = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $column);

        return dirname($dataFile) . "/" . self::INDEX_PREFIX . basename($dataFile) . "__" . $column . ".idx";
    }

    /**
     * creates a index map
     * @param array $rows
     * @param string $column
     * @return array<array>
     */
    public static function buildIndex(array $rows, string $column): array {
        $idx = [];

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;

            if ($i === 0 && isset($row["id"]) && (int)$row["id"] === -1) continue;

            if (!array_key_exists($column, $row) || !isset($row["id"])) continue;

            $key = self::indexKey($row[$column]);

            if (!isset($idx[$key])) {
                $idx[$key] = [];
            }

            $idx[$key][] = (int)$row["id"];
        }

        return $idx;
    }

    /**
     * creates a column index
     * @param string $dataFile
     * @param string $column
     * @param array $rows
     * @return bool
     */
    public static function writeIndex(string $dataFile, string $column, array $rows): bool {
        $payload = json_encode([
            "column" => $column,
            "created_at" => time(),
            "map" => self::buildIndex($rows, $column)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($payload === false) {
            return false;
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $payload = Crypt::encode($payload);
        }

        return self::atomicWrite(self::indexFile($dataFile, $column), $payload);
    }

    /**
     * delete a column index
     * @param string $dataFile
     * @param string $column
     * @return bool
     */
    public static function deleteIndex(string $dataFile, string $column): bool {
        $file = self::indexFile($dataFile, $column);

        return !is_file($file) || @unlink($file);
    }

    /**
     * checks table constrains
     * @param array $rows
     * @param array $candidate
     * @param array $constraints
     * @param mixed $excludeId
     * @return bool
     */
    public static function validateConstraints(array $rows, array $candidate, array $constraints, ?int $excludeId = null): bool {
        if (empty($constraints)) return true;

        foreach ($constraints as $column => $rules) {
            $column = (string)$column;
            $rules = is_array($rules) ? $rules : [];

            if (($rules["required"] ?? false) === true) {
                if (!array_key_exists($column, $candidate) || $candidate[$column] === "" || $candidate[$column] === null) {
                    return false;
                }

            }

            if (($rules["unique"] ?? false) === true && array_key_exists($column, $candidate)) {
                $value = self::indexKey($candidate[$column]);

                foreach ($rows as $i => $row) {
                    if (!is_array($row)) continue;

                    if ($i === 0 && isset($row["id"]) && (int)$row["id"] === -1) continue;

                    if ($excludeId !== null && isset($row["id"]) && (int)$row["id"] === $excludeId) continue;

                    if (!array_key_exists($column, $row)) continue;

                    if (self::indexKey($row[$column]) === $value) {
                        return false;
                    }

                }

            }

        }

        return true;
    }

    /**
     * creates stable index key
     * @param mixed $value
     * @return string
     */
    public static function indexKey(mixed $value): string {
        if (is_bool($value)) return $value ? "bool:true" : "bool:false";

        if ($value === null) return "null";

        if (is_int($value) || is_float($value)) return "num:" . (string)$value;

        return "str:" . (string)$value;
    }

    /**
     * reqrites all known indexes
     * @param string $dataFile
     * @param array $meta
     * @param array $rows
     * @return void
     */
    public static function rebuildIndexes(string $dataFile, array $meta, array $rows): void {
        $meta = self::normalizeMeta($meta);

        foreach ($meta["indexes"] as $column) {
            $column = (string)$column;

            if ($column === "" || $column === "id") continue;
            self::writeIndex($dataFile, $column, $rows);
        }

    }

    /**
     * creates a snapshot of a table-file
     * @param string $dataFile
     * @param array $extraFiles
     * @param string $reason
     * @return string
     */
    public static function snapshot(string $dataFile, array $extraFiles = [], string $reason = "manual"): string {
        if (!is_file($dataFile)) {
            return "";
        }

        $safeReason = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reason);
        $id = date("Ymd_His") . "_" . substr(hash("sha1", $dataFile . microtime(true)), 0, 8) . "_" . $safeReason;
        $dir = dirname($dataFile) . "/" . self::SNAPSHOT_DIR . "/" . basename($dataFile) . "/" . $id;

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $files = array_merge([$dataFile], $extraFiles);
        $copied = [];

        foreach ($files as $file) {
            if (!is_file($file)) continue;

            $target = $dir . "/" . basename($file);

            if (@copy($file, $target)) {
                $copied[] = basename($file);
            }

        }

        $manifest = [
            "id" => $id,
            "reason" => $reason,
            "created_at" => time(),
            "source" => basename($dataFile),
            "files" => $copied
        ];

        self::atomicWrite($dir . "/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        return empty($copied) ? "" : $id;
    }

    /**
     * delete table artefacts
     * @param string $dataFile
     * @return void
     */
    public static function deleteTableArtifacts(string $dataFile): void {
        $dir = dirname($dataFile);
        $base = basename($dataFile);

        foreach (glob($dir . "/" . self::INDEX_PREFIX . $base . "__*.idx") ?: [] as $file) {
            if (is_file($file)) @unlink($file);
        }

        if (is_file($dataFile . self::WAL_SUFFIX)) {
            @unlink($dataFile . self::WAL_SUFFIX);
        }

        $blockDir = $dir . "/.blocks/" . $base;

        if (is_dir($blockDir)) {
            self::deleteDir($blockDir);
        }

        $blocksRoot = $dir . "/.blocks";

        if (is_dir($blocksRoot)) {
            $items = array_diff(scandir($blocksRoot) ?: [], [".", ".."]);

            if (empty($items)) @rmdir($blocksRoot);
        }

        $snapDir = $dir . "/" . self::SNAPSHOT_DIR . "/" . $base;

        if (is_dir($snapDir)) {
            self::deleteDir($snapDir);
        }

        $rootSnapDir = $dir . "/" . self::SNAPSHOT_DIR;

        if (is_dir($rootSnapDir)) {
            $items = array_diff(scandir($rootSnapDir) ?: [], [".", ".."]);

            if (empty($items)) {
                @rmdir($rootSnapDir);
            }

        }

    }

    /**
     * deletes dir recursively
     * @param string $dir
     * @return bool
     */
    public static function deleteDir(string $dir): bool {
        if (!is_dir($dir)) return true;

        $items = scandir($dir);

        if (!$items) return @rmdir($dir);

        foreach ($items as $item) {
            if ($item === "." || $item === "..") continue;

            $path = $dir . "/" . $item;

            if (is_dir($path)) {
                self::deleteDir($path);
            } else {
                @unlink($path);
            }

        }

        return @rmdir($dir);
    }

    /**
     * decodes a journal line by gb-config
     * @param string $line
     * @return string|null
     */
    public static function decodeLine(string $line): ?string {
        $line = trim($line);

        if ($line === "") {
            return null;
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $decoded = Crypt::decode($line);

            return is_string($decoded) ? $decoded : null;
        }

        return $line;
    }

    /**
     * reads WAL entrys of append file
     * @param string $appendFile
     * @return array[]
     */
    public static function readWal(string $appendFile): array {
        $walFile = $appendFile . self::WAL_SUFFIX;

        if (!is_file($walFile)) {
            return [];
        }

        $handle = @fopen($walFile, "r");

        if (!$handle) {
            return [];
        }

        $entries = [];

        try {
            while (!feof($handle)) {
                $line = fgets($handle);

                if ($line === false) break;

                $json = self::decodeLine($line);

                if ($json === null) continue;

                $entry = json_decode($json, true);

                if (is_array($entry) && isset($entry["tx"], $entry["state"], $entry["op"])) {
                    $checksum = (string)($entry["checksum"] ?? "");

                    if ($checksum !== "" && $checksum !== self::journalChecksum($entry)) continue;
                    $entries[] = $entry;
                }

            }

        } finally {
            @fclose($handle);
        }

        return $entries;
    }

    /**
     * recreates commited WAL-operations safely in the append file
     * @param string $appendFile
     * @return array{dangling: int, ok: bool, replayed: int}
     */
    public static function recoverWal(string $appendFile): array {
        $entries = self::readWal($appendFile);

        if (empty($entries)) {
            return ["ok" => true, "replayed" => 0, "dangling" => 0];
        }

        $states = [];
        $ops = [];

        foreach ($entries as $entry) {
            $tx = (string)$entry["tx"];
            $state = (string)$entry["state"];
            $states[$tx][$state] = true;
            $ops[$tx] = $entry["op"];
        }

        $existing = [];

        if (is_file($appendFile)) {
            $handle = @fopen($appendFile, "r");

            if ($handle) {
                try {
                    while (!feof($handle)) {
                        $line = fgets($handle);

                        if ($line === false) break;

                        $json = self::decodeLine($line);

                        if ($json === null) continue;
                        $existing[hash("sha256", $json)] = true;
                    }

                } finally {
                    @fclose($handle);
                }

            }

        }

        $replayed = 0;
        $dangling = 0;

        foreach ($states as $tx => $txStates) {
            if (!empty($txStates["committed"]) && isset($ops[$tx])) {
                $json = json_encode($ops[$tx], 0);

                if ($json === false) continue;

                $hash = hash("sha256", $json);

                if (!isset($existing[$hash])) {
                    self::appendLine($appendFile, self::encodeLine($json) . "\n");

                    $existing[$hash] = true;
                    $replayed++;
                }

            } else if (!empty($txStates["prepared"]) && empty($txStates["failed"])) {
                $dangling++;
            }

        }

        return ["ok" => true, "replayed" => $replayed, "dangling" => $dangling];
    }

    /**
     * restores a snapshot
     * @param string $dataFile
     * @param string $snapshotId
     * @return bool
     */
    public static function restoreSnapshot(string $dataFile, string $snapshotId): bool {
        $snapshotId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $snapshotId);

        if ($snapshotId === "") {
            return false;
        }

        $dir = dirname($dataFile) . "/" . self::SNAPSHOT_DIR . "/" . basename($dataFile) . "/" . $snapshotId;

        if (!is_dir($dir)) {
            return false;
        }

        $ok = true;
        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === "." || $item === ".." || $item === "manifest.json") continue;

            $src = $dir . "/" . $item;
            $target = dirname($dataFile) . "/" . $item;

            if (is_file($src)) {
                $payload = @file_get_contents($src);

                if ($payload === false || !self::atomicWrite($target, $payload)) {
                    $ok = false;
                }

            }

        }

        return $ok;
    }

    /**
     * reads index and gets row-ids for a specific value
     * @param string $dataFile
     * @param string $column
     * @param mixed $value
     * @return array
     */
    public static function indexLookup(string $dataFile, string $column, mixed $value): array {
        $file = self::indexFile($dataFile, $column);

        if (!is_file($file)) {
            return [];
        }

        $payload = @file_get_contents($file);

        if ($payload === false || $payload === "") {
            return [];
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $decoded = Crypt::decode($payload);

            if (is_string($decoded)) {
                $payload = $decoded;
            }

        }

        $idx = json_decode($payload, true);

        if (!is_array($idx) || !isset($idx["map"]) || !is_array($idx["map"])) {
            return [];
        }

        $key = self::indexKey($value);

        return array_values(array_map("intval", $idx["map"][$key] ?? []));
    }

    /**
     * dir of index-files
     * @param string $dataFile
     * @return string
     */
    public static function advancedIndexDir(string $dataFile): string {
        return self::storageDir($dataFile) . "/index/indexes";
    }

    /**
     * normalizes a index definition
     * @param string $name
     * @param string $type
     * @param array $columns
     * @param array $options
     * @return array{columns: array, created_at: int, fulltext: bool, name: array|string|null, prefix: bool, sorted: bool, type: string, unique: bool, updated_at: int}
     */
    public static function normalizeIndexDefinition(string $name, string $type, array $columns, array $options = []): array {
        $type = strtolower(trim($type));

        if ($type === '') $type = 'single';

        $columns = array_values(array_filter(array_map(function ($column) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim((string)$column));
        }, $columns), fn($column) => $column !== '' && $column !== 'id'));

        if ($name === '') {
            $name = $type . '_' . implode('_', $columns);
        }

        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);

        return [
            'name' => $name,
            'type' => $type,
            'columns' => $columns,
            'unique' => (bool)($options['unique'] ?? ($type === 'unique' || $type === 'primary')),
            'sorted' => (bool)($options['sorted'] ?? in_array($type, ['sorted', 'range'], true)),
            'prefix' => (bool)($options['prefix'] ?? ($type === 'prefix')),
            'fulltext' => (bool)($options['fulltext'] ?? ($type === 'fulltext')),
            'created_at' => (int)($options['created_at'] ?? time()),
            'updated_at' => time()
        ];
    }

    /**
     * builds index name by types and columns
     * @param string $type
     * @param array $columns
     * @return array|string|null
     */
    public static function indexName(string $type, array $columns): string {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($type) . '_' . implode('_', $columns));
    }

    /**
     * gets index of a definition
     * @param string $dataFile
     * @param string $name
     * @return string
     */
    public static function advancedIndexFile(string $dataFile, string $name): string {
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);

        return self::advancedIndexDir($dataFile) . '/' . $name . '.idx.json';
    }

    /**
     * gets meta data for indexes
     * @param string $dataFile
     * @return string
     */
    public static function advancedIndexMetaFile(string $dataFile): string {
        return self::advancedIndexDir($dataFile) . '/_meta.json';
    }

    /**
     * display of indexes
     * @param array $row
     * @param array $columns
     * @return string
     */
    public static function compositeIndexKey(array $row, array $columns): string {
        $parts = [];

        foreach ($columns as $column) $parts[] = self::indexKey($row[$column] ?? null);

        return implode('|', $parts);
    }

    /**
     * tokenizer for full-text index
     * @param string $text
     * @return array
     */
    public static function tokenizeFulltext(string $text): array {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $stopwords = array_flip(['der','die','das','den','dem','und','oder','aber','ein','eine','einer','eines','ist','sind','war','waren','the','a','an','and','or','of','to','in','for','on','with']);

        preg_match_all('/[#@]?[\p{L}\p{N}_\-]+/u', $text, $m);

        $tokens = [];

        foreach (($m[0] ?? []) as $token) {
            $token = trim((string)$token);

            if ($token === '') continue;

            $plain = ltrim($token, '#@');
            $len = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : strlen($plain);

            if ($len < 2) continue;

            if (!str_starts_with($token, '#') && !str_starts_with($token, '@') && isset($stopwords[$plain])) continue;

            $tokens[] = $token;

            if (str_starts_with($token, '#')) $tokens[] = 'hashtag:' . substr($token, 1);

            if (str_starts_with($token, '@')) $tokens[] = 'mention:' . substr($token, 1);

            $prefix = '';
            $chars = preg_split('//u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($chars as $i => $ch) {
                $prefix .= $ch;

                if ($i >= 1) $tokens[] = 'prefix:' . $prefix;

                if ($i >= 11) break;
            }

        }

        return array_values(array_unique($tokens));
    }

    /**
     * builds index map
     * @param array $rows
     * @param array $definition
     * @return array
     */
    public static function buildAdvancedIndex(array $rows, array $definition): array {
        $type = (string)($definition['type'] ?? 'single');
        $columns = is_array($definition['columns'] ?? null) ? $definition['columns'] : [];
        $map = [];

        if (empty($columns)) return $map;

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;

            if ($i === 0 && isset($row['id']) && (int)$row['id'] === -1) continue;

            if (!isset($row['id'])) continue;

            $id = (int)$row['id'];

            if ($type === 'fulltext') {
                $haystack = '';

                foreach ($columns as $column) if (array_key_exists($column, $row) && (is_scalar($row[$column]) || $row[$column] === null)) $haystack .= ' ' . (string)$row[$column];

                foreach (self::tokenizeFulltext($haystack) as $token) $map[$token][] = $id;

                continue;
            }

            $key = self::compositeIndexKey($row, $columns);
            $map[$key][] = $id;

            if (!empty($definition['prefix'])) {
                $raw = (string)($row[$columns[0]] ?? '');
                $raw = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);
                $chars = preg_split('//u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $prefix = '';

                foreach ($chars as $pos => $ch) {
                    $prefix .= $ch;

                    if ($pos >= 1) $map['prefix:' . $prefix][] = $id;

                    if ($pos >= 31) break;
                }

            }

        }

        foreach ($map as $key => $ids) $map[$key] = array_values(array_unique(array_map('intval', $ids)));

        if (!empty($definition['sorted'])) ksort($map, SORT_NATURAL);

        return $map;
    }

    /**
     * writes index with checksum
     * @param string $dataFile
     * @param array $definition
     * @param array $rows
     * @return bool
     */
    public static function writeAdvancedIndex(string $dataFile, array $definition, array $rows): bool {
        $dir = self::advancedIndexDir($dataFile);

        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $definition = self::normalizeIndexDefinition((string)($definition['name'] ?? ''), (string)($definition['type'] ?? 'single'), (array)($definition['columns'] ?? []), $definition);
        $map = self::buildAdvancedIndex($rows, $definition);
        $payload = ['definition' => $definition, 'map' => $map, 'checksum' => self::checksum($map), 'updated_at' => time()];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) return false;

        return self::atomicWrite(self::advancedIndexFile($dataFile, $definition['name']), $json . "\n");
    }

    /**
     * reads index
     * @param string $dataFile
     * @param string $name
     * @return array
     */
    public static function readAdvancedIndex(string $dataFile, string $name): array {
        return self::readJsonFile(self::advancedIndexFile($dataFile, $name));
    }

    /**
     * writes index meta file
     * @param string $dataFile
     * @param array $definitions
     * @return bool
     */
    public static function writeAdvancedIndexMeta(string $dataFile, array $definitions): bool {
        $meta = ['format' => self::STORAGE_FORMAT, 'updated_at' => time(), 'definitions' => $definitions, 'checksum' => self::checksum($definitions)];
        $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $json !== false && self::atomicWrite(self::advancedIndexMetaFile($dataFile), $json . "\n");
    }

    /**
     * searches row-ids of index
     * @param string $dataFile
     * @param array $definition
     * @param mixed $value
     * @return array
     */
    public static function advancedIndexLookup(string $dataFile, array $definition, mixed $value): array {
        $idx = self::readAdvancedIndex($dataFile, (string)($definition['name'] ?? ''));

        if (empty($idx['map']) || !is_array($idx['map'])) return [];

        $type = (string)($definition['type'] ?? 'single');

        if ($type === 'fulltext') {
            $ids = [];

            foreach (self::tokenizeFulltext((string)$value) as $token) foreach (($idx['map'][$token] ?? []) as $id) $ids[] = (int)$id;

            return array_values(array_unique($ids));
        }

        $vals = is_array($value) ? $value : [$value];
        $row = [];

        foreach ((array)($definition['columns'] ?? []) as $i => $column) $row[$column] = $vals[$i] ?? null;

        $key = self::compositeIndexKey($row, (array)($definition['columns'] ?? []));

        return array_values(array_map('intval', $idx['map'][$key] ?? []));
    }

    /**
     * gets page sizes
     * @return int[]
     */
    public static function pageSizes(): array {
        return [self::PAGE_4K, self::PAGE_8K, self::PAGE_16K];
    }

    /**
     * normalize page size
     * @param int $pageSize
     * @return int
     */
    public static function normalizePageSize(int $pageSize): int {
        return in_array($pageSize, self::pageSizes(), true) ? $pageSize : self::DEFAULT_PAGE_SIZE;
    }

    /**
     * gets sidecar-storage-dir of a table
     * @param string $dataFile
     * @return string
     */
    public static function storageDir(string $dataFile): string {
        return dirname($dataFile) . "/.blocks/" . basename($dataFile);
    }

    /**
     * initializes page-/chunk storage structure of a table
     * @param string $dataFile
     * @param array $rows
     * @param array $meta
     * @return array
     */
    public static function initStorage(string $dataFile, array $rows, array $meta = []): array {
        return self::syncStorage($dataFile, $rows, $meta, "init");
    }

    /**
     * Schreibt die Sidecar-Storage-Dateien aus den aktuellen Tabellenzeilen neu.
     *
     * Diese Methode ist bewusst als robuste Grundstruktur gebaut: die klassische GBDB-Datei
     * bleibt die Kompatibilitätsquelle, während .blocks/ bereits Page-, Pointer-, Meta-,
     * Journal-, Free- und Repair-Dateien erzeugt. Dadurch bleiben alte GBDB::get/edit/delete/insert
     * Aufrufe stabil und das neue Format kann schrittweise aktiviert werden.
     *
     * @param string $dataFile Hauptdatei der Tabelle.
     * @param array $rows Tabellenzeilen inklusive Header.
     * @param array $meta Tabellen-Meta.
     * @param string $reason Grund der Synchronisierung.
     * @return array Storage-Bericht.
     */

    /**
     * rewrites a sidecar-storage-file of a table
     * @param string $dataFile
     * @param array $rows
     * @param array $meta
     * @param string $reason
     * @return array{checksum: mixed, dir: string, format: int, ok: bool, page_size: int, pages: int, rows: int}
     */
    public static function syncStorage(string $dataFile, array $rows, array $meta = [], string $reason = "sync"): array {
        $meta = self::normalizeMeta($meta);
        $pageSize = self::normalizePageSize((int)($meta["page_size"] ?? self::DEFAULT_PAGE_SIZE));
        $chunkRows = max(1, (int)($meta["chunk_size"] ?? self::DEFAULT_CHUNK_ROWS));
        $dir = self::storageDir($dataFile);

        foreach (["data", "index", "meta", "journal", "free", "repair", "tmp"] as $sub) {
            $path = $dir . "/" . $sub;

            if (!is_dir($path)) @mkdir($path, 0777, true);
        }

        $header = [];
        $body = [];

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;

            if ($i === 0 && isset($row["id"]) && (int)$row["id"] === -1) {
                $header = $row;
                continue;
            }

            if (!isset($row["id"])) continue;

            $body[] = $row;
        }

        $oldFree = self::readJsonFile($dir . "/free/free.json");
        $oldTombstones = is_array($oldFree["tombstones"] ?? null) ? $oldFree["tombstones"] : [];
        $liveIds = [];

        foreach ($body as $row) $liveIds[(string)(int)$row["id"]] = true;

        foreach ($oldTombstones as $id => $item) {
            if (isset($liveIds[(string)$id])) unset($oldTombstones[$id]);
        }

        foreach (glob($dir . "/data/chunk_*.page") ?: [] as $oldPage) {
            if (is_file($oldPage)) @unlink($oldPage);
        }

        $chunks = [];
        $pointers = [];
        $pageChecksums = [];
        $chunkNo = 0;
        $current = [];
        $currentSize = 0;

        $flush = function () use (&$chunks, &$pointers, &$pageChecksums, &$chunkNo, &$current, &$currentSize, $dir, $dataFile, $header, $pageSize, $reason) {
            if (empty($current)) return;

            $chunkNo++;
            $chunkId = str_pad((string)$chunkNo, 6, "0", STR_PAD_LEFT);
            $pageFile = $dir . "/data/chunk_" . $chunkId . ".page";
            $logical = basename($pageFile);
            $rowsOut = [];

            foreach ($current as $offset => $row) {
                $rowChecksum = self::checksum([$row]);
                $rowId = (int)$row["id"];
                $rowsOut[] = [
                    "ptr" => "c{$chunkId}:{$offset}",
                    "id" => $rowId,
                    "deleted" => false,
                    "checksum" => $rowChecksum,
                    "row" => $row
                ];

                $pointers[(string)$rowId] = [
                    "file" => $logical,
                    "chunk" => (int)$chunkNo,
                    "offset" => (int)$offset,
                    "deleted" => false,
                    "checksum" => $rowChecksum
                ];
            }

            $page = [
                "format" => self::STORAGE_FORMAT,
                "page_size" => $pageSize,
                "source" => basename($dataFile),
                "chunk" => (int)$chunkNo,
                "created_at" => time(),
                "reason" => $reason,
                "header" => $header,
                "rows" => $rowsOut
            ];

            $page["checksum"] = self::checksum($page["rows"]);
            $payload = json_encode($page, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($payload === false) $payload = "{}";

            self::atomicWrite($pageFile, $payload . "\n");

            $chunks[] = [
                "file" => $logical,
                "chunk" => (int)$chunkNo,
                "rows" => count($rowsOut),
                "bytes" => strlen($payload),
                "checksum" => $page["checksum"]
            ];

            $pageChecksums[$logical] = $page["checksum"];
            $current = [];
            $currentSize = 0;
        };

        foreach ($body as $row) {
            $json = json_encode($row, JSON_UNESCAPED_UNICODE);
            $size = $json === false ? 128 : strlen($json);
            $wouldOverflow = !empty($current) && ($currentSize + $size > $pageSize || count($current) >= $chunkRows);

            if ($wouldOverflow) $flush();

            $current[] = $row;
            $currentSize += $size;
        }

        $flush();

        $free = [
            "format" => self::STORAGE_FORMAT,
            "updated_at" => time(),
            "free_slots" => [],
            "tombstones" => $oldTombstones,
            "reusable_slots" => count($oldTombstones)
        ];

        self::atomicWrite($dir . "/free/free.json", json_encode($free, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        $manifest = [
            "format" => self::STORAGE_FORMAT,
            "source" => basename($dataFile),
            "created_at" => time(),
            "reason" => $reason,
            "page_size" => $pageSize,
            "chunk_rows" => $chunkRows,
            "chunks" => $chunks,
            "pages" => count($chunks),
            "rows" => count($body),
            "header_checksum" => self::checksum([$header]),
            "data_checksum" => self::checksum($rows),
            "page_checksums" => $pageChecksums
        ];

        $manifest["checksum"] = self::checksum($manifest);

        self::atomicWrite($dir . "/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        $pointerPayload = [
            "format" => self::STORAGE_FORMAT,
            "updated_at" => time(),
            "pointers" => $pointers
        ];

        $pointerPayload["checksum"] = self::checksum($pointerPayload["pointers"]);

        self::atomicWrite($dir . "/index/primary.ptr", json_encode($pointerPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        $storageMeta = [
            "format" => self::STORAGE_FORMAT,
            "storage_version" => self::STORAGE_FORMAT,
            "updated_at" => time(),
            "page_size" => $pageSize,
            "supported_page_sizes" => self::pageSizes(),
            "chunk_size" => $chunkRows,
            "record_pointer_file" => "index/primary.ptr",
            "data_files" => array_map(fn($c) => "data/" . $c["file"], $chunks),
            "journal_file" => "journal/operations.log",
            "free_file" => "free/free.json",
            "repair_file" => "repair/last_report.json",
            "rows" => count($body),
            "tombstones" => count($oldTombstones),
            "checksum" => $manifest["checksum"]
        ];

        self::atomicWrite($dir . "/meta/storage.json", json_encode($storageMeta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        self::appendStorageJournal($dataFile, [
            "op" => "sync",
            "reason" => $reason,
            "rows" => count($body),
            "pages" => count($chunks),
            "checksum" => $manifest["checksum"]
        ]);

        return [
            "ok" => true,
            "format" => self::STORAGE_FORMAT,
            "page_size" => $pageSize,
            "pages" => count($chunks),
            "rows" => count($body),
            "checksum" => $manifest["checksum"],
            "dir" => $dir
        ];
    }

    /**
     * writes storage-journal operation
     * @param string $dataFile
     * @param array $op
     * @return bool
     */
    public static function appendStorageJournal(string $dataFile, array $op): bool {
        $dir = self::storageDir($dataFile) . "/journal";

        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $entry = [
            "ts" => time(),
            "op" => $op
        ];

        $entry["checksum"] = self::checksum($entry["op"]);
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE);

        if ($json === false) return false;

        return self::appendLine($dir . "/operations.log", $json . "\n");
    }

    /**
     * marks data as tombstone im storage-sidecar
     * @param string $dataFile
     * @param array $ids
     * @return bool
     */
    public static function markTombstones(string $dataFile, array $ids): bool {
        $dir = self::storageDir($dataFile);

        if (!is_dir($dir . "/free")) @mkdir($dir . "/free", 0777, true);

        $freeFile = $dir . "/free/free.json";
        $free = self::readJsonFile($freeFile);

        if (empty($free)) {
            $free = ["format" => self::STORAGE_FORMAT, "free_slots" => [], "tombstones" => []];
        }

        if (!is_array($free["tombstones"] ?? null)) $free["tombstones"] = [];

        foreach ($ids as $id) {
            $id = (int)$id;

            if ($id <= 0) continue;

            $free["tombstones"][(string)$id] = [
                "id" => $id,
                "deleted_at" => time(),
                "reusable" => true
            ];
        }

        $free["updated_at"] = time();
        $free["reusable_slots"] = count($free["tombstones"]);

        self::appendStorageJournal($dataFile, ["op" => "tombstone", "ids" => array_values(array_map("intval", $ids))]);

        return self::atomicWrite($freeFile, json_encode($free, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * checks storage-sidecar-files and page checksum
     * @param string $dataFile
     * @return array{checksum: string, errors: array, format: int, ok: bool, pages: int, rows: int, warnings: array|array{errors: string[], ok: bool, warnings: array}}
     */
    public static function verifyStorage(string $dataFile): array {
        $dir = self::storageDir($dataFile);
        $errors = [];
        $warnings = [];

        if (!is_dir($dir)) {
            return ["ok" => false, "errors" => ["storage_dir_missing"], "warnings" => []];
        }

        $manifest = self::readJsonFile($dir . "/manifest.json");

        if (empty($manifest)) $errors[] = "manifest_missing_or_invalid";

        $storageMeta = self::readJsonFile($dir . "/meta/storage.json");

        if (empty($storageMeta)) $warnings[] = "storage_meta_missing_or_invalid";

        $pointer = self::readJsonFile($dir . "/index/primary.ptr");

        if (empty($pointer)) $warnings[] = "primary_pointer_missing_or_invalid";

        $pageChecks = is_array($manifest["page_checksums"] ?? null) ? $manifest["page_checksums"] : [];
        $pages = 0;
        $rows = 0;

        foreach ($pageChecks as $pageFile => $expected) {
            $file = $dir . "/data/" . basename((string)$pageFile);

            if (!is_file($file)) {
                $errors[] = "page_missing:" . basename($file);
                continue;
            }

            $page = self::readJsonFile($file);

            if (empty($page) || !is_array($page["rows"] ?? null)) {
                $errors[] = "page_invalid:" . basename($file);
                continue;
            }

            $actual = self::checksum($page["rows"]);

            if (!hash_equals((string)$expected, (string)$actual)) {
                $errors[] = "page_checksum_mismatch:" . basename($file);
            }

            $pages++;
            $rows += count($page["rows"]);
        }

        if (isset($manifest["pages"]) && (int)$manifest["pages"] !== $pages) {
            $warnings[] = "page_count_mismatch";
        }

        if (isset($manifest["rows"]) && (int)$manifest["rows"] !== $rows) {
            $warnings[] = "row_count_mismatch";
        }

        $report = [
            "ok" => empty($errors),
            "format" => (int)($manifest["format"] ?? 0),
            "errors" => array_values(array_unique($errors)),
            "warnings" => array_values(array_unique($warnings)),
            "pages" => $pages,
            "rows" => $rows,
            "checksum" => (string)($manifest["checksum"] ?? "")
        ];

        if (!is_dir($dir . "/repair")) @mkdir($dir . "/repair", 0777, true);

        self::atomicWrite($dir . "/repair/last_report.json", json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        return $report;
    }

    /**
     * repairs sidecar-storage-files
     * @param string $dataFile
     * @param array $rows
     * @param array $meta
     * @return array{ok: bool, repaired_at: int, sync: array{checksum: mixed, dir: string, format: int, ok: bool, page_size: int, pages: int, rows: int, verify: array}}
     */
    public static function repairStorage(string $dataFile, array $rows, array $meta = []): array {
        $dir = self::storageDir($dataFile);

        if (is_dir($dir . "/tmp")) self::deleteDir($dir . "/tmp");

        self::appendStorageJournal($dataFile, ["op" => "repair_start"]);

        $sync = self::syncStorage($dataFile, $rows, $meta, "repair");
        $verify = self::verifyStorage($dataFile);

        self::appendStorageJournal($dataFile, ["op" => "repair_done", "ok" => (bool)$verify["ok"]]);

        $report = [
            "ok" => (bool)$verify["ok"],
            "sync" => $sync,
            "verify" => $verify,
            "repaired_at" => time()
        ];

        if (!is_dir($dir . "/repair")) @mkdir($dir . "/repair", 0777, true);

        self::atomicWrite($dir . "/repair/last_report.json", json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        return $report;
    }

    /**
     * cleans storage-artefacts and rebuilds chunk files
     * @param string $dataFile
     * @param array $rows
     * @param array $meta
     * @return array{checksum: mixed, dir: string, format: int, ok: bool, page_size: int, pages: int, rows: int}
     */
    public static function vacuumStorage(string $dataFile, array $rows, array $meta = []): array {
        $dir = self::storageDir($dataFile);

        foreach (glob($dir . "/tmp/*") ?: [] as $tmp) {
            if (is_file($tmp)) @unlink($tmp);

            if (is_dir($tmp)) self::deleteDir($tmp);
        }

        self::appendStorageJournal($dataFile, ["op" => "vacuum_start"]);
        $sync = self::syncStorage($dataFile, $rows, $meta, "vacuum");
        self::appendStorageJournal($dataFile, ["op" => "vacuum_done", "checksum" => $sync["checksum"] ?? ""]);

        return $sync;
    }

    /**
     * get decrypted sidecar-storage-json
     * @param string $file
     * @return array
     */

    private static function readJsonFile(string $file): array {
        if (!is_file($file)) return [];
        $raw = @file_get_contents($file);

        if (!is_string($raw) || trim($raw) === "") return [];
        $json = json_decode($raw, true);

        return is_array($json) ? $json : [];
    }

    /**
     * syncs a dir if possible
     * @param string $dir
     * @return void
     */

    private static function syncDir(string $dir): void {
        if (!function_exists("fsync")) {
            return;
        }

        $handle = @fopen($dir, "r");

        if ($handle) {
            @fsync($handle);
            @fclose($handle);
        }

    }

}
