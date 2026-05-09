<?php

trait GreenQL_RowsTrait {

    /**
     * handles row match.
     *
     * @param array $row value.
     * @param null|array $where value.
     * @param null|callable $comparator value.
     *
     * @return bool result.
     */
    public static function rowMatch(array $row, ?array $where, ?callable $comparator = null): bool {
        if ($where === null) return true;

        $type = (string)($where['type'] ?? 'condition');

        if ($type === 'invalid') return false;

        if ($type === 'and') return self::rowMatch($row, $where['left'] ?? null, $comparator) && self::rowMatch($row, $where['right'] ?? null, $comparator);

        if ($type === 'or') return self::rowMatch($row, $where['left'] ?? null, $comparator) || self::rowMatch($row, $where['right'] ?? null, $comparator);

        if ($type === 'not') return !self::rowMatch($row, $where['expr'] ?? null, $comparator);

        $field = (string)($where['field'] ?? '');
        $op = strtoupper((string)($where['op'] ?? '='));
        $value = $where['value'] ?? null;
        $left = $field !== '' && array_key_exists($field, $row) ? $row[$field] : null;

        if ($op === 'IN') return is_array($value) && in_array($left, $value, false);

        if ($op === 'BETWEEN') return is_array($value) && count($value) >= 2 && $left >= $value[0] && $left <= $value[1];

        if ($op === 'IS NULL') return $left === null || $left === '';

        if ($op === 'IS NOT NULL') return !($left === null || $left === '');

        if ($op === 'LIKE') {
            $pattern = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote((string)$value, '/')) . '$/iu';

            return preg_match($pattern, (string)$left) === 1;
        }

        if ($comparator !== null) {
            return (bool)$comparator($field, $left, $op, $value);
        }

        switch ($op) {
            case "=":
            case "==":

                return $left == $value;

            case "!=":

                return $left != $value;

            case ">":

                return $left > $value;

            case "<":

                return $left < $value;

            case ">=":

                return $left >= $value;

            case "<=":

                return $left <= $value;

            case "~=":

                return mb_stripos((string)$left, (string)$value) !== false;
        }

        return false;
    }

    /**
     * handles sort rows.
     *
     * @param array $rows value.
     * @param null|string $field value.
     * @param string $dir value.
     * @param null|callable $sorter value.
     *
     * @return void result.
     */
    public static function sortRows(array &$rows, ?string $field, string $dir = "ASC", ?callable $sorter = null): void {
        if ($field === null || $field === "") {
            return;
        }

        if ($sorter !== null) {
            $sorter($rows, $field, $dir);

            return;
        }

        usort($rows, function ($a, $b) use ($field, $dir) {
            $av = $a[$field] ?? "";
            $bv = $b[$field] ?? "";

            if (is_numeric($av) && is_numeric($bv)) {
                $cmp = $av <=> $bv;
            } else {
                $cmp = strnatcasecmp((string)$av, (string)$bv);
            }

            return strtoupper($dir) === "DESC" ? -$cmp : $cmp;
        });
    }

    /**
     * handles get rows.
     *
     * @param string $db value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function getRows(string $db, string $table): array {
        $driver = self::db();
        $rows = $driver::getData($db, $table);

        return is_array($rows) ? $rows : [];
    }

    /**
     * handles get table keys.
     *
     * @param string $db value.
     * @param string $table value.
     *
     * @return array result.
     */
    public static function getTableKeys(string $db, string $table): array {
        $driver = self::db();
        $keys = $driver::getKeys($db, $table);

        if (!empty($keys)) {
            return $keys;
        }

        $rows = self::getRows($db, $table);

        if (!empty($rows) && is_array($rows[0])) {
            return array_keys($rows[0]);
        }

        return [];
    }

    /**
     * handles select rows.
     *
     * @param string $db value.
     * @param string $table value.
     * @param array $columns value.
     * @param null|array $where value.
     * @param null|string $sortField value.
     * @param string $sortDir value.
     * @param null|int $limit value.
     * @param int $offset value.
     *
     * @return array result.
     */
    public static function selectRows(
        string $db,
        string $table,
        array $columns = ["*"],
        ?array $where = null,
        ?string $sortField = null,
        string $sortDir = "ASC",
        ?int $limit = null,
        int $offset = 0
    ): array {
        $driver = self::db();
        $started = microtime(true);
        $plan = method_exists($driver, 'queryPlan')
            ? $driver::queryPlan($db, $table, (string)($where['field'] ?? ''), $where['value'] ?? '', $sortField, $limit, $offset)
            : ['query_id' => 'q_' . bin2hex(random_bytes(4)), 'strategy' => 'scan'];

        $rows = self::getRows($db, $table);

        $comparator = method_exists($driver, 'typedCompare') ? function ($field, $left, $op, $right) use ($driver, $db, $table) {
            return $driver::typedCompare($db, $table, (string)$field, $left, (string)$op, $right);
        } : null;

        $rows = array_values(array_filter($rows, function ($row) use ($where, $comparator) {
            return is_array($row) && self::rowMatch($row, $where, $comparator);
        }));

        $sorter = method_exists($driver, 'typedSortRows') ? function (&$rows, $field, $dir) use ($driver, $db, $table) {
            $driver::typedSortRows($db, $table, $rows, (string)$field, (string)$dir);
        } : null;

        self::sortRows($rows, $sortField, $sortDir, $sorter);

        $offset = max(0, $offset);

        if ($offset > 0 || ($limit !== null && $limit >= 0)) {
            $rows = array_slice($rows, $offset, $limit !== null && $limit >= 0 ? $limit : null);
        }

        $keys = self::getTableKeys($db, $table);

        if ($columns !== ["*"]) {
            $rows = array_map(function ($row) use ($columns) {
                $tmp = [];

                foreach ($columns as $col) $tmp[$col] = $row[$col] ?? "";

                return $tmp;
            }, $rows);
            $keys = $columns;
        }

        if (method_exists($driver, 'slowQueryLog')) $driver::slowQueryLog($plan, microtime(true) - $started);

        return [
            "keys" => $keys,
            "rows" => $rows,
            "plan" => $plan,
            "query_id" => (string)($plan['query_id'] ?? '')
        ];
    }

    /**
     * handles aggregate rows.
     *
     * @param string $db value.
     * @param string $table value.
     * @param string $fn value.
     * @param string $column value.
     * @param null|string $groupBy value.
     * @param null|array $having value.
     *
     * @return array result.
     */
    public static function aggregateRows(string $db, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null): array {
        $driver = self::db();

        if (method_exists($driver, 'aggregate')) {
            $rows = $driver::aggregate($db, $table, $fn, $column, $groupBy, $having);
            $keys = isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : ['group', strtolower($fn)];

            return ['keys' => $keys, 'rows' => $rows];
        }

        return ['keys' => [], 'rows' => []];
    }

    /**
     * handles distinct rows.
     *
     * @param string $db value.
     * @param string $table value.
     * @param string $column value.
     *
     * @return array result.
     */
    public static function distinctRows(string $db, string $table, string $column): array {
        $driver = self::db();
        $values = method_exists($driver, 'distinct') ? $driver::distinct($db, $table, $column) : [];
        $rows = array_map(fn($value) => [$column => $value], $values);

        return ['keys' => [$column], 'rows' => $rows];
    }

    /**
     * handles stats.
     *
     * @param string $db value.
     *
     * @return array result.
     */
    public static function stats(string $db): array {
        $driver = self::db();
        $tables = $driver::listTables($db);
        $rows = 0;

        foreach ($tables as $table) {
            $data = $driver::getData($db, $table);

            if (is_array($data)) {
                $rows += count($data);
            }

        }

        return [
            "tables" => count($tables),
            "rows" => $rows
        ];
    }

}
