<?php

declare(strict_types=1);

/** Public API. The inherited implementation runs inside one consistent storage scope. */
class GBDB extends GBDBEngine {
    public static function update(?string $pathOfOldDB = null) :array {
        return GBDBRuntime::run(static fn() => parent::update($pathOfOldDB));
    }

    public static function migrate(?string $pathOfOldDB = null) :array {
        return GBDBRuntime::run(static fn() => parent::migrate($pathOfOldDB));
    }

    public static function boot() :array {
        return GBDBRuntime::run(static fn() => parent::boot());
    }

    public static function shutdown() :void {
        GBDBRuntime::run(static function () :void {
            parent::shutdown();
        });
    }

    public static function dirtyShutdownDetected() :bool {
        return GBDBRuntime::run(static fn() => parent::dirtyShutdownDetected());
    }

    public static function recover(bool $auto = false) :array {
        return GBDBRuntime::run(static fn() => parent::recover($auto));
    }

    public static function recoverTable(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::recoverTable($database, $table));
    }

    public static function recoveryReport() :array {
        return GBDBRuntime::run(static fn() => parent::recoveryReport());
    }

    public static function isolationLevel(string $level) :string {
        return GBDBRuntime::run(static fn() => parent::isolationLevel($level));
    }

    public static function beginSnapshot() :string {
        return GBDBRuntime::run(static fn() => parent::beginSnapshot());
    }

    public static function endSnapshot(string $snapshotId) :bool {
        return GBDBRuntime::run(static fn() => parent::endSnapshot($snapshotId));
    }

    public static function transactionSnapshots() :array {
        return GBDBRuntime::run(static fn() => parent::transactionSnapshots(), true);
    }

    public static function rowVersions(string $database, string $table, int $rowId) :array {
        return GBDBRuntime::run(static fn() => parent::rowVersions($database, $table, $rowId), true);
    }

    public static function getDataSnapshot(string $database, string $table, ?string $snapshotId = null, bool $filter = false, mixed $where = '', mixed $is = '') :mixed {
        return GBDBRuntime::run(static fn() => parent::getDataSnapshot($database, $table, $snapshotId, $filter, $where, $is), true);
    }

    public static function optimisticEditData(string $database, string $table, mixed $where, mixed $is, array $newData, int $expectedVersion) :bool {
        return GBDBRuntime::run(static fn() => parent::optimisticEditData($database, $table, $where, $is, $newData, $expectedVersion));
    }

    public static function pessimisticRowLock(string $database, string $table, int $rowId, int $timeoutMs = 0) :string|false {
        return GBDBRuntime::run(static fn() => parent::pessimisticRowLock($database, $table, $rowId, $timeoutMs));
    }

    public static function cleanupRowVersions(string $database, string $table, int $keepSeconds = 3600) :array {
        return GBDBRuntime::run(static fn() => parent::cleanupRowVersions($database, $table, $keepSeconds));
    }

    public static function versionChains(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::versionChains($database, $table), true);
    }

    public static function garbageCollectVersions(string $database, string $table, int $keepSeconds = 3600) :array {
        return GBDBRuntime::run(static fn() => parent::garbageCollectVersions($database, $table, $keepSeconds));
    }

    public static function mvccCleanup(int $keepSeconds = 3600) :array {
        return GBDBRuntime::run(static fn() => parent::mvccCleanup($keepSeconds));
    }

    public static function testConsistentReads() :array {
        return GBDBRuntime::run(static fn() => parent::testConsistentReads());
    }

    public static function mvccLoadTest(int $writes = 100) :array {
        return GBDBRuntime::run(static fn() => parent::mvccLoadTest($writes));
    }

    public static function mvccStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::mvccStats($database, $table));
    }

    public static function transactionTimeout(int $seconds) :int {
        return GBDBRuntime::run(static fn() => parent::transactionTimeout($seconds));
    }

    public static function begin() :bool {
        return GBDBRuntime::run(static fn() => parent::begin());
    }

    public static function savepoint(string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::savepoint($name));
    }

    public static function rollbackTo(string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::rollbackTo($name));
    }

    public static function commit() :bool {
        return GBDBRuntime::run(static fn() => parent::commit());
    }

    public static function rollback() :bool {
        return GBDBRuntime::run(static fn() => parent::rollback());
    }

    public static function transactionStatus() :array {
        return GBDBRuntime::run(static fn() => parent::transactionStatus(), true);
    }

    public static function withInstance(string $instance, callable $callback) :mixed {
        return GBDBRuntime::run(static fn() => parent::withInstance($instance, $callback));
    }

    public static function schemaTable(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::schemaTable($database, $table), true);
    }

    public static function enableSchemaTypes(string $database, string $table, bool $enabled = true) :bool {
        return GBDBRuntime::run(static fn() => parent::enableSchemaTypes($database, $table, $enabled));
    }

    public static function setColumnType(string $database, string $table, string $column, string $type, array $options = []) :bool {
        return GBDBRuntime::run(static fn() => parent::setColumnType($database, $table, $column, $type, $options));
    }

    public static function setColumnDefault(string $database, string $table, string $column, mixed $default) :bool {
        return GBDBRuntime::run(static fn() => parent::setColumnDefault($database, $table, $column, $default));
    }

    public static function setSchemaConstraint(string $database, string $table, string $column, string $constraint, mixed $value = true) :bool {
        return GBDBRuntime::run(static fn() => parent::setSchemaConstraint($database, $table, $column, $constraint, $value));
    }

    public static function createSequence(string $database, string $table, string $name, int $start = 0, int $step = 1) :bool {
        return GBDBRuntime::run(static fn() => parent::createSequence($database, $table, $name, $start, $step));
    }

    public static function nextSequence(string $database, string $table, string $name) :int {
        return GBDBRuntime::run(static fn() => parent::nextSequence($database, $table, $name));
    }

    public static function defineMigration(string $database, string $table, string $name, array $operations) :bool {
        return GBDBRuntime::run(static fn() => parent::defineMigration($database, $table, $name, $operations));
    }

    public static function runMigration(string $database, string $table, string $name) :array {
        return GBDBRuntime::run(static fn() => parent::runMigration($database, $table, $name));
    }

    public static function rollbackMigration(string $database, string $table, string $name) :array {
        return GBDBRuntime::run(static fn() => parent::rollbackMigration($database, $table, $name));
    }

    public static function checkSchema(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::checkSchema($database, $table));
    }

    public static function repairSchema(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairSchema($database, $table));
    }

    public static function migrationHistory(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::migrationHistory($database, $table));
    }

    public static function verifyStorage(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyStorage($database, $table));
    }

    public static function storageStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::storageStats($database, $table));
    }

    public static function createInstance(string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::createInstance($name));
    }

    public static function deleteInstance(string $name, bool $force = false) :bool {
        return GBDBRuntime::run(static fn() => parent::deleteInstance($name, $force));
    }

    public static function listInstances(bool $includeSystem = false) :array {
        return GBDBRuntime::run(static fn() => parent::listInstances($includeSystem), true);
    }

    public static function listAllInstances() :array {
        return GBDBRuntime::run(static fn() => parent::listAllInstances(), true);
    }

    public static function createDatabase(string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::createDatabase($name));
    }

    public static function deleteDatabase(string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::deleteDatabase($name));
    }

    public static function createTable(string $database, string $table, array $cols) :bool {
        return GBDBRuntime::run(static fn() => parent::createTable($database, $table, $cols));
    }

    public static function addColumn(string $database, string $table, string $column, mixed $default = '') :bool {
        return GBDBRuntime::run(static fn() => parent::addColumn($database, $table, $column, $default));
    }

    public static function deleteTable(string $database, string $table) :bool {
        return GBDBRuntime::run(static fn() => parent::deleteTable($database, $table));
    }

    public static function replaceTableContents(string $database, string $table, array $columns, array $rows) :bool {
        return GBDBRuntime::run(static fn() => parent::replaceTableContents($database, $table, $columns, $rows));
    }

    public static function insertData(string $database, string $table, mixed $data) :int {
        return GBDBRuntime::run(static fn() => parent::insertData($database, $table, $data));
    }

    public static function deleteData(string $database, string $table, mixed $where, mixed $is) :bool {
        return GBDBRuntime::run(static fn() => parent::deleteData($database, $table, $where, $is));
    }

    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData) :bool {
        return GBDBRuntime::run(static fn() => parent::editData($database, $table, $where, $is, $newData));
    }

    public static function getData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '') :mixed {
        return GBDBRuntime::run(static fn() => parent::getData($database, $table, $filter, $where, $is), true);
    }

    public static function elementExists(string $database, string $table, mixed $where, mixed $is) :bool {
        return GBDBRuntime::run(static fn() => parent::elementExists($database, $table, $where, $is), true);
    }

    public static function listDBs() :array {
        return GBDBRuntime::run(static fn() => parent::listDBs(), true);
    }

    public static function listTables(string $database, bool $descending = false) :array {
        return GBDBRuntime::run(static fn() => parent::listTables($database, $descending), true);
    }

    public static function deleteAll(string $database) :bool {
        return GBDBRuntime::run(static fn() => parent::deleteAll($database));
    }

    public static function nextID(string $database, string $table) :int {
        return GBDBRuntime::run(static fn() => parent::nextID($database, $table), true);
    }

    public static function getKeys(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::getKeys($database, $table), true);
    }

    public static function query(string $script, array $ctx = [], array $params = []) :array {
        return GBDBRuntime::run(static fn() => parent::query($script, $ctx, $params));
    }

    public static function runScript(string $scriptName, array $params = [], array $ctx = []) :array {
        return GBDBRuntime::run(static fn() => parent::runScript($scriptName, $params, $ctx));
    }

    public static function compactTable(string $database, string $table) :bool {
        return GBDBRuntime::run(static fn() => parent::compactTable($database, $table));
    }

    public static function snapshot(string $database, string $table, string $reason = 'manual') :string {
        return GBDBRuntime::run(static fn() => parent::snapshot($database, $table, $reason));
    }

    public static function createIndex(string $database, string $table, array|string $column, string $type = 'single', array $options = []) :bool {
        return GBDBRuntime::run(static fn() => parent::createIndex($database, $table, $column, $type, $options));
    }

    public static function createPrimaryIndex(string $database, string $table, string $column = 'id') :bool {
        return GBDBRuntime::run(static fn() => parent::createPrimaryIndex($database, $table, $column));
    }

    public static function createUniqueIndex(string $database, string $table, array|string $columns) :bool {
        return GBDBRuntime::run(static fn() => parent::createUniqueIndex($database, $table, $columns));
    }

    public static function createCompositeIndex(string $database, string $table, array $columns) :bool {
        return GBDBRuntime::run(static fn() => parent::createCompositeIndex($database, $table, $columns));
    }

    public static function createSortedIndex(string $database, string $table, array|string $columns) :bool {
        return GBDBRuntime::run(static fn() => parent::createSortedIndex($database, $table, $columns));
    }

    public static function createRangeIndex(string $database, string $table, string $column) :bool {
        return GBDBRuntime::run(static fn() => parent::createRangeIndex($database, $table, $column));
    }

    public static function createPrefixIndex(string $database, string $table, string $column) :bool {
        return GBDBRuntime::run(static fn() => parent::createPrefixIndex($database, $table, $column));
    }

    public static function createFulltextIndex(string $database, string $table, array $columns = []) :bool {
        return GBDBRuntime::run(static fn() => parent::createFulltextIndex($database, $table, $columns));
    }

    public static function dropIndex(string $database, string $table, string $column) :bool {
        return GBDBRuntime::run(static fn() => parent::dropIndex($database, $table, $column));
    }

    public static function listIndexes(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::listIndexes($database, $table), true);
    }

    public static function rebuildIndexes(string $database, string $table) :bool {
        return GBDBRuntime::run(static fn() => parent::rebuildIndexes($database, $table));
    }

    public static function repairIndexes(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairIndexes($database, $table));
    }

    public static function verifyIndexes(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyIndexes($database, $table));
    }

    public static function health(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::health($database, $table));
    }

    public static function repairTable(string $database, string $table) :bool {
        return GBDBRuntime::run(static fn() => parent::repairTable($database, $table));
    }

    public static function meta(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::meta($database, $table));
    }

    public static function addConstraint(string $database, string $table, string $column, string $type) :bool {
        return GBDBRuntime::run(static fn() => parent::addConstraint($database, $table, $column, $type));
    }

    public static function dropConstraint(string $database, string $table, string $column, string $type) :bool {
        return GBDBRuntime::run(static fn() => parent::dropConstraint($database, $table, $column, $type));
    }

    public static function listConstraints(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::listConstraints($database, $table), true);
    }

    public static function vacuum(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::vacuum($database, $table));
    }

    public static function restoreSnapshot(string $database, string $table, string $snapshotId) :bool {
        return GBDBRuntime::run(static fn() => parent::restoreSnapshot($database, $table, $snapshotId));
    }

    public static function clearRuntimeCache(?string $database = null, ?string $table = null) :void {
        GBDBRuntime::run(static function () use ($database, $table) :void {
            parent::clearRuntimeCache($database, $table);
        });
    }

    public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5) :mixed {
        return GBDBRuntime::run(static fn() => parent::getCachedData($database, $table, $filter, $where, $is, $ttl));
    }

    public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true) :array {
        return GBDBRuntime::run(static fn() => parent::bulkInsert($database, $table, $rows, $transactional));
    }

    public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500) :array {
        return GBDBRuntime::run(static fn() => parent::streamRows($database, $table, $callback, $chunkSize));
    }

    public static function page(string $database, string $table, int $page = 1, int $perPage = 50) :array {
        return GBDBRuntime::run(static fn() => parent::page($database, $table, $page, $perPage));
    }

    public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null) :array {
        return GBDBRuntime::run(static fn() => parent::cursor($database, $table, $limit, $cursor));
    }

    public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array {
        return GBDBRuntime::run(static fn() => parent::fulltext_search($database, $table, $query, $columns, $limit));
    }

    public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array {
        return GBDBRuntime::run(static fn() => parent::fulltext($database, $table, $query, $columns, $limit));
    }

    public static function queryPlan(string $database, string $table, string $where = '', mixed $is = '', ?string $sortField = null, ?int $limit = null, int $offset = 0) :array {
        return GBDBRuntime::run(static fn() => parent::queryPlan($database, $table, $where, $is, $sortField, $limit, $offset));
    }

    public static function grantAcl(string $database, string $table, string $role, string $permission) :bool {
        return GBDBRuntime::run(static fn() => parent::grantAcl($database, $table, $role, $permission));
    }

    public static function revokeAcl(string $database, string $table, string $role, string $permission) :bool {
        return GBDBRuntime::run(static fn() => parent::revokeAcl($database, $table, $role, $permission));
    }

    public static function checkAcl(string $database, string $table, string $role, string $permission) :bool {
        return GBDBRuntime::run(static fn() => parent::checkAcl($database, $table, $role, $permission));
    }

    public static function audit(string $action, array $payload = [], string $actor = 'system') :int {
        return GBDBRuntime::run(static fn() => parent::audit($action, $payload, $actor));
    }

    public static function gdprExport(string $database, string $table, string $column, mixed $value) :array {
        return GBDBRuntime::run(static fn() => parent::gdprExport($database, $table, $column, $value));
    }

    public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]') :bool {
        return GBDBRuntime::run(static fn() => parent::gdprRedact($database, $table, $where, $is, $columns, $replacement));
    }

    public static function partitionTableName(string $table, string $partition) :string {
        return GBDBRuntime::run(static fn() => parent::partitionTableName($table, $partition));
    }

    public static function insertPartitioned(string $database, string $table, string $partition, array $data) :int {
        return GBDBRuntime::run(static fn() => parent::insertPartitioned($database, $table, $partition, $data));
    }

    public static function getPartition(string $database, string $table, string $partition) :array {
        return GBDBRuntime::run(static fn() => parent::getPartition($database, $table, $partition));
    }

    public static function shardTableName(string $table, mixed $key, int $shards = 16) :string {
        return GBDBRuntime::run(static fn() => parent::shardTableName($table, $key, $shards));
    }

    public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16) :int {
        return GBDBRuntime::run(static fn() => parent::insertSharded($database, $table, $key, $data, $shards));
    }

    public static function getShard(string $database, string $table, mixed $key, int $shards = 16) :array {
        return GBDBRuntime::run(static fn() => parent::getShard($database, $table, $key, $shards));
    }

    public static function recoverWalOnly(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::recoverWalOnly($database, $table));
    }

    public static function appendLog(string $database, string $table, int $limit = 100) :array {
        return GBDBRuntime::run(static fn() => parent::appendLog($database, $table, $limit));
    }

    public static function monitor(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::monitor($database, $table));
    }

    public static function insert(string $database, string $table, array $data) :int {
        return GBDBRuntime::run(static fn() => parent::insert($database, $table, $data));
    }

    public static function upsert(string $database, string $table, mixed $where, mixed $is, array $data) :array {
        return GBDBRuntime::run(static fn() => parent::upsert($database, $table, $where, $is, $data));
    }

    public static function stats(string $database, ?string $table = null) :array {
        return GBDBRuntime::run(static fn() => parent::stats($database, $table));
    }

    public static function analyze(string $database, string $table, int $sampleSize = 1000) :array {
        return GBDBRuntime::run(static fn() => parent::analyze($database, $table, $sampleSize));
    }

    public static function autoIndex(string $database, string $table, array $columns = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoIndex($database, $table, $columns));
    }

    public static function explain(string $database, string $table, string $where = '', mixed $is = '') :array {
        return GBDBRuntime::run(static fn() => parent::explain($database, $table, $where, $is));
    }

    public static function newQueryId() :string {
        return GBDBRuntime::run(static fn() => parent::newQueryId());
    }

    public static function activeQueries() :array {
        return GBDBRuntime::run(static fn() => parent::activeQueries());
    }

    public static function killQuery(string $queryId) :array {
        return GBDBRuntime::run(static fn() => parent::killQuery($queryId));
    }

    public static function slowQueryLog(array $plan, float $seconds) :void {
        GBDBRuntime::run(static function () use ($plan, $seconds) :void {
            parent::slowQueryLog($plan, $seconds);
        });
    }

    public static function select(string $database, string $table, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::select($database, $table, $options));
    }

    public static function distinct(string $database, string $table, string $column) :array {
        return GBDBRuntime::run(static fn() => parent::distinct($database, $table, $column));
    }

    public static function aggregate(string $database, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null) :array {
        return GBDBRuntime::run(static fn() => parent::aggregate($database, $table, $fn, $column, $groupBy, $having));
    }

    public static function queryOptions(array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::queryOptions($options));
    }

    public static function prepareQuery(string $name, string $script) :bool {
        return GBDBRuntime::run(static fn() => parent::prepareQuery($name, $script));
    }

    public static function executePreparedQuery(string $name, array $params = [], array $ctx = []) :array {
        return GBDBRuntime::run(static fn() => parent::executePreparedQuery($name, $params, $ctx));
    }

    public static function bindQueryParams(string $script, array $params) :string {
        return GBDBRuntime::run(static fn() => parent::bindQueryParams($script, $params));
    }

    public static function clearQueryCache() :void {
        GBDBRuntime::run(static function () :void {
            parent::clearQueryCache();
        });
    }

    public static function innerJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::innerJoin($database, $leftTable, $rightTable, $leftKey, $rightKey, $options));
    }

    public static function leftJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::leftJoin($database, $leftTable, $rightTable, $leftKey, $rightKey, $options));
    }

    public static function join(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, string $type = 'inner', array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::join($database, $leftTable, $rightTable, $leftKey, $rightKey, $type, $options));
    }

    public static function joinPlan(string $leftTable, string $rightTable, int $leftRows, int $rightRows, string $leftKey, string $rightKey, string $type = 'inner') :array {
        return GBDBRuntime::run(static fn() => parent::joinPlan($leftTable, $rightTable, $leftRows, $rightRows, $leftKey, $rightKey, $type));
    }

    public static function subquery(callable $query) :array {
        return GBDBRuntime::run(static fn() => parent::subquery($query));
    }

    public static function streamQuery(string $database, string $table, callable $callback, int $chunkSize = 500) :array {
        return GBDBRuntime::run(static fn() => parent::streamQuery($database, $table, $callback, $chunkSize));
    }

    public static function allowFullScan(string $database, string $table, string $where = '') :bool {
        return GBDBRuntime::run(static fn() => parent::allowFullScan($database, $table, $where));
    }

    public static function addForeignKey(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = 'id', array $options = []) :bool {
        return GBDBRuntime::run(static fn() => parent::addForeignKey($database, $table, $column, $refDatabase, $refTable, $refColumn, $options));
    }

    public static function defineRelation(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = 'id', array $options = []) :bool {
        return GBDBRuntime::run(static fn() => parent::defineRelation($database, $table, $column, $refDatabase, $refTable, $refColumn, $options));
    }

    public static function dropRelation(string $database, string $table, string $name) :bool {
        return GBDBRuntime::run(static fn() => parent::dropRelation($database, $table, $name));
    }

    public static function createRelationIndex(string $database, string $table, string $column) :bool {
        return GBDBRuntime::run(static fn() => parent::createRelationIndex($database, $table, $column));
    }

    public static function checkOrphans(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::checkOrphans($database, $table));
    }

    public static function repairOrphans(string $database, string $table, string $mode = 'report') :array {
        return GBDBRuntime::run(static fn() => parent::repairOrphans($database, $table, $mode));
    }

    public static function repairConstraints(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairConstraints($database, $table));
    }

    public static function relationGraph(?string $database = null) :array {
        return GBDBRuntime::run(static fn() => parent::relationGraph($database));
    }

    public static function relationDocs(?string $database = null) :string {
        return GBDBRuntime::run(static fn() => parent::relationDocs($database));
    }

    public static function checkRelations(?string $database = null) :array {
        return GBDBRuntime::run(static fn() => parent::checkRelations($database));
    }

    public static function snowflakeId(int $node = 1) :string {
        return GBDBRuntime::run(static fn() => parent::snowflakeId($node));
    }

    public static function distributedId(string $tenant = '', string $shard = '') :string {
        return GBDBRuntime::run(static fn() => parent::distributedId($tenant, $shard));
    }

    public static function idExists(string $database, string $table, string $column, mixed $id) :bool {
        return GBDBRuntime::run(static fn() => parent::idExists($database, $table, $column, $id), true);
    }

    public static function safeId(string $database, string $table, string $column = 'uid', string $type = 'ulid', array $options = []) :string {
        return GBDBRuntime::run(static fn() => parent::safeId($database, $table, $column, $type, $options));
    }

    public static function readGenerator(string $database, string $table, int $chunkSize = 500) :Generator {
        return GBDBRuntime::iterate(static fn() => parent::readGenerator($database, $table, $chunkSize));
    }

    public static function chunkedRows(string $database, string $table, int $chunkSize = 500, ?callable $filter = null) :Generator {
        return GBDBRuntime::iterate(static fn() => parent::chunkedRows($database, $table, $chunkSize, $filter));
    }

    public static function openCursor(string $database, string $table, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::openCursor($database, $table, $options));
    }

    public static function fetchCursor(string $token) :array {
        return GBDBRuntime::run(static fn() => parent::fetchCursor($token));
    }

    public static function getDataGuarded(string $database, string $table, int $maxRows = 10000) :array {
        return GBDBRuntime::run(static fn() => parent::getDataGuarded($database, $table, $maxRows), true);
    }

    public static function importStreaming(string $database, string $table, iterable $rows, int $chunkSize = 500) :array {
        return GBDBRuntime::run(static fn() => parent::importStreaming($database, $table, $rows, $chunkSize));
    }

    public static function exportStreaming(string $database, string $table, callable $writer, int $chunkSize = 500) :array {
        return GBDBRuntime::run(static fn() => parent::exportStreaming($database, $table, $writer, $chunkSize));
    }

    public static function setMaxRows(string $database, string $table, int $maxRows) :bool {
        return GBDBRuntime::run(static fn() => parent::setMaxRows($database, $table, $maxRows));
    }

    public static function definePartitioning(string $database, string $table, string $type, string $column, array $options = []) :bool {
        return GBDBRuntime::run(static fn() => parent::definePartitioning($database, $table, $type, $column, $options));
    }

    public static function partitionByDate(string $database, string $table, string $column, string $format = 'Y-m') :bool {
        return GBDBRuntime::run(static fn() => parent::partitionByDate($database, $table, $column, $format));
    }

    public static function partitionByUser(string $database, string $table, string $column = 'user_id') :bool {
        return GBDBRuntime::run(static fn() => parent::partitionByUser($database, $table, $column));
    }

    public static function partitionByTenant(string $database, string $table, string $column = 'tenant_id') :bool {
        return GBDBRuntime::run(static fn() => parent::partitionByTenant($database, $table, $column));
    }

    public static function partitionByHash(string $database, string $table, string $column, int $buckets = 16) :bool {
        return GBDBRuntime::run(static fn() => parent::partitionByHash($database, $table, $column, $buckets));
    }

    public static function partitionKey(string $database, string $table, array $row) :string {
        return GBDBRuntime::run(static fn() => parent::partitionKey($database, $table, $row), true);
    }

    public static function partitionStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::partitionStats($database, $table));
    }

    public static function partitionPrune(string $database, string $table, array $criteria) :array {
        return GBDBRuntime::run(static fn() => parent::partitionPrune($database, $table, $criteria));
    }

    public static function repairPartitions(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairPartitions($database, $table));
    }

    public static function backupPartition(string $database, string $table, string $partition, string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::backupPartition($database, $table, $partition, $target));
    }

    public static function setPartitionReadOnly(string $database, string $table, string $partition, bool $readonly = true) :bool {
        return GBDBRuntime::run(static fn() => parent::setPartitionReadOnly($database, $table, $partition, $readonly));
    }

    public static function archiveOldPartitions(string $database, string $table, int $olderThan, bool $readonly = true) :array {
        return GBDBRuntime::run(static fn() => parent::archiveOldPartitions($database, $table, $olderThan, $readonly));
    }

    public static function preparePartitionMerge(string $database, string $table, array $partitions, string $target) :array {
        return GBDBRuntime::run(static fn() => parent::preparePartitionMerge($database, $table, $partitions, $target));
    }

    public static function preparePartitionSplit(string $database, string $table, string $partition, array $targets) :array {
        return GBDBRuntime::run(static fn() => parent::preparePartitionSplit($database, $table, $partition, $targets));
    }

    public static function preparePartitionMigration(string $database, string $table, string $partition, string $targetInstance) :array {
        return GBDBRuntime::run(static fn() => parent::preparePartitionMigration($database, $table, $partition, $targetInstance));
    }

    public static function defineShardConcept(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineShardConcept($config));
    }

    public static function registerShard(string $name, array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::registerShard($name, $config));
    }

    public static function shardMap() :array {
        return GBDBRuntime::run(static fn() => parent::shardMap());
    }

    public static function defineShardKey(string $database, string $table, string $column, string $strategy = 'hash') :bool {
        return GBDBRuntime::run(static fn() => parent::defineShardKey($database, $table, $column, $strategy));
    }

    public static function shardForValue(mixed $value, string $strategy = 'hash') :string {
        return GBDBRuntime::run(static fn() => parent::shardForValue($value, $strategy));
    }

    public static function shardRouter(string $database, string $table, array $row) :array {
        return GBDBRuntime::run(static fn() => parent::shardRouter($database, $table, $row));
    }

    public static function prepareUserSharding(string $database, string $table, string $column = 'user_id') :bool {
        return GBDBRuntime::run(static fn() => parent::prepareUserSharding($database, $table, $column));
    }

    public static function prepareTenantSharding(string $database, string $table, string $column = 'tenant_id') :bool {
        return GBDBRuntime::run(static fn() => parent::prepareTenantSharding($database, $table, $column));
    }

    public static function prepareHashSharding(string $database, string $table, string $column = 'id') :bool {
        return GBDBRuntime::run(static fn() => parent::prepareHashSharding($database, $table, $column));
    }

    public static function shardAwareId(string $database, string $table, array $row = []) :string {
        return GBDBRuntime::run(static fn() => parent::shardAwareId($database, $table, $row));
    }

    public static function crossShardQueryRules(array $rules = []) :array {
        return GBDBRuntime::run(static fn() => parent::crossShardQueryRules($rules));
    }

    public static function prepareShardRebalancing() :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardRebalancing());
    }

    public static function prepareShardMigration(string $fromShard, string $toShard, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardMigration($fromShard, $toShard, $options));
    }

    public static function shardHealth() :array {
        return GBDBRuntime::run(static fn() => parent::shardHealth());
    }

    public static function prepareCrossShardTransactions() :array {
        return GBDBRuntime::run(static fn() => parent::prepareCrossShardTransactions());
    }

    public static function prepareShardAwareBackups() :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardAwareBackups());
    }

    public static function prepareShardMonitoring() :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardMonitoring());
    }

    public static function definePrimaryReplicaModel(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::definePrimaryReplicaModel($config));
    }

    public static function replicationLog() :string {
        return GBDBRuntime::run(static fn() => parent::replicationLog());
    }

    public static function replicateAsync() :array {
        return GBDBRuntime::run(static fn() => parent::replicateAsync());
    }

    public static function prepareReadReplica(string $name, array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareReadReplica($name, $config));
    }

    public static function replicaCatchup(string $replica) :array {
        return GBDBRuntime::run(static fn() => parent::replicaCatchup($replica));
    }

    public static function replicaLag() :array {
        return GBDBRuntime::run(static fn() => parent::replicaLag());
    }

    public static function replicaHealth() :array {
        return GBDBRuntime::run(static fn() => parent::replicaHealth());
    }

    public static function prepareReplicaRepair(string $replica = '') :array {
        return GBDBRuntime::run(static fn() => parent::prepareReplicaRepair($replica));
    }

    public static function prepareFailover(string $replica = '') :array {
        return GBDBRuntime::run(static fn() => parent::prepareFailover($replica));
    }

    public static function promoteReplicaToPrimary(string $replica) :array {
        return GBDBRuntime::run(static fn() => parent::promoteReplicaToPrimary($replica));
    }

    public static function enableSplitBrainProtection(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::enableSplitBrainProtection($config));
    }

    public static function prepareReplicationSlots(array $replicas = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareReplicationSlots($replicas));
    }

    public static function prepareShardReplication(string $shard = '') :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardReplication($shard));
    }

    public static function prepareTenantReplication(string $tenant = '') :array {
        return GBDBRuntime::run(static fn() => parent::prepareTenantReplication($tenant));
    }

    public static function registerClusterNode(string $node, array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::registerClusterNode($node, $config));
    }

    public static function clusterNodes() :array {
        return GBDBRuntime::run(static fn() => parent::clusterNodes());
    }

    public static function heartbeatNode(string $node = '') :array {
        return GBDBRuntime::run(static fn() => parent::heartbeatNode($node));
    }

    public static function prepareLeaderElection() :array {
        return GBDBRuntime::run(static fn() => parent::prepareLeaderElection());
    }

    public static function defineQuorum(int $minNodes = 1) :array {
        return GBDBRuntime::run(static fn() => parent::defineQuorum($minNodes));
    }

    public static function clusterHealth() :array {
        return GBDBRuntime::run(static fn() => parent::clusterHealth());
    }

    public static function defineNodeRoles(array $roles = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineNodeRoles($roles));
    }

    public static function prepareServiceDiscovery(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareServiceDiscovery($config));
    }

    public static function prepareDistributedLocks() :array {
        return GBDBRuntime::run(static fn() => parent::prepareDistributedLocks());
    }

    public static function setPrimaryNode(string $node) :array {
        return GBDBRuntime::run(static fn() => parent::setPrimaryNode($node));
    }

    public static function setReplicaNode(string $node) :array {
        return GBDBRuntime::run(static fn() => parent::setReplicaNode($node));
    }

    public static function setWorkerNode(string $node) :array {
        return GBDBRuntime::run(static fn() => parent::setWorkerNode($node));
    }

    public static function setMaintenanceNode(string $node) :array {
        return GBDBRuntime::run(static fn() => parent::setMaintenanceNode($node));
    }

    public static function clusterConfig(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::clusterConfig($config));
    }

    public static function detectSplitBrain() :array {
        return GBDBRuntime::run(static fn() => parent::detectSplitBrain());
    }

    public static function prepareClusterRecovery() :array {
        return GBDBRuntime::run(static fn() => parent::prepareClusterRecovery());
    }

    public static function backupLog(string $action, array $payload = []) :bool {
        return GBDBRuntime::run(static fn() => parent::backupLog($action, $payload));
    }

    public static function fullBackup(string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::fullBackup($target));
    }

    public static function prepareIncrementalBackup() :array {
        return GBDBRuntime::run(static fn() => parent::prepareIncrementalBackup());
    }

    public static function prepareDifferentialBackup() :array {
        return GBDBRuntime::run(static fn() => parent::prepareDifferentialBackup());
    }

    public static function snapshotBackup(string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::snapshotBackup($target));
    }

    public static function prepareHotBackup() :array {
        return GBDBRuntime::run(static fn() => parent::prepareHotBackup());
    }

    public static function coldBackup(string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::coldBackup($target));
    }

    public static function verifyBackup(string $path) :array {
        return GBDBRuntime::run(static fn() => parent::verifyBackup($path));
    }

    public static function restoreTest(string $backupPath) :array {
        return GBDBRuntime::run(static fn() => parent::restoreTest($backupPath));
    }

    public static function preparePointInTimeRecovery(?int $timestamp = null) :array {
        return GBDBRuntime::run(static fn() => parent::preparePointInTimeRecovery($timestamp));
    }

    public static function encryptedBackup(string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::encryptedBackup($target));
    }

    public static function prepareRemoteBackups(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareRemoteBackups($config));
    }

    public static function prepareOffsiteBackups(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareOffsiteBackups($config));
    }

    public static function rotateBackups(int $keep = 10) :array {
        return GBDBRuntime::run(static fn() => parent::rotateBackups($keep));
    }

    public static function applyBackupRetention(int $days = 30) :array {
        return GBDBRuntime::run(static fn() => parent::applyBackupRetention($days));
    }

    public static function tenantBackup(string $tenant, string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::tenantBackup($tenant, $target));
    }

    public static function instanceBackup(string $instance, string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::instanceBackup($instance, $target));
    }

    public static function tableBackup(string $database, string $table, string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::tableBackup($database, $table, $target));
    }

    public static function repairMode(bool $repair = false, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::repairMode($repair, $options));
    }

    public static function tableDeepCheck(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::tableDeepCheck($database, $table));
    }

    public static function verifyTableChecksum(string $database, string $table, bool $updateMeta = false) :array {
        return GBDBRuntime::run(static fn() => parent::verifyTableChecksum($database, $table, $updateMeta));
    }

    public static function prepareRowChecksums(string $database, string $table, bool $write = true) :array {
        return GBDBRuntime::run(static fn() => parent::prepareRowChecksums($database, $table, $write));
    }

    public static function verifyPageChecksums(string $database, string $table, int $chunkSize = 500, bool $write = true) :array {
        return GBDBRuntime::run(static fn() => parent::verifyPageChecksums($database, $table, $chunkSize, $write));
    }

    public static function verifyIndexConsistency(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyIndexConsistency($database, $table));
    }

    public static function verifyForeignKeys(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyForeignKeys($database, $table));
    }

    public static function verifySchemaCheck(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifySchemaCheck($database, $table));
    }

    public static function verifyWalCheck(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyWalCheck($database, $table));
    }

    public static function verifyLockCheck(string $database = '', string $table = '') :array {
        return GBDBRuntime::run(static fn() => parent::verifyLockCheck($database, $table));
    }

    public static function verifyStorageCheck(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::verifyStorageCheck($database, $table));
    }

    public static function repairIndex(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairIndex($database, $table));
    }

    public static function repairWal(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairWal($database, $table));
    }

    public static function repairRelations(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::repairRelations($database, $table));
    }

    public static function repairReport(string $database = '', string $table = '') :array {
        return GBDBRuntime::run(static fn() => parent::repairReport($database, $table));
    }

    public static function isolateCorruptFiles(string $database, string $table, string $reason = 'manual') :array {
        return GBDBRuntime::run(static fn() => parent::isolateCorruptFiles($database, $table, $reason));
    }

    public static function manualRecoveryTools() :array {
        return GBDBRuntime::run(static fn() => parent::manualRecoveryTools());
    }

    public static function autoCompact(array|string $options = [], ?string $table = null) :array {
        return GBDBRuntime::run(static fn() => parent::autoCompact($options, $table));
    }

    public static function autoVacuum(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoVacuum($o));
    }

    public static function autoAnalyze(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoAnalyze($o));
    }

    public static function autoIndexRebuild(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoIndexRebuild($o));
    }

    public static function autoIndexRepair(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoIndexRepair($o));
    }

    public static function autoJournalCleanup(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoJournalCleanup($o));
    }

    public static function autoVersionCleanup(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoVersionCleanup($o));
    }

    public static function autoOrphanCleanup(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoOrphanCleanup($o));
    }

    public static function autoStatsRefresh(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoStatsRefresh($o));
    }

    public static function autoMaintenance(array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoMaintenance($options));
    }

    public static function maintenanceScheduler(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::maintenanceScheduler($config));
    }

    public static function maintenanceWindow(?string $start = null, ?string $end = null) :array {
        return GBDBRuntime::run(static fn() => parent::maintenanceWindow($start, $end));
    }

    public static function maintenanceLogs(int $limit = 100) :array {
        return GBDBRuntime::run(static fn() => parent::maintenanceLogs($limit));
    }

    public static function maintenanceCli() :array {
        return GBDBRuntime::run(static fn() => parent::maintenanceCli());
    }

    public static function maintenanceUi() :array {
        return GBDBRuntime::run(static fn() => parent::maintenanceUi());
    }

    public static function deleteByRetention(string $database, string $table, string $column = 'created_at', int $days = 365) :array {
        return GBDBRuntime::run(static fn() => parent::deleteByRetention($database, $table, $column, $days));
    }

    public static function autoBackup(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::autoBackup($o));
    }

    public static function dbHealth(bool $allInstances = true) :array {
        return GBDBRuntime::run(static fn() => parent::dbHealth($allInstances));
    }

    public static function tableStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::tableStats($database, $table));
    }

    public static function indexStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::indexStats($database, $table));
    }

    public static function queryStats() :array {
        return GBDBRuntime::run(static fn() => parent::queryStats());
    }

    public static function walStats(string $database, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::walStats($database, $table));
    }

    public static function replicationStats() :array {
        return GBDBRuntime::run(static fn() => parent::replicationStats());
    }

    public static function backupStats() :array {
        return GBDBRuntime::run(static fn() => parent::backupStats());
    }

    public static function errorLog(string $message = '', array $context = []) :array {
        return GBDBRuntime::run(static fn() => parent::errorLog($message, $context));
    }

    public static function auditLog(string $action = '', array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::auditLog($action, $payload));
    }

    public static function recordQueryMetric(string $type, float $ms, int $rowsScanned = 0, int $rowsReturned = 0, bool $fullScan = false, int $memory = 0) :void {
        GBDBRuntime::run(static function () use ($type, $ms, $rowsScanned, $rowsReturned, $fullScan, $memory) :void {
            parent::recordQueryMetric($type, $ms, $rowsScanned, $rowsReturned, $fullScan, $memory);
        });
    }

    public static function performanceMetrics() :array {
        return GBDBRuntime::run(static fn() => parent::performanceMetrics());
    }

    public static function opsPerMinute() :array {
        return GBDBRuntime::run(static fn() => parent::opsPerMinute());
    }

    public static function queryTimeAverage() :float {
        return GBDBRuntime::run(static fn() => parent::queryTimeAverage());
    }

    public static function queryTimePercentiles() :array {
        return GBDBRuntime::run(static fn() => parent::queryTimePercentiles());
    }

    public static function memoryUsagePerQuery() :array {
        return GBDBRuntime::run(static fn() => parent::memoryUsagePerQuery());
    }

    public static function rowsScanned() :int {
        return GBDBRuntime::run(static fn() => parent::rowsScanned());
    }

    public static function rowsReturned() :int {
        return GBDBRuntime::run(static fn() => parent::rowsReturned());
    }

    public static function fullTableScanCount() :int {
        return GBDBRuntime::run(static fn() => parent::fullTableScanCount());
    }

    public static function indexHitRate() :float {
        return GBDBRuntime::run(static fn() => parent::indexHitRate());
    }

    public static function dashboardData() :array {
        return GBDBRuntime::run(static fn() => parent::dashboardData());
    }

    public static function adminAlerts() :array {
        return GBDBRuntime::run(static fn() => parent::adminAlerts());
    }

    public static function querySafety(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::querySafety($o));
    }

    public static function setMaxExecutionTime(int $s) :array {
        return GBDBRuntime::run(static fn() => parent::setMaxExecutionTime($s));
    }

    public static function setMaxScanRows(int $rows) :array {
        return GBDBRuntime::run(static fn() => parent::setMaxScanRows($rows));
    }

    public static function setMaxExportSize(int $bytes) :array {
        return GBDBRuntime::run(static fn() => parent::setMaxExportSize($bytes));
    }

    public static function blockExpensiveQueries(bool $a = true) :array {
        return GBDBRuntime::run(static fn() => parent::blockExpensiveQueries($a));
    }

    public static function adminOnlyHeavyQueries(bool $a = true) :array {
        return GBDBRuntime::run(static fn() => parent::adminOnlyHeavyQueries($a));
    }

    public static function querySandbox(array $c = []) :array {
        return GBDBRuntime::run(static fn() => parent::querySandbox($c));
    }

    public static function rateLimitQueryType(string $type, int $limit) :array {
        return GBDBRuntime::run(static fn() => parent::rateLimitQueryType($type, $limit));
    }

    public static function cacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool {
        return GBDBRuntime::run(static fn() => parent::cacheSet($key, $value, $ttl, $tags));
    }

    public static function cacheGet(string $key, mixed $default = null) :mixed {
        return GBDBRuntime::run(static fn() => parent::cacheGet($key, $default));
    }

    public static function cacheInvalidate(string $key) :void {
        GBDBRuntime::run(static function () use ($key) :void {
            parent::cacheInvalidate($key);
        });
    }

    public static function cacheInvalidateTag(string $tag) :int {
        return GBDBRuntime::run(static fn() => parent::cacheInvalidateTag($tag));
    }

    public static function rowCache(string $db, string $table, mixed $id, int $ttl = 60) :mixed {
        return GBDBRuntime::run(static fn() => parent::rowCache($db, $table, $id, $ttl));
    }

    public static function tableMetaCache(string $db, string $table, int $ttl = 60) :array {
        return GBDBRuntime::run(static fn() => parent::tableMetaCache($db, $table, $ttl));
    }

    public static function schemaCache(string $db, string $table, int $ttl = 60) :array {
        return GBDBRuntime::run(static fn() => parent::schemaCache($db, $table, $ttl));
    }

    public static function indexCache(string $db, string $table, int $ttl = 60) :array {
        return GBDBRuntime::run(static fn() => parent::indexCache($db, $table, $ttl));
    }

    public static function queryResultCache(string $key, callable $cb, int $ttl = 60, array $tags = []) :mixed {
        return GBDBRuntime::run(static fn() => parent::queryResultCache($key, $cb, $ttl, $tags));
    }

    public static function permissionCache(string $k, mixed $v = null, int $ttl = 300) :mixed {
        return GBDBRuntime::run(static fn() => parent::permissionCache($k, $v, $ttl));
    }

    public static function fulltextCache(string $k, mixed $v = null, int $ttl = 300) :mixed {
        return GBDBRuntime::run(static fn() => parent::fulltextCache($k, $v, $ttl));
    }

    public static function counterCache(string $k, mixed $v = null, int $ttl = 300) :mixed {
        return GBDBRuntime::run(static fn() => parent::counterCache($k, $v, $ttl));
    }

    public static function cacheTtl(string $key) :int {
        return GBDBRuntime::run(static fn() => parent::cacheTtl($key));
    }

    public static function cacheStats() :array {
        return GBDBRuntime::run(static fn() => parent::cacheStats());
    }

    public static function cacheHitRate() :float {
        return GBDBRuntime::run(static fn() => parent::cacheHitRate());
    }

    public static function cacheWarmup(array $items = []) :array {
        return GBDBRuntime::run(static fn() => parent::cacheWarmup($items));
    }

    public static function cacheRepair() :array {
        return GBDBRuntime::run(static fn() => parent::cacheRepair());
    }

    public static function apcuCachePrepared() :array {
        return GBDBRuntime::run(static fn() => parent::apcuCachePrepared());
    }

    public static function redisAdapterPrepared(array $c = []) :array {
        return GBDBRuntime::run(static fn() => parent::redisAdapterPrepared($c));
    }

    public static function fileCacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool {
        return GBDBRuntime::run(static fn() => parent::fileCacheSet($key, $value, $ttl, $tags));
    }

    public static function fileCacheGet(string $key, mixed $default = null) :mixed {
        return GBDBRuntime::run(static fn() => parent::fileCacheGet($key, $default));
    }

    public static function cacheTests() :array {
        return GBDBRuntime::run(static fn() => parent::cacheTests());
    }

    public static function atomicIncrement(string $db, string $table, mixed $id, string $column, int|float $step = 1) :array {
        return GBDBRuntime::run(static fn() => parent::atomicIncrement($db, $table, $id, $column, $step));
    }

    public static function atomicDecrement(string $db, string $table, mixed $id, string $column, int|float $step = 1) :array {
        return GBDBRuntime::run(static fn() => parent::atomicDecrement($db, $table, $id, $column, $step));
    }

    public static function compareAndSwap(string $db, string $table, mixed $id, string $column, mixed $expected, mixed $newValue) :array {
        return GBDBRuntime::run(static fn() => parent::compareAndSwap($db, $table, $id, $column, $expected, $newValue));
    }

    public static function ensureCounterTable(string $db = 'system', string $table = 'counters') :bool {
        return GBDBRuntime::run(static fn() => parent::ensureCounterTable($db, $table));
    }

    public static function counterValue(string $scope, string $name, int|float $delta = 0, string $db = 'system', string $table = 'counters') :array {
        return GBDBRuntime::run(static fn() => parent::counterValue($scope, $name, $delta, $db, $table));
    }

    public static function prepareShardedCounters(int $shards = 16) :array {
        return GBDBRuntime::run(static fn() => parent::prepareShardedCounters($shards));
    }

    public static function prepareDistributedSafeCounters(array $c = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareDistributedSafeCounters($c));
    }

    public static function likeCounter(string $o, int $d = 1) :array {
        return GBDBRuntime::run(static fn() => parent::likeCounter($o, $d));
    }

    public static function commentCounter(string $o, int $d = 1) :array {
        return GBDBRuntime::run(static fn() => parent::commentCounter($o, $d));
    }

    public static function viewCounter(string $o, int $d = 1) :array {
        return GBDBRuntime::run(static fn() => parent::viewCounter($o, $d));
    }

    public static function followerCounter(string $o, int $d = 1) :array {
        return GBDBRuntime::run(static fn() => parent::followerCounter($o, $d));
    }

    public static function unreadCounter(string $o, int $d = 1) :array {
        return GBDBRuntime::run(static fn() => parent::unreadCounter($o, $d));
    }

    public static function counterRepair(string $db = 'system', string $table = 'counters') :array {
        return GBDBRuntime::run(static fn() => parent::counterRepair($db, $table));
    }

    public static function counterRecalculation(string $db, string $table, string $groupColumn, string $scope) :array {
        return GBDBRuntime::run(static fn() => parent::counterRecalculation($db, $table, $groupColumn, $scope));
    }

    public static function cleanupJournals(int $days = 7) :array {
        return GBDBRuntime::run(static fn() => parent::cleanupJournals($days));
    }

    public static function cleanupVersions(int $days = 30) :array {
        return GBDBRuntime::run(static fn() => parent::cleanupVersions($days));
    }

    public static function refreshStats(array $o = []) :array {
        return GBDBRuntime::run(static fn() => parent::refreshStats($o));
    }

    public static function enqueueJob(string $type, array $payload = [], array $options = []) :string {
        return GBDBRuntime::run(static fn() => parent::enqueueJob($type, $payload, $options));
    }

    public static function delayedJob(string $type, array $payload, int $delaySeconds, array $options = []) :string {
        return GBDBRuntime::run(static fn() => parent::delayedJob($type, $payload, $delaySeconds, $options));
    }

    public static function priorityJob(string $type, array $payload, int $priority = 10, array $options = []) :string {
        return GBDBRuntime::run(static fn() => parent::priorityJob($type, $payload, $priority, $options));
    }

    public static function scheduleJob(string $name, string $type, array $payload, string $cronOrInterval, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::scheduleJob($name, $type, $payload, $cronOrInterval, $options));
    }

    public static function recurringJob(string $name, string $type, array $payload, int $everySeconds, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::recurringJob($name, $type, $payload, $everySeconds, $options));
    }

    public static function claimJob(string $workerId, string $queue = 'default') :array {
        return GBDBRuntime::run(static fn() => parent::claimJob($workerId, $queue));
    }

    public static function workerHeartbeat(string $jobId, string $workerId) :bool {
        return GBDBRuntime::run(static fn() => parent::workerHeartbeat($jobId, $workerId));
    }

    public static function processDueJobs(int $limit = 25, string $queue = '') :array {
        return GBDBRuntime::run(static fn() => parent::processDueJobs($limit, $queue));
    }

    public static function workQueue(int $limit = 25, string $queue = '') :array {
        return GBDBRuntime::run(static fn() => parent::workQueue($limit, $queue));
    }

    public static function finishJob(string $jobId, bool $ok = true, array $result = []) :array {
        return GBDBRuntime::run(static fn() => parent::finishJob($jobId, $ok, $result));
    }

    public static function retryFailedJobs(int $limit = 50) :array {
        return GBDBRuntime::run(static fn() => parent::retryFailedJobs($limit));
    }

    public static function queueStats() :array {
        return GBDBRuntime::run(static fn() => parent::queueStats());
    }

    public static function jobDashboard(int $limit = 50) :array {
        return GBDBRuntime::run(static fn() => parent::jobDashboard($limit));
    }

    public static function queueUiPrepared() :array {
        return GBDBRuntime::run(static fn() => parent::queueUiPrepared());
    }

    public static function queueCliPrepared() :array {
        return GBDBRuntime::run(static fn() => parent::queueCliPrepared());
    }

    public static function srvQueueBridge(string $action, array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::srvQueueBridge($action, $payload));
    }

    public static function backgroundIndexUpdate(string $db, string $table) :string {
        return GBDBRuntime::run(static fn() => parent::backgroundIndexUpdate($db, $table));
    }

    public static function backgroundFeedFanout(array $payload) :string {
        return GBDBRuntime::run(static fn() => parent::backgroundFeedFanout($payload));
    }

    public static function backgroundMailSending(array $payload) :string {
        return GBDBRuntime::run(static fn() => parent::backgroundMailSending($payload));
    }

    public static function backgroundMediaProcessing(array $payload) :string {
        return GBDBRuntime::run(static fn() => parent::backgroundMediaProcessing($payload));
    }

    public static function backgroundBackups(array $payload = []) :string {
        return GBDBRuntime::run(static fn() => parent::backgroundBackups($payload));
    }

    public static function defineTrigger(string $db, string $table, string $event, string $name, array $definition) :array {
        return GBDBRuntime::run(static fn() => parent::defineTrigger($db, $table, $event, $name, $definition));
    }

    public static function runDataTriggers(string $event, string $db, string $table, array $context = []) :array {
        return GBDBRuntime::run(static fn() => parent::runDataTriggers($event, $db, $table, $context));
    }

    public static function enqueueEvent(string $event, string $db, string $table, array $payload = []) :string {
        return GBDBRuntime::run(static fn() => parent::enqueueEvent($event, $db, $table, $payload));
    }

    public static function eventLog(string $event, string $db, string $table, array $payload = []) :bool {
        return GBDBRuntime::run(static fn() => parent::eventLog($event, $db, $table, $payload));
    }

    public static function triggerDebug(string $db, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::triggerDebug($db, $table));
    }

    public static function schemaDefinedTriggers(string $db, string $table) :array {
        return GBDBRuntime::run(static fn() => parent::schemaDefinedTriggers($db, $table));
    }

    public static function greenqlTriggerFunction(string $name, string $script) :array {
        return GBDBRuntime::run(static fn() => parent::greenqlTriggerFunction($name, $script));
    }

    public static function defineView(string $name, string $query, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineView($name, $query, $options));
    }

    public static function refreshView(string $name) :array {
        return GBDBRuntime::run(static fn() => parent::refreshView($name));
    }

    public static function getView(string $name, bool $refresh = false) :mixed {
        return GBDBRuntime::run(static fn() => parent::getView($name, $refresh));
    }

    public static function viewQueryPlanner(string $name) :array {
        return GBDBRuntime::run(static fn() => parent::viewQueryPlanner($name));
    }

    public static function materializedViewIndex(string $name, array $columns) :array {
        return GBDBRuntime::run(static fn() => parent::materializedViewIndex($name, $columns));
    }

    public static function defineProcedure(string $name, string $script, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineProcedure($name, $script, $options));
    }

    public static function callProcedure(string $name, array $params = []) :mixed {
        return GBDBRuntime::run(static fn() => parent::callProcedure($name, $params));
    }

    public static function procedureLogs(string $name = '') :array {
        return GBDBRuntime::run(static fn() => parent::procedureLogs($name));
    }

    public static function procedureDebug(string $name) :array {
        return GBDBRuntime::run(static fn() => parent::procedureDebug($name));
    }

    public static function defineDbRole(string $role, array $permissions = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineDbRole($role, $permissions));
    }

    public static function defineDbUser(string $user, array $roles = [
    0 => 'readonly',
    ], array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::defineDbUser($user, $roles, $options));
    }

    public static function defineApiKeyScope(string $keyId, array $scopes) :array {
        return GBDBRuntime::run(static fn() => parent::defineApiKeyScope($keyId, $scopes));
    }

    public static function defaultDbRoles() :array {
        return GBDBRuntime::run(static fn() => parent::defaultDbRoles());
    }

    public static function checkPermission(string $user, string $action, string $db = '', string $table = '', array $row = []) :bool {
        return GBDBRuntime::run(static fn() => parent::checkPermission($user, $action, $db, $table, $row));
    }

    public static function permissionAudit(string $user, string $action, array $context = []) :bool {
        return GBDBRuntime::run(static fn() => parent::permissionAudit($user, $action, $context));
    }

    public static function permissionDebug(string $user) :array {
        return GBDBRuntime::run(static fn() => parent::permissionDebug($user));
    }

    public static function definePolicy(string $name, array $rules) :array {
        return GBDBRuntime::run(static fn() => parent::definePolicy($name, $rules));
    }

    public static function evaluatePolicy(string $action, array $row, array $context = []) :array {
        return GBDBRuntime::run(static fn() => parent::evaluatePolicy($action, $row, $context));
    }

    public static function ownerUidPolicy(string $name = 'owner_uid') :array {
        return GBDBRuntime::run(static fn() => parent::ownerUidPolicy($name));
    }

    public static function visibilityPolicy() :array {
        return GBDBRuntime::run(static fn() => parent::visibilityPolicy());
    }

    public static function tenantIsolationPolicy(string $tenantField = 'tenant_id') :array {
        return GBDBRuntime::run(static fn() => parent::tenantIsolationPolicy($tenantField));
    }

    public static function policyDebugOutput() :array {
        return GBDBRuntime::run(static fn() => parent::policyDebugOutput());
    }

    public static function auditDataChange(string $action, string $db, string $table, array $old = [], array $new = [], array $context = []) :bool {
        return GBDBRuntime::run(static fn() => parent::auditDataChange($action, $db, $table, $old, $new, $context));
    }

    public static function auditExport(array $filter = []) :array {
        return GBDBRuntime::run(static fn() => parent::auditExport($filter));
    }

    public static function auditSearch(string $needle) :array {
        return GBDBRuntime::run(static fn() => parent::auditSearch($needle));
    }

    public static function auditRetention(int $days = 365) :array {
        return GBDBRuntime::run(static fn() => parent::auditRetention($days));
    }

    public static function auditIntegrity() :array {
        return GBDBRuntime::run(static fn() => parent::auditIntegrity());
    }

    public static function markPiiField(string $db, string $table, string $field, string $classification = 'pii') :array {
        return GBDBRuntime::run(static fn() => parent::markPiiField($db, $table, $field, $classification));
    }

    public static function userDataExport(string $userField, mixed $userId, array $tables = []) :array {
        return GBDBRuntime::run(static fn() => parent::userDataExport($userField, $userId, $tables));
    }

    public static function userDataDelete(string $userField, mixed $userId, array $tables = []) :array {
        return GBDBRuntime::run(static fn() => parent::userDataDelete($userField, $userId, $tables));
    }

    public static function userDataRedact(string $db, string $table, string $where, mixed $is, array $fields, string $replacement = '[redacted]') :array {
        return GBDBRuntime::run(static fn() => parent::userDataRedact($db, $table, $where, $is, $fields, $replacement));
    }

    public static function retentionPolicy(string $name, array $rule) :array {
        return GBDBRuntime::run(static fn() => parent::retentionPolicy($name, $rule));
    }

    public static function consentHistory(string $subject, array $entry) :array {
        return GBDBRuntime::run(static fn() => parent::consentHistory($subject, $entry));
    }

    public static function encryptionConfig(array $cfg = []) :array {
        return GBDBRuntime::run(static fn() => parent::encryptionConfig($cfg));
    }

    public static function deriveKey(string $secret, string $salt = '', int $version = 1) :string {
        return GBDBRuntime::run(static fn() => parent::deriveKey($secret, $salt, $version));
    }

    public static function rotateKey(string $name = 'default') :array {
        return GBDBRuntime::run(static fn() => parent::rotateKey($name));
    }

    public static function scrubSecrets(mixed $value) :mixed {
        return GBDBRuntime::run(static fn() => parent::scrubSecrets($value));
    }

    public static function securityTests() :array {
        return GBDBRuntime::run(static fn() => parent::securityTests());
    }

    public static function blobPath(string $hash = '', bool $ensure = true) :string {
        return GBDBRuntime::run(static fn() => parent::blobPath($hash, $ensure));
    }

    public static function putBlob(string $sourcePath, string $visibility = 'private', array $meta = []) :array {
        return GBDBRuntime::run(static fn() => parent::putBlob($sourcePath, $visibility, $meta));
    }

    public static function signedUrl(string $hash, int $ttl = 300) :string {
        return GBDBRuntime::run(static fn() => parent::signedUrl($hash, $ttl));
    }

    public static function temporaryUrl(string $hash, int $ttl = 300) :string {
        return GBDBRuntime::run(static fn() => parent::temporaryUrl($hash, $ttl));
    }

    public static function validateMime(string $mime, array $allowed = [
    0 => 'image/jpeg',
    1 => 'image/png',
    2 => 'image/webp',
    3 => 'application/pdf',
    ]) :bool {
        return GBDBRuntime::run(static fn() => parent::validateMime($mime, $allowed));
    }

    public static function deleteMedia(string $hash) :array {
        return GBDBRuntime::run(static fn() => parent::deleteMedia($hash));
    }

    public static function orphanMediaCleanup() :array {
        return GBDBRuntime::run(static fn() => parent::orphanMediaCleanup());
    }

    public static function mediaPermissions(string $hash, array $permissions) :array {
        return GBDBRuntime::run(static fn() => parent::mediaPermissions($hash, $permissions));
    }

    public static function mediaAudit(string $hash, string $action, array $context = []) :bool {
        return GBDBRuntime::run(static fn() => parent::mediaAudit($hash, $action, $context));
    }

    public static function mediaProcessingPrepared(string $hash) :string {
        return GBDBRuntime::run(static fn() => parent::mediaProcessingPrepared($hash));
    }

    public static function socialSchemaPatterns() :array {
        return GBDBRuntime::run(static fn() => parent::socialSchemaPatterns());
    }

    public static function installSocialPatterns(string $db = 'social') :array {
        return GBDBRuntime::run(static fn() => parent::installSocialPatterns($db));
    }

    public static function softDelete(string $db, string $table, string $where, mixed $is) :bool {
        return GBDBRuntime::run(static fn() => parent::softDelete($db, $table, $where, $is));
    }

    public static function contentVisibilityRules(array $rules = []) :array {
        return GBDBRuntime::run(static fn() => parent::contentVisibilityRules($rules));
    }

    public static function feedFanoutJob(string $postId, array $targets) :string {
        return GBDBRuntime::run(static fn() => parent::feedFanoutJob($postId, $targets));
    }

    public static function fanoutOnRead(array $context) :array {
        return GBDBRuntime::run(static fn() => parent::fanoutOnRead($context));
    }

    public static function hybridFeed(array $context) :array {
        return GBDBRuntime::run(static fn() => parent::hybridFeed($context));
    }

    public static function moderationQueue(string $objectType, string $objectId, string $reason, array $context = []) :string {
        return GBDBRuntime::run(static fn() => parent::moderationQueue($objectType, $objectId, $reason, $context));
    }

    public static function patternTemplate(string $name = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::patternTemplate($name));
    }

    public static function listPatterns(bool $includeInactive = true) :array {
        return GBDBRuntime::run(static fn() => parent::listPatterns($includeInactive));
    }

    public static function getPattern(string $name) :array {
        return GBDBRuntime::run(static fn() => parent::getPattern($name));
    }

    public static function savePattern(array $pattern) :array {
        return GBDBRuntime::run(static fn() => parent::savePattern($pattern));
    }

    public static function deletePattern(string $name) :array {
        return GBDBRuntime::run(static fn() => parent::deletePattern($name));
    }

    public static function renamePattern(string $name, string $newName) :array {
        return GBDBRuntime::run(static fn() => parent::renamePattern($name, $newName));
    }

    public static function setPatternActive(string $name, bool $active) :array {
        return GBDBRuntime::run(static fn() => parent::setPatternActive($name, $active));
    }

    public static function installPattern(string $patternName, string $instanceName, bool $allowInactive = false) :array {
        return GBDBRuntime::run(static fn() => parent::installPattern($patternName, $instanceName, $allowInactive));
    }

    public static function installIntranetPattern(string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::installIntranetPattern($database));
    }

    public static function intranetAudit(string $action, array $payload = [], string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::intranetAudit($action, $payload, $database));
    }

    public static function intranetDirectorySearch(string $query, string $database = 'intranet', int $limit = 50) :array {
        return GBDBRuntime::run(static fn() => parent::intranetDirectorySearch($query, $database, $limit));
    }

    public static function defineDocumentPermission(string $docId, string $subjectType, string $subjectId, string $permission, string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::defineDocumentPermission($docId, $subjectType, $subjectId, $permission, $database));
    }

    public static function defineRetentionRule(string $name, int $days, string $action = 'archive', string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::defineRetentionRule($name, $days, $action, $database));
    }

    public static function prepareSsoAdapter(string $type, array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSsoAdapter($type, $config));
    }

    public static function prepareLdapAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareLdapAdapter($config));
    }

    public static function prepareSamlAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSamlAdapter($config));
    }

    public static function prepareOidcAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareOidcAdapter($config));
    }

    public static function prepareOAuth2Adapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareOAuth2Adapter($config));
    }

    public static function externalUserSync(array $users, string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::externalUserSync($users, $database));
    }

    public static function groupSync(array $groups, string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::groupSync($groups, $database));
    }

    public static function departmentSync(array $departments, string $database = 'intranet') :array {
        return GBDBRuntime::run(static fn() => parent::departmentSync($departments, $database));
    }

    public static function roleMapping(array $mapping) :array {
        return GBDBRuntime::run(static fn() => parent::roleMapping($mapping));
    }

    public static function ssoAudit(string $provider, string $subject, string $action, array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::ssoAudit($provider, $subject, $action, $payload));
    }

    public static function createServiceAccount(string $name, array $scopes = [], array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::createServiceAccount($name, $scopes, $options));
    }

    public static function createApiToken(string $subject, array $scopes = [], int $ttlSeconds = 2592000) :array {
        return GBDBRuntime::run(static fn() => parent::createApiToken($subject, $scopes, $ttlSeconds));
    }

    public static function validateApiToken(string $token, string $scope = '') :array {
        return GBDBRuntime::run(static fn() => parent::validateApiToken($token, $scope));
    }

    public static function rotateApiToken(string $tokenId, int $ttlSeconds = 2592000) :array {
        return GBDBRuntime::run(static fn() => parent::rotateApiToken($tokenId, $ttlSeconds));
    }

    public static function registerSession(string $uid, array $device = [], int $ttlSeconds = 172800) :array {
        return GBDBRuntime::run(static fn() => parent::registerSession($uid, $device, $ttlSeconds));
    }

    public static function forceLogout(string $uid) :array {
        return GBDBRuntime::run(static fn() => parent::forceLogout($uid));
    }

    public static function sessionList(string $uid = '') :array {
        return GBDBRuntime::run(static fn() => parent::sessionList($uid));
    }

    public static function tenantPolicy(string $tenant, array $policy = []) :array {
        return GBDBRuntime::run(static fn() => parent::tenantPolicy($tenant, $policy));
    }

    public static function tenantQuotas(string $tenant, array $quotas = []) :array {
        return GBDBRuntime::run(static fn() => parent::tenantQuotas($tenant, $quotas));
    }

    public static function tenantRestore(string $tenant, string $backupPath) :array {
        return GBDBRuntime::run(static fn() => parent::tenantRestore($tenant, $backupPath));
    }

    public static function tenantExport(string $tenant, string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::tenantExport($tenant, $target));
    }

    public static function tenantDelete(string $tenant, bool $force = false) :array {
        return GBDBRuntime::run(static fn() => parent::tenantDelete($tenant, $force));
    }

    public static function prepareTenantMigration(string $tenant, string $targetShard, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareTenantMigration($tenant, $targetShard, $options));
    }

    public static function tenantStorageStats(string $tenant = '') :array {
        return GBDBRuntime::run(static fn() => parent::tenantStorageStats($tenant));
    }

    public static function tenantAdminRoles(string $tenant, array $roles) :array {
        return GBDBRuntime::run(static fn() => parent::tenantAdminRoles($tenant, $roles));
    }

    public static function tenantEncryptionKeysPrepared(string $tenant, array $options = []) :array {
        return GBDBRuntime::run(static fn() => parent::tenantEncryptionKeysPrepared($tenant, $options));
    }

    public static function quotaLimits(array $limits = []) :array {
        return GBDBRuntime::run(static fn() => parent::quotaLimits($limits));
    }

    public static function quotaCheck(string $database = '', string $table = '') :array {
        return GBDBRuntime::run(static fn() => parent::quotaCheck($database, $table));
    }

    public static function quotaDashboard(bool $includeTenants = false) :array {
        return GBDBRuntime::run(static fn() => parent::quotaDashboard($includeTenants));
    }

    public static function rateLimit(string $bucket, string $key, int $limit, int $windowSeconds = 60) :array {
        return GBDBRuntime::run(static fn() => parent::rateLimit($bucket, $key, $limit, $windowSeconds));
    }

    public static function configureRateLimits(array $limits) :array {
        return GBDBRuntime::run(static fn() => parent::configureRateLimits($limits));
    }

    public static function rateLimitLogs(int $limit = 100) :array {
        return GBDBRuntime::run(static fn() => parent::rateLimitLogs($limit));
    }

    public static function exportRows(string $database, string $table, string $format = 'json', string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::exportRows($database, $table, $format, $target));
    }

    public static function importRows(string $database, string $table, string $file, string $format = 'json', bool $rollback = true) :array {
        return GBDBRuntime::run(static fn() => parent::importRows($database, $table, $file, $format, $rollback));
    }

    public static function validateImportRows(string $database, string $table, array $rows) :array {
        return GBDBRuntime::run(static fn() => parent::validateImportRows($database, $table, $rows));
    }

    public static function gbdbDump(string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::gbdbDump($target));
    }

    public static function gbdbRestore(string $backupPath) :array {
        return GBDBRuntime::run(static fn() => parent::gbdbRestore($backupPath));
    }

    public static function streamingImport(string $database, string $table, string $file, string $format = 'ndjson', int $chunkSize = 500) :array {
        return GBDBRuntime::run(static fn() => parent::streamingImport($database, $table, $file, $format, $chunkSize));
    }

    public static function streamingExport(string $database, string $table, string $format = 'ndjson', string $target = '') :array {
        return GBDBRuntime::run(static fn() => parent::streamingExport($database, $table, $format, $target));
    }

    public static function exportPermissions(array $rules = []) :array {
        return GBDBRuntime::run(static fn() => parent::exportPermissions($rules));
    }

    public static function exportLogs(int $limit = 100) :array {
        return GBDBRuntime::run(static fn() => parent::exportLogs($limit));
    }

    public static function sqlErsatzFeatureMatrix() :array {
        return GBDBRuntime::run(static fn() => parent::sqlErsatzFeatureMatrix());
    }

    public static function sqlErsatzFeatureStatus(string $feature) :array {
        return GBDBRuntime::run(static fn() => parent::sqlErsatzFeatureStatus($feature));
    }

    public static function prepareSqlAdapter(string $type, array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSqlAdapter($type, $config));
    }

    public static function prepareMySQLImportAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareMySQLImportAdapter($config));
    }

    public static function preparePostgreSQLImportAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::preparePostgreSQLImportAdapter($config));
    }

    public static function prepareSQLiteImportAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSQLiteImportAdapter($config));
    }

    public static function prepareSqlCompatibilityLayer(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSqlCompatibilityLayer($config));
    }

    public static function prepareSqlToGreenQLTranslator(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::prepareSqlToGreenQLTranslator($config));
    }

    public static function preparePdoLikeAdapter(array $config = []) :array {
        return GBDBRuntime::run(static fn() => parent::preparePdoLikeAdapter($config));
    }

    public static function adminUiData() :array {
        return GBDBRuntime::run(static fn() => parent::adminUiData());
    }

    public static function adminUiQueryPlan(string $greenql) :array {
        return GBDBRuntime::run(static fn() => parent::adminUiQueryPlan($greenql));
    }

    public static function cliCommand(string $command, array $args = []) :array {
        return GBDBRuntime::run(static fn() => parent::cliCommand($command, $args));
    }

    public static function transactionApi(callable $callback) :array {
        return GBDBRuntime::run(static fn() => parent::transactionApi($callback));
    }

    public static function cursorApi(string $database, string $table, int $chunkSize = 500) :Generator {
        return GBDBRuntime::iterate(static fn() => parent::cursorApi($database, $table, $chunkSize));
    }

    public static function indexApi(string $database, string $table, string $column) :array {
        return GBDBRuntime::run(static fn() => parent::indexApi($database, $table, $column));
    }

    public static function schemaApi(string $database, string $table, array $columns) :array {
        return GBDBRuntime::run(static fn() => parent::schemaApi($database, $table, $columns));
    }

    public static function migrationApi(string $database, string $table, string $id, callable $cb) :array {
        return GBDBRuntime::run(static fn() => parent::migrationApi($database, $table, $id, $cb));
    }

    public static function backupApi(string $type = 'full', array $args = []) :array {
        return GBDBRuntime::run(static fn() => parent::backupApi($type, $args));
    }

    public static function repairApi(string $database = '', string $table = '') :array {
        return GBDBRuntime::run(static fn() => parent::repairApi($database, $table));
    }

    public static function explainApi(string $database, string $table, array $where = []) :array {
        return GBDBRuntime::run(static fn() => parent::explainApi($database, $table, $where));
    }

    public static function statsApi() :array {
        return GBDBRuntime::run(static fn() => parent::statsApi());
    }

    public static function policyApi(string $name, array $rules = []) :array {
        return GBDBRuntime::run(static fn() => parent::policyApi($name, $rules));
    }

    public static function eventApi(string $event, string $db, string $table, array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::eventApi($event, $db, $table, $payload));
    }

    public static function queueApi(string $action, array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::queueApi($action, $payload));
    }

    public static function mediaApi(array $payload = []) :array {
        return GBDBRuntime::run(static fn() => parent::mediaApi($payload));
    }

    public static function searchApi(string $database, string $table, string $query, array $columns = []) :array {
        return GBDBRuntime::run(static fn() => parent::searchApi($database, $table, $query, $columns));
    }

    public static function cacheApi(string $action, string $key = '', mixed $value = null) :array {
        return GBDBRuntime::run(static fn() => parent::cacheApi($action, $key, $value));
    }

    public static function documentationIndex() :array {
        return GBDBRuntime::run(static fn() => parent::documentationIndex());
    }

    public static function enterpriseSelfTest() :array {
        return GBDBRuntime::run(static fn() => parent::enterpriseSelfTest());
    }

    public static function get(string $base, string $table, mixed $search = false, mixed $where = '', mixed $is = '', array $options = []) :mixed {
        return GBDBRuntime::run(static fn() => parent::get($base, $table, $search, $where, $is, $options), true);
    }

    public static function existsInstance(string $instance) :bool {
        return GBDBRuntime::run(static fn() => parent::existsInstance($instance), true);
    }

    public static function create(string $base, string $table = '', bool $useDataTypes = false, object|array $rows = []) :bool {
        return GBDBRuntime::run(static fn() => parent::create($base, $table, $useDataTypes, $rows));
    }

    public static function exists(string $base, ?string $table = null, mixed $where = '', mixed $is = '') :bool {
        return GBDBRuntime::run(static fn() => parent::exists($base, $table, $where, $is), true);
    }

    public static function edit(string $base, string $table, mixed $where, mixed $is, object|array $newDataAsObject, bool $recursive = false) :bool {
        return GBDBRuntime::run(static fn() => parent::edit($base, $table, $where, $is, $newDataAsObject, $recursive));
    }

    public static function delete(string $base, ?string $table = null, mixed $where = '', mixed $is = '', bool $recursive = false) :bool {
        return GBDBRuntime::run(static fn() => parent::delete($base, $table, $where, $is, $recursive));
    }

    public static function getNextId(string $base, string $table) :int {
        return GBDBRuntime::run(static fn() => parent::getNextId($base, $table), true);
    }

    public static function fullTextSearch(string $base, string $table, string $text) :array {
        return GBDBRuntime::run(static fn() => parent::fullTextSearch($base, $table, $text));
    }

    public static function renameTable(string $base, string $table, string $newTableName) :bool {
        return GBDBRuntime::run(static fn() => parent::renameTable($base, $table, $newTableName));
    }

    public static function renameBase(string $base, string $newBaseName) :bool {
        return GBDBRuntime::run(static fn() => parent::renameBase($base, $newBaseName));
    }

    public static function renameInstance(string $instance, string $newInstanceName) :bool {
        return GBDBRuntime::run(static fn() => parent::renameInstance($instance, $newInstanceName));
    }

    public static function moveBase(string $base, string $fromInstance, string $toInstance) :bool {
        return GBDBRuntime::run(static fn() => parent::moveBase($base, $fromInstance, $toInstance));
    }

    public static function moveTable(string $instance, string $base, string $table, string $toInstance, string $toBase) :bool {
        return GBDBRuntime::run(static fn() => parent::moveTable($instance, $base, $table, $toInstance, $toBase));
    }

    public static function createBackup(string $pathToBackupDir = '') :array {
        return GBDBRuntime::run(static fn() => parent::createBackup($pathToBackupDir));
    }

    public static function runFile(string $pathToFileWithFilename, object|array $parametersAsObject = []) :array {
        return GBDBRuntime::run(static fn() => parent::runFile($pathToFileWithFilename, $parametersAsObject));
    }

    /** Current backend and replication health; contains no credentials. */
    public static function backendStatus() :array {
        return GBDBRuntime::status();
    }

    /** SQL/GBDB transition, guarded by the installation-wide routing lock. */
    public static function switchBackend(string $backend) :array {
        return GBDBRuntime::switch($backend);
    }

    /** Replicate in the direction of the currently authoritative backend. */
    public static function synchronizeSQL() :array {
        return GBDBRuntime::synchronize();
    }
}
