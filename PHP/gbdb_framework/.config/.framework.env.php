<?php

declare(strict_types=1);

/**
 * Zentrale Framework-Konfiguration: ausschließlich diese Datei bearbeiten.
 * Aufbau wie die bisherige Vars-Klasse; alle Einstellungen stehen in Konstanten.
 * Leere Schlüssel werden einmalig unter .runtime erzeugt. Diesen Ordner behalten!
 */
if (isset($_SERVER["SCRIPT_FILENAME"]) && realpath((string) $_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    http_response_code(404);
    exit;
}

class Vars {
    protected static ?bool $isDev = null;
    // Toggle DEV Mode true/false/null

    private const APP = [
        "name" => "GBDB Full",
        "environment" => "prod", // prod oder dev
        "version" => "10.0.0",
        "db_arch" => "GBDB", // or SQL
        "main_instance_to_switch_back_to" => ""
    ];

    private const CACHE = [
        "gbdb_instance" => "cache"
    ];

    private const AUTH = [
        "date_format" => "d.m.Y",
        "login_attempts" => 20,
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
            "password" => "",
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

    // Framework-Updater (signierte Releases von mamueller.de). Die Prüfungen unter
    // "verification" und "installation" sind Pflicht: false wird ignoriert.
    // patch_day: Wochentag und Uhrzeit für automatische Updates, z. B. "Fridays, 21:30:00".
    // work_dir: leer = ".gbdb_updater" neben gbdb_framework (Zustand, Backups, Protokolle).
    private const FRAMEWORK_UPDATER = [
        "check_for_updates" => true,
        "update_server" => "https://mamueller.de/gbdb/updates/",
        "verify_api" => "https://mamueller.de/gbdb/verify.php",
        "force_ssl" => true,
        "check_interval" => 21600,
        "auto_updating" => [
            "enabled" => false,
            "patch_day" => "Fridays, 21:30:00",
        ],
        "verification" => [
            "checksum_algorithm" => "sha256",
            "signature_algorithm" => "ed25519",
            "root_public_key" => "BUILTIN",
            "verify_trust_chain" => true,
            "verify_release_manifest" => true,
            "verify_release_catalog" => true,
            "verify_via_api" => true,
            "prevent_rollback" => true,
            "check_key_revocation" => true,
            "reject_expired_metadata" => true,
        ],
        "installation" => [
            "create_backup" => true,
            "verify_checksum" => true,
            "verify_signature" => true,
            "preserve_user_data" => true,
            "preserve_custom_files" => true,
            "preserve_configuration" => true,
            "check_compatibility" => true,
            "rollback_on_failure" => true,
        ],
        "work_dir" => "",
        "http_timeout" => 15,
        "keep_backups" => 3,
    ];

    private const SRVP = [
        "ip"         => "",
        "ssl"        => false,
        "static_key" => "",
        "api_log"    => false,
        "log_path"   => "PHP/gbdb_framework/.logs/srvp/",
        "sys_instance" => "ssm",
        "crypto_info" => "gbdb-srvp-v1",
        "crypto_algo" => "aes-256-gcm",
        "crypto_ttl" => 60
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
        "https_redirect" => false,
        "crypt_data"     => true,
        "crypt_key"      => "", // Bei vorhandenen Daten den bisherigen Schlüssel behalten.
        "installation_key" => "",
    ];

    private const STORAGE = [
        "path" => "GBDB_GQL/.DB", // Relativ zum Framework oder absolut.
        "lock_timeout_ms" => 10000,
        "request_cache_bytes" => 8388608,
    ];

    private const UI = [
        "enabled" => true,
        // Diesen Wert beim ersten Aufruf in das Setup-Token-Feld kopieren.
        // Eigener Token möglich: mindestens 24 Zeichen. Passwort: mindestens 12.
        "setup_token" => "",
        "allow_php_exec" => false,
        "allow_config_edit" => false,
        "allow_plugin_upload" => false,
        "allow_reinstall" => false,
        "login_attempts" => 10,
        "login_window_seconds" => 900,
    ];

    private const FAILOVER = [
        "enabled" => false,
        "dsn" => "mysql:host=127.0.0.1;port=3306;dbname=gbdb;charset=utf8mb4",
        "user" => "",
        "password" => "",
        "prefix" => "gbdb_full", // Pro Installation eindeutig vergeben.
        "connect_timeout" => 3,
        "retry_seconds" => 10,
    ];

    // Leer: DEV verwendet dieselbe Fallback-Verbindung. Einzelne Werte überschreibbar.
    private const FAILOVER_DEV = [];

    private const MAIL = [
        "api_url" => "",
    ];

    // GBDB Distributed Protocol (GDP/1). Ohne enabled = true bleibt alles lokal.
    private const GDP = [
        'enabled' => false,
        'role' => 'replica',
        'node_name' => 'GBDB Central',
        'hub_url' => '',
        'allow_insecure_http' => false,
        'sync_mode' => 'embedded',
        'sync_batch_size' => 100,
        'sync_max_bytes' => 1048576,
        'sync_timeout' => 5,
        'embedded_interval' => 30,
        'embedded_budget_seconds' => 2,
        'offline_enabled' => true,
        'clock_skew_seconds' => 300,
        'max_request_bytes' => 8388608,
        'retention_days' => 30,
        'snapshot_chunk_bytes' => 262144,
        'trigger_token' => '',
        'pairing_ttl' => 900,
    ];

    private const GREENQL = [
        "api_auth" => "",
    ];

    private const LEGACY = [
        "crypto_conversion" => false,
    ];

    private const SQL = [
        "prod" => [
            "dsn"      => "", // Optional; sonst aus server/database gebildet.
            "server"   => "",
            "database" => "",
            "user"     => "",
            "password" => "",
        ],
        "dev" => [
            "dsn"      => "", // Optional; sonst aus server/database gebildet.
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

    /** Interne Abbildung für GBDBConfig; keine zweite Konfigurationsquelle. */
    public static function configuration(array $constants = []) :array {
        $constants = array_replace((new ReflectionClass(self::class))->getConstants(), $constants);
        $sections = [];

        foreach ($constants as $name => $value) {
            $sections[strtolower($name)] = $value;
        }

        $sections["app"]["main_instance"] = $constants["APP"]["main_instance_to_switch_back_to"];
        $sections["auth"]["verify_email"] = $constants["AUTH"]["verify_email_on_registration"];
        $sections["auth"]["root_username"] = $constants["AUTH"]["root_user"]["username"];
        $sections["auth"]["root_password"] = $constants["AUTH"]["root_user"]["password"];
        $sections["auth"]["root_email"] = $constants["AUTH"]["root_user"]["email"];
        $sections["srvp"]["url"] = $constants["SRVP"]["ip"];
        $sections["sql.prod"] = $constants["SQL"]["prod"];
        $sections["sql.dev"] = $constants["SQL"]["dev"];
        $sections["failover.dev"] = $constants["FAILOVER_DEV"];

        return $sections;
    }

    // ======================================================

    public static function __DEV__() :bool {
        if (self::$isDev !== null) {
            return self::$isDev;
        }

        $environment = getenv('GBDB_ENV');

        if ($environment !== false && trim($environment) !== '') {
            $environment = strtolower(trim($environment));

            if (!in_array($environment, ['dev', 'development', 'local', 'prod', 'production'], true)) {
                throw new InvalidArgumentException('GBDB_ENV muss dev oder prod sein.');
            }

            return self::$isDev = in_array($environment, ['dev', 'development', 'local'], true);
        }

        $flag = getenv('GBDB_DEV');

        if ($flag !== false && $flag !== '') {
            $value = filter_var($flag, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($value === null) {
                throw new InvalidArgumentException('GBDB_DEV muss ein boolescher Wert sein.');
            }

            return self::$isDev = $value;
        }

        return self::$isDev = in_array(strtolower((string) GBDBConfig::get('app', 'environment', 'prod')), ['dev', 'development', 'local'], true);
    }

    public static function app_version() :string {
        return (string) GBDBConfig::get('app', 'version', self::APP['version']);
    }

    public static function db_arch() :string {
        $arch = strtoupper((string) GBDBConfig::get("app", "db_arch", "GBDB"));

        return in_array($arch, ["GBDB", "SQL"], true) ? $arch : "GBDB";
    }

    public static function cache_instance() :string {
        return self::CACHE["gbdb_instance"];
    }

    public static function auth_instance() :string {
        return self::AUTH["instance"];
    }

    public static function mail_verify_exp() :int {
        return self::AUTH["mail_verify_token_expires_in_minutes"];
    }

    public static function pwf_exp() :int {
        return self::AUTH["password_forget_token_expires_in_minutes"];
    }

    public static function jwt_exp() :int {
        return self::AUTH["jwt_expires_in_minutes"];
    }

    public static function code_2fa_exp() :int {
        return self::AUTH["2fa_code_expires_in_minutes"];
    }

    public static function logout_ref() :string {
        return self::AUTH["ref_to_after_logout"];
    }

    public static function main_instance() :string {
        return (string) GBDBConfig::get('app', 'main_instance', '');
    }

    public static function auth_root_user() :array {
        $user = self::AUTH['root_user'];
        $user['username'] = (string) GBDBConfig::get('auth', 'root_username', 'admin');
        $user['password'] = (string) GBDBConfig::get('auth', 'root_password', '');
        $user['email'] = (string) GBDBConfig::get('auth', 'root_email', '');

        return $user;
    }

    public static function auth_email_config() :array {
        return self::AUTH["email_config"];
    }

    public static function after_login() :string {
        return self::AUTH["ref_to_after_login"];
    }

    public static function verify_email() :bool {
        return GBDBConfig::flag('auth', 'verify_email', self::AUTH['verify_email_on_registration']);
    }

    public static function date_format() :string {
        return self::AUTH["date_format"];
    }

    public static function ref_2fa() :string {
        return self::AUTH["2fa_user_input_file"];
    }

    public static function auth_whitelist() :array {
        return self::AUTH["files_without_login"];
    }

    public static function mRoot_url() :string {
        return self::MROOT["url"];
    }

    public static function mRoot_license_form() :string {
        return self::MROOT["license_form"];
    }

    public static function mRoot_pid() :string {
        return self::MROOT["pid"];
    }

    public static function mRoot_auth() :string {
        return self::MROOT["auth"];
    }

    public static function update_auth() :string {
        return self::UPDATE["auth"];
    }

    public static function srvp_ip() :string {
        return (string) GBDBConfig::get('srvp', 'url', '');
    }

    public static function srvp_ssl() :bool {
        return self::SRVP["ssl"];
    }

    public static function srvp_static_key() :string {
        return (string) GBDBConfig::get('srvp', 'static_key', '');
    }

    public static function srvp_api_log() :bool {
        return self::SRVP["api_log"];
    }

    public static function srvp_log_path() :string {
        return self::data_root() . '/.logs/srvp/';
    }

    public static function srvp_sys_instance() :string {
        return self::SRVP["sys_instance"];
    }

    public static function sharesuite_api_url() :string {
        return self::SHARESUTE["api_url"];
    }

    public static function sharesuite_api_key() :string {
        return self::SHARESUTE["api_key"];
    }

    public static function sharesuite_api_auth() :string {
        return self::SHARESUTE["api_auth"];
    }

    public static function sharesuite_sid() :string {
        return self::SHARESUTE["sid"];
    }

    public static function mqr_api_url() :string {
        return self::MQR["api_url"];
    }

    public static function mqr_api_key() :string {
        return self::MQR["api_key"];
    }

    public static function enable_https_redirect() :bool {
        return GBDBConfig::flag('security', 'https_redirect', false);
    }

    public static function json_path() :string {
        return self::DB_PATH();
    }

    public static function json_pretty() :bool {
        return self::__DEV__();
    }

    public static function sql_server() :string {
        return self::SQL["prod"]["server"];
    }

    public static function sql_database() :string {
        return self::SQL["prod"]["database"];
    }

    public static function sql_user() :string {
        return self::SQL["prod"]["user"];
    }

    public static function sql_password() :string {
        return self::SQL["prod"]["password"];
    }

    public static function sql_dev_server() :string {
        return self::SQL["dev"]["server"];
    }

    public static function sql_dev_database() :string {
        return self::SQL["dev"]["database"];
    }

    public static function sql_dev_user() :string {
        return self::SQL["dev"]["user"];
    }

    public static function sql_dev_password() :string {
        return self::SQL["dev"]["password"];
    }

    public static function reCaptcha_website_key() :string {
        return self::RECAPTCHA["website_key"];
    }

    public static function reCaptcha_secret_key() :string {
        return self::RECAPTCHA["secret_key"];
    }

    public static function crypt_data() :bool {
        return GBDBConfig::flag('security', 'crypt_data', true);
    }

    public static function cryptKey() :string {
        return GBDBConfig::secret('crypt_key');
    }

    public static function data_extension() :string {
        return self::crypt_data() ? ".db" : ".json";
    }

    public static function init_cookies() :array {
        return self::INIT_COOKIES;
    }

    public static function init_session() :array {
        return self::INIT_SESSION;
    }

    public static function EQR_API_URL() :string {
        return self::EQR_API["url"];
    }

    public static function EQR_API_AUTH() :string {
        return self::EQR_API["auth"];
    }

    protected static function serverVar(string $key, $default = "") {
        return $_SERVER[$key] ?? $default;
    }

    public static function this_file() :string {
        return basename(self::serverVar("SCRIPT_FILENAME", "index.php"));
    }

    public static function this_path() :string {
        return ltrim(self::serverVar("SCRIPT_NAME", ""), "/");
    }

    public static function this_uri() :string {
        $https = self::serverVar("HTTPS", "off");
        $scheme = strtolower($https) === "on" ? "https://" : "http://";
        $host = self::serverVar("HTTP_HOST", "localhost");
        $uri = self::serverVar("REQUEST_URI", "/");

        return $scheme . $host . $uri;
    }

    public static function client_ip() :string {
        return str_replace(":", "-", self::serverVar("REMOTE_ADDR", "0.0.0.0"));
    }

    public static function data_root() :string {
        return GBDBRuntime::root();
    }

    public static function DB_PATH() :string {
        return self::data_root() . '/.storage/';
    }

    public static function jpretty() :int {
        return self::json_pretty() ? JSON_PRETTY_PRINT : 0;
    }

    public static function framework_version() :string {
        return class_exists('GBDBUpdateVersion') ? 'v' . GBDBUpdateVersion::current() : 'v10.0.1-full';
    }

}
