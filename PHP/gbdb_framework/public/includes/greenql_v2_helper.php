<?php

declare(strict_types=1);

class GreenQLUIv2Helper {
    private static ?array $requestUserCache = null;
    private static bool $authStoreReady = false;

    private const SYSTEM_INSTANCE = "__greenql_ui_v2_system";
    private const HIDDEN_INSTANCES = ["default", "__greenql_ui_v2_system", "greenqluiv2system"];
    private const SYSTEM_DB = "system";
    private const USERS_TABLE = "users";

    /**
     * startet die session und initialisiert das interne benutzersystem.
     * @return void Rückgabewert.
     */
    public static function boot(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION["gqlui_v2_csrf"])) {
            $_SESSION["gqlui_v2_csrf"] = bin2hex(random_bytes(24));
        }

        self::ensureAuthStore();
    }

    /**
     * gibt den csrf token zurück.
     * @return string Rückgabewert.
     */
    public static function csrf(): string {
        return (string)($_SESSION["gqlui_v2_csrf"] ?? "");
    }

    /**
     * prüft den csrf token.
     * @param string $token Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function checkCsrf(string $token): bool {
        return hash_equals(self::csrf(), $token);
    }

    /**
     * gibt den internen system-instanznamen zurück.
     * @return string Rückgabewert.
     */
    public static function systemInstance(): string {
        return self::SYSTEM_INSTANCE;
    }

    /**
     * führt eine aktion im system-store aus und stellt die alte instanz wieder her.
     * @param callable $fn Übergabewert.
     * @return mixed Rückgabewert.
     */
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

    /**
     * initialisiert die versteckte system-db.
     * @return void Rückgabewert.
     */
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
                    if (!is_array($user) || (int)($user["id"] ?? 0) < 1) continue;
                    $fix = [];
                    foreach ($need as $col => $default) {
                        if (!array_key_exists($col, $user) || (string)($user[$col] ?? "") === "-header-") $fix[$col] = $default;
                    }
                    if ($fix) GBDB::editData(self::SYSTEM_DB, self::USERS_TABLE, "id", (int)$user["id"], $fix);
                }
            }
        });

        self::$authStoreReady = true;
    }

    /**
     * prüft ob bereits benutzer existieren.
     * @return bool Rückgabewert.
     */
    public static function hasUsers(): bool {
        if (!class_exists("GBDB")) {
            return false;
        }

        return (bool)self::inSystem(function () {
            $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);
            return is_array($users) && count($users) > 0;
        });
    }

    /**
     * legt einen benutzer an.
     * @param string $username Übergabewert.
     * @param string $password Übergabewert.
     * @param string $role Übergabewert.
     * @param string $instances Übergabewert.
     * @param string $bases Übergabewert.
     * @return bool Rückgabewert.
     */
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

        return (bool)self::inSystem(function () use ($username, $password, $role, $instances, $bases, $language, $permissions, $tools) {
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

            if ((int)$id > 0) {
                return true;
            }

            return GBDB::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username);
        });
    }

    /**
     * aktualisiert einen benutzer.
     * @param int $id Übergabewert.
     * @param array $data Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function updateUser(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        return (bool)self::inSystem(function () use ($id, $data) {
            $user = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "id", $id);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            $set = [];

            if (isset($data["username"])) {
                $username = self::clean((string)$data["username"]);

                if ($username === "") {
                    return false;
                }

                $sameName = $username === (string)($user["username"] ?? "");
                $exists = !$sameName && GBDB::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username);

                if ($exists) {
                    return false;
                }

                $set["username"] = $username;
            }

            if (isset($data["role"])) {
                $set["role"] = self::normalizeRole((string)$data["role"]);
            }

            if (isset($data["active"])) {
                $set["active"] = ((string)$data["active"] === "1") ? "1" : "0";
            }

            if (isset($data["instances"])) {
                $set["instances"] = self::normalizeAccessValue((string)$data["instances"]);
            }

            if (isset($data["bases"])) $set["bases"] = self::normalizeAccessValue((string)$data["bases"]);
            if (isset($data["language"])) $set["language"] = self::normalizeLanguage((string)$data["language"]);
            if (isset($data["permissions"])) $set["permissions"] = self::normalizeAccessValue((string)$data["permissions"]);
            if (isset($data["tools"])) $set["tools"] = self::normalizeAccessValue((string)$data["tools"]);

            if (!empty((string)($data["password"] ?? ""))) {
                if (strlen((string)$data["password"]) < 8) {
                    return false;
                }

                $set["password"] = password_hash((string)$data["password"], PASSWORD_DEFAULT);
            }

            if (empty($set)) {
                return false;
            }

            if (!self::changeKeepsAdminSafety($id, $user, $set)) {
                return false;
            }

            $ok = GBDB::editData(self::SYSTEM_DB, self::USERS_TABLE, "id", $id, $set);

            if ($ok && (int)(self::user()["id"] ?? 0) === $id) {
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

    /**
     * setzt das passwort eines benutzers zurück.
     * @param int $id Übergabewert.
     * @param string $password Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function resetPassword(int $id, string $password): bool {
        return self::updateUser($id, ["password" => $password]);
    }

    /**
     * schützt davor, den letzten aktiven admin auszusperren.
     * @param int $id Übergabewert.
     * @param array $oldUser Übergabewert.
     * @param array $set Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function changeKeepsAdminSafety(int $id, array $oldUser, array $set): bool {
        $oldIsAdmin = (string)($oldUser["role"] ?? "") === "admin" && (string)($oldUser["active"] ?? "1") === "1";

        if (!$oldIsAdmin) {
            return true;
        }

        $newRole = (string)($set["role"] ?? $oldUser["role"] ?? "viewer");
        $newActive = (string)($set["active"] ?? $oldUser["active"] ?? "1");

        if ($newRole === "admin" && $newActive === "1") {
            return true;
        }

        $users = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE);

        if (!is_array($users)) {
            return false;
        }

        foreach ($users as $user) {
            if (!is_array($user) || (int)($user["id"] ?? 0) === $id) {
                continue;
            }

            if ((string)($user["role"] ?? "") === "admin" && (string)($user["active"] ?? "1") === "1") {
                return true;
            }
        }

        return false;
    }

    /**
     * löscht einen benutzer.
     * @param int $id Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteUser(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        $me = self::user();

        return (bool)self::inSystem(function () use ($id, $me) {
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

    /**
     * loggt einen benutzer ein.
     * @param string $username Übergabewert.
     * @param string $password Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function login(string $username, string $password): bool {
        $username = self::clean($username);

        if ($username === "" || $password === "") {
            return false;
        }

        return (bool)self::inSystem(function () use ($username, $password) {
            $user = GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "username", $username);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            if ((string)($user["active"] ?? "1") !== "1") {
                return false;
            }

            if (!password_verify($password, (string)($user["password"] ?? ""))) {
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

    /**
     * loggt den benutzer aus.
     * @return void Rückgabewert.
     */
    public static function logout(): void {
        unset($_SESSION["gqlui_v2_user"], $_SESSION["gqlui_v2_user_checked_at"]);
        self::$requestUserCache = [];
    }

    /**
     * gibt den aktuellen benutzer zurück.
     * @return array Rückgabewert.
     */
    public static function user(): array {
        $user = $_SESSION["gqlui_v2_user"] ?? [];
        return is_array($user) ? $user : [];
    }

    /**
     * lädt den aktuellen benutzer aus der datenbank neu.
     * @return array Rückgabewert.
     */
    public static function freshUser(): array {
        if (self::$requestUserCache !== null) {
            return self::$requestUserCache;
        }

        $current = self::user();
        $uid = (string)($current["uid"] ?? "");

        if ($uid === "") {
            self::$requestUserCache = [];
            return [];
        }

        $lastCheck = (int)($_SESSION["gqlui_v2_user_checked_at"] ?? 0);
        if ($lastCheck > 0 && (time() - $lastCheck) < 8 && !empty($current)) {
            self::$requestUserCache = $current;
            return self::$requestUserCache;
        }

        $user = self::inSystem(function () use ($uid) {
            return GBDB::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "uid", $uid);
        });

        if (!is_array($user) || empty($user) || (string)($user["active"] ?? "1") !== "1") {
            self::logout();
            self::$requestUserCache = [];
            return [];
        }

        $_SESSION["gqlui_v2_user"] = self::publicUser($user);
        $_SESSION["gqlui_v2_user_checked_at"] = time();
        self::$requestUserCache = $_SESSION["gqlui_v2_user"];
        return self::$requestUserCache;
    }

    /**
     * prüft ob ein benutzer eingeloggt ist.
     * @return bool Rückgabewert.
     */
    public static function loggedIn(): bool {
        return !empty(self::freshUser());
    }

    /**
     * entfernt sensible daten aus einem user-array.
     * @param array $user Übergabewert.
     * @return array Rückgabewert.
     */
    private static function publicUser(array $user): array {
        return [
            "id" => (int)($user["id"] ?? 0),
            "uid" => (string)($user["uid"] ?? ""),
            "username" => (string)($user["username"] ?? ""),
            "role" => self::normalizeRole((string)($user["role"] ?? "viewer")),
            "active" => (string)($user["active"] ?? "1"),
            "instances" => self::normalizeAccessValue((string)($user["instances"] ?? "*")),
            "bases" => self::normalizeAccessValue((string)($user["bases"] ?? "*")),
            "language" => self::normalizeLanguage((string)($user["language"] ?? ($_SESSION["gbdbui_lang"] ?? "en"))),
            "permissions" => self::normalizeAccessValue((string)($user["permissions"] ?? "")),
            "tools" => self::normalizeAccessValue((string)($user["tools"] ?? ""))
        ];
    }

    /**
     * gibt alle benutzer zurück.
     * @return array Rückgabewert.
     */
    public static function users(): array {
        return (array)self::inSystem(function () {
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

    /**
     * normalisiert rollen.
     * @param string $role Übergabewert.
     * @return string Rückgabewert.
     */
    public static function normalizeRole(string $role): string {
        $role = strtolower(self::clean($role));
        return in_array($role, ["admin", "maintainer", "editor", "viewer"], true) ? $role : "viewer";
    }

    public static function normalizeLanguage(string $language): string {
        $language = strtolower(trim($language));
        return in_array($language, ["de", "en", "fr", "es", "it", "nl"], true) ? $language : "en";
    }
    public static function setLanguage(string $language): void { $_SESSION["gbdbui_lang"] = self::normalizeLanguage($language); }
    public static function language(): string { $u=self::user(); return self::normalizeLanguage((string)($u["language"] ?? $_SESSION["gbdbui_lang"] ?? "en")); }
    public static function t(string $key): string {
        $d=["de"=>["setup_text"=>"Ersteinrichtung der zentralen Admin-Oberfläche.","login_text"=>"Einloggen, um die Framework-Tools zu nutzen.","username"=>"Benutzername","password"=>"Passwort","password_repeat"=>"Passwort wiederholen","language"=>"Sprache","create_admin"=>"Admin anlegen","login"=>"Einloggen"],"en"=>["setup_text"=>"First-time setup for the central admin interface.","login_text"=>"Sign in to use the framework tools.","username"=>"Username","password"=>"Password","password_repeat"=>"Repeat password","language"=>"Language","create_admin"=>"Create admin","login"=>"Sign in"],"fr"=>["setup_text"=>"Configuration initiale de l’interface admin.","login_text"=>"Connectez-vous pour utiliser les outils.","username"=>"Utilisateur","password"=>"Mot de passe","password_repeat"=>"Répéter le mot de passe","language"=>"Langue","create_admin"=>"Créer admin","login"=>"Connexion"],"es"=>["setup_text"=>"Configuración inicial de la interfaz admin.","login_text"=>"Inicia sesión para usar las herramientas.","username"=>"Usuario","password"=>"Contraseña","password_repeat"=>"Repetir contraseña","language"=>"Idioma","create_admin"=>"Crear admin","login"=>"Entrar"],"it"=>["setup_text"=>"Configurazione iniziale dell’interfaccia admin.","login_text"=>"Accedi per usare gli strumenti.","username"=>"Utente","password"=>"Password","password_repeat"=>"Ripeti password","language"=>"Lingua","create_admin"=>"Crea admin","login"=>"Accedi"],"nl"=>["setup_text"=>"Eerste configuratie van de admininterface.","login_text"=>"Log in om de tools te gebruiken.","username"=>"Gebruiker","password"=>"Wachtwoord","password_repeat"=>"Wachtwoord herhalen","language"=>"Taal","create_admin"=>"Admin maken","login"=>"Inloggen"]];
        $lang=self::language(); return (string)($d[$lang][$key] ?? $d["en"][$key] ?? $key);
    }

    /**
     * normalisiert access-angaben.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    private static function normalizeAccessValue(string $value): string {
        $value = trim($value);

        if ($value === "-header-") return "";
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
            return self::clean(trim((string)$item));
        }, explode(",", $value))));

        return empty($items) ? "*" : implode(",", $items);
    }

    /**
     * prüft ob der aktuelle benutzer admin ist.
     * @return bool Rückgabewert.
     */
    public static function isAdmin(): bool {
        return (string)(self::user()["role"] ?? "") === "admin";
    }

    /**
     * prüft ob der aktuelle benutzer schreiben darf.
     * @return bool Rückgabewert.
     */
    public static function canWrite(): bool {
        return self::hasPermission("write") || in_array((string)(self::user()["role"] ?? ""), ["admin", "maintainer", "editor"], true);
    }

    /**
     * prüft ob der aktuelle benutzer strukturen ändern darf.
     * @return bool Rückgabewert.
     */
    public static function canStructure(): bool {
        return self::isAdmin() || self::hasPermission("structure") || (string)(self::user()["role"] ?? "") === "maintainer";
    }

    public static function hasPermission(string $permission, ?array $user = null): bool {
        $permission = self::clean(strtolower($permission)); $user = $user ?? self::user(); $role=(string)($user["role"]??"viewer");
        if ($role === "admin") return true;
        $map=["maintainer"=>["read","write","structure","maintenance","jobs","backup"],"editor"=>["read","write"],"viewer"=>["read"]];
        if (in_array($permission, $map[$role] ?? [], true)) return true;
        $allowed=self::accessValue((string)($user["permissions"]??"")); if ($allowed==="*") return true;
        return is_array($allowed) && in_array($permission, array_map("strval", $allowed), true);
    }

    public static function canUseTool(string $tool): bool {
        $tool=self::clean($tool); if ($tool==="dashboard"||$tool==="greenql_v2") return self::hasPermission("read");
        if (self::isAdmin()) return true; $u=self::user(); $allowed=self::accessValue((string)($u["tools"]??""));
        if ($allowed==="*") return true; if (is_array($allowed)&&in_array($tool,array_map("strval",$allowed),true)) return true;
        $perm=["users"=>"users","php_exec"=>"dev","env"=>"config","plugins"=>"config","migration"=>"maintenance","cryption"=>"maintenance","optimize"=>"maintenance","backup"=>"backup","import_export"=>"backup","enterprise"=>"maintenance","monitoring"=>"jobs","gql_scripts"=>"write","reinstall"=>"danger"];
        return isset($perm[$tool]) && self::hasPermission($perm[$tool]);
    }

    /**
     * bereinigt einen namen.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    public static function clean(string $value): string {
        if (class_exists("GreenQL")) {
            return GreenQL::cleanName($value);
        }

        $value = trim($value);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value) ?? "";
    }

    /**
     * normalisiert reservierte namen für sichere vergleiche.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    private static function reservedSlug(string $value): string {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? "";
    }

    /**
     * prüft ob eine instanz reserviert ist.
     * @param string $instance Übergabewert.
     * @return bool Rückgabewert.
     */
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

    /**
     * prüft ob ein name reserviert ist.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function reservedName(string $name): bool {
        $name = self::clean($name);
        return $name === "" || str_starts_with($name, "__greenql_ui") || $name === self::SYSTEM_DB && class_exists("GBDB") && GBDB::getInstance() === self::SYSTEM_INSTANCE;
    }

    /**
     * parst eine access-liste.
     * @param string $value Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function accessValue(string $value): mixed {
        $value = trim($value);

        if ($value === "-header-") return [];
        if ($value === "" || $value === "*") {
            return "*";
        }

        $json = json_decode($value, true);

        if (is_array($json)) {
            return $json;
        }

        return array_values(array_filter(array_map(function ($item) {
            return self::clean(trim((string)$item));
        }, explode(",", $value))));
    }

    /**
     * prüft ob ein user eine instanz sehen darf.
     * @param string $instance Übergabewert.
     * @param ?array $user Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function canAccessInstance(string $instance, ?array $user = null): bool {
        $instance = self::clean($instance);
        $user = $user ?? self::user();

        if ($instance === "" || self::reservedInstance($instance)) {
            return false;
        }

        if ((string)($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string)($user["instances"] ?? "*"));

        if ($allowed === "*") {
            return true;
        }

        return is_array($allowed) && in_array($instance, array_map("strval", $allowed), true);
    }

    /**
     * prüft ob ein user eine base sehen darf.
     * @param string $instance Übergabewert.
     * @param string $db Übergabewert.
     * @param ?array $user Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function canAccessDb(string $instance, string $db, ?array $user = null): bool {
        $instance = self::clean($instance);
        $db = self::clean($db);
        $user = $user ?? self::user();

        if ($instance === "" || $db === "" || !self::canAccessInstance($instance, $user)) {
            return false;
        }

        if ((string)($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string)($user["bases"] ?? "*"));

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

            return self::clean((string)$item) === $db;
        }

        return false;
    }

    /**
     * gibt sichtbare instanzen zurück.
     * @return array Rückgabewert.
     */
    public static function instances(): array {
        $items = class_exists("GBDB") ? GBDB::listInstances() : [];
        $user = self::user();

        return array_values(array_filter($items, function ($instance) use ($user) {
            $instance = (string)$instance;
            return !self::reservedInstance($instance) && self::canAccessInstance($instance, $user);
        }));
    }

    /**
     * gibt sichtbare datenbanken zurück.
     * @param string $instance Übergabewert.
     * @return array Rückgabewert.
     */
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
            return !self::reservedName((string)$db) && self::canAccessDb($instance, (string)$db);
        }));
    }

    /**
     * gibt sichtbare tabellen zurück.
     * @param string $instance Übergabewert.
     * @param string $database Übergabewert.
     * @return array Rückgabewert.
     */
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
            return !self::reservedName((string)$table);
        }));
    }

    /**
     * parst parameter aus json oder key=value zeilen.
     * @param string $raw Übergabewert.
     * @return array Rückgabewert.
     */
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
            $line = trim((string)$line);

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

    /**
     * blockiert reservierte werte in parametern.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    private static function guardParams(array $params): array {
        foreach ($params as $key => $value) {
            if (is_string($value) && self::containsReserved($value)) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Prüft, ob ein Script zwingend eine GBDB-Instanz braucht.
     * Reine Runtime-Scripte mit F/CALL/OUTPUT/BACK dürfen ohne Instanz laufen.
     * @param string $script Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function scriptRequiresInstance(string $script): bool {
        $pattern = '/(?:\b(?:USE\s+INSTANCE|ROOT\s+INSTANCE|GROW\s+INSTANCE|DROP\s+INSTANCE|SHOW\s+INSTANCES|ROOT|BRANCH|SHOW\s+BASES|SHOW\s+TABLES|GROW\s+BASE|DROP\s+BASE|GROW\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|DESCRIBE|PACK|PEEK|PICK|SEED|RESHAPE|ERASE)\b|\bEXISTS\s+(?:BASE|TABLE|DATA)\b|\b(?:base_exists|table_exists|data_exists|get_bases|bases|get_tables|tables|get_data|fetch_data|fetch|monitor|recover|page|cursor)\s*\()/i';
        return preg_match($pattern, $script) === 1;
    }

    /**
     * prüft ob ein script vom aktuellen user ausgeführt werden darf.
     * @param string $script Übergabewert.
     * @param string $instance Übergabewert.
     * @return array Rückgabewert.
     */
    public static function scriptAllowed(string $script, string $instance): array {
        if (self::containsReserved($script)) {
            return ["ok" => false, "message" => "Dieses Script enthält reservierte Systemnamen und wurde blockiert."];
        }

        if ($instance === "") {
            if (self::scriptRequiresInstance($script)) {
                return ["ok" => false, "message" => "Bitte wähle eine Instanz aus oder nutze USE INSTANCE <name>; im Script."];
            }
        } elseif (!self::canAccessInstance($instance)) {
            return ["ok" => false, "message" => "Du hast keinen Zugriff auf diese Instanz."];
        }

        $role = (string)(self::user()["role"] ?? "viewer");

        if ($role === "viewer" && preg_match('/\b(GROW|DROP|ALTER|EDIT|SEED|RESHAPE|ERASE|DELETE|PACK)\b/i', $script)) {
            return ["ok" => false, "message" => "Viewer dürfen nur lesende Queries ausführen."];
        }

        if ($role === "editor" && preg_match('/\b(GROW\s+INSTANCE|DROP\s+INSTANCE|GROW\s+BASE|DROP\s+BASE|GROW\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE|EDIT\s+TABLE)\b/i', $script)) {
            return ["ok" => false, "message" => "Editor dürfen Daten bearbeiten, aber keine Struktur ändern."];
        }

        if (!self::isAdmin()) {
            if (preg_match_all('/\bIN\s+([a-zA-Z0-9_\-]+)/i', $script, $matches)) {
                foreach ($matches[1] as $db) {
                    $db = self::clean((string)$db);
                    if ($db !== "" && !self::canAccessDb($instance, $db)) {
                        return ["ok" => false, "message" => "Kein Zugriff auf Base: " . $db];
                    }
                }
            }
        }

        return ["ok" => true, "message" => ""];
    }

    /**
     * prüft text auf reservierte systemnamen.
     * @param string $value Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function containsReserved(string $value): bool {
        $low = strtolower($value);
        $slug = self::reservedSlug($value);

        return str_contains($low, strtolower(self::SYSTEM_INSTANCE))
            || str_contains($low, "__greenql_ui")
            || str_contains($slug, self::reservedSlug(self::SYSTEM_INSTANCE));
        }

    /**
     * liest ein gql-script sicher aus dem projekt.
     * @param string $path Übergabewert.
     * @return array Rückgabewert.
     */
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

    /**
     * liest die ziel-instanz aus einem script, falls in der ui noch keine gewählt wurde.
     * @param string $script Übergabewert.
     * @return string Rückgabewert.
     */
    private static function instanceFromScript(string $script): string {
        if (preg_match('/\bUSE\s+INSTANCE\s+([a-zA-Z0-9_\-]+)/i', $script, $m)) {
            return self::clean((string)$m[1]);
        }

        if (preg_match('/\bROOT\s+INSTANCE\s+([a-zA-Z0-9_\-]+)/i', $script, $m)) {
            return self::clean((string)$m[1]);
        }

        return "";
    }

    /**
     * führt greenql geschützt aus.
     * @param string $script Übergabewert.
     * @param string $instance Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $script, string $instance, array $params = []): array {
        $instance = self::clean($instance);

        if ($instance === "") {
            $instance = self::instanceFromScript($script);
        }

        $allowed = self::scriptAllowed($script, $instance);

        if (empty($allowed["ok"])) {
            return self::errorResult((string)$allowed["message"]);
        }

        if ($instance !== "") {
            GBDB::setInstance($instance);
        }

        $ctx = $instance !== "" ? ["instance" => $instance] : [];
        $result = GreenQL::run($script, $ctx, $params);
        return self::filterResult($result);
    }

    /**
     * filtert systemwerte aus resultaten.
     * @param array $result Übergabewert.
     * @return array Rückgabewert.
     */
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

    /**
     * filtert systemzeilen aus query-ergebnissen.
     * @param array $rows Übergabewert.
     * @return array Rückgabewert.
     */
    private static function filterSystemRows(array $rows): array {
        return array_values(array_filter($rows, function ($row) {
            if (!is_array($row)) {
                return true;
            }

            foreach (["instance", "base", "table"] as $key) {
                if (isset($row[$key]) && self::containsReserved((string)$row[$key])) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * baut ein standard-fehlerergebnis.
     * @param string $message Übergabewert.
     * @return array Rückgabewert.
     */
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

    /**
     * escaped html.
     * @param mixed $value Übergabewert.
     * @return string Rückgabewert.
     */
    public static function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
    }
}
