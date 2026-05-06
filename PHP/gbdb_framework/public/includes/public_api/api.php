<?php

/**
 * @author Markus Müller
 *
 * Public API Core
 */

class PAPI {
    private static array $body = [];

    private const ACTION_FUNCTIONS = [
        "ping" => "apiPing",
        "version" => "version",

        // GBDB v1
        "gbdb_databases" => "gbdb_databases",
        "gbdb_create_database" => "gbdb_create_database",
        "gbdb_delete_database" => "gbdb_delete_database",
        "gbdb_delete_all" => "gbdb_delete_all",
        "gbdb_tables" => "gbdb_tables",
        "gbdb_create_table" => "gbdb_create_table",
        "gbdb_delete_table" => "gbdb_delete_table",
        "gbdb_add_column" => "gbdb_add_column",
        "gbdb_schema" => "gbdb_schema",
        "gbdb_keys" => "gbdb_schema",
        "gbdb_data" => "gbdb_data",
        "gbdb_row" => "gbdb_row",
        "gbdb_exists" => "gbdb_exists",
        "gbdb_insert" => "gbdb_insert",
        "gbdb_update" => "gbdb_update",
        "gbdb_delete" => "gbdb_delete",
        "gbdb_next_id" => "gbdb_next_id",
        "gbdb_compact" => "gbdb_compact",
        "gbdb_query" => "gbdb_query",
        "gbdb_run_script" => "gbdb_run_script",
        "greenql" => "gbdb_query",
        "get_gbdb_databases" => "gbdb_databases",
        "get_gbdb_tables" => "gbdb_tables",
        "get_gbdb_data" => "gbdb_data",

        // GBDB v4 short API / Woche 102
        "gbdb_get" => "gbdb_short_get",
        "gbdb_create" => "gbdb_short_create",
        "gbdb_full_text_search" => "gbdb_short_full_text_search",
        "gbdb_rename_table" => "gbdb_short_rename_table",
        "gbdb_rename_base" => "gbdb_short_rename_base",
        "gbdb_move_table" => "gbdb_short_move_table",
        "gbdb_backup" => "gbdb_short_backup",
        "gbdb_run_file" => "gbdb_short_run_file",

        // GBDB v2
        "gbdbv2_instance" => "gbdbv2_instance",
        "gbdbv2_instances" => "gbdbv2_instances",
        "gbdbv2_create_instance" => "gbdbv2_create_instance",
        "gbdbv2_delete_instance" => "gbdbv2_delete_instance",
        "gbdbv2_databases" => "gbdbv2_databases",
        "gbdbv2_create_database" => "gbdbv2_create_database",
        "gbdbv2_delete_database" => "gbdbv2_delete_database",
        "gbdbv2_delete_all" => "gbdbv2_delete_all",
        "gbdbv2_tables" => "gbdbv2_tables",
        "gbdbv2_create_table" => "gbdbv2_create_table",
        "gbdbv2_delete_table" => "gbdbv2_delete_table",
        "gbdbv2_add_column" => "gbdbv2_add_column",
        "gbdbv2_schema" => "gbdbv2_schema",
        "gbdbv2_keys" => "gbdbv2_schema",
        "gbdbv2_data" => "gbdbv2_data",
        "gbdbv2_row" => "gbdbv2_row",
        "gbdbv2_exists" => "gbdbv2_exists",
        "gbdbv2_insert" => "gbdbv2_insert",
        "gbdbv2_update" => "gbdbv2_update",
        "gbdbv2_delete" => "gbdbv2_delete",
        "gbdbv2_next_id" => "gbdbv2_next_id",
        "gbdbv2_compact" => "gbdbv2_compact",
        "gbdbv2_query" => "gbdbv2_query",
        "gbdbv2_run_script" => "gbdbv2_run_script",
        "greenql" => "gbdbv2_query"
    ];

    /**
     * Initialisiert die Public API.
     *
     * @return void
     */
    public static function init(): void {
        self::setBody(self::getBody());
        self::auth();
        self::core();
    }

    /**
     * Liest den JSON-Request-Body ein.
     *
     * @return array
     */
    public static function getBody(): array {
        $inp = file_get_contents("php://input");

        if ($inp === false || trim($inp) === "") {
            return [];
        }

        $json = json_decode($inp, true);

        if (!is_array($json)) {
            self::resp(400, "Error: Invalid JSON body.", false);
        }

        return $json;
    }

    /**
     * Speichert den Request-Body intern.
     *
     * @param array $body Request-Body.
     * @return void
     */
    public static function setBody(array $body): void {
        self::$body = $body;
    }

    /**
     * Gibt den kompletten Body zurück.
     *
     * @return array
     */
    public static function body(): array {
        return self::$body;
    }

    /**
     * Gibt einen Wert aus dem Body zurück.
     *
     * @param string $key Schlüssel.
     * @param mixed $default Standardwert.
     * @return mixed
     */
    public static function val(string $key, mixed $default = null): mixed {
        return self::$body[$key] ?? $default;
    }

    /**
     * Prüft, ob ein Body-Key existiert.
     *
     * @param string $key Schlüssel.
     * @return bool
     */
    public static function has(string $key): bool {
        return array_key_exists($key, self::$body);
    }

    /**
     * Gibt eine JSON-Antwort aus und beendet das Script.
     *
     * @param int $responseStatus HTTP-Statuscode.
     * @param mixed $data Antwortdaten.
     * @param bool|null $ok Erfolgsstatus.
     * @return void
     */
    public static function resp(int $responseStatus, mixed $data, ?bool $ok = null): void {
        http_response_code($responseStatus);
        header("Content-Type: application/json; charset=utf-8");

        if ($ok === null) {
            $ok = ($responseStatus >= 200 && $responseStatus < 300);
        }

        echo json_encode([
            "ok" => $ok,
            "status" => $responseStatus,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * Gibt eine erfolgreiche Antwort aus.
     *
     * @param mixed $data Antwortdaten.
     * @param int $status HTTP-Statuscode.
     * @return void
     */
    public static function success(mixed $data = [], int $status = 200): void {
        self::resp($status, $data, true);
    }

    /**
     * Gibt eine Fehlerantwort aus.
     *
     * @param string $msg Fehlermeldung.
     * @param int $status HTTP-Statuscode.
     * @param mixed|null $details Details.
     * @return void
     */
    public static function error(string $msg, int $status = 400, mixed $details = null): void {
        $data = [
            "msg" => $msg
        ];

        if ($details !== null) {
            $data["details"] = $details;
        }

        self::resp($status, $data, false);
    }

    /**
     * Prüft Pflichtparameter.
     *
     * @param array $params Parameter.
     * @return void
     */
    public static function test_params(array $params): void {
        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];

            if (!array_key_exists($param, self::$body)) {
                self::error("Parameter \"" . $param . "\" not provided.", 403);
            }

            if (is_string(self::$body[$param]) && trim(self::$body[$param]) === "") {
                self::error("Parameter \"" . $param . "\" is empty.", 403);
            }
        }
    }

    /**
     * Prüft die Authentifizierung.
     *
     * @return void
     */
    public static function auth(): void {
        if (!Vars::pApi_need_auth()) {
            return;
        }

        self::test_params(["auth_key"]);

        $keys = Vars::pApi_auth_keys();

        if (!is_array($keys) || count($keys) === 0) {
            self::error("Authentication failed. No auth keys configured.", 403);
        }

        $auth_key = (string)self::$body["auth_key"];
        $ok = false;

        for ($i = 0; $i < count($keys); $i++) {
            if (hash_equals((string)$keys[$i], $auth_key)) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            self::error("Authentication failed. Wrong auth_key.", 403);
        }
    }

    /**
     * Verarbeitet die zentrale API-Aktion.
     *
     * @return void
     */
    public static function core(): void {
        self::test_params(["do"]);

        $do = trim((string)self::$body["do"]);

        if ($do === "") {
            self::error("Parameter \"do\" is empty.", 403);
        }

        if (!isset(self::ACTION_FUNCTIONS[$do])) {
            self::error("Unknown API action: " . $do, 404);
        }

        $fn = self::ACTION_FUNCTIONS[$do];

        if (!method_exists(__CLASS__, $fn)) {
            self::error("API action handler not found: " . $fn, 500);
        }

        self::{$fn}();
    }

    /**
     * Prüft GBDB-Lesezugriff.
     *
     * @return void
     */
    private static function requireGbdbAccess(): void {
        if (method_exists("Vars", "pApi_access_gbdb") && !Vars::pApi_access_gbdb()) {
            self::error("GBDB access on public API is denied. To change that, edit the .framework.env.php", 403);
        }
    }

    /**
     * Prüft GBDB-Schreibzugriff.
     *
     * @return void
     */
    private static function requireGbdbWriteAccess(): void {
        self::requireGbdbAccess();

        if (method_exists("Vars", "pApi_write_gbdb") && !Vars::pApi_write_gbdb()) {
            self::error("GBDB write access on public API is denied. To change that, edit the .framework.env.php", 403);
        }
    }

    /**
     * Prüft GreenQL-Zugriff.
     *
     * @return void
     */
    private static function requireGreenqlAccess(): void {
        self::requireGbdbAccess();

        if (method_exists("Vars", "pApi_greenql") && !Vars::pApi_greenql()) {
            self::error("GreenQL access on public API is denied. To change that, edit the .framework.env.php", 403);
        }
    }

    /**
     * Prüft GBDBv1.
     *
     * @return void
     */
    private static function requireGbdb(): void {
        if (!class_exists("GBDB")) {
            self::error("Required class \"GBDB\" not found.", 500);
        }
    }

    /**
     * Prüft GBDB.
     *
     * @return void
     */
    private static function requireGbdbv2(): void {
        if (!class_exists("GBDB")) {
            self::error("Required class \"GBDB\" not found.", 500);
        }
    }

    /**
     * Holt einen String-Parameter.
     *
     * @param string $key Schlüssel.
     * @return string
     */
    private static function strParam(string $key): string {
        self::test_params([$key]);

        return trim((string)self::$body[$key]);
    }

    /**
     * Holt einen Array-Parameter.
     *
     * @param string $key Schlüssel.
     * @return array
     */
    private static function arrParam(string $key): array {
        self::test_params([$key]);

        if (!is_array(self::$body[$key])) {
            self::error("Parameter \"" . $key . "\" must be an array.", 403);
        }

        return self::$body[$key];
    }

    /**
     * Holt einen Boolean-Parameter.
     *
     * @param string $key Schlüssel.
     * @param bool $default Standardwert.
     * @return bool
     */
    private static function boolParam(string $key, bool $default = false): bool {
        if (!array_key_exists($key, self::$body)) {
            return $default;
        }

        return filter_var(self::$body[$key], FILTER_VALIDATE_BOOL);
    }

    /**
     * Holt where/is Parameter.
     *
     * @return array
     */
    private static function whereParams(): array {
        $where = trim((string)self::val("where", "id"));
        $is = self::val("is", self::val("id", null));

        if ($where === "") {
            self::error("Parameter \"where\" is empty.", 403);
        }

        if ($is === null || trim((string)$is) === "") {
            self::error("Parameter \"id\" or \"is\" not provided.", 403);
        }

        return [$where, $is];
    }

    /**
     * Setzt optional die GBDB-Instanz.
     *
     * @return void
     */
    private static function applyGbdbv2Instance(): void {
        if (isset(self::$body["instance"])) {
            GBDB::setInstance((string)self::$body["instance"]);
        }
    }

    // ######################################
    // # BASIC ACTIONS                      #
    // ######################################

    /**
     * Verarbeitet den API-Ping.
     *
     * @return void
     */
    private static function apiPing(): void {
        self::success([
            "pong" => true,
            "time" => time()
        ]);
    }

    /**
     * Gibt Versionsinformationen zurück.
     *
     * @return void
     */
    private static function version(): void {
        self::success([
            "framework_version" => method_exists("Vars", "framework_version") ? Vars::framework_version() : null,
            "app_version" => method_exists("Vars", "app_version") ? Vars::app_version() : null,
            "public_api_version" => "4.0",
            "gbdb_api" => ["legacy" => ["v1", "v2"], "current" => "v4", "compatibility" => true]
        ]);
    }

    // ######################################
    // # GBDB v1 ACTIONS                    #
    // ######################################

    /**
     * Gibt alle GBDB-Datenbanken zurück.
     *
     * @return void
     */
    private static function gbdb_databases(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        self::success(GBDB::listDBs());
    }

    /**
     * Erstellt eine GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdb_create_database(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");

        self::success([
            "created" => GBDB::createDatabase($database)
        ]);
    }

    /**
     * Löscht eine leere GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdb_delete_database(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");

        self::success([
            "deleted" => GBDB::deleteDatabase($database)
        ]);
    }

    /**
     * Löscht eine komplette GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdb_delete_all(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");

        self::success([
            "deleted" => GBDB::deleteAll($database)
        ]);
    }

    /**
     * Gibt alle Tabellen einer GBDB-Datenbank zurück.
     *
     * @return void
     */
    private static function gbdb_tables(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $descending = self::boolParam("descending", false);

        self::success(GBDB::listTables($database, $descending));
    }

    /**
     * Erstellt eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdb_create_table(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $cols = self::arrParam("cols");

        self::success([
            "created" => GBDB::createTable($database, $table, $cols)
        ]);
    }

    /**
     * Löscht eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdb_delete_table(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "deleted" => GBDB::deleteTable($database, $table)
        ]);
    }

    /**
     * Fügt einer GBDB-Tabelle eine Spalte hinzu.
     *
     * @return void
     */
    private static function gbdb_add_column(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $column = self::strParam("column");
        $default = self::val("default", "");

        self::success([
            "added" => GBDB::addColumn($database, $table, $column, $default)
        ]);
    }

    /**
     * Gibt die Keys einer GBDB-Tabelle zurück.
     *
     * @return void
     */
    private static function gbdb_schema(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success(GBDB::getKeys($database, $table));
    }

    /**
     * Gibt Daten aus einer GBDB-Tabelle zurück.
     *
     * @return void
     */
    private static function gbdb_data(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $filter = self::boolParam("filter", false);

        if ($filter) {
            [$where, $is] = self::whereParams();
            self::success(GBDB::getData($database, $table, true, $where, $is));
        }

        self::success(GBDB::getData($database, $table));
    }

    /**
     * Gibt eine einzelne GBDB-Zeile zurück.
     *
     * @return void
     */
    private static function gbdb_row(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success(GBDB::getData($database, $table, true, $where, $is));
    }

    /**
     * Prüft, ob ein GBDB-Element existiert.
     *
     * @return void
     */
    private static function gbdb_exists(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success([
            "exists" => GBDB::elementExists($database, $table, $where, $is)
        ]);
    }

    /**
     * Fügt GBDB-Daten ein.
     *
     * @return void
     */
    private static function gbdb_insert(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $data = self::arrParam("data");

        $id = GBDB::insertData($database, $table, $data);

        self::success([
            "inserted" => $id > 0,
            "id" => $id
        ]);
    }

    /**
     * Bearbeitet GBDB-Daten.
     *
     * @return void
     */
    private static function gbdb_update(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $data = self::arrParam("data");

        [$where, $is] = self::whereParams();

        self::success([
            "updated" => GBDB::editData($database, $table, $where, $is, $data)
        ]);
    }

    /**
     * Löscht GBDB-Daten.
     *
     * @return void
     */
    private static function gbdb_delete(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success([
            "deleted" => GBDB::deleteData($database, $table, $where, $is)
        ]);
    }

    /**
     * Gibt die nächste GBDB-ID zurück.
     *
     * @return void
     */
    private static function gbdb_next_id(): void {
        self::requireGbdbAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "next_id" => GBDB::nextID($database, $table)
        ]);
    }

    /**
     * Komprimiert eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdb_compact(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdb();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "compacted" => GBDB::compactTable($database, $table)
        ]);
    }

    /**
     * Führt eine GreenQL-Abfrage aus.
     *
     * @return void
     */
    private static function gbdb_query(): void {
        self::requireGreenqlAccess();
        self::requireGbdb();

        $query = self::strParam("query");
        $ctx = self::val("ctx", []);
        $params = self::val("params", []);

        if (!is_array($ctx)) {
            self::error("Parameter \"ctx\" must be an array.", 403);
        }

        if (!is_array($params)) {
            self::error("Parameter \"params\" must be an array.", 403);
        }

        self::success(GBDB::query($query, $ctx, $params));
    }

    /**
     * Führt ein GreenQL-Script aus.
     *
     * @return void
     */
    private static function gbdb_run_script(): void {
        self::requireGreenqlAccess();
        self::requireGbdb();

        $path = self::strParam("path");
        $ctx = self::val("ctx", []);
        $params = self::val("params", []);

        if (!is_array($ctx)) {
            self::error("Parameter \"ctx\" must be an array.", 403);
        }

        if (!is_array($params)) {
            self::error("Parameter \"params\" must be an array.", 403);
        }

        self::success(GBDB::runScript($path, $params, $ctx));
    }

    private static function gbdb_short_get(): void { self::requireGbdbAccess(); self::requireGbdb(); $options = self::val("options", []); if (!is_array($options)) $options = []; self::success(GBDB::get(self::strParam("base"), self::strParam("table"), self::val("where", ""), self::val("is", ""), $options)); }
    private static function gbdb_short_create(): void { self::requireGbdbWriteAccess(); self::requireGbdb(); $rows = self::val("rows", []); if (!is_array($rows) && !is_object($rows)) $rows = []; self::success(["created" => GBDB::create(self::strParam("base"), (string)self::val("table", ""), self::boolParam("use_data_types", false), $rows)]); }
    private static function gbdb_short_full_text_search(): void { self::requireGbdbAccess(); self::requireGbdb(); self::success(GBDB::fullTextSearch(self::strParam("base"), self::strParam("table"), self::strParam("text"))); }
    private static function gbdb_short_rename_table(): void { self::requireGbdbWriteAccess(); self::requireGbdb(); self::success(["renamed" => GBDB::renameTable(self::strParam("base"), self::strParam("table"), self::strParam("new_table"))]); }
    private static function gbdb_short_rename_base(): void { self::requireGbdbWriteAccess(); self::requireGbdb(); self::success(["renamed" => GBDB::renameBase(self::strParam("base"), self::strParam("new_base"))]); }
    private static function gbdb_short_move_table(): void { self::requireGbdbWriteAccess(); self::requireGbdb(); self::success(["moved" => GBDB::moveTable(self::strParam("instance"), self::strParam("base"), self::strParam("table"), self::strParam("to_instance"), self::strParam("to_base"))]); }
    private static function gbdb_short_backup(): void { self::requireGbdbWriteAccess(); self::requireGbdb(); self::success(GBDB::createBackup((string)self::val("path", ""))); }
    private static function gbdb_short_run_file(): void { self::requireGreenqlAccess(); self::requireGbdb(); $params = self::val("params", []); if (!is_array($params) && !is_object($params)) $params = []; self::success(GBDB::runFile(self::strParam("path"), $params)); }

    // ######################################
    // # GBDB v2 ACTIONS                    #
    // ######################################

    /**
     * Gibt die aktive GBDB-Instanz zurück.
     *
     * @return void
     */
    private static function gbdbv2_instance(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        self::success([
            "instance" => GBDB::getInstance()
        ]);
    }

    /**
     * Gibt alle GBDB-Instanzen zurück.
     *
     * @return void
     */
    private static function gbdbv2_instances(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();

        self::success(GBDB::listInstances());
    }

    /**
     * Erstellt eine GBDB-Instanz.
     *
     * @return void
     */
    private static function gbdbv2_create_instance(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();

        $name = self::strParam("name");

        self::success([
            "created" => GBDB::createInstance($name)
        ]);
    }

    /**
     * Löscht eine GBDB-Instanz.
     *
     * @return void
     */
    private static function gbdbv2_delete_instance(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();

        $name = self::strParam("name");
        $force = self::boolParam("force", false);

        self::success([
            "deleted" => GBDB::deleteInstance($name, $force)
        ]);
    }

    /**
     * Gibt alle GBDB-Datenbanken zurück.
     *
     * @return void
     */
    private static function gbdbv2_databases(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        self::success(GBDB::listDBs());
    }

    /**
     * Erstellt eine GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdbv2_create_database(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");

        self::success([
            "created" => GBDB::createDatabase($database)
        ]);
    }

    /**
     * Löscht eine leere GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdbv2_delete_database(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");

        self::success([
            "deleted" => GBDB::deleteDatabase($database)
        ]);
    }

    /**
     * Löscht eine komplette GBDB-Datenbank.
     *
     * @return void
     */
    private static function gbdbv2_delete_all(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");

        self::success([
            "deleted" => GBDB::deleteAll($database)
        ]);
    }

    /**
     * Gibt alle Tabellen einer GBDB-Datenbank zurück.
     *
     * @return void
     */
    private static function gbdbv2_tables(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $descending = self::boolParam("descending", false);

        self::success(GBDB::listTables($database, $descending));
    }

    /**
     * Erstellt eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdbv2_create_table(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $cols = self::arrParam("cols");

        self::success([
            "created" => GBDB::createTable($database, $table, $cols)
        ]);
    }

    /**
     * Löscht eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdbv2_delete_table(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "deleted" => GBDB::deleteTable($database, $table)
        ]);
    }

    /**
     * Fügt einer GBDB-Tabelle eine Spalte hinzu.
     *
     * @return void
     */
    private static function gbdbv2_add_column(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $column = self::strParam("column");
        $default = self::val("default", "");

        self::success([
            "added" => GBDB::addColumn($database, $table, $column, $default)
        ]);
    }

    /**
     * Gibt die Keys einer GBDB-Tabelle zurück.
     *
     * @return void
     */
    private static function gbdbv2_schema(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success(GBDB::getKeys($database, $table));
    }

    /**
     * Gibt Daten aus einer GBDB-Tabelle zurück.
     *
     * @return void
     */
    private static function gbdbv2_data(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $filter = self::boolParam("filter", false);

        if ($filter) {
            [$where, $is] = self::whereParams();
            self::success(GBDB::getData($database, $table, true, $where, $is));
        }

        self::success(GBDB::getData($database, $table));
    }

    /**
     * Gibt eine einzelne GBDB-Zeile zurück.
     *
     * @return void
     */
    private static function gbdbv2_row(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success(GBDB::getData($database, $table, true, $where, $is));
    }

    /**
     * Prüft, ob ein GBDB-Element existiert.
     *
     * @return void
     */
    private static function gbdbv2_exists(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success([
            "exists" => GBDB::elementExists($database, $table, $where, $is)
        ]);
    }

    /**
     * Fügt GBDB-Daten ein.
     *
     * @return void
     */
    private static function gbdbv2_insert(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $data = self::arrParam("data");

        $id = GBDB::insertData($database, $table, $data);

        self::success([
            "inserted" => $id > 0,
            "id" => $id
        ]);
    }

    /**
     * Bearbeitet GBDB-Daten.
     *
     * @return void
     */
    private static function gbdbv2_update(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");
        $data = self::arrParam("data");

        [$where, $is] = self::whereParams();

        self::success([
            "updated" => GBDB::editData($database, $table, $where, $is, $data)
        ]);
    }

    /**
     * Löscht GBDB-Daten.
     *
     * @return void
     */
    private static function gbdbv2_delete(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        [$where, $is] = self::whereParams();

        self::success([
            "deleted" => GBDB::deleteData($database, $table, $where, $is)
        ]);
    }

    /**
     * Gibt die nächste GBDB-ID zurück.
     *
     * @return void
     */
    private static function gbdbv2_next_id(): void {
        self::requireGbdbAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "next_id" => GBDB::nextID($database, $table)
        ]);
    }

    /**
     * Komprimiert eine GBDB-Tabelle.
     *
     * @return void
     */
    private static function gbdbv2_compact(): void {
        self::requireGbdbWriteAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $database = self::strParam("database");
        $table = self::strParam("table");

        self::success([
            "compacted" => GBDB::compactTable($database, $table)
        ]);
    }

    /**
     * Führt eine GreenQL-Abfrage aus.
     *
     * @return void
     */
    private static function gbdbv2_query(): void {
        self::requireGreenqlAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $query = self::strParam("query");
        $ctx = self::val("ctx", []);
        $params = self::val("params", []);

        if (!is_array($ctx)) {
            self::error("Parameter \"ctx\" must be an array.", 403);
        }

        if (!is_array($params)) {
            self::error("Parameter \"params\" must be an array.", 403);
        }

        self::success(GBDB::query($query, $ctx, $params));
    }

    /**
     * Führt ein GreenQL-Script aus.
     *
     * @return void
     */
    private static function gbdbv2_run_script(): void {
        self::requireGreenqlAccess();
        self::requireGbdbv2();
        self::applyGbdbv2Instance();

        $path = self::strParam("path");
        $ctx = self::val("ctx", []);
        $params = self::val("params", []);

        if (!is_array($ctx)) {
            self::error("Parameter \"ctx\" must be an array.", 403);
        }

        if (!is_array($params)) {
            self::error("Parameter \"params\" must be an array.", 403);
        }

        self::success(GBDB::runScript($path, $params, $ctx));
    }
}

?>
