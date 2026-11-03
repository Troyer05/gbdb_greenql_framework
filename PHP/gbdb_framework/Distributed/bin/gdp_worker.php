<?php

declare(strict_types=1);

/**
 * GDP Worker-Modus (Konzept Kapitel 28 B) für Docker und dedizierte Server.
 *
 *   php gdp_worker.php            dauerhaft, Standardintervall 5 Sekunden
 *   php gdp_worker.php --once     ein einzelner Zyklus (z. B. aus systemd-Timern)
 *   php gdp_worker.php --interval=15
 *
 * Er nutzt denselben Synchronisationskern wie der Embedded-Modus.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/autoloader.php';

$options = getopt('', ['once', 'interval::', 'budget::']);
$interval = max(1, (int) ($options['interval'] ?? 5));
$budget = max(1, (int) ($options['budget'] ?? 30));
$running = true;

if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$running) :void {
        $running = false;
    });
    pcntl_signal(SIGINT, static function () use (&$running) :void {
        $running = false;
    });
}

do {
    GDPConfig::reset();
    GDPInstanceRegistry::forget();
    GDPNodeIdentity::forget();
    GDPVault::forget();
    $started = microtime(true);
    $report = GDP::runBackgroundCycle($budget);
    $errors = count($report['errors'] ?? []) + (isset($report['error']) ? 1 : 0);
    fwrite(STDOUT, gmdate('c') . ' GDP-Zyklus: ' . round(microtime(true) - $started, 2) . ' s, Fehler: ' . $errors . (isset($report['skipped']) ? ', übersprungen: ' . $report['skipped'] : '') . PHP_EOL);

    if (isset($options['once'])) {
        exit($errors > 0 ? 1 : 0);
    }

    for ($slept = 0; $slept < $interval && $running; $slept++) {
        sleep(1);
    }
} while ($running);
