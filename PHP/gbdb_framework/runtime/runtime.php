<?php

declare(strict_types=1);

final class GBDBPrimaryFailure extends RuntimeException {
}

final class GBDBWriteRequired extends RuntimeException {
}

/**
 * One authority per installation. All PHP workers share a routing lock; reads
 * may run concurrently, while mutations and backend transitions are exclusive.
 */
final class GBDBRuntime {
    private static bool $initialized = false;
    private static int $depth = 0;
    private static bool $changed = false;
    private static bool $mirrorHealthy = false;
    private static bool $readOnly = false;
    private static mixed $lock = null;
    private static ?GBDBSQLStore $sql = null;
    private static array $state = [];
    private static array $requestCache = [];
    private static array $pathRevisions = [];
    private static int $cacheBytes = 0;

    public static function root() :string {
        self::register();

        return 'gbdbdata://store';
    }

    private static function register() :void {
        if (!in_array('gbdbdata', stream_get_wrappers(), true)) {
            if (!stream_wrapper_register('gbdbdata', GBDBDataStream::class)) {
                throw new RuntimeException('GBDB konnte seinen Datenspeicher nicht registrieren.');
            }
        }
    }

    public static function lockDirectory() :string {
        $directory = rtrim(sys_get_temp_dir(), '/\\') . '/gbdb-' . hash('sha256', GBDBConfig::root());

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('GBDB kann sein Prozess-Sperrverzeichnis nicht erstellen.');
        }

        if (is_link($directory)) {
            throw new RuntimeException('GBDB akzeptiert kein verlinktes Sperrverzeichnis.');
        }

        return $directory;
    }

    private static function acquire(int $operation) :void {
        self::$lock = fopen(self::lockDirectory() . '/routing.lock', 'c+');

        if (self::$lock === false) {
            throw new RuntimeException('GBDB konnte die Speicher-Umschaltung nicht sperren.');
        }

        $deadline = microtime(true) + max(100, (int) GBDBConfig::get('storage', 'lock_timeout_ms', 10000)) / 1000;

        do {
            if (flock(self::$lock, $operation | LOCK_NB)) {
                return;
            }

            usleep(random_int(1000, 5000));
        } while (microtime(true) < $deadline);

        fclose(self::$lock);
        self::$lock = null;
        throw new RuntimeException('GBDB ist ausgelastet: Zeitlimit beim Warten auf den Speicher.');
    }

    private static function release() :void {
        if (is_resource(self::$lock)) {
            flock(self::$lock, LOCK_UN);
            fclose(self::$lock);
        }

        self::$lock = null;
    }

    private static function enabled() :bool {
        return GBDBConfig::flag('failover', 'enabled');
    }

    private static function stateFile() :string {
        return GBDBConfig::runtimePath() . '/storage-state.php';
    }

    private static function loadState() :void {
        $file = self::stateFile();
        self::$state = [
            'mode' => 'gbdb', 'ready' => false, 'dirty' => false,
            'primary_pending' => false, 'generation' => 0,
            'last_sync' => 0, 'last_error' => '', 'last_retry' => 0,
        ];

        if (is_file($file)) {
            $payload = file_get_contents($file);
            $offset = strpos((string) $payload, "\n");

            if ($payload === false || $offset === false) {
                throw new RuntimeException('Der GBDB-Umschaltstatus ist nicht lesbar.');
            }

            $stored = json_decode(substr($payload, $offset + 1), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($stored) || !in_array($stored['mode'] ?? '', ['gbdb', 'sql'], true)) {
                throw new RuntimeException('Der GBDB-Umschaltstatus ist beschädigt.');
            }

            self::$state = array_replace(self::$state, $stored);
        }

        if (!self::enabled() && self::$state['mode'] === 'sql') {
            throw new RuntimeException('SQL ist noch der aktive Speicher. Fallback wieder aktivieren und kontrolliert zurückschalten.');
        }
    }

    private static function saveLocalState() :void {
        if (!self::enabled()) {
            return;
        }

        $directory = GBDBConfig::runtimePath();

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Der GBDB-Umschaltstatus kann nicht gesichert werden.');
        }

        $payload = "<?php exit; ?>\n" . json_encode(self::$state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        if (!self::nativeWrite(self::stateFile(), $payload)) {
            // SQL authority remains durable even when the primary volume is down.
            if (self::$state['mode'] === 'sql' && self::$sql !== null) {
                self::$sql->setState(self::$state);

                return;
            }

            throw new RuntimeException('Der GBDB-Umschaltstatus konnte nicht atomar gespeichert werden.');
        }
    }

    private static function connect() :void {
        if (!self::enabled()) {
            return;
        }

        if (self::$sql === null) {
            $retry = max(1, (int) GBDBConfig::get('failover', 'retry_seconds', 10));

            if (self::$state['mode'] === 'gbdb' && self::$state['last_error'] !== '' && time() - self::$state['last_retry'] < $retry) {
                return;
            }

            self::$state['last_retry'] = time();

            try {
                self::$sql = new GBDBSQLStore(GBDBConfig::sql());
            } catch (Throwable $exception) {
                self::$state['last_error'] = 'SQL-Verbindung nicht verfügbar.';
                self::saveLocalState();

                if (self::$state['mode'] === 'sql') {
                    throw new RuntimeException('Der aktive SQL-Speicher ist nicht erreichbar.', 0, $exception);
                }

                return;
            }
        }

        try {
            $remote = self::$sql->state();
        } catch (PDOException $exception) {
            self::$sql = null;
            self::$state['last_retry'] = time();
            self::$state['last_error'] = 'SQL-Verbindung nicht verfügbar.';
            self::saveLocalState();

            if (self::$state['mode'] === 'sql') {
                throw new RuntimeException('Der aktive SQL-Speicher ist nicht erreichbar.', 0, $exception);
            }

            return;
        }

        $identity = hash('sha256', GBDBConfig::secret('installation_key'));
        $configuration = GBDBConfig::sql();
        $destination = hash('sha256', $configuration['dsn'] . '|' . $configuration['prefix']);

        if (isset(self::$state['destination']) && self::$state['destination'] !== $destination) {
            if (self::$state['mode'] === 'sql') {
                throw new RuntimeException('SQL ist aktiv. Zuerst mit der bisherigen Verbindung nach GBDB zurückschalten, bevor das SQL-Ziel geändert wird.');
            }

            self::$state['ready'] = false;
            self::$state['dirty'] = true;
        }

        self::$state['destination'] = $destination;

        if ($remote === null && self::$state['mode'] === 'gbdb') {
            self::$state['ready'] = false;
            self::$state['dirty'] = true;
        }

        if ($remote !== null && ($remote['installation'] ?? '') !== $identity) {
            throw new RuntimeException('Dieses SQL-Präfix gehört einer anderen GBDB-Installation. Ein eigenes Präfix vergeben.');
        }

        self::$state['installation'] = $identity;

        if (($remote['mode'] ?? '') === 'sql') {
            self::$state = array_replace(self::$state, $remote);
        } elseif (self::$state['mode'] === 'sql') {
            throw new RuntimeException('SQL-Umschaltstatus fehlt oder widerspricht dem lokalen Status. Rückschaltung wurde blockiert.');
        }

        self::$state['last_error'] = '';
    }

    private static function initialize() :void {
        if (self::$initialized) {
            return;
        }

        self::register();
        self::acquire(LOCK_EX);

        try {
            self::loadState();
            self::connect();

            if (self::$state['mode'] === 'gbdb') {
                $root = GBDBConfig::dataPath();

                foreach (['', '/.system', '/.storage', '/.scripts', '/.temp', '/.backups', '/.media'] as $suffix) {
                    if (!is_dir($root . $suffix) && !@mkdir($root . $suffix, 0750, true) && !is_dir($root . $suffix)) {
                        if (self::$sql !== null && self::$state['ready'] && !self::$state['dirty']) {
                            self::activateSQL('Primärspeicher ist nicht beschreibbar.');
                            break;
                        }

                        throw new GBDBPrimaryFailure('GBDB kann seinen Datenspeicher nicht initialisieren.');
                    }
                }

                if (self::$state['mode'] === 'gbdb') {
                    if (!is_file($root . '/.htaccess')) {
                        self::nativeWrite($root . '/.htaccess', "Require all denied\n");
                    }

                    $shipped = GBDBConfig::root() . '/GBDB_GQL/.DB/.scripts';

                    if ($shipped !== $root . '/.scripts' && is_dir($shipped)) {
                        foreach (glob($shipped . '/*.gql') ?: [] as $script) {
                            $target = $root . '/.scripts/' . basename($script);

                            if (!is_file($target)) {
                                self::nativeWrite($target, (string) file_get_contents($script));
                            }
                        }
                    }

                    if (self::$sql !== null && (!self::$state['ready'] || self::$state['dirty'])) {
                        try {
                            self::copyPrimaryToSQL();
                        } catch (Throwable $exception) {
                            self::$state['ready'] = false;
                            self::$state['dirty'] = true;
                            self::$state['last_error'] = 'Synchronisation ausstehend; Primärdaten prüfen.';
                            self::saveLocalState();
                            error_log('[GBDB] SQL-Synchronisation fehlgeschlagen: ' . get_class($exception));
                        }
                    }
                }
            }

            self::$initialized = true;
            self::$depth = 1;
            self::$changed = false;
            self::$readOnly = false;
            self::$mirrorHealthy = self::$sql !== null && self::$state['ready'] && !self::$state['dirty'];
            GBDBEngine::boot();
            self::finish();
            register_shutdown_function(self::shutdownScope(...));
        } finally {
            self::$depth = 0;
            self::release();
        }
    }

    public static function run(callable $callback, bool $readOnly = false) :mixed {
        if (self::$depth > 0) {
            return $callback();
        }

        self::initialize();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $switchToSQL = false;
            self::acquire($readOnly ? LOCK_SH : LOCK_EX);
            self::$depth = 1;
            self::$changed = false;
            self::$readOnly = $readOnly;
            self::$requestCache = [];
            self::$pathRevisions = [];
            self::$cacheBytes = 0;

            try {
                self::loadState();
                self::connect();
                self::$mirrorHealthy = self::$sql !== null && self::$state['ready'] && !self::$state['dirty'];
                // With SQL as authority every file of one mutation commits together (data, meta, GDP journal).
                $result = self::$state['mode'] === 'sql' && !$readOnly && self::$sql !== null
                    ? self::$sql->transaction($callback)
                    : $callback();
                self::finish();

                return $result;
            } catch (GBDBWriteRequired $exception) {
                if (self::$changed || !$readOnly) {
                    throw $exception;
                }

                $readOnly = false;
            } catch (GBDBPrimaryFailure $exception) {
                $canRetry = self::$state['mode'] === 'gbdb' && !self::$changed && self::$sql !== null && self::$state['ready'] && !self::$state['dirty'] && $attempt < 2;

                if (!$canRetry) {
                    if (self::$mirrorHealthy && self::$state['mode'] === 'gbdb' && !$readOnly) {
                        self::activateSQL('Primärfehler während einer Änderung; laufenden Aufruf prüfen.');
                    }

                    throw $exception;
                }

                $switchToSQL = true;
            } finally {
                self::$depth = 0;
                self::release();
            }

            if (!$switchToSQL) {
                continue;
            }

            self::acquire(LOCK_EX);

            try {
                self::loadState();
                self::connect();
                self::activateSQL('Primärdaten nicht lesbar oder beschädigt.');
            } finally {
                self::release();
            }
        }

        throw new RuntimeException('GBDB konnte den Speicherzugriff nicht abschließen.');
    }

    private static function finish() :void {
        if (!self::$changed || !self::enabled()) {
            return;
        }

        if (self::$state['mode'] === 'sql' || self::$mirrorHealthy) {
            self::$state['dirty'] = false;
            self::$state['ready'] = true;
            self::$state['last_sync'] = time();
            self::$state['last_error'] = '';
            self::$sql?->setState(self::$state);
        }

        self::saveLocalState();
    }

    private static function activateSQL(string $reason) :void {
        if (self::$state['mode'] === 'sql') {
            return;
        }

        if (self::$sql === null || !self::$state['ready'] || (self::$state['dirty'] && !self::$mirrorHealthy)) {
            throw new GBDBPrimaryFailure('Kein vollständig synchronisierter SQL-Fallback verfügbar.');
        }

        self::$state['mode'] = 'sql';
        self::$state['dirty'] = false;
        self::$state['primary_pending'] = true;
        self::$state['generation']++;
        self::$state['reason'] = $reason;
        self::$sql->setState(self::$state);
        self::saveLocalState();
        clearstatcache();
    }

    /** Local mutations are atomic; an uncertain primary write is never replayed as a row insert. */
    public static function mutate(callable $primary, callable $sql) :bool {
        return self::run(function () use ($primary, $sql) :bool {
            if (self::$readOnly) {
                throw new GBDBWriteRequired('Für diesen Lesezugriff ist eine Speicherreparatur nötig.');
            }

            if (self::$state['mode'] === 'sql') {
                $sql(self::$sql);
                self::$changed = true;

                if (!$primary()) {
                    self::$state['primary_pending'] = true;
                }

                clearstatcache();

                return true;
            }

            if (!self::$changed && self::enabled()) {
                self::$state['dirty'] = true;
                self::saveLocalState();
            }

            if (!$primary()) {
                if (self::$mirrorHealthy) {
                    self::activateSQL('Schreibfehler im Primärspeicher.');
                    $sql(self::$sql);
                    self::$changed = true;

                    return true;
                }

                throw new GBDBPrimaryFailure('GBDB-Schreibzugriff fehlgeschlagen; kein aktueller SQL-Fallback verfügbar.');
            }

            self::$changed = true;

            if (self::$mirrorHealthy) {
                try {
                    $sql(self::$sql);
                } catch (Throwable $exception) {
                    self::$mirrorHealthy = false;
                    self::$state['ready'] = false;
                    self::$state['last_error'] = 'SQL-Synchronisation ausstehend.';
                    self::$state['last_retry'] = time();
                    self::$sql = null;
                    self::saveLocalState();
                }
            }

            clearstatcache();

            return true;
        });
    }

    public static function read(string $path) :string {
        return self::run(function () use ($path) :string {
            if (self::$state['mode'] === 'sql') {
                return self::$sql->read($path);
            }

            $payload = @file_get_contents(self::native($path));

            if ($payload === false) {
                throw new GBDBPrimaryFailure('GBDB-Datei konnte nicht gelesen werden: ' . $path);
            }

            return $payload;
        }, true);
    }

    /** Failover on corruption, rather than silently converting a broken table to an empty one. */
    public static function validated(string $path, callable $decode) :mixed {
        try {
            return $decode((string) file_get_contents($path));
        } catch (JsonException | UnexpectedValueException $exception) {
            throw new GBDBPrimaryFailure('Beschädigte GBDB-Daten: ' . $path, 0, $exception);
        }
    }

    public static function native(string $relative) :string {
        if (str_contains($relative, "\0") || str_contains($relative, '\\') || preg_match('~(?:^|/)\.\.(?:/|$)~', $relative)) {
            throw new InvalidArgumentException('Ungültiger GBDB-Speicherpfad.');
        }

        $root = GBDBConfig::dataPath();
        $path = $root . ($relative !== '' ? '/' . ltrim($relative, '/') : '');
        $current = $root;

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            $current .= '/' . $segment;

            if (is_link($current)) {
                throw new RuntimeException('Symbolische Links sind im GBDB-Datenverzeichnis nicht erlaubt.');
            }
        }

        return $path;
    }

    public static function stat(string $path) :array|false {
        return self::run(function () use ($path) :array|false {
            if (self::$state['mode'] === 'gbdb') {
                $stat = @stat(self::native($path));

                if ($stat !== false) {
                    return $stat;
                }

                if (self::$mirrorHealthy && self::$sql->entry($path, false) !== null) {
                    throw new GBDBPrimaryFailure('Ein bereits synchronisierter Primärpfad fehlt: ' . $path);
                }

                return false;
            }

            $entry = self::$sql->entry($path, false);

            if ($entry === null) {
                return false;
            }

            $mode = ($entry['kind'] === 'd' ? 0040000 : 0100000) | 0700;
            $modified = (int) $entry['modified'];
            $values = [0, 0, $mode, 1, getmyuid(), getmygid(), 0, (int) $entry['size'], $modified, $modified, $modified, -1, -1];
            $keys = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];

            return $values + array_combine($keys, $values);
        }, true);
    }

    public static function children(string $path) :array {
        return self::run(function () use ($path) :array {
            if (self::$state['mode'] === 'sql') {
                return self::$sql->children($path);
            }

            $entries = @scandir(self::native($path));

            if ($entries === false) {
                throw new GBDBPrimaryFailure('GBDB-Verzeichnis konnte nicht gelesen werden: ' . $path);
            }

            return array_values(array_diff($entries, ['.', '..']));
        }, true);
    }

    public static function write(string $path, string $payload, bool $append = false) :bool {
        $result = self::mutate(
            static function () use ($path, $payload, $append) :bool {
                $file = self::native($path);

                if (!$append) {
                    return self::nativeWrite($file, $payload);
                }

                $handle = @fopen($file, 'ab');

                if ($handle === false) {
                    return false;
                }

                try {
                    return self::writeAll($handle, $payload) && fflush($handle) && (!function_exists('fsync') || fsync($handle));
                } finally {
                    fclose($handle);
                }
            },
            static function (GBDBSQLStore $store) use ($path, $payload, $append) :void {
                if ($append) {
                    $store->append($path, $payload);
                } else {
                    $store->put($path, $payload);
                }
            }
        );
        self::invalidatePath($path);

        return $result;
    }

    public static function writeAll(mixed $handle, string $payload) :bool {
        $offset = 0;
        $length = strlen($payload);

        while ($offset < $length) {
            $written = fwrite($handle, substr($payload, $offset));

            if ($written === false || $written === 0) {
                return false;
            }

            $offset += $written;
        }

        return true;
    }

    private static function nativeWrite(string $file, string $payload) :bool {
        $directory = dirname($file);

        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return false;
        }

        $temporary = $file . '.tmp-' . bin2hex(random_bytes(8));
        $handle = @fopen($temporary, 'xb');

        if ($handle === false) {
            return false;
        }

        try {
            $ok = self::writeAll($handle, $payload) && fflush($handle) && (!function_exists('fsync') || fsync($handle));
        } finally {
            fclose($handle);
        }

        if (!$ok || !@rename($temporary, $file)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    public static function directory(string $path, bool $recursive) :bool {
        return self::mutate(
            static fn() :bool => is_dir(self::native($path)) || @mkdir(self::native($path), 0750, $recursive),
            static function (GBDBSQLStore $store) use ($path, $recursive) :void {
                $segments = explode('/', $path);
                $current = '';

                foreach ($segments as $index => $segment) {
                    $current .= ($current === '' ? '' : '/') . $segment;

                    if ($recursive || $index === count($segments) - 1) {
                        $store->put($current, '', true);
                    }
                }
            }
        );
    }

    public static function remove(string $path, bool $directory = false) :bool {
        if (self::stat($path) === false || ($directory && self::children($path) !== [])) {
            return false;
        }

        return self::mutate(
            static fn() :bool => $directory ? @rmdir(self::native($path)) : @unlink(self::native($path)),
            static function (GBDBSQLStore $store) use ($path, $directory) :void {
                if ($directory && $store->children($path) !== []) {
                    throw new RuntimeException('Das SQL-Verzeichnis ist nicht leer.');
                }

                $store->delete($path);
            }
        );
    }

    public static function rename(string $from, string $to) :bool {
        if (self::stat($from) === false) {
            return false;
        }

        $result = self::mutate(
            static fn() :bool => @rename(self::native($from), self::native($to)),
            static fn(GBDBSQLStore $store) => $store->rename($from, $to)
        );
        self::invalidatePath($from);
        self::invalidatePath($to);

        return $result;
    }

    private static function invalidatePath(string $path) :void {
        $uri = 'gbdbdata://store/' . ltrim($path, '/');
        self::$pathRevisions[$uri] = (self::$pathRevisions[$uri] ?? 0) + 1;
    }

    /** Cache only within the current locked operation; invalidate on writes to its dependencies. */
    public static function remember(string $name, array $dependencies, callable $load) :mixed {
        $versions = [];

        foreach ($dependencies as $path) {
            $path = preg_replace('~(?<!:)/{2,}~', '/', $path);
            $versions[] = self::$pathRevisions[$path] ?? 0;
        }

        $key = $name . ':' . implode(':', $versions);

        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        $before = memory_get_usage();
        $value = $load();
        $bytes = max(0, memory_get_usage() - $before);
        $limit = max(0, (int) GBDBConfig::get('storage', 'request_cache_bytes', 8388608));

        if ($bytes <= $limit && count(self::$requestCache) < 64 && self::$cacheBytes + $bytes <= $limit) {
            self::$requestCache[$key] = $value;
            self::$cacheBytes += $bytes;
        }

        return $value;
    }

    private static function validatePrimaryFile(string $file, string $payload) :void {
        if ($payload === '') {
            return;
            // An empty append log is a valid checkpoint.
        }

        $tableFile = str_ends_with($file, Vars::data_extension()) && str_contains($file, '/.storage/') && !str_contains($file, '/.blocks/');

        if (!$tableFile && !str_ends_with($file, '.json')) {
            return;
        }

        $plain = $tableFile && Vars::crypt_data() ? Crypt::decode($payload) : $payload;

        try {
            if (is_string($plain) && is_array(json_decode($plain, true, 512, JSON_THROW_ON_ERROR))) {
                return;
            }
        } catch (JsonException $exception) {
            // Append logs intentionally contain one separately encoded JSON object per line.
        }

        foreach (explode("\n", trim($payload)) as $line) {
            $decoded = Vars::crypt_data() && $tableFile ? Crypt::decode($line) : $line;
            $operation = is_string($decoded) ? json_decode($decoded, true, 512, JSON_THROW_ON_ERROR) : null;

            if (!is_array($operation) || !in_array($operation['op'] ?? '', ['ins', 'upd', 'del'], true)) {
                throw new UnexpectedValueException('Ungültige Primärdaten.');
            }
        }
    }

    private static function copyPrimaryToSQL() :array {
        if (self::$sql === null) {
            throw new RuntimeException('SQL-Verbindung fehlt.');
        }

        $root = GBDBConfig::dataPath();
        $files = 0;
        $bytes = 0;
        self::$state['dirty'] = true;
        self::saveLocalState();
        self::$sql->transaction(function () use ($root, &$files, &$bytes) :void {
            self::$sql->reset();
            self::$sql->put('', '', true);
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $entry) {
                if ($entry->isLink()) {
                    throw new RuntimeException('Symbolischer Link im Primärbestand.');
                }

                $relative = substr($entry->getPathname(), strlen($root) + 1);

                if ($entry->isDir()) {
                    self::$sql->put($relative, '', true);
                    continue;
                }

                if (!$entry->isFile()) {
                    throw new RuntimeException('Unbekannter Dateityp im Primärbestand.');
                }

                $payload = file_get_contents($entry->getPathname());

                if ($payload === false) {
                    throw new RuntimeException('Nicht lesbare Primärdatei.');
                }

                self::validatePrimaryFile($entry->getPathname(), $payload);
                self::$sql->put($relative, $payload);
                $files++;
                $bytes += strlen($payload);
            }

            self::$state['ready'] = true;
            self::$state['dirty'] = false;
            self::$state['last_sync'] = time();
            self::$state['last_error'] = '';
            self::$sql->setState(self::$state);
        });
        self::saveLocalState();

        return ['ok' => true, 'direction' => 'gbdb_to_sql', 'files' => $files, 'bytes' => $bytes];
    }

    /** Build a verified replacement next to the primary; keep the previous data directory. */
    private static function copySQLToPrimary() :array {
        $root = GBDBConfig::dataPath();
        $stage = dirname($root) . '/.gbdb-rebuild-' . bin2hex(random_bytes(8));
        $previous = dirname($root) . '/.gbdb-previous-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));

        if (!@mkdir($stage, 0750, true)) {
            throw new RuntimeException('Der Primärspeicher ist weiterhin nicht beschreibbar.');
        }

        $files = 0;

        foreach (self::$sql->entries() as $entry) {
            $path = $entry['path'];
            self::native($path);
            // Validate paths returned from SQL before touching the filesystem.
            $target = $stage . ($path === '' ? '' : '/' . $path);

            if ($entry['kind'] === 'd') {
                if (!is_dir($target) && !@mkdir($target, 0750, true)) {
                    throw new RuntimeException('Der Primärspeicher konnte nicht vollständig aufgebaut werden.');
                }

                continue;
            }

            $payload = self::$sql->read($path);

            if (!self::nativeWrite($target, $payload) || !hash_equals(hash('sha256', $payload), (string) hash_file('sha256', $target))) {
                throw new RuntimeException('Datenprüfung beim Wiederaufbau fehlgeschlagen.');
            }

            $files++;
        }

        $hadPrimary = file_exists($root);

        if ($hadPrimary && !@rename($root, $previous)) {
            throw new RuntimeException('Der bisherige Primärspeicher konnte nicht gesichert werden.');
        }

        if (!@rename($stage, $root)) {
            if ($hadPrimary) {
                @rename($previous, $root);
            }

            throw new RuntimeException('Der geprüfte Primärspeicher konnte nicht aktiviert werden.');
        }

        self::$state['primary_pending'] = false;
        self::$state['last_sync'] = time();
        self::$sql->setState(self::$state);
        self::saveLocalState();
        clearstatcache();

        return ['ok' => true, 'direction' => 'sql_to_gbdb', 'files' => $files, 'previous_preserved' => $hadPrimary];
    }

    public static function status() :array {
        return self::run(static fn() :array => [
            'mode' => self::$state['mode'],
            'enabled' => self::enabled(),
            'sql_available' => self::$sql !== null,
            'sql_ready' => self::$sql !== null && self::$state['ready'] && !self::$state['dirty'],
            'sync_pending' => self::$state['dirty'] || self::$state['primary_pending'],
            'generation' => self::$state['generation'],
            'last_sync' => self::$state['last_sync'],
            'last_error' => self::$state['last_error'],
            'reason' => self::$state['reason'] ?? '',
        ], true);
    }

    public static function synchronize() :array {
        return self::run(static function () :array {
            if (self::$sql === null) {
                throw new RuntimeException('SQL-Fallback ist nicht eingerichtet oder nicht erreichbar.');
            }

            $report = self::$state['mode'] === 'sql' ? self::copySQLToPrimary() : self::copyPrimaryToSQL();
            self::$mirrorHealthy = true;

            return $report;
        });
    }

    public static function switch(string $backend) :array {
        $backend = strtolower(trim($backend));

        if (!in_array($backend, ['gbdb', 'sql'], true)) {
            throw new InvalidArgumentException('Backend muss gbdb oder sql sein.');
        }

        return self::run(static function () use ($backend) :array {
            if ($backend === self::$state['mode']) {
                return self::status();
            }

            if ($backend === 'sql') {
                if (self::$state['dirty'] && self::$sql !== null) {
                    self::copyPrimaryToSQL();
                }

                self::activateSQL('Manuelle Umschaltung.');
            } else {
                self::copySQLToPrimary();
                self::$state['mode'] = 'gbdb';
                self::$state['generation']++;
                self::$state['reason'] = 'Manuelle Rückschaltung nach geprüftem Datenabgleich.';
                self::$sql->setState(self::$state);
                self::saveLocalState();
            }

            return self::status();
        });
    }

    /** Generator steps acquire a lock only while advancing, never while the caller consumes a row. */
    public static function iterate(callable $factory) :Generator {
        $generator = self::run(static function () use ($factory) :Generator {
            $iterator = $factory();
            $iterator->rewind();

            return $iterator;
        }, true);

        while ($generator->valid()) {
            yield $generator->key() => $generator->current();
            self::run(static fn() => $generator->next(), true);
        }
    }

    public static function shutdownScope() :void {
        try {
            $error = error_get_last();
            $fatal = $error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true);

            if (self::$depth > 0 && !$fatal) {
                self::finish();
            }

            if (!$fatal) {
                GBDBEngine::shutdown();
            }
        } catch (Throwable $exception) {
            error_log('[GBDB] Shutdown konnte nicht vollständig abgeschlossen werden.');
        } finally {
            self::$depth = 0;
            self::release();
        }
    }
}
