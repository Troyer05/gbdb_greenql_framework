<?php

declare(strict_types=1);

/** Optional native SQL helper. Transparent GBDB fallback uses its own connection. */
class SQL {
    public static ?PDO $pdo = null;

    public static function connect() :bool {
        if (self::$pdo instanceof PDO) {
            return true;
        }

        $environment = Vars::__DEV__() ? 'dev' : 'prod';
        $server = Vars::__DEV__() ? Vars::sql_dev_server() : Vars::sql_server();
        $database = Vars::__DEV__() ? Vars::sql_dev_database() : Vars::sql_database();
        $dsn = (string) GBDBConfig::get('sql.' . $environment, 'dsn', '');

        if ($dsn === '') {
            if ($server === '' || $database === '') {
                return false;
            }

            $dsn = 'mysql:host=' . $server . ';dbname=' . $database . ';charset=utf8mb4';
        }

        $user = (string) GBDBConfig::get('sql.' . $environment, 'user', Vars::__DEV__() ? Vars::sql_dev_user() : Vars::sql_user());
        $password = (string) GBDBConfig::get('sql.' . $environment, 'password', Vars::__DEV__() ? Vars::sql_dev_password() : Vars::sql_password());

        try {
            self::$pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            return true;
        } catch (PDOException $exception) {
            self::$pdo = null;
            error_log('[SQL] Verbindung fehlgeschlagen.');

            return false;
        }
    }

    private static function identifier(string $identifier) :string {
        $parts = explode('.', $identifier);

        foreach ($parts as $part) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $part)) {
                throw new InvalidArgumentException('Ungültiger SQL-Bezeichner.');
            }
        }

        return '`' . implode('`.`', $parts) . '`';
    }

    private static function columns(string $columns) :string {
        if (trim($columns) === '*') {
            return '*';
        }

        $result = [];

        foreach (explode(',', $columns) as $column) {
            $column = trim($column);

            if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\((\*|[a-zA-Z_][a-zA-Z0-9_.]*)\)(?:\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*))?$/iD', $column, $matches)) {
                $expression = strtoupper($matches[1]) . '(' . ($matches[2] === '*' ? '*' : self::identifier($matches[2])) . ')';
                $result[] = $expression . (isset($matches[3]) ? ' AS ' . self::identifier($matches[3]) : '');
            } else {
                $result[] = self::identifier($column);
            }
        }

        return implode(', ', $result);
    }

    /** Trusted application SQL, with bound values. Parameters are never logged. */
    public static function query(string $query, array $parameters = []) :array|bool {
        if (!self::connect()) {
            return false;
        }

        try {
            $statement = self::$pdo->prepare($query);
            $statement->execute($parameters);

            return $statement->columnCount() > 0 ? $statement->fetchAll() : true;
        } catch (PDOException $exception) {
            error_log('[SQL] Abfrage fehlgeschlagen; SQLSTATE ' . $exception->getCode());

            return false;
        }
    }

    public static function select(string $table, string $select = '*', string $where = '', mixed $is = '') :array|bool {
        $query = 'SELECT ' . self::columns($select) . ' FROM ' . self::identifier($table);

        if ($where !== '') {
            return self::query($query . ' WHERE ' . self::identifier($where) . ' = ?', [$is]);
        }

        return self::query($query);
    }

    public static function insert(string $table, array $data) :bool {
        if ($data === []) {
            return false;
        }

        $columns = array_map(self::identifier(...), array_keys($data));
        $sql = 'INSERT INTO ' . self::identifier($table) . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')';

        return self::query($sql, array_values($data)) === true;
    }

    public static function update(string $table, array $data, string $where, mixed $is) :bool {
        if ($data === [] || $where === '') {
            return false;
        }

        $set = array_map(static fn(string $column) :string => self::identifier($column) . ' = ?', array_keys($data));
        $sql = 'UPDATE ' . self::identifier($table) . ' SET ' . implode(', ', $set) . ' WHERE ' . self::identifier($where) . ' = ?';

        return self::query($sql, [...array_values($data), $is]) === true;
    }

    public static function delete(string $table, string $where, mixed $is) :bool {
        return self::query('DELETE FROM ' . self::identifier($table) . ' WHERE ' . self::identifier($where) . ' = ?', [$is]) === true;
    }
}
