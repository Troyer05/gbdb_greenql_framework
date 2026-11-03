# GreenQL – öffentliche Deklarationen

Die Klasse GreenQL verwendet die aufgeführten Traits. Für Anwendungscode sind GBDB::query() und GBDB::runScript() die regulären Einstiege; GreenQL::run() führt Skripttext aus.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## GreenQL

Klasse · Quelle: [GBDB_GQL/greenql/greenql.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql.php#L16)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GreenQL_BaseTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_base.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_base.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `cleanName()` | `public static function cleanName(string $name) :string` | [Zeile 11](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_base.trait.php#L11) |
| `resolveNameToken()` | `public static function resolveNameToken(string $token, array $vars = []) :string` | [Zeile 61](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_base.trait.php#L61) |

## GreenQL_ExecutionTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_execution.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_execution.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `command()` | `public static function command(string $command, array &$ctx = [], array &$vars = [], array $params = []) :array` | [Zeile 276](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_execution.trait.php#L276) |
| `run()` | `public static function run(string $script, array $ctx = [], array $params = []) :array` | [Zeile 3138](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_execution.trait.php#L3138) |

## GreenQL_IoTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_io.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_io.trait.php#L3)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GreenQL_ParserTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_parser.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `unquote()` | `public static function unquote(string $value) :mixed` | [Zeile 11](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L11) |
| `stripComments()` | `public static function stripComments(string $script) :string` | [Zeile 51](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L51) |
| `splitCommands()` | `public static function splitCommands(string $script) :array` | [Zeile 113](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L113) |
| `evaluateValue()` | `public static function evaluateValue(string $value, array $vars = [], array $params = []) :mixed` | [Zeile 231](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L231) |
| `splitNested()` | `public static function splitNested(string $raw) :array` | [Zeile 313](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L313) |
| `parseLiteral()` | `public static function parseLiteral(string $value, array $vars = [], array $params = []) :mixed` | [Zeile 465](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L465) |
| `resolveVariablePath()` | `public static function resolveVariablePath(string $value, array $vars = [], array $params = []) :mixed` | [Zeile 559](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L559) |
| `evaluateExpression()` | `public static function evaluateExpression(string $value, array $vars = [], array $params = []) :mixed` | [Zeile 603](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L603) |
| `parseList()` | `public static function parseList(string $raw, array $vars = []) :array` | [Zeile 855](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L855) |
| `splitArguments()` | `public static function splitArguments(string $raw) :array` | [Zeile 879](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L879) |
| `parseAssignments()` | `public static function parseAssignments(string $raw, array $vars = [], array $params = []) :array` | [Zeile 961](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L961) |
| `parseWhere()` | `public static function parseWhere(string $raw, array $vars = [], array $params = []) :?array` | [Zeile 1397](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_parser.trait.php#L1397) |

## GreenQL_RowsTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_rows.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `rowMatch()` | `public static function rowMatch(array $row, ?array $where, ?callable $comparator = null) :bool` | [Zeile 13](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L13) |
| `sortRows()` | `public static function sortRows(array &$rows, ?string $field, string $dir = "ASC", ?callable $sorter = null) :void` | [Zeile 104](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L104) |
| `getRows()` | `public static function getRows(string $db, string $table) :array` | [Zeile 137](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L137) |
| `getTableKeys()` | `public static function getTableKeys(string $db, string $table) :array` | [Zeile 152](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L152) |
| `selectRows()` | `public static function selectRows( string $db, string $table, array $columns = ["*"], ?array $where = null, ?string $sortField = null, string $sortDir = "ASC", ?int $limit = null, int $offset = 0 ) :array` | [Zeile 183](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L183) |
| `aggregateRows()` | `public static function aggregateRows(string $db, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null) :array` | [Zeile 262](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L262) |
| `distinctRows()` | `public static function distinctRows(string $db, string $table, string $column) :array` | [Zeile 284](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L284) |
| `stats()` | `public static function stats(string $db) :array` | [Zeile 299](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_rows.trait.php#L299) |

## GreenQL_RuntimeTrait

Trait · Quelle: [GBDB_GQL/greenql/greenql_runtime.trait.php](../../PHP/gbdb_framework/GBDB_GQL/greenql/greenql_runtime.trait.php#L3)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

