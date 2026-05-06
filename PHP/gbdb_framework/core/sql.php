<?php

class SQL {

    /** @var PDO|null */
    public static ?PDO $pdo = null;

    /**
     * Stellt die Verbindung her (sicher, UTF-8, Fehlermodus)
     */
    public static function connect(): bool {
        if (self::$pdo instanceof PDO) {
            return true;
        }

        // DEV oder PROD DB auswählen
        if (Vars::__DEV__()) {
            $dsn = "mysql:host=" . Vars::sql_dev_server() . ";dbname=" . Vars::sql_dev_database() . ";charset=utf8mb4";
            $user = Vars::sql_dev_user();
            $pass = Vars::sql_dev_password();
        } else {
            $dsn = "mysql:host=" . Vars::sql_server() . ";dbname=" . Vars::sql_database() . ";charset=utf8mb4";
            $user = Vars::sql_user();
            $pass = Vars::sql_password();
        }

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true
            ]);

            return true;

        } catch (PDOException $e) {
            error_log("[SQL::connect] ERROR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Interne: prepared statement ausführen
     */
    private static function run(string $query, array $params = []): array|bool {
        self::connect();

        try {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute($params);

            if (stripos($query, "SELECT") === 0) {
                return $stmt->fetchAll();
            }

            return true;

        } catch (PDOException $e) {
            error_log("[SQL::run] Query failed: $query | Params: " . json_encode($params));
            error_log($e->getMessage());

            return false;
        }
    }

    /**
     * SELECT
     */
    public static function select(string $table, string $select = "*", string $where = "", mixed $is = "",): array|bool {
        if ($where !== "") {
            $query = "SELECT $select FROM `$table` WHERE `$where` = :is";
            return self::run($query, ["is" => $is]);
        }

        $query = "SELECT $select FROM `$table`";

        return self::run($query);
    }

    /**
     * INSERT
     */
    public static function insert(string $table, array $data): bool {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);

        $sql = "INSERT INTO `$table` (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";

        return self::run($sql, $data);
    }

    /**
     * UPDATE
     */
    public static function update(string $table, array $data, string $where, mixed $is): bool {
        $setParts = [];

        foreach ($data as $col => $val) {
            $setParts[] = "`$col` = :$col";
        }

        $sql = "UPDATE `$table` SET " . implode(", ", $setParts) . " WHERE `$where` = :whereVal";

        $data["whereVal"] = $is;

        return self::run($sql, $data);
    }

    /**
     * DELETE
     */
    public static function delete(string $table, string $where, mixed $is): bool {
        $sql = "DELETE FROM `$table` WHERE `$where` = :is";
        return self::run($sql, ["is" => $is]);
    }
}
