<?php

trait GBDB_CrudTrait {

    /**
     * Erstellt eine Instanz.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createInstance(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getInstanceToken($name, true)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = $base . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777, true);
        }

        return true;
    }


    /**
     * Löscht eine Instanz.
     * @param string $name Übergabewert.
     * @param bool $force Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteInstance(string $name, bool $force = false): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $old = self::getInstance();
        self::setInstance($name);

        if ($force) {
            $databases = self::listDBs();

            foreach ($databases as $database) {
                self::deleteAll($database);
            }
        }

        $dirName = Vars::crypt_data()
            ? self::getInstanceToken($name, false)
            : $name;

        if ($dirName === null) {
            self::setInstance($old);
            return false;
        }

        $path = Vars::DB_PATH() . $dirName;

        $ok = false;

        if (is_dir($path)) {
            $files = scandir($path);
            $rest = $files ? array_diff($files, [".", ".."]) : [];

            if (Vars::crypt_data()) {
                $idx = basename(self::dbIndexFileByInstanceToken($dirName));
                $rest = array_values($rest);

                if (count($rest) === 1 && $rest[0] === $idx) {
                    @unlink($path . "/" . $idx);
                    $rest = [];
                }
            }

            if (count($rest) === 0) {
                $ok = @rmdir($path);
            }
        }

        if ($ok) {
            self::dropInstanceFromIndex($name);
            self::dropSchemaInstance($name);
        }

        self::setInstance($old);

        return $ok;
    }


    /**
     * Prüft, ob eine Instanz nur intern vom Framework genutzt wird.
     * @param string $instance Instanzname.
     * @return bool true bei System-/Default-Instanzen.
     */
    private static function isInternalInstanceName(string $instance): bool {
        $slug = strtolower(Format::cleanString($instance));

        if ($slug === '') return true;

        return in_array($slug, [
            'default',
            'gbdb-system',
            'gbdb_system',
            'gbdbsystem',
            '__greenql_ui_v2_system',
            'greenqluiv2system',
            'greenql-ui-v2-system'
        ], true) || str_starts_with($slug, '__') || str_starts_with($slug, 'gbdb_system_');
    }

    /**
     * Listet Instanzen aus dem Storage, optional inklusive interner Systeminstanzen.
     * @param bool $includeSystem true gibt auch Framework-Systeminstanzen zurück.
     * @return array Rückgabewert.
     */
    public static function listInstances(bool $includeSystem = false): array {
        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            return [];
        }

        if (!Vars::crypt_data()) {
            $instances = [];
            $tmp = scandir($base);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (is_dir($base . $entry)) {
                    $instances[] = $entry;
                }
            }

            $instances = $includeSystem ? $instances : array_values(array_filter($instances, fn($i) => !self::isInternalInstanceName((string)$i)));
            sort($instances, SORT_NATURAL | SORT_FLAG_CASE);

            return $instances;
        }

        $map = self::readIndex(self::instanceIndexFile());
        $out = [];

        foreach ($map as $plain => $token) {
            if (is_dir($base . $token)) {
                $out[] = $plain;
            }
        }

        $out = $includeSystem ? $out : array_values(array_filter($out, fn($i) => !self::isInternalInstanceName((string)$i)));
        sort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /** Gibt alle Instanzen inklusive interner Framework-Instanzen zurück. */
    public static function listAllInstances(): array {
        return self::listInstances(true);
    }

    /**
     * Erstellt eine Datenbank innerhalb der aktiven Instanz.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        self::createInstance(self::instanceName());

        $instancePath = self::instancePath(true);

        if (!is_dir($instancePath)) {
            @mkdir($instancePath, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, true)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = $instancePath . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777, true);
        }

        return true;
    }


    /**
     * Löscht eine leere Datenbank.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, false)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = self::instancePath(false) . $dirName;

        if (is_dir($path)) {
            $files = scandir($path);

            if ($files) {
                $rest = array_diff($files, [".", ".."]);

                if (Vars::crypt_data()) {
                    $instanceToken = self::getInstanceToken(self::instanceName(), false);

                    if ($instanceToken !== null) {
                        $idx = basename(self::tableIndexFileByTokens($instanceToken, $dirName));
                        $rest = array_values($rest);

                        if (count($rest) === 1 && $rest[0] === $idx) {
                            @unlink($path . "/" . $idx);
                            $rest = [];
                        }
                    }
                }

                if (count($rest) === 0) {
                    $ok = @rmdir($path);

                    if ($ok) {
                        self::dropDatabaseFromIndex($name);
                    }

                    return $ok;
                }
            }
        }

        return false;
    }


    /**
     * Erstellt eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        self::createDatabase($database);

        $file = self::makePath($database, $table, true);
        $lockFile = self::lockFileForTable($database, $table, true);
        $metaFile = self::metaFileForTable($database, $table, true);
        $appendFile = self::appendFileForTable($database, $table, true);

        $res = (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $cols) {
            if (file_exists($file)) {
                return false;
            }

            $header = ["id" => -1];

            foreach ($cols as $col) {
                $col = (string)$col;

                if ($col === "" || $col === "id") {
                    continue;
                }

                $header[$col] = "-header-";
            }

            if (!self::writeTable($file, [$header])) {
                return false;
            }

            self::writeMeta($metaFile, GBDBStorage::normalizeMeta([
                "last_id" => 0,
                "rows" => 0,
                "append_ops" => 0,
                "checksum" => GBDBStorage::checksum([$header]),
                "indexes" => [],
                "created_at" => time(),
                "updated_at" => time()
            ]));

            if (!is_file($appendFile)) {
                if (!GBDBStorage::atomicWrite($appendFile, "")) {
                return false;
            }
            }

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, $cols);
            self::syncStorageForTable($database, $table, "create_table");
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return $res;
    }


    /**
     * Fügt eine Spalte hinzu.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param string $column Übergabewert.
     * @param mixed $default Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function addColumn(string $database, string $table, string $column, mixed $default = ""): bool {
        $column = trim($column);

        if ($column === "" || $column === "id") {
            return false;
        }

        $file = self::makePath($database, $table);
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column, $default) {
            if (!file_exists($file)) {
                return false;
            }

            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            if (empty($full) || !isset($full[0]) || !is_array($full[0]) || !self::isHeaderRow($full[0])) {
                return false;
            }

            $header = $full[0];

            if (array_key_exists($column, $header)) {
                return true;
            }

            $newHeader = [];

            foreach ($header as $key => $value) {
                $newHeader[$key] = $value;

                if ($key === "id") {
                    $newHeader[$column] = "-header-";
                }
            }

            if (!array_key_exists($column, $newHeader)) {
                $newHeader[$column] = "-header-";
            }

            $rebuilt = [$newHeader];
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($i === 0) {
                    continue;
                }

                if (!array_key_exists($column, $row)) {
                    $row[$column] = $default;
                }

                $tmp = [];

                foreach ($newHeader as $key => $_) {
                    if ($key === "id") {
                        continue;
                    }

                    $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
                }

                $tmp["id"] = isset($row["id"]) ? (int)$row["id"] : 0;
                $rebuilt[] = $tmp;
                $rows++;
            }

            if (!self::writeTable($file, $rebuilt)) {
                return false;
            }

            if (!GBDBStorage::atomicWrite($appendFile, "")) {
                return false;
            }

            $meta = self::readMeta($metaFile);
            $meta["rows"] = $rows;
            $meta["append_ops"] = 0;
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, [$column => $default]);
            self::syncStorageForTable($database, $table, "add_column");
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return $res;
    }


    /**
     * Löscht eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool)self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $lockFile) {
            if (!file_exists($file)) {
                return false;
            }

            $ok = @unlink($file);

            if ($ok) {
                if (is_file($metaFile)) {
                    @unlink($metaFile);
                }

                if (is_file($appendFile)) {
                    @unlink($appendFile);
                }

                if (is_file($appendFile . ".wal")) {
                    @unlink($appendFile . ".wal");
                }

                GBDBStorage::deleteTableArtifacts($file);

                if (method_exists(static::class, "mvccFile")) {
                    $mvccFile = self::mvccFile($database, $table);
                    if (is_file($mvccFile)) @unlink($mvccFile);
                    $mvccDir = dirname($mvccFile);
                    $left = is_dir($mvccDir) ? array_diff(scandir($mvccDir) ?: [], [".", ".."]) : [];
                    if (is_dir($mvccDir) && empty($left)) @rmdir($mvccDir);
                }

                self::dropTableFromIndex($database, $table);

                if (is_file($lockFile)) {
                    @unlink($lockFile);
                }
            }

            return $ok;
        });

        if ($res) {
            self::dropSchemaTable($database, $table);
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
        }

        return $res;
    }


    /**
     * Fügt Daten ein.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $data Übergabewert.
     * @return int Rückgabewert.
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        if (!is_array($data)) {
            return -1;
        }

        $file = self::makePath($database, $table);

        if (method_exists(static::class, "runDataTriggers")) {
            $trigger = self::runDataTriggers("beforeInsert", $database, $table, ["data" => $data]);
            if (($trigger["ok"] ?? true) === false) return -1;
            if (isset($trigger["context"]["data"]) && is_array($trigger["context"]["data"])) $data = $trigger["context"]["data"];
        }

        if (!file_exists($file)) {
            return -1;
        }

        if (method_exists(static::class, "assertPartitionWritable") && !self::assertPartitionWritable($database, $table, [$data])) {
            return -1;
        }

        if (self::inTransaction()) {
            if (!self::checkTransactionTimeout()) return -1;
            $id = self::reserveTransactionId($database, $table, $data);
            $data["id"] = $id;
            self::$txOps[] = ["type" => "insert", "instance" => self::getInstance(), "db" => $database, "table" => $table, "data" => $data, "id" => $id];
            return $id;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $data) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                self::ensureHeader($base, array_keys($data));

                if (!self::writeTable($file, $base)) {
                    return -1;
                }
            }

            $header = $base[0];
            $meta = self::readMeta($metaFile);
            $next = (int)($meta["last_id"] ?? 0) + 1;

            $id = isset($data["id"]) ? (int)$data["id"] : $next;

            if ($id <= 0) {
                $id = $next;
            }

            $row = self::buildRowFromHeader($header, $data, $id);
            $full = self::applyOps($base, self::readAppendOps($appendFile));

            if (method_exists(static::class, "prepareSchemaRow")) {
                $schemaPrepared = self::prepareSchemaRow($database, $table, $row, $full, $id, true);
                if (!($schemaPrepared["ok"] ?? false)) {
                    return -1;
                }
                $row = $schemaPrepared["row"];
            }

            if (!GBDBStorage::validateConstraints($full, $row, $meta["constraints"] ?? [], $id)) {
                return -1;
            }

            $ok = self::appendOp($appendFile, [
                "op" => "ins",
                "row" => $row,
                "ts" => time()
            ]);

            if (!$ok) {
                return -1;
            }

            if (method_exists(static::class, "writeRowVersion")) {
                self::writeRowVersion($database, $table, $id, "insert", [], $row);
            }

            $meta["last_id"] = max((int)($meta["last_id"] ?? 0), $id);
            $meta["rows"] = (int)($meta["rows"] ?? 0) + 1;
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + 1;

            self::writeMeta($metaFile, $meta);

            return $id;
        });

        if (is_int($res) && $res > 0) {
            if (method_exists(static::class, 'appendReplicationEvent')) self::appendReplicationEvent('insert', ['database' => $database, 'table' => $table, 'id' => $res]);
            if (method_exists(static::class, 'recordPartitionStats')) {
                $insertedRow = self::getData($database, $table, true, "id", $res);
                if (is_array($insertedRow) && !empty($insertedRow)) self::recordPartitionStats($database, $table, $insertedRow);
            }
            if (method_exists(static::class, 'rebuildIndexes')) self::rebuildIndexes($database, $table);
            self::syncStorageForTable($database, $table, "insert");
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
            if (method_exists(static::class, 'auditDataChange') || method_exists(static::class, 'runDataTriggers')) {
                $insertedRow = self::getData($database, $table, true, "id", $res);
                if (method_exists(static::class, 'auditDataChange')) self::auditDataChange('insert', $database, $table, [], is_array($insertedRow) ? $insertedRow : []);
                if (method_exists(static::class, 'runDataTriggers')) self::runDataTriggers('afterInsert', $database, $table, ["id" => $res, "row" => is_array($insertedRow) ? $insertedRow : []]);
                if (method_exists(static::class, 'enqueueEvent')) self::enqueueEvent('insert', $database, $table, ["id" => $res]);
            }
        }

        return is_int($res) ? $res : -1;
    }


    /**
     * Löscht Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        if (method_exists(static::class, "runDataTriggers")) {
            $trigger = self::runDataTriggers("beforeDelete", $database, $table, ["where" => $where, "is" => $is]);
            if (($trigger["ok"] ?? true) === false) return false;
        }

        if (self::inTransaction()) {
            if (!self::checkTransactionTimeout()) return false;
            self::$txOps[] = ["type" => "delete", "instance" => self::getInstance(), "db" => $database, "table" => $table, "where" => $where, "is" => $is];
            return true;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $where, $is) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $hasHeader = isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]);
            $ids = [];
            $beforeRows = [];

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($hasHeader && $i === 0) {
                    continue;
                }

                if (isset($row[$where]) && $row[$where] == $is && isset($row["id"])) {
                    $rid = (int)$row["id"];
                    $ids[] = $rid;
                    $beforeRows[$rid] = $row;
                }
            }

            if (empty($ids)) {
                return false;
            }

            if (method_exists(static::class, "assertPartitionWritable") && !self::assertPartitionWritable($database, $table, array_values($beforeRows))) {
                return false;
            }

            if (method_exists(static::class, "beforeRelationDelete")) {
                $rel = self::beforeRelationDelete($database, $table, array_values($beforeRows));
                if (!($rel["ok"] ?? false)) return false;
            }

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op" => "del",
                    "id" => $id,
                    "ts" => time()
                ])) {
                    return false;
                }

                if (method_exists(static::class, "writeRowVersion")) {
                    self::writeRowVersion($database, $table, $id, "delete", (array)($beforeRows[$id] ?? []), []);
                }
            }

            GBDBStorage::markTombstones($file, $ids);

            $meta = self::readMeta($metaFile);
            $meta["rows"] = max(0, (int)($meta["rows"] ?? 0) - count($ids));
            $meta["deleted_rows"] = (int)($meta["deleted_rows"] ?? 0) + count($ids);
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            if (method_exists(static::class, 'appendReplicationEvent')) self::appendReplicationEvent('delete', ['database' => $database, 'table' => $table, 'where' => $where, 'is' => $is]);
            if (method_exists(static::class, 'rebuildIndexes')) self::rebuildIndexes($database, $table);
            self::syncStorageForTable($database, $table, "delete");
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
            if (method_exists(static::class, 'auditDataChange')) self::auditDataChange('delete', $database, $table, ["where" => $where, "is" => $is], []);
            if (method_exists(static::class, 'runDataTriggers')) self::runDataTriggers('afterDelete', $database, $table, ["where" => $where, "is" => $is]);
            if (method_exists(static::class, 'enqueueEvent')) self::enqueueEvent('delete', $database, $table, ["where" => $where, "is" => $is]);
        }

        return (bool)$res;
    }


    /**
     * Bearbeitet Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @param mixed $newData Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        if (!is_array($newData)) {
            return false;
        }

        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        if (method_exists(static::class, "runDataTriggers")) {
            $trigger = self::runDataTriggers("beforeUpdate", $database, $table, ["where" => $where, "is" => $is, "data" => $newData]);
            if (($trigger["ok"] ?? true) === false) return false;
            if (isset($trigger["context"]["data"]) && is_array($trigger["context"]["data"])) $newData = $trigger["context"]["data"];
        }

        if (self::inTransaction()) {
            if (!self::checkTransactionTimeout()) return false;
            self::$txOps[] = ["type" => "edit", "instance" => self::getInstance(), "db" => $database, "table" => $table, "where" => $where, "is" => $is, "data" => $newData];
            return true;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $where, $is, $newData) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $header = $full[0];
            $hasHeader = isset($header) && is_array($header) && self::isHeaderRow($header);

            $set = [];

            foreach ($newData as $key => $value) {
                if ($key === "id") {
                    continue;
                }

                if ($hasHeader && array_key_exists($key, $header)) {
                    $set[$key] = $value;
                }
            }

            if (empty($set)) {
                return false;
            }

            $ids = [];

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($hasHeader && $i === 0) {
                    continue;
                }

                if (isset($row[$where]) && $row[$where] == $is && isset($row["id"])) {
                    $ids[] = (int)$row["id"];
                }
            }

            if (empty($ids)) {
                return false;
            }

            $rowsById = [];

            foreach ($full as $row) {
                if (is_array($row) && isset($row["id"])) {
                    $rowsById[(int)$row["id"]] = $row;
                }
            }

            $meta = self::readMeta($metaFile);

            foreach ($ids as $id) {
                $before = $rowsById[$id] ?? [];

                if (empty($before)) {
                    continue;
                }

                if (method_exists(static::class, "assertPartitionWritable") && !self::assertPartitionWritable($database, $table, [$before])) {
                    return false;
                }

                $version = method_exists(static::class, "nextRowVersion") ? self::nextRowVersion($before) : ((int)($before["_gbdb_version"] ?? 0) + 1);
                $set["_gbdb_version"] = $version;
                $set["_gbdb_revision"] = $version;
                $set["_gbdb_visible_from"] = microtime(true);
                $after = array_merge($before, $set);

                if (method_exists(static::class, "prepareSchemaRow")) {
                    $schemaPrepared = self::prepareSchemaRow($database, $table, $after, $full, $id, true);
                    if (!($schemaPrepared["ok"] ?? false)) {
                        return false;
                    }
                    $after = $schemaPrepared["row"];
                    $set = [];
                    foreach ($after as $aKey => $aValue) {
                        if ($aKey === "id") continue;
                        if (!array_key_exists($aKey, $before) || $before[$aKey] !== $aValue) {
                            $set[$aKey] = $aValue;
                        }
                    }
                }

                if (!GBDBStorage::validateConstraints($full, $after, $meta["constraints"] ?? [], $id)) {
                    return false;
                }

                if (method_exists(static::class, "afterRelationUpdate")) {
                    $rel = self::afterRelationUpdate($database, $table, [$id => $before], [$id => $after]);
                    if (!($rel["ok"] ?? false)) return false;
                }

                if (!self::appendOp($appendFile, [
                    "op" => "upd",
                    "id" => $id,
                    "set" => $set,
                    "ts" => time()
                ])) {
                    return false;
                }

                if (method_exists(static::class, "writeRowVersion")) {
                    self::writeRowVersion($database, $table, $id, "update", $before, $after);
                }
            }

            $meta = self::readMeta($metaFile);
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            if (method_exists(static::class, 'appendReplicationEvent')) self::appendReplicationEvent('edit', ['database' => $database, 'table' => $table, 'where' => $where, 'is' => $is]);
            if (method_exists(static::class, 'rebuildIndexes')) self::rebuildIndexes($database, $table);
            self::syncStorageForTable($database, $table, "edit");
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
            if (method_exists(static::class, 'auditDataChange')) self::auditDataChange('update', $database, $table, ["where" => $where, "is" => $is], is_array($newData) ? $newData : []);
            if (method_exists(static::class, 'runDataTriggers')) self::runDataTriggers('afterUpdate', $database, $table, ["where" => $where, "is" => $is, "data" => $newData]);
            if (method_exists(static::class, 'enqueueEvent')) self::enqueueEvent('update', $database, $table, ["where" => $where, "is" => $is]);
        }

        return (bool)$res;
    }


    /**
     * Holt Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $filter Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function getData(
        string $database,
        string $table,
        bool $filter = false,
        mixed $where = "",
        mixed $is = ""
    ): mixed {
        $reader = function () use ($database, $table, $filter, $where, $is) {
            $file = self::makePath($database, $table);
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return [];
            }

            $appendFile = self::appendFileForTable($database, $table);
            $ops = self::readAppendOps($appendFile);

            $full = self::applyOps($base, $ops);

            if ($filter) {
                $meta = self::readMeta(self::metaFileForTable($database, $table));
                $indexes = $meta["indexes"] ?? [];
                $defs = is_array($meta["index_definitions"] ?? null) ? $meta["index_definitions"] : [];
                $ids = [];

                foreach ($defs as $definition) {
                    $cols = is_array($definition["columns"] ?? null) ? $definition["columns"] : [];
                    $type = (string)($definition["type"] ?? "");
                    if (count($cols) === 1 && (string)$cols[0] === (string)$where && $type !== "fulltext") {
                        $ids = GBDBStorage::advancedIndexLookup($file, $definition, $is);
                        break;
                    }
                }

                if (empty($ids) && in_array((string)$where, $indexes, true)) {
                    $ids = GBDBStorage::indexLookup($file, (string)$where, $is);
                }

                if (!empty($ids)) {
                    foreach ($full as $i => $row) {
                        if (!is_array($row)) continue;
                        if ($i === 0 && self::isHeaderRow($row)) continue;
                        if (isset($row["id"]) && in_array((int)$row["id"], $ids, true)) return $row;
                    }
                    return [];
                }
            }

            $hasHeader = isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]);

            if ($filter) {
                foreach ($full as $i => $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    if ($hasHeader && $i === 0) {
                        continue;
                    }

                    if (isset($row[$where]) && $row[$where] == $is) {
                        return $row;
                    }
                }

                return [];
            }

            if ($hasHeader) {
                unset($full[0]);
                $full = array_values($full);
            }

            return $full;
        };

        if (method_exists(static::class, "withReadLock")) {
            $locked = self::withReadLock("table-file:" . self::lockFileForTable($database, $table), $reader);
            return $locked === false ? [] : $locked;
        }

        return $reader();
    }

    /**
     * Prüft, ob ein Element existiert.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $row = self::getData($database, $table, true, $where, $is);
        return is_array($row) && !empty($row);
    }


    /**
     * Listet Datenbanken der aktiven Instanz.
     * @return array Rückgabewert.
     */
    public static function listDBs(): array {
        $instancePath = self::instancePath(false);

        if (!is_dir($instancePath)) {
            return [];
        }

        if (!Vars::crypt_data()) {
            $dirs = [];
            $tmp = scandir($instancePath);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (is_dir($instancePath . $entry)) {
                    $dirs[] = $entry;
                }
            }

            sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);

            return $dirs;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);

        if ($instanceToken === null) {
            return [];
        }

        $map = self::readIndex(self::dbIndexFileByInstanceToken($instanceToken));
        $out = [];

        foreach ($map as $plain => $token) {
            if (is_dir($instancePath . $token)) {
                $out[] = $plain;
            }
        }

        sort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }


    /**
     * Listet Tabellen einer Datenbank.
     * @param string $database Übergabewert.
     * @param bool $descending Übergabewert.
     * @return array Rückgabewert.
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if ($database === "") {
            return [];
        }

        $ext = Vars::data_extension();

        if (!Vars::crypt_data()) {
            $databasePath = self::instancePath(false) . $database . "/";

            if (!is_dir($databasePath)) {
                return [];
            }

            $tables = [];
            $order = $descending ? 1 : 0;
            $tmp = scandir($databasePath, $order);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (str_ends_with($entry, ".lock")) {
                    continue;
                }

                if (!str_ends_with($entry, $ext)) {
                    continue;
                }

                if (str_starts_with($entry, "__meta__")) {
                    continue;
                }

                if (str_starts_with($entry, "__append__")) {
                    continue;
                }

                if (str_starts_with($entry, "__idx__")) {
                    continue;
                }

                if (str_starts_with($entry, "__idxa__")) {
                    continue;
                }

                $tables[] = str_replace($ext, "", $entry);
            }

            return $tables;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);
        $dbToken = self::getDbToken($database, false);

        if ($instanceToken === null || $dbToken === null) {
            return [];
        }

        $databasePath = self::instancePath(false) . $dbToken . "/";

        if (!is_dir($databasePath)) {
            return [];
        }

        $map = self::readIndex(self::tableIndexFileByTokens($instanceToken, $dbToken));
        $tables = [];

        foreach ($map as $plain => $token) {
            $file = $databasePath . $token . $ext;

            if (is_file($file)) {
                $tables[] = $plain;
            }
        }

        if ($descending) {
            rsort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $tables;
    }


    /**
     * Löscht eine komplette Datenbank innerhalb der aktiven Instanz.
     * @param string $database Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteAll(string $database): bool {
        $ok = true;
        $tables = self::listTables($database);

        foreach ($tables as $table) {
            if (!self::deleteTable($database, $table)) {
                $ok = false;
                break;
            }
        }

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        if ($ok) {
            self::dropSchemaDatabase($database);
        }

        return $ok;
    }


    /**
     * Gibt die nächste ID zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return int Rückgabewert.
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return 0;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile) {
            $meta = self::readMeta($metaFile);
            return (int)($meta["last_id"] ?? 0) + 1;
        });

        return is_int($res) ? $res : 0;
    }


    /**
     * Gibt die Keys einer Tabelle zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getKeys(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (empty($db) || !isset($db[0]) || !is_array($db[0])) {
            return [];
        }

        return array_keys($db[0]);
    }


    /**
     * Führt eine GreenQL-Abfrage aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function query(string $script, array $ctx = [], array $params = []): array {
        $ctx["instance"] = self::instanceName();
        return GreenQL::run($script, $ctx, $params);
    }


    /**
     * Führt ein GreenQL-Script aus.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        $originalPath = $path;
        if (!is_file($path)) {
            $rel = str_replace('\\', '/', trim($path));
            $rel = preg_replace('#/+#', '/', $rel) ?? '';
            $parts = [];

            foreach (explode('/', $rel) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }

                if ($part === '..') {
                    $parts = [];
                    break;
                }

                $parts[] = $part;
            }

            $rel = implode('/', $parts);
            $scriptRoot = dirname(rtrim(Vars::DB_PATH(), '/')) . '/.scripts';
            $rootReal = realpath($scriptRoot) ?: $scriptRoot;
            $candidates = $rel === '' ? [] : [$scriptRoot . '/' . $rel];
            if ($rel !== '' && !str_ends_with(strtolower($rel), '.gql')) $candidates[] = $scriptRoot . '/' . $rel . '.gql';

            foreach ($candidates as $candidate) {
                $candidateReal = realpath($candidate);

                if ($candidateReal !== false && is_file($candidateReal) && str_starts_with(str_replace('\\', '/', $candidateReal), rtrim(str_replace('\\', '/', $rootReal), '/') . '/')) {
                    $path = $candidateReal;
                    break;
                }
            }
        }
        if (!is_file($path)) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script nicht gefunden: " . $originalPath]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "instance" => self::instanceName(),
                    "db" => GreenQL::cleanName((string)($ctx["db"] ?? "")),
                    "table" => GreenQL::cleanName((string)($ctx["table"] ?? ""))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        $script = file_get_contents($path);

        if ($script === false) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script konnte nicht gelesen werden: " . $path]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "instance" => self::instanceName(),
                    "db" => GreenQL::cleanName((string)($ctx["db"] ?? "")),
                    "table" => GreenQL::cleanName((string)($ctx["table"] ?? ""))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        $ctx["instance"] = self::instanceName();

        return self::query($script, $ctx, $params);
    }
}
