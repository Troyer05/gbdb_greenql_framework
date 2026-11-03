<?php

declare(strict_types=1);

/**
 * Installierte Framework-Version aus gbdb_framework/version.json (vom Release-Manager gestempelt).
 * Die Datei gehört zum Framework-Core; .framework.env.php bleibt bei Updates erhalten und
 * taugt deshalb nicht als Versionsquelle.
 */
final class GBDBUpdateVersion {
    private static ?array $info = null;
    private static bool $resolving = false;

    private function __construct() {
    }

    /**
     * @return array{version: string, release_sequence: int, published_at: string, official: bool}
     */
    public static function info() :array {
        if (self::$info !== null) {
            return self::$info;
        }

        $file = GBDBConfig::root() . '/version.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (is_array($data) && GBDBUpdateTrust::isVersion($data['version'] ?? null)) {
            return self::$info = [
                'version' => $data['version'],
                'release_sequence' => (int) ($data['release_sequence'] ?? 0),
                'published_at' => (string) ($data['published_at'] ?? ''),
                'official' => (int) ($data['release_sequence'] ?? 0) > 0,
            ];
        }

        if (self::$resolving) {
            return ['version' => '0.0.0', 'release_sequence' => 0, 'published_at' => '', 'official' => false];
        }

        // Ältere Stände ohne version.json: Nummer aus Vars::framework_version() (z. B. "v10.0.1-full").
        // Neuere Vars-Klassen fragen dafür wieder hier nach – die Sperre verhindert die Schleife.
        self::$resolving = true;

        try {
            $legacy = class_exists('Vars') && method_exists('Vars', 'framework_version') ? (string) Vars::framework_version() : '';
        } finally {
            self::$resolving = false;
        }

        $version = preg_match('/(\d+)\.(\d+)\.(\d+)/', $legacy, $match) ? (int) $match[1] . '.' . (int) $match[2] . '.' . (int) $match[3] : '0.0.0';

        return self::$info = ['version' => $version, 'release_sequence' => 0, 'published_at' => '', 'official' => false];
    }

    public static function current() :string {
        return self::info()['version'];
    }

    public static function reset() :void {
        self::$info = null;
    }
}
