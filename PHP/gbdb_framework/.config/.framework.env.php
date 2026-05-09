<?php
/**
 * FrameWork Config (ENV)
 */

class Vars {
    protected static ?bool $isDev = null; // Toggle DEV Mode true/false/null

    private const APP = [
        "version" => "1.0",
        "db_arch" => "GBDB", // or SQL
        "main_instance_to_switch_back_to" => ""
    ];

    private const CACHE = [
        "gbdb_instance" => "cache"
    ];

    private const AUTH = [
        "date_format" => "d.m.Y",
        "ref_to_after_login" => "",
        "ref_to_after_logout" => "",
        "verify_email_on_registration" => true,
        "instance" => "auth",
        "mail_verify_token_expires_in_minutes" => 5,
        "password_forget_token_expires_in_minutes" => 5,
        "2fa_code_expires_in_minutes" => 2,
        "jwt_expires_in_minutes" => 120,
        "2fa_user_input_file" => "2fa.php",
        "files_without_login" => [
            "public_api",
            "logout.php",
            "login.php",
            "password_fergot.php",
            "register.php",
            "gbdb_ui.php",
            "2fa.php"
        ],
        "email_config" => [
            "nl2br" => true,
            "from_email" => "noreply@greenbucket.net",
            "from_name" => "GBDB FrameWork",
            "subject_verify" => "Bestätige deine E-Mail Adresse",
            "subject_2fa" => "Dein 2FA Code lautet ...",
            "verify_link" => "https://example-domain.at/verify_mail.php?token=" // token gets added by system
        ],
        "root_user" => [
            "username" => "admin",
            "email" => "",
            "password" => "admin",
            "active" => true,
            "2fa" => false,
            "role" => "admin",
            "firstname" => "Admin",
            "lastname" => "istrator",
            "adress" => "",
            "telephone" => "",
            "mobile" => "",
            "gender" => false,
            "image" => "",
            "text" => ""
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
        "ip"         => "127.0.0.1/SecondServerModul",
        "ssl"        => false,
        "static_key" => "",
        "api_log"    => false,
        "log_path"   => "PHP/gbdb_framework/.logs/srvp/",
        "sys_instance" => "ssm"
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
            "session_name"  => "TestSession2",
            "session_value" => "Test 2",
        ],
    ];

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

    public static function app_version(): string {
        return self::APP["version"];
    }

    public static function db_arch(): string {
        $arch = strtoupper((string)(self::APP["db_arch"] ?? "GBDB"));

        return in_array($arch, ["GBDB", "SQL"], true) ? $arch : "GBDB";
    }

    public static function cache_instance(): string {
        return self::CACHE["gbdb_instance"];
    }

    public static function auth_instance(): string {
        return self::AUTH["instance"];
    }

    public static function mail_verify_exp(): int {
        return self::AUTH["mail_verify_token_expires_in_minutes"];
    }

    public static function pwf_exp(): int {
        return self::AUTH["password_forget_token_expires_in_minutes"];
    }

    public static function jwt_exp(): int {
        return self::AUTH["jwt_expires_in_minutes"];
    }

    public static function code_2fa_exp(): int {
        return self::AUTH["2fa_code_expires_in_minutes"];
    }

    public static function logout_ref(): string {
        return self::AUTH["ref_to_after_logout"];
    }

    public static function main_instance(): string {
        return self::APP["main_instance_to_switch_back_to"];
    }

    public static function auth_root_user(): array {
        return self::AUTH["root_user"];
    }

    public static function auth_email_config(): array {
        return self::AUTH["email_config"];
    }

    public static function after_login(): string {
        return self::AUTH["ref_to_after_login"];
    }

    public static function verify_email(): bool {
        return self::AUTH["verify_email_on_registration"];
    }

    public static function date_format(): string {
        return self::AUTH["date_format"];
    }

    public static function ref_2fa(): string {
        return self::AUTH["2fa_user_input_file"];
    }

    public static function auth_whitelist(): array {
        return self::AUTH["files_without_login"];
    }

    public static function mRoot_url(): string {
        return self::MROOT["url"];
    }

    public static function mRoot_license_form(): string {
        return self::MROOT["license_form"];
    }

    public static function mRoot_pid(): string {
        return self::MROOT["pid"];
    }

    public static function mRoot_auth(): string {
        return self::MROOT["auth"];
    }

    public static function update_auth(): string {
        return self::UPDATE["auth"];
    }

    public static function srvp_ip(): string {
        return self::SRVP["ip"];
    }

    public static function srvp_ssl(): bool {
        return self::SRVP["ssl"];
    }

    public static function srvp_static_key(): string {
        return self::SRVP["static_key"];
    }

    public static function srvp_api_log(): bool {
        return self::SRVP["api_log"];
    }

    public static function srvp_log_path(): string {
        return self::SRVP["log_path"];
    }

    public static function srvp_sys_instance(): string {
        return self::SRVP["sys_instance"];
    }

    public static function sharesuite_api_url(): string {
        return self::SHARESUTE["api_url"];
    }

    public static function sharesuite_api_key(): string {
        return self::SHARESUTE["api_key"];
    }

    public static function sharesuite_api_auth(): string {
        return self::SHARESUTE["api_auth"];
    }

    public static function sharesuite_sid(): string {
        return self::SHARESUTE["sid"];
    }

    public static function mqr_api_url(): string {
        return self::MQR["api_url"];
    }

    public static function mqr_api_key(): string {
        return self::MQR["api_key"];
    }

    public static function enable_https_redirect(): bool {
        return self::SECURITY["https_redirect"];
    }

    public static function json_path(): string {
        $path = (string)(self::GBDB["json_path"] ?? "");

        if ($path !== "" && str_starts_with(str_replace("\\", "/", $path), "/")) {
            return rtrim($path, "/\\") . "/";
        }

        return dirname(__DIR__) . "/GBDB_GQL/.DB/.storage/";
    }

    public static function json_pretty(): bool {
        return self::__DEV__();
    }

    public static function sql_server(): string {
        return self::SQL["prod"]["server"];
    }

    public static function sql_database(): string {
        return self::SQL["prod"]["database"];
    }

    public static function sql_user(): string {
        return self::SQL["prod"]["user"];
    }

    public static function sql_password(): string {
        return self::SQL["prod"]["password"];
    }

    public static function sql_dev_server(): string {
        return self::SQL["dev"]["server"];
    }

    public static function sql_dev_database(): string {
        return self::SQL["dev"]["database"];
    }

    public static function sql_dev_user(): string {
        return self::SQL["dev"]["user"];
    }

    public static function sql_dev_password(): string {
        return self::SQL["dev"]["password"];
    }

    public static function reCaptcha_website_key(): string {
        return self::RECAPTCHA["website_key"];
    }

    public static function reCaptcha_secret_key(): string {
        return self::RECAPTCHA["secret_key"];
    }

    public static function crypt_data(): bool {
        return self::SECURITY["crypt_data"];
    }

    public static function cryptKey(): string {
        return self::SECURITY["crypt_key"];
    }

    public static function data_extension(): string {
        return self::crypt_data() ? ".db" : ".json";
    }

    public static function init_cookies(): array {
        return self::INIT_COOKIES;
    }

    public static function init_session(): array {
        return self::INIT_SESSION;
    }

    public static function EQR_API_URL(): string {
        return self::EQR_API["url"];
    }

    public static function EQR_API_AUTH(): string {
        return self::EQR_API["auth"];
    }

    protected static function serverVar(string $key, $default = "") {
        return $_SERVER[$key] ?? $default;
    }

    public static function this_file(): string {
        return basename(self::serverVar("SCRIPT_FILENAME", "index.php"));
    }

    public static function this_path(): string {
        return ltrim(self::serverVar("SCRIPT_NAME", ""), "/");
    }

    public static function this_uri(): string {
        $https = self::serverVar("HTTPS", "off");
        $scheme = strtolower($https) === "on" ? "https://" : "http://";
        $host = self::serverVar("HTTP_HOST", "localhost");
        $uri = self::serverVar("REQUEST_URI", "/");

        return $scheme . $host . $uri;
    }

    public static function client_ip(): string {
        return str_replace(":", "-", self::serverVar("REMOTE_ADDR", "0.0.0.0"));
    }

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
            @file_put_contents($htaccess, "Require all denied");
        }

        return $dbPath;
    }

    public static function jpretty(): int {
        return self::json_pretty() ? JSON_PRETTY_PRINT : 0;
    }

    public static function framework_version(): string {
        return "v9.3";
    }

}
