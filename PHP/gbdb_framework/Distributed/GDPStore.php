<?php

declare(strict_types=1);

/**
 * Logische GDP-Systemablage (Konzept Kapitel 08: „_gdp_system“).
 *
 * Alle Dateien liegen unter <Datenbestand>/.gdp und werden ausschließlich über
 * den gbdbdata://-Speicher geschrieben. Dadurch gelten für Journal, Cursor und
 * Policies exakt dieselben Garantien wie für Nutzdaten: atomares Ersetzen,
 * fsync, eine Schreibautorität pro Installation und automatische Spiegelung in
 * den SQL-Fallback. Ein Storage-Wechsel erzeugt so keine zweite Journalhistorie.
 */
final class GDPStore {
    public const SYSTEM_INSTANCE = '__gdp_system';

    public static function root() :string {
        return Vars::data_root() . '/.gdp';
    }

    public static function path(string ...$segments) :string {
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || !preg_match('/^[A-Za-z0-9_.-]{1,160}$/D', $segment)) {
                throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültiger GDP-Speicherpfad.');
            }
        }

        return self::root() . ($segments === [] ? '' : '/' . implode('/', $segments));
    }

    /**
     * Exklusiver Abschnitt über den gemeinsamen GBDB-Speicher-Lock.
     */
    public static function transaction(callable $callback) :mixed {
        return GBDBRuntime::run($callback);
    }

    /**
     * Lesender Abschnitt. Schreibt er doch, wiederholt die Runtime exklusiv.
     */
    public static function read(callable $callback) :mixed {
        return GBDBRuntime::run($callback, true);
    }

    public static function exists(string $path) :bool {
        return is_file($path);
    }

    public static function readJson(string $path, array $default = []) :array {
        if (!is_file($path)) {
            return $default;
        }

        $raw = @file_get_contents($path);

        if ($raw === false) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Datei nicht lesbar.', ['file' => basename($path)]);
        }

        try {
            $value = json_decode(self::unseal($raw), true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new GDPIntegrityException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Datei ist beschädigt.', ['file' => basename($path)], $exception);
        }

        if (!is_array($value)) {
            throw new GDPIntegrityException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Datei enthält kein Objekt.', ['file' => basename($path)]);
        }

        return $value;
    }

    public static function writeJson(string $path, array $value, bool $sealed = false) :void {
        self::ensureDirectory(dirname($path));
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $payload = $sealed ? self::seal($json) : $json;

        if (@file_put_contents($path, $payload) !== strlen($payload)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Datei konnte nicht geschrieben werden.', ['file' => basename($path)]);
        }
    }

    public static function appendLine(string $path, string $line) :void {
        if (str_contains($line, "\n")) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Journalzeilen dürfen keinen Zeilenumbruch enthalten.');
        }

        self::ensureDirectory(dirname($path));
        $payload = $line . "\n";

        if (@file_put_contents($path, $payload, FILE_APPEND) !== strlen($payload)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Journal konnte nicht erweitert werden.', ['file' => basename($path)]);
        }
    }

    /**
     * @return list<string>
     */
    public static function readLines(string $path) :array {
        if (!is_file($path)) {
            return [];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Journal nicht lesbar.', ['file' => basename($path)]);
        }

        return $lines;
    }

    public static function delete(string $path) :void {
        if (is_file($path) && !@unlink($path)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Datei konnte nicht entfernt werden.', ['file' => basename($path)]);
        }
    }

    /**
     * @return list<string>
     */
    public static function listDirectory(string $path) :array {
        if (!is_dir($path)) {
            return [];
        }

        $entries = @scandir($path);

        if ($entries === false) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Verzeichnis nicht lesbar.', ['directory' => basename($path)]);
        }

        $result = array_values(array_filter($entries, static fn(string $entry) :bool => $entry !== '.' && $entry !== '..' && !str_contains($entry, '.tmp-')));
        sort($result, SORT_STRING);

        return $result;
    }

    public static function removeTree(string $path) :void {
        if (!is_dir($path)) {
            self::delete($path);

            return;
        }

        foreach (self::listDirectory($path) as $entry) {
            self::removeTree($path . '/' . $entry);
        }

        @rmdir($path);
    }

    public static function ensureDirectory(string $path) :void {
        if (!is_dir($path) && !@mkdir($path, 0750, true) && !is_dir($path)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Verzeichnis konnte nicht angelegt werden.', ['directory' => basename($path)]);
        }
    }

    /**
     * Nutzdaten im Journal werden wie Tabellen verschlüsselt, sobald crypt_data aktiv ist.
     */
    public static function seal(string $plain) :string {
        return Vars::crypt_data() ? 'c:' . Crypt::encode($plain) : 'p:' . $plain;
    }

    public static function unseal(string $stored) :string {
        if (str_starts_with($stored, 'p:')) {
            return substr($stored, 2);
        }

        if (str_starts_with($stored, 'c:')) {
            $plain = Crypt::decode(substr($stored, 2));

            if ($plain === null) {
                throw new GDPIntegrityException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Daten konnten nicht entschlüsselt werden.');
            }

            return $plain;
        }

        // Ältere oder unverschlüsselte Metadateien.
        return $stored;
    }
}

/**
 * Geheimnisse (Knotenschlüssel, Pairing-Codes) außerhalb des Datenbestands.
 *
 * Ablage: .config/.runtime/gdp-vault.php, durch .htaccess, einen exit-Guard und
 * die GBDB-Verschlüsselung geschützt. Logs und Diagnosen enthalten nie Werte aus
 * dem Tresor.
 */
final class GDPVault {
    private static ?array $cache = null;

    private static function file() :string {
        return GBDBConfig::runtimePath() . '/gdp-vault.php';
    }

    private static function lock(callable $callback) :mixed {
        $directory = GBDBConfig::runtimePath();

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Tresor kann nicht angelegt werden.');
        }

        $handle = fopen($directory . '/gdp-vault.lock', 'c+');

        if ($handle === false || !flock($handle, LOCK_EX)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Tresor kann nicht gesperrt werden.');
        }

        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public static function all() :array {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $file = self::file();

        if (!is_file($file)) {
            return self::$cache = [];
        }

        $raw = (string) file_get_contents($file);
        $offset = strpos($raw, "\n");
        $plain = $offset === false ? null : Crypt::decode(substr($raw, $offset + 1));

        if ($plain === null) {
            throw new GDPIntegrityException(GDPErrorCode::STORAGE_STATE_UNCERTAIN, 'GDP-Tresor ist nicht lesbar (Schlüssel geändert?).');
        }

        $value = json_decode($plain, true, 32, JSON_THROW_ON_ERROR);

        return self::$cache = is_array($value) ? $value : [];
    }

    public static function get(string $section, string $key) :mixed {
        return self::all()[$section][$key] ?? null;
    }

    /**
     * Ändert den Tresor atomar: Lesen, Ändern und Schreiben unter einem Dateilock.
     */
    public static function update(callable $mutator) :mixed {
        return self::lock(static function () use ($mutator) :mixed {
            self::$cache = null;
            $data = self::all();
            $result = $mutator($data);
            $payload = "<?php exit; ?>\n" . Crypt::encode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $file = self::file();
            $temporary = $file . '.' . bin2hex(random_bytes(8));

            if (file_put_contents($temporary, $payload, LOCK_EX) !== strlen($payload) || !@chmod($temporary, 0600) || !rename($temporary, $file)) {
                @unlink($temporary);
                throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Tresor konnte nicht gespeichert werden.');
            }

            self::$cache = $data;

            return $result;
        });
    }

    public static function forget() :void {
        self::$cache = null;
    }
}

/**
 * Replay-Schutz: jede Nonce wird je Knoten innerhalb des Zeitfensters nur einmal akzeptiert.
 */
final class GDPNonceCache {
    public static function remember(string $nodeId, string $nonce, int $ttl) :bool {
        $directory = GBDBConfig::runtimePath() . '/gdp-nonces';

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Replay-Schutz ist nicht beschreibbar.');
        }

        $key = hash('sha256', $nodeId . '|' . $nonce);
        $handle = fopen($directory . '/' . substr($key, 0, 2) . '.json', 'c+');

        if ($handle === false || !flock($handle, LOCK_EX)) {
            throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Replay-Schutz kann nicht gesperrt werden.');
        }

        try {
            $entries = json_decode((string) stream_get_contents($handle), true);
            $entries = is_array($entries) ? $entries : [];
            $now = time();

            foreach ($entries as $entryKey => $until) {
                if ((int) $until < $now) {
                    unset($entries[$entryKey]);
                }
            }

            if (isset($entries[$key])) {
                return false;
            }

            if (count($entries) >= 20000) {
                throw new GDPException(GDPErrorCode::RATE_LIMITED, 'Zu viele gleichzeitige GDP-Anfragen.');
            }

            $entries[$key] = $now + $ttl;
            rewind($handle);
            ftruncate($handle, 0);

            if (!GBDBRuntime::writeAll($handle, json_encode($entries, JSON_THROW_ON_ERROR)) || !fflush($handle)) {
                throw new GDPStorageUnavailableException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'GDP-Replay-Schutz konnte nicht gespeichert werden.');
            }

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
