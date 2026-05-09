<?php

class Cache {
    private static string $return_to_instance = "";

    private static function instance(): string {
        return Vars::cache_instance();
    }

    private static function rememberInstance(): void {
        self::$return_to_instance = "";

        if (method_exists("GBDB", "getInstance")) {
            $current = GBDB::getInstance();

            if (is_string($current) && $current !== "") {
                self::$return_to_instance = $current;
            }

        }

    }

    private static function switchInstance(string $to = ""): void {
        if ($to === "") {
            self::rememberInstance();
            GBDB::setInstance(self::instance());

            return;
        }

        if (self::$return_to_instance !== "") {
            GBDB::setInstance(self::$return_to_instance);
            self::$return_to_instance = "";

            return;
        }

        if (Vars::main_instance() !== "") {
            GBDB::setInstance(Vars::main_instance());
        }

    }

    protected static function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!isset($_SESSION["cache"]) || !is_array($_SESSION["cache"])) {
            $_SESSION["cache"] = [
                "updates" => [],
                "data" => []
            ];
        }

        if (!isset($_SESSION["cache"]["updates"]) || !is_array($_SESSION["cache"]["updates"])) {
            $_SESSION["cache"]["updates"] = [];
        }

        if (!isset($_SESSION["cache"]["data"]) || !is_array($_SESSION["cache"]["data"])) {
            $_SESSION["cache"]["data"] = [];
        }

    }

    private static function key(string $db, string $table): string {
        return $db . "::" . $table;
    }

    private static function findUpdate(string $table, mixed $cache): ?string {
        if (!is_array($cache)) {
            return null;
        }

        foreach ($cache as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!isset($entry["table"], $entry["update"])) {
                continue;
            }

            if ((string)$entry["table"] === $table) {
                return (string)$entry["update"];
            }

        }

        return null;
    }

    private static function ensureCacheTable(string $db): void {
        $dbs = GBDB::listDBs();

        if (!is_array($dbs) || !in_array($db, $dbs, true)) {
            GBDB::createDatabase($db);
        }

        $tables = GBDB::listTables($db);

        if (!is_array($tables) || !in_array("cache", $tables, true)) {
            GBDB::createTable($db, "cache", ["table", "update"]);
        }

    }

    /**
     * Load data from cache (it gets updated automatically)
     *
     * @param string $db
     * @param string $table
     * @param mixed $cache
     * @return array
     */
    public static function load(string $db, string $table, mixed $cache): array {
        self::ensureSession();

        $currentUpdate = self::findUpdate($table, $cache);

        if ($currentUpdate === null || $currentUpdate === "") {
            return [];
        }

        $key = self::key($db, $table);

        $needsUpdate =
            !isset($_SESSION["cache"]["updates"][$key]) ||
            $_SESSION["cache"]["updates"][$key] !== $currentUpdate ||
            !isset($_SESSION["cache"]["data"][$key]) ||
            !is_array($_SESSION["cache"]["data"][$key]);

        if (!$needsUpdate) {
            return $_SESSION["cache"]["data"][$key];
        }

        $data = GBDB::getData($db, $table);

        if (!is_array($data)) {
            $_SESSION["cache"]["data"][$key] = [];
            $_SESSION["cache"]["updates"][$key] = $currentUpdate;

            return [];
        }

        $_SESSION["cache"]["data"][$key] = $data;
        $_SESSION["cache"]["updates"][$key] = $currentUpdate;

        return $_SESSION["cache"]["data"][$key];
    }

    /**
     * renews the update-key of a cached table
     *
     * @param string $db
     * @param string $table
     * @return void
     */
    public static function update(string $db, string $table): void {
        self::switchInstance();

        try {
            $newUpdate = bin2hex(random_bytes(16));

            self::ensureCacheTable($db);

            $row = GBDB::getData($db, "cache", true, "table", $table);

            if (is_array($row) && !empty($row)) {
                GBDB::editData($db, "cache", "table", $table, [
                    "update" => $newUpdate
                ]);
            } else {
                GBDB::insertData($db, "cache", [
                    "table" => $table,
                    "update" => $newUpdate
                ]);
            }

        } finally {
            self::switchInstance("restore");
        }

    }

    /**
     * Deletes data of a table in the cache
     *
     * if only $db is set, only that specific table will be deleted from cache
     * if $db is null, all databases will be searched for that table and these tables will get deleted from cache
     *
     * @param string $table
     * @param string|null $db
     * @return void
     */
    public static function clear(string $table, ?string $db = null): void {
        self::ensureSession();

        foreach ($_SESSION["cache"]["data"] as $key => $value) {
            $matches = $db !== null
                ? $key === self::key($db, $table)
                : str_ends_with($key, "::" . $table);

            if ($matches) {
                unset($_SESSION["cache"]["data"][$key]);
                unset($_SESSION["cache"]["updates"][$key]);
            }

        }

    }

    /**
     * deletes cache
     *
     * @return void
     */
    public static function flush(): void {
        self::ensureSession();

        $_SESSION["cache"] = [
            "updates" => [],
            "data" => []
        ];
    }

    /**
     * tests if a table exists in the cache
     *
     * if only $db is set, only that specific table will be cached
     * if $db is null, all databases will be searched for that table and these tables will get cached
     *
     * @param string $table
     * @param string|null $db
     * @return bool
     */
    public static function exists(string $table, ?string $db = null): bool {
        self::ensureSession();

        foreach ($_SESSION["cache"]["data"] as $key => $value) {
            if ($db !== null && $key === self::key($db, $table)) {
                return true;
            }

            if ($db === null && str_ends_with($key, "::" . $table)) {
                return true;
            }

        }

        return false;
    }

    /**
     * gets you a secific cache entry
     *
     * @param string $db
     * @param string $table
     * @return array
     */
    public static function get(string $db, string $table): array {
        self::ensureSession();

        $key = self::key($db, $table);

        if (!isset($_SESSION["cache"]["data"][$key]) || !is_array($_SESSION["cache"]["data"][$key])) {
            return [];
        }

        return $_SESSION["cache"]["data"][$key];
    }

    /**
     * get the saved update-key from a sepcific table
     *
     * @param string $db
     * @param string $table
     * @return string
     */
    public static function updateKey(string $db, string $table): string {
        self::ensureSession();

        $key = self::key($db, $table);

        if (!isset($_SESSION["cache"]["updates"][$key])) {
            return "";
        }

        return (string)$_SESSION["cache"]["updates"][$key];
    }

    /**
     * get entire cached cache
     *
     * @return array
     */
    public static function all(): array {
        self::ensureSession();

        return $_SESSION["cache"]["data"];
    }

    /**
     * get saved update-keys
     *
     * @return array
     */
    public static function updates(): array {
        self::ensureSession();

        return $_SESSION["cache"]["updates"];
    }

}
