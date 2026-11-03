<?php

declare(strict_types=1);

/**
 * Einstellungen des Framework-Updaters aus Vars::FRAMEWORK_UPDATER (.framework.env.php).
 *
 * Sicherheitswerte sind feste Anforderungen: Steht dort false, gilt trotzdem true und die
 * Oberfläche zeigt einen Hinweis. So schaltet keine Konfigurationsänderung die
 * Vertrauenskette, den Rollback-Schutz oder den Datenschutz still ab.
 */
final class GBDBUpdateSettings {
    public const DEFAULTS = [
        'check_for_updates' => true,
        'update_server' => 'https://mamueller.de/gbdb/updates/',
        'verify_api' => 'https://mamueller.de/gbdb/verify.php',
        'force_ssl' => true,
        'check_interval' => 21600,
        'auto_updating' => [
            'enabled' => false,
            'patch_day' => 'Fridays, 21:30:00',
        ],
        'verification' => [
            'checksum_algorithm' => 'sha256',
            'signature_algorithm' => 'ed25519',
            'root_public_key' => 'BUILTIN',
            'verify_trust_chain' => true,
            'verify_release_manifest' => true,
            'verify_release_catalog' => true,
            'verify_via_api' => true,
            'prevent_rollback' => true,
            'check_key_revocation' => true,
            'reject_expired_metadata' => true,
        ],
        'installation' => [
            'create_backup' => true,
            'verify_checksum' => true,
            'verify_signature' => true,
            'preserve_user_data' => true,
            'preserve_custom_files' => true,
            'preserve_configuration' => true,
            'check_compatibility' => true,
            'rollback_on_failure' => true,
        ],
        'work_dir' => '',
        'http_timeout' => 15,
        'keep_backups' => 3,
    ];

    /** Nicht abschaltbar (Konzept Abschnitt 12, verbindliche Sicherheitsvorgabe). */
    private const ENFORCED = [
        'verification' => ['verify_trust_chain', 'verify_release_manifest', 'verify_release_catalog', 'prevent_rollback', 'check_key_revocation', 'reject_expired_metadata'],
        'installation' => ['create_backup', 'verify_checksum', 'verify_signature', 'preserve_user_data', 'preserve_custom_files', 'preserve_configuration', 'check_compatibility', 'rollback_on_failure'],
    ];

    private static ?array $cache = null;

    private function __construct() {
    }

    /**
     * @return array<string, mixed>
     */
    public static function all() :array {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = self::DEFAULTS;

        foreach (array_keys(self::DEFAULTS) as $name) {
            $value = null;

            // FRAMEWORK_UPDATER (Stil der übrigen Bereiche) oder FrameworkUpdater (Schreibweise des Konzepts).
            foreach (['framework_updater', 'frameworkupdater'] as $section) {
                try {
                    $value ??= class_exists('GBDBConfig') ? GBDBConfig::get($section, $name) : null;
                } catch (Throwable) {
                }
            }

            if ($value === null) {
                continue;
            }

            $settings[$name] = is_array(self::DEFAULTS[$name]) && is_array($value) ? array_replace(self::DEFAULTS[$name], $value) : $value;
        }

        $settings['check_for_updates'] = filter_var($settings['check_for_updates'], FILTER_VALIDATE_BOOL);
        $settings['force_ssl'] = filter_var($settings['force_ssl'], FILTER_VALIDATE_BOOL);
        $settings['check_interval'] = max(300, (int) $settings['check_interval']);
        $settings['http_timeout'] = max(3, min(120, (int) $settings['http_timeout']));
        $settings['keep_backups'] = max(1, min(20, (int) $settings['keep_backups']));
        $settings['auto_updating']['enabled'] = filter_var($settings['auto_updating']['enabled'] ?? false, FILTER_VALIDATE_BOOL);
        $settings['verification']['verify_via_api'] = filter_var($settings['verification']['verify_via_api'] ?? true, FILTER_VALIDATE_BOOL);
        $settings['_warnings'] = [];

        foreach (self::ENFORCED as $group => $names) {
            foreach ($names as $name) {
                if (filter_var($settings[$group][$name] ?? true, FILTER_VALIDATE_BOOL) !== true) {
                    $settings['_warnings'][] = $group . '.' . $name . ' = false wird ignoriert: Diese Prüfung ist verpflichtend.';
                }

                $settings[$group][$name] = true;
            }
        }

        foreach (['checksum_algorithm' => 'sha256', 'signature_algorithm' => 'ed25519', 'root_public_key' => 'BUILTIN'] as $name => $required) {
            if (strtolower((string) $settings['verification'][$name]) !== strtolower($required)) {
                $settings['_warnings'][] = 'verification.' . $name . ' muss „' . $required . '“ sein; der Wert wird ignoriert.';
            }

            $settings['verification'][$name] = $required;
        }

        // HTTP ohne TLS nur in DEV-Umgebungen (Testserver); in PROD immer HTTPS mit Zertifikatsprüfung.
        if (!$settings['force_ssl'] && !(class_exists('Vars') && Vars::__DEV__())) {
            $settings['_warnings'][] = 'force_ssl = false gilt nur in DEV-Umgebungen; HTTPS bleibt erzwungen.';
            $settings['force_ssl'] = true;
        }

        return self::$cache = $settings;
    }

    public static function reset() :void {
        self::$cache = null;
    }

    public static function frameworkRoot() :string {
        return rtrim(str_replace('\\', '/', GBDBConfig::root()), '/');
    }

    /** Ordner, in dem gbdb_framework liegt (dort liegen auch gbdb_ui.php und die anderen Einstiege). */
    public static function projectRoot() :string {
        return dirname(self::frameworkRoot());
    }

    /** Arbeitsordner für Zustand, Downloads, Staging und Backups – außerhalb des Update-Ziels. */
    public static function workDir() :string {
        $configured = (string) (self::all()['work_dir'] ?? '');

        if ($configured === '') {
            return self::projectRoot() . '/.gbdb_updater';
        }

        $path = str_replace('\\', '/', $configured);

        if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:\//', $path)) {
            $path = self::projectRoot() . '/' . $path;
        }

        $path = rtrim($path, '/');

        if ($path === self::frameworkRoot() || str_starts_with($path . '/', self::frameworkRoot() . '/')) {
            throw new RuntimeException('FRAMEWORK_UPDATER.work_dir darf nicht im Framework-Ordner liegen.');
        }

        return $path;
    }

    public static function url(string $name) :string {
        $url = (string) self::all()[$name];
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['https', 'http'], true) || ($scheme !== 'https' && self::all()['force_ssl'])) {
            throw new RuntimeException('FRAMEWORK_UPDATER.' . $name . ' muss eine HTTPS-Adresse sein.');
        }

        return $url;
    }
}
