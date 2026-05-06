<?php

trait GBDB_QueryTrait {
    /**
     * Setzt Query-Grenzen für komplexe Abfragen.
     * @param array $options Optionen: timeout, memory, max_join_size, block_full_scan.
     * @return array Aktive Optionen.
     */
    public static function queryOptions(array $options = []): array {
        if (isset($options['timeout'])) {
            self::$queryTimeout = max(1, (int)$options['timeout']);
        }

        if (isset($options['memory'])) {
            self::$queryMemoryLimit = max(1024 * 1024, (int)$options['memory']);
        }

        if (isset($options['max_join_size'])) {
            self::$queryMaxJoinSize = max(1, (int)$options['max_join_size']);
        }

        if (isset($options['block_full_scan'])) {
            self::$queryBlockFullScan = (bool)$options['block_full_scan'];
        }

        return [
            'timeout' => self::$queryTimeout,
            'memory' => self::$queryMemoryLimit,
            'max_join_size' => self::$queryMaxJoinSize,
            'block_full_scan' => self::$queryBlockFullScan
        ];
    }

    /**
     * Speichert eine parametrisierte Query intern ab.
     * Platzhalter werden als :name im Script angegeben.
     * @param string $name Name.
     * @param string $script GreenQL-/Query-Script.
     * @return bool true bei Erfolg.
     */
    public static function prepareQuery(string $name, string $script): bool {
        $name = Format::cleanString($name);

        if ($name === '' || trim($script) === '') {
            return false;
        }

        self::$preparedQueries[$name] = $script;

        return true;
    }

    /**
     * Führt eine vorbereitete Query aus.
     * @param string $name Name.
     * @param array $params Parameter.
     * @param array $ctx Kontext.
     * @return array Ergebnis.
     */
    public static function executePreparedQuery(string $name, array $params = [], array $ctx = []): array {
        $name = Format::cleanString($name);

        if (!isset(self::$preparedQueries[$name])) {
            return [
                'ok' => false,
                'message' => 'Prepared Query nicht gefunden: ' . $name,
                'rows' => [],
                'keys' => []
            ];
        }

        $script = self::bindQueryParams(self::$preparedQueries[$name], $params);

        return self::query($script, $ctx, $params);
    }

    /**
     * Bindet Parameter sicher in ein Query-Script ein.
     * @param string $script Script.
     * @param array $params Parameter.
     * @return string Script mit JSON-kodierten Werten.
     */
    public static function bindQueryParams(string $script, array $params): string {
        return preg_replace_callback('/:([a-zA-Z_][a-zA-Z0-9_]*)/', function ($m) use ($params) {
            $key = (string)$m[1];

            if (!array_key_exists($key, $params)) {
                return $m[0];
            }

            $json = json_encode($params[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $json === false ? 'null' : $json;
        }, $script) ?? $script;
    }

    /**
     * Liefert einen stabilen Query-Cache-Key.
     * @param string $type Typ.
     * @param array $payload Payload.
     * @return string Key.
     */
    private static function queryCacheKey2(string $type, array $payload): string {
        return $type . ':' . hash(
            'sha256',
            self::getInstance() . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Leert den Query-Cache.
     * @return void
     */
    public static function clearQueryCache(): void {
        self::$queryCache = [];
    }

    /**
     * Führt einen Inner Join aus.
     * @param string $database Base.
     * @param string $leftTable Linke Tabelle.
     * @param string $rightTable Rechte Tabelle.
     * @param string $leftKey Linker Join-Key.
     * @param string $rightKey Rechter Join-Key.
     * @param array $options Optionen.
     * @return array Join-Ergebnis.
     */
    public static function innerJoin(
        string $database,
        string $leftTable,
        string $rightTable,
        string $leftKey,
        string $rightKey,
        array $options = []
    ): array {
        return self::join($database, $leftTable, $rightTable, $leftKey, $rightKey, 'inner', $options);
    }

    /**
     * Führt einen Left Join aus.
     * @param string $database Base.
     * @param string $leftTable Linke Tabelle.
     * @param string $rightTable Rechte Tabelle.
     * @param string $leftKey Linker Join-Key.
     * @param string $rightKey Rechter Join-Key.
     * @param array $options Optionen.
     * @return array Join-Ergebnis.
     */
    public static function leftJoin(
        string $database,
        string $leftTable,
        string $rightTable,
        string $leftKey,
        string $rightKey,
        array $options = []
    ): array {
        return self::join($database, $leftTable, $rightTable, $leftKey, $rightKey, 'left', $options);
    }

    /**
     * Führt einen tabellenintern geplanten Join aus.
     * @param string $database Base.
     * @param string $leftTable Linke Tabelle.
     * @param string $rightTable Rechte Tabelle.
     * @param string $leftKey Linker Key.
     * @param string $rightKey Rechter Key.
     * @param string $type inner|left.
     * @param array $options Optionen: select, prefix_left, prefix_right, limit, cache_ttl.
     * @return array Ergebnis mit keys, rows und plan.
     */
    public static function join(
        string $database,
        string $leftTable,
        string $rightTable,
        string $leftKey,
        string $rightKey,
        string $type = 'inner',
        array $options = []
    ): array {
        $started = microtime(true);
        $memoryStart = memory_get_usage(true);
        $type = strtolower($type) === 'left' ? 'left' : 'inner';
        $limit = max(1, (int)($options['limit'] ?? 1000));
        $cacheTtl = max(0, (int)($options['cache_ttl'] ?? 0));
        $cacheKey = self::queryCacheKey2(
            'join',
            compact('database', 'leftTable', 'rightTable', 'leftKey', 'rightKey', 'type', 'options')
        );

        if ($cacheTtl > 0 && isset(self::$queryCache[$cacheKey]) && (time() - (int)self::$queryCache[$cacheKey]['ts']) <= $cacheTtl) {
            return self::$queryCache[$cacheKey]['data'];
        }

        $left = self::getData($database, $leftTable);
        $right = self::getData($database, $rightTable);

        if (!is_array($left)) {
            $left = [];
        }

        if (!is_array($right)) {
            $right = [];
        }

        $plan = self::joinPlan($leftTable, $rightTable, count($left), count($right), $leftKey, $rightKey, $type);

        if ((int)$plan['estimated_pairs'] > self::$queryMaxJoinSize) {
            return [
                'ok' => false,
                'message' => 'Join Size Limit überschritten.',
                'keys' => [],
                'rows' => [],
                'plan' => $plan
            ];
        }

        $index = [];

        foreach ($right as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = GBDBStorage::indexKey($row[$rightKey] ?? null);
            $index[$key][] = $row;
        }

        $rows = [];
        $prefixLeft = (string)($options['prefix_left'] ?? $leftTable . '.');
        $prefixRight = (string)($options['prefix_right'] ?? $rightTable . '.');
        $select = is_array($options['select'] ?? null) ? $options['select'] : ['*'];

        foreach ($left as $lrow) {
            if (!is_array($lrow)) {
                continue;
            }

            if ((microtime(true) - $started) > self::$queryTimeout) {
                return [
                    'ok' => false,
                    'message' => 'Query Timeout erreicht.',
                    'keys' => [],
                    'rows' => $rows,
                    'plan' => $plan
                ];
            }

            if ((memory_get_usage(true) - $memoryStart) > self::$queryMemoryLimit) {
                return [
                    'ok' => false,
                    'message' => 'Query Memory Limit erreicht.',
                    'keys' => [],
                    'rows' => $rows,
                    'plan' => $plan
                ];
            }

            $key = GBDBStorage::indexKey($lrow[$leftKey] ?? null);
            $matches = $index[$key] ?? [];

            if (empty($matches) && $type === 'left') {
                $rows[] = self::projectJoinRow($lrow, [], $prefixLeft, $prefixRight, $select);
            } else {
                foreach ($matches as $rrow) {
                    $rows[] = self::projectJoinRow($lrow, $rrow, $prefixLeft, $prefixRight, $select);
                }
            }

            if (count($rows) >= $limit) {
                break;
            }
        }

        $keys = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $keys[$key] = true;
            }
        }

        $result = [
            'ok' => true,
            'message' => count($rows) . ' Join-Zeilen.',
            'keys' => array_keys($keys),
            'rows' => $rows,
            'plan' => $plan
        ];

        if ($cacheTtl > 0) {
            self::$queryCache[$cacheKey] = [
                'ts' => time(),
                'data' => $result
            ];
        }

        return $result;
    }

    /**
     * Erstellt einen einfachen Join-Query-Plan.
     */
    public static function joinPlan(
        string $leftTable,
        string $rightTable,
        int $leftRows,
        int $rightRows,
        string $leftKey,
        string $rightKey,
        string $type = 'inner'
    ): array {
        return [
            'type' => strtolower($type) === 'left' ? 'left' : 'inner',
            'left_table' => $leftTable,
            'right_table' => $rightTable,
            'left_rows' => $leftRows,
            'right_rows' => $rightRows,
            'left_key' => $leftKey,
            'right_key' => $rightKey,
            'strategy' => 'hash_join_right_index',
            'estimated_pairs' => $leftRows * max(1, $rightRows),
            'max_join_size' => self::$queryMaxJoinSize
        ];
    }

    /**
     * Projiziert eine Join-Zeile.
     */
    private static function projectJoinRow(array $left, array $right, string $prefixLeft, string $prefixRight, array $select): array {
        $all = [];

        foreach ($left as $key => $value) {
            $all[$prefixLeft . $key] = $value;
        }

        foreach ($right as $key => $value) {
            $all[$prefixRight . $key] = $value;
        }

        if (in_array('*', $select, true)) {
            return $all;
        }

        $out = [];

        foreach ($select as $key) {
            if (array_key_exists((string)$key, $all)) {
                $out[(string)$key] = $all[(string)$key];
            }
        }

        return $out;
    }

    /**
     * Führt eine Subquery als Callable auf Query-Rows aus.
     * @param callable $query Callback.
     * @return array Ergebnis.
     */
    public static function subquery(callable $query): array {
        $started = microtime(true);
        $result = $query();

        if ((microtime(true) - $started) > self::$queryTimeout) {
            return [
                'ok' => false,
                'message' => 'Subquery Timeout erreicht.',
                'rows' => []
            ];
        }

        return is_array($result)
            ? $result
            : [
                'ok' => false,
                'message' => 'Subquery lieferte kein Array.',
                'rows' => []
            ];
    }

    /**
     * Streamt Query-Ergebnisse callbackbasiert in Chunks.
     * @param string $database Base.
     * @param string $table Tabelle.
     * @param callable $callback Callback.
     * @param int $chunkSize Chunk-Größe.
     * @return array Bericht.
     */
    public static function streamQuery(string $database, string $table, callable $callback, int $chunkSize = 500): array {
        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            $rows = [];
        }

        $chunkSize = max(1, $chunkSize);
        $sent = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $callback($chunk);
            $sent += count($chunk);
        }

        return [
            'ok' => true,
            'chunks' => (int)ceil(max(0, count($rows)) / $chunkSize),
            'rows' => $sent
        ];
    }

    /**
     * Prüft, ob ein Full Table Scan erlaubt ist.
     * @param string $database Base.
     * @param string $table Tabelle.
     * @param string $where Spalte.
     * @return bool true wenn erlaubt.
     */
    public static function allowFullScan(string $database, string $table, string $where = ''): bool {
        if (!self::$queryBlockFullScan) {
            return true;
        }

        if ($where === '') {
            return false;
        }

        $meta = self::meta($database, $table);
        $indexes = (array)($meta['indexes'] ?? []);

        return in_array($where, $indexes, true);
    }
}
