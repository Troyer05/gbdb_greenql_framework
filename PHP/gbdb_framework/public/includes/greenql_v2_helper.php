<?php

declare(strict_types=1);

class GreenQLUIv2Helper {
    private static ?array $requestUserCache = null;
    private static bool $authStoreReady = false;

    private const SYSTEM_INSTANCE = "__greenql_ui_v2_system";
    private const HIDDEN_INSTANCES = ["default", "__greenql_ui_v2_system", "greenqluiv2system", "__public_api"];
    private const SYSTEM_DB = "system";
    private const USERS_TABLE = "users";
    private const PAPI_SYS_DB = "papi";
    private const PAPI_LOG_DB = "plog";
    private const PAPI_KEYS_TABLE = "keys";
    private const PAPI_FETCH_LOG_TABLE = "fetches";
    private const PAPI_KEY_LOG_TABLE = "key_log";

    public static function boot(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION["gqlui_v2_csrf"])) {
            $_SESSION["gqlui_v2_csrf"] = bin2hex(random_bytes(24));
        }

        self::ensureAuthStore();
    }

    public static function csrf(): string {
        return (string) ($_SESSION["gqlui_v2_csrf"] ?? "");
    }

    public static function checkCsrf(string $token): bool {
        return hash_equals(self::csrf(), $token);
    }

    public static function systemInstance(): string {
        return self::SYSTEM_INSTANCE;
    }

    private static function inSystem(callable $fn): mixed {
        $old = class_exists("GBDB") ? GBDB::getInstance() : "default";

        try {
            GBDB::setInstance(self::SYSTEM_INSTANCE);

            return $fn();
        } finally {
            if (class_exists("GBDB")) {
                GBDB::setInstance($old);
            }

        }

    }

    private static function ensureAuthStore(): void {
        if (self::$authStoreReady || !class_exists("GBDB")) {
            return;
        }

        self::inSystem(function () {
            if (!in_array(self::SYSTEM_DB, GBDB::listDBs(), true)) {
                GBDB::createDatabase(self::SYSTEM_DB);
            }

            if (!in_array(self::USERS_TABLE, GBDB::listTables(self::SYSTEM_DB), true)) {
                GBDB::createTable(self::SYSTEM_DB, self::USERS_TABLE, [
                    "uid",
                    "username",
                    "password",
                    "role",
                    "active",
                    "instances",
                    "bases",
                    "created_at",
                    "last_login",
                    "language",
                    "permissions",
                    "tools"
                ]);

                GBDB::createTable(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE, [
                    "key",
                    "name",
                    "created_at",
                    "created_by",
                    "active",
                    "exp",
                    "rights"
                ]);

                GBDB::createTable(self::PAPI_LOG_DB, self::PAPI_FETCH_LOG_TABLE, [
                    "key",
                    "ip",
                    "datetime",
                    "action"
                ]);

                GBDB::createTable(self::PAPI_LOG_DB, self::PAPI_KEY_LOG_TABLE, [
                    "key",
                    "user",
                    "action",
                    "rights"
                ]);

                return;
            }

            $need = [
                "active" => "1",
                "instances" => "*",
                "bases" => "*",
                "language" => "en",
                "permissions" => "",
                "tools" => ""
            ];

            foreach ($need as $col => $default) {
                if (!in_array($col, GBDB::getKeys(self::SYSTEM_DB, self::USERS_TABLE), true)) {
                    GBDB::addColumn(self::SYSTEM_DB, self::USERS_TABLE, $col, $default);
                }

            }

            $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);

            if (is_array($users)) {
                foreach ($users as $user) {
                    if (!is_array($user) || (int) ($user["id"] ?? 0) < 1)
                        continue;
                    $fix = [];

                    foreach ($need as $col => $default) {
                        if (!array_key_exists($col, $user) || (string) ($user[$col] ?? "") === "-header-")
                            $fix[$col] = $default;
                    }

                    if ($fix)
                        GBDB::editData(self::SYSTEM_DB, self::USERS_TABLE, "id", (int) $user["id"], $fix);
                }

            }

        });

        self::ensurePublicApiStore();

        self::$authStoreReady = true;
    }


    private static function ensurePublicApiStore(): void {
        self::inSystem(function () {
            if (!in_array(self::PAPI_SYS_DB, GBDB::listDBs(), true)) {
                GBDB::createDatabase(self::PAPI_SYS_DB);
            }

            if (!in_array(self::PAPI_LOG_DB, GBDB::listDBs(), true)) {
                GBDB::createDatabase(self::PAPI_LOG_DB);
            }

            if (!in_array(self::PAPI_KEYS_TABLE, GBDB::listTables(self::PAPI_SYS_DB), true)) {
                GBDB::createTable(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE, [
                    "key",
                    "name",
                    "created_at",
                    "created_by",
                    "active",
                    "exp",
                    "rights"
                ]);
            }

            if (!in_array(self::PAPI_FETCH_LOG_TABLE, GBDB::listTables(self::PAPI_LOG_DB), true)) {
                GBDB::createTable(self::PAPI_LOG_DB, self::PAPI_FETCH_LOG_TABLE, [
                    "key",
                    "ip",
                    "datetime",
                    "action"
                ]);
            }

            if (!in_array(self::PAPI_KEY_LOG_TABLE, GBDB::listTables(self::PAPI_LOG_DB), true)) {
                GBDB::createTable(self::PAPI_LOG_DB, self::PAPI_KEY_LOG_TABLE, [
                    "key",
                    "user",
                    "datetime",
                    "action"
                ]);
            }

            $need = [
                self::PAPI_KEYS_TABLE => ["key", "name", "created_at", "created_by", "active", "exp", "rights"],
            ];

            foreach ($need as $table => $columns) {
                $keys = GBDB::getKeys(self::PAPI_SYS_DB, $table);

                foreach ($columns as $column) {
                    if (!in_array($column, $keys, true)) {
                        GBDB::addColumn(self::PAPI_SYS_DB, $table, $column, "");
                    }
                }
            }

            $logNeed = [
                self::PAPI_FETCH_LOG_TABLE => ["key", "ip", "datetime", "action"],
                self::PAPI_KEY_LOG_TABLE => ["key", "user", "datetime", "action"]
            ];

            foreach ($logNeed as $table => $columns) {
                $keys = GBDB::getKeys(self::PAPI_LOG_DB, $table);

                foreach ($columns as $column) {
                    if (!in_array($column, $keys, true)) {
                        GBDB::addColumn(self::PAPI_LOG_DB, $table, $column, "");
                    }
                }
            }
        });
    }

    /**
     * returns public api endpoint path guessed from current ui entry
     *
     * @return string
     */
    public static function publicApiEndpoint(): string {
        $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $host = (string) ($_SERVER["HTTP_HOST"] ?? "localhost");
        $script = (string) ($_SERVER["SCRIPT_NAME"] ?? "/gbdb_ui.php");
        $path = preg_replace('/gbdb_ui\.php$/', 'public_api.php', $script) ?: "/public_api.php";

        return $scheme . "://" . $host . $path;
    }

    /**
     * returns public api keys
     *
     * @return array
     */
    public static function publicApiKeys(): array {
        return (array) self::inSystem(function () {
            self::ensurePublicApiStore();
            $rows = GBDB::getData(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE);

            return is_array($rows) ? array_values($rows) : [];
        });
    }

    /**
     * creates public api key
     *
     * @param string $name
     * @param string $rights
     * @param string $exp
     * @param string $active
     * @return string
     */
    public static function createPublicApiKey(string $name, string $rights, string $exp = "", string $active = "1"): string {
        $name = trim($name);
        $key = "gbdb_" . bin2hex(random_bytes(24));
        $rights = self::normalizePublicApiRights($rights);
        $exp = self::normalizeDateTime($exp);
        $active = $active === "1" ? "1" : "0";
        $user = self::user();

        if ($name === "") {
            $name = "api-key-" . date("Ymd-His");
        }

        self::inSystem(function () use ($key, $name, $rights, $exp, $active, $user): void {
            self::ensurePublicApiStore();
            GBDB::insertData(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE, [
                "key" => $key,
                "name" => $name,
                "created_at" => date("Y-m-d H:i:s"),
                "created_by" => (string) ($user["uid"] ?? ""),
                "active" => $active,
                "exp" => $exp,
                "rights" => $rights
            ]);
        });

        return $key;
    }

    /**
     * updates public api key metadata
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updatePublicApiKey(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        return (bool) self::inSystem(function () use ($id, $data) {
            self::ensurePublicApiStore();
            $set = [];

            if (isset($data["name"])) {
                $set["name"] = trim((string) $data["name"]);
            }

            if (isset($data["rights"])) {
                $set["rights"] = self::normalizePublicApiRights((string) $data["rights"]);
            }

            if (isset($data["exp"])) {
                $set["exp"] = self::normalizeDateTime((string) $data["exp"]);
            }

            if (isset($data["active"])) {
                $set["active"] = (string) $data["active"] === "1" ? "1" : "0";
            }

            if (empty($set)) {
                return false;
            }

            return GBDB::editData(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE, "id", $id, $set);
        });
    }

    /**
     * deletes public api key
     *
     * @param int $id
     * @return bool
     */
    public static function deletePublicApiKey(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        return (bool) self::inSystem(function () use ($id) {
            self::ensurePublicApiStore();

            return GBDB::deleteData(self::PAPI_SYS_DB, self::PAPI_KEYS_TABLE, "id", $id);
        });
    }

    /**
     * masks public api key for display
     *
     * @param string $key
     * @return string
     */
    public static function maskPublicApiKey(string $key): string {
        if (strlen($key) <= 14) {
            return str_repeat("•", max(6, strlen($key)));
        }

        return substr($key, 0, 9) . "…" . substr($key, -6);
    }

    /**
     * returns public api logs
     *
     * @param string $table
     * @param int $limit
     * @return array
     */
    public static function publicApiLogs(string $table, int $limit = 50): array {
        $table = $table === self::PAPI_KEY_LOG_TABLE ? self::PAPI_KEY_LOG_TABLE : self::PAPI_FETCH_LOG_TABLE;
        $limit = max(1, min(300, $limit));

        return (array) self::inSystem(function () use ($table, $limit) {
            self::ensurePublicApiStore();
            $rows = GBDB::getData(self::PAPI_LOG_DB, $table);

            if (!is_array($rows)) {
                return [];
            }

            $rows = array_values($rows);
            usort($rows, function ($a, $b): int {
                return (int) ($b["id"] ?? 0) <=> (int) ($a["id"] ?? 0);
            });

            return array_slice($rows, 0, $limit);
        });
    }

    /**
     * returns available public api modules and actions
     *
     * @return array
     */
    public static function publicApiModules(): array {
        $apiDir = __DIR__ . "/public_api";
        $files = [];

        if (is_file($apiDir . "/gbdb.php")) {
            $files[] = $apiDir . "/gbdb.php";
        }

        $moduleDir = $apiDir . "/public_api_modules";

        if (is_dir($moduleDir)) {
            foreach ((array) scandir($moduleDir) as $file) {
                if ($file === "." || $file === ".." || !str_ends_with($file, ".php")) {
                    continue;
                }

                $files[] = $moduleDir . "/" . $file;
            }
        }

        if (is_file($apiDir . "/module_helper.php")) {
            require_once $apiDir . "/module_helper.php";
        }

        $modules = [];

        foreach ($files as $file) {
            $before = get_declared_classes();
            require_once $file;
            $after = get_declared_classes();
            $classes = array_diff($after, $before);

            foreach ($classes as $class) {
                if (!str_starts_with($class, "PublicAPI_") || !method_exists($class, "name") || !method_exists($class, "requiresRights")) {
                    continue;
                }

                $name = (string) $class::name();
                $allRights = $class::requiresRights();
                $actions = [];

                try {
                    $ref = new ReflectionClass($class);
                    if ($ref->hasConstant("ACTIONS")) {
                        $const = $ref->getConstant("ACTIONS");

                        if (is_array($const)) {
                            foreach ($const as $action => $meta) {
                                $actions[(string) $action] = is_array($meta) ? array_values((array) ($meta["rights"] ?? [])) : [];
                            }
                        }
                    }
                } catch (Throwable) {
                    $actions = [];
                }

                $modules[$name] = [
                    "name" => $name,
                    "class" => $class,
                    "file" => basename($file),
                    "rights" => is_array($allRights) ? array_values($allRights) : [],
                    "actions" => $actions
                ];
            }
        }

        ksort($modules);

        return array_values($modules);
    }

    /**
     * normalizes public api rights list
     *
     * @param string $rights
     * @return string
     */
    public static function normalizePublicApiRights(string $rights): string {
        $rights = trim($rights);

        if ($rights === "") {
            return "[]";
        }

        if ($rights === "*") {
            return json_encode(["*"], JSON_UNESCAPED_UNICODE) ?: "[]";
        }

        $decoded = json_decode($rights, true);

        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = preg_split('/[\s,]+/', $rights) ?: [];
        }

        $out = [];

        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item === "") {
                continue;
            }

            if ($item !== "*" && !preg_match('/^[a-zA-Z0-9_.*-]+$/', $item)) {
                continue;
            }

            if (!in_array($item, $out, true)) {
                $out[] = $item;
            }
        }

        return json_encode(array_values($out), JSON_UNESCAPED_UNICODE) ?: "[]";
    }

    private static function normalizeDateTime(string $value): string {
        $value = trim($value);

        if ($value === "") {
            return "";
        }

        $ts = strtotime($value);

        if ($ts === false) {
            return "";
        }

        return date("Y-m-d H:i:s", $ts);
    }

    public static function hasUsers(): bool {
        if (!class_exists("GBDB")) {
            return false;
        }

        return (bool) self::inSystem(function () {
            $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);

            return is_array($users) && count($users) > 0;
        });
    }

    public static function createUser(string $username, string $password, string $role = "admin", string $instances = "*", string $bases = "*", string $language = "en", string $permissions = "", string $tools = ""): bool {
        $username = self::clean($username);
        $role = self::normalizeRole($role);
        $instances = self::normalizeAccessValue($instances);
        $bases = self::normalizeAccessValue($bases);
        $language = self::normalizeLanguage($language);
        $permissions = self::normalizeAccessValue($permissions);
        $tools = self::normalizeAccessValue($tools);

        if ($username === "" || strlen($password) < 8) {
            return false;
        }

        return (bool) self::inSystem(function () use ($username, $password, $role, $instances, $bases, $language, $permissions, $tools) {
            if (GBDB::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username)) {
                return false;
            }

            $id = GBDB::insertData(self::SYSTEM_DB, self::USERS_TABLE, [
                "uid" => bin2hex(random_bytes(12)),
                "username" => $username,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role" => $role,
                "active" => "1",
                "instances" => $instances,
                "bases" => $bases,
                "language" => $language,
                "permissions" => $permissions,
                "tools" => $tools,
                "created_at" => date("d.m.Y H:i"),
                "last_login" => ""
            ]);

            if ((int) $id > 0) {
                return true;
            }

            return GBDB::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username);
        });
    }

    public static function updateUser(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        return (bool) self::inSystem(function () use ($id, $data) {
            $user = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "id", $id);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            $set = [];

            if (isset($data["username"])) {
                $username = self::clean((string) $data["username"]);

                if ($username === "") {
                    return false;
                }

                $sameName = $username === (string) ($user["username"] ?? "");
                $exists = !$sameName && GBDB::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username);

                if ($exists) {
                    return false;
                }

                $set["username"] = $username;
            }

            if (isset($data["role"])) {
                $set["role"] = self::normalizeRole((string) $data["role"]);
            }

            if (isset($data["active"])) {
                $set["active"] = ((string) $data["active"] === "1") ? "1" : "0";
            }

            if (isset($data["instances"])) {
                $set["instances"] = self::normalizeAccessValue((string) $data["instances"]);
            }

            if (isset($data["bases"]))
                $set["bases"] = self::normalizeAccessValue((string) $data["bases"]);

            if (isset($data["language"]))
                $set["language"] = self::normalizeLanguage((string) $data["language"]);

            if (isset($data["permissions"]))
                $set["permissions"] = self::normalizeAccessValue((string) $data["permissions"]);

            if (isset($data["tools"]))
                $set["tools"] = self::normalizeAccessValue((string) $data["tools"]);

            if (!empty((string) ($data["password"] ?? ""))) {
                if (strlen((string) $data["password"]) < 8) {
                    return false;
                }

                $set["password"] = password_hash((string) $data["password"], PASSWORD_DEFAULT);
            }

            if (empty($set)) {
                return false;
            }

            if (!self::changeKeepsAdminSafety($id, $user, $set)) {
                return false;
            }

            $ok = GBDB::editData(self::SYSTEM_DB, self::USERS_TABLE, "id", $id, $set);

            if ($ok && (int) (self::user()["id"] ?? 0) === $id) {
                $fresh = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "id", $id);

                if (is_array($fresh) && !empty($fresh)) {
                    $_SESSION["gqlui_v2_user"] = self::publicUser($fresh);
                    $_SESSION["gqlui_v2_user_checked_at"] = time();
                    self::$requestUserCache = $_SESSION["gqlui_v2_user"];
                }

            }

            return $ok;
        });
    }

    public static function resetPassword(int $id, string $password): bool {
        return self::updateUser($id, ["password" => $password]);
    }

    private static function changeKeepsAdminSafety(int $id, array $oldUser, array $set): bool {
        $oldIsAdmin = (string) ($oldUser["role"] ?? "") === "admin" && (string) ($oldUser["active"] ?? "1") === "1";

        if (!$oldIsAdmin) {
            return true;
        }

        $newRole = (string) ($set["role"] ?? $oldUser["role"] ?? "viewer");
        $newActive = (string) ($set["active"] ?? $oldUser["active"] ?? "1");

        if ($newRole === "admin" && $newActive === "1") {
            return true;
        }

        $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);

        if (!is_array($users)) {
            return false;
        }

        foreach ($users as $user) {
            if (!is_array($user) || (int) ($user["id"] ?? 0) === $id) {
                continue;
            }

            if ((string) ($user["role"] ?? "") === "admin" && (string) ($user["active"] ?? "1") === "1") {
                return true;
            }

        }

        return false;
    }

    public static function deleteUser(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        $me = self::user();

        return (bool) self::inSystem(function () use ($id, $me) {
            $user = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "id", $id);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            if (($user["uid"] ?? "") === ($me["uid"] ?? "")) {
                return false;
            }

            if (!self::changeKeepsAdminSafety($id, $user, ["active" => "0"])) {
                return false;
            }

            return GBDB::deleteData(self::SYSTEM_DB, self::USERS_TABLE, "id", $id);
        });
    }

    public static function login(string $username, string $password): bool {
        $username = self::clean($username);

        if ($username === "" || $password === "") {
            return false;
        }

        return (bool) self::inSystem(function () use ($username, $password) {
            $user = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "username", $username);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            if ((string) ($user["active"] ?? "1") !== "1") {
                return false;
            }

            if (!password_verify($password, (string) ($user["password"] ?? ""))) {
                return false;
            }

            $_SESSION["gqlui_v2_user"] = self::publicUser($user);
            $_SESSION["gqlui_v2_user_checked_at"] = time();
            self::$requestUserCache = $_SESSION["gqlui_v2_user"];

            GBDB::editData(self::SYSTEM_DB, self::USERS_TABLE, "username", $username, [
                "last_login" => date("d.m.Y H:i")
            ]);

            return true;
        });
    }

    public static function logout(): void {
        unset($_SESSION["gqlui_v2_user"], $_SESSION["gqlui_v2_user_checked_at"]);
        self::$requestUserCache = [];
    }

    public static function user(): array {
        $user = $_SESSION["gqlui_v2_user"] ?? [];

        return is_array($user) ? $user : [];
    }

    public static function freshUser(): array {
        if (self::$requestUserCache !== null) {
            return self::$requestUserCache;
        }

        $current = self::user();
        $uid = (string) ($current["uid"] ?? "");

        if ($uid === "") {
            self::$requestUserCache = [];

            return [];
        }

        $lastCheck = (int) ($_SESSION["gqlui_v2_user_checked_at"] ?? 0);

        if ($lastCheck > 0 && (time() - $lastCheck) < 8 && !empty($current)) {
            self::$requestUserCache = $current;

            return self::$requestUserCache;
        }

        $user = self::inSystem(function () use ($uid) {
            return GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "uid", $uid);
        });

        if (!is_array($user) || empty($user) || (string) ($user["active"] ?? "1") !== "1") {
            self::logout();
            self::$requestUserCache = [];

            return [];
        }

        $_SESSION["gqlui_v2_user"] = self::publicUser($user);
        $_SESSION["gqlui_v2_user_checked_at"] = time();
        self::$requestUserCache = $_SESSION["gqlui_v2_user"];

        return self::$requestUserCache;
    }

    public static function loggedIn(): bool {
        return !empty(self::freshUser());
    }

    private static function publicUser(array $user): array {
        return [
            "id" => (int) ($user["id"] ?? 0),
            "uid" => (string) ($user["uid"] ?? ""),
            "username" => (string) ($user["username"] ?? ""),
            "role" => self::normalizeRole((string) ($user["role"] ?? "viewer")),
            "active" => (string) ($user["active"] ?? "1"),
            "instances" => self::normalizeAccessValue((string) ($user["instances"] ?? "*")),
            "bases" => self::normalizeAccessValue((string) ($user["bases"] ?? "*")),
            "language" => self::normalizeLanguage((string) ($user["language"] ?? ($_SESSION["gbdbui_lang"] ?? "en"))),
            "permissions" => self::normalizeAccessValue((string) ($user["permissions"] ?? "")),
            "tools" => self::normalizeAccessValue((string) ($user["tools"] ?? ""))
        ];
    }

    public static function users(): array {
        return (array) self::inSystem(function () {
            $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);

            if (!is_array($users)) {
                return [];
            }

            return array_map(function ($user) {
                if (is_array($user)) {
                    unset($user["password"]);
                }

                return $user;
            }, $users);
        });
    }

    public static function normalizeRole(string $role): string {
        $role = strtolower(self::clean($role));

        return in_array($role, ["admin", "maintainer", "editor", "viewer"], true) ? $role : "viewer";
    }

    public static function normalizeLanguage(string $language): string {
        $language = strtolower(trim($language));

        return in_array($language, ["de", "en", "fr", "es", "it", "nl"], true) ? $language : "en";
    }

    public static function setLanguage(string $language): void {
        $_SESSION["gbdbui_lang"] = self::normalizeLanguage($language);
    }

    public static function language(): string {
        $u = self::user();

        return self::normalizeLanguage((string) ($u["language"] ?? $_SESSION["gbdbui_lang"] ?? "en"));
    }

    public static function t(string $key): string {
        $d = ["de" => ["setup_text" => "Ersteinrichtung der zentralen Admin-Oberfläche.", "login_text" => "Einloggen, um die Framework-Tools zu nutzen.", "username" => "Benutzername", "password" => "Passwort", "password_repeat" => "Passwort wiederholen", "language" => "Sprache", "create_admin" => "Admin anlegen", "login" => "Einloggen"], "en" => ["setup_text" => "First-time setup for the central admin interface.", "login_text" => "Sign in to use the framework tools.", "username" => "Username", "password" => "Password", "password_repeat" => "Repeat password", "language" => "Language", "create_admin" => "Create admin", "login" => "Sign in"], "fr" => ["setup_text" => "Configuration initiale de l’interface admin.", "login_text" => "Connectez-vous pour utiliser les outils.", "username" => "Utilisateur", "password" => "Mot de passe", "password_repeat" => "Répéter le mot de passe", "language" => "Langue", "create_admin" => "Créer admin", "login" => "Connexion"], "es" => ["setup_text" => "Configuración inicial de la interfaz admin.", "login_text" => "Inicia sesión para usar las herramientas.", "username" => "Usuario", "password" => "Contraseña", "password_repeat" => "Repetir contraseña", "language" => "Idioma", "create_admin" => "Crear admin", "login" => "Entrar"], "it" => ["setup_text" => "Configurazione iniziale dell’interfaccia admin.", "login_text" => "Accedi per usare gli strumenti.", "username" => "Utente", "password" => "Password", "password_repeat" => "Ripeti password", "language" => "Lingua", "create_admin" => "Crea admin", "login" => "Accedi"], "nl" => ["setup_text" => "Eerste configuratie van de admininterface.", "login_text" => "Log in om de tools te gebruiken.", "username" => "Gebruiker", "password" => "Wachtwoord", "password_repeat" => "Wachtwoord herhalen", "language" => "Taal", "create_admin" => "Admin maken", "login" => "Inloggen"]];
        $lang = self::language();

        return (string) ($d[$lang][$key] ?? $d["en"][$key] ?? $key);
    }

    private static function normalizeAccessValue(string $value): string {
        $value = trim($value);

        if ($value === "-header-")

            return "";

        if ($value === "") {
            return "*";
        }

        if ($value === "*") {
            return "*";
        }

        $json = json_decode($value, true);

        if (is_array($json)) {
            return json_encode($json, JSON_UNESCAPED_UNICODE) ?: "*";
        }

        $items = array_values(array_filter(array_map(function ($item) {
            return self::clean(trim((string) $item));
        }, explode(",", $value))));

        return empty($items) ? "*" : implode(",", $items);
    }

    public static function isAdmin(): bool {
        return (string) (self::user()["role"] ?? "") === "admin";
    }

    public static function canWrite(): bool {
        return self::hasPermission("write") || in_array((string) (self::user()["role"] ?? ""), ["admin", "maintainer", "editor"], true);
    }

    public static function canStructure(): bool {
        return self::isAdmin() || self::hasPermission("structure") || (string) (self::user()["role"] ?? "") === "maintainer";
    }

    public static function hasPermission(string $permission, ?array $user = null): bool {
        $permission = self::clean(strtolower($permission));
        $user = $user ?? self::user();
        $role = (string) ($user["role"] ?? "viewer");

        if ($role === "admin")

            return true;
        $map = ["maintainer" => ["read", "write", "structure", "maintenance", "jobs", "backup"], "editor" => ["read", "write"], "viewer" => ["read"]];

        if (in_array($permission, $map[$role] ?? [], true))

            return true;
        $allowed = self::accessValue((string) ($user["permissions"] ?? ""));

        if ($allowed === "*")

            return true;

        return is_array($allowed) && in_array($permission, array_map("strval", $allowed), true);
    }

    public static function canUseTool(string $tool): bool {
        $tool = self::clean($tool);

        if ($tool === "dashboard" || $tool === "greenql_v2")

            return self::hasPermission("read");

        if (self::isAdmin())

            return true;
        $u = self::user();
        $allowed = self::accessValue((string) ($u["tools"] ?? ""));

        if ($allowed === "*")

            return true;

        if (is_array($allowed) && in_array($tool, array_map("strval", $allowed), true))

            return true;
        $perm = ["users" => "users", "public_api" => "users", "php_exec" => "dev", "env" => "config", "plugins" => "config", "migration" => "maintenance", "cryption" => "maintenance", "optimize" => "maintenance", "backup" => "backup", "import_export" => "backup", "enterprise" => "maintenance", "monitoring" => "jobs", "gql_scripts" => "write", "reinstall" => "danger"];

        return isset($perm[$tool]) && self::hasPermission($perm[$tool]);
    }

    public static function clean(string $value): string {
        if (class_exists("GreenQL")) {
            return GreenQL::cleanName($value);
        }

        $value = trim($value);

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value) ?? "";
    }

    private static function reservedSlug(string $value): string {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? "";
    }

    public static function reservedInstance(string $instance): bool {
        $instance = self::clean($instance);
        $slug = self::reservedSlug($instance);
        $systemSlug = self::reservedSlug(self::SYSTEM_INSTANCE);

        return in_array($instance, self::HIDDEN_INSTANCES, true)
            || $instance === self::SYSTEM_INSTANCE
            || $slug === $systemSlug
            || $slug === "default"
            || str_starts_with($slug, "greenqlui");
    }

    public static function reservedName(string $name): bool {
        $name = self::clean($name);

        return $name === "" || str_starts_with($name, "__greenql_ui") || $name === self::SYSTEM_DB && class_exists("GBDB") && GBDB::getInstance() === self::SYSTEM_INSTANCE;
    }

    private static function accessValue(string $value): mixed {
        $value = trim($value);

        if ($value === "-header-")

            return [];

        if ($value === "" || $value === "*") {
            return "*";
        }

        $json = json_decode($value, true);

        if (is_array($json)) {
            return $json;
        }

        return array_values(array_filter(array_map(function ($item) {
            return self::clean(trim((string) $item));
        }, explode(",", $value))));
    }

    public static function canAccessInstance(string $instance, ?array $user = null): bool {
        $instance = self::clean($instance);
        $user = $user ?? self::user();

        if ($instance === "" || self::reservedInstance($instance)) {
            return false;
        }

        if ((string) ($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string) ($user["instances"] ?? "*"));

        if ($allowed === "*") {
            return true;
        }

        return is_array($allowed) && in_array($instance, array_map("strval", $allowed), true);
    }

    public static function canAccessDb(string $instance, string $db, ?array $user = null): bool {
        $instance = self::clean($instance);
        $db = self::clean($db);
        $user = $user ?? self::user();

        if ($instance === "" || $db === "" || !self::canAccessInstance($instance, $user)) {
            return false;
        }

        if ((string) ($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string) ($user["bases"] ?? "*"));

        if ($allowed === "*") {
            return true;
        }

        if (is_array($allowed) && array_is_list($allowed)) {
            return in_array($db, array_map("strval", $allowed), true);
        }

        if (is_array($allowed) && isset($allowed[$instance])) {
            $item = $allowed[$instance];

            if ($item === "*") {
                return true;
            }

            if (is_array($item)) {
                return in_array($db, array_map("strval", $item), true);
            }

            return self::clean((string) $item) === $db;
        }

        return false;
    }

    public static function instances(): array {
        $items = class_exists("GBDB") ? GBDB::listInstances() : [];
        $user = self::user();

        return array_values(array_filter($items, function ($instance) use ($user) {
            $instance = (string) $instance;

            return !self::reservedInstance($instance) && self::canAccessInstance($instance, $user);
        }));
    }

    public static function databases(string $instance): array {
        $instance = self::clean($instance);

        if (!self::canAccessInstance($instance)) {
            return [];
        }

        $old = GBDB::getInstance();

        try {
            GBDB::setInstance($instance);
            $dbs = GBDB::listDBs();
        } finally {
            GBDB::setInstance($old);
        }

        return array_values(array_filter($dbs, function ($db) use ($instance) {
            return !self::reservedName((string) $db) && self::canAccessDb($instance, (string) $db);
        }));
    }

    public static function tables(string $instance, string $database): array {
        $instance = self::clean($instance);
        $database = self::clean($database);

        if (!self::canAccessDb($instance, $database)) {
            return [];
        }

        $old = GBDB::getInstance();

        try {
            GBDB::setInstance($instance);
            $tables = GBDB::listTables($database);
        } finally {
            GBDB::setInstance($old);
        }

        return array_values(array_filter($tables, function ($table) {
            return !self::reservedName((string) $table);
        }));
    }

    public static function parseParams(string $raw): array {
        $raw = trim($raw);

        if ($raw === "") {
            return [];
        }

        $json = json_decode($raw, true);

        if (is_array($json)) {
            return self::guardParams($json);
        }

        $params = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw);

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === "" || str_starts_with($line, "#")) {
                continue;
            }

            if (!str_contains($line, "=")) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key !== "") {
                $params[$key] = $value;
            }

        }

        return self::guardParams($params);
    }

    private static function guardParams(array $params): array {
        foreach ($params as $key => $value) {
            if (is_string($value) && self::containsReserved($value)) {
                unset($params[$key]);
            }

        }

        return $params;
    }

    private static function scriptRequiresInstance(string $script): bool {
        $pattern = '/(?:\b(?:USE\s+INSTANCE|ROOT\s+INSTANCE|GROW\s+INSTANCE|DROP\s+INSTANCE|SHOW\s+INSTANCES|ROOT|BRANCH|SHOW\s+BASES|SHOW\s+TABLES|GROW\s+BASE|DROP\s+BASE|GROW\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|DESCRIBE|PACK|PEEK|PICK|SEED|RESHAPE|ERASE)\b|\bEXISTS\s+(?:BASE|TABLE|DATA)\b|\b(?:base_exists|table_exists|data_exists|get_bases|bases|get_tables|tables|get_data|fetch_data|fetch|monitor|recover|page|cursor)\s*\()/i';

        return preg_match($pattern, $script) === 1;
    }

    public static function scriptAllowed(string $script, string $instance): array {
        if (self::containsReserved($script)) {
            return ["ok" => false, "message" => "Dieses Script enthält reservierte Systemnamen und wurde blockiert."];
        }

        if ($instance === "") {
            if (self::scriptRequiresInstance($script)) {
                return ["ok" => false, "message" => "Bitte wähle eine Instanz aus oder nutze USE INSTANCE <name>; im Script."];
            }

        } else if (!self::canAccessInstance($instance)) {
            return ["ok" => false, "message" => "Du hast keinen Zugriff auf diese Instanz."];
        }

        $role = (string) (self::user()["role"] ?? "viewer");

        if ($role === "viewer" && preg_match('/\b(GROW|DROP|ALTER|EDIT|SEED|RESHAPE|ERASE|DELETE|PACK)\b/i', $script)) {
            return ["ok" => false, "message" => "Viewer dürfen nur lesende Queries ausführen."];
        }

        if ($role === "editor" && preg_match('/\b(GROW\s+INSTANCE|DROP\s+INSTANCE|GROW\s+BASE|DROP\s+BASE|GROW\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|EDIT\s+TABLE)\b/i', $script)) {
            return ["ok" => false, "message" => "Editor dürfen Daten bearbeiten, aber keine Struktur ändern."];
        }

        if (!self::isAdmin()) {
            if (preg_match_all('/\bIN\s+([a-zA-Z0-9_\-]+)/i', $script, $matches)) {
                foreach ($matches[1] as $db) {
                    $db = self::clean((string) $db);

                    if ($db !== "" && !self::canAccessDb($instance, $db)) {
                        return ["ok" => false, "message" => "Kein Zugriff auf Base: " . $db];
                    }

                }

            }

        }

        return ["ok" => true, "message" => ""];
    }

    private static function containsReserved(string $value): bool {
        $low = strtolower($value);
        $slug = self::reservedSlug($value);

        return str_contains($low, strtolower(self::SYSTEM_INSTANCE))
            || str_contains($low, "__greenql_ui")
            || str_contains($slug, self::reservedSlug(self::SYSTEM_INSTANCE));
    }

    public static function readScriptPath(string $path): array {
        $path = trim($path);

        if ($path === "") {
            return ["ok" => false, "script" => "", "message" => "Kein Script-Pfad angegeben."];
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== "gql") {
            return ["ok" => false, "script" => "", "message" => "Es sind nur .gql Dateien erlaubt."];
        }

        $root = realpath(dirname(__DIR__, 6));
        $full = realpath($path);

        if ($full === false && $root !== false) {
            $full = realpath($root . "/" . ltrim($path, "/"));
        }

        if ($root === false || $full === false || !str_starts_with($full, $root)) {
            return ["ok" => false, "script" => "", "message" => "Script liegt nicht innerhalb des Projekts."];
        }

        $script = @file_get_contents($full);

        if ($script === false) {
            return ["ok" => false, "script" => "", "message" => "Script konnte nicht gelesen werden."];
        }

        return ["ok" => true, "script" => $script, "message" => "Script geladen: " . str_replace($root . "/", "", $full)];
    }

    private static function instanceFromScript(string $script): string {
        if (preg_match('/\bUSE\s+INSTANCE\s+([a-zA-Z0-9_\-]+)/i', $script, $m)) {
            return self::clean((string) $m[1]);
        }

        if (preg_match('/\bROOT\s+INSTANCE\s+([a-zA-Z0-9_\-]+)/i', $script, $m)) {
            return self::clean((string) $m[1]);
        }

        return "";
    }

    public static function runScript(string $script, string $instance, array $params = []): array {
        $instance = self::clean($instance);

        if ($instance === "") {
            $instance = self::instanceFromScript($script);
        }

        $allowed = self::scriptAllowed($script, $instance);

        if (empty($allowed["ok"])) {
            return self::errorResult((string) $allowed["message"]);
        }

        if ($instance !== "") {
            GBDB::setInstance($instance);
        }

        $ctx = $instance !== "" ? ["instance" => $instance] : [];
        $result = GreenQL::run($script, $ctx, $params);

        return self::filterResult($result);
    }

    private static function filterResult(array $result): array {
        if (isset($result["rows"]) && is_array($result["rows"])) {
            $result["rows"] = self::filterSystemRows($result["rows"]);
        }

        if (isset($result["results"]) && is_array($result["results"])) {
            foreach ($result["results"] as $i => $r) {
                if (isset($r["rows"]) && is_array($r["rows"])) {
                    $result["results"][$i]["rows"] = self::filterSystemRows($r["rows"]);
                }

            }

        }

        return $result;
    }

    private static function filterSystemRows(array $rows): array {
        return array_values(array_filter($rows, function ($row) {
            if (!is_array($row)) {
                return true;
            }

            foreach (["instance", "base", "table"] as $key) {
                if (isset($row[$key]) && self::containsReserved((string) $row[$key])) {
                    return false;
                }

            }

            return true;
        }));
    }

    public static function errorResult(string $message): array {
        return [
            "ok" => false,
            "messages" => [
                ["ok" => false, "text" => $message]
            ],
            "results" => [],
            "keys" => [],
            "rows" => [],
            "ctx" => [],
            "vars" => [],
            "refresh" => false
        ];
    }

    public static function e(mixed $value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }

}
