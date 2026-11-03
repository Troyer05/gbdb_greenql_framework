<?php

declare(strict_types=1);

// Optional when the framework is outside the entrypoint's ancestor directories:
// define('GBDB_FRAMEWORK_PATH', '/absolute/path/to/gbdb_framework');
$framework = class_exists('GBDBConfig', false) ? GBDBConfig::root() : '';
$explicit = defined('GBDB_FRAMEWORK_PATH') ? (string) GBDB_FRAMEWORK_PATH : (string) getenv('GBDB_FRAMEWORK_PATH');

if ($framework === '' && $explicit !== '') {
    $framework = rtrim($explicit, '/\\');
}

if ($framework === '') {
    $directory = __DIR__;

    for ($level = 0; $level < 16; $level++) {
        foreach (['/gbdb_framework', '/PHP/gbdb_framework', '/assets/php/inc/gbdb_framework', ''] as $relative) {
            $candidate = $directory . $relative;

            if (is_file($candidate . '/autoloader.php') && is_dir($candidate . '/GBDB_GQL')) {
                $framework = $candidate;
                break 2;
            }
        }

        if ($directory === dirname($directory)) {
            break;
        }

        $directory = dirname($directory);
    }
}

if ($framework === '' || !is_file($framework . '/autoloader.php')) {
    http_response_code(500);
    exit('GBDB-Framework nicht gefunden. GBDB_FRAMEWORK_PATH auf den Framework-Ordner setzen.');
}

require_once $framework . '/SRV/backend_api.php';
