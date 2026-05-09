# GreenQL Public Methods

### `GreenQL`

File: `GBDB_GQL/greenql/greenql.php`

No public methods detected.

### `GreenQL_BaseTrait`

File: `GBDB_GQL/greenql/greenql_base.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `cleanName()` | `public static function cleanName(string $name)` | See class section; use when the method name matches the required operation. |
| `resolveNameToken()` | `public static function resolveNameToken(string $token, array $vars = [])` | See class section; use when the method name matches the required operation. |

### `GreenQL_ExecutionTrait`

File: `GBDB_GQL/greenql/greenql_execution.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `command()` | `public static function command(string $command, array &$ctx = [], array &$vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `run()` | `public static function run(string $script, array $ctx = [], array $params = [])` | See class section; use when the method name matches the required operation. |

### `GreenQL_IoTrait`

File: `GBDB_GQL/greenql/greenql_io.trait.php`

No public methods detected.

### `GreenQL_ParserTrait`

File: `GBDB_GQL/greenql/greenql_parser.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `unquote()` | `public static function unquote(string $value)` | See class section; use when the method name matches the required operation. |
| `stripComments()` | `public static function stripComments(string $script)` | See class section; use when the method name matches the required operation. |
| `splitCommands()` | `public static function splitCommands(string $script)` | See class section; use when the method name matches the required operation. |
| `evaluateValue()` | `public static function evaluateValue(string $value, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `splitNested()` | `public static function splitNested(string $raw)` | See class section; use when the method name matches the required operation. |
| `parseLiteral()` | `public static function parseLiteral(string $value, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `resolveVariablePath()` | `public static function resolveVariablePath(string $value, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `evaluateExpression()` | `public static function evaluateExpression(string $value, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `parseList()` | `public static function parseList(string $raw, array $vars = [])` | See class section; use when the method name matches the required operation. |
| `splitArguments()` | `public static function splitArguments(string $raw)` | See class section; use when the method name matches the required operation. |
| `parseAssignments()` | `public static function parseAssignments(string $raw, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `parseWhere()` | `public static function parseWhere(string $raw, array $vars = [], array $params = [])` | See class section; use when the method name matches the required operation. |

### `GreenQL_RowsTrait`

File: `GBDB_GQL/greenql/greenql_rows.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `rowMatch()` | `public static function rowMatch(array $row, ?array $where, ?callable $comparator = null)` | See class section; use when the method name matches the required operation. |
| `sortRows()` | `public static function sortRows(array &$rows, ?string $field, string $dir = "ASC", ?callable $sorter = null)` | See class section; use when the method name matches the required operation. |
| `getRows()` | `public static function getRows(string $db, string $table)` | Read/list data without changing storage. |
| `getTableKeys()` | `public static function getTableKeys(string $db, string $table)` | Read/list data without changing storage. |
| `selectRows()` | `public static function selectRows(string $db, string $table, array $columns = ["*"], ?array $where = null, ?string $sortField = null, string $sortDir = "ASC", ?int $limit = null, int $offset = 0)` | See class section; use when the method name matches the required operation. |
| `aggregateRows()` | `public static function aggregateRows(string $db, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null)` | See class section; use when the method name matches the required operation. |
| `distinctRows()` | `public static function distinctRows(string $db, string $table, string $column)` | See class section; use when the method name matches the required operation. |
| `stats()` | `public static function stats(string $db)` | Operations, diagnostics, backups or maintenance. |

### `GreenQL_RuntimeTrait`

File: `GBDB_GQL/greenql/greenql_runtime.trait.php`

No public methods detected.
