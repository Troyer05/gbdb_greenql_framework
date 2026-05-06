<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/autoloader.php';

/**
 * Woche 16-20 Regressionstest:
 * - Recovery/WAL
 * - Read/Write-Locks
 * - Optimistic/Pessimistic Locking
 * - MVCC Row-Versionen + Snapshot Reads
 */

$oldInstance = GBDB::getInstance();
$instance = 'week16_20_test_' . date('Ymd_His');

GBDB::setInstance($instance);
GBDB::createDatabase('demo');
GBDB::createTable('demo', 'items', ['name', 'value']);

$id = GBDB::insertData('demo', 'items', ['name' => 'alpha', 'value' => 1]);
$editOk = GBDB::editData('demo', 'items', 'id', $id, ['value' => 2]);
$row = GBDB::getData('demo', 'items', true, 'id', $id);

$versions = GBDB::rowVersions('demo', 'items', $id);
$snapshot = GBDB::beginSnapshot();
$snapshotRows = GBDB::getDataSnapshot('demo', 'items', $snapshot);
GBDB::endSnapshot($snapshot);

$readLock = GBDB::lockTable('demo', 'items', 'read', 1000);
$readLockOk = $readLock !== false;
if ($readLockOk) GBDB::releaseLock($readLock);

$rowLock = GBDB::pessimisticRowLock('demo', 'items', $id, 1000);
$rowLockOk = $rowLock !== false;
if ($rowLockOk) GBDB::releaseLock($rowLock);

$optimisticOk = GBDB::optimisticEditData('demo', 'items', 'id', $id, ['value' => 3], (int)($row['_gbdb_version'] ?? 0));
$optimisticFail = GBDB::optimisticEditData('demo', 'items', 'id', $id, ['value' => 4], 1) === false;

$recovery = GBDB::recoverTable('demo', 'items');
$locks = GBDB::lockStats();
$deadlocks = GBDB::detectDeadlocks(1);
$mvcc = GBDB::mvccStats('demo', 'items');

GBDB::deleteAll('demo');
GBDB::setInstance($oldInstance);
GBDB::deleteInstance($instance, true);

$report = [
    'ok' => $id > 0
        && $editOk
        && is_array($row)
        && count($versions) >= 2
        && count($snapshotRows) >= 1
        && $readLockOk
        && $rowLockOk
        && $optimisticOk
        && $optimisticFail
        && ($recovery['ok'] ?? false),
    'insert_id' => $id,
    'edit_ok' => $editOk,
    'versions' => count($versions),
    'snapshot_rows' => count($snapshotRows),
    'read_lock_ok' => $readLockOk,
    'row_lock_ok' => $rowLockOk,
    'optimistic_ok' => $optimisticOk,
    'optimistic_fail_expected' => $optimisticFail,
    'recovery' => $recovery,
    'lock_stats' => $locks,
    'deadlocks' => $deadlocks,
    'mvcc' => $mvcc
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
