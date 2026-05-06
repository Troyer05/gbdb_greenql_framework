<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/autoloader.php';

function week1_assert(bool $ok, string $message, array &$results): void {
    $results[] = ['ok' => $ok, 'test' => $message];
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$results = [];
$report = GBDB::update();
week1_assert(is_array($report) && ($report['ok'] ?? false), 'GBDB::update() läuft fehlerfrei', $results);

$instance = 'week1regression' . date('YmdHis');
GBDB::createInstance($instance);
GBDB::setInstance($instance);
week1_assert(GBDB::getInstance() === $instance, 'GBDB::setInstance()/getInstance()', $results);

week1_assert(GBDB::createDatabase('main') || in_array('main', GBDB::listDBs(), true), 'GBDB::createDatabase()', $results);
week1_assert(GBDB::createTable('main', 'users', ['uid', 'name', 'role']) || in_array('users', GBDB::listTables('main'), true), 'GBDB::createTable()', $results);

$id = GBDB::insertData('main', 'users', ['uid' => 'u1', 'name' => 'Markus', 'role' => 'admin']);
week1_assert((int)$id >= 0, 'GBDB::insertData()', $results);

$row = GBDB::getData('main', 'users', true, 'uid', 'u1');
week1_assert(is_array($row) && ($row['name'] ?? '') === 'Markus', 'GBDB::getData() mit Filter', $results);

$edited = GBDB::editData('main', 'users', 'uid', 'u1', ['role' => 'dev']);
week1_assert($edited !== false, 'GBDB::editData()', $results);

$row = GBDB::getData('main', 'users', true, 'uid', 'u1');
week1_assert(is_array($row) && ($row['role'] ?? '') === 'dev', 'GBDB::getData() nach editData()', $results);

week1_assert(GBDB::elementExists('main', 'users', 'uid', 'u1'), 'GBDB::elementExists()', $results);

$deleted = GBDB::deleteData('main', 'users', 'uid', 'u1');
week1_assert($deleted !== false, 'GBDB::deleteData()', $results);
week1_assert(!GBDB::elementExists('main', 'users', 'uid', 'u1'), 'Datensatz nach deleteData() entfernt', $results);

GBDB::query('ROOT main; GROW TABLE posts WITH title, status; SEED posts WITH title="Hallo", status="draft";');
$posts = GBDB::getData('main', 'posts');
week1_assert(is_array($posts) && count($posts) >= 1, 'GreenQL über GBDB::query()', $results);

GBDB::deleteInstance($instance, true);

echo json_encode(['ok' => true, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
