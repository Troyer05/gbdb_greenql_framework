<?php

trait GreenQL_IoTrait {
    private static function scriptRoot() :string {
        $dir = __DIR__;

        while ($dir !== dirname($dir)) {
            if (is_dir($dir . "/GBDB_GQL/.DB/.scripts") || is_dir($dir . "/scripts/greenql") || is_file($dir . "/GPT_TODO.md")) {
                return $dir;
            }

            $dir = dirname($dir);
        }

        return dirname(__DIR__, 7);
    }

    private static function resolveScriptPath(string $path) :string {
        $path = trim((string)self::unquote($path));
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return '';
        }

        $root = self::scriptRoot();
        $full = $root . '/' . $path;

        if (!is_file($full)) {
            $alt = Vars::data_root() . '/.scripts/' . $path;

            if (!is_file($alt)) {
                $alt = $root . '/scripts/greenql/' . $path;
            }

            if (is_file($alt)) {
                $full = $alt;
            } else {
                return '';
            }
        }

        if (str_starts_with($full, 'gbdbdata://')) {
            return $full;
        }

        $realRoot = GBDBFilesystem::realpath($root);
        $realFile = GBDBFilesystem::realpath($full);

        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot)) {
            return '';
        }

        return $realFile;
    }

    private static function resolveLogPath(string $path) :string {
        $path = trim((string)self::unquote($path));
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return '';
        }

        $root = self::scriptRoot();
        $rootReal = GBDBFilesystem::realpath($root);

        if ($rootReal === false) {
            return '';
        }

        $full = $root . '/' . $path;
        $dir = dirname($full);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $dirReal = GBDBFilesystem::realpath($dir);

        if ($dirReal === false || !str_starts_with($dirReal, $rootReal)) {
            return '';
        }

        return $dirReal . '/' . basename($full);
    }

    private static function activeLogFile(array $ctx) :string {
        $file = (string)($ctx['logfile'] ?? self::$defaultLogFile);

        return $file !== '' ? $file : '';
    }

    private static function formatLogValue(mixed $value) :string {
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

    private static function writeLogLine(string $file, mixed $value) :bool {
        if ($file === '') {
            return false;
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . self::formatLogValue($value) . PHP_EOL;

        return @file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
    }

    private static function readEnvValue(string $key) :mixed {
        $key = trim($key);

        if ($key === '' || !preg_match('/^[a-zA-Z0-9_.\-]+$/', $key)) {
            return null;
        }

        return GBDBConfig::get('greenql', $key);
    }
}
