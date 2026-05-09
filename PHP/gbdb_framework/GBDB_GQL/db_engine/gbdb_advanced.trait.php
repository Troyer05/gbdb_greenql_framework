<?php

trait GBDB_AdvancedTrait {
    private static array $runtimeCacheV2 = [];

    private static function cacheKey(string $database, string $table, array $extra = []): string {
        return hash('sha256', json_encode([$database, $table, $extra], JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * handles clear runtime cache.
     *
     * @param null|string $database value.
     * @param null|string $table value.
     *
     * @return void result.
     */
    public static function clearRuntimeCache(?string $database = null, ?string $table = null): void {
        if ($database === null || $table === null) {
            self::$runtimeCacheV2 = [];

            return;
        }

        self::$runtimeCacheV2 = [];
    }

    /**
     * handles get cached data.
     *
     * @param string $database value.
     * @param string $table value.
     * @param bool $filter value.
     * @param mixed $where value.
     * @param mixed $is value.
     * @param int $ttl value.
     *
     * @return mixed result.
     */
    public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5): mixed {
        $key = hash('sha256', json_encode([$database, $table, $filter, $where, $is], JSON_UNESCAPED_UNICODE) ?: '');
        $now = time();

        if (isset(self::$runtimeCacheV2[$key]) && ($now - (int)self::$runtimeCacheV2[$key]['ts']) <= max(1, $ttl)) {
            return self::$runtimeCacheV2[$key]['data'];
        }

        $data = self::getData($database, $table, $filter, $where, $is);

        self::$runtimeCacheV2[$key] = ['ts' => $now, 'data' => $data];

        return $data;
    }

    /**
     * handles bulk insert.
     *
     * @param string $database value.
     * @param string $table value.
     * @param array $rows value.
     * @param bool $transactional value.
     *
     * @return array result.
     */
    public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true): array {
        $ids = [];
        $errors = [];
        $startedTx = false;

        if ($transactional && !self::inTransaction()) {
            $startedTx = self::begin();
        }

        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                $errors[] = ['index' => $i, 'error' => 'row_not_array'];
                continue;
            }

            $id = self::insertData($database, $table, $row);

            if ($id <= 0) {
                $errors[] = ['index' => $i, 'error' => 'insert_failed'];
                continue;
            }

            $ids[] = $id;
        }

        if ($startedTx) {
            if (!empty($errors)) {
                self::rollback();

                return ['ok' => false, 'ids' => [], 'errors' => $errors];
            }

            $ok = self::commit();

            return ['ok' => $ok, 'ids' => $ok ? $ids : [], 'errors' => $ok ? [] : [['error' => 'commit_failed']]];
        }

        return ['ok' => empty($errors), 'ids' => $ids, 'errors' => $errors];
    }

    /**
     * handles stream rows.
     *
     * @param string $database value.
     * @param string $table value.
     * @param callable $callback value.
     * @param int $chunkSize value.
     *
     * @return array result.
     */
    public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500): array {
        $rows = self::getData($database, $table);
        $count = 0;
        $chunkSize = max(1, $chunkSize);

        foreach (array_chunk(is_array($rows) ? $rows : [], $chunkSize) as $chunk) {
            foreach ($chunk as $row) {
                if (!is_array($row)) continue;
                $callback($row, $count);
                $count++;
            }

        }

        return ['ok' => true, 'rows' => $count, 'chunk_size' => $chunkSize];
    }

    /**
     * handles page.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $page value.
     * @param int $perPage value.
     *
     * @return array result.
     */
    public static function page(string $database, string $table, int $page = 1, int $perPage = 50): array {
        $page = max(1, $page);
        $perPage = max(1, min(1000, $perPage));
        $rows = self::getData($database, $table);
        $rows = is_array($rows) ? array_values($rows) : [];
        $total = count($rows);
        $offset = ($page - 1) * $perPage;

        return [
            'ok' => true,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => (int)ceil($total / $perPage),
            'rows' => array_slice($rows, $offset, $perPage)
        ];
    }

    /**
     * handles cursor.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $limit value.
     * @param null|string $cursor value.
     *
     * @return array result.
     */
    public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null): array {
        $limit = max(1, min(1000, $limit));
        $offset = 0;

        if ($cursor !== null && $cursor !== '') {
            $decoded = base64_decode($cursor, true);
            $json = $decoded !== false ? json_decode($decoded, true) : null;

            if (is_array($json) && isset($json['offset'])) {
                $offset = max(0, (int)$json['offset']);
            }

        }

        $rows = self::getData($database, $table);
        $rows = is_array($rows) ? array_values($rows) : [];
        $slice = array_slice($rows, $offset, $limit);
        $nextOffset = $offset + count($slice);

        $next = $nextOffset < count($rows)
            ? base64_encode(json_encode(['offset' => $nextOffset], JSON_UNESCAPED_UNICODE) ?: '')
            : null;

        return ['ok' => true, 'rows' => $slice, 'cursor' => $next, 'offset' => $offset, 'limit' => $limit];
    }

    /**
     * handles fulltext search.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $query value.
     * @param array $columns value.
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        $queryTokens = self::tokenizeText($query);

        if (empty($queryTokens)) return [];

        $file = self::makePath($database, $table);
        $meta = self::meta($database, $table);
        $defs = is_array($meta['index_definitions'] ?? null) ? $meta['index_definitions'] : [];
        $candidateIds = [];

        foreach ($defs as $definition) {
            if (($definition['type'] ?? '') !== 'fulltext') continue;

            if (!empty($columns)) {
                $defCols = (array)($definition['columns'] ?? []);

                if (array_values($columns) !== array_values(array_intersect($columns, $defCols))) continue;
            }

            $candidateIds = GBDBStorage::advancedIndexLookup($file, $definition, $query);
            break;
        }

        $rows = self::getData($database, $table);
        $hits = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) continue;

            if (!empty($candidateIds) && !in_array((int)($row['id'] ?? 0), $candidateIds, true)) continue;

            $haystack = '';
            $useColumns = empty($columns) ? array_keys($row) : $columns;

            foreach ($useColumns as $column) {
                if ($column === 'id') continue;

                if (isset($row[$column]) && (is_scalar($row[$column]) || $row[$column] === null)) $haystack .= ' ' . (string)$row[$column];
            }

            $tokens = array_count_values(self::tokenizeText($haystack));
            $score = 0;

            foreach ($queryTokens as $token) $score += (int)($tokens[$token] ?? 0);

            if ($score > 0) $hits[] = ['score' => $score, 'row' => $row];
        }

        usort($hits, fn($a, $b) => ($b['score'] <=> $a['score']));

        return array_slice($hits, 0, max(1, $limit));
    }

    /**
     * handles fulltext search.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $query value.
     * @param array $columns value.
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function fulltextSearch(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        return self::fulltext_search($database, $table, $query, $columns, $limit);
    }

    /**
     * handles fulltext.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $query value.
     * @param array $columns value.
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        return self::fulltext_search($database, $table, $query, $columns, $limit);
    }

    private static function tokenizeText(string $text): array {
        return GBDBStorage::tokenizeFulltext($text);
    }

    /**
     * handles query plan.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $where value.
     * @param mixed $is value.
     * @param null|string $sortField value.
     * @param null|int $limit value.
     * @param int $offset value.
     *
     * @return array result.
     */
    public static function queryPlan(string $database, string $table, string $where = '', mixed $is = '', ?string $sortField = null, ?int $limit = null, int $offset = 0): array {
        $meta = self::meta($database, $table);
        $defs = is_array($meta['index_definitions'] ?? null) ? $meta['index_definitions'] : [];
        $best = '';
        $strategy = 'full_table_scan';
        $cost = max(1, (int)($meta['rows'] ?? 0));

        foreach ($defs as $name => $definition) {
            $cols = (array)($definition['columns'] ?? []);

            if ($where !== '' && count($cols) >= 1 && (string)$cols[0] === (string)$where && ($definition['type'] ?? '') !== 'fulltext') {
                $best = (string)$name;
                $strategy = 'index_lookup';
                $cost = max(1, (int)ceil($cost * 0.05));

                break;
            }

            if ($sortField !== null && in_array((string)$sortField, $cols, true) && !empty($definition['sorted'])) {
                $best = (string)$name;
                $strategy = 'sorted_index_scan';
                $cost = max(1, (int)ceil($cost * 0.35));
            }

        }

        $warning = $strategy === 'full_table_scan' && (int)($meta['rows'] ?? 0) > 1000 ? 'full_table_scan' : '';
        $suggested = ($where !== '' && $best === '') ? [['type' => 'single', 'columns' => [$where], 'reason' => 'where_filter']] : [];

        return [
            'query_id' => self::newQueryId(),
            'engine' => 'GBDB',
            'database' => $database,
            'table' => $table,
            'where' => $where,
            'sort' => $sortField ?? '',
            'limit' => $limit,
            'offset' => $offset,
            'uses_index' => $best !== '',
            'index' => $best,
            'strategy' => $strategy,
            'estimated_rows' => (int)($meta['rows'] ?? 0),
            'estimated_cost' => $cost,
            'append_ops' => (int)($meta['append_ops'] ?? 0),
            'warning' => $warning,
            'suggested_indexes' => $suggested
        ];
    }

    /**
     * handles grant acl.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $role value.
     * @param string $permission value.
     *
     * @return bool result.
     */
    public static function grantAcl(string $database, string $table, string $role, string $permission): bool {
        return self::changeAcl($database, $table, $role, $permission, true);
    }

    /**
     * handles revoke acl.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $role value.
     * @param string $permission value.
     *
     * @return bool result.
     */
    public static function revokeAcl(string $database, string $table, string $role, string $permission): bool {
        return self::changeAcl($database, $table, $role, $permission, false);
    }

    /**
     * handles check acl.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $role value.
     * @param string $permission value.
     *
     * @return bool result.
     */
    public static function checkAcl(string $database, string $table, string $role, string $permission): bool {
        $meta = self::meta($database, $table);
        $acl = $meta['acl'] ?? [];

        if (($acl['*'][$permission] ?? false) === true) return true;

        if (($acl[$role]['*'] ?? false) === true) return true;

        return ($acl[$role][$permission] ?? false) === true;
    }

    private static function changeAcl(string $database, string $table, string $role, string $permission, bool $allow): bool {
        $role = trim($role);
        $permission = trim($permission);

        if ($role === '' || $permission === '') return false;

        $metaFile = self::metaFileForTable($database, $table);
        $lockFile = self::lockFileForTable($database, $table);

        return (bool)self::withTableLock($lockFile, function () use ($metaFile, $role, $permission, $allow) {
            $meta = self::readMeta($metaFile);

            if (!isset($meta['acl']) || !is_array($meta['acl'])) $meta['acl'] = [];

            if (!isset($meta['acl'][$role]) || !is_array($meta['acl'][$role])) $meta['acl'][$role] = [];

            if ($allow) {
                $meta['acl'][$role][$permission] = true;
            } else {
                unset($meta['acl'][$role][$permission]);

                if (empty($meta['acl'][$role])) unset($meta['acl'][$role]);
            }

            return self::writeMeta($metaFile, $meta);
        });
    }

    /**
     * handles audit.
     *
     * @param string $action value.
     * @param array $payload value.
     * @param string $actor value.
     *
     * @return int result.
     */
    public static function audit(string $action, array $payload = [], string $actor = 'system'): int {
        $db = '_gbdb_audit';
        $table = 'audit_log';

        if (!in_array($db, self::listDBs(), true)) {
            self::createDatabase($db);
        }

        if (!in_array($table, self::listTables($db), true)) {
            self::createTable($db, $table, ['ts', 'actor', 'action', 'payload']);
        }

        return self::insertData($db, $table, [
            'ts' => time(),
            'actor' => $actor,
            'action' => $action,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * handles gdpr export.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $column value.
     * @param mixed $value value.
     *
     * @return array result.
     */
    public static function gdprExport(string $database, string $table, string $column, mixed $value): array {
        $rows = self::getData($database, $table);
        $out = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && array_key_exists($column, $row) && $row[$column] == $value) {
                $out[] = $row;
            }

        }

        self::audit('gdpr_export', compact('database', 'table', 'column'));

        return $out;
    }

    /**
     * handles gdpr redact.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $where value.
     * @param mixed $is value.
     * @param array $columns value.
     * @param string $replacement value.
     *
     * @return bool result.
     */
    public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]'): bool {
        $set = [];

        foreach ($columns as $column) {
            if ($column !== '' && $column !== 'id') $set[(string)$column] = $replacement;
        }

        if (empty($set)) return false;

        $ok = self::editData($database, $table, $where, $is, $set);

        self::audit('gdpr_redact', compact('database', 'table', 'where', 'columns'));

        return $ok;
    }

    /**
     * handles migrate.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $migrationId value.
     * @param callable $callback value.
     *
     * @return array result.
     */
    public static function migrate(string $database, string $table, string $migrationId, callable $callback): array {
        $metaFile = self::metaFileForTable($database, $table);
        $lockFile = self::lockFileForTable($database, $table);

        return (array)self::withTableLock($lockFile, function () use ($database, $table, $migrationId, $callback, $metaFile) {
            $meta = self::readMeta($metaFile);
            $done = $meta['migrations'] ?? [];

            if (!is_array($done)) $done = [];

            if (in_array($migrationId, $done, true)) {
                return ['ok' => true, 'already_done' => true, 'migration' => $migrationId];
            }

            $result = $callback($database, $table);

            if ($result === false) {
                return ['ok' => false, 'migration' => $migrationId, 'error' => 'callback_failed'];
            }

            $meta = self::readMeta($metaFile);
            $done = $meta['migrations'] ?? [];

            if (!is_array($done)) $done = [];

            $done[] = $migrationId;
            $meta['migrations'] = array_values(array_unique($done));

            self::writeMeta($metaFile, $meta);

            return ['ok' => true, 'already_done' => false, 'migration' => $migrationId];
        });
    }

    /**
     * handles partition table name.
     *
     * @param string $table value.
     * @param string $partition value.
     *
     * @return string result.
     */
    public static function partitionTableName(string $table, string $partition): string {
        return Format::cleanString($table) . '__p_' . Format::cleanString($partition);
    }

    /**
     * handles insert partitioned.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $partition value.
     * @param array $data value.
     *
     * @return int result.
     */
    public static function insertPartitioned(string $database, string $table, string $partition, array $data): int {
        $pTable = self::partitionTableName($table, $partition);

        if (!in_array($database, self::listDBs(), true)) self::createDatabase($database);

        if (!in_array($pTable, self::listTables($database), true)) self::createTable($database, $pTable, array_keys($data));

        return self::insertData($database, $pTable, $data);
    }

    /**
     * handles get partition.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $partition value.
     *
     * @return array result.
     */
    public static function getPartition(string $database, string $table, string $partition): array {
        $rows = self::getData($database, self::partitionTableName($table, $partition));

        return is_array($rows) ? $rows : [];
    }

    /**
     * handles shard table name.
     *
     * @param string $table value.
     * @param mixed $key value.
     * @param int $shards value.
     *
     * @return string result.
     */
    public static function shardTableName(string $table, mixed $key, int $shards = 16): string {
        $shards = max(1, $shards);
        $slot = hexdec(substr(hash('crc32b', (string)$key), 0, 6)) % $shards;

        return Format::cleanString($table) . '__s_' . $slot;
    }

    /**
     * handles insert sharded.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $key value.
     * @param array $data value.
     * @param int $shards value.
     *
     * @return int result.
     */
    public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16): int {
        $sTable = self::shardTableName($table, $key, $shards);

        if (!in_array($database, self::listDBs(), true)) self::createDatabase($database);

        if (!in_array($sTable, self::listTables($database), true)) self::createTable($database, $sTable, array_keys($data));

        return self::insertData($database, $sTable, $data);
    }

    /**
     * handles get shard.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $key value.
     * @param int $shards value.
     *
     * @return array result.
     */
    public static function getShard(string $database, string $table, mixed $key, int $shards = 16): array {
        $rows = self::getData($database, self::shardTableName($table, $key, $shards));

        return is_array($rows) ? $rows : [];
    }

    /**
     * handles recover wal only.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function recoverWalOnly(string $database, string $table): array {
        return GBDBStorage::recoverWal(self::appendFileForTable($database, $table));
    }

    /**
     * handles append log.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $limit value.
     *
     * @return array result.
     */
    public static function appendLog(string $database, string $table, int $limit = 100): array {
        $ops = self::readAppendOps(self::appendFileForTable($database, $table));

        return array_slice($ops, max(0, count($ops) - max(1, $limit)));
    }

    /**
     * handles monitor.
     *
     * @param string $database value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function monitor(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $append = self::appendFileForTable($database, $table);
        $meta = self::meta($database, $table);
        $health = self::health($database, $table);

        return [
            'ok' => is_file($file),
            'database' => $database,
            'table' => $table,
            'rows' => (int)($meta['rows'] ?? 0),
            'append_ops' => (int)($meta['append_ops'] ?? 0),
            'data_size' => is_file($file) ? (int)filesize($file) : 0,
            'append_size' => is_file($append) ? (int)filesize($append) : 0,
            'indexes' => $meta['indexes'] ?? [],
            'constraints' => $meta['constraints'] ?? [],
            'health' => $health,
            'updated_at' => $meta['updated_at'] ?? 0
        ];
    }

    /**
     * handles get.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     * @param array $options value.
     *
     * @return mixed result.
     */
    public static function get(string $database, string $table, mixed $where = '', mixed $is = '', array $options = []): mixed {
        $filtered = $where !== '' && $where !== null;
        $data = self::getData($database, $table, $filtered, $where, $is);

        if ($filtered || !is_array($data)) {
            return $data;
        }

        if (isset($options['sort']) && is_string($options['sort']) && $options['sort'] !== '') {
            $field = $options['sort'];
            $dir = strtoupper((string)($options['dir'] ?? 'ASC'));

            usort($data, function ($a, $b) use ($field, $dir) {
                $av = is_array($a) ? ($a[$field] ?? null) : null;
                $bv = is_array($b) ? ($b[$field] ?? null) : null;

                if (is_numeric($av) && is_numeric($bv)) {
                    $cmp = ((float)$av <=> (float)$bv);
                } else {
                    $cmp = strnatcasecmp((string)$av, (string)$bv);
                }

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

    /**
     * handles insert.
     *
     * @param string $database value.
     * @param string $table value.
     * @param array $data value.
     *
     * @return int result.
     */
    public static function insert(string $database, string $table, array $data): int {
        if ($database === '' || $table === '' || empty($data)) return -1;

        if (!in_array($database, self::listDBs(), true)) {
            self::createDatabase($database);
        }

        if (!in_array($table, self::listTables($database), true)) {
            self::createTable($database, $table, array_keys($data));
        }

        return self::insertData($database, $table, $data);
    }

    /**
     * handles edit.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     * @param array $data value.
     *
     * @return bool result.
     */
    public static function edit(string $database, string $table, mixed $where, mixed $is, array $data): bool {
        return self::editData($database, $table, $where, $is, $data);
    }

    /**
     * handles delete.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     *
     * @return bool result.
     */
    public static function delete(string $database, string $table, mixed $where, mixed $is): bool {
        return self::deleteData($database, $table, $where, $is);
    }

    /**
     * handles exists.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     *
     * @return bool result.
     */
    public static function exists(string $database, string $table, mixed $where, mixed $is): bool {
        return self::elementExists($database, $table, $where, $is);
    }

    /**
     * handles upsert.
     *
     * @param string $database value.
     * @param string $table value.
     * @param mixed $where value.
     * @param mixed $is value.
     * @param array $data value.
     *
     * @return array result.
     */
    public static function upsert(string $database, string $table, mixed $where, mixed $is, array $data): array {
        $row = self::get($database, $table, $where, $is);

        if (is_array($row) && !empty($row)) {
            $ok = self::edit($database, $table, $where, $is, $data);

            return ['ok' => $ok, 'action' => 'update', 'id' => (int)($row['id'] ?? 0)];
        }

        $data[(string)$where] = $is;
        $id = self::insert($database, $table, $data);

        return ['ok' => $id > 0, 'action' => 'insert', 'id' => $id];
    }

    /**
     * handles stats.
     *
     * @param string $database value.
     * @param null|string $table value.
     *
     * @return array result.
     */
    public static function stats(string $database, ?string $table = null): array {
        if ($table !== null && $table !== '') {
            $monitor = method_exists(static::class, 'monitor') ? self::monitor($database, $table) : [];
            $meta = self::meta($database, $table);

            return [
                'ok' => (bool)($monitor['ok'] ?? is_file(self::makePath($database, $table))),
                'database' => $database,
                'table' => $table,
                'rows' => (int)($monitor['rows'] ?? ($meta['rows'] ?? 0)),
                'append_ops' => (int)($monitor['append_ops'] ?? ($meta['append_ops'] ?? 0)),
                'data_size' => (int)($monitor['data_size'] ?? 0),
                'append_size' => (int)($monitor['append_size'] ?? 0),
                'indexes' => $meta['indexes'] ?? [],
                'constraints' => $meta['constraints'] ?? [],
                'updated_at' => (int)($meta['updated_at'] ?? 0),
                'health' => $monitor['health'] ?? self::health($database, $table)
            ];
        }

        $rows = [];

        foreach (self::listTables($database) as $tableName) {
            $rows[] = self::stats($database, $tableName);
        }

        return ['ok' => true, 'database' => $database, 'tables' => $rows, 'count' => count($rows)];
    }

    /**
     * handles analyze.
     *
     * @param string $database value.
     * @param string $table value.
     * @param int $sampleSize value.
     *
     * @return array result.
     */
    public static function analyze(string $database, string $table, int $sampleSize = 1000): array {
        $rows = self::get($database, $table, '', '', ['limit' => max(1, $sampleSize)]);
        $keys = self::getKeys($database, $table);
        $meta = self::meta($database, $table);
        $existing = $meta['indexes'] ?? [];
        $columns = [];
        $suggestions = [];

        foreach ($keys as $key) {
            if ($key === 'id') continue;

            $values = [];
            $filled = 0;
            $numeric = 0;

            foreach (is_array($rows) ? $rows : [] as $row) {
                if (!is_array($row) || !array_key_exists($key, $row)) continue;
                $value = $row[$key];

                if ($value !== '' && $value !== null) $filled++;

                if (is_numeric($value)) $numeric++;

                $values[(string)$value] = true;
            }

            $distinct = count($values);
            $total = max(1, count(is_array($rows) ? $rows : []));
            $ratio = $distinct / $total;

            $columns[$key] = [
                'filled' => $filled,
                'distinct' => $distinct,
                'distinct_ratio' => round($ratio, 4),
                'numeric_ratio' => round($numeric / $total, 4),
                'indexed' => in_array($key, $existing, true)
            ];

            if (!in_array($key, $existing, true) && $distinct > 1 && $ratio >= 0.2) {
                $suggestions[] = [
                    'column' => $key,
                    'reason' => $ratio >= 0.75 ? 'high_cardinality' : 'medium_cardinality',
                    'distinct_ratio' => round($ratio, 4)
                ];
            }

        }

        return [
            'ok' => true,
            'database' => $database,
            'table' => $table,
            'sample_rows' => count(is_array($rows) ? $rows : []),
            'stats' => self::stats($database, $table),
            'columns' => $columns,
            'suggested_indexes' => $suggestions
        ];
    }

    /**
     * handles auto index.
     *
     * @param string $database value.
     * @param string $table value.
     * @param array $columns value.
     *
     * @return array result.
     */
    public static function autoIndex(string $database, string $table, array $columns = []): array {
        if (empty($columns)) {
            $analysis = self::analyze($database, $table);

            foreach ($analysis['suggested_indexes'] ?? [] as $suggestion) {
                if (isset($suggestion['column'])) $columns[] = (string)$suggestion['column'];
            }

        }

        $created = [];
        $failed = [];

        foreach (array_values(array_unique($columns)) as $column) {
            $column = Format::cleanString((string)$column);

            if ($column === '' || $column === 'id') continue;

            if (self::createIndex($database, $table, $column)) {
                $created[] = $column;
            } else {
                $failed[] = $column;
            }

        }

        return ['ok' => empty($failed), 'database' => $database, 'table' => $table, 'created' => $created, 'failed' => $failed];
    }

    /**
     * handles explain.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $where value.
     * @param mixed $is value.
     *
     * @return array result.
     */
    public static function explain(string $database, string $table, string $where = '', mixed $is = ''): array {
        return self::queryPlan($database, $table, $where, $is);
    }

    /**
     * handles new query id.
     *
     * @return string result.
     */
    public static function newQueryId(): string {
        return 'q_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    /**
     * handles active queries.
     *
     * @return array result.
     */
    public static function activeQueries(): array {
        return [];
    }

    /**
     * handles kill query.
     *
     * @param string $queryId value.
     *
     * @return array result.
     */
    public static function killQuery(string $queryId): array {
        return ['ok' => false, 'query_id' => $queryId, 'message' => 'Kill Query ist vorbereitet; echte laufende Worker folgen in spaeteren Transaction/Lock-Wochen.'];
    }

    /**
     * handles slow query log.
     *
     * @param array $plan value.
     * @param float $seconds value.
     *
     * @return void result.
     */
    public static function slowQueryLog(array $plan, float $seconds): void {
        if ($seconds < 0.25 && ($plan['warning'] ?? '') === '') return;

        $dir = dirname(__DIR__, 2) . '/.logs/query';

        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $entry = ['ts' => time(), 'seconds' => $seconds, 'plan' => $plan];

        @file_put_contents($dir . '/slow_queries.log', json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    }

    /**
     * handles select.
     *
     * @param string $database value.
     * @param string $table value.
     * @param array $options value.
     *
     * @return array result.
     */
    public static function select(string $database, string $table, array $options = []): array {
        $start = microtime(true);
        $where = (string)($options['where'] ?? '');
        $is = $options['is'] ?? '';
        $sort = isset($options['sort']) ? (string)$options['sort'] : null;
        $dir = strtoupper((string)($options['dir'] ?? 'ASC'));
        $limit = isset($options['limit']) ? max(0, (int)$options['limit']) : 0;
        $offset = max(0, (int)($options['offset'] ?? 0));
        $plan = self::queryPlan($database, $table, $where, $is, $sort, $limit > 0 ? $limit : null, $offset);
        $rows = $where !== '' ? [self::getData($database, $table, true, $where, $is)] : self::getData($database, $table);
        $rows = array_values(array_filter(is_array($rows) ? $rows : [], fn($row) => is_array($row) && !empty($row)));

        if ($sort !== null && $sort !== '') {
            usort($rows, function ($a, $b) use ($sort, $dir) {
                $av = $a[$sort] ?? null; $bv = $b[$sort] ?? null;
                $cmp = is_numeric($av) && is_numeric($bv) ? ((float)$av <=> (float)$bv) : strnatcasecmp((string)$av, (string)$bv);

                return $dir === 'DESC' ? -$cmp : $cmp;
            });
        }

        if ($offset > 0 || $limit > 0) $rows = array_slice($rows, $offset, $limit > 0 ? $limit : null);

        self::slowQueryLog($plan, microtime(true) - $start);

        return ['ok' => true, 'query_id' => $plan['query_id'], 'plan' => $plan, 'rows' => $rows, 'keys' => isset($rows[0]) ? array_keys($rows[0]) : self::getKeys($database, $table)];
    }

    /**
     * handles distinct.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $column value.
     *
     * @return array result.
     */
    public static function distinct(string $database, string $table, string $column): array {
        $seen = [];

        foreach (self::getData($database, $table) as $row) if (is_array($row) && array_key_exists($column, $row)) $seen[(string)$row[$column]] = $row[$column];

        return array_values($seen);
    }

    /**
     * handles aggregate.
     *
     * @param string $database value.
     * @param string $table value.
     * @param string $fn value.
     * @param string $column value.
     * @param null|string $groupBy value.
     * @param null|array $having value.
     *
     * @return array result.
     */
    public static function aggregate(string $database, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null): array {
        $fn = strtoupper($fn);
        $rows = self::getData($database, $table);
        $groups = ['__all__' => []];

        if ($groupBy !== null && $groupBy !== '') {
            $groups = [];

            foreach ($rows as $row) if (is_array($row)) $groups[(string)($row[$groupBy] ?? '')][] = $row;
        } else {
            $groups['__all__'] = is_array($rows) ? $rows : [];
        }

        $out = [];

        foreach ($groups as $key => $items) {
            $values = [];

            foreach ($items as $row) if (is_array($row) && ($column === '*' || array_key_exists($column, $row))) $values[] = $column === '*' ? 1 : $row[$column];

            $numeric = array_values(array_filter($values, 'is_numeric'));
            $value = match ($fn) {
                'COUNT' => count($items),
                'SUM' => array_sum(array_map('floatval', $numeric)),
                'AVG' => count($numeric) ? array_sum(array_map('floatval', $numeric)) / count($numeric) : 0,
                'MIN' => empty($values) ? null : min($values),
                'MAX' => empty($values) ? null : max($values),
                default => null
            };

            $row = ['group' => $groupBy === null ? '' : $key, strtolower($fn) => $value];

            if ($having !== null) {
                $left = $value; $op = (string)($having['op'] ?? '='); $right = $having['value'] ?? null;
                $pass = match ($op) { '>' => $left > $right, '<' => $left < $right, '>=' => $left >= $right, '<=' => $left <= $right, '!=', '<>' => $left != $right, default => $left == $right };

                if (!$pass) continue;
            }

            $out[] = $row;
        }

        return $out;
    }

}
