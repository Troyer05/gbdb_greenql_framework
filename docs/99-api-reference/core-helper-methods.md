# Konfiguration, Auth und Hilfsklassen

Vars beschreibt die zentrale Konfiguration. Anwendungscode verwendet Auth aus runtime/auth_facade.php; GBDBAuthEngine ist intern. Globale Funktionen sind mit ihrer tatsächlichen Quelldatei aufgeführt.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## Auth

Klasse · Quelle: [runtime/auth_facade.php](../../PHP/gbdb_framework/runtime/auth_facade.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `createStructure()` | `public static function createStructure() :array` | [Zeile 7](../../PHP/gbdb_framework/runtime/auth_facade.php#L7) |
| `logout()` | `public static function logout(string $error = '', string $jwt = '') :array` | [Zeile 11](../../PHP/gbdb_framework/runtime/auth_facade.php#L11) |
| `init()` | `public static function init() :array` | [Zeile 15](../../PHP/gbdb_framework/runtime/auth_facade.php#L15) |
| `login()` | `public static function login(string $usernameOrEmail, string $passwordPlainText) :array` | [Zeile 19](../../PHP/gbdb_framework/runtime/auth_facade.php#L19) |
| `user_registration()` | `public static function user_registration(string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = 'user', string $firstname = '', string $lastname = '', string $adress = '', string $telephone = '', string $mobile = '', bool $gender = false, string $image = '', bool $verifyMail = true) :array` | [Zeile 23](../../PHP/gbdb_framework/runtime/auth_facade.php#L23) |
| `verify_email()` | `public static function verify_email(string $token) :array` | [Zeile 27](../../PHP/gbdb_framework/runtime/auth_facade.php#L27) |
| `verify_2fa_code()` | `public static function verify_2fa_code(string\|int $code) :array` | [Zeile 31](../../PHP/gbdb_framework/runtime/auth_facade.php#L31) |
| `edit_user()` | `public static function edit_user(string $uid, string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = 'user', string $firstname = '', string $lastname = '', string $adress = '', string $telephone = '', string $mobile = '', bool $gender = false, string $image = '', string $text = '') :array` | [Zeile 35](../../PHP/gbdb_framework/runtime/auth_facade.php#L35) |
| `delete_user()` | `public static function delete_user(string $uid) :array` | [Zeile 39](../../PHP/gbdb_framework/runtime/auth_facade.php#L39) |
| `get_user()` | `public static function get_user(string $uid) :array` | [Zeile 43](../../PHP/gbdb_framework/runtime/auth_facade.php#L43) |
| `get_jwt_user()` | `public static function get_jwt_user(string $jwt) :array` | [Zeile 47](../../PHP/gbdb_framework/runtime/auth_facade.php#L47) |
| `getUsers()` | `public static function getUsers(int $limit = 10000000) :array` | [Zeile 51](../../PHP/gbdb_framework/runtime/auth_facade.php#L51) |
| `resendVerification()` | `public static function resendVerification(string $usernameOrEmail) :array` | [Zeile 54](../../PHP/gbdb_framework/runtime/auth_facade.php#L54) |

## Vars

Klasse · Quelle: [.config/.framework.env.php](../../PHP/gbdb_framework/.config/.framework.env.php#L15)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `configuration()` | `public static function configuration(array $constants = []) :array` | [Zeile 277](../../PHP/gbdb_framework/.config/.framework.env.php#L277) |
| `__DEV__()` | `public static function __DEV__() :bool` | [Zeile 300](../../PHP/gbdb_framework/.config/.framework.env.php#L300) |
| `app_version()` | `public static function app_version() :string` | [Zeile 332](../../PHP/gbdb_framework/.config/.framework.env.php#L332) |
| `db_arch()` | `public static function db_arch() :string` | [Zeile 336](../../PHP/gbdb_framework/.config/.framework.env.php#L336) |
| `cache_instance()` | `public static function cache_instance() :string` | [Zeile 342](../../PHP/gbdb_framework/.config/.framework.env.php#L342) |
| `auth_instance()` | `public static function auth_instance() :string` | [Zeile 346](../../PHP/gbdb_framework/.config/.framework.env.php#L346) |
| `mail_verify_exp()` | `public static function mail_verify_exp() :int` | [Zeile 350](../../PHP/gbdb_framework/.config/.framework.env.php#L350) |
| `pwf_exp()` | `public static function pwf_exp() :int` | [Zeile 354](../../PHP/gbdb_framework/.config/.framework.env.php#L354) |
| `jwt_exp()` | `public static function jwt_exp() :int` | [Zeile 358](../../PHP/gbdb_framework/.config/.framework.env.php#L358) |
| `code_2fa_exp()` | `public static function code_2fa_exp() :int` | [Zeile 362](../../PHP/gbdb_framework/.config/.framework.env.php#L362) |
| `logout_ref()` | `public static function logout_ref() :string` | [Zeile 366](../../PHP/gbdb_framework/.config/.framework.env.php#L366) |
| `main_instance()` | `public static function main_instance() :string` | [Zeile 370](../../PHP/gbdb_framework/.config/.framework.env.php#L370) |
| `auth_root_user()` | `public static function auth_root_user() :array` | [Zeile 374](../../PHP/gbdb_framework/.config/.framework.env.php#L374) |
| `auth_email_config()` | `public static function auth_email_config() :array` | [Zeile 383](../../PHP/gbdb_framework/.config/.framework.env.php#L383) |
| `after_login()` | `public static function after_login() :string` | [Zeile 387](../../PHP/gbdb_framework/.config/.framework.env.php#L387) |
| `verify_email()` | `public static function verify_email() :bool` | [Zeile 391](../../PHP/gbdb_framework/.config/.framework.env.php#L391) |
| `date_format()` | `public static function date_format() :string` | [Zeile 395](../../PHP/gbdb_framework/.config/.framework.env.php#L395) |
| `ref_2fa()` | `public static function ref_2fa() :string` | [Zeile 399](../../PHP/gbdb_framework/.config/.framework.env.php#L399) |
| `auth_whitelist()` | `public static function auth_whitelist() :array` | [Zeile 403](../../PHP/gbdb_framework/.config/.framework.env.php#L403) |
| `mRoot_url()` | `public static function mRoot_url() :string` | [Zeile 407](../../PHP/gbdb_framework/.config/.framework.env.php#L407) |
| `mRoot_license_form()` | `public static function mRoot_license_form() :string` | [Zeile 411](../../PHP/gbdb_framework/.config/.framework.env.php#L411) |
| `mRoot_pid()` | `public static function mRoot_pid() :string` | [Zeile 415](../../PHP/gbdb_framework/.config/.framework.env.php#L415) |
| `mRoot_auth()` | `public static function mRoot_auth() :string` | [Zeile 419](../../PHP/gbdb_framework/.config/.framework.env.php#L419) |
| `update_auth()` | `public static function update_auth() :string` | [Zeile 423](../../PHP/gbdb_framework/.config/.framework.env.php#L423) |
| `srvp_ip()` | `public static function srvp_ip() :string` | [Zeile 427](../../PHP/gbdb_framework/.config/.framework.env.php#L427) |
| `srvp_ssl()` | `public static function srvp_ssl() :bool` | [Zeile 431](../../PHP/gbdb_framework/.config/.framework.env.php#L431) |
| `srvp_static_key()` | `public static function srvp_static_key() :string` | [Zeile 435](../../PHP/gbdb_framework/.config/.framework.env.php#L435) |
| `srvp_api_log()` | `public static function srvp_api_log() :bool` | [Zeile 439](../../PHP/gbdb_framework/.config/.framework.env.php#L439) |
| `srvp_log_path()` | `public static function srvp_log_path() :string` | [Zeile 443](../../PHP/gbdb_framework/.config/.framework.env.php#L443) |
| `srvp_sys_instance()` | `public static function srvp_sys_instance() :string` | [Zeile 447](../../PHP/gbdb_framework/.config/.framework.env.php#L447) |
| `sharesuite_api_url()` | `public static function sharesuite_api_url() :string` | [Zeile 451](../../PHP/gbdb_framework/.config/.framework.env.php#L451) |
| `sharesuite_api_key()` | `public static function sharesuite_api_key() :string` | [Zeile 455](../../PHP/gbdb_framework/.config/.framework.env.php#L455) |
| `sharesuite_api_auth()` | `public static function sharesuite_api_auth() :string` | [Zeile 459](../../PHP/gbdb_framework/.config/.framework.env.php#L459) |
| `sharesuite_sid()` | `public static function sharesuite_sid() :string` | [Zeile 463](../../PHP/gbdb_framework/.config/.framework.env.php#L463) |
| `mqr_api_url()` | `public static function mqr_api_url() :string` | [Zeile 467](../../PHP/gbdb_framework/.config/.framework.env.php#L467) |
| `mqr_api_key()` | `public static function mqr_api_key() :string` | [Zeile 471](../../PHP/gbdb_framework/.config/.framework.env.php#L471) |
| `enable_https_redirect()` | `public static function enable_https_redirect() :bool` | [Zeile 475](../../PHP/gbdb_framework/.config/.framework.env.php#L475) |
| `json_path()` | `public static function json_path() :string` | [Zeile 479](../../PHP/gbdb_framework/.config/.framework.env.php#L479) |
| `json_pretty()` | `public static function json_pretty() :bool` | [Zeile 483](../../PHP/gbdb_framework/.config/.framework.env.php#L483) |
| `sql_server()` | `public static function sql_server() :string` | [Zeile 487](../../PHP/gbdb_framework/.config/.framework.env.php#L487) |
| `sql_database()` | `public static function sql_database() :string` | [Zeile 491](../../PHP/gbdb_framework/.config/.framework.env.php#L491) |
| `sql_user()` | `public static function sql_user() :string` | [Zeile 495](../../PHP/gbdb_framework/.config/.framework.env.php#L495) |
| `sql_password()` | `public static function sql_password() :string` | [Zeile 499](../../PHP/gbdb_framework/.config/.framework.env.php#L499) |
| `sql_dev_server()` | `public static function sql_dev_server() :string` | [Zeile 503](../../PHP/gbdb_framework/.config/.framework.env.php#L503) |
| `sql_dev_database()` | `public static function sql_dev_database() :string` | [Zeile 507](../../PHP/gbdb_framework/.config/.framework.env.php#L507) |
| `sql_dev_user()` | `public static function sql_dev_user() :string` | [Zeile 511](../../PHP/gbdb_framework/.config/.framework.env.php#L511) |
| `sql_dev_password()` | `public static function sql_dev_password() :string` | [Zeile 515](../../PHP/gbdb_framework/.config/.framework.env.php#L515) |
| `reCaptcha_website_key()` | `public static function reCaptcha_website_key() :string` | [Zeile 519](../../PHP/gbdb_framework/.config/.framework.env.php#L519) |
| `reCaptcha_secret_key()` | `public static function reCaptcha_secret_key() :string` | [Zeile 523](../../PHP/gbdb_framework/.config/.framework.env.php#L523) |
| `crypt_data()` | `public static function crypt_data() :bool` | [Zeile 527](../../PHP/gbdb_framework/.config/.framework.env.php#L527) |
| `cryptKey()` | `public static function cryptKey() :string` | [Zeile 531](../../PHP/gbdb_framework/.config/.framework.env.php#L531) |
| `data_extension()` | `public static function data_extension() :string` | [Zeile 535](../../PHP/gbdb_framework/.config/.framework.env.php#L535) |
| `init_cookies()` | `public static function init_cookies() :array` | [Zeile 539](../../PHP/gbdb_framework/.config/.framework.env.php#L539) |
| `init_session()` | `public static function init_session() :array` | [Zeile 543](../../PHP/gbdb_framework/.config/.framework.env.php#L543) |
| `EQR_API_URL()` | `public static function EQR_API_URL() :string` | [Zeile 547](../../PHP/gbdb_framework/.config/.framework.env.php#L547) |
| `EQR_API_AUTH()` | `public static function EQR_API_AUTH() :string` | [Zeile 551](../../PHP/gbdb_framework/.config/.framework.env.php#L551) |
| `this_file()` | `public static function this_file() :string` | [Zeile 559](../../PHP/gbdb_framework/.config/.framework.env.php#L559) |
| `this_path()` | `public static function this_path() :string` | [Zeile 563](../../PHP/gbdb_framework/.config/.framework.env.php#L563) |
| `this_uri()` | `public static function this_uri() :string` | [Zeile 567](../../PHP/gbdb_framework/.config/.framework.env.php#L567) |
| `client_ip()` | `public static function client_ip() :string` | [Zeile 576](../../PHP/gbdb_framework/.config/.framework.env.php#L576) |
| `data_root()` | `public static function data_root() :string` | [Zeile 580](../../PHP/gbdb_framework/.config/.framework.env.php#L580) |
| `DB_PATH()` | `public static function DB_PATH() :string` | [Zeile 584](../../PHP/gbdb_framework/.config/.framework.env.php#L584) |
| `jpretty()` | `public static function jpretty() :int` | [Zeile 588](../../PHP/gbdb_framework/.config/.framework.env.php#L588) |
| `framework_version()` | `public static function framework_version() :string` | [Zeile 592](../../PHP/gbdb_framework/.config/.framework.env.php#L592) |

## Globale Funktionen

Funktionen · Quelle: [autoloader.php](../../PHP/gbdb_framework/autoloader.php#L18)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `mb_strtolower()` | `function mb_strtolower(string $string, ?string $encoding = null) :string` | [Zeile 18](../../PHP/gbdb_framework/autoloader.php#L18) |
| `mb_stripos()` | `function mb_stripos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null) :int\|false` | [Zeile 25](../../PHP/gbdb_framework/autoloader.php#L25) |
| `mb_strlen()` | `function mb_strlen(string $string, ?string $encoding = null) :int` | [Zeile 32](../../PHP/gbdb_framework/autoloader.php#L32) |
| `gbdb_loadLocal()` | `function gbdb_loadLocal(string $folder, array $skip = []) :void` | [Zeile 45](../../PHP/gbdb_framework/autoloader.php#L45) |

## GBDBAuthEngine

Klasse · Quelle: [core/auth.php](../../PHP/gbdb_framework/core/auth.php#L4)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `setSrvUsage()` | `public static function setSrvUsage(bool $usage) :void` | [Zeile 19](../../PHP/gbdb_framework/core/auth.php#L19) |
| `setReturnInstance()` | `public static function setReturnInstance(string $instance) :void` | [Zeile 491](../../PHP/gbdb_framework/core/auth.php#L491) |
| `createStructure()` | `public static function createStructure() :array` | [Zeile 500](../../PHP/gbdb_framework/core/auth.php#L500) |
| `hashPass()` | `public static function hashPass(string $password) :string` | [Zeile 616](../../PHP/gbdb_framework/core/auth.php#L616) |
| `logout()` | `public static function logout(string $error = "", string $jwt = "") :array` | [Zeile 627](../../PHP/gbdb_framework/core/auth.php#L627) |
| `init()` | `public static function init() :array` | [Zeile 666](../../PHP/gbdb_framework/core/auth.php#L666) |
| `login()` | `public static function login(string $usernameOrEmail, string $passwordPlainText) :array` | [Zeile 763](../../PHP/gbdb_framework/core/auth.php#L763) |
| `user_registration()` | `public static function user_registration( string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "", bool $verifyMail = true ) :array` | [Zeile 876](../../PHP/gbdb_framework/core/auth.php#L876) |
| `verify_email()` | `public static function verify_email(string $token) :array` | [Zeile 988](../../PHP/gbdb_framework/core/auth.php#L988) |
| `resendVerification()` | `public static function resendVerification(string $usernameOrEmail) :array` | [Zeile 1032](../../PHP/gbdb_framework/core/auth.php#L1032) |
| `verify_2fa_code()` | `public static function verify_2fa_code(string\|int $code) :array` | [Zeile 1063](../../PHP/gbdb_framework/core/auth.php#L1063) |
| `edit_user()` | `public static function edit_user( string $uid, string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "", string $text = "" ) :array` | [Zeile 1142](../../PHP/gbdb_framework/core/auth.php#L1142) |
| `delete_user()` | `public static function delete_user(string $uid) :array` | [Zeile 1257](../../PHP/gbdb_framework/core/auth.php#L1257) |
| `get_user()` | `public static function get_user(string $uid) :array` | [Zeile 1290](../../PHP/gbdb_framework/core/auth.php#L1290) |
| `get_jwt_user()` | `public static function get_jwt_user(string $jwt) :array` | [Zeile 1326](../../PHP/gbdb_framework/core/auth.php#L1326) |
| `getUsers()` | `public static function getUsers(int $limit = 10000000) :array` | [Zeile 1378](../../PHP/gbdb_framework/core/auth.php#L1378) |

## Cache

Klasse · Quelle: [core/cache.php](../../PHP/gbdb_framework/core/cache.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `load()` | `public static function load(string $db, string $table, mixed $cache) :array` | [Zeile 111](../../PHP/gbdb_framework/core/cache.php#L111) |
| `update()` | `public static function update(string $db, string $table) :void` | [Zeile 154](../../PHP/gbdb_framework/core/cache.php#L154) |
| `clear()` | `public static function clear(string $table, ?string $db = null) :void` | [Zeile 189](../../PHP/gbdb_framework/core/cache.php#L189) |
| `flush()` | `public static function flush() :void` | [Zeile 209](../../PHP/gbdb_framework/core/cache.php#L209) |
| `exists()` | `public static function exists(string $table, ?string $db = null) :bool` | [Zeile 228](../../PHP/gbdb_framework/core/cache.php#L228) |
| `get()` | `public static function get(string $db, string $table) :array` | [Zeile 251](../../PHP/gbdb_framework/core/cache.php#L251) |
| `updateKey()` | `public static function updateKey(string $db, string $table) :string` | [Zeile 270](../../PHP/gbdb_framework/core/cache.php#L270) |
| `all()` | `public static function all() :array` | [Zeile 287](../../PHP/gbdb_framework/core/cache.php#L287) |
| `updates()` | `public static function updates() :array` | [Zeile 298](../../PHP/gbdb_framework/core/cache.php#L298) |

## Converter

Klasse · Quelle: [core/converter.php](../../PHP/gbdb_framework/core/converter.php#L3)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## Cookie

Klasse · Quelle: [core/cookies.php](../../PHP/gbdb_framework/core/cookies.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `set()` | `public static function set(string $name, string $value, int $expiration = self::DUR) :void` | [Zeile 54](../../PHP/gbdb_framework/core/cookies.php#L54) |
| `setSecure()` | `public static function setSecure(string $name, string $value, int $expiration = self::DUR) :void` | [Zeile 65](../../PHP/gbdb_framework/core/cookies.php#L65) |
| `add()` | `public static function add(string $name, string $value) :void` | [Zeile 85](../../PHP/gbdb_framework/core/cookies.php#L85) |
| `get()` | `public static function get(string $name) :mixed` | [Zeile 96](../../PHP/gbdb_framework/core/cookies.php#L96) |
| `delete()` | `public static function delete(string $name) :void` | [Zeile 105](../../PHP/gbdb_framework/core/cookies.php#L105) |
| `edit()` | `public static function edit(string $name, string $value) :void` | [Zeile 126](../../PHP/gbdb_framework/core/cookies.php#L126) |
| `refresh()` | `public static function refresh(int $thresholdSeconds = 3600) :void` | [Zeile 135](../../PHP/gbdb_framework/core/cookies.php#L135) |
| `init()` | `public static function init() :void` | [Zeile 152](../../PHP/gbdb_framework/core/cookies.php#L152) |
| `exists()` | `public static function exists(string $name) :bool` | [Zeile 166](../../PHP/gbdb_framework/core/cookies.php#L166) |

## Crypt

Klasse · Quelle: [core/crypt.php](../../PHP/gbdb_framework/core/crypt.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `encode()` | `public static function encode(string $data) :string` | [Zeile 21](../../PHP/gbdb_framework/core/crypt.php#L21) |
| `decode()` | `public static function decode(string $data) :?string` | [Zeile 53](../../PHP/gbdb_framework/core/crypt.php#L53) |

## DatabaseBridge

Klasse · Quelle: [core/database_bridge.php](../../PHP/gbdb_framework/core/database_bridge.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `setDriver()` | `public static function setDriver(string $driver) :void` | [Zeile 12](../../PHP/gbdb_framework/core/database_bridge.php#L12) |
| `setInstance()` | `public static function setInstance(string $instance) :void` | [Zeile 26](../../PHP/gbdb_framework/core/database_bridge.php#L26) |
| `get()` | `public static function get(string $db, string $table, bool $filter = false, string $where = "", mixed $is = "") :mixed` | [Zeile 84](../../PHP/gbdb_framework/core/database_bridge.php#L84) |
| `insert()` | `public static function insert(string $db, string $table, array $data) :mixed` | [Zeile 119](../../PHP/gbdb_framework/core/database_bridge.php#L119) |
| `delete()` | `public static function delete(string $db, string $table, string $where, mixed $is) :mixed` | [Zeile 149](../../PHP/gbdb_framework/core/database_bridge.php#L149) |
| `update()` | `public static function update(string $db, string $table, string $where, mixed $is, array $data) :mixed` | [Zeile 180](../../PHP/gbdb_framework/core/database_bridge.php#L180) |
| `createDatabase()` | `public static function createDatabase(string $name) :bool` | [Zeile 207](../../PHP/gbdb_framework/core/database_bridge.php#L207) |
| `deleteDatabase()` | `public static function deleteDatabase(string $name) :bool` | [Zeile 226](../../PHP/gbdb_framework/core/database_bridge.php#L226) |
| `createTable()` | `public static function createTable(string $db, string $table, array $columns) :bool` | [Zeile 247](../../PHP/gbdb_framework/core/database_bridge.php#L247) |
| `deleteTable()` | `public static function deleteTable(string $db, string $table) :bool` | [Zeile 267](../../PHP/gbdb_framework/core/database_bridge.php#L267) |
| `addColumn()` | `public static function addColumn(string $db, string $table, string $column, mixed $default = "") :bool` | [Zeile 289](../../PHP/gbdb_framework/core/database_bridge.php#L289) |
| `createIndex()` | `public static function createIndex(string $db, string $table, string $column) :bool` | [Zeile 310](../../PHP/gbdb_framework/core/database_bridge.php#L310) |
| `begin()` | `public static function begin() :bool` | [Zeile 324](../../PHP/gbdb_framework/core/database_bridge.php#L324) |
| `commit()` | `public static function commit() :bool` | [Zeile 338](../../PHP/gbdb_framework/core/database_bridge.php#L338) |
| `rollback()` | `public static function rollback() :bool` | [Zeile 352](../../PHP/gbdb_framework/core/database_bridge.php#L352) |

## FileTool

Klasse · Quelle: [core/file_tool.php](../../PHP/gbdb_framework/core/file_tool.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `exists()` | `public static function exists(string $path) :bool` | [Zeile 9](../../PHP/gbdb_framework/core/file_tool.php#L9) |
| `read()` | `public static function read(string $path) :string` | [Zeile 18](../../PHP/gbdb_framework/core/file_tool.php#L18) |
| `write()` | `public static function write(string $path, string $content) :bool` | [Zeile 34](../../PHP/gbdb_framework/core/file_tool.php#L34) |
| `readJson()` | `public static function readJson(string $path) :array` | [Zeile 64](../../PHP/gbdb_framework/core/file_tool.php#L64) |
| `writeJson()` | `public static function writeJson(string $path, array $data) :bool` | [Zeile 87](../../PHP/gbdb_framework/core/file_tool.php#L87) |
| `delete()` | `public static function delete(string $path) :bool` | [Zeile 109](../../PHP/gbdb_framework/core/file_tool.php#L109) |
| `copyDir()` | `public static function copyDir(string $src, string $dest) :void` | [Zeile 119](../../PHP/gbdb_framework/core/file_tool.php#L119) |
| `deleteOldFiles()` | `public static function deleteOldFiles(string $dir, int $days) :void` | [Zeile 152](../../PHP/gbdb_framework/core/file_tool.php#L152) |
| `dirSize()` | `public static function dirSize(string $dir) :float` | [Zeile 171](../../PHP/gbdb_framework/core/file_tool.php#L171) |
| `listFiles()` | `public static function listFiles(string $dir, string $ext = '') :array` | [Zeile 193](../../PHP/gbdb_framework/core/file_tool.php#L193) |
| `backupDir()` | `public static function backupDir(string $src, string $dest) :bool` | [Zeile 228](../../PHP/gbdb_framework/core/file_tool.php#L228) |
| `listDirs()` | `public static function listDirs(string $path) :array` | [Zeile 257](../../PHP/gbdb_framework/core/file_tool.php#L257) |

## Format

Klasse · Quelle: [core/format.php](../../PHP/gbdb_framework/core/format.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `dateForInput()` | `public static function dateForInput(mixed $date) :string` | [Zeile 19](../../PHP/gbdb_framework/core/format.php#L19) |
| `timeForInput()` | `public static function timeForInput(mixed $time) :string` | [Zeile 32](../../PHP/gbdb_framework/core/format.php#L32) |
| `dateToView()` | `public static function dateToView(mixed $date) :string` | [Zeile 45](../../PHP/gbdb_framework/core/format.php#L45) |
| `shortString()` | `public static function shortString( string $string, int $maxLength = 14 ) :string` | [Zeile 59](../../PHP/gbdb_framework/core/format.php#L59) |
| `cleanString()` | `public static function cleanString(string $string) :string` | [Zeile 81](../../PHP/gbdb_framework/core/format.php#L81) |
| `newLineCode()` | `public static function newLineCode(string $string, bool $forHtml = true) :string` | [Zeile 91](../../PHP/gbdb_framework/core/format.php#L91) |

## GBDBQueryBuilder

Klasse · Quelle: [core/gbdb_query_builder.php](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L14)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `table()` | `public static function table(string $database, string $table) :self` | [Zeile 22](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L22) |
| `where()` | `public function where(string $column, mixed $value, string $operator = '=') :self` | [Zeile 30](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L30) |
| `orderBy()` | `public function orderBy(string $column, string $direction = 'ASC') :self` | [Zeile 36](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L36) |
| `limit()` | `public function limit(int $limit, int $offset = 0) :self` | [Zeile 43](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L43) |
| `get()` | `public function get() :array` | [Zeile 50](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L50) |
| `first()` | `public function first() :?array` | [Zeile 75](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L75) |
| `insert()` | `public function insert(array $row) :int` | [Zeile 86](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L86) |
| `update()` | `public function update(array $data) :array` | [Zeile 90](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L90) |
| `delete()` | `public function delete() :array` | [Zeile 107](../../PHP/gbdb_framework/core/gbdb_query_builder.php#L107) |

## GBDBStorage

Klasse · Quelle: [core/gbdb_storage.php](../../PHP/gbdb_framework/core/gbdb_storage.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `atomicWrite()` | `public static function atomicWrite(string $file, string $payload) :bool` | [Zeile 21](../../PHP/gbdb_framework/core/gbdb_storage.php#L21) |
| `appendLine()` | `public static function appendLine(string $file, string $line) :bool` | [Zeile 84](../../PHP/gbdb_framework/core/gbdb_storage.php#L84) |
| `wal()` | `public static function wal(string $appendFile, array $op, string $state, string $tx) :bool` | [Zeile 132](../../PHP/gbdb_framework/core/gbdb_storage.php#L132) |
| `journalChecksum()` | `public static function journalChecksum(array $entry) :string` | [Zeile 157](../../PHP/gbdb_framework/core/gbdb_storage.php#L157) |
| `journal()` | `public static function journal(string $file, array $entry) :bool` | [Zeile 170](../../PHP/gbdb_framework/core/gbdb_storage.php#L170) |
| `readJournal()` | `public static function readJournal(string $file) :array` | [Zeile 191](../../PHP/gbdb_framework/core/gbdb_storage.php#L191) |
| `encodeLine()` | `public static function encodeLine(string $json) :string` | [Zeile 242](../../PHP/gbdb_framework/core/gbdb_storage.php#L242) |
| `normalizeMeta()` | `public static function normalizeMeta(array $meta = []) :array` | [Zeile 255](../../PHP/gbdb_framework/core/gbdb_storage.php#L255) |
| `touchMeta()` | `public static function touchMeta(array $meta, bool $bumpVersion = true) :array` | [Zeile 299](../../PHP/gbdb_framework/core/gbdb_storage.php#L299) |
| `shouldCompact()` | `public static function shouldCompact(array $meta, string $appendFile) :bool` | [Zeile 316](../../PHP/gbdb_framework/core/gbdb_storage.php#L316) |
| `checksum()` | `public static function checksum(array $rows) :string` | [Zeile 346](../../PHP/gbdb_framework/core/gbdb_storage.php#L346) |
| `indexFile()` | `public static function indexFile(string $dataFile, string $column) :string` | [Zeile 358](../../PHP/gbdb_framework/core/gbdb_storage.php#L358) |
| `buildIndex()` | `public static function buildIndex(array $rows, string $column) :array` | [Zeile 370](../../PHP/gbdb_framework/core/gbdb_storage.php#L370) |
| `writeIndex()` | `public static function writeIndex(string $dataFile, string $column, array $rows) :bool` | [Zeile 405](../../PHP/gbdb_framework/core/gbdb_storage.php#L405) |
| `deleteIndex()` | `public static function deleteIndex(string $dataFile, string $column) :bool` | [Zeile 429](../../PHP/gbdb_framework/core/gbdb_storage.php#L429) |
| `validateConstraints()` | `public static function validateConstraints(array $rows, array $candidate, array $constraints, ?int $excludeId = null) :bool` | [Zeile 443](../../PHP/gbdb_framework/core/gbdb_storage.php#L443) |
| `indexKey()` | `public static function indexKey(mixed $value) :string` | [Zeile 493](../../PHP/gbdb_framework/core/gbdb_storage.php#L493) |
| `rebuildIndexes()` | `public static function rebuildIndexes(string $dataFile, array $meta, array $rows) :void` | [Zeile 516](../../PHP/gbdb_framework/core/gbdb_storage.php#L516) |
| `snapshot()` | `public static function snapshot(string $dataFile, array $extraFiles = [], string $reason = "manual") :string` | [Zeile 536](../../PHP/gbdb_framework/core/gbdb_storage.php#L536) |
| `deleteTableArtifacts()` | `public static function deleteTableArtifacts(string $dataFile) :void` | [Zeile 582](../../PHP/gbdb_framework/core/gbdb_storage.php#L582) |
| `deleteDir()` | `public static function deleteDir(string $dir) :bool` | [Zeile 634](../../PHP/gbdb_framework/core/gbdb_storage.php#L634) |
| `decodeLine()` | `public static function decodeLine(string $line) :?string` | [Zeile 667](../../PHP/gbdb_framework/core/gbdb_storage.php#L667) |
| `readWal()` | `public static function readWal(string $appendFile) :array` | [Zeile 688](../../PHP/gbdb_framework/core/gbdb_storage.php#L688) |
| `recoverWal()` | `public static function recoverWal(string $appendFile) :array` | [Zeile 746](../../PHP/gbdb_framework/core/gbdb_storage.php#L746) |
| `restoreSnapshot()` | `public static function restoreSnapshot(string $dataFile, string $snapshotId) :bool` | [Zeile 823](../../PHP/gbdb_framework/core/gbdb_storage.php#L823) |
| `indexLookup()` | `public static function indexLookup(string $dataFile, string $column, mixed $value) :array` | [Zeile 866](../../PHP/gbdb_framework/core/gbdb_storage.php#L866) |
| `advancedIndexDir()` | `public static function advancedIndexDir(string $dataFile) :string` | [Zeile 903](../../PHP/gbdb_framework/core/gbdb_storage.php#L903) |
| `normalizeIndexDefinition()` | `public static function normalizeIndexDefinition(string $name, string $type, array $columns, array $options = []) :array` | [Zeile 915](../../PHP/gbdb_framework/core/gbdb_storage.php#L915) |
| `indexName()` | `public static function indexName(string $type, array $columns) :string` | [Zeile 951](../../PHP/gbdb_framework/core/gbdb_storage.php#L951) |
| `advancedIndexFile()` | `public static function advancedIndexFile(string $dataFile, string $name) :string` | [Zeile 961](../../PHP/gbdb_framework/core/gbdb_storage.php#L961) |
| `advancedIndexMetaFile()` | `public static function advancedIndexMetaFile(string $dataFile) :string` | [Zeile 972](../../PHP/gbdb_framework/core/gbdb_storage.php#L972) |
| `compositeIndexKey()` | `public static function compositeIndexKey(array $row, array $columns) :string` | [Zeile 982](../../PHP/gbdb_framework/core/gbdb_storage.php#L982) |
| `tokenizeFulltext()` | `public static function tokenizeFulltext(string $text) :array` | [Zeile 997](../../PHP/gbdb_framework/core/gbdb_storage.php#L997) |
| `buildAdvancedIndex()` | `public static function buildAdvancedIndex(array $rows, array $definition) :array` | [Zeile 1058](../../PHP/gbdb_framework/core/gbdb_storage.php#L1058) |
| `writeAdvancedIndex()` | `public static function writeAdvancedIndex(string $dataFile, array $definition, array $rows) :bool` | [Zeile 1139](../../PHP/gbdb_framework/core/gbdb_storage.php#L1139) |
| `readAdvancedIndex()` | `public static function readAdvancedIndex(string $dataFile, string $name) :array` | [Zeile 1164](../../PHP/gbdb_framework/core/gbdb_storage.php#L1164) |
| `writeAdvancedIndexMeta()` | `public static function writeAdvancedIndexMeta(string $dataFile, array $definitions) :bool` | [Zeile 1174](../../PHP/gbdb_framework/core/gbdb_storage.php#L1174) |
| `advancedIndexLookup()` | `public static function advancedIndexLookup(string $dataFile, array $definition, mixed $value) :array` | [Zeile 1188](../../PHP/gbdb_framework/core/gbdb_storage.php#L1188) |
| `pageSizes()` | `public static function pageSizes() :array` | [Zeile 1225](../../PHP/gbdb_framework/core/gbdb_storage.php#L1225) |
| `normalizePageSize()` | `public static function normalizePageSize(int $pageSize) :int` | [Zeile 1234](../../PHP/gbdb_framework/core/gbdb_storage.php#L1234) |
| `storageDir()` | `public static function storageDir(string $dataFile) :string` | [Zeile 1243](../../PHP/gbdb_framework/core/gbdb_storage.php#L1243) |
| `initStorage()` | `public static function initStorage(string $dataFile, array $rows, array $meta = []) :array` | [Zeile 1254](../../PHP/gbdb_framework/core/gbdb_storage.php#L1254) |
| `syncStorage()` | `public static function syncStorage(string $dataFile, array $rows, array $meta = [], string $reason = "sync") :array` | [Zeile 1281](../../PHP/gbdb_framework/core/gbdb_storage.php#L1281) |
| `appendStorageJournal()` | `public static function appendStorageJournal(string $dataFile, array $op) :bool` | [Zeile 1504](../../PHP/gbdb_framework/core/gbdb_storage.php#L1504) |
| `markTombstones()` | `public static function markTombstones(string $dataFile, array $ids) :bool` | [Zeile 1532](../../PHP/gbdb_framework/core/gbdb_storage.php#L1532) |
| `verifyStorage()` | `public static function verifyStorage(string $dataFile) :array` | [Zeile 1577](../../PHP/gbdb_framework/core/gbdb_storage.php#L1577) |
| `repairStorage()` | `public static function repairStorage(string $dataFile, array $rows, array $meta = []) :array` | [Zeile 1667](../../PHP/gbdb_framework/core/gbdb_storage.php#L1667) |
| `vacuumStorage()` | `public static function vacuumStorage(string $dataFile, array $rows, array $meta = []) :array` | [Zeile 1704](../../PHP/gbdb_framework/core/gbdb_storage.php#L1704) |

## GetForm

Klasse · Quelle: [core/getForm.php](../../PHP/gbdb_framework/core/getForm.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `getDropdown()` | `public static function getDropdown(mixed $dropdown) :mixed` | [Zeile 9](../../PHP/gbdb_framework/core/getForm.php#L9) |
| `upload()` | `public static function upload(mixed $file, string $path = "./", string $useName = "") :bool` | [Zeile 26](../../PHP/gbdb_framework/core/getForm.php#L26) |
| `check_required_fields()` | `public static function check_required_fields(mixed $post_data) :mixed` | [Zeile 99](../../PHP/gbdb_framework/core/getForm.php#L99) |
| `createInput()` | `public static function createInput(string $name, string $type, mixed $form_data, string $placeholder = "", string $class = "", string $id = "") :string` | [Zeile 125](../../PHP/gbdb_framework/core/getForm.php#L125) |
| `checkPost()` | `public static function checkPost() :bool` | [Zeile 147](../../PHP/gbdb_framework/core/getForm.php#L147) |

## Http

Klasse · Quelle: [core/http.php](../../PHP/gbdb_framework/core/http.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get(string $url, array $headers = [], int $timeout = 10) :string\|false` | [Zeile 11](../../PHP/gbdb_framework/core/http.php#L11) |
| `post()` | `public static function post(string $url, array $data = [], array $headers = [], int $timeout = 10) :string\|false` | [Zeile 53](../../PHP/gbdb_framework/core/http.php#L53) |
| `sendMail()` | `public static function sendMail(array $mail) :bool\|array` | [Zeile 102](../../PHP/gbdb_framework/core/http.php#L102) |
| `jsonResponse()` | `public static function jsonResponse(array $data, int $status = 200) :void` | [Zeile 140](../../PHP/gbdb_framework/core/http.php#L140) |
| `redirect()` | `public static function redirect(string $url, int $status = 302) :void` | [Zeile 155](../../PHP/gbdb_framework/core/http.php#L155) |
| `getHeaders()` | `public static function getHeaders() :array` | [Zeile 166](../../PHP/gbdb_framework/core/http.php#L166) |
| `method()` | `public static function method() :string` | [Zeile 189](../../PHP/gbdb_framework/core/http.php#L189) |
| `isJson()` | `public static function isJson() :bool` | [Zeile 197](../../PHP/gbdb_framework/core/http.php#L197) |
| `jsonInput()` | `public static function jsonInput() :array` | [Zeile 207](../../PHP/gbdb_framework/core/http.php#L207) |

## Json

Klasse · Quelle: [core/json.php](../../PHP/gbdb_framework/core/json.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `decode()` | `public static function decode(string $json, bool $assoc = false) :mixed` | [Zeile 10](../../PHP/gbdb_framework/core/json.php#L10) |
| `encode()` | `public static function encode(mixed $data, bool $pretty = false) :string` | [Zeile 32](../../PHP/gbdb_framework/core/json.php#L32) |
| `isJson()` | `public static function isJson(string $json) :bool` | [Zeile 55](../../PHP/gbdb_framework/core/json.php#L55) |
| `loop()` | `public static function loop(mixed $data, callable $callback) :mixed` | [Zeile 71](../../PHP/gbdb_framework/core/json.php#L71) |
| `elementExists()` | `public static function elementExists(mixed $data, string $key) :bool` | [Zeile 91](../../PHP/gbdb_framework/core/json.php#L91) |
| `getElement()` | `public static function getElement(mixed $data, string $key) :mixed` | [Zeile 109](../../PHP/gbdb_framework/core/json.php#L109) |

## ReCaptcha

Klasse · Quelle: [core/recaptcha.php](../../PHP/gbdb_framework/core/recaptcha.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `loadJsApi()` | `public function loadJsApi() :void` | [Zeile 8](../../PHP/gbdb_framework/core/recaptcha.php#L8) |
| `postName()` | `public static function postName() :string` | [Zeile 16](../../PHP/gbdb_framework/core/recaptcha.php#L16) |
| `checkBox()` | `public static function checkBox(string $callbackJs = "") :string` | [Zeile 25](../../PHP/gbdb_framework/core/recaptcha.php#L25) |
| `verify()` | `public static function verify(?string $token) :bool` | [Zeile 37](../../PHP/gbdb_framework/core/recaptcha.php#L37) |

## Ref

Klasse · Quelle: [core/ref.php](../../PHP/gbdb_framework/core/ref.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `to()` | `public static function to(string $url) :void` | [Zeile 9](../../PHP/gbdb_framework/core/ref.php#L9) |
| `this_file()` | `public static function this_file() :void` | [Zeile 19](../../PHP/gbdb_framework/core/ref.php#L19) |

## Route

Klasse · Quelle: [core/route.php](../../PHP/gbdb_framework/core/route.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `middleware()` | `public static function middleware(callable $mw) :self` | [Zeile 15](../../PHP/gbdb_framework/core/route.php#L15) |
| `get()` | `public static function get(string $path, callable $callback) :void` | [Zeile 27](../../PHP/gbdb_framework/core/route.php#L27) |
| `post()` | `public static function post(string $path, callable $callback) :void` | [Zeile 37](../../PHP/gbdb_framework/core/route.php#L37) |
| `put()` | `public static function put(string $path, callable $callback) :void` | [Zeile 47](../../PHP/gbdb_framework/core/route.php#L47) |
| `delete()` | `public static function delete(string $path, callable $callback) :void` | [Zeile 57](../../PHP/gbdb_framework/core/route.php#L57) |
| `patch()` | `public static function patch(string $path, callable $callback) :void` | [Zeile 67](../../PHP/gbdb_framework/core/route.php#L67) |
| `notFound()` | `public static function notFound(callable $callback) :void` | [Zeile 76](../../PHP/gbdb_framework/core/route.php#L76) |
| `methodNotAllowed()` | `public static function methodNotAllowed(callable $callback) :void` | [Zeile 85](../../PHP/gbdb_framework/core/route.php#L85) |
| `dispatch()` | `public static function dispatch() :void` | [Zeile 102](../../PHP/gbdb_framework/core/route.php#L102) |

## Session

Klasse · Quelle: [core/session.php](../../PHP/gbdb_framework/core/session.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `handler()` | `public static function handler() :void` | [Zeile 8](../../PHP/gbdb_framework/core/session.php#L8) |
| `init()` | `public static function init() :void` | [Zeile 46](../../PHP/gbdb_framework/core/session.php#L46) |
| `get()` | `public static function get(string $name) :mixed` | [Zeile 57](../../PHP/gbdb_framework/core/session.php#L57) |
| `set()` | `public static function set(string $name, mixed $value) :void` | [Zeile 67](../../PHP/gbdb_framework/core/session.php#L67) |
| `exists()` | `public static function exists(string $name) :bool` | [Zeile 76](../../PHP/gbdb_framework/core/session.php#L76) |
| `delete()` | `public static function delete(string $name) :void` | [Zeile 85](../../PHP/gbdb_framework/core/session.php#L85) |
| `destroy()` | `public static function destroy() :void` | [Zeile 93](../../PHP/gbdb_framework/core/session.php#L93) |

## SQL

Klasse · Quelle: [core/sql.php](../../PHP/gbdb_framework/core/sql.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `connect()` | `public static function connect() :bool` | [Zeile 9](../../PHP/gbdb_framework/core/sql.php#L9) |
| `query()` | `public static function query(string $query, array $parameters = []) :array\|bool` | [Zeile 82](../../PHP/gbdb_framework/core/sql.php#L82) |
| `select()` | `public static function select(string $table, string $select = '*', string $where = '', mixed $is = '') :array\|bool` | [Zeile 99](../../PHP/gbdb_framework/core/sql.php#L99) |
| `insert()` | `public static function insert(string $table, array $data) :bool` | [Zeile 109](../../PHP/gbdb_framework/core/sql.php#L109) |
| `update()` | `public static function update(string $table, array $data, string $where, mixed $is) :bool` | [Zeile 120](../../PHP/gbdb_framework/core/sql.php#L120) |
| `delete()` | `public static function delete(string $table, string $where, mixed $is) :bool` | [Zeile 131](../../PHP/gbdb_framework/core/sql.php#L131) |

## Time

Klasse · Quelle: [core/time.php](../../PHP/gbdb_framework/core/time.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `timeAgo()` | `public static function timeAgo(mixed $timestamp) :string` | [Zeile 9](../../PHP/gbdb_framework/core/time.php#L9) |

## Tools

Klasse · Quelle: [core/tools.php](../../PHP/gbdb_framework/core/tools.php#L7)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `generatePassword()` | `public static function generatePassword(int $length) :string` | [Zeile 13](../../PHP/gbdb_framework/core/tools.php#L13) |
| `testPasswordStrength()` | `public static function testPasswordStrength(string $password) :string` | [Zeile 35](../../PHP/gbdb_framework/core/tools.php#L35) |
| `getDomainInfo()` | `public static function getDomainInfo(string $domain) :mixed` | [Zeile 60](../../PHP/gbdb_framework/core/tools.php#L60) |
| `generateId()` | `public static function generateId() :int` | [Zeile 88](../../PHP/gbdb_framework/core/tools.php#L88) |
| `generateToken()` | `public static function generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4) :array` | [Zeile 122](../../PHP/gbdb_framework/core/tools.php#L122) |
| `getIpCountry()` | `public static function getIpCountry(string $ip) :string` | [Zeile 131](../../PHP/gbdb_framework/core/tools.php#L131) |
| `ping4()` | `public static function ping4(string $ip) :string` | [Zeile 158](../../PHP/gbdb_framework/core/tools.php#L158) |
| `ping6()` | `public static function ping6(string $ip) :string` | [Zeile 183](../../PHP/gbdb_framework/core/tools.php#L183) |
| `qr()` | `public static function qr(string $value, int $width, int $height) :string` | [Zeile 212](../../PHP/gbdb_framework/core/tools.php#L212) |
| `bar()` | `public static function bar(string $value, int $width, int $height = 175) :string` | [Zeile 230](../../PHP/gbdb_framework/core/tools.php#L230) |

## Validate

Klasse · Quelle: [core/validate.php](../../PHP/gbdb_framework/core/validate.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `required()` | `public static function required(array $data, array $fields) :bool` | [Zeile 10](../../PHP/gbdb_framework/core/validate.php#L10) |
| `email()` | `public static function email(string $value) :bool` | [Zeile 29](../../PHP/gbdb_framework/core/validate.php#L29) |
| `number()` | `public static function number(string\|int\|float $value) :bool` | [Zeile 38](../../PHP/gbdb_framework/core/validate.php#L38) |
| `minLength()` | `public static function minLength(string $value, int $min) :bool` | [Zeile 50](../../PHP/gbdb_framework/core/validate.php#L50) |
| `maxLength()` | `public static function maxLength(string $value, int $max) :bool` | [Zeile 60](../../PHP/gbdb_framework/core/validate.php#L60) |
| `regex()` | `public static function regex(string $value, string $pattern) :bool` | [Zeile 70](../../PHP/gbdb_framework/core/validate.php#L70) |
| `between()` | `public static function between(float\|int $value, float\|int $min, float\|int $max) :bool` | [Zeile 91](../../PHP/gbdb_framework/core/validate.php#L91) |
| `in()` | `public static function in(string\|int $value, array $allowed) :bool` | [Zeile 101](../../PHP/gbdb_framework/core/validate.php#L101) |
| `match()` | `public static function match(string $a, string $b) :bool` | [Zeile 111](../../PHP/gbdb_framework/core/validate.php#L111) |
| `validateArray()` | `public static function validateArray(array $data, array $rules) :array` | [Zeile 121](../../PHP/gbdb_framework/core/validate.php#L121) |

## Globale Funktionen

Funktionen · Quelle: [functions.php](../../PHP/gbdb_framework/functions.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `resp()` | `function resp(int $status, mixed $data)` | [Zeile 3](../../PHP/gbdb_framework/functions.php#L3) |
| `test_param()` | `function test_param(array $params, array $body)` | [Zeile 15](../../PHP/gbdb_framework/functions.php#L15) |
| `general_auth()` | `function general_auth($body, $method)` | [Zeile 23](../../PHP/gbdb_framework/functions.php#L23) |
| `GBDB_DB_ARCH()` | `function GBDB_DB_ARCH() :string` | [Zeile 42](../../PHP/gbdb_framework/functions.php#L42) |
| `DB_DRIVER()` | `function DB_DRIVER(array $ctx = []) :string` | [Zeile 56](../../PHP/gbdb_framework/functions.php#L56) |
| `DB_CTX_FROM_BODY()` | `function DB_CTX_FROM_BODY(array $body) :array` | [Zeile 69](../../PHP/gbdb_framework/functions.php#L69) |
| `DB_GET()` | `function DB_GET($db, $table, $filter = false, $where = "", $is = "", array $ctx = [])` | [Zeile 79](../../PHP/gbdb_framework/functions.php#L79) |
| `DB_PUT()` | `function DB_PUT($db, $table, $data, array $ctx = [])` | [Zeile 95](../../PHP/gbdb_framework/functions.php#L95) |
| `DB_EDIT()` | `function DB_EDIT($db, $table, $where, $is, $data, array $ctx = [])` | [Zeile 107](../../PHP/gbdb_framework/functions.php#L107) |
| `DB_DELETE()` | `function DB_DELETE($db, $table, $where, $is, array $ctx = [])` | [Zeile 119](../../PHP/gbdb_framework/functions.php#L119) |
| `DB_QUERY()` | `function DB_QUERY($query, array $ctx = [], array $params = [])` | [Zeile 131](../../PHP/gbdb_framework/functions.php#L131) |
| `_token_file_path()` | `function _token_file_path() :string` | [Zeile 145](../../PHP/gbdb_framework/functions.php#L145) |
| `_tkn_key()` | `function _tkn_key() :string` | [Zeile 149](../../PHP/gbdb_framework/functions.php#L149) |
| `_tkn_encrypt()` | `function _tkn_encrypt(string $plain) :string` | [Zeile 153](../../PHP/gbdb_framework/functions.php#L153) |
| `_tkn_decrypt()` | `function _tkn_decrypt(string $raw) :string` | [Zeile 171](../../PHP/gbdb_framework/functions.php#L171) |
| `read_tokens()` | `function read_tokens() :array` | [Zeile 206](../../PHP/gbdb_framework/functions.php#L206) |
| `add_token()` | `function add_token(string $token) :void` | [Zeile 257](../../PHP/gbdb_framework/functions.php#L257) |
| `test_token()` | `function test_token(string $token) :bool` | [Zeile 316](../../PHP/gbdb_framework/functions.php#L316) |
| `delete_token()` | `function delete_token(string $token) :void` | [Zeile 326](../../PHP/gbdb_framework/functions.php#L326) |

