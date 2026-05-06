<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../gbdb.php';

$rows = [
    ['id' => 1, 'active' => 1, 'role' => 'admin', 'created' => '2026-03-01', 'deleted' => null, 'email' => 'root@example.com'],
    ['id' => 2, 'active' => 1, 'role' => 'dev', 'created' => '2026-07-01', 'deleted' => '', 'email' => 'dev@example.com'],
    ['id' => 3, 'active' => 0, 'role' => 'guest', 'created' => '2025-12-01', 'deleted' => '2026-01-01', 'email' => 'guest@test.local'],
];

$tests = [
    'simple' => ['active = 1', [1, 2]],
    'and' => ['active = 1 AND role = "admin"', [1]],
    'or' => ['role = "admin" OR role = "dev"', [1, 2]],
    'mixed_parentheses' => ['active = 1 AND (role = "admin" OR role = "dev")', [1, 2]],
    'in' => ['id IN [1,2,3]', [1, 2, 3]],
    'between' => ['created BETWEEN "2026-01-01" AND "2026-12-31"', [1, 2]],
    'null' => ['deleted IS NULL', [1, 2]],
    'not_null' => ['deleted IS NOT NULL', [3]],
    'like' => ['email LIKE "%@example.com"', [1, 2]],
    'not' => ['NOT (role = "guest")', [1, 2]],
    'invalid' => ['active = AND role = "admin"', []],
];

$out = [];
$ok = true;

foreach ($tests as $name => [$whereRaw, $expected]) {
    $where = GreenQL::parseWhere($whereRaw);
    $hits = [];
    foreach ($rows as $row) {
        if (GreenQL::rowMatch($row, $where)) $hits[] = $row['id'];
    }
    $pass = $hits === $expected;
    $ok = $ok && $pass;
    $out[$name] = ['ok' => $pass, 'where' => $whereRaw, 'expected' => $expected, 'got' => $hits, 'ast' => $where];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => $ok, 'tests' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
