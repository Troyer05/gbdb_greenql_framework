# Public API Public Methods

### `PublicAPI`

File: `public/includes/public_api/api.php`

| Method | Signature | Typical use |
|---|---|---|
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `setLogTable()` | `public static function setLogTable(string $table)` | Write/change storage or configuration; validate input and permissions first. |
| `log()` | `public static function log(string $log, array $sys = [])` | See class section; use when the method name matches the required operation. |
| `respond()` | `public static function respond(int $status, mixed $data)` | See class section; use when the method name matches the required operation. |

### `PublicAPI_GBDB`

File: `public/includes/public_api/gbdb.php`

| Method | Signature | Typical use |
|---|---|---|
| `name()` | `public static function name()` | See class section; use when the method name matches the required operation. |
| `requiresRights()` | `public static function requiresRights(string $action = "")` | See class section; use when the method name matches the required operation. |
| `handle()` | `public static function handle(string $action, array $body, array $keyData)` | See class section; use when the method name matches the required operation. |

### `PublicAPI_ModuleHelper`

File: `public/includes/public_api/module_helper.php`

| Method | Signature | Typical use |
|---|---|---|
| `rights()` | `public static function rights(array $actions, string $action = "")` | See class section; use when the method name matches the required operation. |
| `handle()` | `public static function handle(string $class, array $actions, string $action, array $body, array $keyData)` | See class section; use when the method name matches the required operation. |
| `requireParams()` | `public static function requireParams(array $body, array $params)` | See class section; use when the method name matches the required operation. |
| `str()` | `public static function str(array $body, string $key, string $default = "")` | See class section; use when the method name matches the required operation. |
| `arr()` | `public static function arr(array $body, string $key)` | See class section; use when the method name matches the required operation. |
| `rightsFromKey()` | `public static function rightsFromKey(array $keyData)` | See class section; use when the method name matches the required operation. |

### `PublicAPI_Test`

File: `public/includes/public_api/public_api_modules/example.php`

| Method | Signature | Typical use |
|---|---|---|
| `name()` | `public static function name()` | See class section; use when the method name matches the required operation. |
| `requiresRights()` | `public static function requiresRights(string $action = "")` | See class section; use when the method name matches the required operation. |
| `handle()` | `public static function handle(string $action, array $body, array $keyData)` | See class section; use when the method name matches the required operation. |
