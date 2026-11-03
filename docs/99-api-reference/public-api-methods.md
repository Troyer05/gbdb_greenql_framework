# Public API – Dispatcher und Module

HTTP-Einstieg ist PHP/public_api.php. Aktionshandler des GBDB-Moduls sind überwiegend privat und deshalb nicht Teil dieses öffentlichen PHP-Signaturindex. Aktionen und Rechte stehen im API-Handbuch.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## PublicAPI

Klasse · Quelle: [public/includes/public_api/api.php](../../PHP/gbdb_framework/public/includes/public_api/api.php#L7)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `init()` | `public static function init() :void` | [Zeile 29](../../PHP/gbdb_framework/public/includes/public_api/api.php#L29) |
| `setLogTable()` | `public static function setLogTable(string $table) :void` | [Zeile 63](../../PHP/gbdb_framework/public/includes/public_api/api.php#L63) |
| `log()` | `public static function log(string $log, array $sys = []) :void` | [Zeile 89](../../PHP/gbdb_framework/public/includes/public_api/api.php#L89) |
| `respond()` | `public static function respond(int $status, mixed $data) :never` | [Zeile 138](../../PHP/gbdb_framework/public/includes/public_api/api.php#L138) |

## PublicAPI_GBDB

Klasse · Quelle: [public/includes/public_api/gbdb.php](../../PHP/gbdb_framework/public/includes/public_api/gbdb.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `name()` | `public static function name() :string` | [Zeile 45](../../PHP/gbdb_framework/public/includes/public_api/gbdb.php#L45) |
| `requiresRights()` | `public static function requiresRights(string $action = "") :array` | [Zeile 55](../../PHP/gbdb_framework/public/includes/public_api/gbdb.php#L55) |
| `handle()` | `public static function handle(string $action, array $body, array $keyData) :mixed` | [Zeile 67](../../PHP/gbdb_framework/public/includes/public_api/gbdb.php#L67) |

## PublicAPI_ModuleHelper

Klasse · Quelle: [public/includes/public_api/module_helper.php](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `rights()` | `public static function rights(array $actions, string $action = "") :array` | [Zeile 4](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L4) |
| `handle()` | `public static function handle(string $class, array $actions, string $action, array $body, array $keyData) :mixed` | [Zeile 12](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L12) |
| `requireParams()` | `public static function requireParams(array $body, array $params) :bool\|array` | [Zeile 32](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L32) |
| `str()` | `public static function str(array $body, string $key, string $default = "") :string` | [Zeile 45](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L45) |
| `arr()` | `public static function arr(array $body, string $key) :array` | [Zeile 49](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L49) |
| `rightsFromKey()` | `public static function rightsFromKey(array $keyData) :array` | [Zeile 57](../../PHP/gbdb_framework/public/includes/public_api/module_helper.php#L57) |

## PublicAPI_Test

Klasse · Quelle: [public/includes/public_api/public_api_modules/example.php](../../PHP/gbdb_framework/public/includes/public_api/public_api_modules/example.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `name()` | `public static function name() :string` | [Zeile 11](../../PHP/gbdb_framework/public/includes/public_api/public_api_modules/example.php#L11) |
| `requiresRights()` | `public static function requiresRights(string $action = "") :array` | [Zeile 15](../../PHP/gbdb_framework/public/includes/public_api/public_api_modules/example.php#L15) |
| `handle()` | `public static function handle(string $action, array $body, array $keyData) :mixed` | [Zeile 19](../../PHP/gbdb_framework/public/includes/public_api/public_api_modules/example.php#L19) |

