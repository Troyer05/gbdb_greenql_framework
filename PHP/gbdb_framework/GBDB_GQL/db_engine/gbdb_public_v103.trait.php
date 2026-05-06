<?php

declare(strict_types=1);

trait GBDB_PublicV103Trait {
    public static function getActualInstance(): string {
        return self::getInstance();
    }

    /**
     * Kurzer Alias für getData().
     *
     * Ziel-Signatur:
     * GBDB::get($base, $table, $search = false, $where = '', $is = '')
     *
     * Rückwärtskompatibel:
     * GBDB::get($base, $table, $where, $is, $options)
     *
     * @param string $base Base/Datenbank.
     * @param string $table Tabelle.
     * @param mixed $search Bool für getData-Filter oder alte WHERE-Spalte.
     * @param mixed $where WHERE-Spalte oder alter Suchwert.
     * @param mixed $is Suchwert oder alte Options.
     * @param array $options Optionale Sortierung/Paginierung.
     * @return mixed Rückgabewert von getData().
     */
    public static function get(string $base, string $table, mixed $search = false, mixed $where = '', mixed $is = '', array $options = []): mixed {
        $useGetDataSignature = is_bool($search);

        if ($useGetDataSignature) {
            $data = self::getData($base, $table, $search, $where, $is);
        } else {
            $legacyWhere = $search;
            $legacyIs = $where;
            $legacyOptions = is_array($is) ? $is : $options;
            $data = self::getData($base, $table, $legacyWhere !== '' && $legacyWhere !== null, $legacyWhere, $legacyIs);
            $options = $legacyOptions;
        }

        if (!is_array($data) || empty($options)) {
            return $data;
        }

        if (isset($options['sort']) && is_string($options['sort']) && $options['sort'] !== '') {
            $field = $options['sort'];
            $dir = strtoupper((string)($options['dir'] ?? 'ASC'));

            usort($data, function ($a, $b) use ($field, $dir) {
                $av = is_array($a) ? ($a[$field] ?? null) : null;
                $bv = is_array($b) ? ($b[$field] ?? null) : null;
                $cmp = is_numeric($av) && is_numeric($bv)
                    ? ((float)$av <=> (float)$bv)
                    : strnatcasecmp((string)$av, (string)$bv);

                return $dir === 'DESC' ? -$cmp : $cmp;
            });
        }

        $offset = max(0, (int)($options['offset'] ?? 0));
        $limit = isset($options['limit']) ? (int)$options['limit'] : 0;

        if ($offset > 0 || $limit > 0) {
            $data = array_slice($data, $offset, $limit > 0 ? $limit : null);
        }

        return $data;
    }

    public static function existsInstance(string $instance): bool {
        $instance = Format::cleanString($instance);

        return $instance !== '' && in_array($instance, self::listInstances(true), true);
    }

    public static function create(string $base, string $table = '', bool $useDataTypes = false, array|object $rows = []): bool {
        $base = Format::cleanString($base);
        $table = Format::cleanString($table);

        if ($base === '') {
            return false;
        }

        if ($table === '') {
            return self::createDatabase($base);
        }

        if (!in_array($base, self::listDBs(), true)) {
            self::createDatabase($base);
        }

        $rowData = is_object($rows) ? get_object_vars($rows) : $rows;
        $cols = [];

        if (self::isAssocArrayV103($rowData)) {
            $cols = array_keys($rowData);
        } else {
            foreach ($rowData as $row) {
                if (is_object($row)) {
                    $row = get_object_vars($row);
                }

                if (is_array($row)) {
                    $cols = array_values(array_unique(array_merge($cols, array_keys($row))));
                } elseif (is_string($row) && $row !== '') {
                    $cols[] = $row;
                }
            }
        }

        $cols = array_values(array_filter(
            array_unique(array_map('strval', $cols)),
            fn($c) => $c !== '' && $c !== 'id'
        ));

        if (empty($cols)) {
            $cols = [
                'name',
                'value',
                'created_at'
            ];
        }

        $ok = self::createTable($base, $table, $cols);

        if ($ok && $useDataTypes && self::isAssocArrayV103($rowData)) {
            $schema = self::readSchema();
            $schema[self::getInstance()][$base][$table]['types'] = $rowData;
            self::writeSchema($schema);
        }

        return $ok;
    }

    public static function exists(string $base, ?string $table = null, mixed $where = '', mixed $is = ''): bool {
        $base = Format::cleanString($base);
        $table = $table === null ? null : Format::cleanString($table);

        if ($base === '') {
            return false;
        }

        if ($table === null || $table === '') {
            return in_array($base, self::listDBs(), true);
        }

        if (!in_array($table, self::listTables($base), true)) {
            return false;
        }

        if ($where === '' || $where === null) {
            return true;
        }

        return self::elementExists($base, $table, $where, $is);
    }

    public static function edit(string $base, string $table, mixed $where, mixed $is, array|object $newDataAsObject, bool $recursive = false): bool {
        return self::editData(
            $base,
            $table,
            $where,
            $is,
            is_object($newDataAsObject) ? get_object_vars($newDataAsObject) : $newDataAsObject
        );
    }

    public static function delete(string $base, ?string $table = null, mixed $where = '', mixed $is = '', bool $recursive = false): bool {
        $base = Format::cleanString($base);
        $table = $table === null ? null : Format::cleanString($table);

        if ($base === '') {
            return false;
        }

        if ($table === null || $table === '') {
            return $recursive ? self::deleteAll($base) : self::deleteDatabase($base);
        }

        if ($where === '' || $where === null) {
            return self::deleteTable($base, $table);
        }

        return self::deleteData($base, $table, $where, $is);
    }

    public static function getNextId(string $base, string $table): int {
        return self::nextID($base, $table);
    }

    public static function fullTextSearch(string $base, string $table, string $text): array {
        return self::fulltext_search($base, $table, $text);
    }

    public static function renameTable(string $base, string $table, string $newTableName): bool {
        $keys = self::getKeys($base, $table);

        if (empty($keys) || self::exists($base, $newTableName)) {
            return false;
        }

        $rows = self::getData($base, $table);

        if (!self::createTable($base, $newTableName, array_values(array_filter($keys, fn($k) => $k !== 'id')))) {
            return false;
        }

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }

            unset($row['id']);

            self::insertData($base, $newTableName, $row);
        }

        return self::deleteTable($base, $table);
    }

    public static function renameBase(string $base, string $newBaseName): bool {
        if (!self::exists($base) || self::exists($newBaseName)) {
            return false;
        }

        if (!self::createDatabase($newBaseName)) {
            return false;
        }

        foreach (self::listTables($base) as $table) {
            $keys = self::getKeys($base, $table);

            self::createTable($newBaseName, $table, array_values(array_filter($keys, fn($k) => $k !== 'id')));

            foreach (self::getData($base, $table) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                unset($row['id']);

                self::insertData($newBaseName, $table, $row);
            }
        }

        return self::deleteAll($base);
    }

    public static function renameInstance(string $instance, string $newInstanceName): bool {
        if (!self::existsInstance($instance) || self::existsInstance($newInstanceName)) {
            return false;
        }

        if (!self::createInstance($newInstanceName)) {
            return false;
        }

        $old = self::getInstance();

        self::setInstance($instance);

        foreach (self::listDBs() as $base) {
            self::moveBase($base, $instance, $newInstanceName);
        }

        self::setInstance($old);

        return self::deleteInstance($instance, true);
    }

    public static function moveBase(string $base, string $fromInstance, string $toInstance): bool {
        $old = self::getInstance();

        self::setInstance($fromInstance);

        if (!self::exists($base)) {
            self::setInstance($old);

            return false;
        }

        foreach (self::listTables($base) as $table) {
            if (!self::moveTable($fromInstance, $base, $table, $toInstance, $base)) {
                self::setInstance($old);

                return false;
            }
        }

        self::deleteAll($base);
        self::setInstance($old);

        return true;
    }

    public static function moveTable(string $instance, string $base, string $table, string $toInstance, string $toBase): bool {
        $old = self::getInstance();

        self::setInstance($instance);

        if (!self::exists($base, $table)) {
            self::setInstance($old);

            return false;
        }

        $keys = self::getKeys($base, $table);
        $rows = self::getData($base, $table);

        self::setInstance($toInstance);
        self::createInstance($toInstance);

        if (!self::exists($toBase)) {
            self::createDatabase($toBase);
        }

        if (self::exists($toBase, $table)) {
            self::setInstance($old);

            return false;
        }

        self::createTable($toBase, $table, array_values(array_filter($keys, fn($k) => $k !== 'id')));

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }

            unset($row['id']);

            self::insertData($toBase, $table, $row);
        }

        self::setInstance($instance);

        $ok = self::deleteTable($base, $table);

        self::setInstance($old);

        return $ok;
    }

    public static function createBackup(string $pathToBackupDir = ''): array {
        return self::fullBackup($pathToBackupDir);
    }

    public static function runFile(string $pathToFileWithFilename, array|object $parametersAsObject = []): array {
        return self::runScript(
            $pathToFileWithFilename,
            is_object($parametersAsObject) ? get_object_vars($parametersAsObject) : $parametersAsObject
        );
    }

    private static function isAssocArrayV103(array $array): bool {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }
}
