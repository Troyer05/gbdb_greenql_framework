<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/autoloader.php';

GBDB::update();

$instance = 'week1testdata';
GBDB::createInstance($instance);
GBDB::setInstance($instance);
GBDB::createDatabase('week1bench');

$tables = [
    'smalltable'  => 5,
    'mediumtable' => 15,
    'largetable'  => 30,
];

foreach ($tables as $table => $rows) {
    if (!in_array($table, GBDB::listTables('week1bench'), true)) {
        GBDB::createTable('week1bench', $table, ['uid', 'title', 'group_name', 'value', 'created_at']);
    }

    $existing = GBDB::getData('week1bench', $table);
    $existingCount = is_array($existing) ? count($existing) : 0;

    for ($i = $existingCount; $i < $rows; $i++) {
        GBDB::insertData('week1bench', $table, [
            'uid' => $table . '_' . $i,
            'title' => ucfirst($table) . ' Row ' . $i,
            'group_name' => 'group_' . ($i % 5),
            'value' => (string)$i,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

$summary = [];
foreach (array_keys($tables) as $table) {
    $summary[$table] = count(GBDB::getData('week1bench', $table));
}

echo json_encode([
    'ok' => true,
    'instance' => GBDB::getInstance(),
    'base' => 'week1bench',
    'tables' => $summary,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
