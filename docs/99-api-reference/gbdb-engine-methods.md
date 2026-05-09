# GBDB Engine Public Methods

### `GBDB`

File: `GBDB_GQL/db_engine/gbdb.php`

| Method | Signature | Typical use |
|---|---|---|
| `update()` | `public static function update(?string $pathOfOldDB = null)` | Write/change storage or configuration; validate input and permissions first. |
| `migrate()` | `public static function migrate(?string $pathOfOldDB = null)` | See class section; use when the method name matches the required operation. |

### `GBDB_AdminOpsTrait`

File: `GBDB_GQL/db_engine/gbdb_admin_ops.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `repairMode()` | `public static function repairMode(bool $repair = false, array $options = [])` | Operations, diagnostics, backups or maintenance. |
| `tableDeepCheck()` | `public static function tableDeepCheck(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `verifyTableChecksum()` | `public static function verifyTableChecksum(string $database, string $table, bool $updateMeta = false)` | See class section; use when the method name matches the required operation. |
| `prepareRowChecksums()` | `public static function prepareRowChecksums(string $database, string $table, bool $write = true)` | See class section; use when the method name matches the required operation. |
| `verifyPageChecksums()` | `public static function verifyPageChecksums(string $database, string $table, int $chunkSize = 500, bool $write = true)` | See class section; use when the method name matches the required operation. |
| `verifyIndexConsistency()` | `public static function verifyIndexConsistency(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `verifyForeignKeys()` | `public static function verifyForeignKeys(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `verifySchemaCheck()` | `public static function verifySchemaCheck(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `verifyWalCheck()` | `public static function verifyWalCheck(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `verifyLockCheck()` | `public static function verifyLockCheck(string $database = "", string $table = "")` | See class section; use when the method name matches the required operation. |
| `verifyStorageCheck()` | `public static function verifyStorageCheck(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `repairIndex()` | `public static function repairIndex(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `repairWal()` | `public static function repairWal(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `repairRelations()` | `public static function repairRelations(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `repairReport()` | `public static function repairReport(string $database = "", string $table = "")` | Operations, diagnostics, backups or maintenance. |
| `isolateCorruptFiles()` | `public static function isolateCorruptFiles(string $database, string $table, string $reason = "manual")` | See class section; use when the method name matches the required operation. |
| `manualRecoveryTools()` | `public static function manualRecoveryTools()` | See class section; use when the method name matches the required operation. |
| `autoCompact()` | `public static function autoCompact(array|string $options = [], ?string $table = null)` | See class section; use when the method name matches the required operation. |
| `autoVacuum()` | `public static function autoVacuum(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoAnalyze()` | `public static function autoAnalyze(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoIndexRebuild()` | `public static function autoIndexRebuild(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoIndexRepair()` | `public static function autoIndexRepair(array $o = [])` | Operations, diagnostics, backups or maintenance. |
| `autoJournalCleanup()` | `public static function autoJournalCleanup(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoVersionCleanup()` | `public static function autoVersionCleanup(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoOrphanCleanup()` | `public static function autoOrphanCleanup(array $o = [])` | See class section; use when the method name matches the required operation. |
| `autoStatsRefresh()` | `public static function autoStatsRefresh(array $o = [])` | Operations, diagnostics, backups or maintenance. |
| `autoMaintenance()` | `public static function autoMaintenance(array $options = [])` | Operations, diagnostics, backups or maintenance. |
| `maintenanceScheduler()` | `public static function maintenanceScheduler(array $config = [])` | Operations, diagnostics, backups or maintenance. |
| `maintenanceWindow()` | `public static function maintenanceWindow(?string $start = null, ?string $end = null)` | Operations, diagnostics, backups or maintenance. |
| `maintenanceLogs()` | `public static function maintenanceLogs(int $limit = 100)` | Operations, diagnostics, backups or maintenance. |
| `maintenanceCli()` | `public static function maintenanceCli()` | Operations, diagnostics, backups or maintenance. |
| `maintenanceUi()` | `public static function maintenanceUi()` | Operations, diagnostics, backups or maintenance. |
| `deleteByRetention()` | `public static function deleteByRetention(string $database, string $table, string $column = "created_at", int $days = 365)` | Write/change storage or configuration; validate input and permissions first. |
| `autoBackup()` | `public static function autoBackup(array $o = [])` | Operations, diagnostics, backups or maintenance. |
| `dbHealth()` | `public static function dbHealth(bool $allInstances = true)` | Operations, diagnostics, backups or maintenance. |
| `tableStats()` | `public static function tableStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `indexStats()` | `public static function indexStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `queryStats()` | `public static function queryStats()` | Operations, diagnostics, backups or maintenance. |
| `walStats()` | `public static function walStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `replicationStats()` | `public static function replicationStats()` | Operations, diagnostics, backups or maintenance. |
| `backupStats()` | `public static function backupStats()` | Operations, diagnostics, backups or maintenance. |
| `errorLog()` | `public static function errorLog(string $message = "", array $context = [])` | See class section; use when the method name matches the required operation. |
| `auditLog()` | `public static function auditLog(string $action = "", array $payload = [])` | See class section; use when the method name matches the required operation. |
| `recordQueryMetric()` | `public static function recordQueryMetric(string $type, float $ms, int $rowsScanned = 0, int $rowsReturned = 0, bool $fullScan = false, int $memory = 0)` | See class section; use when the method name matches the required operation. |
| `performanceMetrics()` | `public static function performanceMetrics()` | See class section; use when the method name matches the required operation. |
| `opsPerMinute()` | `public static function opsPerMinute()` | See class section; use when the method name matches the required operation. |
| `queryTimeAverage()` | `public static function queryTimeAverage()` | See class section; use when the method name matches the required operation. |
| `queryTimePercentiles()` | `public static function queryTimePercentiles()` | See class section; use when the method name matches the required operation. |
| `memoryUsagePerQuery()` | `public static function memoryUsagePerQuery()` | See class section; use when the method name matches the required operation. |
| `rowsScanned()` | `public static function rowsScanned()` | See class section; use when the method name matches the required operation. |
| `rowsReturned()` | `public static function rowsReturned()` | See class section; use when the method name matches the required operation. |
| `fullTableScanCount()` | `public static function fullTableScanCount()` | See class section; use when the method name matches the required operation. |
| `indexHitRate()` | `public static function indexHitRate()` | See class section; use when the method name matches the required operation. |
| `dashboardData()` | `public static function dashboardData()` | See class section; use when the method name matches the required operation. |
| `adminAlerts()` | `public static function adminAlerts()` | See class section; use when the method name matches the required operation. |
| `querySafety()` | `public static function querySafety(array $o = [])` | See class section; use when the method name matches the required operation. |
| `setMaxExecutionTime()` | `public static function setMaxExecutionTime(int $s)` | Write/change storage or configuration; validate input and permissions first. |
| `setMaxScanRows()` | `public static function setMaxScanRows(int $rows)` | Write/change storage or configuration; validate input and permissions first. |
| `setMaxExportSize()` | `public static function setMaxExportSize(int $bytes)` | Write/change storage or configuration; validate input and permissions first. |
| `blockExpensiveQueries()` | `public static function blockExpensiveQueries(bool $a = true)` | See class section; use when the method name matches the required operation. |
| `adminOnlyHeavyQueries()` | `public static function adminOnlyHeavyQueries(bool $a = true)` | See class section; use when the method name matches the required operation. |
| `querySandbox()` | `public static function querySandbox(array $c = [])` | See class section; use when the method name matches the required operation. |
| `rateLimitQueryType()` | `public static function rateLimitQueryType(string $type, int $limit)` | See class section; use when the method name matches the required operation. |
| `cacheSet()` | `public static function cacheSet(string $key, mixed $value, int $ttl = 60, array $tags = [])` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheGet()` | `public static function cacheGet(string $key, mixed $default = null)` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheInvalidate()` | `public static function cacheInvalidate(string $key)` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheInvalidateTag()` | `public static function cacheInvalidateTag(string $tag)` | Speed up repeated reads or invalidate cached values after writes. |
| `rowCache()` | `public static function rowCache(string $db, string $table, mixed $id, int $ttl = 60)` | Speed up repeated reads or invalidate cached values after writes. |
| `tableMetaCache()` | `public static function tableMetaCache(string $db, string $table, int $ttl = 60)` | Speed up repeated reads or invalidate cached values after writes. |
| `schemaCache()` | `public static function schemaCache(string $db, string $table, int $ttl = 60)` | Speed up repeated reads or invalidate cached values after writes. |
| `indexCache()` | `public static function indexCache(string $db, string $table, int $ttl = 60)` | Speed up repeated reads or invalidate cached values after writes. |
| `queryResultCache()` | `public static function queryResultCache(string $key, callable $cb, int $ttl = 60, array $tags = [])` | Speed up repeated reads or invalidate cached values after writes. |
| `permissionCache()` | `public static function permissionCache(string $k, mixed $v = null, int $ttl = 300)` | Speed up repeated reads or invalidate cached values after writes. |
| `fulltextCache()` | `public static function fulltextCache(string $k, mixed $v = null, int $ttl = 300)` | Speed up repeated reads or invalidate cached values after writes. |
| `counterCache()` | `public static function counterCache(string $k, mixed $v = null, int $ttl = 300)` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheTtl()` | `public static function cacheTtl(string $key)` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheStats()` | `public static function cacheStats()` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheHitRate()` | `public static function cacheHitRate()` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheWarmup()` | `public static function cacheWarmup(array $items = [])` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheRepair()` | `public static function cacheRepair()` | Speed up repeated reads or invalidate cached values after writes. |
| `apcuCachePrepared()` | `public static function apcuCachePrepared()` | Speed up repeated reads or invalidate cached values after writes. |
| `redisAdapterPrepared()` | `public static function redisAdapterPrepared(array $c = [])` | See class section; use when the method name matches the required operation. |
| `fileCacheSet()` | `public static function fileCacheSet(string $key, mixed $value, int $ttl = 60, array $tags = [])` | Speed up repeated reads or invalidate cached values after writes. |
| `fileCacheGet()` | `public static function fileCacheGet(string $key, mixed $default = null)` | Speed up repeated reads or invalidate cached values after writes. |
| `cacheTests()` | `public static function cacheTests()` | Speed up repeated reads or invalidate cached values after writes. |
| `atomicIncrement()` | `public static function atomicIncrement(string $db, string $table, mixed $id, string $column, int|float $step = 1)` | See class section; use when the method name matches the required operation. |
| `atomicDecrement()` | `public static function atomicDecrement(string $db, string $table, mixed $id, string $column, int|float $step = 1)` | See class section; use when the method name matches the required operation. |
| `compareAndSwap()` | `public static function compareAndSwap(string $db, string $table, mixed $id, string $column, mixed $expected, mixed $newValue)` | See class section; use when the method name matches the required operation. |
| `ensureCounterTable()` | `public static function ensureCounterTable(string $db = "system", string $table = "counters")` | See class section; use when the method name matches the required operation. |
| `counterValue()` | `public static function counterValue(string $scope, string $name, int|float $delta = 0, string $db = "system", string $table = "counters")` | See class section; use when the method name matches the required operation. |
| `prepareShardedCounters()` | `public static function prepareShardedCounters(int $shards = 16)` | See class section; use when the method name matches the required operation. |
| `prepareDistributedSafeCounters()` | `public static function prepareDistributedSafeCounters(array $c = [])` | See class section; use when the method name matches the required operation. |
| `likeCounter()` | `public static function likeCounter(string $o, int $d = 1)` | See class section; use when the method name matches the required operation. |
| `commentCounter()` | `public static function commentCounter(string $o, int $d = 1)` | See class section; use when the method name matches the required operation. |
| `viewCounter()` | `public static function viewCounter(string $o, int $d = 1)` | See class section; use when the method name matches the required operation. |
| `followerCounter()` | `public static function followerCounter(string $o, int $d = 1)` | See class section; use when the method name matches the required operation. |
| `unreadCounter()` | `public static function unreadCounter(string $o, int $d = 1)` | See class section; use when the method name matches the required operation. |
| `counterRepair()` | `public static function counterRepair(string $db = "system", string $table = "counters")` | Operations, diagnostics, backups or maintenance. |
| `counterRecalculation()` | `public static function counterRecalculation(string $db, string $table, string $groupColumn, string $scope)` | See class section; use when the method name matches the required operation. |
| `cleanupJournals()` | `public static function cleanupJournals(int $days = 7)` | See class section; use when the method name matches the required operation. |
| `cleanupVersions()` | `public static function cleanupVersions(int $days = 30)` | See class section; use when the method name matches the required operation. |
| `refreshStats()` | `public static function refreshStats(array $o = [])` | Operations, diagnostics, backups or maintenance. |

### `GBDB_AdvancedTrait`

File: `GBDB_GQL/db_engine/gbdb_advanced.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `clearRuntimeCache()` | `public static function clearRuntimeCache(?string $database = null, ?string $table = null)` | Speed up repeated reads or invalidate cached values after writes. |
| `getCachedData()` | `public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5)` | Read/list data without changing storage. |
| `bulkInsert()` | `public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true)` | Write/change storage or configuration; validate input and permissions first. |
| `streamRows()` | `public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `page()` | `public static function page(string $database, string $table, int $page = 1, int $perPage = 50)` | Read/list data without changing storage. |
| `cursor()` | `public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null)` | Read/list data without changing storage. |
| `fulltext_search()` | `public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `fulltextSearch()` | `public static function fulltextSearch(string $database, string $table, string $query, array $columns = [], int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `fulltext()` | `public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `queryPlan()` | `public static function queryPlan(string $database, string $table, string $where = '', mixed $is = '', ?string $sortField = null, ?int $limit = null, int $offset = 0)` | See class section; use when the method name matches the required operation. |
| `grantAcl()` | `public static function grantAcl(string $database, string $table, string $role, string $permission)` | Write/change storage or configuration; validate input and permissions first. |
| `revokeAcl()` | `public static function revokeAcl(string $database, string $table, string $role, string $permission)` | Write/change storage or configuration; validate input and permissions first. |
| `checkAcl()` | `public static function checkAcl(string $database, string $table, string $role, string $permission)` | See class section; use when the method name matches the required operation. |
| `audit()` | `public static function audit(string $action, array $payload = [], string $actor = 'system')` | See class section; use when the method name matches the required operation. |
| `gdprExport()` | `public static function gdprExport(string $database, string $table, string $column, mixed $value)` | See class section; use when the method name matches the required operation. |
| `gdprRedact()` | `public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]')` | See class section; use when the method name matches the required operation. |
| `migrate()` | `public static function migrate(string $database, string $table, string $migrationId, callable $callback)` | See class section; use when the method name matches the required operation. |
| `partitionTableName()` | `public static function partitionTableName(string $table, string $partition)` | See class section; use when the method name matches the required operation. |
| `insertPartitioned()` | `public static function insertPartitioned(string $database, string $table, string $partition, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `getPartition()` | `public static function getPartition(string $database, string $table, string $partition)` | Read/list data without changing storage. |
| `shardTableName()` | `public static function shardTableName(string $table, mixed $key, int $shards = 16)` | See class section; use when the method name matches the required operation. |
| `insertSharded()` | `public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16)` | Write/change storage or configuration; validate input and permissions first. |
| `getShard()` | `public static function getShard(string $database, string $table, mixed $key, int $shards = 16)` | Read/list data without changing storage. |
| `recoverWalOnly()` | `public static function recoverWalOnly(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `appendLog()` | `public static function appendLog(string $database, string $table, int $limit = 100)` | See class section; use when the method name matches the required operation. |
| `monitor()` | `public static function monitor(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `get()` | `public static function get(string $database, string $table, mixed $where = '', mixed $is = '', array $options = [])` | Read/list data without changing storage. |
| `insert()` | `public static function insert(string $database, string $table, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `edit()` | `public static function edit(string $database, string $table, mixed $where, mixed $is, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `delete()` | `public static function delete(string $database, string $table, mixed $where, mixed $is)` | Write/change storage or configuration; validate input and permissions first. |
| `exists()` | `public static function exists(string $database, string $table, mixed $where, mixed $is)` | See class section; use when the method name matches the required operation. |
| `upsert()` | `public static function upsert(string $database, string $table, mixed $where, mixed $is, array $data)` | See class section; use when the method name matches the required operation. |
| `stats()` | `public static function stats(string $database, ?string $table = null)` | Operations, diagnostics, backups or maintenance. |
| `analyze()` | `public static function analyze(string $database, string $table, int $sampleSize = 1000)` | See class section; use when the method name matches the required operation. |
| `autoIndex()` | `public static function autoIndex(string $database, string $table, array $columns = [])` | See class section; use when the method name matches the required operation. |
| `explain()` | `public static function explain(string $database, string $table, string $where = '', mixed $is = '')` | See class section; use when the method name matches the required operation. |
| `newQueryId()` | `public static function newQueryId()` | See class section; use when the method name matches the required operation. |
| `activeQueries()` | `public static function activeQueries()` | See class section; use when the method name matches the required operation. |
| `killQuery()` | `public static function killQuery(string $queryId)` | See class section; use when the method name matches the required operation. |
| `slowQueryLog()` | `public static function slowQueryLog(array $plan, float $seconds)` | See class section; use when the method name matches the required operation. |
| `select()` | `public static function select(string $database, string $table, array $options = [])` | Read/list data without changing storage. |
| `distinct()` | `public static function distinct(string $database, string $table, string $column)` | See class section; use when the method name matches the required operation. |
| `aggregate()` | `public static function aggregate(string $database, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null)` | See class section; use when the method name matches the required operation. |

### `GBDB_ClusterBackupTrait`

File: `GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `repairPartitions()` | `public static function repairPartitions(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `backupPartition()` | `public static function backupPartition(string $database, string $table, string $partition, string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `setPartitionReadOnly()` | `public static function setPartitionReadOnly(string $database, string $table, string $partition, bool $readonly = true)` | Write/change storage or configuration; validate input and permissions first. |
| `archiveOldPartitions()` | `public static function archiveOldPartitions(string $database, string $table, int $olderThan, bool $readonly = true)` | See class section; use when the method name matches the required operation. |
| `preparePartitionMerge()` | `public static function preparePartitionMerge(string $database, string $table, array $partitions, string $target)` | See class section; use when the method name matches the required operation. |
| `preparePartitionSplit()` | `public static function preparePartitionSplit(string $database, string $table, string $partition, array $targets)` | See class section; use when the method name matches the required operation. |
| `preparePartitionMigration()` | `public static function preparePartitionMigration(string $database, string $table, string $partition, string $targetInstance)` | See class section; use when the method name matches the required operation. |
| `defineShardConcept()` | `public static function defineShardConcept(array $config = [])` | See class section; use when the method name matches the required operation. |
| `registerShard()` | `public static function registerShard(string $name, array $config = [])` | See class section; use when the method name matches the required operation. |
| `shardMap()` | `public static function shardMap()` | See class section; use when the method name matches the required operation. |
| `defineShardKey()` | `public static function defineShardKey(string $database, string $table, string $column, string $strategy = "hash")` | See class section; use when the method name matches the required operation. |
| `shardForValue()` | `public static function shardForValue(mixed $value, string $strategy = "hash")` | See class section; use when the method name matches the required operation. |
| `shardRouter()` | `public static function shardRouter(string $database, string $table, array $row)` | See class section; use when the method name matches the required operation. |
| `prepareUserSharding()` | `public static function prepareUserSharding(string $database, string $table, string $column = "user_id")` | Authentication/user workflows. |
| `prepareTenantSharding()` | `public static function prepareTenantSharding(string $database, string $table, string $column = "tenant_id")` | See class section; use when the method name matches the required operation. |
| `prepareHashSharding()` | `public static function prepareHashSharding(string $database, string $table, string $column = "id")` | See class section; use when the method name matches the required operation. |
| `shardAwareId()` | `public static function shardAwareId(string $database, string $table, array $row = [])` | See class section; use when the method name matches the required operation. |
| `crossShardQueryRules()` | `public static function crossShardQueryRules(array $rules = [])` | See class section; use when the method name matches the required operation. |
| `prepareShardRebalancing()` | `public static function prepareShardRebalancing()` | See class section; use when the method name matches the required operation. |
| `prepareShardMigration()` | `public static function prepareShardMigration(string $fromShard, string $toShard, array $options = [])` | See class section; use when the method name matches the required operation. |
| `shardHealth()` | `public static function shardHealth()` | Operations, diagnostics, backups or maintenance. |
| `prepareCrossShardTransactions()` | `public static function prepareCrossShardTransactions()` | See class section; use when the method name matches the required operation. |
| `prepareShardAwareBackups()` | `public static function prepareShardAwareBackups()` | Operations, diagnostics, backups or maintenance. |
| `prepareShardMonitoring()` | `public static function prepareShardMonitoring()` | See class section; use when the method name matches the required operation. |
| `definePrimaryReplicaModel()` | `public static function definePrimaryReplicaModel(array $config = [])` | See class section; use when the method name matches the required operation. |
| `replicationLog()` | `public static function replicationLog()` | See class section; use when the method name matches the required operation. |
| `replicateAsync()` | `public static function replicateAsync()` | See class section; use when the method name matches the required operation. |
| `prepareReadReplica()` | `public static function prepareReadReplica(string $name, array $config = [])` | See class section; use when the method name matches the required operation. |
| `replicaCatchup()` | `public static function replicaCatchup(string $replica)` | See class section; use when the method name matches the required operation. |
| `replicaLag()` | `public static function replicaLag()` | See class section; use when the method name matches the required operation. |
| `replicaHealth()` | `public static function replicaHealth()` | Operations, diagnostics, backups or maintenance. |
| `prepareReplicaRepair()` | `public static function prepareReplicaRepair(string $replica = "")` | Operations, diagnostics, backups or maintenance. |
| `prepareFailover()` | `public static function prepareFailover(string $replica = "")` | See class section; use when the method name matches the required operation. |
| `promoteReplicaToPrimary()` | `public static function promoteReplicaToPrimary(string $replica)` | See class section; use when the method name matches the required operation. |
| `enableSplitBrainProtection()` | `public static function enableSplitBrainProtection(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareReplicationSlots()` | `public static function prepareReplicationSlots(array $replicas = [])` | See class section; use when the method name matches the required operation. |
| `prepareShardReplication()` | `public static function prepareShardReplication(string $shard = "")` | See class section; use when the method name matches the required operation. |
| `prepareTenantReplication()` | `public static function prepareTenantReplication(string $tenant = "")` | See class section; use when the method name matches the required operation. |
| `registerClusterNode()` | `public static function registerClusterNode(string $node, array $config = [])` | See class section; use when the method name matches the required operation. |
| `clusterNodes()` | `public static function clusterNodes()` | See class section; use when the method name matches the required operation. |
| `heartbeatNode()` | `public static function heartbeatNode(string $node = "")` | See class section; use when the method name matches the required operation. |
| `prepareLeaderElection()` | `public static function prepareLeaderElection()` | See class section; use when the method name matches the required operation. |
| `defineQuorum()` | `public static function defineQuorum(int $minNodes = 1)` | See class section; use when the method name matches the required operation. |
| `clusterHealth()` | `public static function clusterHealth()` | Operations, diagnostics, backups or maintenance. |
| `defineNodeRoles()` | `public static function defineNodeRoles(array $roles = [])` | See class section; use when the method name matches the required operation. |
| `prepareServiceDiscovery()` | `public static function prepareServiceDiscovery(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareDistributedLocks()` | `public static function prepareDistributedLocks()` | See class section; use when the method name matches the required operation. |
| `setPrimaryNode()` | `public static function setPrimaryNode(string $node)` | Write/change storage or configuration; validate input and permissions first. |
| `setReplicaNode()` | `public static function setReplicaNode(string $node)` | Write/change storage or configuration; validate input and permissions first. |
| `setWorkerNode()` | `public static function setWorkerNode(string $node)` | Write/change storage or configuration; validate input and permissions first. |
| `setMaintenanceNode()` | `public static function setMaintenanceNode(string $node)` | Write/change storage or configuration; validate input and permissions first. |
| `clusterConfig()` | `public static function clusterConfig(array $config = [])` | See class section; use when the method name matches the required operation. |
| `detectSplitBrain()` | `public static function detectSplitBrain()` | See class section; use when the method name matches the required operation. |
| `prepareClusterRecovery()` | `public static function prepareClusterRecovery()` | See class section; use when the method name matches the required operation. |
| `backupLog()` | `public static function backupLog(string $action, array $payload = [])` | Operations, diagnostics, backups or maintenance. |
| `fullBackup()` | `public static function fullBackup(string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `prepareIncrementalBackup()` | `public static function prepareIncrementalBackup()` | Operations, diagnostics, backups or maintenance. |
| `prepareDifferentialBackup()` | `public static function prepareDifferentialBackup()` | Operations, diagnostics, backups or maintenance. |
| `snapshotBackup()` | `public static function snapshotBackup(string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `prepareHotBackup()` | `public static function prepareHotBackup()` | Operations, diagnostics, backups or maintenance. |
| `coldBackup()` | `public static function coldBackup(string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `verifyBackup()` | `public static function verifyBackup(string $path)` | Operations, diagnostics, backups or maintenance. |
| `restoreTest()` | `public static function restoreTest(string $backupPath)` | See class section; use when the method name matches the required operation. |
| `preparePointInTimeRecovery()` | `public static function preparePointInTimeRecovery(?int $timestamp = null)` | See class section; use when the method name matches the required operation. |
| `encryptedBackup()` | `public static function encryptedBackup(string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `prepareRemoteBackups()` | `public static function prepareRemoteBackups(array $config = [])` | Operations, diagnostics, backups or maintenance. |
| `prepareOffsiteBackups()` | `public static function prepareOffsiteBackups(array $config = [])` | Operations, diagnostics, backups or maintenance. |
| `rotateBackups()` | `public static function rotateBackups(int $keep = 10)` | Operations, diagnostics, backups or maintenance. |
| `applyBackupRetention()` | `public static function applyBackupRetention(int $days = 30)` | Operations, diagnostics, backups or maintenance. |
| `tenantBackup()` | `public static function tenantBackup(string $tenant, string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `instanceBackup()` | `public static function instanceBackup(string $instance, string $target = "")` | Operations, diagnostics, backups or maintenance. |
| `tableBackup()` | `public static function tableBackup(string $database, string $table, string $target = "")` | Operations, diagnostics, backups or maintenance. |

### `GBDB_CrudTrait`

File: `GBDB_GQL/db_engine/gbdb_crud.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `createInstance()` | `public static function createInstance(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteInstance()` | `public static function deleteInstance(string $name, bool $force = false)` | Write/change storage or configuration; validate input and permissions first. |
| `listInstances()` | `public static function listInstances(bool $includeSystem = false)` | Read/list data without changing storage. |
| `listAllInstances()` | `public static function listAllInstances()` | See class section; use when the method name matches the required operation. |
| `createDatabase()` | `public static function createDatabase(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteDatabase()` | `public static function deleteDatabase(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `createTable()` | `public static function createTable(string $database, string $table, array $cols)` | Write/change storage or configuration; validate input and permissions first. |
| `addColumn()` | `public static function addColumn(string $database, string $table, string $column, mixed $default = "")` | Write/change storage or configuration; validate input and permissions first. |
| `deleteTable()` | `public static function deleteTable(string $database, string $table)` | Write/change storage or configuration; validate input and permissions first. |
| `insertData()` | `public static function insertData(string $database, string $table, mixed $data)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteData()` | `public static function deleteData(string $database, string $table, mixed $where, mixed $is)` | Write/change storage or configuration; validate input and permissions first. |
| `editData()` | `public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData)` | Write/change storage or configuration; validate input and permissions first. |
| `getData()` | `public static function getData(string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = "")` | Read/list data without changing storage. |
| `elementExists()` | `public static function elementExists(string $database, string $table, mixed $where, mixed $is)` | See class section; use when the method name matches the required operation. |
| `listDBs()` | `public static function listDBs()` | Read/list data without changing storage. |
| `listTables()` | `public static function listTables(string $database, bool $descending = false)` | Read/list data without changing storage. |
| `deleteAll()` | `public static function deleteAll(string $database)` | Write/change storage or configuration; validate input and permissions first. |
| `nextID()` | `public static function nextID(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `getKeys()` | `public static function getKeys(string $database, string $table)` | Read/list data without changing storage. |
| `query()` | `public static function query(string $script, array $ctx = [], array $params = [])` | See class section; use when the method name matches the required operation. |
| `runScript()` | `public static function runScript(string $scriptName, array $params = [], array $ctx = [])` | See class section; use when the method name matches the required operation. |

### `GBDB_EnterpriseOpsTrait`

File: `GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `patternTemplate()` | `public static function patternTemplate(string $name = 'intranet')` | See class section; use when the method name matches the required operation. |
| `listPatterns()` | `public static function listPatterns(bool $includeInactive = true)` | See class section; use when the method name matches the required operation. |
| `getPattern()` | `public static function getPattern(string $name)` | Read/list data without changing storage. |
| `savePattern()` | `public static function savePattern(array $pattern)` | See class section; use when the method name matches the required operation. |
| `deletePattern()` | `public static function deletePattern(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `renamePattern()` | `public static function renamePattern(string $name, string $newName)` | Write/change storage or configuration; validate input and permissions first. |
| `setPatternActive()` | `public static function setPatternActive(string $name, bool $active)` | Write/change storage or configuration; validate input and permissions first. |
| `installPattern()` | `public static function installPattern(string $patternName, string $instanceName, bool $allowInactive = false)` | See class section; use when the method name matches the required operation. |
| `installIntranetPattern()` | `public static function installIntranetPattern(string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `intranetAudit()` | `public static function intranetAudit(string $action, array $payload = [], string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `intranetDirectorySearch()` | `public static function intranetDirectorySearch(string $query, string $database = 'intranet', int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `defineDocumentPermission()` | `public static function defineDocumentPermission(string $docId, string $subjectType, string $subjectId, string $permission, string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `defineRetentionRule()` | `public static function defineRetentionRule(string $name, int $days, string $action = 'archive', string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `prepareSsoAdapter()` | `public static function prepareSsoAdapter(string $type, array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareLdapAdapter()` | `public static function prepareLdapAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareSamlAdapter()` | `public static function prepareSamlAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareOidcAdapter()` | `public static function prepareOidcAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareOAuth2Adapter()` | `public static function prepareOAuth2Adapter(array $config = [])` | Authentication/user workflows. |
| `externalUserSync()` | `public static function externalUserSync(array $users, string $database = 'intranet')` | Authentication/user workflows. |
| `groupSync()` | `public static function groupSync(array $groups, string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `departmentSync()` | `public static function departmentSync(array $departments, string $database = 'intranet')` | See class section; use when the method name matches the required operation. |
| `roleMapping()` | `public static function roleMapping(array $mapping)` | See class section; use when the method name matches the required operation. |
| `ssoAudit()` | `public static function ssoAudit(string $provider, string $subject, string $action, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `createServiceAccount()` | `public static function createServiceAccount(string $name, array $scopes = [], array $options = [])` | Write/change storage or configuration; validate input and permissions first. |
| `createApiToken()` | `public static function createApiToken(string $subject, array $scopes = [], int $ttlSeconds = 2592000)` | Write/change storage or configuration; validate input and permissions first. |
| `validateApiToken()` | `public static function validateApiToken(string $token, string $scope = '')` | See class section; use when the method name matches the required operation. |
| `rotateApiToken()` | `public static function rotateApiToken(string $tokenId, int $ttlSeconds = 2592000)` | See class section; use when the method name matches the required operation. |
| `registerSession()` | `public static function registerSession(string $uid, array $device = [], int $ttlSeconds = 172800)` | See class section; use when the method name matches the required operation. |
| `forceLogout()` | `public static function forceLogout(string $uid)` | See class section; use when the method name matches the required operation. |
| `sessionList()` | `public static function sessionList(string $uid = '')` | See class section; use when the method name matches the required operation. |
| `tenantPolicy()` | `public static function tenantPolicy(string $tenant, array $policy = [])` | See class section; use when the method name matches the required operation. |
| `tenantQuotas()` | `public static function tenantQuotas(string $tenant, array $quotas = [])` | See class section; use when the method name matches the required operation. |
| `tenantRestore()` | `public static function tenantRestore(string $tenant, string $backupPath)` | See class section; use when the method name matches the required operation. |
| `tenantExport()` | `public static function tenantExport(string $tenant, string $target = '')` | See class section; use when the method name matches the required operation. |
| `tenantDelete()` | `public static function tenantDelete(string $tenant, bool $force = false)` | See class section; use when the method name matches the required operation. |
| `prepareTenantMigration()` | `public static function prepareTenantMigration(string $tenant, string $targetShard, array $options = [])` | See class section; use when the method name matches the required operation. |
| `tenantStorageStats()` | `public static function tenantStorageStats(string $tenant = '')` | Operations, diagnostics, backups or maintenance. |
| `tenantAdminRoles()` | `public static function tenantAdminRoles(string $tenant, array $roles)` | See class section; use when the method name matches the required operation. |
| `tenantEncryptionKeysPrepared()` | `public static function tenantEncryptionKeysPrepared(string $tenant, array $options = [])` | See class section; use when the method name matches the required operation. |
| `quotaLimits()` | `public static function quotaLimits(array $limits = [])` | See class section; use when the method name matches the required operation. |
| `quotaCheck()` | `public static function quotaCheck(string $database = '', string $table = '')` | See class section; use when the method name matches the required operation. |
| `quotaDashboard()` | `public static function quotaDashboard(bool $includeTenants = false)` | See class section; use when the method name matches the required operation. |
| `rateLimit()` | `public static function rateLimit(string $bucket, string $key, int $limit, int $windowSeconds = 60)` | See class section; use when the method name matches the required operation. |
| `configureRateLimits()` | `public static function configureRateLimits(array $limits)` | See class section; use when the method name matches the required operation. |
| `rateLimitLogs()` | `public static function rateLimitLogs(int $limit = 100)` | See class section; use when the method name matches the required operation. |
| `exportRows()` | `public static function exportRows(string $database, string $table, string $format = 'json', string $target = '')` | See class section; use when the method name matches the required operation. |
| `importRows()` | `public static function importRows(string $database, string $table, string $file, string $format = 'json', bool $rollback = true)` | See class section; use when the method name matches the required operation. |
| `validateImportRows()` | `public static function validateImportRows(string $database, string $table, array $rows)` | See class section; use when the method name matches the required operation. |
| `gbdbDump()` | `public static function gbdbDump(string $target = '')` | See class section; use when the method name matches the required operation. |
| `gbdbRestore()` | `public static function gbdbRestore(string $backupPath)` | See class section; use when the method name matches the required operation. |
| `streamingImport()` | `public static function streamingImport(string $database, string $table, string $file, string $format = 'ndjson', int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `streamingExport()` | `public static function streamingExport(string $database, string $table, string $format = 'ndjson', string $target = '')` | See class section; use when the method name matches the required operation. |
| `exportPermissions()` | `public static function exportPermissions(array $rules = [])` | See class section; use when the method name matches the required operation. |
| `exportLogs()` | `public static function exportLogs(int $limit = 100)` | See class section; use when the method name matches the required operation. |
| `sqlErsatzFeatureMatrix()` | `public static function sqlErsatzFeatureMatrix()` | See class section; use when the method name matches the required operation. |
| `sqlErsatzFeatureStatus()` | `public static function sqlErsatzFeatureStatus(string $feature)` | See class section; use when the method name matches the required operation. |
| `prepareSqlAdapter()` | `public static function prepareSqlAdapter(string $type, array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareMySQLImportAdapter()` | `public static function prepareMySQLImportAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `preparePostgreSQLImportAdapter()` | `public static function preparePostgreSQLImportAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareSQLiteImportAdapter()` | `public static function prepareSQLiteImportAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareSqlCompatibilityLayer()` | `public static function prepareSqlCompatibilityLayer(array $config = [])` | See class section; use when the method name matches the required operation. |
| `prepareSqlToGreenQLTranslator()` | `public static function prepareSqlToGreenQLTranslator(array $config = [])` | See class section; use when the method name matches the required operation. |
| `preparePdoLikeAdapter()` | `public static function preparePdoLikeAdapter(array $config = [])` | See class section; use when the method name matches the required operation. |
| `adminUiData()` | `public static function adminUiData()` | See class section; use when the method name matches the required operation. |
| `adminUiQueryPlan()` | `public static function adminUiQueryPlan(string $greenql)` | See class section; use when the method name matches the required operation. |
| `cliCommand()` | `public static function cliCommand(string $command, array $args = [])` | See class section; use when the method name matches the required operation. |
| `transactionApi()` | `public static function transactionApi(callable $callback)` | See class section; use when the method name matches the required operation. |
| `cursorApi()` | `public static function cursorApi(string $database, string $table, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `indexApi()` | `public static function indexApi(string $database, string $table, string $column)` | See class section; use when the method name matches the required operation. |
| `schemaApi()` | `public static function schemaApi(string $database, string $table, array $columns)` | See class section; use when the method name matches the required operation. |
| `migrationApi()` | `public static function migrationApi(string $database, string $table, string $id, callable $cb)` | See class section; use when the method name matches the required operation. |
| `backupApi()` | `public static function backupApi(string $type = 'full', array $args = [])` | Operations, diagnostics, backups or maintenance. |
| `repairApi()` | `public static function repairApi(string $database = '', string $table = '')` | Operations, diagnostics, backups or maintenance. |
| `explainApi()` | `public static function explainApi(string $database, string $table, array $where = [])` | See class section; use when the method name matches the required operation. |
| `statsApi()` | `public static function statsApi()` | Operations, diagnostics, backups or maintenance. |
| `policyApi()` | `public static function policyApi(string $name, array $rules = [])` | See class section; use when the method name matches the required operation. |
| `eventApi()` | `public static function eventApi(string $event, string $db, string $table, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `queueApi()` | `public static function queueApi(string $action, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `mediaApi()` | `public static function mediaApi(array $payload = [])` | See class section; use when the method name matches the required operation. |
| `searchApi()` | `public static function searchApi(string $database, string $table, string $query, array $columns = [])` | See class section; use when the method name matches the required operation. |
| `cacheApi()` | `public static function cacheApi(string $action, string $key = '', mixed $value = null)` | Speed up repeated reads or invalidate cached values after writes. |
| `documentationIndex()` | `public static function documentationIndex()` | See class section; use when the method name matches the required operation. |
| `enterpriseSelfTest()` | `public static function enterpriseSelfTest()` | See class section; use when the method name matches the required operation. |

### `GBDB_IndexTrait`

File: `GBDB_GQL/db_engine/gbdb_index.trait.php`

No public methods detected.

### `GBDB_InstanceSchemaTrait`

File: `GBDB_GQL/db_engine/gbdb_instance_schema.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `setInstance()` | `public static function setInstance(string $instance)` | Write/change storage or configuration; validate input and permissions first. |
| `instance()` | `public static function instance(string $instance)` | See class section; use when the method name matches the required operation. |
| `getInstance()` | `public static function getInstance()` | Read/list data without changing storage. |
| `withInstance()` | `public static function withInstance(string $instance, callable $callback)` | See class section; use when the method name matches the required operation. |
| `schemaTypes()` | `public static function schemaTypes()` | See class section; use when the method name matches the required operation. |
| `schemaTable()` | `public static function schemaTable(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `enableSchemaTypes()` | `public static function enableSchemaTypes(string $database, string $table, bool $enabled = true)` | See class section; use when the method name matches the required operation. |
| `setColumnType()` | `public static function setColumnType(string $database, string $table, string $column, string $type, array $options = [])` | Write/change storage or configuration; validate input and permissions first. |
| `setColumnDefault()` | `public static function setColumnDefault(string $database, string $table, string $column, mixed $default)` | Write/change storage or configuration; validate input and permissions first. |
| `setSchemaConstraint()` | `public static function setSchemaConstraint(string $database, string $table, string $column, string $constraint, mixed $value = true)` | Write/change storage or configuration; validate input and permissions first. |
| `createSequence()` | `public static function createSequence(string $database, string $table, string $name, int $start = 0, int $step = 1)` | Write/change storage or configuration; validate input and permissions first. |
| `nextSequence()` | `public static function nextSequence(string $database, string $table, string $name)` | See class section; use when the method name matches the required operation. |
| `defineMigration()` | `public static function defineMigration(string $database, string $table, string $name, array $operations)` | See class section; use when the method name matches the required operation. |
| `runMigration()` | `public static function runMigration(string $database, string $table, string $name)` | See class section; use when the method name matches the required operation. |
| `rollbackMigration()` | `public static function rollbackMigration(string $database, string $table, string $name)` | See class section; use when the method name matches the required operation. |
| `checkSchema()` | `public static function checkSchema(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `repairSchema()` | `public static function repairSchema(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `typedCompare()` | `public static function typedCompare(string $database, string $table, string $column, mixed $left, string $op, mixed $right)` | See class section; use when the method name matches the required operation. |
| `typedSortRows()` | `public static function typedSortRows(string $database, string $table, array &$rows, string $column, string $dir = "ASC")` | See class section; use when the method name matches the required operation. |
| `migrationHistory()` | `public static function migrationHistory(string $database, string $table)` | See class section; use when the method name matches the required operation. |

### `GBDB_JobsSecurityMediaSocialTrait`

File: `GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `enqueueJob()` | `public static function enqueueJob(string $type, array $payload = [], array $options = [])` | See class section; use when the method name matches the required operation. |
| `delayedJob()` | `public static function delayedJob(string $type, array $payload, int $delaySeconds, array $options = [])` | See class section; use when the method name matches the required operation. |
| `priorityJob()` | `public static function priorityJob(string $type, array $payload, int $priority = 10, array $options = [])` | See class section; use when the method name matches the required operation. |
| `scheduleJob()` | `public static function scheduleJob(string $name, string $type, array $payload, string $cronOrInterval, array $options = [])` | See class section; use when the method name matches the required operation. |
| `recurringJob()` | `public static function recurringJob(string $name, string $type, array $payload, int $everySeconds, array $options = [])` | See class section; use when the method name matches the required operation. |
| `claimJob()` | `public static function claimJob(string $workerId, string $queue = 'default')` | See class section; use when the method name matches the required operation. |
| `workerHeartbeat()` | `public static function workerHeartbeat(string $jobId, string $workerId)` | See class section; use when the method name matches the required operation. |
| `processDueJobs()` | `public static function processDueJobs(int $limit = 25, string $queue = '')` | See class section; use when the method name matches the required operation. |
| `workQueue()` | `public static function workQueue(int $limit = 25, string $queue = '')` | See class section; use when the method name matches the required operation. |
| `finishJob()` | `public static function finishJob(string $jobId, bool $ok = true, array $result = [])` | See class section; use when the method name matches the required operation. |
| `retryFailedJobs()` | `public static function retryFailedJobs(int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `queueStats()` | `public static function queueStats()` | Operations, diagnostics, backups or maintenance. |
| `jobDashboard()` | `public static function jobDashboard(int $limit = 50)` | See class section; use when the method name matches the required operation. |
| `queueUiPrepared()` | `public static function queueUiPrepared()` | See class section; use when the method name matches the required operation. |
| `queueCliPrepared()` | `public static function queueCliPrepared()` | See class section; use when the method name matches the required operation. |
| `srvQueueBridge()` | `public static function srvQueueBridge(string $action, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `backgroundIndexUpdate()` | `public static function backgroundIndexUpdate(string $db, string $table)` | See class section; use when the method name matches the required operation. |
| `backgroundFeedFanout()` | `public static function backgroundFeedFanout(array $payload)` | See class section; use when the method name matches the required operation. |
| `backgroundMailSending()` | `public static function backgroundMailSending(array $payload)` | See class section; use when the method name matches the required operation. |
| `backgroundMediaProcessing()` | `public static function backgroundMediaProcessing(array $payload)` | See class section; use when the method name matches the required operation. |
| `backgroundBackups()` | `public static function backgroundBackups(array $payload = [])` | Operations, diagnostics, backups or maintenance. |
| `defineTrigger()` | `public static function defineTrigger(string $db, string $table, string $event, string $name, array $definition)` | See class section; use when the method name matches the required operation. |
| `runDataTriggers()` | `public static function runDataTriggers(string $event, string $db, string $table, array $context = [])` | See class section; use when the method name matches the required operation. |
| `enqueueEvent()` | `public static function enqueueEvent(string $event, string $db, string $table, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `eventLog()` | `public static function eventLog(string $event, string $db, string $table, array $payload = [])` | See class section; use when the method name matches the required operation. |
| `triggerDebug()` | `public static function triggerDebug(string $db, string $table)` | See class section; use when the method name matches the required operation. |
| `schemaDefinedTriggers()` | `public static function schemaDefinedTriggers(string $db, string $table)` | See class section; use when the method name matches the required operation. |
| `greenqlTriggerFunction()` | `public static function greenqlTriggerFunction(string $name, string $script)` | See class section; use when the method name matches the required operation. |
| `defineView()` | `public static function defineView(string $name, string $query, array $options = [])` | See class section; use when the method name matches the required operation. |
| `refreshView()` | `public static function refreshView(string $name)` | See class section; use when the method name matches the required operation. |
| `getView()` | `public static function getView(string $name, bool $refresh = false)` | Read/list data without changing storage. |
| `viewQueryPlanner()` | `public static function viewQueryPlanner(string $name)` | See class section; use when the method name matches the required operation. |
| `materializedViewIndex()` | `public static function materializedViewIndex(string $name, array $columns)` | See class section; use when the method name matches the required operation. |
| `defineProcedure()` | `public static function defineProcedure(string $name, string $script, array $options = [])` | See class section; use when the method name matches the required operation. |
| `callProcedure()` | `public static function callProcedure(string $name, array $params = [])` | See class section; use when the method name matches the required operation. |
| `procedureLogs()` | `public static function procedureLogs(string $name = '')` | See class section; use when the method name matches the required operation. |
| `procedureDebug()` | `public static function procedureDebug(string $name)` | See class section; use when the method name matches the required operation. |
| `defineDbRole()` | `public static function defineDbRole(string $role, array $permissions = [])` | See class section; use when the method name matches the required operation. |
| `defineDbUser()` | `public static function defineDbUser(string $user, array $roles = ['readonly'], array $options = [])` | Authentication/user workflows. |
| `defineApiKeyScope()` | `public static function defineApiKeyScope(string $keyId, array $scopes)` | See class section; use when the method name matches the required operation. |
| `defaultDbRoles()` | `public static function defaultDbRoles()` | See class section; use when the method name matches the required operation. |
| `checkPermission()` | `public static function checkPermission(string $user, string $action, string $db = '', string $table = '', array $row = [])` | See class section; use when the method name matches the required operation. |
| `permissionAudit()` | `public static function permissionAudit(string $user, string $action, array $context = [])` | See class section; use when the method name matches the required operation. |
| `permissionDebug()` | `public static function permissionDebug(string $user)` | See class section; use when the method name matches the required operation. |
| `definePolicy()` | `public static function definePolicy(string $name, array $rules)` | See class section; use when the method name matches the required operation. |
| `evaluatePolicy()` | `public static function evaluatePolicy(string $action, array $row, array $context = [])` | See class section; use when the method name matches the required operation. |
| `ownerUidPolicy()` | `public static function ownerUidPolicy(string $name = 'owner_uid')` | See class section; use when the method name matches the required operation. |
| `visibilityPolicy()` | `public static function visibilityPolicy()` | See class section; use when the method name matches the required operation. |
| `tenantIsolationPolicy()` | `public static function tenantIsolationPolicy(string $tenantField = 'tenant_id')` | See class section; use when the method name matches the required operation. |
| `policyDebugOutput()` | `public static function policyDebugOutput()` | See class section; use when the method name matches the required operation. |
| `auditDataChange()` | `public static function auditDataChange(string $action, string $db, string $table, array $old = [], array $new = [], array $context = [])` | See class section; use when the method name matches the required operation. |
| `auditExport()` | `public static function auditExport(array $filter = [])` | See class section; use when the method name matches the required operation. |
| `auditSearch()` | `public static function auditSearch(string $needle)` | See class section; use when the method name matches the required operation. |
| `auditRetention()` | `public static function auditRetention(int $days = 365)` | See class section; use when the method name matches the required operation. |
| `auditIntegrity()` | `public static function auditIntegrity()` | See class section; use when the method name matches the required operation. |
| `markPiiField()` | `public static function markPiiField(string $db, string $table, string $field, string $classification = 'pii')` | See class section; use when the method name matches the required operation. |
| `userDataExport()` | `public static function userDataExport(string $userField, mixed $userId, array $tables = [])` | Authentication/user workflows. |
| `userDataDelete()` | `public static function userDataDelete(string $userField, mixed $userId, array $tables = [])` | Authentication/user workflows. |
| `userDataRedact()` | `public static function userDataRedact(string $db, string $table, string $where, mixed $is, array $fields, string $replacement = '[redacted]')` | Authentication/user workflows. |
| `retentionPolicy()` | `public static function retentionPolicy(string $name, array $rule)` | See class section; use when the method name matches the required operation. |
| `consentHistory()` | `public static function consentHistory(string $subject, array $entry)` | See class section; use when the method name matches the required operation. |
| `encryptionConfig()` | `public static function encryptionConfig(array $cfg = [])` | See class section; use when the method name matches the required operation. |
| `deriveKey()` | `public static function deriveKey(string $secret, string $salt = '', int $version = 1)` | See class section; use when the method name matches the required operation. |
| `rotateKey()` | `public static function rotateKey(string $name = 'default')` | See class section; use when the method name matches the required operation. |
| `scrubSecrets()` | `public static function scrubSecrets(mixed $value)` | See class section; use when the method name matches the required operation. |
| `securityTests()` | `public static function securityTests()` | See class section; use when the method name matches the required operation. |
| `blobPath()` | `public static function blobPath(string $hash = '', bool $ensure = true)` | See class section; use when the method name matches the required operation. |
| `putBlob()` | `public static function putBlob(string $sourcePath, string $visibility = 'private', array $meta = [])` | See class section; use when the method name matches the required operation. |
| `signedUrl()` | `public static function signedUrl(string $hash, int $ttl = 300)` | See class section; use when the method name matches the required operation. |
| `temporaryUrl()` | `public static function temporaryUrl(string $hash, int $ttl = 300)` | See class section; use when the method name matches the required operation. |
| `validateMime()` | `public static function validateMime(string $mime, array $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])` | See class section; use when the method name matches the required operation. |
| `deleteMedia()` | `public static function deleteMedia(string $hash)` | Write/change storage or configuration; validate input and permissions first. |
| `orphanMediaCleanup()` | `public static function orphanMediaCleanup()` | See class section; use when the method name matches the required operation. |
| `mediaPermissions()` | `public static function mediaPermissions(string $hash, array $permissions)` | See class section; use when the method name matches the required operation. |
| `mediaAudit()` | `public static function mediaAudit(string $hash, string $action, array $context = [])` | See class section; use when the method name matches the required operation. |
| `mediaProcessingPrepared()` | `public static function mediaProcessingPrepared(string $hash)` | See class section; use when the method name matches the required operation. |
| `socialSchemaPatterns()` | `public static function socialSchemaPatterns()` | See class section; use when the method name matches the required operation. |
| `installSocialPatterns()` | `public static function installSocialPatterns(string $db = 'social')` | See class section; use when the method name matches the required operation. |
| `softDelete()` | `public static function softDelete(string $db, string $table, string $where, mixed $is)` | See class section; use when the method name matches the required operation. |
| `contentVisibilityRules()` | `public static function contentVisibilityRules(array $rules = [])` | See class section; use when the method name matches the required operation. |
| `feedFanoutJob()` | `public static function feedFanoutJob(string $postId, array $targets)` | See class section; use when the method name matches the required operation. |
| `fanoutOnRead()` | `public static function fanoutOnRead(array $context)` | See class section; use when the method name matches the required operation. |
| `hybridFeed()` | `public static function hybridFeed(array $context)` | See class section; use when the method name matches the required operation. |
| `moderationQueue()` | `public static function moderationQueue(string $objectType, string $objectId, string $reason, array $context = [])` | See class section; use when the method name matches the required operation. |

### `GBDB_LockingTrait`

File: `GBDB_GQL/db_engine/gbdb_locking.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `lockTimeout()` | `public static function lockTimeout(int $milliseconds)` | See class section; use when the method name matches the required operation. |
| `cleanupLocks()` | `public static function cleanupLocks(int $olderThanSeconds = 0)` | See class section; use when the method name matches the required operation. |
| `acquireLock()` | `public static function acquireLock(string $resource, string $type = 'write', int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `releaseLock()` | `public static function releaseLock(string $lockId)` | See class section; use when the method name matches the required operation. |
| `lockTable()` | `public static function lockTable(string $database, string $table, string $type = 'write', int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `lockChunk()` | `public static function lockChunk(string $database, string $table, string|int $chunk, string $type = 'write', int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `lockRow()` | `public static function lockRow(string $database, string $table, int $rowId, string $type = 'write', int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `lockPage()` | `public static function lockPage(string $database, string $table, string|int $page, string $type = 'write', int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `lockMonitor()` | `public static function lockMonitor()` | See class section; use when the method name matches the required operation. |
| `lockStats()` | `public static function lockStats()` | Operations, diagnostics, backups or maintenance. |
| `detectDeadlocks()` | `public static function detectDeadlocks(int $olderThanSeconds = 15)` | See class section; use when the method name matches the required operation. |
| `resolveDeadlocks()` | `public static function resolveDeadlocks(int $olderThanSeconds = 15)` | See class section; use when the method name matches the required operation. |
| `retryOnLockCollision()` | `public static function retryOnLockCollision(callable $fn, int $tries = 3, int $sleepMs = 50)` | See class section; use when the method name matches the required operation. |
| `maybeEscalateLock()` | `public static function maybeEscalateLock(string $database, string $table, int $threshold = 32)` | See class section; use when the method name matches the required operation. |

### `GBDB_MaintenanceTrait`

File: `GBDB_GQL/db_engine/gbdb_maintenance.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `compactTable()` | `public static function compactTable(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `snapshot()` | `public static function snapshot(string $database, string $table, string $reason = "manual")` | See class section; use when the method name matches the required operation. |
| `createIndex()` | `public static function createIndex(string $database, string $table, string|array $column, string $type = 'single', array $options = [])` | Write/change storage or configuration; validate input and permissions first. |
| `createPrimaryIndex()` | `public static function createPrimaryIndex(string $database, string $table, string $column = 'id')` | Write/change storage or configuration; validate input and permissions first. |
| `createUniqueIndex()` | `public static function createUniqueIndex(string $database, string $table, string|array $columns)` | Write/change storage or configuration; validate input and permissions first. |
| `createCompositeIndex()` | `public static function createCompositeIndex(string $database, string $table, array $columns)` | Write/change storage or configuration; validate input and permissions first. |
| `createSortedIndex()` | `public static function createSortedIndex(string $database, string $table, string|array $columns)` | Write/change storage or configuration; validate input and permissions first. |
| `createRangeIndex()` | `public static function createRangeIndex(string $database, string $table, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `createPrefixIndex()` | `public static function createPrefixIndex(string $database, string $table, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `createFulltextIndex()` | `public static function createFulltextIndex(string $database, string $table, array $columns = [])` | Write/change storage or configuration; validate input and permissions first. |
| `dropIndex()` | `public static function dropIndex(string $database, string $table, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `listIndexes()` | `public static function listIndexes(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `rebuildIndexes()` | `public static function rebuildIndexes(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `repairIndexes()` | `public static function repairIndexes(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `verifyIndexes()` | `public static function verifyIndexes(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `health()` | `public static function health(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `repairTable()` | `public static function repairTable(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `meta()` | `public static function meta(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `addConstraint()` | `public static function addConstraint(string $database, string $table, string $column, string $type)` | Write/change storage or configuration; validate input and permissions first. |
| `dropConstraint()` | `public static function dropConstraint(string $database, string $table, string $column, string $type)` | Write/change storage or configuration; validate input and permissions first. |
| `listConstraints()` | `public static function listConstraints(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `vacuum()` | `public static function vacuum(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `restoreSnapshot()` | `public static function restoreSnapshot(string $database, string $table, string $snapshotId)` | See class section; use when the method name matches the required operation. |

### `GBDB_MVCCTrait`

File: `GBDB_GQL/db_engine/gbdb_mvcc.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `isolationLevel()` | `public static function isolationLevel(string $level)` | See class section; use when the method name matches the required operation. |
| `beginSnapshot()` | `public static function beginSnapshot()` | See class section; use when the method name matches the required operation. |
| `endSnapshot()` | `public static function endSnapshot(string $snapshotId)` | See class section; use when the method name matches the required operation. |
| `transactionSnapshots()` | `public static function transactionSnapshots()` | See class section; use when the method name matches the required operation. |
| `rowVersions()` | `public static function rowVersions(string $database, string $table, int $rowId)` | See class section; use when the method name matches the required operation. |
| `getDataSnapshot()` | `public static function getDataSnapshot(string $database, string $table, ?string $snapshotId = null, bool $filter = false, mixed $where = '', mixed $is = '')` | Read/list data without changing storage. |
| `optimisticEditData()` | `public static function optimisticEditData(string $database, string $table, mixed $where, mixed $is, array $newData, int $expectedVersion)` | See class section; use when the method name matches the required operation. |
| `pessimisticRowLock()` | `public static function pessimisticRowLock(string $database, string $table, int $rowId, int $timeoutMs = 0)` | See class section; use when the method name matches the required operation. |
| `cleanupRowVersions()` | `public static function cleanupRowVersions(string $database, string $table, int $keepSeconds = 3600)` | See class section; use when the method name matches the required operation. |
| `versionChains()` | `public static function versionChains(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `garbageCollectVersions()` | `public static function garbageCollectVersions(string $database, string $table, int $keepSeconds = 3600)` | See class section; use when the method name matches the required operation. |
| `mvccCleanup()` | `public static function mvccCleanup(int $keepSeconds = 3600)` | See class section; use when the method name matches the required operation. |
| `testConsistentReads()` | `public static function testConsistentReads()` | See class section; use when the method name matches the required operation. |
| `mvccLoadTest()` | `public static function mvccLoadTest(int $writes = 100)` | See class section; use when the method name matches the required operation. |
| `mvccStats()` | `public static function mvccStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |

### `GBDB_PublicV103Trait`

File: `GBDB_GQL/db_engine/gbdb_public_v103.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `getActualInstance()` | `public static function getActualInstance()` | Read/list data without changing storage. |
| `get()` | `public static function get(string $base, string $table, mixed $search = false, mixed $where = '', mixed $is = '', array $options = [])` | Read/list data without changing storage. |
| `existsInstance()` | `public static function existsInstance(string $instance)` | See class section; use when the method name matches the required operation. |
| `create()` | `public static function create(string $base, string $table = '', bool $useDataTypes = false, array|object $rows = [])` | Write/change storage or configuration; validate input and permissions first. |
| `exists()` | `public static function exists(string $base, ?string $table = null, mixed $where = '', mixed $is = '')` | See class section; use when the method name matches the required operation. |
| `edit()` | `public static function edit(string $base, string $table, mixed $where, mixed $is, array|object $newDataAsObject, bool $recursive = false)` | Write/change storage or configuration; validate input and permissions first. |
| `delete()` | `public static function delete(string $base, ?string $table = null, mixed $where = '', mixed $is = '', bool $recursive = false)` | Write/change storage or configuration; validate input and permissions first. |
| `getNextId()` | `public static function getNextId(string $base, string $table)` | Read/list data without changing storage. |
| `fullTextSearch()` | `public static function fullTextSearch(string $base, string $table, string $text)` | See class section; use when the method name matches the required operation. |
| `renameTable()` | `public static function renameTable(string $base, string $table, string $newTableName)` | Write/change storage or configuration; validate input and permissions first. |
| `renameBase()` | `public static function renameBase(string $base, string $newBaseName)` | Write/change storage or configuration; validate input and permissions first. |
| `renameInstance()` | `public static function renameInstance(string $instance, string $newInstanceName)` | Write/change storage or configuration; validate input and permissions first. |
| `moveBase()` | `public static function moveBase(string $base, string $fromInstance, string $toInstance)` | See class section; use when the method name matches the required operation. |
| `moveTable()` | `public static function moveTable(string $instance, string $base, string $table, string $toInstance, string $toBase)` | See class section; use when the method name matches the required operation. |
| `createBackup()` | `public static function createBackup(string $pathToBackupDir = '')` | Write/change storage or configuration; validate input and permissions first. |
| `runFile()` | `public static function runFile(string $pathToFileWithFilename, array|object $parametersAsObject = [])` | See class section; use when the method name matches the required operation. |

### `GBDB_QueryTrait`

File: `GBDB_GQL/db_engine/gbdb_query.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `queryOptions()` | `public static function queryOptions(array $options = [])` | See class section; use when the method name matches the required operation. |
| `prepareQuery()` | `public static function prepareQuery(string $name, string $script)` | See class section; use when the method name matches the required operation. |
| `executePreparedQuery()` | `public static function executePreparedQuery(string $name, array $params = [], array $ctx = [])` | See class section; use when the method name matches the required operation. |
| `bindQueryParams()` | `public static function bindQueryParams(string $script, array $params)` | See class section; use when the method name matches the required operation. |
| `clearQueryCache()` | `public static function clearQueryCache()` | Speed up repeated reads or invalidate cached values after writes. |
| `innerJoin()` | `public static function innerJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = [])` | See class section; use when the method name matches the required operation. |
| `leftJoin()` | `public static function leftJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = [])` | See class section; use when the method name matches the required operation. |
| `join()` | `public static function join(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, string $type = 'inner', array $options = [])` | See class section; use when the method name matches the required operation. |
| `joinPlan()` | `public static function joinPlan(string $leftTable, string $rightTable, int $leftRows, int $rightRows, string $leftKey, string $rightKey, string $type = 'inner')` | See class section; use when the method name matches the required operation. |
| `subquery()` | `public static function subquery(callable $query)` | See class section; use when the method name matches the required operation. |
| `streamQuery()` | `public static function streamQuery(string $database, string $table, callable $callback, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `allowFullScan()` | `public static function allowFullScan(string $database, string $table, string $where = '')` | See class section; use when the method name matches the required operation. |

### `GBDB_RecoveryTrait`

File: `GBDB_GQL/db_engine/gbdb_recovery.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `boot()` | `public static function boot()` | See class section; use when the method name matches the required operation. |
| `shutdown()` | `public static function shutdown()` | See class section; use when the method name matches the required operation. |
| `dirtyShutdownDetected()` | `public static function dirtyShutdownDetected()` | See class section; use when the method name matches the required operation. |
| `recover()` | `public static function recover(bool $auto = false)` | See class section; use when the method name matches the required operation. |
| `recoverTable()` | `public static function recoverTable(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `recoveryReport()` | `public static function recoveryReport()` | See class section; use when the method name matches the required operation. |

### `GBDB_RelationsIdsStreamingPartitionTrait`

File: `GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `addForeignKey()` | `public static function addForeignKey(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = "id", array $options = [])` | Write/change storage or configuration; validate input and permissions first. |
| `defineRelation()` | `public static function defineRelation(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = "id", array $options = [])` | See class section; use when the method name matches the required operation. |
| `dropRelation()` | `public static function dropRelation(string $database, string $table, string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `createRelationIndex()` | `public static function createRelationIndex(string $database, string $table, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `checkOrphans()` | `public static function checkOrphans(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `repairOrphans()` | `public static function repairOrphans(string $database, string $table, string $mode = "report")` | Operations, diagnostics, backups or maintenance. |
| `repairConstraints()` | `public static function repairConstraints(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `relationGraph()` | `public static function relationGraph(?string $database = null)` | See class section; use when the method name matches the required operation. |
| `relationDocs()` | `public static function relationDocs(?string $database = null)` | See class section; use when the method name matches the required operation. |
| `checkRelations()` | `public static function checkRelations(?string $database = null)` | See class section; use when the method name matches the required operation. |
| `uuid()` | `public static function uuid()` | See class section; use when the method name matches the required operation. |
| `ulid()` | `public static function ulid()` | See class section; use when the method name matches the required operation. |
| `snowflakeId()` | `public static function snowflakeId(int $node = 1)` | See class section; use when the method name matches the required operation. |
| `distributedId()` | `public static function distributedId(string $tenant = "", string $shard = "")` | See class section; use when the method name matches the required operation. |
| `idExists()` | `public static function idExists(string $database, string $table, string $column, mixed $id)` | See class section; use when the method name matches the required operation. |
| `safeId()` | `public static function safeId(string $database, string $table, string $column = "uid", string $type = "ulid", array $options = [])` | See class section; use when the method name matches the required operation. |
| `readGenerator()` | `public static function readGenerator(string $database, string $table, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `chunkedRows()` | `public static function chunkedRows(string $database, string $table, int $chunkSize = 500, ?callable $filter = null)` | See class section; use when the method name matches the required operation. |
| `openCursor()` | `public static function openCursor(string $database, string $table, array $options = [])` | See class section; use when the method name matches the required operation. |
| `fetchCursor()` | `public static function fetchCursor(string $token)` | See class section; use when the method name matches the required operation. |
| `getDataGuarded()` | `public static function getDataGuarded(string $database, string $table, int $maxRows = 10000)` | Read/list data without changing storage. |
| `importStreaming()` | `public static function importStreaming(string $database, string $table, iterable $rows, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `exportStreaming()` | `public static function exportStreaming(string $database, string $table, callable $writer, int $chunkSize = 500)` | See class section; use when the method name matches the required operation. |
| `setMaxRows()` | `public static function setMaxRows(string $database, string $table, int $maxRows)` | Write/change storage or configuration; validate input and permissions first. |
| `definePartitioning()` | `public static function definePartitioning(string $database, string $table, string $type, string $column, array $options = [])` | See class section; use when the method name matches the required operation. |
| `partitionByDate()` | `public static function partitionByDate(string $database, string $table, string $column, string $format = "Y-m")` | See class section; use when the method name matches the required operation. |
| `partitionByUser()` | `public static function partitionByUser(string $database, string $table, string $column = "user_id")` | Authentication/user workflows. |
| `partitionByTenant()` | `public static function partitionByTenant(string $database, string $table, string $column = "tenant_id")` | See class section; use when the method name matches the required operation. |
| `partitionByHash()` | `public static function partitionByHash(string $database, string $table, string $column, int $buckets = 16)` | See class section; use when the method name matches the required operation. |
| `partitionKey()` | `public static function partitionKey(string $database, string $table, array $row)` | See class section; use when the method name matches the required operation. |
| `partitionStats()` | `public static function partitionStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |
| `partitionPrune()` | `public static function partitionPrune(string $database, string $table, array $criteria)` | See class section; use when the method name matches the required operation. |

### `GBDB_StorageTrait`

File: `GBDB_GQL/db_engine/gbdb_storage.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `verifyStorage()` | `public static function verifyStorage(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `storageStats()` | `public static function storageStats(string $database, string $table)` | Operations, diagnostics, backups or maintenance. |

### `GBDB_TransactionTrait`

File: `GBDB_GQL/db_engine/gbdb_transaction.trait.php`

| Method | Signature | Typical use |
|---|---|---|
| `transactionTimeout()` | `public static function transactionTimeout(int $seconds)` | See class section; use when the method name matches the required operation. |
| `begin()` | `public static function begin()` | See class section; use when the method name matches the required operation. |
| `savepoint()` | `public static function savepoint(string $name)` | See class section; use when the method name matches the required operation. |
| `rollbackTo()` | `public static function rollbackTo(string $name)` | See class section; use when the method name matches the required operation. |
| `commit()` | `public static function commit()` | See class section; use when the method name matches the required operation. |
| `rollback()` | `public static function rollback()` | See class section; use when the method name matches the required operation. |
| `transactionStatus()` | `public static function transactionStatus()` | See class section; use when the method name matches the required operation. |
