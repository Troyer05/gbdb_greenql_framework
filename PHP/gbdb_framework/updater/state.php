<?php

declare(strict_types=1);

/**
 * Persistenter Zustand des Updaters im Arbeitsordner (außerhalb des Framework-Ordners):
 *
 *   state/trust_state.json   höchster bestätigter Vertrauensstand (Root, Versionen, Sequenzen)
 *   state/trust.json(.sig)   zuletzt bestätigte Vertrauensmetadaten
 *   state/status.json        Ergebnis der letzten Update-Suche, übersprungene Versionen, Planung
 *   state/installed.json     installierte Version mit Core-Dateimanifest
 *   state/job.json           laufender oder letzter Installationsauftrag
 *   state/history.jsonl      Update-Historie
 *   maintenance.json         Wartungsmodus während der Installation
 *   backups/, staging/, downloads/, logs/, rollback/
 *
 * Der Vertrauensstand liegt bewusst nicht in Datenbank- oder Update-Backups: Ein Restore oder
 * Rollback setzt ihn nie auf ältere Werte zurück.
 */
final class GBDBUpdateState {
    private function __construct() {
    }

    public static function dir(string $sub = '') :string {
        return GBDBUpdateSettings::workDir() . ($sub !== '' ? '/' . $sub : '');
    }

    public static function ensure() :void {
        $base = self::dir();

        foreach (['', 'state', 'backups', 'staging', 'downloads', 'logs', 'rollback'] as $sub) {
            $path = $base . ($sub !== '' ? '/' . $sub : '');

            if (!is_dir($path) && !@mkdir($path, 0770, true) && !is_dir($path)) {
                throw new RuntimeException('Arbeitsordner des Updaters kann nicht angelegt werden: ' . $path);
            }
        }

        if (!is_file($base . '/.htaccess')) {
            @file_put_contents($base . '/.htaccess', "Require all denied\n");
        }

        if (!is_file($base . '/index.html')) {
            @file_put_contents($base . '/index.html', '');
        }
    }

    public static function writeFile(string $path, string $content) :void {
        $folder = dirname($path);

        if (!is_dir($folder) && !@mkdir($folder, 0770, true) && !is_dir($folder)) {
            throw new RuntimeException('Ordner kann nicht angelegt werden: ' . $folder);
        }

        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));

        if (file_put_contents($temporary, $content, LOCK_EX) !== strlen($content) || !@rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException('Datei kann nicht geschrieben werden: ' . $path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(string $name, array $default = []) :array {
        $file = self::dir('state/' . $name . '.json');

        if (!is_file($file)) {
            return $default;
        }

        $data = json_decode((string) @file_get_contents($file), true);

        return is_array($data) ? $data : $default;
    }

    /** @param array<string, mixed> $data */
    public static function write(string $name, array $data) :void {
        self::ensure();
        self::writeFile(self::dir('state/' . $name . '.json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    }

    public static function remove(string $name) :void {
        @unlink(self::dir('state/' . $name . '.json'));
    }

    // ------------------------------------------------------------------ Sperre

    /**
     * Exklusive Sperre für eine Update-Aktion; null, wenn bereits ein anderer Vorgang läuft.
     * Die Sperre hängt am Prozess (flock): Ein abgebrochener Aufruf hinterlässt keine Dauersperre.
     *
     * @return resource|null
     */
    public static function lock() {
        self::ensure();
        $handle = @fopen(self::dir('state/update.lock'), 'c+');

        if ($handle === false) {
            throw new RuntimeException('Update-Sperre kann nicht angelegt werden.');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    /** @param resource|null $handle */
    public static function unlock($handle) :void {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public static function isLocked() :bool {
        $handle = self::lock();

        if ($handle === null) {
            return true;
        }

        self::unlock($handle);

        return false;
    }

    // ------------------------------------------------------------------ Vertrauensstand

    /**
     * Vertrauenswürdiger Zustand: gespeichert oder aus dem fest integrierten Anker.
     *
     * @return array<string, mixed>
     */
    public static function trustState() :array {
        $stored = self::read('trust_state');

        if ($stored !== []) {
            return [
                'root_keys' => GBDBUpdateTrust::keySet($stored['root_keys'] ?? null, 'Gespeicherter Root-Schlüssel'),
                'root_threshold' => (int) ($stored['root_threshold'] ?? 1),
                'recovery_keys' => ($stored['recovery_keys'] ?? []) === [] ? [] : GBDBUpdateTrust::keySet($stored['recovery_keys'], 'Gespeicherter Recovery-Schlüssel'),
                'recovery_threshold' => (int) ($stored['recovery_threshold'] ?? 0),
                'trust_version' => (int) ($stored['trust_version'] ?? 0),
                'trust_hash' => (string) ($stored['trust_hash'] ?? ''),
                'catalog_sequence' => (int) ($stored['catalog_sequence'] ?? 0),
                'catalog_hash' => (string) ($stored['catalog_hash'] ?? ''),
                'highest_release_sequence' => (int) ($stored['highest_release_sequence'] ?? 0),
                'stored' => true,
            ];
        }

        $anchorFile = GBDBUpdateSettings::frameworkRoot() . '/updater/trust_anchor.json';

        if (!is_file($anchorFile)) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::NO_TRUST_ANCHOR, 'Der fest integrierte Vertrauensanker (updater/trust_anchor.json) fehlt.');
        }

        $state = GBDBUpdateTrust::anchorState((string) file_get_contents($anchorFile));
        $installed = GBDBUpdateVersion::info();

        return $state + ['catalog_sequence' => 0, 'catalog_hash' => '', 'highest_release_sequence' => $installed['release_sequence'], 'stored' => false];
    }

    /**
     * Neuen Vertrauensstand übernehmen – nur aufsteigend (Rollback-Schutz auch gegen Fehlbedienung).
     *
     * @param array<string, mixed> $state
     */
    public static function saveTrustState(array $state, ?string $trustBytes = null, ?string $trustSignature = null) :void {
        $current = self::read('trust_state');

        foreach (['trust_version', 'catalog_sequence', 'highest_release_sequence'] as $field) {
            if ((int) ($state[$field] ?? 0) < (int) ($current[$field] ?? 0)) {
                throw new GBDBUpdateTrustException(GBDBUpdateTrust::ROLLBACK_DETECTED, 'Ein älterer Vertrauensstand darf den gespeicherten nicht ersetzen (' . $field . ').');
            }
        }

        self::write('trust_state', [
            'root_keys' => GBDBUpdateTrust::keyList($state['root_keys']),
            'root_threshold' => $state['root_threshold'],
            'recovery_keys' => $state['recovery_keys'] === [] ? [] : GBDBUpdateTrust::keyList($state['recovery_keys']),
            'recovery_threshold' => $state['recovery_threshold'],
            'trust_version' => $state['trust_version'],
            'trust_hash' => $state['trust_hash'],
            'catalog_sequence' => $state['catalog_sequence'],
            'catalog_hash' => $state['catalog_hash'],
            'highest_release_sequence' => $state['highest_release_sequence'],
            'updated_at' => GBDBUpdateTrust::formatTime(time()),
        ]);

        if ($trustBytes !== null && $trustSignature !== null) {
            self::writeFile(self::dir('state/trust.json'), $trustBytes);
            self::writeFile(self::dir('state/trust.json.sig'), $trustSignature);
        }
    }

    // ------------------------------------------------------------------ Historie und Protokolle

    /** @param array<string, mixed> $entry */
    public static function addHistory(array $entry) :void {
        self::ensure();
        $line = json_encode($entry + ['time' => GBDBUpdateTrust::formatTime(time())], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents(self::dir('state/history.jsonl'), $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function history(int $limit = 50) :array {
        $file = self::dir('state/history.jsonl');
        $lines = is_file($file) ? (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
        $entries = [];

        foreach (array_reverse(array_slice($lines, -$limit)) as $line) {
            $entry = json_decode($line, true);

            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    public static function log(string $jobId, string $message) :void {
        if (!preg_match('/^[a-z0-9-]{6,64}$/', $jobId)) {
            return;
        }

        @file_put_contents(self::dir('logs/' . $jobId . '.log'), gmdate('Y-m-d H:i:s') . ' UTC  ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function logText(string $jobId) :string {
        $file = self::dir('logs/' . $jobId . '.log');

        return preg_match('/^[a-z0-9-]{6,64}$/', $jobId) && is_file($file) ? (string) file_get_contents($file) : '';
    }

    /** Sicherheitsereignisse (Signatur-, Schlüssel- oder Rollback-Fehler) dauerhaft festhalten. */
    public static function securityEvent(string $status, string $message, string $keyId = '') :void {
        self::ensure();
        $line = json_encode(['time' => GBDBUpdateTrust::formatTime(time()), 'status' => $status, 'message' => $message, 'key_id' => $keyId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents(self::dir('logs/security.jsonl'), $line, FILE_APPEND | LOCK_EX);
    }

    // ------------------------------------------------------------------ Wartungsmodus

    /**
     * @return array<string, mixed>|null
     */
    public static function maintenance() :?array {
        $file = self::dir('maintenance.json');

        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) @file_get_contents($file), true);

        return is_array($data) ? $data : ['job' => '', 'since' => 0];
    }

    public static function setMaintenance(string $jobId) :void {
        self::writeFile(self::dir('maintenance.json'), json_encode(['job' => $jobId, 'since' => time()], JSON_THROW_ON_ERROR) . "\n");
    }

    public static function clearMaintenance() :void {
        @unlink(self::dir('maintenance.json'));
    }

    // ------------------------------------------------------------------ Dateien

    public static function removeTree(string $path) :void {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    public static function newId() :string {
        return gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
    }
}
