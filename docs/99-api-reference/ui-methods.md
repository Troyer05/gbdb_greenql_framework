# Verwaltungsoberfläche – Helfer und Funktionen

GreenQLUIv2Helper und globale UI-Funktionen unterstützen Anmeldung, Berechtigungen, Seiten und Formulare. Dateien nicht allein zum Laden einer Funktion einbinden: Manche sind ausführbare Seiteneinstiege.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## Globale Funktionen

Funktionen · Quelle: [public/includes/_shared.php](../../PHP/gbdb_framework/public/includes/_shared.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_e()` | `function gbdbui_e(mixed $value) :string` | [Zeile 5](../../PHP/gbdb_framework/public/includes/_shared.php#L5) |
| `gbdbui_url()` | `function gbdbui_url(string $tool = '', array $params = []) :string` | [Zeile 13](../../PHP/gbdb_framework/public/includes/_shared.php#L13) |
| `gbdbui_asset()` | `function gbdbui_asset(string $asset) :string` | [Zeile 29](../../PHP/gbdb_framework/public/includes/_shared.php#L29) |
| `gbdbui_framework_version()` | `function gbdbui_framework_version() :string` | [Zeile 39](../../PHP/gbdb_framework/public/includes/_shared.php#L39) |
| `gbdbui_update_banner()` | `function gbdbui_update_banner(string $active) :void` | [Zeile 51](../../PHP/gbdb_framework/public/includes/_shared.php#L51) |
| `gbdbui_navigation()` | `function gbdbui_navigation() :array` | [Zeile 110](../../PHP/gbdb_framework/public/includes/_shared.php#L110) |
| `gbdbui_icon()` | `function gbdbui_icon(string $name, int $size = 20) :string` | [Zeile 147](../../PHP/gbdb_framework/public/includes/_shared.php#L147) |
| `gbdbui_notifications()` | `function gbdbui_notifications() :array` | [Zeile 195](../../PHP/gbdb_framework/public/includes/_shared.php#L195) |
| `gbdbui_layout_start()` | `function gbdbui_layout_start(string $active, string $title, string $subtitle = '', array $options = []) :void` | [Zeile 252](../../PHP/gbdb_framework/public/includes/_shared.php#L252) |
| `gbdbui_layout_end()` | `function gbdbui_layout_end() :void` | [Zeile 384](../../PHP/gbdb_framework/public/includes/_shared.php#L384) |
| `gbdbui_nav()` | `function gbdbui_nav(string $active = '') :void` | [Zeile 399](../../PHP/gbdb_framework/public/includes/_shared.php#L399) |
| `gbdbui_flash()` | `function gbdbui_flash(string $type, string $text) :void` | [Zeile 413](../../PHP/gbdb_framework/public/includes/_shared.php#L413) |
| `gbdbui_flashes()` | `function gbdbui_flashes() :array` | [Zeile 421](../../PHP/gbdb_framework/public/includes/_shared.php#L421) |
| `gbdbui_render_flashes()` | `function gbdbui_render_flashes() :void` | [Zeile 432](../../PHP/gbdb_framework/public/includes/_shared.php#L432) |
| `gbdbui_can_tool()` | `function gbdbui_can_tool(string $tool) :bool` | [Zeile 440](../../PHP/gbdb_framework/public/includes/_shared.php#L440) |
| `gbdbui_cache_get()` | `function gbdbui_cache_get(string $key, int $ttl = 10) :mixed` | [Zeile 452](../../PHP/gbdb_framework/public/includes/_shared.php#L452) |
| `gbdbui_cache_set()` | `function gbdbui_cache_set(string $key, mixed $value) :mixed` | [Zeile 473](../../PHP/gbdb_framework/public/includes/_shared.php#L473) |
| `gbdbui_list_dbs_cached()` | `function gbdbui_list_dbs_cached(?string $instance = null, int $ttl = 10) :array` | [Zeile 485](../../PHP/gbdb_framework/public/includes/_shared.php#L485) |
| `gbdbui_list_tables_cached()` | `function gbdbui_list_tables_cached(string $db, ?string $instance = null, int $ttl = 10) :array` | [Zeile 509](../../PHP/gbdb_framework/public/includes/_shared.php#L509) |
| `gbdbui_cache_clear()` | `function gbdbui_cache_clear() :void` | [Zeile 533](../../PHP/gbdb_framework/public/includes/_shared.php#L533) |
| `gbdbui_dir_size()` | `function gbdbui_dir_size(string $path) :int` | [Zeile 540](../../PHP/gbdb_framework/public/includes/_shared.php#L540) |
| `gbdbui_bytes()` | `function gbdbui_bytes(int $bytes) :string` | [Zeile 572](../../PHP/gbdb_framework/public/includes/_shared.php#L572) |
| `gbdbui_copy_tree()` | `function gbdbui_copy_tree(string $from, string $to) :array` | [Zeile 585](../../PHP/gbdb_framework/public/includes/_shared.php#L585) |
| `gbdbui_delete_tree()` | `function gbdbui_delete_tree(string $dir) :bool` | [Zeile 617](../../PHP/gbdb_framework/public/includes/_shared.php#L617) |
| `gbdbui_require_tool()` | `function gbdbui_require_tool(string $tool) :void` | [Zeile 633](../../PHP/gbdb_framework/public/includes/_shared.php#L633) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/backup.page.php](../../PHP/gbdb_framework/public/includes/backup.page.php#L23)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_backup_target_error()` | `function gbdbui_backup_target_error(string $target) :string` | [Zeile 23](../../PHP/gbdb_framework/public/includes/backup.page.php#L23) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/cryption.logic.php](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `h()` | `function h(string $s) :string` | [Zeile 8](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L8) |
| `ensure_dir()` | `function ensure_dir(string $dir) :void` | [Zeile 12](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L12) |
| `atomic_write()` | `function atomic_write(string $file, string $payload) :bool` | [Zeile 18](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L18) |
| `name_token()` | `function name_token(string $plain, string $ns = 'g') :string` | [Zeile 25](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L25) |
| `json_pretty_flags()` | `function json_pretty_flags() :int` | [Zeile 35](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L35) |
| `read_plain_json()` | `function read_plain_json(string $file) :array` | [Zeile 39](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L39) |
| `read_enc_db()` | `function read_enc_db(string $file) :array` | [Zeile 50](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L50) |
| `write_plain_json()` | `function write_plain_json(string $file, array $data) :bool` | [Zeile 66](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L66) |
| `write_enc_db()` | `function write_enc_db(string $file, array $data) :bool` | [Zeile 76](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L76) |
| `db_index_filename()` | `function db_index_filename(string $extEnc) :string` | [Zeile 87](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L87) |
| `table_index_filename()` | `function table_index_filename(string $extEnc) :string` | [Zeile 91](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L91) |
| `build_index_table()` | `function build_index_table(array $mapPlainToToken) :array` | [Zeile 95](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L95) |
| `parse_index_table()` | `function parse_index_table(array $table) :array` | [Zeile 107](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L107) |
| `rrmdir()` | `function rrmdir(string $dir) :void` | [Zeile 137](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L137) |
| `meta_filename_plain()` | `function meta_filename_plain(string $tblPlain, string $extPlain) :string` | [Zeile 158](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L158) |
| `append_filename_plain()` | `function append_filename_plain(string $tblPlain, string $extPlain) :string` | [Zeile 162](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L162) |
| `meta_filename_enc()` | `function meta_filename_enc(string $tblToken, string $extEnc) :string` | [Zeile 166](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L166) |
| `append_filename_enc()` | `function append_filename_enc(string $tblToken, string $extEnc) :string` | [Zeile 170](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L170) |
| `looks_like_meta_or_append_or_idx()` | `function looks_like_meta_or_append_or_idx(string $file, string $extPlain, string $extEnc) :bool` | [Zeile 174](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L174) |
| `detect_state()` | `function detect_state(string $gbdbRoot, string $extEnc, string $extPlain) :string` | [Zeile 198](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L198) |
| `dump_plain()` | `function dump_plain(string $gbdbRoot, string $extPlain, string $extEnc) :array` | [Zeile 234](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L234) |
| `dump_encrypted()` | `function dump_encrypted(string $gbdbRoot, string $extEnc) :array` | [Zeile 271](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L271) |
| `compute_meta_from_table()` | `function compute_meta_from_table(array $table) :array` | [Zeile 315](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L315) |
| `write_encrypted_schema()` | `function write_encrypted_schema(string $targetRoot, array $dump, string $extEnc) :array` | [Zeile 345](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L345) |
| `write_plain_schema()` | `function write_plain_schema(string $targetRoot, array $dump, string $extPlain) :array` | [Zeile 419](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L419) |
| `swap_with_backup()` | `function swap_with_backup(string $gbdbRoot, string $parent, string $tmpRoot) :string` | [Zeile 443](../../PHP/gbdb_framework/public/includes/cryption.logic.php#L443) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/distributed.page.php](../../PHP/gbdb_framework/public/includes/distributed.page.php#L33)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_gdp_endpoint()` | `function gbdbui_gdp_endpoint() :string` | [Zeile 33](../../PHP/gbdb_framework/public/includes/distributed.page.php#L33) |
| `gbdbui_gdp_time()` | `function gbdbui_gdp_time(int $timestamp) :string` | [Zeile 53](../../PHP/gbdb_framework/public/includes/distributed.page.php#L53) |
| `gbdbui_gdp_state_badge()` | `function gbdbui_gdp_state_badge(string $state) :string` | [Zeile 57](../../PHP/gbdb_framework/public/includes/distributed.page.php#L57) |
| `gbdbui_gdp_redirect()` | `function gbdbui_gdp_redirect(string $view, array $params = []) :never` | [Zeile 72](../../PHP/gbdb_framework/public/includes/distributed.page.php#L72) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/gql_scripts.page.php](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L17)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_script_rel()` | `function gbdbui_script_rel(string $path) :string` | [Zeile 17](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L17) |
| `gbdbui_script_abs()` | `function gbdbui_script_abs(string $root, string $rel) :string` | [Zeile 37](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L37) |
| `gbdbui_script_tree()` | `function gbdbui_script_tree(string $root) :array` | [Zeile 41](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L41) |
| `gbdbui_gql_output_text()` | `function gbdbui_gql_output_text(mixed $value) :string` | [Zeile 71](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L71) |
| `gbdbui_gql_outputs()` | `function gbdbui_gql_outputs(array $runResult) :array` | [Zeile 87](../../PHP/gbdb_framework/public/includes/gql_scripts.page.php#L87) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/greenql_v2.logic.php](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `e()` | `function e(mixed $value) :string` | [Zeile 3](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L3) |
| `csrf()` | `function csrf() :string` | [Zeile 7](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L7) |
| `selfUrl()` | `function selfUrl(array $params = []) :string` | [Zeile 11](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L11) |
| `redirectSelf()` | `function redirectSelf(array $params = []) :void` | [Zeile 31](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L31) |
| `flash()` | `function flash(string $type, string $text) :void` | [Zeile 37](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L37) |
| `flashes()` | `function flashes() :array` | [Zeile 41](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L41) |
| `selectedMode()` | `function selectedMode() :string` | [Zeile 48](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L48) |
| `selectedInstance()` | `function selectedInstance() :string` | [Zeile 54](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L54) |
| `selectedDb()` | `function selectedDb(string $instance) :string` | [Zeile 68](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L68) |
| `selectedTable()` | `function selectedTable(string $instance, string $db) :string` | [Zeile 78](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L78) |
| `queryResultBox()` | `function queryResultBox(array $result) :void` | [Zeile 89](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L89) |
| `paramTextFromPost()` | `function paramTextFromPost() :string` | [Zeile 166](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L166) |
| `uiValue()` | `function uiValue(mixed $value) :mixed` | [Zeile 170](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L170) |
| `tableDefaults()` | `function tableDefaults(string $instance, string $db, string $table) :array` | [Zeile 205](../../PHP/gbdb_framework/public/includes/greenql_v2.logic.php#L205) |

## GreenQLUIv2Helper

Klasse · Quelle: [public/includes/greenql_v2_helper.php](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `boot()` | `public static function boot() :void` | [Zeile 25](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L25) |
| `csrf()` | `public static function csrf() :string` | [Zeile 60](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L60) |
| `checkCsrf()` | `public static function checkCsrf(string $token) :bool` | [Zeile 64](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L64) |
| `systemInstance()` | `public static function systemInstance() :string` | [Zeile 68](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L68) |
| `permissionMigrationNotice()` | `public static function permissionMigrationNotice() :?array` | [Zeile 255](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L255) |
| `acknowledgePermissionMigration()` | `public static function acknowledgePermissionMigration() :bool` | [Zeile 268](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L268) |
| `publicApiEndpoint()` | `public static function publicApiEndpoint() :string` | [Zeile 377](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L377) |
| `publicApiKeys()` | `public static function publicApiKeys() :array` | [Zeile 391](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L391) |
| `createPublicApiKey()` | `public static function createPublicApiKey(string $name, string $rights, string $exp = "", string $active = "1") :string` | [Zeile 409](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L409) |
| `updatePublicApiKey()` | `public static function updatePublicApiKey(int $id, array $data) :bool` | [Zeile 444](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L444) |
| `deletePublicApiKey()` | `public static function deletePublicApiKey(int $id) :bool` | [Zeile 483](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L483) |
| `maskPublicApiKey()` | `public static function maskPublicApiKey(string $key) :string` | [Zeile 501](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L501) |
| `publicApiLogs()` | `public static function publicApiLogs(string $table, int $limit = 50) :array` | [Zeile 516](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L516) |
| `publicApiModules()` | `public static function publicApiModules() :array` | [Zeile 542](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L542) |
| `normalizePublicApiRights()` | `public static function normalizePublicApiRights(string $rights) :string` | [Zeile 619](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L619) |
| `hasUsers()` | `public static function hasUsers() :bool` | [Zeile 679](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L679) |
| `setupConfigurationError()` | `public static function setupConfigurationError() :string` | [Zeile 691](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L691) |
| `setupError()` | `public static function setupError() :string` | [Zeile 699](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L699) |
| `createInitialAdmin()` | `public static function createInitialAdmin(string $username, string $password, string $language, string $token) :bool` | [Zeile 703](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L703) |
| `createUser()` | `public static function createUser(string $username, string $password, string $role = "admin", string $instances = "*", string $bases = "*", string $language = "en", string $permissions = "", string $tools = "") :bool` | [Zeile 752](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L752) |
| `updateUser()` | `public static function updateUser(int $id, array $data) :bool` | [Zeile 793](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L793) |
| `resetPassword()` | `public static function resetPassword(int $id, string $password) :bool` | [Zeile 888](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L888) |
| `deleteUser()` | `public static function deleteUser(int $id) :bool` | [Zeile 933](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L933) |
| `login()` | `public static function login(string $username, string $password) :bool` | [Zeile 963](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L963) |
| `logout()` | `public static function logout() :void` | [Zeile 1006](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1006) |
| `user()` | `public static function user() :array` | [Zeile 1017](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1017) |
| `freshUser()` | `public static function freshUser() :array` | [Zeile 1023](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1023) |
| `loggedIn()` | `public static function loggedIn() :bool` | [Zeile 1063](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1063) |
| `users()` | `public static function users() :array` | [Zeile 1082](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1082) |
| `normalizeRole()` | `public static function normalizeRole(string $role) :string` | [Zeile 1100](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1100) |
| `normalizeLanguage()` | `public static function normalizeLanguage(string $language) :string` | [Zeile 1106](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1106) |
| `setLanguage()` | `public static function setLanguage(string $language) :void` | [Zeile 1112](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1112) |
| `language()` | `public static function language() :string` | [Zeile 1116](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1116) |
| `t()` | `public static function t(string $key) :string` | [Zeile 1122](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1122) |
| `isAdmin()` | `public static function isAdmin() :bool` | [Zeile 1181](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1181) |
| `canWrite()` | `public static function canWrite() :bool` | [Zeile 1185](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1185) |
| `canStructure()` | `public static function canStructure() :bool` | [Zeile 1189](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1189) |
| `hasPermission()` | `public static function hasPermission(string $permission, ?array $user = null) :bool` | [Zeile 1193](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1193) |
| `disabledToolSwitch()` | `public static function disabledToolSwitch(string $tool) :string` | [Zeile 1226](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1226) |
| `canUseTool()` | `public static function canUseTool(string $tool) :bool` | [Zeile 1232](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1232) |
| `clean()` | `public static function clean(string $value) :string` | [Zeile 1271](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1271) |
| `reservedInstance()` | `public static function reservedInstance(string $instance) :bool` | [Zeile 1285](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1285) |
| `reservedName()` | `public static function reservedName(string $name) :bool` | [Zeile 1297](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1297) |
| `canAccessInstance()` | `public static function canAccessInstance(string $instance, ?array $user = null) :bool` | [Zeile 1325](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1325) |
| `canAccessDb()` | `public static function canAccessDb(string $instance, string $db, ?array $user = null) :bool` | [Zeile 1346](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1346) |
| `instances()` | `public static function instances() :array` | [Zeile 1386](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1386) |
| `databases()` | `public static function databases(string $instance) :array` | [Zeile 1397](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1397) |
| `tables()` | `public static function tables(string $instance, string $database) :array` | [Zeile 1418](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1418) |
| `parseParams()` | `public static function parseParams(string $raw) :array` | [Zeile 1440](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1440) |
| `scriptAllowed()` | `public static function scriptAllowed(string $script, string $instance) :array` | [Zeile 1495](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1495) |
| `readScriptPath()` | `public static function readScriptPath(string $path) :array` | [Zeile 1542](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1542) |
| `runScript()` | `public static function runScript(string $script, string $instance, array $params = []) :array` | [Zeile 1587](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1587) |
| `errorResult()` | `public static function errorResult(string $message) :array` | [Zeile 1642](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1642) |
| `e()` | `public static function e(mixed $value) :string` | [Zeile 1657](../../PHP/gbdb_framework/public/includes/greenql_v2_helper.php#L1657) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/import_export.page.php](../../PHP/gbdb_framework/public/includes/import_export.page.php#L42)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_import_export_target()` | `function gbdbui_import_export_target(string $value) :array` | [Zeile 42](../../PHP/gbdb_framework/public/includes/import_export.page.php#L42) |
| `gbdbui_import_export_valid_target()` | `function gbdbui_import_export_valid_target(string $instance, string $db, string $table) :bool` | [Zeile 52](../../PHP/gbdb_framework/public/includes/import_export.page.php#L52) |
| `gbdbui_import_export_with_instance()` | `function gbdbui_import_export_with_instance(string $instance, callable $callback) :mixed` | [Zeile 64](../../PHP/gbdb_framework/public/includes/import_export.page.php#L64) |
| `gbdbui_export_filename()` | `function gbdbui_export_filename(string $instance, string $db, string $table, string $format) :string` | [Zeile 75](../../PHP/gbdb_framework/public/includes/import_export.page.php#L75) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/migration.logic.php](../../PHP/gbdb_framework/public/includes/migration.logic.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `h()` | `function h($s)` | [Zeile 3](../../PHP/gbdb_framework/public/includes/migration.logic.php#L3) |
| `nowStamp()` | `function nowStamp() :string` | [Zeile 7](../../PHP/gbdb_framework/public/includes/migration.logic.php#L7) |
| `ensure_dir()` | `function ensure_dir(string $dir) :void` | [Zeile 11](../../PHP/gbdb_framework/public/includes/migration.logic.php#L11) |
| `rrmdir()` | `function rrmdir(string $dir) :void` | [Zeile 17](../../PHP/gbdb_framework/public/includes/migration.logic.php#L17) |
| `atomic_write()` | `function atomic_write(string $file, string $payload) :bool` | [Zeile 39](../../PHP/gbdb_framework/public/includes/migration.logic.php#L39) |
| `name_token()` | `function name_token(string $plain, string $ns = 'g') :string` | [Zeile 46](../../PHP/gbdb_framework/public/includes/migration.logic.php#L46) |
| `build_index_table()` | `function build_index_table(array $mapPlainToToken) :array` | [Zeile 56](../../PHP/gbdb_framework/public/includes/migration.logic.php#L56) |
| `parse_index_table()` | `function parse_index_table(array $table) :array` | [Zeile 68](../../PHP/gbdb_framework/public/includes/migration.logic.php#L68) |
| `read_table_any()` | `function read_table_any(string $file) :array` | [Zeile 98](../../PHP/gbdb_framework/public/includes/migration.logic.php#L98) |
| `write_table_target()` | `function write_table_target(string $file, array $data, bool $encrypt, int $jsonFlags, bool $backupIfExists = true) :bool` | [Zeile 127](../../PHP/gbdb_framework/public/includes/migration.logic.php#L127) |
| `ensure_header()` | `function ensure_header(array $table, array $fallbackCols = []) :array` | [Zeile 144](../../PHP/gbdb_framework/public/includes/migration.logic.php#L144) |
| `compute_meta_from_table()` | `function compute_meta_from_table(array $table) :array` | [Zeile 174](../../PHP/gbdb_framework/public/includes/migration.logic.php#L174) |
| `meta_file_for_table_plain()` | `function meta_file_for_table_plain(string $dbDir, string $tablePlain, string $ext) :string` | [Zeile 204](../../PHP/gbdb_framework/public/includes/migration.logic.php#L204) |
| `append_file_for_table_plain()` | `function append_file_for_table_plain(string $dbDir, string $tablePlain, string $ext) :string` | [Zeile 208](../../PHP/gbdb_framework/public/includes/migration.logic.php#L208) |
| `meta_file_for_table_crypt()` | `function meta_file_for_table_crypt(string $dbDir, string $tblToken, string $ext) :string` | [Zeile 212](../../PHP/gbdb_framework/public/includes/migration.logic.php#L212) |
| `append_file_for_table_crypt()` | `function append_file_for_table_crypt(string $dbDir, string $tblToken, string $ext) :string` | [Zeile 216](../../PHP/gbdb_framework/public/includes/migration.logic.php#L216) |
| `detect_plain_structure()` | `function detect_plain_structure(string $gbdbRoot) :bool` | [Zeile 220](../../PHP/gbdb_framework/public/includes/migration.logic.php#L220) |
| `gbdb_parent_from_root()` | `function gbdb_parent_from_root(string $gbdbRoot) :string` | [Zeile 245](../../PHP/gbdb_framework/public/includes/migration.logic.php#L245) |
| `db_index_filename()` | `function db_index_filename(string $extEnc) :string` | [Zeile 249](../../PHP/gbdb_framework/public/includes/migration.logic.php#L249) |
| `table_index_filename()` | `function table_index_filename(string $extEnc) :string` | [Zeile 253](../../PHP/gbdb_framework/public/includes/migration.logic.php#L253) |
| `dump_plain_any()` | `function dump_plain_any(string $gbdbRoot) :array` | [Zeile 257](../../PHP/gbdb_framework/public/includes/migration.logic.php#L257) |
| `write_plain_schema_new()` | `function write_plain_schema_new(string $targetRoot, array $dump, string $extPlain, int $jsonFlags) :array` | [Zeile 317](../../PHP/gbdb_framework/public/includes/migration.logic.php#L317) |
| `write_crypt_schema_new()` | `function write_crypt_schema_new(string $targetRoot, array $dump, string $extEnc, int $jsonFlags) :array` | [Zeile 374](../../PHP/gbdb_framework/public/includes/migration.logic.php#L374) |
| `swap_with_backup()` | `function swap_with_backup(string $gbdbRoot, string $parent, string $tmpRoot) :string` | [Zeile 472](../../PHP/gbdb_framework/public/includes/migration.logic.php#L472) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/optimize.logic.php](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `h()` | `function h($s)` | [Zeile 3](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L3) |
| `name_token()` | `function name_token(string $plain, string $ns = 'g') :string` | [Zeile 7](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L7) |
| `read_table_any()` | `function read_table_any(string $file) :array` | [Zeile 17](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L17) |
| `parse_index_table()` | `function parse_index_table(array $table) :array` | [Zeile 44](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L44) |
| `count_lines()` | `function count_lines(string $file) :int` | [Zeile 74](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L74) |
| `meta_file_plain()` | `function meta_file_plain(string $dbDir, string $tablePlain, string $ext) :string` | [Zeile 104](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L104) |
| `append_file_plain()` | `function append_file_plain(string $dbDir, string $tablePlain, string $ext) :string` | [Zeile 108](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L108) |
| `meta_file_crypt()` | `function meta_file_crypt(string $dbDir, string $tblToken, string $ext) :string` | [Zeile 112](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L112) |
| `append_file_crypt()` | `function append_file_crypt(string $dbDir, string $tblToken, string $ext) :string` | [Zeile 116](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L116) |
| `db_index_file()` | `function db_index_file(string $root, string $ext) :string` | [Zeile 120](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L120) |
| `table_index_file()` | `function table_index_file(string $dbDir, string $ext) :string` | [Zeile 124](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L124) |
| `resolve_paths_for_table()` | `function resolve_paths_for_table(string $dbPlain, string $tablePlain) :array` | [Zeile 128](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L128) |
| `tableStats()` | `function tableStats(string $db, string $table) :array` | [Zeile 179](../../PHP/gbdb_framework/public/includes/optimize.logic.php#L179) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/php_exec.page.php](../../PHP/gbdb_framework/public/includes/php_exec.page.php#L34)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_php_exec_prepare()` | `function gbdbui_php_exec_prepare(string $code) :string` | [Zeile 34](../../PHP/gbdb_framework/public/includes/php_exec.page.php#L34) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/public_api.page.php](../../PHP/gbdb_framework/public/includes/public_api.page.php#L11)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_public_api_redirect()` | `function gbdbui_public_api_redirect() :void` | [Zeile 11](../../PHP/gbdb_framework/public/includes/public_api.page.php#L11) |
| `gbdbui_public_api_rights_from_post()` | `function gbdbui_public_api_rights_from_post() :string` | [Zeile 17](../../PHP/gbdb_framework/public/includes/public_api.page.php#L17) |
| `gbdbui_public_api_rights_badges()` | `function gbdbui_public_api_rights_badges(string $rights) :string` | [Zeile 46](../../PHP/gbdb_framework/public/includes/public_api.page.php#L46) |
| `gbdbui_public_api_datetime_input()` | `function gbdbui_public_api_datetime_input(string $value) :string` | [Zeile 69](../../PHP/gbdb_framework/public/includes/public_api.page.php#L69) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/updates.page.php](../../PHP/gbdb_framework/public/includes/updates.page.php#L11)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_upd_time()` | `function gbdbui_upd_time(mixed $value) :string` | [Zeile 11](../../PHP/gbdb_framework/public/includes/updates.page.php#L11) |
| `gbdbui_upd_badge()` | `function gbdbui_upd_badge(string $status) :string` | [Zeile 21](../../PHP/gbdb_framework/public/includes/updates.page.php#L21) |

## Globale Funktionen

Funktionen · Quelle: [public/includes/users.page.php](../../PHP/gbdb_framework/public/includes/users.page.php#L11)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_users_redirect()` | `function gbdbui_users_redirect() :void` | [Zeile 11](../../PHP/gbdb_framework/public/includes/users.page.php#L11) |
| `gbdbui_csv_items()` | `function gbdbui_csv_items(string $value) :array` | [Zeile 17](../../PHP/gbdb_framework/public/includes/users.page.php#L17) |
| `gbdbui_render_chip_group()` | `function gbdbui_render_chip_group(string $value, string $fallback = '—') :string` | [Zeile 30](../../PHP/gbdb_framework/public/includes/users.page.php#L30) |

## Globale Funktionen

Funktionen · Quelle: [public/index.php](../../PHP/gbdb_framework/public/index.php#L55)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `gbdbui_redirect()` | `function gbdbui_redirect(string $tool = 'dashboard', array $params = []) :void` | [Zeile 55](../../PHP/gbdb_framework/public/index.php#L55) |

