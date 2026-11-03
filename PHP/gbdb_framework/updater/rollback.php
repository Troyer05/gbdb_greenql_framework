<?php

declare(strict_types=1);

/**
 * Wiederherstellung nach einem fehlgeschlagenen Update – eigenständig, ohne andere Klassen.
 *
 * Vor jeder Installation wird diese Datei aus dem *laufenden* (alten) Framework in den
 * Arbeitsordner kopiert (rollback/rollback.php). Scheitert neuer Code so schwer, dass er sich
 * nicht laden lässt, nutzt der Wartungsschutz diese Kopie und stellt den alten Stand her.
 *
 * Grundlage ist backups/<job>/meta.json (Format 1):
 *   files    vorhandene Dateien, die das Update ersetzt, entfernt oder ergänzt (mit Sicherung)
 *   created  Dateien, die das Update neu anlegt (werden wieder entfernt)
 *   data     Sicherung des Datenordners (nur zurückgespielt, wenn Migrationen liefen)
 */
final class GBDBUpdateRollback {
    private function __construct() {
    }

    /**
     * @return array{ok: bool, errors: list<string>, restored: int, removed: int, data: bool}
     */
    public static function run(string $workDir, string $jobId, string $reason, bool $restoreData = false) :array {
        $result = ['ok' => true, 'errors' => [], 'restored' => 0, 'removed' => 0, 'data' => false];
        $backup = $workDir . '/backups/' . $jobId;
        $meta = is_file($backup . '/meta.json') ? json_decode((string) file_get_contents($backup . '/meta.json'), true) : null;

        if (!preg_match('/^[a-z0-9-]{6,64}$/', $jobId) || !is_array($meta) || ($meta['format'] ?? 0) !== 1) {
            $result['ok'] = false;
            $result['errors'][] = 'Backup-Metadaten fehlen oder sind ungültig: ' . $backup;
            self::finish($workDir, $jobId, $reason, $result);

            return $result;
        }

        self::log($workDir, $jobId, 'Rollback gestartet: ' . $reason);

        foreach ((array) ($meta['created'] ?? []) as $created) {
            $target = (string) ($created['target'] ?? '');

            if ($target === '' || !is_file($target)) {
                continue;
            }

            $hash = hash_file('sha256', $target);

            if ($hash !== ($created['sha256'] ?? '')) {
                $result['errors'][] = 'Neue Datei wurde zwischenzeitlich verändert und bleibt erhalten: ' . $target;

                continue;
            }

            if (@unlink($target)) {
                $result['removed']++;
            } else {
                $result['errors'][] = 'Neue Datei konnte nicht entfernt werden: ' . $target;
            }
        }

        foreach ((array) ($meta['files'] ?? []) as $file) {
            $target = (string) ($file['target'] ?? '');
            $source = $backup . '/' . (string) ($file['backup'] ?? '');

            if ($target === '' || !is_file($source) || hash_file('sha256', $source) !== ($file['sha256'] ?? '')) {
                $result['errors'][] = 'Sicherung fehlt oder ist beschädigt: ' . $target;

                continue;
            }

            if (is_file($target) && hash_file('sha256', $target) === $file['sha256']) {
                continue;
            }

            if (self::copy($source, $target)) {
                $result['restored']++;
            } else {
                $result['errors'][] = 'Datei konnte nicht zurückgeschrieben werden: ' . $target;
            }
        }

        if ($restoreData && is_array($meta['data'] ?? null)) {
            $result['data'] = true;
            $errors = self::restoreData((string) $meta['data']['root'], $backup . '/' . (string) $meta['data']['backup'], $backup . '/' . (string) $meta['data']['manifest']);
            array_push($result['errors'], ...$errors);
        }

        $installed = $workDir . '/state/installed.json';

        if (is_array($meta['installed_before'] ?? null)) {
            self::write($installed, json_encode($meta['installed_before'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        } elseif (array_key_exists('installed_before', $meta)) {
            @unlink($installed);
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $result['ok'] = $result['errors'] === [];
        self::finish($workDir, $jobId, $reason, $result);

        return $result;
    }

    /**
     * Datenordner auf den gesicherten Stand: gesicherte Dateien zurück, später entstandene entfernen.
     *
     * @return list<string>
     */
    private static function restoreData(string $root, string $backup, string $manifestFile) :array {
        $errors = [];
        $manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : null;

        if (!is_array($manifest) || !is_dir($root) || !is_dir($backup)) {
            return ['Datensicherung ist unvollständig; Daten wurden nicht zurückgespielt.'];
        }

        $known = array_fill_keys(array_keys($manifest), true);

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $item) {
            $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($root) + 1);

            if ($item->isFile() && !isset($known[$relative]) && !@unlink($item->getPathname())) {
                $errors[] = 'Datei im Datenordner konnte nicht entfernt werden: ' . $relative;
            }
        }

        foreach ($manifest as $relative => $hash) {
            $source = $backup . '/' . $relative;
            $target = $root . '/' . $relative;

            if (is_file($target) && hash_file('sha256', $target) === $hash) {
                continue;
            }

            if (!is_file($source) || !self::copy($source, $target)) {
                $errors[] = 'Datendatei konnte nicht zurückgeschrieben werden: ' . $relative;
            }
        }

        return $errors;
    }

    private static function copy(string $source, string $target) :bool {
        $folder = dirname($target);

        if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
            return false;
        }

        $temporary = $target . '.gbdb-rb-' . bin2hex(random_bytes(4));

        if (!@copy($source, $temporary)) {
            return false;
        }

        if (!@rename($temporary, $target)) {
            @unlink($temporary);

            return false;
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($target, true);
        }

        return true;
    }

    private static function write(string $file, string $content) :void {
        $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));

        if (@file_put_contents($temporary, $content, LOCK_EX) !== false) {
            @rename($temporary, $file);
        }
    }

    private static function log(string $workDir, string $jobId, string $message) :void {
        @file_put_contents($workDir . '/logs/' . $jobId . '.log', gmdate('Y-m-d H:i:s') . ' UTC  ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }

    /** @param array{ok: bool, errors: list<string>, restored: int, removed: int, data: bool} $result */
    private static function finish(string $workDir, string $jobId, string $reason, array $result) :void {
        $jobFile = $workDir . '/state/job.json';
        $job = is_file($jobFile) ? json_decode((string) file_get_contents($jobFile), true) : null;
        $job = is_array($job) ? $job : ['id' => $jobId];
        $job['status'] = $result['ok'] ? 'rolled_back' : 'rollback_failed';
        $job['error'] = $reason;
        $job['rollback'] = $result;
        $job['updated_at'] = time();
        self::write($jobFile, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

        foreach ($result['errors'] as $error) {
            self::log($workDir, $jobId, 'Rollback-Fehler: ' . $error);
        }

        self::log($workDir, $jobId, $result['ok']
            ? 'Rollback abgeschlossen (' . $result['restored'] . ' Dateien zurück, ' . $result['removed'] . ' neue entfernt' . ($result['data'] ? ', Daten zurückgespielt' : '') . ').'
            : 'Rollback UNVOLLSTÄNDIG. Wartungsmodus bleibt aktiv; Sicherung unter backups/' . $jobId . '.');
        $history = [
            'type' => 'update', 'job' => $jobId, 'from' => $job['from_version'] ?? '', 'to' => $job['version'] ?? '', 'trigger' => $job['trigger'] ?? '',
            'result' => $job['status'], 'error' => $reason, 'backup' => $jobId, 'rollback' => $result['ok'] ? 'vollständig' : 'unvollständig',
            'time' => gmdate('Y-m-d\TH:i:s\Z'),
        ] + (array) ($job['verification'] ?? []);
        @file_put_contents($workDir . '/state/history.jsonl', json_encode($history, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

        // Unvollständiger Rollback: sicherer Fehlerzustand, Wartungsmodus bleibt bestehen.
        if ($result['ok']) {
            @unlink($workDir . '/maintenance.json');
        }
    }
}
