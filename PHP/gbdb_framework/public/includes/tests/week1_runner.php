<?php
declare(strict_types=1);

$base = __DIR__;
$tests = [
    'Testdatenbank' => $base . '/week1_testdata.php',
    'Regression' => $base . '/week1_regression.php',
];

foreach ($tests as $name => $file) {
    echo "\n=== " . $name . " ===\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($file), $code);
    if ($code !== 0) {
        exit($code);
    }
}
