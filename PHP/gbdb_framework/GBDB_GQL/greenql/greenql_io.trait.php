<?php

trait GreenQL_IoTrait {

    /**
     * Ermittelt den Framework-Root für FILE-Befehle.
     * @return string Projektpfad.
     */
    private static function scriptRoot(): string {
        $dir = __DIR__;

        while ($dir !== dirname($dir)) {
            if (is_dir($dir . "/GBDB_GQL/.DB/.scripts") || is_dir($dir . "/scripts/greenql") || is_file($dir . "/GPT_TODO.md")) {
                return $dir;
            }

            $dir = dirname($dir);
        }

        return dirname(__DIR__, 7);
    }


    /**
     * Löst GreenQL-Dateipfade sicher innerhalb des Projektes auf.
     * @param string $path Dateipfad.
     * @return string Vollständiger Pfad oder leer.
     */
    private static function resolveScriptPath(string $path): string {
        $path = trim((string)self::unquote($path));
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return '';
        }

        $root = self::scriptRoot();
        $full = $root . '/' . $path;

        if (!is_file($full)) {
            $alt = $root . '/GBDB_GQL/.DB/.scripts/' . $path;

        if (!is_file($alt)) {
            $alt = $root . '/scripts/greenql/' . $path;
        }
            if (is_file($alt)) {
                $full = $alt;
            } else {
                return '';
            }
        }

        $realRoot = realpath($root);
        $realFile = realpath($full);

        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot)) {
            return '';
        }

        return $realFile;
    }


    /**
     * Löst einen Log-Dateipfad sicher innerhalb des Projektes auf.
     * @param string $path Dateipfad.
     * @return string Vollständiger Pfad oder leer.
     */
    private static function resolveLogPath(string $path): string {
        $path = trim((string)self::unquote($path));
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return '';
        }

        $root = self::scriptRoot();
        $rootReal = realpath($root);

        if ($rootReal === false) {
            return '';
        }

        $full = $root . '/' . $path;
        $dir = dirname($full);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $dirReal = realpath($dir);

        if ($dirReal === false || !str_starts_with($dirReal, $rootReal)) {
            return '';
        }

        return $dirReal . '/' . basename($full);
    }


    /**
     * Gibt den aktiven Log-Dateipfad zurück.
     * @param array $ctx Context.
     * @return string Dateipfad oder leer.
     */
    private static function activeLogFile(array $ctx): string {
        $file = (string)($ctx['logfile'] ?? self::$defaultLogFile);
        return $file !== '' ? $file : '';
    }


    /**
     * Formatiert einen Log-Wert lesbar.
     * @param mixed $value Wert.
     * @return string Log-Zeile.
     */
    private static function formatLogValue(mixed $value): string {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string)$value;
    }


    /**
     * Schreibt eine GreenQL-Logzeile.
     * @param string $file Log-Datei.
     * @param mixed $value Wert.
     * @return bool Ergebnis.
     */
    private static function writeLogLine(string $file, mixed $value): bool {
        if ($file === '') {
            return false;
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . self::formatLogValue($value) . PHP_EOL;
        return @file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
    }


    /**
     * Liest eine GreenQL-ENV-Variable aus .config/.greenql.env.php.
     * Die Datei wird als PHP-Datei geladen und soll ein Array zurückgeben.
     * Zusätzlich werden $GREENQL_ENV, $GQL_ENV und $ENV als Array unterstützt.
     * @param string $key ENV-Schlüssel.
     * @return mixed ENV-Wert oder null.
     */
    private static function readEnvValue(string $key): mixed {
        $key = trim($key);

        if ($key === '' || !preg_match('/^[a-zA-Z0-9_.\-]+$/', $key)) {
            return null;
        }

        $root = self::scriptRoot();

        $candidates = [
            $root . '/.config/.greenql.env.php'
        ];

        $file = '';

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }

        $realRoot = realpath($root);
        $realFile = $file !== '' ? realpath($file) : false;

        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $loader = static function (string $__greenqlEnvFile): array {
            $GREENQL_ENV = [];
            $GQL_ENV = [];
            $ENV = [];

            $returned = require $__greenqlEnvFile;

            if (is_array($returned)) return $returned;
            if (is_array($GREENQL_ENV) && !empty($GREENQL_ENV)) return $GREENQL_ENV;
            if (is_array($GQL_ENV) && !empty($GQL_ENV)) return $GQL_ENV;
            if (is_array($ENV) && !empty($ENV)) return $ENV;

            $vars = get_defined_vars();
            unset($vars['__greenqlEnvFile'], $vars['returned'], $vars['GREENQL_ENV'], $vars['GQL_ENV'], $vars['ENV']);

            return $vars;
        };

        $env = $loader($realFile);

        return is_array($env) && array_key_exists($key, $env) ? $env[$key] : null;
    }

}
