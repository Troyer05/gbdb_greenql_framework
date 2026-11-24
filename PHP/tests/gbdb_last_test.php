<?php

declare(strict_types=1);

/**
 * GL1: Lastfestigkeit der GBDB auf einer frischen Testinstallation (temporärer Ordner, eigene Daten).
 *
 *   php tests/gbdb_last_test.php
 *
 * Geteilte Lesesperre, Schreiber-Vorrang, GBDBBusy, Zwischenspeicher über Läufe und sein Verwerfen nach
 * fremdem Schreiben, GBDB::deleteWhere, begrenzte Tombstones, nur geänderte Seiten, verschlüsselte Seiten,
 * GL1-B: Mengenlöschung als Block (Wandzeit), WAL-Block wiederherstellbar.
 * Die gleichen Pfade prüft in jeder EA tests/gbdb-last.test.php (mit EaCore\Store).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$package = dirname(__DIR__);
$root = sys_get_temp_dir() . '/gbdb-last-' . bin2hex(random_bytes(6));
$php = getenv('GBDB_TEST_PHP') ?: PHP_BINARY;
$checks = 0;
$failures = 0;

function check(bool $condition, string $message, mixed $detail = null) :void {
    global $checks, $failures;
    $checks++;

    if (!$condition) {
        $failures++;
        echo 'FAIL: ', $message, $detail === null ? '' : ' → ' . json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";

        return;
    }

    echo 'PASS: ', $message, "\n";
}

function copyPackage(string $source, string $target) :void {
    mkdir($target, 0700, true);

    foreach (new DirectoryIterator($source) as $entry) {
        $name = $entry->getFilename();

        if ($entry->isDot() || $entry->isLink() || in_array($name, ['.runtime', 'tests', '.storage', '.system', '.gdp', '.backups', '.temp', '.media'], true)) {
            continue;
        }

        $entry->isDir() ? copyPackage($entry->getPathname(), $target . '/' . $name) : copy($entry->getPathname(), $target . '/' . $name);
    }
}

function removeTree(string $directory) :void {
    if (!is_dir($directory)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
        $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($directory);
}

/** Kindprozess derselben Installation; $code gibt JSON aus. */
function child(string $code) :array {
    global $php, $root;
    $file = $root . '/kind-' . bin2hex(random_bytes(5)) . '.php';
    file_put_contents($file, "<?php\nrequire " . var_export($root . '/pkg/gbdb_framework/autoloader.php', true) . ";\n" . $code . "\n");
    $process = proc_open([$php, $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);

    return ['p' => $process, 'pipes' => $pipes, 'file' => $file];
}

function finish(array $child) :array {
    $out = stream_get_contents($child['pipes'][1]);
    $err = stream_get_contents($child['pipes'][2]);
    proc_close($child['p']);
    @unlink($child['file']);
    $data = json_decode(trim((string) $out), true);

    return is_array($data) ? $data : ['raw' => $out, 'err' => $err];
}

try {
    mkdir($root, 0700, true);
    copyPackage($package, $root . '/pkg');
    require $root . '/pkg/gbdb_framework/autoloader.php';
    $instance = 'gllast';
    $table = 'eintraege';

    if (!GBDB::existsInstance($instance)) {
        GBDB::createInstance($instance);
    }

    GBDB::withInstance($instance, static function () use ($table) :void {
        if (!GBDB::exists('daten', $table)) {
            GBDB::createDatabase('daten');
            GBDB::createTable('daten', $table, ['schluessel', 'wert']);
        }

        for ($i = 1; $i <= 30; $i++) {
            GBDB::insert('daten', $table, ['schluessel' => 'k' . $i, 'wert' => 'GEHEIM-' . $i . str_repeat('x', 60)]);
        }
    });
    $read = static fn () => GBDBRuntime::run(static fn () => GBDB::withInstance('gllast', static fn () => GBDB::getData('daten', 'eintraege')), true);

    // Leser parallel
    $holder = child('GBDBRuntime::run(static function () { usleep(1500000); }, true); echo json_encode(["ok" => 1]);');
    usleep(400000);
    $t = microtime(true);
    $read();
    $waited = microtime(true) - $t;
    finish($holder);
    check($waited < 0.8, 'Leser warten nicht auf Leser', round($waited, 3));

    // Schreiber wartet auf Leser, neuer Leser hinter dem Schreiber
    $r2 = child('GBDBRuntime::run(static function () {}, true); usleep(1500000); GBDBRuntime::run(static function () {}, true); echo json_encode(["erhalten" => microtime(true)]);');
    $w = child('GBDBRuntime::run(static function () {}, true); usleep(900000); GBDBRuntime::run(static function () { usleep(500000); }); echo json_encode(["erhalten" => microtime(true) - 0.5, "ende" => microtime(true)]);');
    usleep(300000);
    $r1 = child('GBDBRuntime::run(static function () { usleep(2000000); }, true); echo json_encode(["ende" => microtime(true)]);');
    [$a, $b, $c] = [finish($r1), finish($w), finish($r2)];
    check(isset($a['ende'], $b['erhalten']) && $b['erhalten'] >= $a['ende'] - 0.05, 'Schreiber wartet auf den laufenden Leser', [$a, $b]);
    check(isset($b['ende'], $c['erhalten']) && $c['erhalten'] >= $b['ende'] - 0.05, 'Neuer Leser wartet hinter dem angemeldeten Schreiber', [$b, $c]);

    // GBDBBusy
    $short = child('usleep(1000000); putenv("GBDB_STORAGE_LOCK_TIMEOUT_MS=300"); try { GBDBRuntime::run(static function () {}); echo json_encode(["c" => "keine"]); } catch (Throwable $e) { echo json_encode(["c" => get_class($e), "rt" => $e instanceof RuntimeException]); }');
    usleep(200000);
    $hold = child('GBDBRuntime::run(static function () { usleep(2500000); }); echo json_encode(["ok" => 1]);');
    $res = finish($short);
    finish($hold);
    check(($res['c'] ?? '') === 'GBDBBusy' && ($res['rt'] ?? false) === true, 'Zeitlimit als GBDBBusy (RuntimeException)', $res);

    // Zwischenspeicher
    $before = GBDBRuntime::counters();
    $read();
    $mid = GBDBRuntime::counters();
    $read();
    $after = GBDBRuntime::counters();
    check($after['cache_misses'] === $mid['cache_misses'] && $after['cache_hits'] > $mid['cache_hits'], 'Zweiter Lesezugriff ohne neues Entschlüsseln', [$mid, $after]);
    finish(child('GBDB::withInstance("gllast", static fn () => GBDB::insert("daten", "eintraege", ["schluessel" => "fremd", "wert" => "1"])); echo json_encode(["ok" => 1]);'));
    $rows = $read();
    check(in_array('fremd', array_column(array_filter((array) $rows, 'is_array'), 'schluessel'), true), 'Zeile aus einem anderen Prozess sofort sichtbar');

    // deleteWhere
    $z0 = GBDBRuntime::counters();
    $deleted = GBDB::withInstance($instance, static fn () => GBDB::deleteWhere('daten', 'eintraege', static fn (array $row) :bool => preg_match('/^k([0-9]+)$/', (string) $row['schluessel'], $m) === 1 && (int) $m[1] <= 20));
    $z1 = GBDBRuntime::counters();
    $left = array_column(array_filter((array) $read(), static fn ($r) => is_array($r) && (int) ($r['id'] ?? 0) > 0), 'schluessel');
    check($deleted === 20 && count($left) === 11 && !in_array('k3', $left, true) && in_array('k25', $left, true), 'deleteWhere löscht genau die passenden Zeilen', [$deleted, $left]);
    check($z1['runs_exclusive'] - $z0['runs_exclusive'] === 1, 'deleteWhere in einem Schreiblauf', $z1['runs_exclusive'] - $z0['runs_exclusive']);

    // Tombstones
    $file = GBDB::withInstance($instance, static function () :string {
        $m = new ReflectionMethod(GBDBEngine::class, 'makePath');
        $m->setAccessible(true);

        return (string) $m->invoke(null, 'daten', 'eintraege');
    });
    $relative = substr(GBDBStorage::storageDir($file), strlen('gbdbdata://store/'));
    $old = ['format' => 4, 'free_slots' => [], 'tombstones' => []];

    for ($i = 50000; $i < 53000; $i++) {
        $old['tombstones'][(string) $i] = ['id' => $i, 'deleted_at' => 1, 'reusable' => true];
    }

    GBDBRuntime::write($relative . '/free/free.json', json_encode($old, JSON_PRETTY_PRINT) . "\n");
    GBDB::withInstance($instance, static fn () => GBDB::deleteWhere('daten', 'eintraege', static fn (array $row) :bool => $row['schluessel'] === 'k21'));
    $free = json_decode((string) file_get_contents(GBDBRuntime::native($relative . '/free/free.json')), true);
    check(count($free['tombstones'] ?? []) === GBDBStorage::TOMBSTONE_KEEP && (int) ($free['tombstones_dropped'] ?? 0) >= 2000, 'Tombstones auf ' . GBDBStorage::TOMBSTONE_KEEP . ' begrenzt, Rest gezählt', [count($free['tombstones'] ?? []), $free['tombstones_dropped'] ?? null]);

    // GL1-B: Mengenlöschung schreibt Append-, WAL- und Änderungsprotokoll als Block (vorher etwa 15 ms je Zeile)
    GBDB::withInstance($instance, static function () :void {
        for ($i = 1; $i <= 600; $i++) {
            GBDB::insert('daten', 'eintraege', ['schluessel' => 'b' . $i, 'wert' => 'x']);
        }
    });
    $eventJobs = static function () :int {
        $n = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(GBDBConfig::dataPath(), FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->getFilename() === 'jobs.json' && str_contains($f->getPathname(), '/queue/')) {
                foreach ((array) (json_decode((string) file_get_contents($f->getPathname()), true)['jobs'] ?? []) as $job) {
                    $n += (($job['type'] ?? '') === 'event.delete' && ($job['payload']['table'] ?? '') === 'eintraege') ? 1 : 0;
                }
            }
        }

        return $n;
    };
    $jobsBefore = $eventJobs();
    $t = microtime(true);
    $deleted = GBDB::withInstance($instance, static fn () => GBDB::deleteWhere('daten', 'eintraege', static fn (array $row) :bool => str_starts_with((string) $row['schluessel'], 'b')));
    $seconds = microtime(true) - $t;
    check($deleted === 600 && $seconds < 2.0, 'GL1-B deleteWhere 600 Zeilen unter 2 s Wandzeit', [$deleted, round($seconds, 3)]);
    check($eventJobs() - $jobsBefore === 600, 'GL1-B Ereignis-Warteschlange weiter mit einem Job je gelöschter Zeile (ein Schreibvorgang)', [$jobsBefore, $eventJobs()]);
    $gone = finish(child('echo json_encode(["n" => count(array_filter((array) GBDB::withInstance("gllast", static fn () => GBDB::getData("daten", "eintraege")), static fn ($r) => is_array($r) && str_starts_with((string) ($r["schluessel"] ?? ""), "b")))]);'));
    check(($gone['n'] ?? -1) === 0, 'GL1-B gelöschte Zeilen auch für einen anderen Prozess weg', $gone);
    $walFile = sys_get_temp_dir() . '/gllast-wal-' . bin2hex(random_bytes(4)) . '.db';
    $walOps = [['op' => 'del', 'id' => 1, 'ts' => 1], ['op' => 'del', 'id' => 2, 'ts' => 1]];
    GBDBStorage::walBlock($walFile, array_map(static fn (array $op) :array => [$op, 'committed', 'tx_b' . $op['id']], $walOps));
    $recovered = GBDBStorage::recoverWal($walFile);
    check(($recovered['replayed'] ?? 0) === 2 && (GBDBStorage::recoverWal($walFile)['replayed'] ?? -1) === 0, 'GL1-B WAL-Block wird von recoverWal genau einmal nachgetragen', $recovered);
    @unlink($walFile);
    @unlink($walFile . '.wal');

    // Seiten
    putenv('GBDB_EAGER_STORAGE_SYNC=1');
    GBDB::withInstance($instance, static function () :void {
        for ($i = 1; $i <= 120; $i++) {
            GBDB::insert('daten', 'eintraege', ['schluessel' => 'p' . $i, 'wert' => 'GEHEIM-p' . $i . str_repeat('y', 60)]);
        }
    });
    $pages = glob(GBDBRuntime::native($relative . '/data') . '/chunk_*.page') ?: [];
    $inodes = [];
    clearstatcache();

    foreach ($pages as $page) {
        $inodes[$page] = fileinode($page);
    }

    GBDB::withInstance($instance, static fn () => GBDB::edit('daten', 'eintraege', 'schluessel', 'p120', ['wert' => 'GEHEIM-neu']));
    clearstatcache();
    $same = count(array_filter($pages, static fn (string $p) :bool => is_file($p) && fileinode($p) === $inodes[$p]));
    check(count($pages) >= 3 && $same === count($pages) - 1, 'Änderung einer Zeile schreibt nur eine Seite', [count($pages), $same]);
    $plain = false;

    foreach ($pages as $page) {
        $plain = $plain || str_contains((string) file_get_contents($page), 'GEHEIM-');
    }

    check(!Vars::crypt_data() || !$plain, 'Seiten ohne Klartext bei verschlüsselten Daten');
    $verify = GBDB::withInstance($instance, static fn () => GBDB::verifyStorage('daten', 'eintraege'));
    check(($verify['ok'] ?? false) === true, 'Speicherprüfung ok', $verify['errors'] ?? null);
    putenv('GBDB_EAGER_STORAGE_SYNC');
} catch (Throwable $exception) {
    $failures++;
    echo 'FAIL: ', get_class($exception), ': ', $exception->getMessage(), ' @ ', $exception->getFile(), ':', $exception->getLine(), "\n";
} finally {
    removeTree($root);
}

echo $failures === 0 ? "OK: {$checks} Prüfungen\n" : "FEHLER: {$failures} von {$checks} Prüfungen\n";
exit($failures === 0 ? 0 : 1);
