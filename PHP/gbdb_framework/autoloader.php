<?php

declare(strict_types=1);

require_once __DIR__ . '/runtime/config.php';
require_once __DIR__ . '/.config/.framework.env.php';
require_once __DIR__ . '/runtime/sql_store.php';
require_once __DIR__ . '/runtime/runtime.php';
require_once __DIR__ . '/runtime/filesystem.php';
require_once __DIR__ . '/runtime/rate_limiter.php';

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null) :string {
        return strtolower($string);
    }

}

if (!function_exists('mb_stripos')) {
    function mb_stripos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null) :int|false {
        return stripos($haystack, $needle, $offset);
    }

}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null) :int {
        return strlen($string);
    }

}

/**
 * Lädt PHP-Dateien aus einem Ordner stabil sortiert.
 * @param string $folder Ordner.
 * @param array $skip Basenamen, die übersprungen werden.
 * @return void
 */

function gbdb_loadLocal(string $folder, array $skip = []) :void {
    $path = rtrim($folder, '/\\');

    if (!is_dir($path)) {
        error_log("[GBDB Loader] Ordner fehlt: $path");

        return;
    }

    $files = GBDBFilesystem::glob($path . "/*.php");

    if (!$files) {
        return;
    }

    sort($files, SORT_STRING);

    foreach ($files as $file) {
        $base = basename($file);

        if (in_array($base, $skip, true)) {
            continue;
        }
        if (str_ends_with($base, ".disabled.php") || str_ends_with($base, ".php.disabled")) {
            continue;
        }
        if (is_file($file . ".disabled")) {
            continue;
        }

        require_once $file;
    }
}

$BASE = __DIR__;

require_once $BASE . "/GBDB_GQL/db_engine/gbdb.php";
require_once $BASE . "/GBDB_GQL/greenql/greenql.php";

gbdb_loadLocal($BASE . '/core');
require_once $BASE . '/runtime/facade.php';
require_once $BASE . '/runtime/auth_facade.php';
require_once $BASE . '/Distributed/loader.php';

GDP::bootEmbedded();

gbdb_loadLocal($BASE . "/plugins", ["musterCode.php"]);
