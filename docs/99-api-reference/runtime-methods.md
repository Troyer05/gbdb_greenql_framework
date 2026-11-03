# Interne Speicherlaufzeit

Diese Klassen steuern Konfiguration, Dateizugriff, SQL-Speicher und Sperren. Reguläre Datenoperationen über GBDB ausführen. Öffentliche Sichtbarkeit ist hier keine Empfehlung für direkte Anwendungskopplung.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## GBDBConfig

Klasse · Quelle: [runtime/config.php](../../PHP/gbdb_framework/runtime/config.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `root()` | `public static function root() :string` | [Zeile 9](../../PHP/gbdb_framework/runtime/config.php#L9) |
| `get()` | `public static function get(string $section, string $name, mixed $default = null) :mixed` | [Zeile 13](../../PHP/gbdb_framework/runtime/config.php#L13) |
| `file()` | `public static function file() :string` | [Zeile 27](../../PHP/gbdb_framework/runtime/config.php#L27) |
| `editableConstants()` | `public static function editableConstants() :array` | [Zeile 32](../../PHP/gbdb_framework/runtime/config.php#L32) |
| `constantsToSections()` | `public static function constantsToSections(array $constants) :array` | [Zeile 80](../../PHP/gbdb_framework/runtime/config.php#L80) |
| `sourceWithConstants()` | `public static function sourceWithConstants(array $constants) :string` | [Zeile 129](../../PHP/gbdb_framework/runtime/config.php#L129) |
| `flag()` | `public static function flag(string $section, string $name, bool $default = false) :bool` | [Zeile 180](../../PHP/gbdb_framework/runtime/config.php#L180) |
| `dataPath()` | `public static function dataPath() :string` | [Zeile 191](../../PHP/gbdb_framework/runtime/config.php#L191) |
| `runtimePath()` | `public static function runtimePath() :string` | [Zeile 205](../../PHP/gbdb_framework/runtime/config.php#L205) |
| `secret()` | `public static function secret(string $name) :string` | [Zeile 209](../../PHP/gbdb_framework/runtime/config.php#L209) |
| `sql()` | `public static function sql() :array` | [Zeile 259](../../PHP/gbdb_framework/runtime/config.php#L259) |

## GBDBDataStream

Klasse · Quelle: [runtime/filesystem.php](../../PHP/gbdb_framework/runtime/filesystem.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `stream_open()` | `public function stream_open(string $path, string $mode, int $options, ?string &$openedPath) :bool` | [Zeile 49](../../PHP/gbdb_framework/runtime/filesystem.php#L49) |
| `stream_read()` | `public function stream_read(int $count) :string` | [Zeile 73](../../PHP/gbdb_framework/runtime/filesystem.php#L73) |
| `stream_write()` | `public function stream_write(string $payload) :int` | [Zeile 80](../../PHP/gbdb_framework/runtime/filesystem.php#L80) |
| `stream_flush()` | `public function stream_flush() :bool` | [Zeile 102](../../PHP/gbdb_framework/runtime/filesystem.php#L102) |
| `stream_close()` | `public function stream_close() :void` | [Zeile 122](../../PHP/gbdb_framework/runtime/filesystem.php#L122) |
| `stream_eof()` | `public function stream_eof() :bool` | [Zeile 130](../../PHP/gbdb_framework/runtime/filesystem.php#L130) |
| `stream_tell()` | `public function stream_tell() :int` | [Zeile 134](../../PHP/gbdb_framework/runtime/filesystem.php#L134) |
| `stream_seek()` | `public function stream_seek(int $offset, int $whence = SEEK_SET) :bool` | [Zeile 138](../../PHP/gbdb_framework/runtime/filesystem.php#L138) |
| `stream_truncate()` | `public function stream_truncate(int $size) :bool` | [Zeile 155](../../PHP/gbdb_framework/runtime/filesystem.php#L155) |
| `stream_stat()` | `public function stream_stat() :array\|false` | [Zeile 166](../../PHP/gbdb_framework/runtime/filesystem.php#L166) |
| `url_stat()` | `public function url_stat(string $path, int $flags) :array\|false` | [Zeile 176](../../PHP/gbdb_framework/runtime/filesystem.php#L176) |
| `stream_lock()` | `public function stream_lock(int $operation) :bool` | [Zeile 180](../../PHP/gbdb_framework/runtime/filesystem.php#L180) |
| `stream_set_option()` | `public function stream_set_option(int $option, int $argument1, int $argument2) :bool` | [Zeile 208](../../PHP/gbdb_framework/runtime/filesystem.php#L208) |
| `stream_metadata()` | `public function stream_metadata(string $path, int $option, mixed $value) :bool` | [Zeile 212](../../PHP/gbdb_framework/runtime/filesystem.php#L212) |
| `mkdir()` | `public function mkdir(string $path, int $mode, int $options) :bool` | [Zeile 223](../../PHP/gbdb_framework/runtime/filesystem.php#L223) |
| `rmdir()` | `public function rmdir(string $path, int $options) :bool` | [Zeile 227](../../PHP/gbdb_framework/runtime/filesystem.php#L227) |
| `unlink()` | `public function unlink(string $path) :bool` | [Zeile 231](../../PHP/gbdb_framework/runtime/filesystem.php#L231) |
| `rename()` | `public function rename(string $from, string $to) :bool` | [Zeile 235](../../PHP/gbdb_framework/runtime/filesystem.php#L235) |
| `dir_opendir()` | `public function dir_opendir(string $path, int $options) :bool` | [Zeile 239](../../PHP/gbdb_framework/runtime/filesystem.php#L239) |
| `dir_readdir()` | `public function dir_readdir() :string\|false` | [Zeile 252](../../PHP/gbdb_framework/runtime/filesystem.php#L252) |
| `dir_rewinddir()` | `public function dir_rewinddir() :bool` | [Zeile 256](../../PHP/gbdb_framework/runtime/filesystem.php#L256) |
| `dir_closedir()` | `public function dir_closedir() :bool` | [Zeile 262](../../PHP/gbdb_framework/runtime/filesystem.php#L262) |

## GBDBFilesystem

Klasse · Quelle: [runtime/filesystem.php](../../PHP/gbdb_framework/runtime/filesystem.php#L270)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `glob()` | `public static function glob(string $pattern, int $flags = 0) :array\|false` | [Zeile 271](../../PHP/gbdb_framework/runtime/filesystem.php#L271) |
| `realpath()` | `public static function realpath(string $path) :string\|false` | [Zeile 307](../../PHP/gbdb_framework/runtime/filesystem.php#L307) |
| `sync()` | `public static function sync(mixed $handle) :bool` | [Zeile 311](../../PHP/gbdb_framework/runtime/filesystem.php#L311) |

## GBDBRateLimiter

Klasse · Quelle: [runtime/rate_limiter.php](../../PHP/gbdb_framework/runtime/rate_limiter.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `allow()` | `public static function allow(string $purpose, string $identity, int $limit, int $seconds) :bool` | [Zeile 7](../../PHP/gbdb_framework/runtime/rate_limiter.php#L7) |

## GBDBPrimaryFailure

Klasse · Quelle: [runtime/runtime.php](../../PHP/gbdb_framework/runtime/runtime.php#L5)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GBDBRuntime

Klasse · Quelle: [runtime/runtime.php](../../PHP/gbdb_framework/runtime/runtime.php#L15)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `root()` | `public static function root() :string` | [Zeile 28](../../PHP/gbdb_framework/runtime/runtime.php#L28) |
| `lockDirectory()` | `public static function lockDirectory() :string` | [Zeile 42](../../PHP/gbdb_framework/runtime/runtime.php#L42) |
| `run()` | `public static function run(callable $callback, bool $readOnly = false) :mixed` | [Zeile 299](../../PHP/gbdb_framework/runtime/runtime.php#L299) |
| `mutate()` | `public static function mutate(callable $primary, callable $sql) :bool` | [Zeile 404](../../PHP/gbdb_framework/runtime/runtime.php#L404) |
| `read()` | `public static function read(string $path) :string` | [Zeile 461](../../PHP/gbdb_framework/runtime/runtime.php#L461) |
| `validated()` | `public static function validated(string $path, callable $decode) :mixed` | [Zeile 478](../../PHP/gbdb_framework/runtime/runtime.php#L478) |
| `native()` | `public static function native(string $relative) :string` | [Zeile 486](../../PHP/gbdb_framework/runtime/runtime.php#L486) |
| `stat()` | `public static function stat(string $path) :array\|false` | [Zeile 510](../../PHP/gbdb_framework/runtime/runtime.php#L510) |
| `children()` | `public static function children(string $path) :array` | [Zeile 541](../../PHP/gbdb_framework/runtime/runtime.php#L541) |
| `write()` | `public static function write(string $path, string $payload, bool $append = false) :bool` | [Zeile 557](../../PHP/gbdb_framework/runtime/runtime.php#L557) |
| `writeAll()` | `public static function writeAll(mixed $handle, string $payload) :bool` | [Zeile 591](../../PHP/gbdb_framework/runtime/runtime.php#L591) |
| `directory()` | `public static function directory(string $path, bool $recursive) :bool` | [Zeile 637](../../PHP/gbdb_framework/runtime/runtime.php#L637) |
| `remove()` | `public static function remove(string $path, bool $directory = false) :bool` | [Zeile 655](../../PHP/gbdb_framework/runtime/runtime.php#L655) |
| `rename()` | `public static function rename(string $from, string $to) :bool` | [Zeile 672](../../PHP/gbdb_framework/runtime/runtime.php#L672) |
| `remember()` | `public static function remember(string $name, array $dependencies, callable $load) :mixed` | [Zeile 693](../../PHP/gbdb_framework/runtime/runtime.php#L693) |
| `status()` | `public static function status() :array` | [Zeile 864](../../PHP/gbdb_framework/runtime/runtime.php#L864) |
| `synchronize()` | `public static function synchronize() :array` | [Zeile 878](../../PHP/gbdb_framework/runtime/runtime.php#L878) |
| `switch()` | `public static function switch(string $backend) :array` | [Zeile 891](../../PHP/gbdb_framework/runtime/runtime.php#L891) |
| `iterate()` | `public static function iterate(callable $factory) :Generator` | [Zeile 923](../../PHP/gbdb_framework/runtime/runtime.php#L923) |
| `shutdownScope()` | `public static function shutdownScope() :void` | [Zeile 937](../../PHP/gbdb_framework/runtime/runtime.php#L937) |

## GBDBWriteRequired

Klasse · Quelle: [runtime/runtime.php](../../PHP/gbdb_framework/runtime/runtime.php#L8)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GBDBSQLStore

Klasse · Quelle: [runtime/sql_store.php](../../PHP/gbdb_framework/runtime/sql_store.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct(array $configuration)` | [Zeile 14](../../PHP/gbdb_framework/runtime/sql_store.php#L14) |
| `state()` | `public function state() :?array` | [Zeile 78](../../PHP/gbdb_framework/runtime/sql_store.php#L78) |
| `setState()` | `public function setState(array $state) :void` | [Zeile 96](../../PHP/gbdb_framework/runtime/sql_store.php#L96) |
| `entry()` | `public function entry(string $path, bool $withPayload = true) :?array` | [Zeile 104](../../PHP/gbdb_framework/runtime/sql_store.php#L104) |
| `read()` | `public function read(string $path) :string` | [Zeile 118](../../PHP/gbdb_framework/runtime/sql_store.php#L118) |
| `put()` | `public function put(string $path, string $payload = '', bool $directory = false) :void` | [Zeile 134](../../PHP/gbdb_framework/runtime/sql_store.php#L134) |
| `append()` | `public function append(string $path, string $payload) :void` | [Zeile 145](../../PHP/gbdb_framework/runtime/sql_store.php#L145) |
| `children()` | `public function children(string $path) :array` | [Zeile 162](../../PHP/gbdb_framework/runtime/sql_store.php#L162) |
| `entries()` | `public function entries() :Generator` | [Zeile 169](../../PHP/gbdb_framework/runtime/sql_store.php#L169) |
| `delete()` | `public function delete(string $path) :void` | [Zeile 181](../../PHP/gbdb_framework/runtime/sql_store.php#L181) |
| `rename()` | `public function rename(string $from, string $to) :void` | [Zeile 185](../../PHP/gbdb_framework/runtime/sql_store.php#L185) |
| `reset()` | `public function reset() :void` | [Zeile 214](../../PHP/gbdb_framework/runtime/sql_store.php#L214) |
| `transaction()` | `public function transaction(callable $callback) :mixed` | [Zeile 218](../../PHP/gbdb_framework/runtime/sql_store.php#L218) |

