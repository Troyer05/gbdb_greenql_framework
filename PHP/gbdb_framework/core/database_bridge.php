<?php

class DatabaseBridge {
    private static string $driver = "";
    private static string $instance = "";

    /**
     * Sets database driver manually (SQL or GBDB)
     * @param string $driver
     * @return void
     */
    public static function setDriver(string $driver): void {
        $driver = strtoupper(trim($driver));
        self::$driver = in_array($driver, ["GBDB", "SQL"], true) ? $driver : "";
    }

    /**
     * sets active gbdb-instance
     * @param string $instance
     * @return void
     */
    public static function setInstance(string $instance): void {
        self::$instance = trim($instance);

        if (self::$instance !== "" && class_exists("GBDB")) {
            self::$driver = "GBDB";
            GBDB::setInstance(self::$instance);
        }

    }

    private static function driver(): string {
        if (self::$driver !== "") {
            return self::$driver;
        }

        if (class_exists("Vars") && method_exists("Vars", "db_arch")) {
            return Vars::db_arch();
        }

        if (defined("DB_ARCH") && strtoupper((string)DB_ARCH) === "SQL") {
            return "SQL";
        }

        return "GBDB";
    }

    private static function isSQL(): bool {
        return self::driver() === "SQL";
    }

    private static function isGBDB(): bool {
        return self::driver() === "GBDB";
    }

    private static function ensureSQL(): void {
        if (self::isSQL()) {
            try {
                SQL::connect();
            } catch (Throwable $e) {
                error_log("[DatabaseBridge] SQL connection failed: " . $e->getMessage());
            }

        }

    }

    private static function ensureGBDB(): void {
        if (self::isGBDB() && self::$instance !== "" && class_exists("GBDB")) {
            GBDB::setInstance(self::$instance);
        }

    }

    /**
     * gets data
     * @param string $db
     * @param string $table
     * @param bool $filter
     * @param string $where
     * @param mixed $is
     * @return mixed
     */
    public static function get(string $db, string $table, bool $filter = false, string $where = "", mixed $is = ""): mixed {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::getData($db, $table, $filter, $where, $is);
        }

        if (!self::isSQL()) {
            return GBDB::getData($db, $table, $filter, $where, $is);
        }

        self::ensureSQL();

        try {
            if (!$filter) {
                return SQL::select($table);
            }

            $isValue = is_string($is) ? "'" . addslashes($is) . "'" : $is;

            return SQL::select($table, "*", $where, $isValue);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL SELECT failed: " . $e->getMessage());

            return [];
        }

    }

    /**
     * inserts data
     * @param string $db
     * @param string $table
     * @param array $data
     * @return bool|int
     */
    public static function insert(string $db, string $table, array $data): mixed {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::insert($db, $table, $data);
        }

        if (!self::isSQL()) {
            return GBDB::insert($db, $table, $data);
        }

        self::ensureSQL();

        try {
            return SQL::insert($table, $data);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL INSERT failed: " . $e->getMessage());

            return false;
        }

    }

    /**
     * deletes data
     * @param string $db
     * @param string $table
     * @param string $where
     * @param mixed $is
     * @return bool
     */
    public static function delete(string $db, string $table, string $where, mixed $is): mixed {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::delete($db, $table, $where, $is);
        }

        if (!self::isSQL()) {
            return GBDB::delete($db, $table, $where, $is);
        }

        self::ensureSQL();

        try {
            return SQL::delete($table, $where, $is);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL DELETE failed: " . $e->getMessage());

            return false;
        }

    }

    /**
     * updates data
     * @param string $db
     * @param string $table
     * @param string $where
     * @param mixed $is
     * @param array $data
     * @return bool
     */
    public static function update(string $db, string $table, string $where, mixed $is, array $data): mixed {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::edit($db, $table, $where, $is, $data);
        }

        if (!self::isSQL()) {
            return GBDB::edit($db, $table, $where, $is, $data);
        }

        self::ensureSQL();

        try {
            return SQL::update($table, $data, $where, $is);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL UPDATE failed: " . $e->getMessage());

            return false;
        }

    }

    /**
     * creates database
     * @param string $name
     * @return bool
     */
    public static function createDatabase(string $name): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::createDatabase($name);
        }

        if (!self::isSQL()) {
            return GBDB::createDatabase($name);
        }

        return true;
    }

    /**
     * deletes database
     * @param string $name
     * @return bool
     */
    public static function deleteDatabase(string $name): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::deleteDatabase($name);
        }

        if (!self::isSQL()) {
            return GBDB::deleteDatabase($name);
        }

        return false;
    }

    /**
     * creates table
     * @param string $db
     * @param string $table
     * @param array $columns
     * @return bool
     */
    public static function createTable(string $db, string $table, array $columns): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::createTable($db, $table, $columns);
        }

        if (!self::isSQL()) {
            return GBDB::createTable($db, $table, $columns);
        }

        return false;
    }

    /**
     * deletes table
     * @param string $db
     * @param string $table
     * @return bool
     */
    public static function deleteTable(string $db, string $table): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::deleteTable($db, $table);
        }

        if (!self::isSQL()) {
            return GBDB::deleteTable($db, $table);
        }

        return false;
    }

    /**
     * adds new column
     * @param string $db
     * @param string $table
     * @param string $column
     * @param mixed $default
     * @return bool
     */
    public static function addColumn(string $db, string $table, string $column, mixed $default = ""): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::addColumn($db, $table, $column, $default);
        }

        if (!self::isSQL()) {
            return GBDB::addColumn($db, $table, $column, $default);
        }

        return false;
    }

    /**
     * creates new index
     * @param string $db
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function createIndex(string $db, string $table, string $column): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::createIndex($db, $table, $column);
        }

        return !self::isSQL() && GBDB::createIndex($db, $table, $column);
    }

    /**
     * starts transaction
     * @return bool
     */
    public static function begin(): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::begin();
        }

        return !self::isSQL() && GBDB::begin();
    }

    /**
     * saves transaction
     * @return bool
     */
    public static function commit(): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::commit();
        }

        return !self::isSQL() && GBDB::commit();
    }

    /**
     * deletes transaction
     * @return bool
     */
    public static function rollback(): bool {
        if (self::isGBDB()) {
            self::ensureGBDB();

            return GBDB::rollback();
        }

        return !self::isSQL() && GBDB::rollback();
    }

}
