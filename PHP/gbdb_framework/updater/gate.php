<?php

declare(strict_types=1);

/**
 * Anbindung des Updaters an jede Framework-Anfrage (aus autoloader.php):
 *
 * - boot(): früh, vor dem übrigen Framework. Während einer Installation antworten alle
 *   Anfragen mit 503 (Wartungsmodus). Nach dem Einspielen läuft die Selbstprüfung in der
 *   ersten Anfrage mit neuem Code; ein abgebrochener Vorgang wird zurückgerollt.
 * - afterBoot(): am Ende des Ladens. Selbstprüfung ausführen oder – nur wenn fällig –
 *   Update-Suche und automatische Updates nach Auslieferung der Antwort anstoßen.
 *
 * Ohne Wartungsdatei kostet boot() einen Dateizugriff; Updater-Klassen werden erst bei
 * Bedarf geladen. Fehler hier dürfen den normalen Betrieb nie stören.
 */
final class GBDBUpdateGate {
    private const CLASSES = [
        'GBDBUpdateSignature' => 'signature.php',
        'GBDBUpdateZip' => 'zip.php',
        'GBDBUpdateZipWriter' => 'zip_writer.php',
        'GBDBUpdateTrust' => 'trust.php',
        'GBDBUpdateTrustException' => 'trust.php',
        'GBDBUpdateSettings' => 'settings.php',
        'GBDBUpdateVersion' => 'version.php',
        'GBDBUpdateHttp' => 'http.php',
        'GBDBUpdateState' => 'state.php',
        'GBDBUpdateConfigMerger' => 'config_merger.php',
        'GBDBUpdateInstaller' => 'installer.php',
        'GBDBFrameworkUpdater' => 'updater.php',
    ];

    private static bool $verifyMode = false;
    private static bool $registered = false;

    private function __construct() {
    }

    public static function register() :void {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        spl_autoload_register(static function (string $class) :void {
            if (isset(self::CLASSES[$class])) {
                require_once __DIR__ . '/' . self::CLASSES[$class];
            }
        });
    }

    public static function boot() :void {
        self::register();

        if (PHP_SAPI === 'cli') {
            return;
        }

        try {
            $maintenance = GBDBUpdateState::maintenance();
        } catch (Throwable) {
            return;
        }

        if ($maintenance === null) {
            return;
        }

        $job = GBDBUpdateState::read('job');
        $status = (string) ($job['status'] ?? '');

        if ($status === 'verify_pending') {
            self::$verifyMode = true;
            register_shutdown_function([self::class, 'onShutdown']);

            return;
        }

        if (in_array($status, ['applying', 'verifying'], true)) {
            // Läuft der Vorgang noch (Sperre gehalten)? Sonst wurde er abgebrochen: zurückrollen.
            $lock = GBDBUpdateState::lock();

            if ($lock !== null) {
                try {
                    $job = GBDBUpdateState::read('job');

                    if (in_array($job['status'] ?? '', ['applying', 'verifying'], true)) {
                        $dirty = is_file(GBDBUpdateState::dir('backups/' . $job['id'] . '/data_dirty'));
                        self::rollback((string) $job['id'], 'Installation wurde unterbrochen (Anfrage abgebrochen oder Zeitlimit).', $dirty);
                    }
                } finally {
                    GBDBUpdateState::unlock($lock);
                }

                self::respond('Ein unterbrochenes Framework-Update wurde zurückgenommen. Bitte die Seite neu laden.', 503, 5);
            }

            self::respond('GBDB wird gerade aktualisiert. Bitte in einer Minute erneut versuchen.');
        }

        if ($status === 'rollback_failed') {
            self::respond('Ein Framework-Update ist fehlgeschlagen und konnte nicht vollständig zurückgenommen werden. Die Installation bleibt zum Schutz der Daten gesperrt. '
                . 'Sicherung und Protokoll liegen im Arbeitsordner des Updaters (backups/' . ($job['id'] ?? '') . ', logs/' . ($job['id'] ?? '') . '.log).', 503, 600);
        }

        // Wartungsdatei ohne laufenden Auftrag (z. B. nach manueller Bereinigung): aufheben.
        GBDBUpdateState::clearMaintenance();
    }

    public static function afterBoot() :void {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (self::$verifyMode) {
            $result = GBDBUpdateInstaller::verifyPending();
            self::$verifyMode = false;

            if ($result['message'] === 'busy') {
                self::respond('GBDB schließt gerade ein Update ab. Bitte in wenigen Sekunden erneut versuchen.', 503, 5);
            }

            if (!$result['ok']) {
                // Der geladene Code ist bereits wieder ersetzt: diese Anfrage nicht fortsetzen.
                self::respond('Das Framework-Update ist bei der Selbstprüfung gescheitert und wurde zurückgenommen. Bitte die Seite neu laden. ' . $result['message'], 503, 5);
            }

            return;
        }

        if (function_exists('fastcgi_finish_request') && GBDBFrameworkUpdater::tickDue()) {
            // Als letzte Shutdown-Funktion: erst die Antwort ausliefern, dann prüfen.
            register_shutdown_function(static function () :void {
                register_shutdown_function(static function () :void {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_write_close();
                    }

                    fastcgi_finish_request();
                    GBDBFrameworkUpdater::tick(true);
                });
            });
        }
    }

    /** Fatale Fehler beim Laden des neuen Codes: mit dem bereitgelegten alten Rollback zurück. */
    public static function onShutdown() :void {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }

        try {
            $job = GBDBUpdateState::read('job');

            if (!in_array($job['status'] ?? '', ['verify_pending', 'verifying'], true)) {
                return;
            }

            $dirty = is_file(GBDBUpdateState::dir('backups/' . $job['id'] . '/data_dirty'));
            self::rollback((string) $job['id'], 'Neuer Code nicht ladbar: ' . $error['message'] . ' in ' . basename((string) $error['file']) . ':' . $error['line'], $dirty);
        } catch (Throwable $exception) {
            error_log('[GBDB Updater] Rollback nach fatalem Fehler gescheitert: ' . $exception->getMessage());
        }
    }

    /**
     * Rollback über die vor der Installation bereitgelegte Kopie (alter Code).
     *
     * @return array{ok: bool, errors: list<string>, restored: int, removed: int, data: bool}
     */
    public static function rollback(string $jobId, string $reason, bool $restoreData) :array {
        $copy = GBDBUpdateState::dir('rollback/rollback.php');

        if (!class_exists('GBDBUpdateRollback', false)) {
            require_once is_file($copy) ? $copy : __DIR__ . '/rollback.php';
        }

        return GBDBUpdateRollback::run(GBDBUpdateSettings::workDir(), $jobId, $reason, $restoreData);
    }

    private static function respond(string $message, int $code = 503, int $retry = 60) :never {
        if (!headers_sent()) {
            http_response_code($code);
            header('Retry-After: ' . $retry);
            header('Cache-Control: no-store');
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Wartung · GBDB</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#f5f7fb;color:#1c2333;display:grid;place-items:center;min-height:100vh;margin:0;padding:16px}'
            . 'main{max-width:560px;background:#fff;border-radius:14px;padding:28px;box-shadow:0 8px 30px rgba(0,0,0,.08)}h1{font-size:20px;margin:0 0 10px}'
            . '@media (prefers-color-scheme: dark){body{background:#11151f;color:#e6e9f0}main{background:#1a202d}}</style></head>'
            . '<body><main><h1>GBDB-Wartung</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';

        exit;
    }
}
