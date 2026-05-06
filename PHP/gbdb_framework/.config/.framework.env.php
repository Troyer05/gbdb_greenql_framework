<?php

class Vars {
    /**
     * Umgebungsvariablen zur Konfiguration von GBDB-FrameWork
     */

    protected static ?bool $isDev = null; // Toggle DEV Mode true/false/null

    private const APP = [
        "version" => "1.0",
        "db_arch" => "GBDB" // or SQL
    ];

    private const PUBLIC_API = [
        "need_auth" => true,
        "gbdb_access" => true,
        "gbdb_write_permission" => true,
        "greenql_access" => true,
        "auth_keys" => [
            "dev_key_01" // Ist nur demo, vor produktivschaltung entfernen
        ]
    ];

    private const MROOT = [
        "url"          => "https://mamueller.de/mroot/api.php",
        "license_form" => "lizenz.php",
        "pid"          => "12345",
        "auth"         => "AWE-mm_4",
    ];

    private const UPDATE = [
        "auth" => "AWE-mm_4",
    ];

    private const SRVP = [
        "ip"         => "127.0.0.1/REPOS/SecondServerModul",
        "ssl"        => false,
        "static_key" => "abc",
        "api_log"    => false,
        "log_path"   => "PHP/gbdb_framework/.logs/srvp/",
    ];

    private const SHARESUTE = [
        "api_url"  => "",
        "api_key"  => "",
        "api_auth" => "",
        "sid" => ""
    ];

    private const MQR = [
        "api_url" => "https://museumqr.de/api.php",
        "api_key" => "DEIN_API_KEY",
    ];

    private const SECURITY = [
        "https_redirect" => true,
        "crypt_data"     => true,
        "crypt_key"      => "abc",
    ];

    private const GBDB = [
        "json_path" => "PHP/gbdb_framework/GBDB_GQL/.DB/.storage/",
    ];

    private const SQL = [
        "prod" => [
            "server"   => "",
            "database" => "",
            "user"     => "",
            "password" => "",
        ],
        "dev" => [
            "server"   => "",
            "database" => "",
            "user"     => "",
            "password" => "",
        ],
    ];

    private const RECAPTCHA = [
        "website_key" => "",
        "secret_key"  => "",
    ];

    private const EQR_API = [
        "url" => "",
        "auth" => ""
    ];

    private const INIT_COOKIES = [
        [
            "cookie_name"  => "TestCookie",
            "cookie_value" => "Test1",
        ],
        [
            "cookie_name"  => "Cookie2",
            "cookie_value" => "abc",
        ],
    ];

    private const INIT_SESSION = [
        [
            "session_name"  => "pnp",
            "session_value" => "",
        ],
        [
            "session_name"  => "Test Session Variable 2",
            "session_value" => "Test 2",
        ],
    ];

    /**
     * Verarbeitet die Funktion a u t h.
     * @return array Rückgabewert.
     */
    public static function AUTH(): array {
        return [
            "main_db" => "userdb",
            "token_expires_days" => "3",
            "jwt_cookie_name" => "jwt",
            "logout_file" => "",
            "login_file" => "",
            "files_no_login" => ["login.php", "logout.php"],
            "root_user" => [
                "uid" => "abc123",
                "username" => "admin",
                "password" => Auth::hashPass("admin"),
                "email" => "",
                "active" => true,
                "rolle" => "admin",
                "datum" => "",
                "tfa" => false
            ],
            "root_user_meta" => [
                "uid" => "abc123",
                "vorname" => "System",
                "nachname" => "Administrator",
                "telefon" => "",
                "mobil" => "",
                "adresse" => "",
                "gender" => "",
                "bio" => "",
                "image" => ""
            ],
            "email_config" => [
                "from_email" => "noreply@greenbucket.net",
                "from_name" => "GBDB FrameWork",
                "subject_verify" => "Bestätige deine E-Mail Adresse",
                "subject_2fa" => "Dein 2FA Code lautet ...",
                "mail_verify" => "PHP/gbdb_framework/mail_templates/verify_mail.html",
                "mail_2fa" => "PHP/gbdb_framework/mail_templates/tfa_mail.html",
                "verify_link" => "https://example-domain.at/verify_mail.php?token=" // Token wird später angehängt
            ]
        ];
    }

    // ======================================================

    public static function __DEV__(): bool {
        if (self::$isDev !== null) {
            return self::$isDev;
        }

        $env = getenv("GBDB_ENV");

        if ($env !== false && strtolower($env) === "dev") {
            return self::$isDev = true;
        }

        $envDevFlag = getenv("GBDB_DEV");

        if ($envDevFlag !== false && (int)$envDevFlag === 1) {
            return self::$isDev = true;
        }

        if (file_exists("D:\\priv_laptop")) {
            return self::$isDev = true;
        }

        if (file_exists("C:\\daa\\daa.txt")) {
            return self::$isDev = true;
        }

        if (file_exists("/privl.p")) {
            return self::$isDev = true;
        }

        return self::$isDev = false;
    }

    /**
     * Verarbeitet die Funktion app_version.
     * @return string Rückgabewert.
     */
    public static function app_version(): string {
        return self::APP["version"];
    }

    /**
     * Gibt die aktive Datenbank-Architektur zurück.
     * @return string GBDB oder SQL.
     */
    public static function db_arch(): string {
        $arch = strtoupper((string)(self::APP["db_arch"] ?? "GBDB"));
        return in_array($arch, ["GBDB", "SQL"], true) ? $arch : "GBDB";
    }

    /**
     * Verarbeitet die Funktion PAPI
     * @return bool
     */
    public static function pApi_need_auth(): bool {
        return self::PUBLIC_API["need_auth"];
    }

    /**
     * Verarbeitet die Funktion PAPI
     * @return array
     */
    public static function pApi_auth_keys(): array {
        return self::PUBLIC_API["auth_keys"];
    }

    public static function pApi_access_gbdb(): bool {
        return self::PUBLIC_API["gbdb_access"];
    }

    public static function pApi_write_gbdb(): bool {
        return self::PUBLIC_API["gbdb_write_permission"];
    }

    public static function pApi_greenql(): bool {
        return self::PUBLIC_API["greenql_access"];
    }

    /**
     * Verarbeitet die Funktion m root_url.
     * @return string Rückgabewert.
     */
    public static function mRoot_url(): string {
        return self::MROOT["url"];
    }

    /**
     * Verarbeitet die Funktion m root_license_form.
     * @return string Rückgabewert.
     */
    public static function mRoot_license_form(): string {
        return self::MROOT["license_form"];
    }

    /**
     * Verarbeitet die Funktion m root_pid.
     * @return string Rückgabewert.
     */
    public static function mRoot_pid(): string {
        return self::MROOT["pid"];
    }

    /**
     * Verarbeitet die Funktion m root_auth.
     * @return string Rückgabewert.
     */
    public static function mRoot_auth(): string {
        return self::MROOT["auth"];
    }

    /**
     * Verarbeitet die Funktion update_auth.
     * @return string Rückgabewert.
     */
    public static function update_auth(): string {
        return self::UPDATE["auth"];
    }

    /**
     * Verarbeitet die Funktion srvp_ip.
     * @return string Rückgabewert.
     */
    public static function srvp_ip(): string {
        return self::SRVP["ip"];
    }

    /**
     * Verarbeitet die Funktion srvp_ssl.
     * @return bool Rückgabewert.
     */
    public static function srvp_ssl(): bool {
        return self::SRVP["ssl"];
    }

    /**
     * Verarbeitet die Funktion srvp_static_key.
     * @return string Rückgabewert.
     */
    public static function srvp_static_key(): string {
        return self::SRVP["static_key"];
    }

    /**
     * Verarbeitet die Funktion srvp_api_log.
     * @return bool Rückgabewert.
     */
    public static function srvp_api_log(): bool {
        return self::SRVP["api_log"];
    }

    /**
     * Verarbeitet die Funktion srvp_log_path.
     * @return string Rückgabewert.
     */
    public static function srvp_log_path(): string {
        return self::SRVP["log_path"];
    }

    /**
     * Verarbeitet die Funktion sharesuite_api_url.
     * @return string Rückgabewert.
     */
    public static function sharesuite_api_url(): string {
        return self::SHARESUTE["api_url"];
    }

    /**
     * Verarbeitet die Funktion sharesuite_api_key.
     * @return string Rückgabewert.
     */
    public static function sharesuite_api_key(): string {
        return self::SHARESUTE["api_key"];
    }

    /**
     * Verarbeitet die Funktion sharesuite_api_auth.
     * @return string Rückgabewert.
     */
    public static function sharesuite_api_auth(): string {
        return self::SHARESUTE["api_auth"];
    }

    /**
     * Verarbeitet die Funktion sharesuite_sid.
     * @return string Rückgabewert.
     */
    public static function sharesuite_sid(): string {
        return self::SHARESUTE["sid"];
    }

    /**
     * Verarbeitet die Funktion mqr_api_url.
     * @return string Rückgabewert.
     */
    public static function mqr_api_url(): string {
        return self::MQR["api_url"];
    }

    /**
     * Verarbeitet die Funktion mqr_api_key.
     * @return string Rückgabewert.
     */
    public static function mqr_api_key(): string {
        return self::MQR["api_key"];
    }

    /**
     * Verarbeitet die Funktion enable_https_redirect.
     * @return bool Rückgabewert.
     */
    public static function enable_https_redirect(): bool {
        return self::SECURITY["https_redirect"];
    }

    /**
     * Verarbeitet die Funktion json_path.
     * @return string Rückgabewert.
     */
    public static function json_path(): string {
        $path = (string)(self::GBDB["json_path"] ?? "");

        if ($path !== "" && str_starts_with(str_replace("\\", "/", $path), "/")) {
            return rtrim($path, "/\\") . "/";
        }

        return dirname(__DIR__) . "/GBDB_GQL/.DB/.storage/";
    }

    /**
     * Verarbeitet die Funktion json_pretty.
     * @return bool Rückgabewert.
     */
    public static function json_pretty(): bool {
        return self::__DEV__();
    }

    /**
     * Verarbeitet die Funktion sql_server.
     * @return string Rückgabewert.
     */
    public static function sql_server(): string {
        return self::SQL["prod"]["server"];
    }

    /**
     * Verarbeitet die Funktion sql_database.
     * @return string Rückgabewert.
     */
    public static function sql_database(): string {
        return self::SQL["prod"]["database"];
    }

    /**
     * Verarbeitet die Funktion sql_user.
     * @return string Rückgabewert.
     */
    public static function sql_user(): string {
        return self::SQL["prod"]["user"];
    }

    /**
     * Verarbeitet die Funktion sql_password.
     * @return string Rückgabewert.
     */
    public static function sql_password(): string {
        return self::SQL["prod"]["password"];
    }

    /**
     * Verarbeitet die Funktion sql_dev_server.
     * @return string Rückgabewert.
     */
    public static function sql_dev_server(): string {
        return self::SQL["dev"]["server"];
    }

    /**
     * Verarbeitet die Funktion sql_dev_database.
     * @return string Rückgabewert.
     */
    public static function sql_dev_database(): string {
        return self::SQL["dev"]["database"];
    }

    /**
     * Verarbeitet die Funktion sql_dev_user.
     * @return string Rückgabewert.
     */
    public static function sql_dev_user(): string {
        return self::SQL["dev"]["user"];
    }

    /**
     * Verarbeitet die Funktion sql_dev_password.
     * @return string Rückgabewert.
     */
    public static function sql_dev_password(): string {
        return self::SQL["dev"]["password"];
    }

    /**
     * Verarbeitet die Funktion re captcha_website_key.
     * @return string Rückgabewert.
     */
    public static function reCaptcha_website_key(): string {
        return self::RECAPTCHA["website_key"];
    }

    /**
     * Verarbeitet die Funktion re captcha_secret_key.
     * @return string Rückgabewert.
     */
    public static function reCaptcha_secret_key(): string {
        return self::RECAPTCHA["secret_key"];
    }

    /**
     * Verarbeitet die Funktion crypt_data.
     * @return bool Rückgabewert.
     */
    public static function crypt_data(): bool {
        return self::SECURITY["crypt_data"];
    }

    /**
     * Verarbeitet die Funktion crypt key.
     * @return string Rückgabewert.
     */
    public static function cryptKey(): string {
        return self::SECURITY["crypt_key"];
    }

    /**
     * Verarbeitet die Funktion data_extension.
     * @return string Rückgabewert.
     */
    public static function data_extension(): string {
        return self::crypt_data() ? ".db" : ".json";
    }

    /**
     * Verarbeitet die Funktion init_cookies.
     * @return array Rückgabewert.
     */
    public static function init_cookies(): array {
        return self::INIT_COOKIES;
    }

    /**
     * Verarbeitet die Funktion init_session.
     * @return array Rückgabewert.
     */
    public static function init_session(): array {
        return self::INIT_SESSION;
    }

    public static function EQR_API_URL(): string {
        return self::EQR_API["url"];
    }

    public static function EQR_API_AUTH(): string {
        return self::EQR_API["auth"];
    }

    /**
     * Verarbeitet die Funktion server var.
     * @param string $key Übergabewert.
     * @param mixed $default Übergabewert.
     * @return mixed Rückgabewert.
     */
    protected static function serverVar(string $key, $default = "") {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Verarbeitet die Funktion this_file.
     * @return string Rückgabewert.
     */
    public static function this_file(): string {
        return basename(self::serverVar("SCRIPT_FILENAME", "index.php"));
    }

    /**
     * Verarbeitet die Funktion this_path.
     * @return string Rückgabewert.
     */
    public static function this_path(): string {
        return ltrim(self::serverVar("SCRIPT_NAME", ""), "/");
    }

    /**
     * Verarbeitet die Funktion this_uri.
     * @return string Rückgabewert.
     */
    public static function this_uri(): string {
        $https = self::serverVar("HTTPS", "off");
        $scheme = strtolower($https) === "on" ? "https://" : "http://";
        $host = self::serverVar("HTTP_HOST", "localhost");
        $uri = self::serverVar("REQUEST_URI", "/");

        return $scheme . $host . $uri;
    }

    /**
     * Verarbeitet die Funktion client_ip.
     * @return string Rückgabewert.
     */
    public static function client_ip(): string {
        return str_replace(":", "-", self::serverVar("REMOTE_ADDR", "0.0.0.0"));
    }

    /**
     * Verarbeitet die Funktion d b_ p a t h.
     * @return string Rückgabewert.
     */
    public static function DB_PATH(): string {
        $basePath = dirname(__DIR__) . "/GBDB_GQL/.DB/";
        $dbPath = $basePath . ".storage/";

        foreach ([$basePath, $basePath . ".system/", $dbPath, $basePath . ".scripts/", $basePath . ".temp/"] as $path) {
            if (!is_dir($path)) {
                if (!@mkdir($path, 0777, true) && !is_dir($path)) {
                    trigger_error("GBDB: Konnte Ordner '{$path}' nicht erstellen.", E_USER_WARNING);
                }
            }
        }

        $htaccess = $basePath . ".htaccess";
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied
");
        }

        return $dbPath;
    }

    /**
     * Verarbeitet die Funktion jpretty.
     * @return int Rückgabewert.
     */
    public static function jpretty(): int {
        return self::json_pretty() ? JSON_PRETTY_PRINT : 0;
    }

    public static function framework_version(): string {
        return "v8.0";
    }
}
