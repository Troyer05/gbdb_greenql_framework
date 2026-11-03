<?php

declare(strict_types=1);

/**
 * Installation eines Framework-Releases in Phasen (Konzept Abschnitt 8 und 9):
 *
 *   download   Vertrauenskette neu prüfen, ZIP laden, SHA-256 + Dateimanifest, Verify-API
 *   preflight  Kompatibilität, Schreibrechte, Speicherplatz, lokale Änderungen, Konflikte
 *   backup     betroffene Core-Dateien und Konfiguration sichern (außerhalb des Ziels)
 *   staging    ZIP entpacken, Dateien, Syntax, Struktur und Vertrauensanker prüfen
 *   apply      Wartungsmodus, erneute Offline-Prüfung, Datensicherung bei Migrationen, Dateien
 *   verify     nächste Anfrage mit neuem Code: Selbstprüfung, Migrationen, Abschluss
 *
 * Jede Phase läuft in einer eigenen Anfrage unter der Update-Sperre; der Auftrag liegt in
 * state/job.json. Bis zur Phase apply bleibt die Installation unverändert.
 */
final class GBDBUpdateInstaller {
    private const PHASES = ['download', 'preflight', 'backup', 'staging', 'apply'];
    private const ACTIVE = ['running', 'needs_confirmation', 'applying', 'verify_pending', 'verifying'];
    private const CONFIRMATIONS = ['api_unavailable', 'conflicts', 'unknown_baseline'];

    /** Muss jedes Release enthalten, sonst wäre das Framework danach nicht lauffähig oder nicht mehr aktualisierbar. */
    private const REQUIRED_FILES = [
        'gbdb_framework/autoloader.php',
        'gbdb_framework/functions.php',
        'gbdb_framework/version.json',
        'gbdb_framework/runtime/config.php',
        'gbdb_framework/.config/.framework.env.php',
        'gbdb_framework/GBDB_GQL/db_engine/gbdb.php',
        'gbdb_framework/GBDB_GQL/greenql/greenql.php',
        'gbdb_framework/public/index.php',
        'gbdb_framework/updater/gate.php',
        'gbdb_framework/updater/updater.php',
        'gbdb_framework/updater/installer.php',
        'gbdb_framework/updater/rollback.php',
        'gbdb_framework/updater/trust.php',
        'gbdb_framework/updater/signature.php',
        'gbdb_framework/updater/trust_anchor.json',
    ];

    private function __construct() {
    }

    // ------------------------------------------------------------------ Auftrag

    /**
     * @return array<string, mixed>
     */
    public static function job() :array {
        return GBDBUpdateState::read('job');
    }

    public static function isActive(array $job) :bool {
        return in_array($job['status'] ?? '', self::ACTIVE, true);
    }

    /** @param array<string, mixed> $job */
    private static function save(array &$job) :void {
        $job['updated_at'] = time();
        GBDBUpdateState::write('job', $job);
    }

    private static function jobDir(array $job, string $sub = '') :string {
        return GBDBUpdateState::dir('staging/' . $job['id'] . ($sub !== '' ? '/' . $sub : ''));
    }

    /**
     * Neuen Auftrag anlegen (manuell oder automatisch).
     *
     * @return array<string, mixed>
     */
    public static function start(string $version, string $trigger) :array {
        if (!GBDBUpdateTrust::isVersion($version) || !in_array($trigger, ['manual', 'auto'], true)) {
            throw new InvalidArgumentException('Ungültige Version oder ungültiger Auslöser.');
        }

        $lock = GBDBUpdateState::lock();

        if ($lock === null) {
            throw new RuntimeException('Es läuft bereits ein Update-Vorgang.');
        }

        try {
            if (self::isActive(self::job())) {
                throw new RuntimeException('Es gibt bereits einen offenen Update-Auftrag.');
            }

            $job = [
                'id' => GBDBUpdateState::newId(),
                'version' => $version,
                'from_version' => GBDBUpdateVersion::current(),
                'trigger' => $trigger,
                'status' => 'running',
                'phase' => 'download',
                'confirm' => [],
                'question' => null,
                'messages' => [],
                'created_at' => time(),
            ];
            self::save($job);
            GBDBUpdateState::log($job['id'], 'Auftrag angelegt: ' . $job['from_version'] . ' → ' . $version . ' (' . $trigger . ')');

            return $job;
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    /**
     * Admin-Bestätigung für einen offenen Rückfragepunkt (nie für Signatur- oder Checksum-Fehler).
     *
     * @return array<string, mixed>
     */
    public static function confirm(string $what) :array {
        $lock = self::lockOrFail();

        try {
            $job = self::job();

            if (($job['status'] ?? '') !== 'needs_confirmation' || ($job['question']['type'] ?? '') !== $what || !in_array($what, self::CONFIRMATIONS, true) || $job['trigger'] !== 'manual') {
                throw new RuntimeException('Für diesen Auftrag ist keine solche Bestätigung offen.');
            }

            $job['confirm'][$what] = time();
            $job['status'] = 'running';
            $job['question'] = null;
            self::save($job);
            GBDBUpdateState::log($job['id'], 'Administrator hat bestätigt: ' . $what);

            return $job;
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    /**
     * Auftrag vor der Installation abbrechen.
     *
     * @return array<string, mixed>
     */
    public static function cancel() :array {
        $lock = self::lockOrFail();

        try {
            $job = self::job();

            if (!in_array($job['status'] ?? '', ['running', 'needs_confirmation'], true)) {
                throw new RuntimeException('Dieser Auftrag kann nicht mehr abgebrochen werden.');
            }

            self::fail($job, 'Vom Administrator abgebrochen.', 'cancelled');

            return $job;
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    /**
     * Nächste Phase ausführen.
     *
     * @return array<string, mixed>
     */
    public static function step() :array {
        $lock = self::lockOrFail();

        try {
            $job = self::job();

            if (($job['status'] ?? '') !== 'running' || !in_array($job['phase'] ?? '', self::PHASES, true)) {
                return $job;
            }

            @set_time_limit(600);
            @ignore_user_abort(true);
            GBDBUpdateState::log($job['id'], 'Phase ' . $job['phase'] . ' gestartet.');

            try {
                $next = match ($job['phase']) {
                    'download' => self::phaseDownload($job),
                    'preflight' => self::phasePreflight($job),
                    'backup' => self::phaseBackup($job),
                    'staging' => self::phaseStaging($job),
                    'apply' => self::phaseApply($job),
                };

                if ($job['status'] === 'running' && $next !== null) {
                    $job['phase'] = $next;
                }

                self::save($job);
            } catch (GBDBUpdateTrustException $exception) {
                GBDBUpdateState::securityEvent($exception->status, $exception->getMessage(), $exception->keyId);
                $job['verification']['status'] = $exception->status;
                self::fail($job, $exception->getMessage(), 'failed', $exception->status, $exception->keyId);
            } catch (Throwable $exception) {
                self::fail($job, $exception->getMessage());
            }

            return $job;
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    /**
     * Automatisches Update: alle Phasen am Stück; jede Rückfrage beendet den Vorgang.
     */
    public static function runAutomatic(string $version) :void {
        $job = self::job();

        if (self::isActive($job)) {
            return;
        }

        $job = self::start($version, 'auto');

        for ($guard = 0; $guard < 10 && ($job['status'] ?? '') === 'running'; $guard++) {
            $job = self::step();
        }

        if (($job['status'] ?? '') === 'needs_confirmation') {
            $lock = self::lockOrFail();

            try {
                $job = self::job();
                self::fail($job, 'Automatisches Update abgebrochen: ' . (string) ($job['question']['text'] ?? 'Rückfrage erforderlich') . ' Bitte manuell im Update-Bereich installieren.');
            } finally {
                GBDBUpdateState::unlock($lock);
            }
        }
    }

    /** @return resource */
    private static function lockOrFail() {
        $lock = GBDBUpdateState::lock();

        if ($lock === null) {
            throw new RuntimeException('Es läuft gerade ein anderer Update-Vorgang. Bitte kurz warten.');
        }

        return $lock;
    }

    /** @param array<string, mixed> $job */
    private static function ask(array &$job, string $type, string $text, array $details = []) :void {
        $job['status'] = 'needs_confirmation';
        $job['question'] = ['type' => $type, 'text' => $text, 'details' => $details];
        GBDBUpdateState::log($job['id'], 'Rückfrage: ' . $text);
    }

    /** @param array<string, mixed> $job */
    private static function fail(array &$job, string $message, string $status = 'failed', string $code = '', string $keyId = '') :void {
        $job['status'] = $status;
        $job['error'] = $message;
        $job['error_code'] = $code;
        $job['key_id'] = $keyId;
        self::save($job);
        GBDBUpdateState::log($job['id'], ($status === 'cancelled' ? 'Abgebrochen: ' : 'Fehlgeschlagen: ') . $message);
        GBDBUpdateState::addHistory(self::historyEntry($job, $status, $message, 'nicht erforderlich (keine Änderung)'));
        GBDBUpdateState::removeTree(self::jobDir($job));
        @unlink(GBDBUpdateState::dir('downloads/' . $job['id'] . '.zip'));
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private static function historyEntry(array $job, string $result, string $error, string $rollback) :array {
        $verification = (array) ($job['verification'] ?? []);

        return [
            'type' => 'update',
            'job' => $job['id'],
            'from' => $job['from_version'] ?? '',
            'to' => $job['version'] ?? '',
            'trigger' => $job['trigger'] ?? '',
            'result' => $result,
            'error' => $error,
            'sha256' => $verification['sha256'] ?? '',
            'checksum' => $verification['checksum'] ?? '',
            'signature' => $verification['signature'] ?? '',
            'signing_key_id' => $verification['signing_key_id'] ?? '',
            'trust_version' => $verification['trust_version'] ?? 0,
            'catalog_sequence' => $verification['catalog_sequence'] ?? 0,
            'api' => $verification['api'] ?? '',
            'backup' => is_dir(GBDBUpdateState::dir('backups/' . $job['id'])) ? $job['id'] : '',
            'rollback' => $rollback,
        ];
    }

    // ------------------------------------------------------------------ Phase download

    /** @param array<string, mixed> $job */
    private static function phaseDownload(array &$job) :?string {
        $dir = self::jobDir($job, 'meta');
        GBDBUpdateState::removeTree(self::jobDir($job));

        if (!@mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Staging-Ordner kann nicht angelegt werden.');
        }

        $metadata = GBDBFrameworkUpdater::verifiedMetadata();
        $checked = GBDBFrameworkUpdater::verifiedManifest($metadata, $job['version']);
        $manifest = $checked['manifest'];
        $installed = GBDBUpdateVersion::current();

        if (GBDBUpdateTrust::compareVersions($job['version'], $installed) <= 0) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INCOMPATIBLE, 'Version ' . $job['version'] . ' ist nicht neuer als die installierte Version ' . $installed . '. Downgrades und gleiche Versionen werden nicht installiert.');
        }

        $incompatible = GBDBFrameworkUpdater::compatibility($manifest);

        if ($incompatible !== []) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INCOMPATIBLE, implode(' ', $incompatible));
        }

        foreach (['manifest.json' => $checked['bytes'], 'manifest.json.sig' => $checked['signature'], 'releases.json' => $metadata['catalog_bytes'], 'releases.json.sig' => $metadata['catalog_signature']] as $name => $bytes) {
            GBDBUpdateState::writeFile($dir . '/' . $name, $bytes);
        }

        // Ausgangsstand für die Erkennung lokaler Änderungen: Manifest der installierten Version.
        $baseline = GBDBUpdateState::read('installed');

        if (($baseline['version'] ?? null) !== $installed && isset($metadata['catalog']['_releases'][$installed])) {
            try {
                $current = GBDBFrameworkUpdater::verifiedManifest($metadata, $installed)['manifest'];
                $baseline = ['version' => $installed, 'files' => $current['files'], 'config_files' => $current['config_files'], 'from' => 'server'];
            } catch (Throwable $exception) {
                GBDBUpdateState::log($job['id'], 'Manifest der installierten Version nicht verwendbar: ' . $exception->getMessage());
            }
        }

        GBDBUpdateState::writeFile($dir . '/baseline.json', json_encode(($baseline['version'] ?? null) === $installed ? $baseline : [], JSON_UNESCAPED_SLASHES) . "\n");
        $zip = GBDBUpdateState::dir('downloads/' . $job['id'] . '.zip');
        $response = GBDBUpdateHttp::download(rtrim(GBDBUpdateSettings::url('update_server'), '/') . '/' . $manifest['filename'], $zip, (int) $manifest['size'] + 1024, 600);

        if (!$response['ok']) {
            throw new RuntimeException('Download fehlgeschlagen: ' . $response['error']);
        }

        GBDBUpdateTrust::verifyZip($zip, $manifest);
        $job['verification'] = [
            'status' => GBDBUpdateTrust::VERIFIED,
            'sha256' => $manifest['sha256'],
            'checksum' => 'ok',
            'signature' => 'ok',
            'signing_key_id' => $manifest['signing_key_id'],
            'trust_version' => $metadata['state']['trust_version'],
            'catalog_sequence' => $metadata['state']['catalog_sequence'],
            'api' => 'nicht geprüft',
        ];
        $job['release'] = ['sha256' => $manifest['sha256'], 'size' => $manifest['size'], 'requires_migration' => $manifest['requires_migration'], 'published_at' => $manifest['published_at']];
        GBDBUpdateState::log($job['id'], 'Vertrauenskette, Manifest-Signatur (' . $manifest['signing_key_id'] . '), SHA-256 und Dateimanifest der ZIP bestätigt.');
        $api = self::verifyViaApi($manifest);
        $job['verification']['api'] = $api['text'];
        GBDBUpdateState::log($job['id'], 'Verify-API: ' . $api['text']);

        if ($api['result'] === 'rejected') {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::API_REJECTED, 'Der offizielle Verifizierungsserver kennt diese ZIP nicht oder meldet eine abweichende Checksum. Installation blockiert.');
        }

        if ($api['result'] !== 'verified') {
            $job['verification']['status'] = GBDBUpdateTrust::VERIFIED_API_UNAVAILABLE;

            if (empty($job['confirm']['api_unavailable'])) {
                if ($job['trigger'] === 'auto') {
                    throw new RuntimeException('Die zusätzliche API-Prüfung war nicht möglich (' . $api['text'] . '). Automatische Updates installieren nur vollständig verifizierte Releases.');
                }

                self::ask($job, 'api_unavailable', 'Das Update konnte nicht vollständig über den offiziellen Verifizierungsserver geprüft werden. Die kryptografische Vertrauenskette ist gültig.', ['api' => $api['text']]);

                return 'preflight';
            }
        }

        return 'preflight';
    }

    /**
     * Zusätzliche Prüfung beim Herausgeber; ersetzt nie die Signaturprüfung.
     *
     * @param array<string, mixed> $manifest
     * @return array{result: string, text: string}
     */
    private static function verifyViaApi(array $manifest) :array {
        if (!GBDBUpdateSettings::all()['verification']['verify_via_api']) {
            return ['result' => 'unavailable', 'text' => 'in der Konfiguration deaktiviert'];
        }

        try {
            $response = GBDBUpdateHttp::postJson(GBDBUpdateSettings::url('verify_api'), ['version' => $manifest['version'], 'filename' => $manifest['filename'], 'sha256' => $manifest['sha256']], 65536, 20);
        } catch (Throwable $exception) {
            return ['result' => 'unavailable', 'text' => $exception->getMessage()];
        }

        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            return ['result' => 'unavailable', 'text' => $response['error'] !== '' ? $response['error'] : 'unlesbare Antwort'];
        }

        if (($data['verified'] ?? null) === true && ($data['sha256'] ?? null) === $manifest['sha256'] && ($data['version'] ?? null) === $manifest['version']) {
            return ['result' => 'verified', 'text' => 'bestätigt'];
        }

        // Nur eine eindeutige, fachliche Ablehnung blockiert; Serverfehler gelten als „nicht erreichbar“.
        if (($data['verified'] ?? null) === false && in_array($data['reason'] ?? '', ['unknown_version', 'checksum_mismatch', 'filename_mismatch', 'release_revoked'], true)) {
            return ['result' => 'rejected', 'text' => 'abgelehnt (' . $data['reason'] . ')'];
        }

        return ['result' => 'unavailable', 'text' => 'keine verwertbare Antwort (HTTP ' . $response['status'] . ')'];
    }

    // ------------------------------------------------------------------ Phase preflight

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private static function manifest(array $job) :array {
        $data = json_decode((string) @file_get_contents(self::jobDir($job, 'meta/manifest.json')), true);

        if (!is_array($data)) {
            throw new RuntimeException('Gespeichertes Manifest fehlt.');
        }

        return $data;
    }

    /** Zielpfad einer Release-Datei; null für Pfade, die das Release nicht verwalten darf. */
    public static function target(string $path) :?string {
        if (str_starts_with($path, 'gbdb_framework/')) {
            return GBDBUpdateSettings::frameworkRoot() . substr($path, strlen('gbdb_framework'));
        }

        return !str_contains($path, '/') ? GBDBUpdateSettings::projectRoot() . '/' . $path : null;
    }

    /** Grund, warum ein Ziel geschützt ist (persistente Daten, Schlüssel, Logs, Arbeitsordner). */
    public static function protectedReason(string $target) :string {
        $framework = GBDBUpdateSettings::frameworkRoot();
        $areas = [
            $framework . '/.config/.runtime' => 'Schlüssel und Laufzeitgeheimnisse',
            $framework . '/.logs' => 'Protokolle',
            GBDBUpdateSettings::workDir() => 'Arbeitsordner des Updaters',
        ];

        try {
            $areas[GBDBConfig::dataPath()] = 'Datenordner';
        } catch (Throwable) {
        }

        foreach ($areas as $area => $reason) {
            if ($target === $area || str_starts_with($target, rtrim($area, '/') . '/')) {
                return $reason;
            }
        }

        return '';
    }

    private static function hashOrNull(string $file) :?string {
        return is_file($file) && !is_link($file) ? (hash_file('sha256', $file) ?: null) : null;
    }

    /**
     * Installationsplan: was mit jeder Datei passiert und warum.
     *
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $baseline
     * @return array<string, mixed>
     */
    public static function plan(array $manifest, array $baseline) :array {
        $baseFiles = (array) ($baseline['files'] ?? []);
        $baseConfig = (array) ($baseline['config_files'] ?? []);
        $hasBaseline = $baseFiles !== [];
        $config = array_fill_keys($manifest['config_files'], true);
        $entries = array_fill_keys($manifest['entry_files'], true);
        $project = GBDBUpdateSettings::projectRoot();
        $realProject = realpath($project) ?: $project;
        $manageEntries = !empty(GBDBUpdateState::read('installed')['entry_files_managed']);

        foreach ($manifest['entry_files'] as $entry) {
            $manageEntries = $manageEntries || is_file($project . '/' . $entry);
        }

        $actions = [];
        $problems = [];

        foreach ($manifest['files'] as $path => $info) {
            $target = self::target($path);
            $item = ['path' => $path, 'target' => $target, 'sha256' => $info['sha256'], 'size' => $info['size'], 'local' => null, 'baseline' => $baseFiles[$path]['sha256'] ?? null];

            if ($target === null) {
                $actions[] = $item + ['action' => 'skip', 'reason' => 'außerhalb des Framework-Bereichs'];

                continue;
            }

            if (isset($entries[$path]) && !$manageEntries) {
                $actions[] = $item + ['action' => 'skip', 'reason' => 'Einstiegsdateien werden in diesem Projekt nicht verwaltet'];

                continue;
            }

            $reason = self::protectedReason($target);

            if ($reason !== '' && !isset($config[$path])) {
                $actions[] = $item + ['action' => 'skip', 'reason' => 'geschützter Bereich: ' . $reason];

                continue;
            }

            $folder = dirname($target);

            while (!is_dir($folder) && $folder !== dirname($folder)) {
                $folder = dirname($folder);
            }

            $realFolder = realpath($folder) ?: '';

            if (is_link($target) || ($realFolder !== $realProject && !str_starts_with($realFolder . '/', rtrim($realProject, '/') . '/'))) {
                $problems[] = 'Symbolischer Link oder Ziel außerhalb des Projekts: ' . $path;
                $actions[] = $item + ['action' => 'skip', 'reason' => 'symbolischer Link'];

                continue;
            }

            $item['local'] = self::hashOrNull($target);

            if (isset($config[$path])) {
                if ($item['local'] === null) {
                    $action = 'install_config';
                } elseif (str_ends_with($path, '/.config/.framework.env.php')) {
                    $action = 'merge_config';
                } else {
                    $action = 'keep_config';
                }
            } elseif ($item['local'] === null) {
                $action = is_file($target) || is_dir($target) ? 'blocked' : 'create';
            } elseif ($item['local'] === $info['sha256']) {
                $action = 'none';
            } elseif ($item['baseline'] !== null) {
                if ($item['local'] === $item['baseline']) {
                    $action = 'update';
                } elseif ($info['sha256'] === $item['baseline']) {
                    // Release ändert die Datei nicht: die lokale Anpassung bleibt unangetastet.
                    $action = 'keep_modified';
                    $item['reason'] = 'lokal verändert, im Release unverändert';
                } else {
                    $action = 'replace_modified';
                }
            } else {
                $action = $hasBaseline ? (in_array($path, $baseConfig, true) ? 'update' : 'replace_foreign') : 'replace_unknown';
            }

            if ($action === 'blocked') {
                $problems[] = 'Am Zielpfad liegt ein Ordner oder eine nicht lesbare Datei: ' . $path;
            }

            $actions[] = $item + ['action' => $action];
        }

        // Veraltete Core-Dateien: nur entfernen, wenn eindeutig verwaltet und unverändert.
        foreach ($baseFiles as $path => $info) {
            if (isset($manifest['files'][$path]) || in_array($path, $baseConfig, true)) {
                continue;
            }

            $target = self::target((string) $path);

            if ($target === null || self::protectedReason($target) !== '' || !is_file($target)) {
                continue;
            }

            $local = self::hashOrNull($target);
            $actions[] = ['path' => $path, 'target' => $target, 'sha256' => null, 'size' => 0, 'local' => $local, 'baseline' => $info['sha256'] ?? null,
                'action' => $local === ($info['sha256'] ?? '') ? 'delete' : 'keep_modified', 'reason' => $local === ($info['sha256'] ?? '') ? '' : 'lokal verändert, bleibt erhalten'];
        }

        $summary = array_count_values(array_column($actions, 'action'));

        return [
            'actions' => $actions,
            'summary' => $summary,
            'problems' => $problems,
            'conflicts' => array_values(array_filter($actions, static fn(array $a) :bool => in_array($a['action'], ['replace_modified', 'replace_foreign'], true))),
            'unknown' => array_values(array_filter($actions, static fn(array $a) :bool => $a['action'] === 'replace_unknown')),
            'has_baseline' => $hasBaseline,
            'manage_entries' => $manageEntries,
        ];
    }

    /** @param array<string, mixed> $job */
    private static function phasePreflight(array &$job) :?string {
        $manifest = self::manifest($job);
        $baseline = json_decode((string) @file_get_contents(self::jobDir($job, 'meta/baseline.json')), true) ?: [];
        $incompatible = GBDBFrameworkUpdater::compatibility($manifest);

        if ($incompatible !== []) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INCOMPATIBLE, implode(' ', $incompatible));
        }

        $plan = self::plan($manifest, $baseline);
        $needed = (int) $manifest['size'];

        foreach ($plan['actions'] as $action) {
            $needed += (int) $action['size'] * 2;

            if (in_array($action['action'], ['update', 'replace_modified', 'replace_foreign', 'replace_unknown', 'merge_config', 'delete'], true)) {
                if (!is_writable($action['target']) || !is_writable(dirname($action['target']))) {
                    $plan['problems'][] = 'Keine Schreibrechte: ' . $action['path'];
                }
            } elseif (in_array($action['action'], ['create', 'install_config'], true)) {
                $folder = dirname($action['target']);

                while (!is_dir($folder) && $folder !== dirname($folder)) {
                    $folder = dirname($folder);
                }

                if (!is_writable($folder)) {
                    $plan['problems'][] = 'Keine Schreibrechte für neuen Pfad: ' . $action['path'];
                }
            }
        }

        if (self::pendingMigrations($manifest, $job['from_version']) !== []) {
            try {
                $needed += self::directorySize(GBDBConfig::dataPath());
            } catch (Throwable $exception) {
                $plan['problems'][] = 'Datenordner für die Sicherung vor Migrationen nicht lesbar: ' . $exception->getMessage();
            }
        }

        $free = @disk_free_space(GBDBUpdateState::dir());

        if ($free !== false && $free < $needed * 1.2) {
            $plan['problems'][] = 'Zu wenig Speicherplatz: benötigt etwa ' . self::bytes((int) ($needed * 1.2)) . ', frei ' . self::bytes((int) $free) . '.';
        }

        GBDBUpdateState::writeFile(self::jobDir($job, 'plan.json'), json_encode($plan, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $job['plan_summary'] = $plan['summary'];

        if ($plan['problems'] !== []) {
            throw new RuntimeException('Kritische Probleme vor der Installation: ' . implode(' | ', array_slice($plan['problems'], 0, 10)));
        }

        GBDBUpdateState::log($job['id'], 'Plan: ' . json_encode($plan['summary']));

        if ($plan['unknown'] !== [] && empty($job['confirm']['unknown_baseline'])) {
            self::ask($job, 'unknown_baseline', 'Für die installierte Version ist kein Dateimanifest bekannt. ' . count($plan['unknown']) . ' abweichende Dateien werden durch das offizielle Release ersetzt (Kopie im Backup).', ['files' => array_slice(array_column($plan['unknown'], 'path'), 0, 200)]);

            return 'backup';
        }

        if ($plan['conflicts'] !== [] && empty($job['confirm']['conflicts'])) {
            self::ask($job, 'conflicts', count($plan['conflicts']) . ' lokal veränderte oder fremde Dateien liegen an Pfaden des neuen Releases. Sie würden ersetzt (Kopie im Backup).', ['files' => array_slice(array_map(static fn(array $a) :string => $a['path'] . ($a['action'] === 'replace_foreign' ? ' (fremd)' : ' (verändert)'), $plan['conflicts']), 0, 200)]);

            return 'backup';
        }

        return 'backup';
    }

    // ------------------------------------------------------------------ Phase backup

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private static function loadPlan(array $job) :array {
        $plan = json_decode((string) @file_get_contents(self::jobDir($job, 'plan.json')), true);

        if (!is_array($plan)) {
            throw new RuntimeException('Installationsplan fehlt.');
        }

        return $plan;
    }

    /** @param array<string, mixed> $job */
    private static function phaseBackup(array &$job) :?string {
        $plan = self::loadPlan($job);
        $dir = GBDBUpdateState::dir('backups/' . $job['id']);
        GBDBUpdateState::removeTree($dir);

        if (!@mkdir($dir . '/files', 0770, true) && !is_dir($dir . '/files')) {
            throw new RuntimeException('Backup-Ordner kann nicht angelegt werden.');
        }

        $installedBefore = GBDBUpdateState::read('installed');
        $meta = [
            'format' => 1,
            'job' => $job['id'],
            'from_version' => $job['from_version'],
            'to_version' => $job['version'],
            'created_at' => time(),
            'framework_root' => GBDBUpdateSettings::frameworkRoot(),
            'project_root' => GBDBUpdateSettings::projectRoot(),
            'files' => [],
            'created' => [],
            'data' => null,
            'installed_before' => $installedBefore === [] ? null : $installedBefore,
        ];
        $number = 0;

        foreach ($plan['actions'] as $action) {
            if (in_array($action['action'], ['update', 'replace_modified', 'replace_foreign', 'replace_unknown', 'merge_config', 'delete'], true)) {
                $name = 'files/' . str_pad((string) ++$number, 6, '0', STR_PAD_LEFT);

                if (!@copy($action['target'], $dir . '/' . $name) || hash_file('sha256', $dir . '/' . $name) !== hash_file('sha256', $action['target'])) {
                    throw new RuntimeException('Sicherung fehlgeschlagen: ' . $action['path']);
                }

                $meta['files'][] = ['path' => $action['path'], 'target' => $action['target'], 'backup' => $name, 'sha256' => hash_file('sha256', $dir . '/' . $name)];
            } elseif (in_array($action['action'], ['create', 'install_config'], true)) {
                $meta['created'][] = ['path' => $action['path'], 'target' => $action['target'], 'sha256' => $action['sha256']];
            }
        }

        // Versionsstand gehört zur Wiederherstellung, auch wenn version.json unverändert bliebe.
        GBDBUpdateState::writeFile($dir . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

        // Rollback-Werkzeug des laufenden (alten) Frameworks bereitlegen.
        $rollback = GBDBUpdateSettings::frameworkRoot() . '/updater/rollback.php';

        if (!is_file($rollback) || !@copy($rollback, GBDBUpdateState::dir('rollback/rollback.php'))) {
            throw new RuntimeException('Das Rollback-Werkzeug konnte nicht bereitgelegt werden.');
        }

        $job['backup'] = $job['id'];
        GBDBUpdateState::log($job['id'], 'Backup erstellt: ' . count($meta['files']) . ' Dateien gesichert, ' . count($meta['created']) . ' neue Dateien vermerkt.');

        return 'staging';
    }

    // ------------------------------------------------------------------ Phase staging

    /** @param array<string, mixed> $job */
    private static function phaseStaging(array &$job) :?string {
        $manifest = self::manifest($job);
        $release = self::jobDir($job, 'release');
        GBDBUpdateState::removeTree($release);

        if (!@mkdir($release, 0770, true) && !is_dir($release)) {
            throw new RuntimeException('Staging-Ordner kann nicht angelegt werden.');
        }

        $zip = GBDBUpdateState::dir('downloads/' . $job['id'] . '.zip');
        GBDBUpdateTrust::verifyZip($zip, $manifest);
        GBDBUpdateZip::extractTo($zip, $release);
        $syntax = [];

        foreach ($manifest['files'] as $path => $info) {
            $file = $release . '/' . $path;

            if (!is_file($file) || hash_file('sha256', $file) !== $info['sha256']) {
                throw new GBDBUpdateTrustException(GBDBUpdateTrust::INVALID_CHECKSUM, 'Entpackte Datei stimmt nicht mit dem Dateimanifest überein: ' . $path);
            }

            if (str_ends_with($path, '.php')) {
                try {
                    token_get_all((string) file_get_contents($file), TOKEN_PARSE);
                } catch (ParseError $error) {
                    $syntax[] = $path . ' (Zeile ' . $error->getLine() . ')';
                }
            }
        }

        if ($syntax !== []) {
            throw new RuntimeException('Syntaxfehler im Release: ' . implode(', ', array_slice($syntax, 0, 10)));
        }

        $missing = array_values(array_diff(self::REQUIRED_FILES, array_keys($manifest['files'])));

        if ($missing !== []) {
            throw new RuntimeException('Dem Release fehlen benötigte Framework-Dateien: ' . implode(', ', $missing));
        }

        $version = json_decode((string) file_get_contents($release . '/gbdb_framework/version.json'), true);

        if (($version['version'] ?? null) !== $job['version']) {
            throw new RuntimeException('version.json im Release nennt eine andere Version.');
        }

        // Ein Release darf den Vertrauensanker nicht ändern: Er muss dem bestätigten Root entsprechen.
        $anchor = GBDBUpdateTrust::anchorState((string) file_get_contents($release . '/gbdb_framework/updater/trust_anchor.json'));
        $trusted = GBDBUpdateState::trustState();

        if ($anchor['root_keys'] != $trusted['root_keys']) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INVALID_METADATA, 'Der Vertrauensanker im Release weicht vom bestätigten Root-Schlüssel ab. Installation blockiert.');
        }

        $local = GBDBConfig::file();

        if (is_file($local)) {
            $merge = GBDBUpdateConfigMerger::merge((string) file_get_contents($local), (string) file_get_contents($release . '/gbdb_framework/.config/.framework.env.php'));
            $job['config_changes'] = $merge['changes'];
            $job['config_warnings'] = $merge['warnings'];
        }

        GBDBUpdateState::log($job['id'], 'Staging geprüft: ' . count($manifest['files']) . ' Dateien, Syntax, Struktur, Vertrauensanker und Konfigurationsabgleich in Ordnung.');

        return 'apply';
    }

    // ------------------------------------------------------------------ Phase apply

    /** @param array<string, mixed> $job */
    private static function phaseApply(array &$job) :?string {
        $manifest = self::manifest($job);
        $plan = self::loadPlan($job);
        $release = self::jobDir($job, 'release');
        self::recheckOffline($job, $manifest);

        GBDBUpdateState::setMaintenance($job['id']);
        $job['status'] = 'applying';
        $job['config_constants'] = array_keys(GBDBConfig::editableConstants());
        self::save($job);
        GBDBUpdateState::log($job['id'], 'Wartungsmodus aktiv, Installation beginnt.');

        try {
            $dataRoot = GBDBConfig::dataPath();
            $paths = self::dataPaths($dataRoot);
            GBDBUpdateState::writeFile(self::jobDir($job, 'data_paths.json'), json_encode($paths, JSON_UNESCAPED_SLASHES) . "\n");

            if (self::pendingMigrations($manifest, $job['from_version']) !== []) {
                self::backupData($job, $dataRoot);
            }

            foreach ($plan['actions'] as $action) {
                $source = $release . '/' . $action['path'];

                switch ($action['action']) {
                    case 'create':
                    case 'install_config':
                    case 'update':
                    case 'replace_modified':
                    case 'replace_foreign':
                    case 'replace_unknown':
                        self::put($action['target'], (string) file_get_contents($source), $action['sha256']);
                        break;
                    case 'merge_config':
                        $merge = GBDBUpdateConfigMerger::merge((string) file_get_contents($action['target']), (string) file_get_contents($source));
                        self::put($action['target'], $merge['source'], null);
                        $job['config_changes'] = $merge['changes'];
                        break;
                    case 'delete':
                        if (!@unlink($action['target'])) {
                            throw new RuntimeException('Veraltete Datei konnte nicht entfernt werden: ' . $action['path']);
                        }
                        break;
                }
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } catch (Throwable $exception) {
            GBDBUpdateState::log($job['id'], 'Installation fehlgeschlagen: ' . $exception->getMessage());
            $result = GBDBUpdateGate::rollback($job['id'], 'Installation fehlgeschlagen: ' . $exception->getMessage(), false);
            $job = self::job();
            $job['rollback'] = $result;

            return null;
        }

        $job['status'] = 'verify_pending';
        GBDBUpdateState::log($job['id'], 'Dateien installiert. Selbstprüfung folgt mit der nächsten Anfrage (neuer Code).');

        return null;
    }

    /** Datei atomar schreiben (gleicher Ordner, dann umbenennen) und Inhalt prüfen. */
    private static function put(string $target, string $content, ?string $sha256) :void {
        $folder = dirname($target);

        if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new RuntimeException('Ordner kann nicht angelegt werden: ' . $folder);
        }

        if ($sha256 !== null && !hash_equals($sha256, hash('sha256', $content))) {
            throw new RuntimeException('Inhalt weicht vom Manifest ab: ' . $target);
        }

        $temporary = $target . '.gbdb-up-' . bin2hex(random_bytes(4));
        $mode = is_file($target) ? (fileperms($target) & 0777) : 0644;

        if (file_put_contents($temporary, $content, LOCK_EX) !== strlen($content)) {
            @unlink($temporary);

            throw new RuntimeException('Datei kann nicht geschrieben werden: ' . $target);
        }

        @chmod($temporary, $mode);

        if (!@rename($temporary, $target)) {
            @unlink($temporary);

            throw new RuntimeException('Datei kann nicht ersetzt werden: ' . $target);
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($target, true);
        }
    }

    /**
     * Unmittelbar vor der Änderung erneut: ZIP gegen Manifest, Manifest gegen bestätigten Vertrauensstand.
     *
     * @param array<string, mixed> $job
     * @param array<string, mixed> $manifest
     */
    private static function recheckOffline(array $job, array $manifest) :void {
        $trustBytes = (string) @file_get_contents(GBDBUpdateState::dir('state/trust.json'));
        $trust = GBDBUpdateTrust::parseTrust($trustBytes);
        $state = GBDBUpdateState::trustState();

        if (!hash_equals((string) $state['trust_hash'], hash('sha256', $trustBytes))) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INVALID_METADATA, 'Gespeicherte Vertrauensmetadaten wurden verändert.');
        }

        $catalog = json_decode((string) file_get_contents(self::jobDir($job, 'meta/releases.json')), true);
        $entry = is_array($catalog) ? (array) (array_values(array_filter((array) ($catalog['releases'] ?? []), static fn($r) :bool => is_array($r) && ($r['version'] ?? '') === $job['version']))[0] ?? []) : [];

        if ($entry === []) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::INVALID_METADATA, 'Katalogeintrag des Releases fehlt.');
        }

        GBDBUpdateTrust::verifyManifest((string) file_get_contents(self::jobDir($job, 'meta/manifest.json')), (string) file_get_contents(self::jobDir($job, 'meta/manifest.json.sig')), $trust, $entry, time());
        GBDBUpdateTrust::verifyZip(GBDBUpdateState::dir('downloads/' . $job['id'] . '.zip'), $manifest);
    }

    /**
     * @return list<string>
     */
    private static function dataPaths(string $root) :array {
        $paths = [];

        if (!is_dir($root)) {
            return $paths;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $item) {
            $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($root) + 1);

            // Sperr- und Temporärdateien entstehen und verschwinden im laufenden Betrieb.
            if ($item->isFile() && !preg_match('~(^|/)\.(temp|locks?)(/|$)|\.lock$|\.tmp~', $relative)) {
                $paths[] = $relative;
            }
        }

        return $paths;
    }

    /** @param array<string, mixed> $job */
    private static function backupData(array $job, string $root) :void {
        $backup = GBDBUpdateState::dir('backups/' . $job['id']);
        $manifest = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($root) + 1);
            $target = $backup . '/data/' . $relative;

            if ((!is_dir(dirname($target)) && !@mkdir(dirname($target), 0770, true)) || !@copy($item->getPathname(), $target)) {
                throw new RuntimeException('Datensicherung fehlgeschlagen: ' . $relative);
            }

            $manifest[$relative] = hash_file('sha256', $target);

            if ($manifest[$relative] !== hash_file('sha256', $item->getPathname())) {
                throw new RuntimeException('Datensicherung ist nicht identisch: ' . $relative);
            }
        }

        GBDBUpdateState::writeFile($backup . '/data_files.json', json_encode($manifest, JSON_UNESCAPED_SLASHES) . "\n");
        $meta = json_decode((string) file_get_contents($backup . '/meta.json'), true);
        $meta['data'] = ['root' => $root, 'backup' => 'data', 'manifest' => 'data_files.json'];
        GBDBUpdateState::writeFile($backup . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        GBDBUpdateState::log($job['id'], 'Datenbestand gesichert (' . count($manifest) . ' Dateien) – Migrationen folgen.');
    }

    // ------------------------------------------------------------------ Selbstprüfung (neuer Code)

    /**
     * Selbstprüfung und Migrationen in der ersten Anfrage nach der Installation.
     *
     * @return array{ok: bool, message: string}
     */
    public static function verifyPending() :array {
        $lock = GBDBUpdateState::lock();

        if ($lock === null) {
            return ['ok' => false, 'message' => 'busy'];
        }

        try {
            $job = self::job();

            if (($job['status'] ?? '') !== 'verify_pending') {
                return ['ok' => ($job['status'] ?? '') === 'completed', 'message' => (string) ($job['status'] ?? '')];
            }

            $job['status'] = 'verifying';
            self::save($job);
            @set_time_limit(600);
            @ignore_user_abort(true);

            try {
                $checks = self::selfTest($job);
                $job['selftest'] = $checks;
                self::finalize($job);

                return ['ok' => true, 'message' => 'GBDB ' . $job['version'] . ' ist installiert und geprüft.'];
            } catch (Throwable $exception) {
                GBDBUpdateState::log($job['id'], 'Selbstprüfung fehlgeschlagen: ' . $exception->getMessage());
                $dirty = is_file(GBDBUpdateState::dir('backups/' . $job['id'] . '/data_dirty'));
                GBDBUpdateGate::rollback($job['id'], 'Selbstprüfung fehlgeschlagen: ' . $exception->getMessage(), $dirty);

                return ['ok' => false, 'message' => 'Das Update wurde zurückgenommen: ' . $exception->getMessage()];
            }
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    /**
     * @param array<string, mixed> $job
     * @return list<string>
     */
    private static function selfTest(array &$job) :array {
        $done = [];
        $manifest = self::manifest($job);
        $plan = self::loadPlan($job);

        foreach (['Vars', 'GBDBConfig', 'GBDB', 'GreenQL', 'GBDBFrameworkUpdater', 'GBDBUpdateTrust'] as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException('Framework-Core nicht vollständig ladbar: Klasse ' . $class . ' fehlt.');
            }
        }

        $done[] = 'Framework-Core ladbar';
        GBDBUpdateVersion::reset();

        if (GBDBUpdateVersion::current() !== $job['version']) {
            throw new RuntimeException('Neue Version ist nicht aktiv (gemeldet: ' . GBDBUpdateVersion::current() . ').');
        }

        $done[] = 'Neue Version ' . $job['version'] . ' aktiv';

        foreach ($plan['actions'] as $action) {
            if (in_array($action['action'], ['create', 'install_config', 'update', 'replace_modified', 'replace_foreign', 'replace_unknown', 'none'], true)
                && self::hashOrNull($action['target']) !== $action['sha256']) {
                throw new RuntimeException('Datei weicht nach der Installation vom Manifest ab: ' . $action['path']);
            }

            if ($action['action'] === 'delete' && is_file($action['target'])) {
                throw new RuntimeException('Veraltete Datei ist noch vorhanden: ' . $action['path']);
            }
        }

        $done[] = 'Dateimanifest korrekt';
        $constants = array_keys(GBDBConfig::editableConstants());
        $lost = array_diff((array) ($job['config_constants'] ?? []), $constants);

        if ($lost !== []) {
            throw new RuntimeException('Konfigurationsbereiche fehlen nach dem Update: ' . implode(', ', $lost));
        }

        $done[] = 'Konfiguration erhalten';
        $root = GBDBConfig::dataPath();

        if (!is_dir($root) || !is_readable($root)) {
            throw new RuntimeException('Datenordner nicht erreichbar: ' . $root);
        }

        if (self::pendingMigrations($manifest, $job['from_version']) === []) {
            $before = json_decode((string) @file_get_contents(self::jobDir($job, 'data_paths.json')), true) ?: [];
            $now = array_fill_keys(self::dataPaths($root), true);
            $missing = array_values(array_filter($before, static fn(string $path) :bool => !isset($now[$path])));

            if ($missing !== []) {
                throw new RuntimeException(count($missing) . ' Datendateien fehlen nach dem Update, z. B. ' . $missing[0]);
            }
        }

        $done[] = 'Daten vorhanden';
        GBDB::listInstances();
        $done[] = 'Datenbank erreichbar';
        $applied = self::runMigrations($job, $manifest);

        if ($applied !== []) {
            GBDB::listInstances();
            $done[] = 'Migrationen erfolgreich: ' . implode(', ', $applied);
        }

        GBDBUpdateState::log($job['id'], 'Selbstprüfung bestanden: ' . implode('; ', $done));

        return $done;
    }

    /**
     * Migrationsdatei: return ['up' => callable, 'down' => callable|null]. Ausgeführt wird
     * nur, was noch nicht als angewendet vermerkt ist; vorher ist der Datenbestand gesichert.
     *
     * @param array<string, mixed> $job
     * @param array<string, mixed> $manifest
     * @return list<string>
     */
    private static function runMigrations(array $job, array $manifest) :array {
        $state = GBDBUpdateState::read('migrations');
        $applied = [];
        $done = [];

        foreach (self::pendingMigrations($manifest, (string) $job['from_version']) as $migration) {
            if (!is_file(GBDBUpdateState::dir('backups/' . $job['id'] . '/data_files.json'))) {
                throw new RuntimeException('Migration ohne Datensicherung ist nicht erlaubt.');
            }

            $file = self::target((string) $migration['file']);
            $definition = $file !== null && is_file($file) ? (static fn(string $path) => require $path)($file) : null;

            if (!is_array($definition) || !is_callable($definition['up'] ?? null)) {
                throw new RuntimeException('Migration ' . $migration['id'] . ' ist ungültig.');
            }

            @touch(GBDBUpdateState::dir('backups/' . $job['id'] . '/data_dirty'));

            try {
                ($definition['up'])();
            } catch (Throwable $exception) {
                foreach (array_reverse($applied) as $previous) {
                    if (is_callable($previous['down'] ?? null)) {
                        try {
                            ($previous['down'])();
                        } catch (Throwable) {
                        }
                    }
                }

                throw new RuntimeException('Migration ' . $migration['id'] . ' fehlgeschlagen: ' . $exception->getMessage());
            }

            $applied[] = $definition;
            $done[] = (string) $migration['id'];
            $state[$migration['id']] = ['version' => $job['version'], 'at' => GBDBUpdateTrust::formatTime(time())];
        }

        if ($done !== []) {
            GBDBUpdateState::write('migrations', $state);
        }

        return $done;
    }

    /** @param array<string, mixed> $job */
    private static function finalize(array &$job) :void {
        $manifest = self::manifest($job);
        $plan = self::loadPlan($job);
        $managed = [];

        foreach ($plan['actions'] as $action) {
            if ($action['sha256'] !== null && !in_array($action['action'], ['skip', 'keep_config', 'install_config', 'merge_config'], true) && !in_array($action['path'], $manifest['config_files'], true)) {
                $managed[$action['path']] = ['sha256' => $action['sha256'], 'size' => $action['size']];
            }
        }

        GBDBUpdateState::write('installed', [
            'version' => $job['version'],
            'release_sequence' => $manifest['release_sequence'],
            'published_at' => $manifest['published_at'],
            'manifest_sha256' => hash('sha256', (string) file_get_contents(self::jobDir($job, 'meta/manifest.json'))),
            'signing_key_id' => $manifest['signing_key_id'],
            'trust_version' => $job['verification']['trust_version'] ?? 0,
            'catalog_sequence' => $job['verification']['catalog_sequence'] ?? 0,
            'entry_files_managed' => (bool) $plan['manage_entries'],
            'config_files' => $manifest['config_files'],
            'files' => $managed,
            'installed_at' => GBDBUpdateTrust::formatTime(time()),
        ]);

        $trust = GBDBUpdateState::trustState();
        $trust['highest_release_sequence'] = max($trust['highest_release_sequence'], (int) $manifest['release_sequence']);
        GBDBUpdateState::saveTrustState($trust);

        $job['status'] = 'completed';
        $job['completed_at'] = time();
        self::save($job);
        GBDBUpdateState::addHistory(self::historyEntry($job, 'completed', '', 'nicht erforderlich'));
        GBDBUpdateState::log($job['id'], 'Update auf ' . $job['version'] . ' abgeschlossen.');
        GBDBUpdateState::clearMaintenance();
        GBDBUpdateState::removeTree(self::jobDir($job));
        @unlink(GBDBUpdateState::dir('downloads/' . $job['id'] . '.zip'));
        self::pruneBackups();

        $status = GBDBUpdateState::read('status');

        if (is_array($status['available'] ?? null) && GBDBUpdateTrust::compareVersions((string) $status['available']['version'], $job['version']) <= 0) {
            $status['available'] = null;
            $status['state'] = 'up_to_date';
            $status['message'] = 'Das Framework ist aktuell.';
        }

        if (($status['scheduled']['version'] ?? null) !== null && GBDBUpdateTrust::compareVersions((string) $status['scheduled']['version'], $job['version']) <= 0) {
            unset($status['scheduled']);
        }

        GBDBUpdateState::write('status', $status);
    }

    private static function pruneBackups() :void {
        $keep = (int) GBDBUpdateSettings::all()['keep_backups'];
        $folders = glob(GBDBUpdateState::dir('backups/*'), GLOB_ONLYDIR) ?: [];
        rsort($folders, SORT_STRING);

        foreach (array_slice($folders, $keep) as $folder) {
            GBDBUpdateState::removeTree($folder);
        }
    }

    // ------------------------------------------------------------------ Wiederherstellungspunkte

    /**
     * Sicherungen, aus denen sich der Stand vor einem Update wiederherstellen lässt.
     *
     * @return list<array<string, mixed>>
     */
    public static function backups() :array {
        $list = [];

        foreach (glob(GBDBUpdateState::dir('backups/*/meta.json')) ?: [] as $file) {
            $meta = json_decode((string) file_get_contents($file), true);

            if (is_array($meta)) {
                $list[] = ['job' => $meta['job'], 'from' => $meta['from_version'], 'to' => $meta['to_version'], 'created_at' => $meta['created_at'], 'files' => count($meta['files']), 'data' => $meta['data'] !== null];
            }
        }

        usort($list, static fn(array $a, array $b) :int => $b['created_at'] <=> $a['created_at']);

        return $list;
    }

    /**
     * Kontrollierte Wiederherstellung des Stands vor dem letzten Update (administrativer Downgrade).
     * Signatur- und Vertrauensstand bleiben unberührt; Daten werden nie zurückgespielt.
     *
     * @return array{ok: bool, message: string}
     */
    public static function restoreLatest(string $jobId) :array {
        $lock = self::lockOrFail();

        try {
            $job = self::job();
            $latest = self::backups()[0] ?? null;

            if (self::isActive($job) || $latest === null || $latest['job'] !== $jobId || $latest['to'] !== GBDBUpdateVersion::current()) {
                throw new RuntimeException('Wiederherstellen ist nur für das zuletzt installierte Update möglich.');
            }

            if ($latest['data']) {
                throw new RuntimeException('Dieses Update hat Daten migriert. Eine Wiederherstellung des alten Codes ist nicht automatisch möglich.');
            }

            GBDBUpdateState::setMaintenance($jobId);
            GBDBUpdateState::log($jobId, 'Manuelle Wiederherstellung des Stands vor dem Update gestartet.');
            $result = GBDBUpdateGate::rollback($jobId, 'Manuelle Wiederherstellung auf ' . $latest['from'], false);

            return ['ok' => $result['ok'], 'message' => $result['ok'] ? 'Stand vor dem Update (' . $latest['from'] . ') wiederhergestellt.' : 'Wiederherstellung unvollständig: ' . implode(' ', $result['errors'])];
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    // ------------------------------------------------------------------ Hilfen

    /**
     * Migrationen, die für den Sprung von $from auf dieses Release anstehen
     * (auch aus übersprungenen Zwischenversionen, nie bereits angewendete).
     *
     * @param array<string, mixed> $manifest
     * @return list<array<string, mixed>>
     */
    public static function pendingMigrations(array $manifest, string $from) :array {
        $applied = GBDBUpdateState::read('migrations');
        $pending = [];

        foreach ((array) ($manifest['migrations'] ?? []) as $migration) {
            $since = (string) ($migration['since'] ?? $manifest['version']);

            if (!isset($applied[$migration['id']]) && GBDBUpdateTrust::isVersion($since) && version_compare($since, $from, '>')) {
                $pending[] = $migration;
            }
        }

        return $pending;
    }

    private static function directorySize(string $path) :int {
        $size = 0;

        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $item) {
                $size += $item->isFile() ? (int) $item->getSize() : 0;
            }
        }

        return $size;
    }

    public static function bytes(int $bytes) :string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1, ',', '.') . ' ' . $units[$index];
    }
}
