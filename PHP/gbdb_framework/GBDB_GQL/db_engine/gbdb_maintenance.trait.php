<?php

trait GBDB_MaintenanceTrait {

    /**
     * Komprimiert eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function compactTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);

            if (empty($ops)) {
                return true;
            }

            $full = self::applyOps($base, $ops);

            if (!self::writeTable($file, $full)) {
                return false;
            }

            if (!GBDBStorage::atomicWrite($appendFile, "")) {
                return false;
            }

            $meta = self::readMeta($metaFile);

            $maxId = (int)($meta["last_id"] ?? 0);
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($i === 0 && self::isHeaderRow($row)) {
                    continue;
                }

                $rows++;

                if (isset($row["id"])) {
                    $maxId = max($maxId, (int)$row["id"]);
                }
            }

            $meta["rows"] = $rows;
            $meta["last_id"] = $maxId;
            $meta["append_ops"] = 0;
            $meta["deleted_rows"] = 0;
            $meta["checksum"] = GBDBStorage::checksum($full);
            $meta["last_compaction"] = time();

            GBDBStorage::rebuildIndexes($file, $meta, $full);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::syncStorageForTable($database, $table, "compact");
        }

        return (bool)$res;
    }


    /**
     * Erstellt einen Snapshot einer Tabelle.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $reason Grund.
     * @return string Snapshot-ID oder leer.
     */
    public static function snapshot(string $database, string $table, string $reason = "manual"): string {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return "";

        $extras = [
            self::metaFileForTable($database, $table),
            self::appendFileForTable($database, $table),
            self::appendFileForTable($database, $table) . ".wal"
        ];

        $id = GBDBStorage::snapshot($file, $extras, $reason);

        if ($id !== "") {
            $metaFile = self::metaFileForTable($database, $table);
            $meta = self::readMeta($metaFile);
            $meta["last_snapshot"] = time();
            self::writeMeta($metaFile, $meta);
        }

        return $id;
    }


    /**
     * Erstellt einen Index. Unterstützt primary, unique, single, composite, sorted, range, prefix und fulltext.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string|array $column Spalte oder Spaltenliste.
     * @param string $type Indextyp.
     * @param array $options Optionen.
     * @return bool true bei Erfolg.
     */
    public static function createIndex(string $database, string $table, string|array $column, string $type = 'single', array $options = []): bool {
        $columns = is_array($column) ? $column : [$column];
        $columns = array_values(array_filter(array_map(fn($c) => Format::cleanString((string)$c), $columns), fn($c) => $c !== '' && $c !== 'id'));
        $type = strtolower(trim($type));
        if ($type === '') $type = count($columns) > 1 ? 'composite' : 'single';
        if (empty($columns)) return false;

        $file = self::makePath($database, $table);
        if (!is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $columns, $type, $options) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;
            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $header = $full[0] ?? [];
            if (!is_array($header)) return false;
            foreach ($columns as $col) if (!array_key_exists($col, $header)) return false;

            $name = (string)($options['name'] ?? GBDBStorage::indexName($type, $columns));
            $definition = GBDBStorage::normalizeIndexDefinition($name, $type, $columns, $options);
            if (!GBDBStorage::writeAdvancedIndex($file, $definition, $full)) return false;

            if (count($columns) === 1 && in_array($type, ['single', 'unique', 'sorted', 'range', 'prefix', 'primary'], true)) {
                GBDBStorage::writeIndex($file, $columns[0], $full);
            }

            $meta = self::readMeta($metaFile);
            if (!isset($meta['indexes']) || !is_array($meta['indexes'])) $meta['indexes'] = [];
            if (!isset($meta['index_definitions']) || !is_array($meta['index_definitions'])) $meta['index_definitions'] = [];
            foreach ($columns as $col) if (!in_array($col, $meta['indexes'], true)) $meta['indexes'][] = $col;
            sort($meta['indexes'], SORT_NATURAL | SORT_FLAG_CASE);
            $meta['index_definitions'][$definition['name']] = $definition;

            if (!isset($meta['constraints']) || !is_array($meta['constraints'])) $meta['constraints'] = [];
            if (!empty($definition['unique'])) {
                foreach ($columns as $col) {
                    if (!isset($meta['constraints'][$col]) || !is_array($meta['constraints'][$col])) $meta['constraints'][$col] = [];
                    if (count($columns) === 1) $meta['constraints'][$col]['unique'] = true;
                }
            }

            $meta['checksum'] = GBDBStorage::checksum($full);
            $meta['index_checksum'] = GBDBStorage::checksum($meta['index_definitions']);
            GBDBStorage::writeAdvancedIndexMeta($file, $meta['index_definitions']);
            self::writeMeta($metaFile, $meta);
            return true;
        });

        return (bool)$res;
    }

    /** Erstellt einen Primary-Index. */
    public static function createPrimaryIndex(string $database, string $table, string $column = 'id'): bool {
        return self::createIndex($database, $table, $column === 'id' ? self::firstUserColumn($database, $table) : $column, 'primary', ['unique' => true]);
    }

    /** Erstellt einen Unique-Index. */
    public static function createUniqueIndex(string $database, string $table, string|array $columns): bool {
        return self::createIndex($database, $table, $columns, 'unique', ['unique' => true]);
    }

    /** Erstellt einen Composite-Index. */
    public static function createCompositeIndex(string $database, string $table, array $columns): bool {
        return self::createIndex($database, $table, $columns, 'composite');
    }

    /** Erstellt einen Sorted-Index. */
    public static function createSortedIndex(string $database, string $table, string|array $columns): bool {
        return self::createIndex($database, $table, $columns, 'sorted', ['sorted' => true]);
    }

    /** Erstellt einen Range-Index. */
    public static function createRangeIndex(string $database, string $table, string $column): bool {
        return self::createIndex($database, $table, $column, 'range', ['sorted' => true]);
    }

    /** Erstellt einen Prefix-Index. */
    public static function createPrefixIndex(string $database, string $table, string $column): bool {
        return self::createIndex($database, $table, $column, 'prefix', ['prefix' => true]);
    }

    /** Erstellt einen Fulltext-Index. */
    public static function createFulltextIndex(string $database, string $table, array $columns = []): bool {
        if (empty($columns)) $columns = array_values(array_filter(self::getKeys($database, $table), fn($k) => $k !== 'id'));
        return self::createIndex($database, $table, $columns, 'fulltext', ['fulltext' => true]);
    }

    /** Gibt die erste Nicht-ID-Spalte zurueck. */
    private static function firstUserColumn(string $database, string $table): string {
        foreach (self::getKeys($database, $table) as $key) if ($key !== 'id') return (string)$key;
        return '';
    }

    /**
     * Löscht einen Index.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte oder Indexname.
     * @return bool true bei Erfolg.
     */
    public static function dropIndex(string $database, string $table, string $column): bool {
        $column = Format::cleanString($column);
        $file = self::makePath($database, $table);
        if ($column === '' || !is_file($file)) return false;
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        return (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $column) {
            $meta = self::readMeta($metaFile);
            $defs = is_array($meta['index_definitions'] ?? null) ? $meta['index_definitions'] : [];
            foreach ($defs as $name => $def) {
                $cols = (array)($def['columns'] ?? []);
                if ($name === $column || in_array($column, $cols, true)) {
                    @unlink(GBDBStorage::advancedIndexFile($file, (string)$name));
                    unset($defs[$name]);
                }
            }
            $meta['index_definitions'] = $defs;
            $still = [];
            foreach ($defs as $def) foreach ((array)($def['columns'] ?? []) as $col) $still[$col] = true;
            $meta['indexes'] = array_keys($still);
            GBDBStorage::deleteIndex($file, $column);
            GBDBStorage::writeAdvancedIndexMeta($file, $defs);
            return self::writeMeta($metaFile, $meta);
        });
    }

    /**
     * Gibt alle Indexe einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Index-Spalten/Definitionen.
     */
    public static function listIndexes(string $database, string $table): array {
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        if (!empty($meta['index_definitions']) && is_array($meta['index_definitions'])) return $meta['index_definitions'];
        return array_values($meta['indexes'] ?? []);
    }

    /** Baut alle bekannten Indexe einer Tabelle neu. */
    public static function rebuildIndexes(string $database, string $table): bool {
        $file = self::makePath($database, $table);
        if (!is_file($file)) return false;
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        return (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;
            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $meta = self::readMeta($metaFile);
            $defs = is_array($meta['index_definitions'] ?? null) ? $meta['index_definitions'] : [];

            if (empty($defs)) {
                foreach (($meta['indexes'] ?? []) as $column) {
                    $column = (string)$column;
                    if ($column === '' || $column === 'id') continue;
                    $defs[GBDBStorage::indexName('single', [$column])] = GBDBStorage::normalizeIndexDefinition('', 'single', [$column]);
                }
            }

            foreach ($defs as $name => $definition) {
                $definition['name'] = (string)$name;
                GBDBStorage::writeAdvancedIndex($file, $definition, $full);
                $cols = (array)($definition['columns'] ?? []);
                if (count($cols) === 1) GBDBStorage::writeIndex($file, $cols[0], $full);
            }

            $meta['index_definitions'] = $defs;
            $meta['index_checksum'] = GBDBStorage::checksum($defs);
            $meta['checksum'] = GBDBStorage::checksum($full);
            GBDBStorage::writeAdvancedIndexMeta($file, $defs);
            self::writeMeta($metaFile, $meta);
            return true;
        });
    }

    /** Prüft Indexdateien und baut defekte Indexe neu. */
    public static function repairIndexes(string $database, string $table): array {
        $before = self::verifyIndexes($database, $table);
        $ok = self::rebuildIndexes($database, $table);
        $after = self::verifyIndexes($database, $table);
        return ['ok' => $ok && (bool)($after['ok'] ?? false), 'before' => $before, 'after' => $after];
    }

    /** Prueft Index-Checksums. */
    public static function verifyIndexes(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        $defs = is_array($meta['index_definitions'] ?? null) ? $meta['index_definitions'] : [];
        $errors = [];
        foreach ($defs as $name => $definition) {
            $idx = GBDBStorage::readAdvancedIndex($file, (string)$name);
            if (empty($idx)) { $errors[] = 'index_missing:' . $name; continue; }
            $map = is_array($idx['map'] ?? null) ? $idx['map'] : [];
            if (!hash_equals((string)($idx['checksum'] ?? ''), GBDBStorage::checksum($map))) $errors[] = 'index_checksum_mismatch:' . $name;
        }
        return ['ok' => empty($errors), 'errors' => $errors, 'indexes' => array_keys($defs)];
    }

    /**
     * Prüft eine Tabelle auf grundlegende Konsistenz.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Diagnose-Daten.
     */
    public static function health(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $warnings = [];
        $errors = [];

        if (!is_file($file)) {
            return ["ok" => false, "errors" => ["table_not_found"], "warnings" => []];
        }

        $base = self::ini($file);

        if (empty($base) || !isset($base[0]) || !is_array($base[0]) || !self::isHeaderRow($base[0])) {
            $errors[] = "invalid_or_missing_header";
        }

        $ops = self::readAppendOps($appendFile);
        $full = self::applyOps($base, $ops);
        $meta = self::readMeta($metaFile);
        $rows = 0;
        $ids = [];

        foreach ($full as $i => $row) {
            if (!is_array($row)) continue;
            if ($i === 0 && self::isHeaderRow($row)) continue;
            $rows++;

            if (!isset($row["id"])) {
                $warnings[] = "row_without_id";
                continue;
            }

            $id = (int)$row["id"];

            if (isset($ids[$id])) {
                $errors[] = "duplicate_id:" . $id;
            }

            $ids[$id] = true;
        }

        if ($rows !== (int)($meta["rows"] ?? 0)) {
            $warnings[] = "meta_rows_mismatch";
        }

        if ((int)($meta["append_ops"] ?? 0) > 0 && !is_file($appendFile)) {
            $warnings[] = "append_file_missing";
        }

        if (GBDBStorage::shouldCompact($meta, $appendFile)) {
            $warnings[] = "compaction_recommended";
        }

        $storage = GBDBStorage::verifyStorage($file);

        if (($storage["ok"] ?? false) !== true) {
            $warnings[] = "storage_sidecar_needs_repair";
        }

        return [
            "ok" => empty($errors),
            "errors" => array_values(array_unique($errors)),
            "warnings" => array_values(array_unique($warnings)),
            "meta" => $meta,
            "rows_real" => $rows,
            "append_ops_real" => count($ops),
            "storage" => $storage
        ];
    }


    /**
     * Repariert eine Tabelle durch Komprimieren und Index-Neuaufbau.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return bool true bei Erfolg.
     */
    public static function repairTable(string $database, string $table): bool {
        self::snapshot($database, $table, "before_repair");

        if (!self::compactTable($database, $table)) {
            return false;
        }

        if (!self::rebuildIndexes($database, $table)) {
            return false;
        }

        $file = self::makePath($database, $table);
        $rows = self::ini($file);
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        $repair = GBDBStorage::repairStorage($file, $rows, $meta);

        if (($repair["ok"] ?? false) === true) {
            $meta["storage_last_repair"] = time();
            $meta["storage_checksum"] = $repair["verify"]["checksum"] ?? ($repair["sync"]["checksum"] ?? "");
            $meta["storage_verified_at"] = time();
            self::writeMeta(self::metaFileForTable($database, $table), $meta);
        }

        return (bool)($repair["ok"] ?? false);
    }


    /**
     * Gibt die Meta-Daten einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Meta-Daten.
     */
    public static function meta(string $database, string $table): array {
        return self::readMeta(self::metaFileForTable($database, $table));
    }


    /**
     * Fügt einen einfachen Constraint für eine Tabellenspalte hinzu.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $type Constraint-Typ: unique oder required.
     * @return bool true bei Erfolg.
     */
    public static function addConstraint(string $database, string $table, string $column, string $type): bool {
        $column = Format::cleanString($column);
        $type = strtolower(Format::cleanString($type));
        $file = self::makePath($database, $table);

        if ($column === "" || $column === "id" || !is_file($file)) return false;
        if (!in_array($type, ["unique", "required"], true)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column, $type) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            if (!array_key_exists($column, $base[0])) return false;

            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $meta = self::readMeta($metaFile);
            $constraints = $meta["constraints"] ?? [];

            if (!isset($constraints[$column]) || !is_array($constraints[$column])) {
                $constraints[$column] = [];
            }

            $constraints[$column][$type] = true;

            foreach ($full as $i => $row) {
                if (!is_array($row)) continue;
                if ($i === 0 && self::isHeaderRow($row)) continue;

                if (!GBDBStorage::validateConstraints($full, $row, $constraints, isset($row["id"]) ? (int)$row["id"] : null)) {
                    return false;
                }
            }

            $meta["constraints"] = $constraints;

            if ($type === "unique") {
                $idx = $meta["indexes"] ?? [];
                if (!in_array($column, $idx, true)) $idx[] = $column;
                sort($idx, SORT_NATURAL | SORT_FLAG_CASE);
                $meta["indexes"] = $idx;
                GBDBStorage::writeIndex($file, $column, $full);
            }

            self::writeMeta($metaFile, $meta);
            return true;
        });

        return (bool)$res;
    }


    /**
     * Entfernt einen einfachen Constraint von einer Tabellenspalte.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $type Constraint-Typ: unique oder required.
     * @return bool true bei Erfolg.
     */
    public static function dropConstraint(string $database, string $table, string $column, string $type): bool {
        $column = Format::cleanString($column);
        $type = strtolower(Format::cleanString($type));
        $file = self::makePath($database, $table);

        if ($column === "" || !is_file($file)) return false;
        if (!in_array($type, ["unique", "required"], true)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile, $column, $type) {
            $meta = self::readMeta($metaFile);
            $constraints = $meta["constraints"] ?? [];

            if (isset($constraints[$column][$type])) {
                unset($constraints[$column][$type]);
            }

            if (isset($constraints[$column]) && empty($constraints[$column])) {
                unset($constraints[$column]);
            }

            $meta["constraints"] = $constraints;
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }


    /**
     * Gibt die Constraints einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Constraints.
     */
    public static function listConstraints(string $database, string $table): array {
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        return $meta["constraints"] ?? [];
    }


    /**
     * Führt Vacuum/Cleanup für eine Tabelle aus.
     *
     * Vacuum komprimiert zuerst die klassische GBDB-Append-Struktur und baut danach
     * die neue Page-/Chunk-Storage-Struktur frisch auf. Dadurch werden Tombstones,
     * freie Slots, verwaiste Temp-Dateien und alte Chunk-Dateien bereinigt.
     *
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Vacuum-Bericht.
     */
    public static function vacuum(string $database, string $table): array {
        if (!self::compactTable($database, $table)) {
            return ["ok" => false, "error" => "compact_failed"];
        }

        $file = self::makePath($database, $table);
        if (!is_file($file)) return ["ok" => false, "error" => "table_not_found"];

        $rows = self::ini($file);
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        $report = GBDBStorage::vacuumStorage($file, $rows, $meta);

        if (($report["ok"] ?? false) === true) {
            $meta["storage_checksum"] = $report["checksum"] ?? "";
            $meta["storage_verified_at"] = time();
            self::writeMeta(self::metaFileForTable($database, $table), $meta);
        }

        return $report;
    }


    /**
     * Stellt einen Tabellen-Snapshot wieder her.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $snapshotId Snapshot-ID.
     * @return bool true bei Erfolg.
     */
    public static function restoreSnapshot(string $database, string $table, string $snapshotId): bool {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $snapshotId) {
            return GBDBStorage::restoreSnapshot($file, $snapshotId);
        });

        return (bool)$res;
    }
}
