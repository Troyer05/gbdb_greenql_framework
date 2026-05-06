<?php

class SrvP {
    private static array $ctx = [];

    /**
     * Ermittelt den API-Endpunkt.
     * @return string Rückgabewert.
     */
    private static function endpoint(): string {
        $host = trim((string)Vars::srvp_ip());
        $host = preg_replace('#/+$#', '', $host) ?? $host;

        if (str_ends_with($host, "/backend.php")) {
            return (Vars::srvp_ssl() ? "https://" : "http://") . $host;
        }

        return (Vars::srvp_ssl() ? "https://" : "http://") . $host . "/backend.php";
    }

    /**
     * Setzt den Remote-Kontext, z.B. GBDB Instanz.
     * @param array $ctx Übergabewert.
     * @return void Rückgabewert.
     */
    public static function setContext(array $ctx): void {
        self::$ctx = $ctx;
    }

    /**
     * Setzt die Remote-GBDB-Instanz.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    public static function setInstance(string $instance): void {
        self::$ctx["instance"] = GreenQL::cleanName($instance);
    }

    /**
     * Gibt den aktuellen Remote-Kontext zurück.
     * @return array Rückgabewert.
     */
    public static function getContext(): array {
        return self::$ctx;
    }

    /**
     * Kombiniert globalen und lokalen Kontext.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    private static function ctx(array $ctx = []): array {
        return array_merge(self::$ctx, $ctx);
    }

    /**
     * Sendet eine Anfrage und verarbeitet die Antwort.
     * @param array $payload Übergabewert.
     * @return array Rückgabewert.
     */
    private static function request(array $payload): array {
        $resp = Http::post(
            self::endpoint(),
            $payload,
            ["Content-Type: application/json"]
        );

        if ($resp === false || $resp === null || $resp === "") {
            throw new Exception("Empty response from backend: " . self::endpoint());
        }

        if (is_array($resp)) {
            return $resp;
        }

        $decoded = json_decode((string)$resp, true);

        if (!is_array($decoded)) {
            throw new Exception("Invalid JSON response: " . $resp);
        }

        return $decoded;
    }

    /**
     * Gibt den data-Teil einer Backend-Antwort zurück.
     * @param array $resp Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function data(array $resp): mixed {
        return $resp["data"] ?? $resp;
    }

    /**
     * Verarbeitet die Funktion get token.
     * @return string Rückgabewert.
     */
    private static function getToken(): string {
        $resp = self::request([
            "sauth" => hash("sha256", Vars::srvp_static_key()),
            "do" => "gtoken"
        ]);

        if (!isset($resp["data"]) || $resp["data"] == "") {
            throw new Exception("Token not returned by backend");
        }

        return $resp["data"];
    }

    /**
     * Verarbeitet die Funktion payload with token.
     * @param array $body Übergabewert.
     * @return array Rückgabewert.
     */
    private static function payloadWithToken(array $body): array {
        $body["sauth"] = hash("sha256", Vars::srvp_static_key());
        $body["token"] = self::getToken();

        return $body;
    }

    /**
     * Fügt Kontext zur Nutzlast hinzu.
     * @param array $body Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    private static function payload(array $body, array $ctx = []): array {
        $ctx = self::ctx($ctx);

        if (!empty($ctx)) {
            $body["ctx"] = $ctx;

            if (!empty($ctx["instance"])) {
                $body["instance"] = $ctx["instance"];
            }
        }

        return self::payloadWithToken($body);
    }

    /**
     * Prüft den Backend-Treiber.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function driver(array $ctx = []): array {
        return self::request(self::payload(["do" => "driver"], $ctx));
    }

    /**
     * Listet Instanzen.
     * @return array Rückgabewert.
     */
    public static function listInstances(): array {
        return self::request(self::payload(["do" => "instances"]));
    }

    /**
     * Erstellt eine Instanz.
     * @param string $instance Übergabewert.
     * @return array Rückgabewert.
     */
    public static function createInstance(string $instance): array {
        return self::request(self::payload([
            "do" => "create_instance",
            "instance" => $instance
        ]));
    }

    /**
     * Löscht eine Instanz.
     * @param string $instance Übergabewert.
     * @param bool $force Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteInstance(string $instance, bool $force = false): array {
        return self::request(self::payload([
            "do" => "delete_instance",
            "instance" => $instance,
            "force" => $force
        ]));
    }

    /**
     * Listet Bases.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function listDBs(array $ctx = []): array {
        return self::request(self::payload(["do" => "bases"], $ctx));
    }

    /**
     * Listet Tabellen.
     * @param string $db Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function listTables(string $db, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "tables",
            "db" => $db
        ], $ctx));
    }

    /**
     * Erstellt eine Base.
     * @param string $db Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function createDatabase(string $db, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "create_base",
            "db" => $db
        ], $ctx));
    }

    /**
     * Löscht eine Base.
     * @param string $db Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteDatabase(string $db, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "delete_base",
            "db" => $db
        ], $ctx));
    }

    /**
     * Erstellt eine Tabelle.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function createTable(string $db, string $table, array $cols, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "create_table",
            "db" => $db,
            "table" => $table,
            "cols" => $cols
        ], $ctx));
    }

    /**
     * Löscht eine Tabelle.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteTable(string $db, string $table, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "delete_table",
            "db" => $db,
            "table" => $table
        ], $ctx));
    }

    /**
     * Liest Tabellenschlüssel.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getKeys(string $db, string $table, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "keys",
            "db" => $db,
            "table" => $table
        ], $ctx));
    }

    /**
     * Verarbeitet die Funktion get data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $filter Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getData(string $db, string $table, bool $filter = false, string $where = "", string $is = "", array $ctx = []): array {
        $body = [
            "do" => "get",
            "db" => $db,
            "table" => $table
        ];

        if ($filter) {
            $body["where"] = $where;
            $body["is"] = $is;
        }

        return self::request(self::payload($body, $ctx));
    }

    /**
     * Verarbeitet die Funktion add data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $data Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function addData(string $db, string $table, array $data, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "put",
            "db" => $db,
            "table" => $table,
            "data" => $data
        ], $ctx));
    }

    /**
     * Alias für addData.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $data Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function insertData(string $db, string $table, array $data, array $ctx = []): array {
        return self::addData($db, $table, $data, $ctx);
    }

    /**
     * Verarbeitet die Funktion delete data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteData(string $db, string $table, string $where, string $is, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "delete",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is
        ], $ctx));
    }

    /**
     * Verarbeitet die Funktion edit data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @param array $data Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editData(string $db, string $table, string $where, string $is, array $data, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "edit",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is,
            "data" => $data
        ], $ctx));
    }

    /**
     * Führt eine GreenQL-Abfrage aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function query(string $script, array $ctx = [], array $params = []): array {
        return self::request(self::payload([
            "do" => "query",
            "query" => $script,
            "params" => $params
        ], $ctx));
    }

    /**
     * Führt ein Script aus und gibt das Ergebnis zurück.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        return self::request(self::payload([
            "do" => "runscript",
            "path" => $path,
            "params" => $params
        ], $ctx));
    }

    public static function get(string $base, string $table, mixed $where = '', mixed $is = '', array $options = [], array $ctx = []): array { return self::request(self::payload(["do"=>"gbdb_get","base"=>$base,"table"=>$table,"where"=>$where,"is"=>$is,"options"=>$options], $ctx)); }
    public static function create(string $base, string $table = '', bool $useDataTypes = false, array|object $rows = [], array $ctx = []): array { return self::request(self::payload(["do"=>"gbdb_create","base"=>$base,"table"=>$table,"use_data_types"=>$useDataTypes,"rows"=>is_object($rows)?get_object_vars($rows):$rows], $ctx)); }
    public static function fullTextSearch(string $base, string $table, string $text, array $ctx = []): array { return self::request(self::payload(["do"=>"gbdb_full_text_search","base"=>$base,"table"=>$table,"text"=>$text], $ctx)); }
    public static function createBackup(string $path = '', array $ctx = []): array { return self::request(self::payload(["do"=>"gbdb_backup","path"=>$path], $ctx)); }
    public static function runFile(string $path, array $params = [], array $ctx = []): array { return self::request(self::payload(["do"=>"gbdb_run_file","path"=>$path,"params"=>$params], $ctx)); }

    /**
     * Initialisiert Auth auf dem Zielserver.
     * @return array Rückgabewert.
     */
    public static function auth_init(): array {
        return self::request(self::payload(["do" => "auth", "action" => "init"]));
    }

    /**
     * Meldet einen Benutzer über den Zielserver an.
     * @param string $username_or_email Übergabewert.
     * @param string $plain_text_password Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_login(string $username_or_email, string $plain_text_password): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "login",
            "username_or_email" => $username_or_email,
            "plain_text_password" => $plain_text_password
        ]));
    }

    /**
     * Prüft einen Auth-Token über den Zielserver.
     * @param string $jwt Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_token(string $jwt): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "token",
            "jwt" => $jwt
        ]));
    }

    /**
     * Meldet einen Benutzer remote ab.
     * @param string $jwt Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_logout(string $jwt): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "logout",
            "jwt" => $jwt
        ]));
    }

    /**
     * Prüft 2FA remote.
     * @param string $uid Übergabewert.
     * @param string $code Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_login2Fa(string $uid, string $code): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "login_2fa",
            "uid" => $uid,
            "code" => $code
        ]));
    }

    /**
     * Liest den aktuell authentifizierten Benutzer remote.
     * @param string $jwt Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_me(string $jwt): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "me",
            "jwt" => $jwt
        ]));
    }

    /**
     * Liest Auth-Daten über den Zielserver.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_get(string $table, string $where = "", string $is = ""): array {
        $body = [
            "do" => "auth",
            "action" => "get",
            "table" => $table
        ];

        if ($where != "") {
            $body["where"] = $where;
            $body["is"] = $is;
        }

        return self::request(self::payload($body));
    }

    /**
     * Liest einen Benutzer über den Zielserver.
     * @param string $uid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_user(string $uid): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "user",
            "uid" => $uid
        ]));
    }

    /**
     * Legt einen Benutzer über den Zielserver an.
     * @param array $user_data Übergabewert.
     * @param array $user_meta Übergabewert.
     * @param bool $is_this_register Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_newUser(array $user_data, array $user_meta = [], bool $is_this_register = false): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "new_user",
            "user_data" => $user_data,
            "user_meta" => $user_meta,
            "is_this_register" => $is_this_register
        ]));
    }

    /**
     * Bearbeitet einen Benutzer über den Zielserver.
     * @param string $uid Übergabewert.
     * @param array $user_data Übergabewert.
     * @param array $user_meta Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_editUser(string $uid, array $user_data, array $user_meta = []): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "edit_user",
            "uid" => $uid,
            "user_data" => $user_data,
            "user_meta" => $user_meta
        ]));
    }

    /**
     * Löscht Auth-Daten über den Zielserver.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_delete(string $table, string $where, string $is): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "delete",
            "table" => $table,
            "where" => $where,
            "is" => $is
        ]));
    }

    /**
     * Verifiziert eine E-Mail über den Zielserver.
     * @param string $token Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_verifyEmail(string $token): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "verify_email",
            "token" => $token
        ]));
    }

    /**
     * Verifiziert einen 2FA-Code über den Zielserver.
     * @param string $code Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_verify2FaCode(string $code): array {
        return self::request(self::payload([
            "do" => "auth",
            "action" => "verify_2fa",
            "code" => $code
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_enqueue.
     * @param string $service Übergabewert.
     * @param string $action Übergabewert.
     * @param array $payload Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_enqueue(string $service, string $action, array $payload = [], array $ctx = []): array {
        return self::request(self::payload([
            "do" => "srv_enqueue",
            "service" => $service,
            "action" => $action,
            "payload" => $payload
        ], $ctx));
    }

    /**
     * Verarbeitet die Funktion srv_run_one.
     * @param int $id Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_run_one(int $id, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "srv_run_one",
            "id" => $id
        ], $ctx));
    }

    /**
     * Verarbeitet die Funktion srv_status.
     * @param int|null $id Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_status(?int $id = null, array $ctx = []): array {
        $body = ["do" => "srv_status"];

        if ($id !== null) {
            $body["id"] = $id;
        }

        return self::request(self::payload($body, $ctx));
    }

    /**
     * Verarbeitet die Funktion srv_logs.
     * @param int $job_id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_logs(int $job_id): array {
        return self::request(self::payload([
            "do" => "srv_logs",
            "job_id" => $job_id
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_jobs.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_jobs(array $ctx = []): array {
        return self::request(self::payload([
            "do" => "srv_jobs"
        ], $ctx));
    }

    /**
     * Prüft, ob eine Remote-Instanz existiert.
     * @param string $instance Instanzname.
     * @return array Backend-Antwort.
     */
    public static function instance_exists(string $instance): array {
        return self::request(self::payload([
            "do" => "instance_exists",
            "instance" => $instance
        ]));
    }

    /**
     * Prüft, ob eine Remote-Base existiert.
     * @param string $db Base.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function base_exists(string $db, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "base_exists",
            "db" => $db
        ], $ctx));
    }

    /**
     * Prüft, ob eine Remote-Tabelle existiert.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function table_exists(string $db, string $table, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "table_exists",
            "db" => $db,
            "table" => $table
        ], $ctx));
    }

    /**
     * Prüft, ob Remote-Daten anhand eines Filters existieren.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $where Spalte.
     * @param mixed $is Vergleichswert.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function data_exists(string $db, string $table, string $where, mixed $is, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "data_exists",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is
        ], $ctx));
    }

    /**
     * Liest Remote-Monitoring-Daten.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function monitor(string $db = "", string $table = "", array $ctx = []): array {
        $body = ["do" => "monitor"];
        if ($db !== "") $body["db"] = $db;
        if ($table !== "") $body["table"] = $table;
        return self::request(self::payload($body, $ctx));
    }

    /**
     * Führt Remote-Recovery für eine Tabelle aus.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function recover(string $db, string $table, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "recover",
            "db" => $db,
            "table" => $table
        ], $ctx));
    }

    /**
     * Lädt eine Remote-Page.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param int $page Seite ab 1.
     * @param int $size Seitengröße.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function page(string $db, string $table, int $page = 1, int $size = 50, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "page",
            "db" => $db,
            "table" => $table,
            "page" => $page,
            "size" => $size
        ], $ctx));
    }

    /**
     * Lädt einen Remote-Cursor.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param int $size Chunk-Größe.
     * @param string|null $cursor Cursor-Token.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function cursor(string $db, string $table, int $size = 100, ?string $cursor = null, array $ctx = []): array {
        $body = [
            "do" => "cursor",
            "db" => $db,
            "table" => $table,
            "size" => $size
        ];
        if ($cursor !== null && $cursor !== "") $body["cursor"] = $cursor;
        return self::request(self::payload($body, $ctx));
    }

    /**
     * Führt Remote-Volltextsuche aus.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $query Suchtext.
     * @param array $columns Spaltenfilter.
     * @param int $limit Maximale Treffer.
     * @param array $ctx Kontext.
     * @return array Backend-Antwort.
     */
    public static function fulltext_search(string $db, string $table, string $query, array $columns = [], int $limit = 50, array $ctx = []): array {
        return self::request(self::payload([
            "do" => "fulltext_search",
            "db" => $db,
            "table" => $table,
            "query" => $query,
            "columns" => $columns,
            "limit" => $limit
        ], $ctx));
    }

}
