<?php

class PublicAPI_GBDB {
    private const ACTIONS = [
        "info" => ["method" => "info", "rights" => ["gbdb-read"]],
        "list_instances" => ["method" => "listInstances", "rights" => ["gbdb-read"]],
        "list_bases" => ["method" => "listBases", "rights" => ["gbdb-read"]],
        "list_tables" => ["method" => "listTables", "rights" => ["gbdb-read"]],
        "keys" => ["method" => "keys", "rights" => ["gbdb-read"]],
        "next_id" => ["method" => "nextId", "rights" => ["gbdb-read"]],
        "get" => ["method" => "get", "rights" => ["gbdb-read"]],
        "exists" => ["method" => "exists", "rights" => ["gbdb-read"]],
        "page" => ["method" => "page", "rights" => ["gbdb-read"]],
        "cursor" => ["method" => "cursor", "rights" => ["gbdb-read"]],
        "fulltext" => ["method" => "fulltext", "rights" => ["gbdb-read"]],
        "stats" => ["method" => "stats", "rights" => ["gbdb-read"]],

        "create_instance" => ["method" => "createInstance", "rights" => ["gbdb-admin"]],
        "drop_instance" => ["method" => "dropInstance", "rights" => ["gbdb-admin"]],
        "create_base" => ["method" => "createBase", "rights" => ["gbdb-write"]],
        "drop_base" => ["method" => "dropBase", "rights" => ["gbdb-admin"]],
        "create_table" => ["method" => "createTable", "rights" => ["gbdb-write"]],
        "drop_table" => ["method" => "dropTable", "rights" => ["gbdb-admin"]],
        "add_column" => ["method" => "addColumn", "rights" => ["gbdb-write"]],
        "insert" => ["method" => "insert", "rights" => ["gbdb-write"]],
        "bulk_insert" => ["method" => "bulkInsert", "rights" => ["gbdb-write"]],
        "update" => ["method" => "update", "rights" => ["gbdb-write"]],
        "upsert" => ["method" => "upsert", "rights" => ["gbdb-write"]],
        "delete" => ["method" => "deleteRows", "rights" => ["gbdb-write"]],

        "query" => ["method" => "query", "rights" => ["gbdb-gql"]],
        "run_script" => ["method" => "runScript", "rights" => ["gbdb-gql"]]
    ];

    /**
     * returns public api module name
     *
     * @return string
     */
    public static function name(): string {
        return "gbdb";
    }

    /**
     * returns required rights for action
     *
     * @param string $action
     * @return array
     */
    public static function requiresRights(string $action = ""): array {
        return PublicAPI_ModuleHelper::rights(self::ACTIONS, $action);
    }

    /**
     * handles public api action
     *
     * @param string $action
     * @param array $body
     * @param array $keyData
     * @return mixed
     */
    public static function handle(string $action, array $body, array $keyData): mixed {
        try {
            return PublicAPI_ModuleHelper::handle(self::class, self::ACTIONS, $action, $body, $keyData);
        } catch (Throwable $e) {
            return [
                "status" => 500,
                "data" => [
                    "error" => "gbdb api error",
                    "message" => $e->getMessage()
                ]
            ];
        }
    }

    private static function info(array $body, array $keyData): array {
        return [
            "module" => self::name(),
            "rights" => self::requiresRights(),
            "actions" => array_keys(self::ACTIONS)
        ];
    }

    private static function listInstances(array $body, array $keyData): array {
        $includeSystem = self::bool($body, "include_system", false);

        return [
            "instance" => GBDB::getInstance(),
            "items" => GBDB::listInstances($includeSystem)
        ];
    }

    private static function listBases(array $body, array $keyData): mixed {
        return self::run($body, function (): array {
            return [
                "instance" => GBDB::getInstance(),
                "items" => GBDB::listDBs()
            ];
        });
    }

    private static function listTables(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "base" => self::base($body),
                "items" => GBDB::listTables(self::base($body), self::bool($body, "desc", false))
            ];
        });
    }

    private static function keys(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return GBDB::getKeys(self::base($body), self::table($body));
        });
    }

    private static function nextId(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): int {
            return GBDB::nextID(self::base($body), self::table($body));
        });
    }

    private static function get(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): mixed {
            return GBDB::get(
                self::base($body),
                self::table($body),
                $body["where"] ?? "",
                $body["is"] ?? "",
                self::arr($body, "options")
            );
        });
    }

    private static function exists(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "where", "is"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "exists" => GBDB::exists(self::base($body), self::table($body), $body["where"], $body["is"])
            ];
        });
    }

    private static function page(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return GBDB::page(self::base($body), self::table($body), self::int($body, "page", 1), self::int($body, "per_page", 50));
        });
    }

    private static function cursor(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return GBDB::cursor(self::base($body), self::table($body), self::int($body, "limit", 100), self::str($body, "cursor", ""));
        });
    }

    private static function fulltext(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "query"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return GBDB::fulltext(
                self::base($body),
                self::table($body),
                self::str($body, "query"),
                self::cleanList(self::arr($body, "columns")),
                self::int($body, "limit", 50)
            );
        });
    }

    private static function stats(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            $table = self::table($body, false);

            return GBDB::stats(self::base($body), $table == "" ? null : $table);
        });
    }

    private static function createInstance(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["instance"])) !== true) {
            return $err;
        }

        return [
            "done" => GBDB::createInstance(self::instance($body)),
            "instance" => self::instance($body)
        ];
    }

    private static function dropInstance(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["instance"])) !== true) {
            return $err;
        }

        return [
            "done" => GBDB::deleteInstance(self::instance($body), self::bool($body, "force", false)),
            "instance" => self::instance($body)
        ];
    }

    private static function createBase(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "done" => GBDB::createDatabase(self::base($body)),
                "base" => self::base($body)
            ];
        });
    }

    private static function dropBase(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "done" => GBDB::deleteDatabase(self::base($body)),
                "base" => self::base($body)
            ];
        });
    }

    private static function createTable(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "columns"])) !== true) {
            return $err;
        }

        $columns = self::cleanList(self::arr($body, "columns"));

        if (empty($columns)) {
            return ["status" => 400, "data" => "columns must be a non empty array"];
        }

        return self::run($body, function () use ($body, $columns): array {
            return [
                "done" => GBDB::createTable(self::base($body), self::table($body), $columns),
                "base" => self::base($body),
                "table" => self::table($body),
                "columns" => $columns
            ];
        });
    }

    private static function dropTable(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "done" => GBDB::deleteTable(self::base($body), self::table($body)),
                "base" => self::base($body),
                "table" => self::table($body)
            ];
        });
    }

    private static function addColumn(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "column"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            $column = self::clean(self::str($body, "column"));

            if ($column == "") {
                return ["done" => false, "error" => "invalid column"];
            }

            return [
                "done" => GBDB::addColumn(self::base($body), self::table($body), $column, $body["default"] ?? ""),
                "column" => $column
            ];
        });
    }

    private static function insert(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "data"])) !== true) {
            return $err;
        }

        $data = self::arr($body, "data");

        if (empty($data)) {
            return ["status" => 400, "data" => "data must be a non empty object"];
        }

        return self::run($body, function () use ($body, $data): array {
            return [
                "id" => GBDB::insert(self::base($body), self::table($body), $data)
            ];
        });
    }

    private static function bulkInsert(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "rows"])) !== true) {
            return $err;
        }

        $rows = self::arr($body, "rows");

        if (empty($rows)) {
            return ["status" => 400, "data" => "rows must be a non empty array"];
        }

        return self::run($body, function () use ($body, $rows): array {
            return GBDB::bulkInsert(self::base($body), self::table($body), $rows, self::bool($body, "transactional", true));
        });
    }

    private static function update(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "where", "is", "data"])) !== true) {
            return $err;
        }

        $data = self::arr($body, "data");

        if (empty($data)) {
            return ["status" => 400, "data" => "data must be a non empty object"];
        }

        return self::run($body, function () use ($body, $data): array {
            return [
                "done" => GBDB::edit(self::base($body), self::table($body), $body["where"], $body["is"], $data)
            ];
        });
    }

    private static function upsert(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "where", "is", "data"])) !== true) {
            return $err;
        }

        $data = self::arr($body, "data");

        if (empty($data)) {
            return ["status" => 400, "data" => "data must be a non empty object"];
        }

        return self::run($body, function () use ($body, $data): array {
            return GBDB::upsert(self::base($body), self::table($body), $body["where"], $body["is"], $data);
        });
    }

    private static function deleteRows(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["base", "table", "where", "is"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return [
                "done" => GBDB::delete(self::base($body), self::table($body), $body["where"], $body["is"])
            ];
        });
    }

    private static function query(array $body, array $keyData): mixed {
        if (($err = self::requireBody($body, ["script"])) !== true) {
            return $err;
        }

        return self::run($body, function () use ($body): array {
            return GBDB::query(self::str($body, "script"), self::arr($body, "ctx"), self::arr($body, "params"));
        });
    }

    private static function runScript(array $body, array $keyData): mixed {
        $script = self::str($body, "script", self::str($body, "script_name"));

        if ($script == "") {
            return ["status" => 400, "data" => "missing param: script"];
        }

        return self::run($body, function () use ($body, $script): array {
            return GBDB::runScript($script, self::arr($body, "params"), self::arr($body, "ctx"));
        });
    }

    private static function run(array $body, callable $callback): mixed {
        $instance = self::instance($body, false);

        if ($instance == "") {
            return $callback();
        }

        return GBDB::withInstance($instance, $callback);
    }

    private static function requireBody(array $body, array $params): bool|array {
        foreach ($params as $param) {
            if ($param == "base" && (isset($body["base"]) || isset($body["database"]))) {
                continue;
            }

            if (!isset($body[$param])) {
                return [
                    "status" => 400,
                    "data" => "missing param: " . $param
                ];
            }
        }

        return true;
    }

    private static function str(array $body, string $key, string $default = ""): string {
        return (string) ($body[$key] ?? $default);
    }

    private static function int(array $body, string $key, int $default = 0): int {
        return (int) ($body[$key] ?? $default);
    }

    private static function bool(array $body, string $key, bool $default = false): bool {
        if (!isset($body[$key])) {
            return $default;
        }

        if (is_bool($body[$key])) {
            return $body[$key];
        }

        return in_array(strtolower((string) $body[$key]), ["1", "true", "yes", "on"], true);
    }

    private static function arr(array $body, string $key): array {
        if (!isset($body[$key]) || !is_array($body[$key])) {
            return [];
        }

        return $body[$key];
    }

    private static function instance(array $body, bool $required = true): string {
        $instance = self::clean(self::str($body, "instance"));

        if ($required && $instance == "") {
            return "default";
        }

        return $instance;
    }

    private static function base(array $body): string {
        return self::clean((string) ($body["base"] ?? ($body["database"] ?? "")));
    }

    private static function table(array $body, bool $required = true): string {
        $table = self::clean(self::str($body, "table"));

        if (!$required && $table == "") {
            return "";
        }

        return $table;
    }

    private static function clean(string $value): string {
        return Format::cleanString($value);
    }

    private static function cleanList(array $items): array {
        $out = [];

        foreach ($items as $item) {
            $item = self::clean((string) $item);

            if ($item == "") {
                continue;
            }

            if (!in_array($item, $out, true)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}