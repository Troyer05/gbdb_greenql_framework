# GBDB – Fassade, Engine und Traits

Anwendungscode verwendet GBDB aus runtime/facade.php. GBDBEngine und die Traits sind interne Implementierungen. Traits werden nicht direkt aufgerufen; gleichnamige Deklarationen können durch die Fassade oder Trait-Auflösung ersetzt werden.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## GBDB

Klasse · Quelle: [runtime/facade.php](../../PHP/gbdb_framework/runtime/facade.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `update()` | `public static function update(?string $pathOfOldDB = null) :array` | [Zeile 7](../../PHP/gbdb_framework/runtime/facade.php#L7) |
| `migrate()` | `public static function migrate(?string $pathOfOldDB = null) :array` | [Zeile 11](../../PHP/gbdb_framework/runtime/facade.php#L11) |
| `boot()` | `public static function boot() :array` | [Zeile 15](../../PHP/gbdb_framework/runtime/facade.php#L15) |
| `shutdown()` | `public static function shutdown() :void` | [Zeile 19](../../PHP/gbdb_framework/runtime/facade.php#L19) |
| `dirtyShutdownDetected()` | `public static function dirtyShutdownDetected() :bool` | [Zeile 25](../../PHP/gbdb_framework/runtime/facade.php#L25) |
| `recover()` | `public static function recover(bool $auto = false) :array` | [Zeile 29](../../PHP/gbdb_framework/runtime/facade.php#L29) |
| `recoverTable()` | `public static function recoverTable(string $database, string $table) :array` | [Zeile 33](../../PHP/gbdb_framework/runtime/facade.php#L33) |
| `recoveryReport()` | `public static function recoveryReport() :array` | [Zeile 37](../../PHP/gbdb_framework/runtime/facade.php#L37) |
| `isolationLevel()` | `public static function isolationLevel(string $level) :string` | [Zeile 41](../../PHP/gbdb_framework/runtime/facade.php#L41) |
| `beginSnapshot()` | `public static function beginSnapshot() :string` | [Zeile 45](../../PHP/gbdb_framework/runtime/facade.php#L45) |
| `endSnapshot()` | `public static function endSnapshot(string $snapshotId) :bool` | [Zeile 49](../../PHP/gbdb_framework/runtime/facade.php#L49) |
| `transactionSnapshots()` | `public static function transactionSnapshots() :array` | [Zeile 53](../../PHP/gbdb_framework/runtime/facade.php#L53) |
| `rowVersions()` | `public static function rowVersions(string $database, string $table, int $rowId) :array` | [Zeile 57](../../PHP/gbdb_framework/runtime/facade.php#L57) |
| `getDataSnapshot()` | `public static function getDataSnapshot(string $database, string $table, ?string $snapshotId = null, bool $filter = false, mixed $where = '', mixed $is = '') :mixed` | [Zeile 61](../../PHP/gbdb_framework/runtime/facade.php#L61) |
| `optimisticEditData()` | `public static function optimisticEditData(string $database, string $table, mixed $where, mixed $is, array $newData, int $expectedVersion) :bool` | [Zeile 65](../../PHP/gbdb_framework/runtime/facade.php#L65) |
| `pessimisticRowLock()` | `public static function pessimisticRowLock(string $database, string $table, int $rowId, int $timeoutMs = 0) :string\|false` | [Zeile 69](../../PHP/gbdb_framework/runtime/facade.php#L69) |
| `cleanupRowVersions()` | `public static function cleanupRowVersions(string $database, string $table, int $keepSeconds = 3600) :array` | [Zeile 73](../../PHP/gbdb_framework/runtime/facade.php#L73) |
| `versionChains()` | `public static function versionChains(string $database, string $table) :array` | [Zeile 77](../../PHP/gbdb_framework/runtime/facade.php#L77) |
| `garbageCollectVersions()` | `public static function garbageCollectVersions(string $database, string $table, int $keepSeconds = 3600) :array` | [Zeile 81](../../PHP/gbdb_framework/runtime/facade.php#L81) |
| `mvccCleanup()` | `public static function mvccCleanup(int $keepSeconds = 3600) :array` | [Zeile 85](../../PHP/gbdb_framework/runtime/facade.php#L85) |
| `testConsistentReads()` | `public static function testConsistentReads() :array` | [Zeile 89](../../PHP/gbdb_framework/runtime/facade.php#L89) |
| `mvccLoadTest()` | `public static function mvccLoadTest(int $writes = 100) :array` | [Zeile 93](../../PHP/gbdb_framework/runtime/facade.php#L93) |
| `mvccStats()` | `public static function mvccStats(string $database, string $table) :array` | [Zeile 97](../../PHP/gbdb_framework/runtime/facade.php#L97) |
| `transactionTimeout()` | `public static function transactionTimeout(int $seconds) :int` | [Zeile 101](../../PHP/gbdb_framework/runtime/facade.php#L101) |
| `begin()` | `public static function begin() :bool` | [Zeile 105](../../PHP/gbdb_framework/runtime/facade.php#L105) |
| `savepoint()` | `public static function savepoint(string $name) :bool` | [Zeile 109](../../PHP/gbdb_framework/runtime/facade.php#L109) |
| `rollbackTo()` | `public static function rollbackTo(string $name) :bool` | [Zeile 113](../../PHP/gbdb_framework/runtime/facade.php#L113) |
| `commit()` | `public static function commit() :bool` | [Zeile 117](../../PHP/gbdb_framework/runtime/facade.php#L117) |
| `rollback()` | `public static function rollback() :bool` | [Zeile 121](../../PHP/gbdb_framework/runtime/facade.php#L121) |
| `transactionStatus()` | `public static function transactionStatus() :array` | [Zeile 125](../../PHP/gbdb_framework/runtime/facade.php#L125) |
| `withInstance()` | `public static function withInstance(string $instance, callable $callback) :mixed` | [Zeile 129](../../PHP/gbdb_framework/runtime/facade.php#L129) |
| `schemaTable()` | `public static function schemaTable(string $database, string $table) :array` | [Zeile 133](../../PHP/gbdb_framework/runtime/facade.php#L133) |
| `enableSchemaTypes()` | `public static function enableSchemaTypes(string $database, string $table, bool $enabled = true) :bool` | [Zeile 137](../../PHP/gbdb_framework/runtime/facade.php#L137) |
| `setColumnType()` | `public static function setColumnType(string $database, string $table, string $column, string $type, array $options = []) :bool` | [Zeile 141](../../PHP/gbdb_framework/runtime/facade.php#L141) |
| `setColumnDefault()` | `public static function setColumnDefault(string $database, string $table, string $column, mixed $default) :bool` | [Zeile 145](../../PHP/gbdb_framework/runtime/facade.php#L145) |
| `setSchemaConstraint()` | `public static function setSchemaConstraint(string $database, string $table, string $column, string $constraint, mixed $value = true) :bool` | [Zeile 149](../../PHP/gbdb_framework/runtime/facade.php#L149) |
| `createSequence()` | `public static function createSequence(string $database, string $table, string $name, int $start = 0, int $step = 1) :bool` | [Zeile 153](../../PHP/gbdb_framework/runtime/facade.php#L153) |
| `nextSequence()` | `public static function nextSequence(string $database, string $table, string $name) :int` | [Zeile 157](../../PHP/gbdb_framework/runtime/facade.php#L157) |
| `defineMigration()` | `public static function defineMigration(string $database, string $table, string $name, array $operations) :bool` | [Zeile 161](../../PHP/gbdb_framework/runtime/facade.php#L161) |
| `runMigration()` | `public static function runMigration(string $database, string $table, string $name) :array` | [Zeile 165](../../PHP/gbdb_framework/runtime/facade.php#L165) |
| `rollbackMigration()` | `public static function rollbackMigration(string $database, string $table, string $name) :array` | [Zeile 169](../../PHP/gbdb_framework/runtime/facade.php#L169) |
| `checkSchema()` | `public static function checkSchema(string $database, string $table) :array` | [Zeile 173](../../PHP/gbdb_framework/runtime/facade.php#L173) |
| `repairSchema()` | `public static function repairSchema(string $database, string $table) :array` | [Zeile 177](../../PHP/gbdb_framework/runtime/facade.php#L177) |
| `migrationHistory()` | `public static function migrationHistory(string $database, string $table) :array` | [Zeile 181](../../PHP/gbdb_framework/runtime/facade.php#L181) |
| `verifyStorage()` | `public static function verifyStorage(string $database, string $table) :array` | [Zeile 185](../../PHP/gbdb_framework/runtime/facade.php#L185) |
| `storageStats()` | `public static function storageStats(string $database, string $table) :array` | [Zeile 189](../../PHP/gbdb_framework/runtime/facade.php#L189) |
| `createInstance()` | `public static function createInstance(string $name) :bool` | [Zeile 193](../../PHP/gbdb_framework/runtime/facade.php#L193) |
| `deleteInstance()` | `public static function deleteInstance(string $name, bool $force = false) :bool` | [Zeile 197](../../PHP/gbdb_framework/runtime/facade.php#L197) |
| `listInstances()` | `public static function listInstances(bool $includeSystem = false) :array` | [Zeile 201](../../PHP/gbdb_framework/runtime/facade.php#L201) |
| `listAllInstances()` | `public static function listAllInstances() :array` | [Zeile 205](../../PHP/gbdb_framework/runtime/facade.php#L205) |
| `createDatabase()` | `public static function createDatabase(string $name) :bool` | [Zeile 209](../../PHP/gbdb_framework/runtime/facade.php#L209) |
| `deleteDatabase()` | `public static function deleteDatabase(string $name) :bool` | [Zeile 213](../../PHP/gbdb_framework/runtime/facade.php#L213) |
| `createTable()` | `public static function createTable(string $database, string $table, array $cols) :bool` | [Zeile 217](../../PHP/gbdb_framework/runtime/facade.php#L217) |
| `addColumn()` | `public static function addColumn(string $database, string $table, string $column, mixed $default = '') :bool` | [Zeile 221](../../PHP/gbdb_framework/runtime/facade.php#L221) |
| `deleteTable()` | `public static function deleteTable(string $database, string $table) :bool` | [Zeile 225](../../PHP/gbdb_framework/runtime/facade.php#L225) |
| `replaceTableContents()` | `public static function replaceTableContents(string $database, string $table, array $columns, array $rows) :bool` | [Zeile 229](../../PHP/gbdb_framework/runtime/facade.php#L229) |
| `insertData()` | `public static function insertData(string $database, string $table, mixed $data) :int` | [Zeile 233](../../PHP/gbdb_framework/runtime/facade.php#L233) |
| `deleteData()` | `public static function deleteData(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 237](../../PHP/gbdb_framework/runtime/facade.php#L237) |
| `editData()` | `public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData) :bool` | [Zeile 241](../../PHP/gbdb_framework/runtime/facade.php#L241) |
| `getData()` | `public static function getData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '') :mixed` | [Zeile 245](../../PHP/gbdb_framework/runtime/facade.php#L245) |
| `elementExists()` | `public static function elementExists(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 249](../../PHP/gbdb_framework/runtime/facade.php#L249) |
| `listDBs()` | `public static function listDBs() :array` | [Zeile 253](../../PHP/gbdb_framework/runtime/facade.php#L253) |
| `listTables()` | `public static function listTables(string $database, bool $descending = false) :array` | [Zeile 257](../../PHP/gbdb_framework/runtime/facade.php#L257) |
| `deleteAll()` | `public static function deleteAll(string $database) :bool` | [Zeile 261](../../PHP/gbdb_framework/runtime/facade.php#L261) |
| `nextID()` | `public static function nextID(string $database, string $table) :int` | [Zeile 265](../../PHP/gbdb_framework/runtime/facade.php#L265) |
| `getKeys()` | `public static function getKeys(string $database, string $table) :array` | [Zeile 269](../../PHP/gbdb_framework/runtime/facade.php#L269) |
| `query()` | `public static function query(string $script, array $ctx = [], array $params = []) :array` | [Zeile 273](../../PHP/gbdb_framework/runtime/facade.php#L273) |
| `runScript()` | `public static function runScript(string $scriptName, array $params = [], array $ctx = []) :array` | [Zeile 277](../../PHP/gbdb_framework/runtime/facade.php#L277) |
| `compactTable()` | `public static function compactTable(string $database, string $table) :bool` | [Zeile 281](../../PHP/gbdb_framework/runtime/facade.php#L281) |
| `snapshot()` | `public static function snapshot(string $database, string $table, string $reason = 'manual') :string` | [Zeile 285](../../PHP/gbdb_framework/runtime/facade.php#L285) |
| `createIndex()` | `public static function createIndex(string $database, string $table, array\|string $column, string $type = 'single', array $options = []) :bool` | [Zeile 289](../../PHP/gbdb_framework/runtime/facade.php#L289) |
| `createPrimaryIndex()` | `public static function createPrimaryIndex(string $database, string $table, string $column = 'id') :bool` | [Zeile 293](../../PHP/gbdb_framework/runtime/facade.php#L293) |
| `createUniqueIndex()` | `public static function createUniqueIndex(string $database, string $table, array\|string $columns) :bool` | [Zeile 297](../../PHP/gbdb_framework/runtime/facade.php#L297) |
| `createCompositeIndex()` | `public static function createCompositeIndex(string $database, string $table, array $columns) :bool` | [Zeile 301](../../PHP/gbdb_framework/runtime/facade.php#L301) |
| `createSortedIndex()` | `public static function createSortedIndex(string $database, string $table, array\|string $columns) :bool` | [Zeile 305](../../PHP/gbdb_framework/runtime/facade.php#L305) |
| `createRangeIndex()` | `public static function createRangeIndex(string $database, string $table, string $column) :bool` | [Zeile 309](../../PHP/gbdb_framework/runtime/facade.php#L309) |
| `createPrefixIndex()` | `public static function createPrefixIndex(string $database, string $table, string $column) :bool` | [Zeile 313](../../PHP/gbdb_framework/runtime/facade.php#L313) |
| `createFulltextIndex()` | `public static function createFulltextIndex(string $database, string $table, array $columns = []) :bool` | [Zeile 317](../../PHP/gbdb_framework/runtime/facade.php#L317) |
| `dropIndex()` | `public static function dropIndex(string $database, string $table, string $column) :bool` | [Zeile 321](../../PHP/gbdb_framework/runtime/facade.php#L321) |
| `listIndexes()` | `public static function listIndexes(string $database, string $table) :array` | [Zeile 325](../../PHP/gbdb_framework/runtime/facade.php#L325) |
| `rebuildIndexes()` | `public static function rebuildIndexes(string $database, string $table) :bool` | [Zeile 329](../../PHP/gbdb_framework/runtime/facade.php#L329) |
| `repairIndexes()` | `public static function repairIndexes(string $database, string $table) :array` | [Zeile 333](../../PHP/gbdb_framework/runtime/facade.php#L333) |
| `verifyIndexes()` | `public static function verifyIndexes(string $database, string $table) :array` | [Zeile 337](../../PHP/gbdb_framework/runtime/facade.php#L337) |
| `health()` | `public static function health(string $database, string $table) :array` | [Zeile 341](../../PHP/gbdb_framework/runtime/facade.php#L341) |
| `repairTable()` | `public static function repairTable(string $database, string $table) :bool` | [Zeile 345](../../PHP/gbdb_framework/runtime/facade.php#L345) |
| `meta()` | `public static function meta(string $database, string $table) :array` | [Zeile 349](../../PHP/gbdb_framework/runtime/facade.php#L349) |
| `addConstraint()` | `public static function addConstraint(string $database, string $table, string $column, string $type) :bool` | [Zeile 353](../../PHP/gbdb_framework/runtime/facade.php#L353) |
| `dropConstraint()` | `public static function dropConstraint(string $database, string $table, string $column, string $type) :bool` | [Zeile 357](../../PHP/gbdb_framework/runtime/facade.php#L357) |
| `listConstraints()` | `public static function listConstraints(string $database, string $table) :array` | [Zeile 361](../../PHP/gbdb_framework/runtime/facade.php#L361) |
| `vacuum()` | `public static function vacuum(string $database, string $table) :array` | [Zeile 365](../../PHP/gbdb_framework/runtime/facade.php#L365) |
| `restoreSnapshot()` | `public static function restoreSnapshot(string $database, string $table, string $snapshotId) :bool` | [Zeile 369](../../PHP/gbdb_framework/runtime/facade.php#L369) |
| `clearRuntimeCache()` | `public static function clearRuntimeCache(?string $database = null, ?string $table = null) :void` | [Zeile 373](../../PHP/gbdb_framework/runtime/facade.php#L373) |
| `getCachedData()` | `public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5) :mixed` | [Zeile 379](../../PHP/gbdb_framework/runtime/facade.php#L379) |
| `bulkInsert()` | `public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true) :array` | [Zeile 383](../../PHP/gbdb_framework/runtime/facade.php#L383) |
| `streamRows()` | `public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500) :array` | [Zeile 387](../../PHP/gbdb_framework/runtime/facade.php#L387) |
| `page()` | `public static function page(string $database, string $table, int $page = 1, int $perPage = 50) :array` | [Zeile 391](../../PHP/gbdb_framework/runtime/facade.php#L391) |
| `cursor()` | `public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null) :array` | [Zeile 395](../../PHP/gbdb_framework/runtime/facade.php#L395) |
| `fulltext_search()` | `public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array` | [Zeile 399](../../PHP/gbdb_framework/runtime/facade.php#L399) |
| `fulltext()` | `public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array` | [Zeile 403](../../PHP/gbdb_framework/runtime/facade.php#L403) |
| `queryPlan()` | `public static function queryPlan(string $database, string $table, string $where = '', mixed $is = '', ?string $sortField = null, ?int $limit = null, int $offset = 0) :array` | [Zeile 407](../../PHP/gbdb_framework/runtime/facade.php#L407) |
| `grantAcl()` | `public static function grantAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 411](../../PHP/gbdb_framework/runtime/facade.php#L411) |
| `revokeAcl()` | `public static function revokeAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 415](../../PHP/gbdb_framework/runtime/facade.php#L415) |
| `checkAcl()` | `public static function checkAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 419](../../PHP/gbdb_framework/runtime/facade.php#L419) |
| `audit()` | `public static function audit(string $action, array $payload = [], string $actor = 'system') :int` | [Zeile 423](../../PHP/gbdb_framework/runtime/facade.php#L423) |
| `gdprExport()` | `public static function gdprExport(string $database, string $table, string $column, mixed $value) :array` | [Zeile 427](../../PHP/gbdb_framework/runtime/facade.php#L427) |
| `gdprRedact()` | `public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]') :bool` | [Zeile 431](../../PHP/gbdb_framework/runtime/facade.php#L431) |
| `partitionTableName()` | `public static function partitionTableName(string $table, string $partition) :string` | [Zeile 435](../../PHP/gbdb_framework/runtime/facade.php#L435) |
| `insertPartitioned()` | `public static function insertPartitioned(string $database, string $table, string $partition, array $data) :int` | [Zeile 439](../../PHP/gbdb_framework/runtime/facade.php#L439) |
| `getPartition()` | `public static function getPartition(string $database, string $table, string $partition) :array` | [Zeile 443](../../PHP/gbdb_framework/runtime/facade.php#L443) |
| `shardTableName()` | `public static function shardTableName(string $table, mixed $key, int $shards = 16) :string` | [Zeile 447](../../PHP/gbdb_framework/runtime/facade.php#L447) |
| `insertSharded()` | `public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16) :int` | [Zeile 451](../../PHP/gbdb_framework/runtime/facade.php#L451) |
| `getShard()` | `public static function getShard(string $database, string $table, mixed $key, int $shards = 16) :array` | [Zeile 455](../../PHP/gbdb_framework/runtime/facade.php#L455) |
| `recoverWalOnly()` | `public static function recoverWalOnly(string $database, string $table) :array` | [Zeile 459](../../PHP/gbdb_framework/runtime/facade.php#L459) |
| `appendLog()` | `public static function appendLog(string $database, string $table, int $limit = 100) :array` | [Zeile 463](../../PHP/gbdb_framework/runtime/facade.php#L463) |
| `monitor()` | `public static function monitor(string $database, string $table) :array` | [Zeile 467](../../PHP/gbdb_framework/runtime/facade.php#L467) |
| `insert()` | `public static function insert(string $database, string $table, array $data) :int` | [Zeile 471](../../PHP/gbdb_framework/runtime/facade.php#L471) |
| `upsert()` | `public static function upsert(string $database, string $table, mixed $where, mixed $is, array $data) :array` | [Zeile 475](../../PHP/gbdb_framework/runtime/facade.php#L475) |
| `stats()` | `public static function stats(string $database, ?string $table = null) :array` | [Zeile 479](../../PHP/gbdb_framework/runtime/facade.php#L479) |
| `analyze()` | `public static function analyze(string $database, string $table, int $sampleSize = 1000) :array` | [Zeile 483](../../PHP/gbdb_framework/runtime/facade.php#L483) |
| `autoIndex()` | `public static function autoIndex(string $database, string $table, array $columns = []) :array` | [Zeile 487](../../PHP/gbdb_framework/runtime/facade.php#L487) |
| `explain()` | `public static function explain(string $database, string $table, string $where = '', mixed $is = '') :array` | [Zeile 491](../../PHP/gbdb_framework/runtime/facade.php#L491) |
| `newQueryId()` | `public static function newQueryId() :string` | [Zeile 495](../../PHP/gbdb_framework/runtime/facade.php#L495) |
| `activeQueries()` | `public static function activeQueries() :array` | [Zeile 499](../../PHP/gbdb_framework/runtime/facade.php#L499) |
| `killQuery()` | `public static function killQuery(string $queryId) :array` | [Zeile 503](../../PHP/gbdb_framework/runtime/facade.php#L503) |
| `slowQueryLog()` | `public static function slowQueryLog(array $plan, float $seconds) :void` | [Zeile 507](../../PHP/gbdb_framework/runtime/facade.php#L507) |
| `select()` | `public static function select(string $database, string $table, array $options = []) :array` | [Zeile 513](../../PHP/gbdb_framework/runtime/facade.php#L513) |
| `distinct()` | `public static function distinct(string $database, string $table, string $column) :array` | [Zeile 517](../../PHP/gbdb_framework/runtime/facade.php#L517) |
| `aggregate()` | `public static function aggregate(string $database, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null) :array` | [Zeile 521](../../PHP/gbdb_framework/runtime/facade.php#L521) |
| `queryOptions()` | `public static function queryOptions(array $options = []) :array` | [Zeile 525](../../PHP/gbdb_framework/runtime/facade.php#L525) |
| `prepareQuery()` | `public static function prepareQuery(string $name, string $script) :bool` | [Zeile 529](../../PHP/gbdb_framework/runtime/facade.php#L529) |
| `executePreparedQuery()` | `public static function executePreparedQuery(string $name, array $params = [], array $ctx = []) :array` | [Zeile 533](../../PHP/gbdb_framework/runtime/facade.php#L533) |
| `bindQueryParams()` | `public static function bindQueryParams(string $script, array $params) :string` | [Zeile 537](../../PHP/gbdb_framework/runtime/facade.php#L537) |
| `clearQueryCache()` | `public static function clearQueryCache() :void` | [Zeile 541](../../PHP/gbdb_framework/runtime/facade.php#L541) |
| `innerJoin()` | `public static function innerJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = []) :array` | [Zeile 547](../../PHP/gbdb_framework/runtime/facade.php#L547) |
| `leftJoin()` | `public static function leftJoin(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = []) :array` | [Zeile 551](../../PHP/gbdb_framework/runtime/facade.php#L551) |
| `join()` | `public static function join(string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, string $type = 'inner', array $options = []) :array` | [Zeile 555](../../PHP/gbdb_framework/runtime/facade.php#L555) |
| `joinPlan()` | `public static function joinPlan(string $leftTable, string $rightTable, int $leftRows, int $rightRows, string $leftKey, string $rightKey, string $type = 'inner') :array` | [Zeile 559](../../PHP/gbdb_framework/runtime/facade.php#L559) |
| `subquery()` | `public static function subquery(callable $query) :array` | [Zeile 563](../../PHP/gbdb_framework/runtime/facade.php#L563) |
| `streamQuery()` | `public static function streamQuery(string $database, string $table, callable $callback, int $chunkSize = 500) :array` | [Zeile 567](../../PHP/gbdb_framework/runtime/facade.php#L567) |
| `allowFullScan()` | `public static function allowFullScan(string $database, string $table, string $where = '') :bool` | [Zeile 571](../../PHP/gbdb_framework/runtime/facade.php#L571) |
| `addForeignKey()` | `public static function addForeignKey(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = 'id', array $options = []) :bool` | [Zeile 575](../../PHP/gbdb_framework/runtime/facade.php#L575) |
| `defineRelation()` | `public static function defineRelation(string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = 'id', array $options = []) :bool` | [Zeile 579](../../PHP/gbdb_framework/runtime/facade.php#L579) |
| `dropRelation()` | `public static function dropRelation(string $database, string $table, string $name) :bool` | [Zeile 583](../../PHP/gbdb_framework/runtime/facade.php#L583) |
| `createRelationIndex()` | `public static function createRelationIndex(string $database, string $table, string $column) :bool` | [Zeile 587](../../PHP/gbdb_framework/runtime/facade.php#L587) |
| `checkOrphans()` | `public static function checkOrphans(string $database, string $table) :array` | [Zeile 591](../../PHP/gbdb_framework/runtime/facade.php#L591) |
| `repairOrphans()` | `public static function repairOrphans(string $database, string $table, string $mode = 'report') :array` | [Zeile 595](../../PHP/gbdb_framework/runtime/facade.php#L595) |
| `repairConstraints()` | `public static function repairConstraints(string $database, string $table) :array` | [Zeile 599](../../PHP/gbdb_framework/runtime/facade.php#L599) |
| `relationGraph()` | `public static function relationGraph(?string $database = null) :array` | [Zeile 603](../../PHP/gbdb_framework/runtime/facade.php#L603) |
| `relationDocs()` | `public static function relationDocs(?string $database = null) :string` | [Zeile 607](../../PHP/gbdb_framework/runtime/facade.php#L607) |
| `checkRelations()` | `public static function checkRelations(?string $database = null) :array` | [Zeile 611](../../PHP/gbdb_framework/runtime/facade.php#L611) |
| `snowflakeId()` | `public static function snowflakeId(int $node = 1) :string` | [Zeile 615](../../PHP/gbdb_framework/runtime/facade.php#L615) |
| `distributedId()` | `public static function distributedId(string $tenant = '', string $shard = '') :string` | [Zeile 619](../../PHP/gbdb_framework/runtime/facade.php#L619) |
| `idExists()` | `public static function idExists(string $database, string $table, string $column, mixed $id) :bool` | [Zeile 623](../../PHP/gbdb_framework/runtime/facade.php#L623) |
| `safeId()` | `public static function safeId(string $database, string $table, string $column = 'uid', string $type = 'ulid', array $options = []) :string` | [Zeile 627](../../PHP/gbdb_framework/runtime/facade.php#L627) |
| `readGenerator()` | `public static function readGenerator(string $database, string $table, int $chunkSize = 500) :Generator` | [Zeile 631](../../PHP/gbdb_framework/runtime/facade.php#L631) |
| `chunkedRows()` | `public static function chunkedRows(string $database, string $table, int $chunkSize = 500, ?callable $filter = null) :Generator` | [Zeile 635](../../PHP/gbdb_framework/runtime/facade.php#L635) |
| `openCursor()` | `public static function openCursor(string $database, string $table, array $options = []) :array` | [Zeile 639](../../PHP/gbdb_framework/runtime/facade.php#L639) |
| `fetchCursor()` | `public static function fetchCursor(string $token) :array` | [Zeile 643](../../PHP/gbdb_framework/runtime/facade.php#L643) |
| `getDataGuarded()` | `public static function getDataGuarded(string $database, string $table, int $maxRows = 10000) :array` | [Zeile 647](../../PHP/gbdb_framework/runtime/facade.php#L647) |
| `importStreaming()` | `public static function importStreaming(string $database, string $table, iterable $rows, int $chunkSize = 500) :array` | [Zeile 651](../../PHP/gbdb_framework/runtime/facade.php#L651) |
| `exportStreaming()` | `public static function exportStreaming(string $database, string $table, callable $writer, int $chunkSize = 500) :array` | [Zeile 655](../../PHP/gbdb_framework/runtime/facade.php#L655) |
| `setMaxRows()` | `public static function setMaxRows(string $database, string $table, int $maxRows) :bool` | [Zeile 659](../../PHP/gbdb_framework/runtime/facade.php#L659) |
| `definePartitioning()` | `public static function definePartitioning(string $database, string $table, string $type, string $column, array $options = []) :bool` | [Zeile 663](../../PHP/gbdb_framework/runtime/facade.php#L663) |
| `partitionByDate()` | `public static function partitionByDate(string $database, string $table, string $column, string $format = 'Y-m') :bool` | [Zeile 667](../../PHP/gbdb_framework/runtime/facade.php#L667) |
| `partitionByUser()` | `public static function partitionByUser(string $database, string $table, string $column = 'user_id') :bool` | [Zeile 671](../../PHP/gbdb_framework/runtime/facade.php#L671) |
| `partitionByTenant()` | `public static function partitionByTenant(string $database, string $table, string $column = 'tenant_id') :bool` | [Zeile 675](../../PHP/gbdb_framework/runtime/facade.php#L675) |
| `partitionByHash()` | `public static function partitionByHash(string $database, string $table, string $column, int $buckets = 16) :bool` | [Zeile 679](../../PHP/gbdb_framework/runtime/facade.php#L679) |
| `partitionKey()` | `public static function partitionKey(string $database, string $table, array $row) :string` | [Zeile 683](../../PHP/gbdb_framework/runtime/facade.php#L683) |
| `partitionStats()` | `public static function partitionStats(string $database, string $table) :array` | [Zeile 687](../../PHP/gbdb_framework/runtime/facade.php#L687) |
| `partitionPrune()` | `public static function partitionPrune(string $database, string $table, array $criteria) :array` | [Zeile 691](../../PHP/gbdb_framework/runtime/facade.php#L691) |
| `repairPartitions()` | `public static function repairPartitions(string $database, string $table) :array` | [Zeile 695](../../PHP/gbdb_framework/runtime/facade.php#L695) |
| `backupPartition()` | `public static function backupPartition(string $database, string $table, string $partition, string $target = '') :array` | [Zeile 699](../../PHP/gbdb_framework/runtime/facade.php#L699) |
| `setPartitionReadOnly()` | `public static function setPartitionReadOnly(string $database, string $table, string $partition, bool $readonly = true) :bool` | [Zeile 703](../../PHP/gbdb_framework/runtime/facade.php#L703) |
| `archiveOldPartitions()` | `public static function archiveOldPartitions(string $database, string $table, int $olderThan, bool $readonly = true) :array` | [Zeile 707](../../PHP/gbdb_framework/runtime/facade.php#L707) |
| `preparePartitionMerge()` | `public static function preparePartitionMerge(string $database, string $table, array $partitions, string $target) :array` | [Zeile 711](../../PHP/gbdb_framework/runtime/facade.php#L711) |
| `preparePartitionSplit()` | `public static function preparePartitionSplit(string $database, string $table, string $partition, array $targets) :array` | [Zeile 715](../../PHP/gbdb_framework/runtime/facade.php#L715) |
| `preparePartitionMigration()` | `public static function preparePartitionMigration(string $database, string $table, string $partition, string $targetInstance) :array` | [Zeile 719](../../PHP/gbdb_framework/runtime/facade.php#L719) |
| `defineShardConcept()` | `public static function defineShardConcept(array $config = []) :array` | [Zeile 723](../../PHP/gbdb_framework/runtime/facade.php#L723) |
| `registerShard()` | `public static function registerShard(string $name, array $config = []) :array` | [Zeile 727](../../PHP/gbdb_framework/runtime/facade.php#L727) |
| `shardMap()` | `public static function shardMap() :array` | [Zeile 731](../../PHP/gbdb_framework/runtime/facade.php#L731) |
| `defineShardKey()` | `public static function defineShardKey(string $database, string $table, string $column, string $strategy = 'hash') :bool` | [Zeile 735](../../PHP/gbdb_framework/runtime/facade.php#L735) |
| `shardForValue()` | `public static function shardForValue(mixed $value, string $strategy = 'hash') :string` | [Zeile 739](../../PHP/gbdb_framework/runtime/facade.php#L739) |
| `shardRouter()` | `public static function shardRouter(string $database, string $table, array $row) :array` | [Zeile 743](../../PHP/gbdb_framework/runtime/facade.php#L743) |
| `prepareUserSharding()` | `public static function prepareUserSharding(string $database, string $table, string $column = 'user_id') :bool` | [Zeile 747](../../PHP/gbdb_framework/runtime/facade.php#L747) |
| `prepareTenantSharding()` | `public static function prepareTenantSharding(string $database, string $table, string $column = 'tenant_id') :bool` | [Zeile 751](../../PHP/gbdb_framework/runtime/facade.php#L751) |
| `prepareHashSharding()` | `public static function prepareHashSharding(string $database, string $table, string $column = 'id') :bool` | [Zeile 755](../../PHP/gbdb_framework/runtime/facade.php#L755) |
| `shardAwareId()` | `public static function shardAwareId(string $database, string $table, array $row = []) :string` | [Zeile 759](../../PHP/gbdb_framework/runtime/facade.php#L759) |
| `crossShardQueryRules()` | `public static function crossShardQueryRules(array $rules = []) :array` | [Zeile 763](../../PHP/gbdb_framework/runtime/facade.php#L763) |
| `prepareShardRebalancing()` | `public static function prepareShardRebalancing() :array` | [Zeile 767](../../PHP/gbdb_framework/runtime/facade.php#L767) |
| `prepareShardMigration()` | `public static function prepareShardMigration(string $fromShard, string $toShard, array $options = []) :array` | [Zeile 771](../../PHP/gbdb_framework/runtime/facade.php#L771) |
| `shardHealth()` | `public static function shardHealth() :array` | [Zeile 775](../../PHP/gbdb_framework/runtime/facade.php#L775) |
| `prepareCrossShardTransactions()` | `public static function prepareCrossShardTransactions() :array` | [Zeile 779](../../PHP/gbdb_framework/runtime/facade.php#L779) |
| `prepareShardAwareBackups()` | `public static function prepareShardAwareBackups() :array` | [Zeile 783](../../PHP/gbdb_framework/runtime/facade.php#L783) |
| `prepareShardMonitoring()` | `public static function prepareShardMonitoring() :array` | [Zeile 787](../../PHP/gbdb_framework/runtime/facade.php#L787) |
| `definePrimaryReplicaModel()` | `public static function definePrimaryReplicaModel(array $config = []) :array` | [Zeile 791](../../PHP/gbdb_framework/runtime/facade.php#L791) |
| `replicationLog()` | `public static function replicationLog() :string` | [Zeile 795](../../PHP/gbdb_framework/runtime/facade.php#L795) |
| `replicateAsync()` | `public static function replicateAsync() :array` | [Zeile 799](../../PHP/gbdb_framework/runtime/facade.php#L799) |
| `prepareReadReplica()` | `public static function prepareReadReplica(string $name, array $config = []) :array` | [Zeile 803](../../PHP/gbdb_framework/runtime/facade.php#L803) |
| `replicaCatchup()` | `public static function replicaCatchup(string $replica) :array` | [Zeile 807](../../PHP/gbdb_framework/runtime/facade.php#L807) |
| `replicaLag()` | `public static function replicaLag() :array` | [Zeile 811](../../PHP/gbdb_framework/runtime/facade.php#L811) |
| `replicaHealth()` | `public static function replicaHealth() :array` | [Zeile 815](../../PHP/gbdb_framework/runtime/facade.php#L815) |
| `prepareReplicaRepair()` | `public static function prepareReplicaRepair(string $replica = '') :array` | [Zeile 819](../../PHP/gbdb_framework/runtime/facade.php#L819) |
| `prepareFailover()` | `public static function prepareFailover(string $replica = '') :array` | [Zeile 823](../../PHP/gbdb_framework/runtime/facade.php#L823) |
| `promoteReplicaToPrimary()` | `public static function promoteReplicaToPrimary(string $replica) :array` | [Zeile 827](../../PHP/gbdb_framework/runtime/facade.php#L827) |
| `enableSplitBrainProtection()` | `public static function enableSplitBrainProtection(array $config = []) :array` | [Zeile 831](../../PHP/gbdb_framework/runtime/facade.php#L831) |
| `prepareReplicationSlots()` | `public static function prepareReplicationSlots(array $replicas = []) :array` | [Zeile 835](../../PHP/gbdb_framework/runtime/facade.php#L835) |
| `prepareShardReplication()` | `public static function prepareShardReplication(string $shard = '') :array` | [Zeile 839](../../PHP/gbdb_framework/runtime/facade.php#L839) |
| `prepareTenantReplication()` | `public static function prepareTenantReplication(string $tenant = '') :array` | [Zeile 843](../../PHP/gbdb_framework/runtime/facade.php#L843) |
| `registerClusterNode()` | `public static function registerClusterNode(string $node, array $config = []) :array` | [Zeile 847](../../PHP/gbdb_framework/runtime/facade.php#L847) |
| `clusterNodes()` | `public static function clusterNodes() :array` | [Zeile 851](../../PHP/gbdb_framework/runtime/facade.php#L851) |
| `heartbeatNode()` | `public static function heartbeatNode(string $node = '') :array` | [Zeile 855](../../PHP/gbdb_framework/runtime/facade.php#L855) |
| `prepareLeaderElection()` | `public static function prepareLeaderElection() :array` | [Zeile 859](../../PHP/gbdb_framework/runtime/facade.php#L859) |
| `defineQuorum()` | `public static function defineQuorum(int $minNodes = 1) :array` | [Zeile 863](../../PHP/gbdb_framework/runtime/facade.php#L863) |
| `clusterHealth()` | `public static function clusterHealth() :array` | [Zeile 867](../../PHP/gbdb_framework/runtime/facade.php#L867) |
| `defineNodeRoles()` | `public static function defineNodeRoles(array $roles = []) :array` | [Zeile 871](../../PHP/gbdb_framework/runtime/facade.php#L871) |
| `prepareServiceDiscovery()` | `public static function prepareServiceDiscovery(array $config = []) :array` | [Zeile 875](../../PHP/gbdb_framework/runtime/facade.php#L875) |
| `prepareDistributedLocks()` | `public static function prepareDistributedLocks() :array` | [Zeile 879](../../PHP/gbdb_framework/runtime/facade.php#L879) |
| `setPrimaryNode()` | `public static function setPrimaryNode(string $node) :array` | [Zeile 883](../../PHP/gbdb_framework/runtime/facade.php#L883) |
| `setReplicaNode()` | `public static function setReplicaNode(string $node) :array` | [Zeile 887](../../PHP/gbdb_framework/runtime/facade.php#L887) |
| `setWorkerNode()` | `public static function setWorkerNode(string $node) :array` | [Zeile 891](../../PHP/gbdb_framework/runtime/facade.php#L891) |
| `setMaintenanceNode()` | `public static function setMaintenanceNode(string $node) :array` | [Zeile 895](../../PHP/gbdb_framework/runtime/facade.php#L895) |
| `clusterConfig()` | `public static function clusterConfig(array $config = []) :array` | [Zeile 899](../../PHP/gbdb_framework/runtime/facade.php#L899) |
| `detectSplitBrain()` | `public static function detectSplitBrain() :array` | [Zeile 903](../../PHP/gbdb_framework/runtime/facade.php#L903) |
| `prepareClusterRecovery()` | `public static function prepareClusterRecovery() :array` | [Zeile 907](../../PHP/gbdb_framework/runtime/facade.php#L907) |
| `backupLog()` | `public static function backupLog(string $action, array $payload = []) :bool` | [Zeile 911](../../PHP/gbdb_framework/runtime/facade.php#L911) |
| `fullBackup()` | `public static function fullBackup(string $target = '') :array` | [Zeile 915](../../PHP/gbdb_framework/runtime/facade.php#L915) |
| `prepareIncrementalBackup()` | `public static function prepareIncrementalBackup() :array` | [Zeile 919](../../PHP/gbdb_framework/runtime/facade.php#L919) |
| `prepareDifferentialBackup()` | `public static function prepareDifferentialBackup() :array` | [Zeile 923](../../PHP/gbdb_framework/runtime/facade.php#L923) |
| `snapshotBackup()` | `public static function snapshotBackup(string $target = '') :array` | [Zeile 927](../../PHP/gbdb_framework/runtime/facade.php#L927) |
| `prepareHotBackup()` | `public static function prepareHotBackup() :array` | [Zeile 931](../../PHP/gbdb_framework/runtime/facade.php#L931) |
| `coldBackup()` | `public static function coldBackup(string $target = '') :array` | [Zeile 935](../../PHP/gbdb_framework/runtime/facade.php#L935) |
| `verifyBackup()` | `public static function verifyBackup(string $path) :array` | [Zeile 939](../../PHP/gbdb_framework/runtime/facade.php#L939) |
| `restoreTest()` | `public static function restoreTest(string $backupPath) :array` | [Zeile 943](../../PHP/gbdb_framework/runtime/facade.php#L943) |
| `preparePointInTimeRecovery()` | `public static function preparePointInTimeRecovery(?int $timestamp = null) :array` | [Zeile 947](../../PHP/gbdb_framework/runtime/facade.php#L947) |
| `encryptedBackup()` | `public static function encryptedBackup(string $target = '') :array` | [Zeile 951](../../PHP/gbdb_framework/runtime/facade.php#L951) |
| `prepareRemoteBackups()` | `public static function prepareRemoteBackups(array $config = []) :array` | [Zeile 955](../../PHP/gbdb_framework/runtime/facade.php#L955) |
| `prepareOffsiteBackups()` | `public static function prepareOffsiteBackups(array $config = []) :array` | [Zeile 959](../../PHP/gbdb_framework/runtime/facade.php#L959) |
| `rotateBackups()` | `public static function rotateBackups(int $keep = 10) :array` | [Zeile 963](../../PHP/gbdb_framework/runtime/facade.php#L963) |
| `applyBackupRetention()` | `public static function applyBackupRetention(int $days = 30) :array` | [Zeile 967](../../PHP/gbdb_framework/runtime/facade.php#L967) |
| `tenantBackup()` | `public static function tenantBackup(string $tenant, string $target = '') :array` | [Zeile 971](../../PHP/gbdb_framework/runtime/facade.php#L971) |
| `instanceBackup()` | `public static function instanceBackup(string $instance, string $target = '') :array` | [Zeile 975](../../PHP/gbdb_framework/runtime/facade.php#L975) |
| `tableBackup()` | `public static function tableBackup(string $database, string $table, string $target = '') :array` | [Zeile 979](../../PHP/gbdb_framework/runtime/facade.php#L979) |
| `repairMode()` | `public static function repairMode(bool $repair = false, array $options = []) :array` | [Zeile 983](../../PHP/gbdb_framework/runtime/facade.php#L983) |
| `tableDeepCheck()` | `public static function tableDeepCheck(string $database, string $table) :array` | [Zeile 987](../../PHP/gbdb_framework/runtime/facade.php#L987) |
| `verifyTableChecksum()` | `public static function verifyTableChecksum(string $database, string $table, bool $updateMeta = false) :array` | [Zeile 991](../../PHP/gbdb_framework/runtime/facade.php#L991) |
| `prepareRowChecksums()` | `public static function prepareRowChecksums(string $database, string $table, bool $write = true) :array` | [Zeile 995](../../PHP/gbdb_framework/runtime/facade.php#L995) |
| `verifyPageChecksums()` | `public static function verifyPageChecksums(string $database, string $table, int $chunkSize = 500, bool $write = true) :array` | [Zeile 999](../../PHP/gbdb_framework/runtime/facade.php#L999) |
| `verifyIndexConsistency()` | `public static function verifyIndexConsistency(string $database, string $table) :array` | [Zeile 1003](../../PHP/gbdb_framework/runtime/facade.php#L1003) |
| `verifyForeignKeys()` | `public static function verifyForeignKeys(string $database, string $table) :array` | [Zeile 1007](../../PHP/gbdb_framework/runtime/facade.php#L1007) |
| `verifySchemaCheck()` | `public static function verifySchemaCheck(string $database, string $table) :array` | [Zeile 1011](../../PHP/gbdb_framework/runtime/facade.php#L1011) |
| `verifyWalCheck()` | `public static function verifyWalCheck(string $database, string $table) :array` | [Zeile 1015](../../PHP/gbdb_framework/runtime/facade.php#L1015) |
| `verifyLockCheck()` | `public static function verifyLockCheck(string $database = '', string $table = '') :array` | [Zeile 1019](../../PHP/gbdb_framework/runtime/facade.php#L1019) |
| `verifyStorageCheck()` | `public static function verifyStorageCheck(string $database, string $table) :array` | [Zeile 1023](../../PHP/gbdb_framework/runtime/facade.php#L1023) |
| `repairIndex()` | `public static function repairIndex(string $database, string $table) :array` | [Zeile 1027](../../PHP/gbdb_framework/runtime/facade.php#L1027) |
| `repairWal()` | `public static function repairWal(string $database, string $table) :array` | [Zeile 1031](../../PHP/gbdb_framework/runtime/facade.php#L1031) |
| `repairRelations()` | `public static function repairRelations(string $database, string $table) :array` | [Zeile 1035](../../PHP/gbdb_framework/runtime/facade.php#L1035) |
| `repairReport()` | `public static function repairReport(string $database = '', string $table = '') :array` | [Zeile 1039](../../PHP/gbdb_framework/runtime/facade.php#L1039) |
| `isolateCorruptFiles()` | `public static function isolateCorruptFiles(string $database, string $table, string $reason = 'manual') :array` | [Zeile 1043](../../PHP/gbdb_framework/runtime/facade.php#L1043) |
| `manualRecoveryTools()` | `public static function manualRecoveryTools() :array` | [Zeile 1047](../../PHP/gbdb_framework/runtime/facade.php#L1047) |
| `autoCompact()` | `public static function autoCompact(array\|string $options = [], ?string $table = null) :array` | [Zeile 1051](../../PHP/gbdb_framework/runtime/facade.php#L1051) |
| `autoVacuum()` | `public static function autoVacuum(array $o = []) :array` | [Zeile 1055](../../PHP/gbdb_framework/runtime/facade.php#L1055) |
| `autoAnalyze()` | `public static function autoAnalyze(array $o = []) :array` | [Zeile 1059](../../PHP/gbdb_framework/runtime/facade.php#L1059) |
| `autoIndexRebuild()` | `public static function autoIndexRebuild(array $o = []) :array` | [Zeile 1063](../../PHP/gbdb_framework/runtime/facade.php#L1063) |
| `autoIndexRepair()` | `public static function autoIndexRepair(array $o = []) :array` | [Zeile 1067](../../PHP/gbdb_framework/runtime/facade.php#L1067) |
| `autoJournalCleanup()` | `public static function autoJournalCleanup(array $o = []) :array` | [Zeile 1071](../../PHP/gbdb_framework/runtime/facade.php#L1071) |
| `autoVersionCleanup()` | `public static function autoVersionCleanup(array $o = []) :array` | [Zeile 1075](../../PHP/gbdb_framework/runtime/facade.php#L1075) |
| `autoOrphanCleanup()` | `public static function autoOrphanCleanup(array $o = []) :array` | [Zeile 1079](../../PHP/gbdb_framework/runtime/facade.php#L1079) |
| `autoStatsRefresh()` | `public static function autoStatsRefresh(array $o = []) :array` | [Zeile 1083](../../PHP/gbdb_framework/runtime/facade.php#L1083) |
| `autoMaintenance()` | `public static function autoMaintenance(array $options = []) :array` | [Zeile 1087](../../PHP/gbdb_framework/runtime/facade.php#L1087) |
| `maintenanceScheduler()` | `public static function maintenanceScheduler(array $config = []) :array` | [Zeile 1091](../../PHP/gbdb_framework/runtime/facade.php#L1091) |
| `maintenanceWindow()` | `public static function maintenanceWindow(?string $start = null, ?string $end = null) :array` | [Zeile 1095](../../PHP/gbdb_framework/runtime/facade.php#L1095) |
| `maintenanceLogs()` | `public static function maintenanceLogs(int $limit = 100) :array` | [Zeile 1099](../../PHP/gbdb_framework/runtime/facade.php#L1099) |
| `maintenanceCli()` | `public static function maintenanceCli() :array` | [Zeile 1103](../../PHP/gbdb_framework/runtime/facade.php#L1103) |
| `maintenanceUi()` | `public static function maintenanceUi() :array` | [Zeile 1107](../../PHP/gbdb_framework/runtime/facade.php#L1107) |
| `deleteByRetention()` | `public static function deleteByRetention(string $database, string $table, string $column = 'created_at', int $days = 365) :array` | [Zeile 1111](../../PHP/gbdb_framework/runtime/facade.php#L1111) |
| `autoBackup()` | `public static function autoBackup(array $o = []) :array` | [Zeile 1115](../../PHP/gbdb_framework/runtime/facade.php#L1115) |
| `dbHealth()` | `public static function dbHealth(bool $allInstances = true) :array` | [Zeile 1119](../../PHP/gbdb_framework/runtime/facade.php#L1119) |
| `tableStats()` | `public static function tableStats(string $database, string $table) :array` | [Zeile 1123](../../PHP/gbdb_framework/runtime/facade.php#L1123) |
| `indexStats()` | `public static function indexStats(string $database, string $table) :array` | [Zeile 1127](../../PHP/gbdb_framework/runtime/facade.php#L1127) |
| `queryStats()` | `public static function queryStats() :array` | [Zeile 1131](../../PHP/gbdb_framework/runtime/facade.php#L1131) |
| `walStats()` | `public static function walStats(string $database, string $table) :array` | [Zeile 1135](../../PHP/gbdb_framework/runtime/facade.php#L1135) |
| `replicationStats()` | `public static function replicationStats() :array` | [Zeile 1139](../../PHP/gbdb_framework/runtime/facade.php#L1139) |
| `backupStats()` | `public static function backupStats() :array` | [Zeile 1143](../../PHP/gbdb_framework/runtime/facade.php#L1143) |
| `errorLog()` | `public static function errorLog(string $message = '', array $context = []) :array` | [Zeile 1147](../../PHP/gbdb_framework/runtime/facade.php#L1147) |
| `auditLog()` | `public static function auditLog(string $action = '', array $payload = []) :array` | [Zeile 1151](../../PHP/gbdb_framework/runtime/facade.php#L1151) |
| `recordQueryMetric()` | `public static function recordQueryMetric(string $type, float $ms, int $rowsScanned = 0, int $rowsReturned = 0, bool $fullScan = false, int $memory = 0) :void` | [Zeile 1155](../../PHP/gbdb_framework/runtime/facade.php#L1155) |
| `performanceMetrics()` | `public static function performanceMetrics() :array` | [Zeile 1161](../../PHP/gbdb_framework/runtime/facade.php#L1161) |
| `opsPerMinute()` | `public static function opsPerMinute() :array` | [Zeile 1165](../../PHP/gbdb_framework/runtime/facade.php#L1165) |
| `queryTimeAverage()` | `public static function queryTimeAverage() :float` | [Zeile 1169](../../PHP/gbdb_framework/runtime/facade.php#L1169) |
| `queryTimePercentiles()` | `public static function queryTimePercentiles() :array` | [Zeile 1173](../../PHP/gbdb_framework/runtime/facade.php#L1173) |
| `memoryUsagePerQuery()` | `public static function memoryUsagePerQuery() :array` | [Zeile 1177](../../PHP/gbdb_framework/runtime/facade.php#L1177) |
| `rowsScanned()` | `public static function rowsScanned() :int` | [Zeile 1181](../../PHP/gbdb_framework/runtime/facade.php#L1181) |
| `rowsReturned()` | `public static function rowsReturned() :int` | [Zeile 1185](../../PHP/gbdb_framework/runtime/facade.php#L1185) |
| `fullTableScanCount()` | `public static function fullTableScanCount() :int` | [Zeile 1189](../../PHP/gbdb_framework/runtime/facade.php#L1189) |
| `indexHitRate()` | `public static function indexHitRate() :float` | [Zeile 1193](../../PHP/gbdb_framework/runtime/facade.php#L1193) |
| `dashboardData()` | `public static function dashboardData() :array` | [Zeile 1197](../../PHP/gbdb_framework/runtime/facade.php#L1197) |
| `adminAlerts()` | `public static function adminAlerts() :array` | [Zeile 1201](../../PHP/gbdb_framework/runtime/facade.php#L1201) |
| `querySafety()` | `public static function querySafety(array $o = []) :array` | [Zeile 1205](../../PHP/gbdb_framework/runtime/facade.php#L1205) |
| `setMaxExecutionTime()` | `public static function setMaxExecutionTime(int $s) :array` | [Zeile 1209](../../PHP/gbdb_framework/runtime/facade.php#L1209) |
| `setMaxScanRows()` | `public static function setMaxScanRows(int $rows) :array` | [Zeile 1213](../../PHP/gbdb_framework/runtime/facade.php#L1213) |
| `setMaxExportSize()` | `public static function setMaxExportSize(int $bytes) :array` | [Zeile 1217](../../PHP/gbdb_framework/runtime/facade.php#L1217) |
| `blockExpensiveQueries()` | `public static function blockExpensiveQueries(bool $a = true) :array` | [Zeile 1221](../../PHP/gbdb_framework/runtime/facade.php#L1221) |
| `adminOnlyHeavyQueries()` | `public static function adminOnlyHeavyQueries(bool $a = true) :array` | [Zeile 1225](../../PHP/gbdb_framework/runtime/facade.php#L1225) |
| `querySandbox()` | `public static function querySandbox(array $c = []) :array` | [Zeile 1229](../../PHP/gbdb_framework/runtime/facade.php#L1229) |
| `rateLimitQueryType()` | `public static function rateLimitQueryType(string $type, int $limit) :array` | [Zeile 1233](../../PHP/gbdb_framework/runtime/facade.php#L1233) |
| `cacheSet()` | `public static function cacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool` | [Zeile 1237](../../PHP/gbdb_framework/runtime/facade.php#L1237) |
| `cacheGet()` | `public static function cacheGet(string $key, mixed $default = null) :mixed` | [Zeile 1241](../../PHP/gbdb_framework/runtime/facade.php#L1241) |
| `cacheInvalidate()` | `public static function cacheInvalidate(string $key) :void` | [Zeile 1245](../../PHP/gbdb_framework/runtime/facade.php#L1245) |
| `cacheInvalidateTag()` | `public static function cacheInvalidateTag(string $tag) :int` | [Zeile 1251](../../PHP/gbdb_framework/runtime/facade.php#L1251) |
| `rowCache()` | `public static function rowCache(string $db, string $table, mixed $id, int $ttl = 60) :mixed` | [Zeile 1255](../../PHP/gbdb_framework/runtime/facade.php#L1255) |
| `tableMetaCache()` | `public static function tableMetaCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1259](../../PHP/gbdb_framework/runtime/facade.php#L1259) |
| `schemaCache()` | `public static function schemaCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1263](../../PHP/gbdb_framework/runtime/facade.php#L1263) |
| `indexCache()` | `public static function indexCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1267](../../PHP/gbdb_framework/runtime/facade.php#L1267) |
| `queryResultCache()` | `public static function queryResultCache(string $key, callable $cb, int $ttl = 60, array $tags = []) :mixed` | [Zeile 1271](../../PHP/gbdb_framework/runtime/facade.php#L1271) |
| `permissionCache()` | `public static function permissionCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1275](../../PHP/gbdb_framework/runtime/facade.php#L1275) |
| `fulltextCache()` | `public static function fulltextCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1279](../../PHP/gbdb_framework/runtime/facade.php#L1279) |
| `counterCache()` | `public static function counterCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1283](../../PHP/gbdb_framework/runtime/facade.php#L1283) |
| `cacheTtl()` | `public static function cacheTtl(string $key) :int` | [Zeile 1287](../../PHP/gbdb_framework/runtime/facade.php#L1287) |
| `cacheStats()` | `public static function cacheStats() :array` | [Zeile 1291](../../PHP/gbdb_framework/runtime/facade.php#L1291) |
| `cacheHitRate()` | `public static function cacheHitRate() :float` | [Zeile 1295](../../PHP/gbdb_framework/runtime/facade.php#L1295) |
| `cacheWarmup()` | `public static function cacheWarmup(array $items = []) :array` | [Zeile 1299](../../PHP/gbdb_framework/runtime/facade.php#L1299) |
| `cacheRepair()` | `public static function cacheRepair() :array` | [Zeile 1303](../../PHP/gbdb_framework/runtime/facade.php#L1303) |
| `apcuCachePrepared()` | `public static function apcuCachePrepared() :array` | [Zeile 1307](../../PHP/gbdb_framework/runtime/facade.php#L1307) |
| `redisAdapterPrepared()` | `public static function redisAdapterPrepared(array $c = []) :array` | [Zeile 1311](../../PHP/gbdb_framework/runtime/facade.php#L1311) |
| `fileCacheSet()` | `public static function fileCacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool` | [Zeile 1315](../../PHP/gbdb_framework/runtime/facade.php#L1315) |
| `fileCacheGet()` | `public static function fileCacheGet(string $key, mixed $default = null) :mixed` | [Zeile 1319](../../PHP/gbdb_framework/runtime/facade.php#L1319) |
| `cacheTests()` | `public static function cacheTests() :array` | [Zeile 1323](../../PHP/gbdb_framework/runtime/facade.php#L1323) |
| `atomicIncrement()` | `public static function atomicIncrement(string $db, string $table, mixed $id, string $column, int\|float $step = 1) :array` | [Zeile 1327](../../PHP/gbdb_framework/runtime/facade.php#L1327) |
| `atomicDecrement()` | `public static function atomicDecrement(string $db, string $table, mixed $id, string $column, int\|float $step = 1) :array` | [Zeile 1331](../../PHP/gbdb_framework/runtime/facade.php#L1331) |
| `compareAndSwap()` | `public static function compareAndSwap(string $db, string $table, mixed $id, string $column, mixed $expected, mixed $newValue) :array` | [Zeile 1335](../../PHP/gbdb_framework/runtime/facade.php#L1335) |
| `ensureCounterTable()` | `public static function ensureCounterTable(string $db = 'system', string $table = 'counters') :bool` | [Zeile 1339](../../PHP/gbdb_framework/runtime/facade.php#L1339) |
| `counterValue()` | `public static function counterValue(string $scope, string $name, int\|float $delta = 0, string $db = 'system', string $table = 'counters') :array` | [Zeile 1343](../../PHP/gbdb_framework/runtime/facade.php#L1343) |
| `prepareShardedCounters()` | `public static function prepareShardedCounters(int $shards = 16) :array` | [Zeile 1347](../../PHP/gbdb_framework/runtime/facade.php#L1347) |
| `prepareDistributedSafeCounters()` | `public static function prepareDistributedSafeCounters(array $c = []) :array` | [Zeile 1351](../../PHP/gbdb_framework/runtime/facade.php#L1351) |
| `likeCounter()` | `public static function likeCounter(string $o, int $d = 1) :array` | [Zeile 1355](../../PHP/gbdb_framework/runtime/facade.php#L1355) |
| `commentCounter()` | `public static function commentCounter(string $o, int $d = 1) :array` | [Zeile 1359](../../PHP/gbdb_framework/runtime/facade.php#L1359) |
| `viewCounter()` | `public static function viewCounter(string $o, int $d = 1) :array` | [Zeile 1363](../../PHP/gbdb_framework/runtime/facade.php#L1363) |
| `followerCounter()` | `public static function followerCounter(string $o, int $d = 1) :array` | [Zeile 1367](../../PHP/gbdb_framework/runtime/facade.php#L1367) |
| `unreadCounter()` | `public static function unreadCounter(string $o, int $d = 1) :array` | [Zeile 1371](../../PHP/gbdb_framework/runtime/facade.php#L1371) |
| `counterRepair()` | `public static function counterRepair(string $db = 'system', string $table = 'counters') :array` | [Zeile 1375](../../PHP/gbdb_framework/runtime/facade.php#L1375) |
| `counterRecalculation()` | `public static function counterRecalculation(string $db, string $table, string $groupColumn, string $scope) :array` | [Zeile 1379](../../PHP/gbdb_framework/runtime/facade.php#L1379) |
| `cleanupJournals()` | `public static function cleanupJournals(int $days = 7) :array` | [Zeile 1383](../../PHP/gbdb_framework/runtime/facade.php#L1383) |
| `cleanupVersions()` | `public static function cleanupVersions(int $days = 30) :array` | [Zeile 1387](../../PHP/gbdb_framework/runtime/facade.php#L1387) |
| `refreshStats()` | `public static function refreshStats(array $o = []) :array` | [Zeile 1391](../../PHP/gbdb_framework/runtime/facade.php#L1391) |
| `enqueueJob()` | `public static function enqueueJob(string $type, array $payload = [], array $options = []) :string` | [Zeile 1395](../../PHP/gbdb_framework/runtime/facade.php#L1395) |
| `delayedJob()` | `public static function delayedJob(string $type, array $payload, int $delaySeconds, array $options = []) :string` | [Zeile 1399](../../PHP/gbdb_framework/runtime/facade.php#L1399) |
| `priorityJob()` | `public static function priorityJob(string $type, array $payload, int $priority = 10, array $options = []) :string` | [Zeile 1403](../../PHP/gbdb_framework/runtime/facade.php#L1403) |
| `scheduleJob()` | `public static function scheduleJob(string $name, string $type, array $payload, string $cronOrInterval, array $options = []) :array` | [Zeile 1407](../../PHP/gbdb_framework/runtime/facade.php#L1407) |
| `recurringJob()` | `public static function recurringJob(string $name, string $type, array $payload, int $everySeconds, array $options = []) :array` | [Zeile 1411](../../PHP/gbdb_framework/runtime/facade.php#L1411) |
| `claimJob()` | `public static function claimJob(string $workerId, string $queue = 'default') :array` | [Zeile 1415](../../PHP/gbdb_framework/runtime/facade.php#L1415) |
| `workerHeartbeat()` | `public static function workerHeartbeat(string $jobId, string $workerId) :bool` | [Zeile 1419](../../PHP/gbdb_framework/runtime/facade.php#L1419) |
| `processDueJobs()` | `public static function processDueJobs(int $limit = 25, string $queue = '') :array` | [Zeile 1423](../../PHP/gbdb_framework/runtime/facade.php#L1423) |
| `workQueue()` | `public static function workQueue(int $limit = 25, string $queue = '') :array` | [Zeile 1427](../../PHP/gbdb_framework/runtime/facade.php#L1427) |
| `finishJob()` | `public static function finishJob(string $jobId, bool $ok = true, array $result = []) :array` | [Zeile 1431](../../PHP/gbdb_framework/runtime/facade.php#L1431) |
| `retryFailedJobs()` | `public static function retryFailedJobs(int $limit = 50) :array` | [Zeile 1435](../../PHP/gbdb_framework/runtime/facade.php#L1435) |
| `queueStats()` | `public static function queueStats() :array` | [Zeile 1439](../../PHP/gbdb_framework/runtime/facade.php#L1439) |
| `jobDashboard()` | `public static function jobDashboard(int $limit = 50) :array` | [Zeile 1443](../../PHP/gbdb_framework/runtime/facade.php#L1443) |
| `queueUiPrepared()` | `public static function queueUiPrepared() :array` | [Zeile 1447](../../PHP/gbdb_framework/runtime/facade.php#L1447) |
| `queueCliPrepared()` | `public static function queueCliPrepared() :array` | [Zeile 1451](../../PHP/gbdb_framework/runtime/facade.php#L1451) |
| `srvQueueBridge()` | `public static function srvQueueBridge(string $action, array $payload = []) :array` | [Zeile 1455](../../PHP/gbdb_framework/runtime/facade.php#L1455) |
| `backgroundIndexUpdate()` | `public static function backgroundIndexUpdate(string $db, string $table) :string` | [Zeile 1459](../../PHP/gbdb_framework/runtime/facade.php#L1459) |
| `backgroundFeedFanout()` | `public static function backgroundFeedFanout(array $payload) :string` | [Zeile 1463](../../PHP/gbdb_framework/runtime/facade.php#L1463) |
| `backgroundMailSending()` | `public static function backgroundMailSending(array $payload) :string` | [Zeile 1467](../../PHP/gbdb_framework/runtime/facade.php#L1467) |
| `backgroundMediaProcessing()` | `public static function backgroundMediaProcessing(array $payload) :string` | [Zeile 1471](../../PHP/gbdb_framework/runtime/facade.php#L1471) |
| `backgroundBackups()` | `public static function backgroundBackups(array $payload = []) :string` | [Zeile 1475](../../PHP/gbdb_framework/runtime/facade.php#L1475) |
| `defineTrigger()` | `public static function defineTrigger(string $db, string $table, string $event, string $name, array $definition) :array` | [Zeile 1479](../../PHP/gbdb_framework/runtime/facade.php#L1479) |
| `runDataTriggers()` | `public static function runDataTriggers(string $event, string $db, string $table, array $context = []) :array` | [Zeile 1483](../../PHP/gbdb_framework/runtime/facade.php#L1483) |
| `enqueueEvent()` | `public static function enqueueEvent(string $event, string $db, string $table, array $payload = []) :string` | [Zeile 1487](../../PHP/gbdb_framework/runtime/facade.php#L1487) |
| `eventLog()` | `public static function eventLog(string $event, string $db, string $table, array $payload = []) :bool` | [Zeile 1491](../../PHP/gbdb_framework/runtime/facade.php#L1491) |
| `triggerDebug()` | `public static function triggerDebug(string $db, string $table) :array` | [Zeile 1495](../../PHP/gbdb_framework/runtime/facade.php#L1495) |
| `schemaDefinedTriggers()` | `public static function schemaDefinedTriggers(string $db, string $table) :array` | [Zeile 1499](../../PHP/gbdb_framework/runtime/facade.php#L1499) |
| `greenqlTriggerFunction()` | `public static function greenqlTriggerFunction(string $name, string $script) :array` | [Zeile 1503](../../PHP/gbdb_framework/runtime/facade.php#L1503) |
| `defineView()` | `public static function defineView(string $name, string $query, array $options = []) :array` | [Zeile 1507](../../PHP/gbdb_framework/runtime/facade.php#L1507) |
| `refreshView()` | `public static function refreshView(string $name) :array` | [Zeile 1511](../../PHP/gbdb_framework/runtime/facade.php#L1511) |
| `getView()` | `public static function getView(string $name, bool $refresh = false) :mixed` | [Zeile 1515](../../PHP/gbdb_framework/runtime/facade.php#L1515) |
| `viewQueryPlanner()` | `public static function viewQueryPlanner(string $name) :array` | [Zeile 1519](../../PHP/gbdb_framework/runtime/facade.php#L1519) |
| `materializedViewIndex()` | `public static function materializedViewIndex(string $name, array $columns) :array` | [Zeile 1523](../../PHP/gbdb_framework/runtime/facade.php#L1523) |
| `defineProcedure()` | `public static function defineProcedure(string $name, string $script, array $options = []) :array` | [Zeile 1527](../../PHP/gbdb_framework/runtime/facade.php#L1527) |
| `callProcedure()` | `public static function callProcedure(string $name, array $params = []) :mixed` | [Zeile 1531](../../PHP/gbdb_framework/runtime/facade.php#L1531) |
| `procedureLogs()` | `public static function procedureLogs(string $name = '') :array` | [Zeile 1535](../../PHP/gbdb_framework/runtime/facade.php#L1535) |
| `procedureDebug()` | `public static function procedureDebug(string $name) :array` | [Zeile 1539](../../PHP/gbdb_framework/runtime/facade.php#L1539) |
| `defineDbRole()` | `public static function defineDbRole(string $role, array $permissions = []) :array` | [Zeile 1543](../../PHP/gbdb_framework/runtime/facade.php#L1543) |
| `defineDbUser()` | `public static function defineDbUser(string $user, array $roles = [ 0 => 'readonly', ], array $options = []) :array` | [Zeile 1547](../../PHP/gbdb_framework/runtime/facade.php#L1547) |
| `defineApiKeyScope()` | `public static function defineApiKeyScope(string $keyId, array $scopes) :array` | [Zeile 1553](../../PHP/gbdb_framework/runtime/facade.php#L1553) |
| `defaultDbRoles()` | `public static function defaultDbRoles() :array` | [Zeile 1557](../../PHP/gbdb_framework/runtime/facade.php#L1557) |
| `checkPermission()` | `public static function checkPermission(string $user, string $action, string $db = '', string $table = '', array $row = []) :bool` | [Zeile 1561](../../PHP/gbdb_framework/runtime/facade.php#L1561) |
| `permissionAudit()` | `public static function permissionAudit(string $user, string $action, array $context = []) :bool` | [Zeile 1565](../../PHP/gbdb_framework/runtime/facade.php#L1565) |
| `permissionDebug()` | `public static function permissionDebug(string $user) :array` | [Zeile 1569](../../PHP/gbdb_framework/runtime/facade.php#L1569) |
| `definePolicy()` | `public static function definePolicy(string $name, array $rules) :array` | [Zeile 1573](../../PHP/gbdb_framework/runtime/facade.php#L1573) |
| `evaluatePolicy()` | `public static function evaluatePolicy(string $action, array $row, array $context = []) :array` | [Zeile 1577](../../PHP/gbdb_framework/runtime/facade.php#L1577) |
| `ownerUidPolicy()` | `public static function ownerUidPolicy(string $name = 'owner_uid') :array` | [Zeile 1581](../../PHP/gbdb_framework/runtime/facade.php#L1581) |
| `visibilityPolicy()` | `public static function visibilityPolicy() :array` | [Zeile 1585](../../PHP/gbdb_framework/runtime/facade.php#L1585) |
| `tenantIsolationPolicy()` | `public static function tenantIsolationPolicy(string $tenantField = 'tenant_id') :array` | [Zeile 1589](../../PHP/gbdb_framework/runtime/facade.php#L1589) |
| `policyDebugOutput()` | `public static function policyDebugOutput() :array` | [Zeile 1593](../../PHP/gbdb_framework/runtime/facade.php#L1593) |
| `auditDataChange()` | `public static function auditDataChange(string $action, string $db, string $table, array $old = [], array $new = [], array $context = []) :bool` | [Zeile 1597](../../PHP/gbdb_framework/runtime/facade.php#L1597) |
| `auditExport()` | `public static function auditExport(array $filter = []) :array` | [Zeile 1601](../../PHP/gbdb_framework/runtime/facade.php#L1601) |
| `auditSearch()` | `public static function auditSearch(string $needle) :array` | [Zeile 1605](../../PHP/gbdb_framework/runtime/facade.php#L1605) |
| `auditRetention()` | `public static function auditRetention(int $days = 365) :array` | [Zeile 1609](../../PHP/gbdb_framework/runtime/facade.php#L1609) |
| `auditIntegrity()` | `public static function auditIntegrity() :array` | [Zeile 1613](../../PHP/gbdb_framework/runtime/facade.php#L1613) |
| `markPiiField()` | `public static function markPiiField(string $db, string $table, string $field, string $classification = 'pii') :array` | [Zeile 1617](../../PHP/gbdb_framework/runtime/facade.php#L1617) |
| `userDataExport()` | `public static function userDataExport(string $userField, mixed $userId, array $tables = []) :array` | [Zeile 1621](../../PHP/gbdb_framework/runtime/facade.php#L1621) |
| `userDataDelete()` | `public static function userDataDelete(string $userField, mixed $userId, array $tables = []) :array` | [Zeile 1625](../../PHP/gbdb_framework/runtime/facade.php#L1625) |
| `userDataRedact()` | `public static function userDataRedact(string $db, string $table, string $where, mixed $is, array $fields, string $replacement = '[redacted]') :array` | [Zeile 1629](../../PHP/gbdb_framework/runtime/facade.php#L1629) |
| `retentionPolicy()` | `public static function retentionPolicy(string $name, array $rule) :array` | [Zeile 1633](../../PHP/gbdb_framework/runtime/facade.php#L1633) |
| `consentHistory()` | `public static function consentHistory(string $subject, array $entry) :array` | [Zeile 1637](../../PHP/gbdb_framework/runtime/facade.php#L1637) |
| `encryptionConfig()` | `public static function encryptionConfig(array $cfg = []) :array` | [Zeile 1641](../../PHP/gbdb_framework/runtime/facade.php#L1641) |
| `deriveKey()` | `public static function deriveKey(string $secret, string $salt = '', int $version = 1) :string` | [Zeile 1645](../../PHP/gbdb_framework/runtime/facade.php#L1645) |
| `rotateKey()` | `public static function rotateKey(string $name = 'default') :array` | [Zeile 1649](../../PHP/gbdb_framework/runtime/facade.php#L1649) |
| `scrubSecrets()` | `public static function scrubSecrets(mixed $value) :mixed` | [Zeile 1653](../../PHP/gbdb_framework/runtime/facade.php#L1653) |
| `securityTests()` | `public static function securityTests() :array` | [Zeile 1657](../../PHP/gbdb_framework/runtime/facade.php#L1657) |
| `blobPath()` | `public static function blobPath(string $hash = '', bool $ensure = true) :string` | [Zeile 1661](../../PHP/gbdb_framework/runtime/facade.php#L1661) |
| `putBlob()` | `public static function putBlob(string $sourcePath, string $visibility = 'private', array $meta = []) :array` | [Zeile 1665](../../PHP/gbdb_framework/runtime/facade.php#L1665) |
| `signedUrl()` | `public static function signedUrl(string $hash, int $ttl = 300) :string` | [Zeile 1669](../../PHP/gbdb_framework/runtime/facade.php#L1669) |
| `temporaryUrl()` | `public static function temporaryUrl(string $hash, int $ttl = 300) :string` | [Zeile 1673](../../PHP/gbdb_framework/runtime/facade.php#L1673) |
| `validateMime()` | `public static function validateMime(string $mime, array $allowed = [ 0 => 'image/jpeg', 1 => 'image/png', 2 => 'image/webp', 3 => 'application/pdf', ]) :bool` | [Zeile 1677](../../PHP/gbdb_framework/runtime/facade.php#L1677) |
| `deleteMedia()` | `public static function deleteMedia(string $hash) :array` | [Zeile 1686](../../PHP/gbdb_framework/runtime/facade.php#L1686) |
| `orphanMediaCleanup()` | `public static function orphanMediaCleanup() :array` | [Zeile 1690](../../PHP/gbdb_framework/runtime/facade.php#L1690) |
| `mediaPermissions()` | `public static function mediaPermissions(string $hash, array $permissions) :array` | [Zeile 1694](../../PHP/gbdb_framework/runtime/facade.php#L1694) |
| `mediaAudit()` | `public static function mediaAudit(string $hash, string $action, array $context = []) :bool` | [Zeile 1698](../../PHP/gbdb_framework/runtime/facade.php#L1698) |
| `mediaProcessingPrepared()` | `public static function mediaProcessingPrepared(string $hash) :string` | [Zeile 1702](../../PHP/gbdb_framework/runtime/facade.php#L1702) |
| `socialSchemaPatterns()` | `public static function socialSchemaPatterns() :array` | [Zeile 1706](../../PHP/gbdb_framework/runtime/facade.php#L1706) |
| `installSocialPatterns()` | `public static function installSocialPatterns(string $db = 'social') :array` | [Zeile 1710](../../PHP/gbdb_framework/runtime/facade.php#L1710) |
| `softDelete()` | `public static function softDelete(string $db, string $table, string $where, mixed $is) :bool` | [Zeile 1714](../../PHP/gbdb_framework/runtime/facade.php#L1714) |
| `contentVisibilityRules()` | `public static function contentVisibilityRules(array $rules = []) :array` | [Zeile 1718](../../PHP/gbdb_framework/runtime/facade.php#L1718) |
| `feedFanoutJob()` | `public static function feedFanoutJob(string $postId, array $targets) :string` | [Zeile 1722](../../PHP/gbdb_framework/runtime/facade.php#L1722) |
| `fanoutOnRead()` | `public static function fanoutOnRead(array $context) :array` | [Zeile 1726](../../PHP/gbdb_framework/runtime/facade.php#L1726) |
| `hybridFeed()` | `public static function hybridFeed(array $context) :array` | [Zeile 1730](../../PHP/gbdb_framework/runtime/facade.php#L1730) |
| `moderationQueue()` | `public static function moderationQueue(string $objectType, string $objectId, string $reason, array $context = []) :string` | [Zeile 1734](../../PHP/gbdb_framework/runtime/facade.php#L1734) |
| `patternTemplate()` | `public static function patternTemplate(string $name = 'intranet') :array` | [Zeile 1738](../../PHP/gbdb_framework/runtime/facade.php#L1738) |
| `listPatterns()` | `public static function listPatterns(bool $includeInactive = true) :array` | [Zeile 1742](../../PHP/gbdb_framework/runtime/facade.php#L1742) |
| `getPattern()` | `public static function getPattern(string $name) :array` | [Zeile 1746](../../PHP/gbdb_framework/runtime/facade.php#L1746) |
| `savePattern()` | `public static function savePattern(array $pattern) :array` | [Zeile 1750](../../PHP/gbdb_framework/runtime/facade.php#L1750) |
| `deletePattern()` | `public static function deletePattern(string $name) :array` | [Zeile 1754](../../PHP/gbdb_framework/runtime/facade.php#L1754) |
| `renamePattern()` | `public static function renamePattern(string $name, string $newName) :array` | [Zeile 1758](../../PHP/gbdb_framework/runtime/facade.php#L1758) |
| `setPatternActive()` | `public static function setPatternActive(string $name, bool $active) :array` | [Zeile 1762](../../PHP/gbdb_framework/runtime/facade.php#L1762) |
| `installPattern()` | `public static function installPattern(string $patternName, string $instanceName, bool $allowInactive = false) :array` | [Zeile 1766](../../PHP/gbdb_framework/runtime/facade.php#L1766) |
| `installIntranetPattern()` | `public static function installIntranetPattern(string $database = 'intranet') :array` | [Zeile 1770](../../PHP/gbdb_framework/runtime/facade.php#L1770) |
| `intranetAudit()` | `public static function intranetAudit(string $action, array $payload = [], string $database = 'intranet') :array` | [Zeile 1774](../../PHP/gbdb_framework/runtime/facade.php#L1774) |
| `intranetDirectorySearch()` | `public static function intranetDirectorySearch(string $query, string $database = 'intranet', int $limit = 50) :array` | [Zeile 1778](../../PHP/gbdb_framework/runtime/facade.php#L1778) |
| `defineDocumentPermission()` | `public static function defineDocumentPermission(string $docId, string $subjectType, string $subjectId, string $permission, string $database = 'intranet') :array` | [Zeile 1782](../../PHP/gbdb_framework/runtime/facade.php#L1782) |
| `defineRetentionRule()` | `public static function defineRetentionRule(string $name, int $days, string $action = 'archive', string $database = 'intranet') :array` | [Zeile 1786](../../PHP/gbdb_framework/runtime/facade.php#L1786) |
| `prepareSsoAdapter()` | `public static function prepareSsoAdapter(string $type, array $config = []) :array` | [Zeile 1790](../../PHP/gbdb_framework/runtime/facade.php#L1790) |
| `prepareLdapAdapter()` | `public static function prepareLdapAdapter(array $config = []) :array` | [Zeile 1794](../../PHP/gbdb_framework/runtime/facade.php#L1794) |
| `prepareSamlAdapter()` | `public static function prepareSamlAdapter(array $config = []) :array` | [Zeile 1798](../../PHP/gbdb_framework/runtime/facade.php#L1798) |
| `prepareOidcAdapter()` | `public static function prepareOidcAdapter(array $config = []) :array` | [Zeile 1802](../../PHP/gbdb_framework/runtime/facade.php#L1802) |
| `prepareOAuth2Adapter()` | `public static function prepareOAuth2Adapter(array $config = []) :array` | [Zeile 1806](../../PHP/gbdb_framework/runtime/facade.php#L1806) |
| `externalUserSync()` | `public static function externalUserSync(array $users, string $database = 'intranet') :array` | [Zeile 1810](../../PHP/gbdb_framework/runtime/facade.php#L1810) |
| `groupSync()` | `public static function groupSync(array $groups, string $database = 'intranet') :array` | [Zeile 1814](../../PHP/gbdb_framework/runtime/facade.php#L1814) |
| `departmentSync()` | `public static function departmentSync(array $departments, string $database = 'intranet') :array` | [Zeile 1818](../../PHP/gbdb_framework/runtime/facade.php#L1818) |
| `roleMapping()` | `public static function roleMapping(array $mapping) :array` | [Zeile 1822](../../PHP/gbdb_framework/runtime/facade.php#L1822) |
| `ssoAudit()` | `public static function ssoAudit(string $provider, string $subject, string $action, array $payload = []) :array` | [Zeile 1826](../../PHP/gbdb_framework/runtime/facade.php#L1826) |
| `createServiceAccount()` | `public static function createServiceAccount(string $name, array $scopes = [], array $options = []) :array` | [Zeile 1830](../../PHP/gbdb_framework/runtime/facade.php#L1830) |
| `createApiToken()` | `public static function createApiToken(string $subject, array $scopes = [], int $ttlSeconds = 2592000) :array` | [Zeile 1834](../../PHP/gbdb_framework/runtime/facade.php#L1834) |
| `validateApiToken()` | `public static function validateApiToken(string $token, string $scope = '') :array` | [Zeile 1838](../../PHP/gbdb_framework/runtime/facade.php#L1838) |
| `rotateApiToken()` | `public static function rotateApiToken(string $tokenId, int $ttlSeconds = 2592000) :array` | [Zeile 1842](../../PHP/gbdb_framework/runtime/facade.php#L1842) |
| `registerSession()` | `public static function registerSession(string $uid, array $device = [], int $ttlSeconds = 172800) :array` | [Zeile 1846](../../PHP/gbdb_framework/runtime/facade.php#L1846) |
| `forceLogout()` | `public static function forceLogout(string $uid) :array` | [Zeile 1850](../../PHP/gbdb_framework/runtime/facade.php#L1850) |
| `sessionList()` | `public static function sessionList(string $uid = '') :array` | [Zeile 1854](../../PHP/gbdb_framework/runtime/facade.php#L1854) |
| `tenantPolicy()` | `public static function tenantPolicy(string $tenant, array $policy = []) :array` | [Zeile 1858](../../PHP/gbdb_framework/runtime/facade.php#L1858) |
| `tenantQuotas()` | `public static function tenantQuotas(string $tenant, array $quotas = []) :array` | [Zeile 1862](../../PHP/gbdb_framework/runtime/facade.php#L1862) |
| `tenantRestore()` | `public static function tenantRestore(string $tenant, string $backupPath) :array` | [Zeile 1866](../../PHP/gbdb_framework/runtime/facade.php#L1866) |
| `tenantExport()` | `public static function tenantExport(string $tenant, string $target = '') :array` | [Zeile 1870](../../PHP/gbdb_framework/runtime/facade.php#L1870) |
| `tenantDelete()` | `public static function tenantDelete(string $tenant, bool $force = false) :array` | [Zeile 1874](../../PHP/gbdb_framework/runtime/facade.php#L1874) |
| `prepareTenantMigration()` | `public static function prepareTenantMigration(string $tenant, string $targetShard, array $options = []) :array` | [Zeile 1878](../../PHP/gbdb_framework/runtime/facade.php#L1878) |
| `tenantStorageStats()` | `public static function tenantStorageStats(string $tenant = '') :array` | [Zeile 1882](../../PHP/gbdb_framework/runtime/facade.php#L1882) |
| `tenantAdminRoles()` | `public static function tenantAdminRoles(string $tenant, array $roles) :array` | [Zeile 1886](../../PHP/gbdb_framework/runtime/facade.php#L1886) |
| `tenantEncryptionKeysPrepared()` | `public static function tenantEncryptionKeysPrepared(string $tenant, array $options = []) :array` | [Zeile 1890](../../PHP/gbdb_framework/runtime/facade.php#L1890) |
| `quotaLimits()` | `public static function quotaLimits(array $limits = []) :array` | [Zeile 1894](../../PHP/gbdb_framework/runtime/facade.php#L1894) |
| `quotaCheck()` | `public static function quotaCheck(string $database = '', string $table = '') :array` | [Zeile 1898](../../PHP/gbdb_framework/runtime/facade.php#L1898) |
| `quotaDashboard()` | `public static function quotaDashboard(bool $includeTenants = false) :array` | [Zeile 1902](../../PHP/gbdb_framework/runtime/facade.php#L1902) |
| `rateLimit()` | `public static function rateLimit(string $bucket, string $key, int $limit, int $windowSeconds = 60) :array` | [Zeile 1906](../../PHP/gbdb_framework/runtime/facade.php#L1906) |
| `configureRateLimits()` | `public static function configureRateLimits(array $limits) :array` | [Zeile 1910](../../PHP/gbdb_framework/runtime/facade.php#L1910) |
| `rateLimitLogs()` | `public static function rateLimitLogs(int $limit = 100) :array` | [Zeile 1914](../../PHP/gbdb_framework/runtime/facade.php#L1914) |
| `exportRows()` | `public static function exportRows(string $database, string $table, string $format = 'json', string $target = '') :array` | [Zeile 1918](../../PHP/gbdb_framework/runtime/facade.php#L1918) |
| `importRows()` | `public static function importRows(string $database, string $table, string $file, string $format = 'json', bool $rollback = true) :array` | [Zeile 1922](../../PHP/gbdb_framework/runtime/facade.php#L1922) |
| `validateImportRows()` | `public static function validateImportRows(string $database, string $table, array $rows) :array` | [Zeile 1926](../../PHP/gbdb_framework/runtime/facade.php#L1926) |
| `gbdbDump()` | `public static function gbdbDump(string $target = '') :array` | [Zeile 1930](../../PHP/gbdb_framework/runtime/facade.php#L1930) |
| `gbdbRestore()` | `public static function gbdbRestore(string $backupPath) :array` | [Zeile 1934](../../PHP/gbdb_framework/runtime/facade.php#L1934) |
| `streamingImport()` | `public static function streamingImport(string $database, string $table, string $file, string $format = 'ndjson', int $chunkSize = 500) :array` | [Zeile 1938](../../PHP/gbdb_framework/runtime/facade.php#L1938) |
| `streamingExport()` | `public static function streamingExport(string $database, string $table, string $format = 'ndjson', string $target = '') :array` | [Zeile 1942](../../PHP/gbdb_framework/runtime/facade.php#L1942) |
| `exportPermissions()` | `public static function exportPermissions(array $rules = []) :array` | [Zeile 1946](../../PHP/gbdb_framework/runtime/facade.php#L1946) |
| `exportLogs()` | `public static function exportLogs(int $limit = 100) :array` | [Zeile 1950](../../PHP/gbdb_framework/runtime/facade.php#L1950) |
| `sqlErsatzFeatureMatrix()` | `public static function sqlErsatzFeatureMatrix() :array` | [Zeile 1954](../../PHP/gbdb_framework/runtime/facade.php#L1954) |
| `sqlErsatzFeatureStatus()` | `public static function sqlErsatzFeatureStatus(string $feature) :array` | [Zeile 1958](../../PHP/gbdb_framework/runtime/facade.php#L1958) |
| `prepareSqlAdapter()` | `public static function prepareSqlAdapter(string $type, array $config = []) :array` | [Zeile 1962](../../PHP/gbdb_framework/runtime/facade.php#L1962) |
| `prepareMySQLImportAdapter()` | `public static function prepareMySQLImportAdapter(array $config = []) :array` | [Zeile 1966](../../PHP/gbdb_framework/runtime/facade.php#L1966) |
| `preparePostgreSQLImportAdapter()` | `public static function preparePostgreSQLImportAdapter(array $config = []) :array` | [Zeile 1970](../../PHP/gbdb_framework/runtime/facade.php#L1970) |
| `prepareSQLiteImportAdapter()` | `public static function prepareSQLiteImportAdapter(array $config = []) :array` | [Zeile 1974](../../PHP/gbdb_framework/runtime/facade.php#L1974) |
| `prepareSqlCompatibilityLayer()` | `public static function prepareSqlCompatibilityLayer(array $config = []) :array` | [Zeile 1978](../../PHP/gbdb_framework/runtime/facade.php#L1978) |
| `prepareSqlToGreenQLTranslator()` | `public static function prepareSqlToGreenQLTranslator(array $config = []) :array` | [Zeile 1982](../../PHP/gbdb_framework/runtime/facade.php#L1982) |
| `preparePdoLikeAdapter()` | `public static function preparePdoLikeAdapter(array $config = []) :array` | [Zeile 1986](../../PHP/gbdb_framework/runtime/facade.php#L1986) |
| `adminUiData()` | `public static function adminUiData() :array` | [Zeile 1990](../../PHP/gbdb_framework/runtime/facade.php#L1990) |
| `adminUiQueryPlan()` | `public static function adminUiQueryPlan(string $greenql) :array` | [Zeile 1994](../../PHP/gbdb_framework/runtime/facade.php#L1994) |
| `cliCommand()` | `public static function cliCommand(string $command, array $args = []) :array` | [Zeile 1998](../../PHP/gbdb_framework/runtime/facade.php#L1998) |
| `transactionApi()` | `public static function transactionApi(callable $callback) :array` | [Zeile 2002](../../PHP/gbdb_framework/runtime/facade.php#L2002) |
| `cursorApi()` | `public static function cursorApi(string $database, string $table, int $chunkSize = 500) :Generator` | [Zeile 2006](../../PHP/gbdb_framework/runtime/facade.php#L2006) |
| `indexApi()` | `public static function indexApi(string $database, string $table, string $column) :array` | [Zeile 2010](../../PHP/gbdb_framework/runtime/facade.php#L2010) |
| `schemaApi()` | `public static function schemaApi(string $database, string $table, array $columns) :array` | [Zeile 2014](../../PHP/gbdb_framework/runtime/facade.php#L2014) |
| `migrationApi()` | `public static function migrationApi(string $database, string $table, string $id, callable $cb) :array` | [Zeile 2018](../../PHP/gbdb_framework/runtime/facade.php#L2018) |
| `backupApi()` | `public static function backupApi(string $type = 'full', array $args = []) :array` | [Zeile 2022](../../PHP/gbdb_framework/runtime/facade.php#L2022) |
| `repairApi()` | `public static function repairApi(string $database = '', string $table = '') :array` | [Zeile 2026](../../PHP/gbdb_framework/runtime/facade.php#L2026) |
| `explainApi()` | `public static function explainApi(string $database, string $table, array $where = []) :array` | [Zeile 2030](../../PHP/gbdb_framework/runtime/facade.php#L2030) |
| `statsApi()` | `public static function statsApi() :array` | [Zeile 2034](../../PHP/gbdb_framework/runtime/facade.php#L2034) |
| `policyApi()` | `public static function policyApi(string $name, array $rules = []) :array` | [Zeile 2038](../../PHP/gbdb_framework/runtime/facade.php#L2038) |
| `eventApi()` | `public static function eventApi(string $event, string $db, string $table, array $payload = []) :array` | [Zeile 2042](../../PHP/gbdb_framework/runtime/facade.php#L2042) |
| `queueApi()` | `public static function queueApi(string $action, array $payload = []) :array` | [Zeile 2046](../../PHP/gbdb_framework/runtime/facade.php#L2046) |
| `mediaApi()` | `public static function mediaApi(array $payload = []) :array` | [Zeile 2050](../../PHP/gbdb_framework/runtime/facade.php#L2050) |
| `searchApi()` | `public static function searchApi(string $database, string $table, string $query, array $columns = []) :array` | [Zeile 2054](../../PHP/gbdb_framework/runtime/facade.php#L2054) |
| `cacheApi()` | `public static function cacheApi(string $action, string $key = '', mixed $value = null) :array` | [Zeile 2058](../../PHP/gbdb_framework/runtime/facade.php#L2058) |
| `documentationIndex()` | `public static function documentationIndex() :array` | [Zeile 2062](../../PHP/gbdb_framework/runtime/facade.php#L2062) |
| `enterpriseSelfTest()` | `public static function enterpriseSelfTest() :array` | [Zeile 2066](../../PHP/gbdb_framework/runtime/facade.php#L2066) |
| `get()` | `public static function get(string $base, string $table, mixed $search = false, mixed $where = '', mixed $is = '', array $options = []) :mixed` | [Zeile 2070](../../PHP/gbdb_framework/runtime/facade.php#L2070) |
| `existsInstance()` | `public static function existsInstance(string $instance) :bool` | [Zeile 2074](../../PHP/gbdb_framework/runtime/facade.php#L2074) |
| `create()` | `public static function create(string $base, string $table = '', bool $useDataTypes = false, object\|array $rows = []) :bool` | [Zeile 2078](../../PHP/gbdb_framework/runtime/facade.php#L2078) |
| `exists()` | `public static function exists(string $base, ?string $table = null, mixed $where = '', mixed $is = '') :bool` | [Zeile 2082](../../PHP/gbdb_framework/runtime/facade.php#L2082) |
| `edit()` | `public static function edit(string $base, string $table, mixed $where, mixed $is, object\|array $newDataAsObject, bool $recursive = false) :bool` | [Zeile 2086](../../PHP/gbdb_framework/runtime/facade.php#L2086) |
| `delete()` | `public static function delete(string $base, ?string $table = null, mixed $where = '', mixed $is = '', bool $recursive = false) :bool` | [Zeile 2090](../../PHP/gbdb_framework/runtime/facade.php#L2090) |
| `getNextId()` | `public static function getNextId(string $base, string $table) :int` | [Zeile 2094](../../PHP/gbdb_framework/runtime/facade.php#L2094) |
| `fullTextSearch()` | `public static function fullTextSearch(string $base, string $table, string $text) :array` | [Zeile 2098](../../PHP/gbdb_framework/runtime/facade.php#L2098) |
| `renameTable()` | `public static function renameTable(string $base, string $table, string $newTableName) :bool` | [Zeile 2102](../../PHP/gbdb_framework/runtime/facade.php#L2102) |
| `renameBase()` | `public static function renameBase(string $base, string $newBaseName) :bool` | [Zeile 2106](../../PHP/gbdb_framework/runtime/facade.php#L2106) |
| `renameInstance()` | `public static function renameInstance(string $instance, string $newInstanceName) :bool` | [Zeile 2110](../../PHP/gbdb_framework/runtime/facade.php#L2110) |
| `moveBase()` | `public static function moveBase(string $base, string $fromInstance, string $toInstance) :bool` | [Zeile 2114](../../PHP/gbdb_framework/runtime/facade.php#L2114) |
| `moveTable()` | `public static function moveTable(string $instance, string $base, string $table, string $toInstance, string $toBase) :bool` | [Zeile 2118](../../PHP/gbdb_framework/runtime/facade.php#L2118) |
| `createBackup()` | `public static function createBackup(string $pathToBackupDir = '') :array` | [Zeile 2122](../../PHP/gbdb_framework/runtime/facade.php#L2122) |
| `runFile()` | `public static function runFile(string $pathToFileWithFilename, object\|array $parametersAsObject = []) :array` | [Zeile 2126](../../PHP/gbdb_framework/runtime/facade.php#L2126) |
| `backendStatus()` | `public static function backendStatus() :array` | [Zeile 2131](../../PHP/gbdb_framework/runtime/facade.php#L2131) |
| `switchBackend()` | `public static function switchBackend(string $backend) :array` | [Zeile 2136](../../PHP/gbdb_framework/runtime/facade.php#L2136) |
| `synchronizeSQL()` | `public static function synchronizeSQL() :array` | [Zeile 2141](../../PHP/gbdb_framework/runtime/facade.php#L2141) |

## GBDBEngine

Klasse · Quelle: [GBDB_GQL/db_engine/gbdb.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb.php#L34)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `update()` | `public static function update(?string $pathOfOldDB = null) :array` | [Zeile 87](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb.php#L87) |
| `migrate()` | `public static function migrate(?string $pathOfOldDB = null) :array` | [Zeile 126](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb.php#L126) |

## GBDBInstanceAccessDenied

Klasse · Quelle: [GBDB_GQL/db_engine/gbdb.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb.php#L24)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GBDB_AdminOpsTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_admin_ops.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `repairMode()` | `public static function repairMode(bool $repair = false, array $options = []) :array` | [Zeile 73](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L73) |
| `tableDeepCheck()` | `public static function tableDeepCheck(string $database, string $table) :array` | [Zeile 125](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L125) |
| `verifyTableChecksum()` | `public static function verifyTableChecksum(string $database, string $table, bool $updateMeta = false) :array` | [Zeile 162](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L162) |
| `prepareRowChecksums()` | `public static function prepareRowChecksums(string $database, string $table, bool $write = true) :array` | [Zeile 203](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L203) |
| `verifyPageChecksums()` | `public static function verifyPageChecksums( string $database, string $table, int $chunkSize = 500, bool $write = true ) :array` | [Zeile 264](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L264) |
| `verifyIndexConsistency()` | `public static function verifyIndexConsistency(string $database, string $table) :array` | [Zeile 329](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L329) |
| `verifyForeignKeys()` | `public static function verifyForeignKeys(string $database, string $table) :array` | [Zeile 341](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L341) |
| `verifySchemaCheck()` | `public static function verifySchemaCheck(string $database, string $table) :array` | [Zeile 365](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L365) |
| `verifyWalCheck()` | `public static function verifyWalCheck(string $database, string $table) :array` | [Zeile 387](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L387) |
| `verifyLockCheck()` | `public static function verifyLockCheck(string $database = "", string $table = "") :array` | [Zeile 433](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L433) |
| `verifyStorageCheck()` | `public static function verifyStorageCheck(string $database, string $table) :array` | [Zeile 466](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L466) |
| `repairIndex()` | `public static function repairIndex(string $database, string $table) :array` | [Zeile 485](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L485) |
| `repairWal()` | `public static function repairWal(string $database, string $table) :array` | [Zeile 497](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L497) |
| `repairRelations()` | `public static function repairRelations(string $database, string $table) :array` | [Zeile 509](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L509) |
| `repairReport()` | `public static function repairReport(string $database = "", string $table = "") :array` | [Zeile 526](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L526) |
| `isolateCorruptFiles()` | `public static function isolateCorruptFiles(string $database, string $table, string $reason = "manual") :array` | [Zeile 545](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L545) |
| `manualRecoveryTools()` | `public static function manualRecoveryTools() :array` | [Zeile 597](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L597) |
| `autoCompact()` | `public static function autoCompact(array\|string $options = [], ?string $table = null) :array` | [Zeile 619](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L619) |
| `autoVacuum()` | `public static function autoVacuum(array $o = []) :array` | [Zeile 654](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L654) |
| `autoAnalyze()` | `public static function autoAnalyze(array $o = []) :array` | [Zeile 665](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L665) |
| `autoIndexRebuild()` | `public static function autoIndexRebuild(array $o = []) :array` | [Zeile 676](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L676) |
| `autoIndexRepair()` | `public static function autoIndexRepair(array $o = []) :array` | [Zeile 687](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L687) |
| `autoJournalCleanup()` | `public static function autoJournalCleanup(array $o = []) :array` | [Zeile 698](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L698) |
| `autoVersionCleanup()` | `public static function autoVersionCleanup(array $o = []) :array` | [Zeile 709](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L709) |
| `autoOrphanCleanup()` | `public static function autoOrphanCleanup(array $o = []) :array` | [Zeile 720](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L720) |
| `autoStatsRefresh()` | `public static function autoStatsRefresh(array $o = []) :array` | [Zeile 731](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L731) |
| `autoMaintenance()` | `public static function autoMaintenance(array $options = []) :array` | [Zeile 742](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L742) |
| `maintenanceScheduler()` | `public static function maintenanceScheduler(array $config = []) :array` | [Zeile 792](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L792) |
| `maintenanceWindow()` | `public static function maintenanceWindow(?string $start = null, ?string $end = null) :array` | [Zeile 821](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L821) |
| `maintenanceLogs()` | `public static function maintenanceLogs(int $limit = 100) :array` | [Zeile 853](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L853) |
| `maintenanceCli()` | `public static function maintenanceCli() :array` | [Zeile 885](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L885) |
| `maintenanceUi()` | `public static function maintenanceUi() :array` | [Zeile 905](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L905) |
| `deleteByRetention()` | `public static function deleteByRetention( string $database, string $table, string $column = "created_at", int $days = 365 ) :array` | [Zeile 930](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L930) |
| `autoBackup()` | `public static function autoBackup(array $o = []) :array` | [Zeile 969](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L969) |
| `dbHealth()` | `public static function dbHealth(bool $allInstances = true) :array` | [Zeile 980](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L980) |
| `tableStats()` | `public static function tableStats(string $database, string $table) :array` | [Zeile 998](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L998) |
| `indexStats()` | `public static function indexStats(string $database, string $table) :array` | [Zeile 1019](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1019) |
| `queryStats()` | `public static function queryStats() :array` | [Zeile 1035](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1035) |
| `walStats()` | `public static function walStats(string $database, string $table) :array` | [Zeile 1047](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1047) |
| `replicationStats()` | `public static function replicationStats() :array` | [Zeile 1056](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1056) |
| `backupStats()` | `public static function backupStats() :array` | [Zeile 1070](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1070) |
| `errorLog()` | `public static function errorLog(string $message = "", array $context = []) :array` | [Zeile 1088](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1088) |
| `auditLog()` | `public static function auditLog(string $action = "", array $payload = []) :array` | [Zeile 1107](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1107) |
| `recordQueryMetric()` | `public static function recordQueryMetric( string $type, float $ms, int $rowsScanned = 0, int $rowsReturned = 0, bool $fullScan = false, int $memory = 0 ) :void` | [Zeile 1134](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1134) |
| `performanceMetrics()` | `public static function performanceMetrics() :array` | [Zeile 1182](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1182) |
| `opsPerMinute()` | `public static function opsPerMinute() :array` | [Zeile 1220](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1220) |
| `queryTimeAverage()` | `public static function queryTimeAverage() :float` | [Zeile 1242](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1242) |
| `queryTimePercentiles()` | `public static function queryTimePercentiles() :array` | [Zeile 1251](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1251) |
| `memoryUsagePerQuery()` | `public static function memoryUsagePerQuery() :array` | [Zeile 1265](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1265) |
| `rowsScanned()` | `public static function rowsScanned() :int` | [Zeile 1280](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1280) |
| `rowsReturned()` | `public static function rowsReturned() :int` | [Zeile 1289](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1289) |
| `fullTableScanCount()` | `public static function fullTableScanCount() :int` | [Zeile 1298](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1298) |
| `indexHitRate()` | `public static function indexHitRate() :float` | [Zeile 1307](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1307) |
| `dashboardData()` | `public static function dashboardData() :array` | [Zeile 1319](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1319) |
| `adminAlerts()` | `public static function adminAlerts() :array` | [Zeile 1334](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1334) |
| `querySafety()` | `public static function querySafety(array $o = []) :array` | [Zeile 1358](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1358) |
| `setMaxExecutionTime()` | `public static function setMaxExecutionTime(int $s) :array` | [Zeile 1369](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1369) |
| `setMaxScanRows()` | `public static function setMaxScanRows(int $rows) :array` | [Zeile 1380](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1380) |
| `setMaxExportSize()` | `public static function setMaxExportSize(int $bytes) :array` | [Zeile 1396](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1396) |
| `blockExpensiveQueries()` | `public static function blockExpensiveQueries(bool $a = true) :array` | [Zeile 1412](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1412) |
| `adminOnlyHeavyQueries()` | `public static function adminOnlyHeavyQueries(bool $a = true) :array` | [Zeile 1423](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1423) |
| `querySandbox()` | `public static function querySandbox(array $c = []) :array` | [Zeile 1439](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1439) |
| `rateLimitQueryType()` | `public static function rateLimitQueryType(string $type, int $limit) :array` | [Zeile 1460](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1460) |
| `cacheSet()` | `public static function cacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool` | [Zeile 1486](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1486) |
| `cacheGet()` | `public static function cacheGet(string $key, mixed $default = null) :mixed` | [Zeile 1509](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1509) |
| `cacheInvalidate()` | `public static function cacheInvalidate(string $key) :void` | [Zeile 1526](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1526) |
| `cacheInvalidateTag()` | `public static function cacheInvalidateTag(string $tag) :int` | [Zeile 1539](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1539) |
| `rowCache()` | `public static function rowCache(string $db, string $table, mixed $id, int $ttl = 60) :mixed` | [Zeile 1560](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1560) |
| `tableMetaCache()` | `public static function tableMetaCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1587](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1587) |
| `schemaCache()` | `public static function schemaCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1614](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1614) |
| `indexCache()` | `public static function indexCache(string $db, string $table, int $ttl = 60) :array` | [Zeile 1641](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1641) |
| `queryResultCache()` | `public static function queryResultCache(string $key, callable $cb, int $ttl = 60, array $tags = []) :mixed` | [Zeile 1669](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1669) |
| `permissionCache()` | `public static function permissionCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1692](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1692) |
| `fulltextCache()` | `public static function fulltextCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1709](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1709) |
| `counterCache()` | `public static function counterCache(string $k, mixed $v = null, int $ttl = 300) :mixed` | [Zeile 1726](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1726) |
| `cacheTtl()` | `public static function cacheTtl(string $key) :int` | [Zeile 1741](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1741) |
| `cacheStats()` | `public static function cacheStats() :array` | [Zeile 1754](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1754) |
| `cacheHitRate()` | `public static function cacheHitRate() :float` | [Zeile 1774](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1774) |
| `cacheWarmup()` | `public static function cacheWarmup(array $items = []) :array` | [Zeile 1787](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1787) |
| `cacheRepair()` | `public static function cacheRepair() :array` | [Zeile 1809](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1809) |
| `apcuCachePrepared()` | `public static function apcuCachePrepared() :array` | [Zeile 1832](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1832) |
| `redisAdapterPrepared()` | `public static function redisAdapterPrepared(array $c = []) :array` | [Zeile 1848](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1848) |
| `fileCacheSet()` | `public static function fileCacheSet(string $key, mixed $value, int $ttl = 60, array $tags = []) :bool` | [Zeile 1875](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1875) |
| `fileCacheGet()` | `public static function fileCacheGet(string $key, mixed $default = null) :mixed` | [Zeile 1895](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1895) |
| `cacheTests()` | `public static function cacheTests() :array` | [Zeile 1913](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1913) |
| `atomicIncrement()` | `public static function atomicIncrement( string $db, string $table, mixed $id, string $column, int\|float $step = 1 ) :array` | [Zeile 1936](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1936) |
| `atomicDecrement()` | `public static function atomicDecrement( string $db, string $table, mixed $id, string $column, int\|float $step = 1 ) :array` | [Zeile 1957](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L1957) |
| `compareAndSwap()` | `public static function compareAndSwap( string $db, string $table, mixed $id, string $column, mixed $expected, mixed $newValue ) :array` | [Zeile 2011](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2011) |
| `ensureCounterTable()` | `public static function ensureCounterTable(string $db = "system", string $table = "counters") :bool` | [Zeile 2054](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2054) |
| `counterValue()` | `public static function counterValue( string $scope, string $name, int\|float $delta = 0, string $db = "system", string $table = "counters" ) :array` | [Zeile 2082](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2082) |
| `prepareShardedCounters()` | `public static function prepareShardedCounters(int $shards = 16) :array` | [Zeile 2123](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2123) |
| `prepareDistributedSafeCounters()` | `public static function prepareDistributedSafeCounters(array $c = []) :array` | [Zeile 2142](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2142) |
| `likeCounter()` | `public static function likeCounter(string $o, int $d = 1) :array` | [Zeile 2162](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2162) |
| `commentCounter()` | `public static function commentCounter(string $o, int $d = 1) :array` | [Zeile 2174](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2174) |
| `viewCounter()` | `public static function viewCounter(string $o, int $d = 1) :array` | [Zeile 2186](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2186) |
| `followerCounter()` | `public static function followerCounter(string $o, int $d = 1) :array` | [Zeile 2198](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2198) |
| `unreadCounter()` | `public static function unreadCounter(string $o, int $d = 1) :array` | [Zeile 2210](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2210) |
| `counterRepair()` | `public static function counterRepair(string $db = "system", string $table = "counters") :array` | [Zeile 2222](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2222) |
| `counterRecalculation()` | `public static function counterRecalculation( string $db, string $table, string $groupColumn, string $scope ) :array` | [Zeile 2260](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2260) |
| `cleanupJournals()` | `public static function cleanupJournals(int $days = 7) :array` | [Zeile 2303](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2303) |
| `cleanupVersions()` | `public static function cleanupVersions(int $days = 30) :array` | [Zeile 2314](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2314) |
| `refreshStats()` | `public static function refreshStats(array $o = []) :array` | [Zeile 2351](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_admin_ops.trait.php#L2351) |

## GBDB_AdvancedTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_advanced.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `clearRuntimeCache()` | `public static function clearRuntimeCache(?string $database = null, ?string $table = null) :void` | [Zeile 18](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L18) |
| `getCachedData()` | `public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5) :mixed` | [Zeile 40](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L40) |
| `bulkInsert()` | `public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true) :array` | [Zeile 65](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L65) |
| `streamRows()` | `public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500) :array` | [Zeile 115](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L115) |
| `page()` | `public static function page(string $database, string $table, int $page = 1, int $perPage = 50) :array` | [Zeile 143](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L143) |
| `cursor()` | `public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null) :array` | [Zeile 171](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L171) |
| `fulltext_search()` | `public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array` | [Zeile 207](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L207) |
| `fulltextSearch()` | `public static function fulltextSearch(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array` | [Zeile 289](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L289) |
| `fulltext()` | `public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50) :array` | [Zeile 304](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L304) |
| `queryPlan()` | `public static function queryPlan(string $database, string $table, string $where = '', mixed $is = '', ?string $sortField = null, ?int $limit = null, int $offset = 0) :array` | [Zeile 325](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L325) |
| `grantAcl()` | `public static function grantAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 383](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L383) |
| `revokeAcl()` | `public static function revokeAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 397](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L397) |
| `checkAcl()` | `public static function checkAcl(string $database, string $table, string $role, string $permission) :bool` | [Zeile 411](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L411) |
| `audit()` | `public static function audit(string $action, array $payload = [], string $actor = 'system') :int` | [Zeile 471](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L471) |
| `gdprExport()` | `public static function gdprExport(string $database, string $table, string $column, mixed $value) :array` | [Zeile 501](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L501) |
| `gdprRedact()` | `public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]') :bool` | [Zeile 528](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L528) |
| `migrate()` | `public static function migrate(string $database, string $table, string $migrationId, callable $callback) :array` | [Zeile 558](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L558) |
| `partitionTableName()` | `public static function partitionTableName(string $table, string $partition) :string` | [Zeile 604](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L604) |
| `insertPartitioned()` | `public static function insertPartitioned(string $database, string $table, string $partition, array $data) :int` | [Zeile 618](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L618) |
| `getPartition()` | `public static function getPartition(string $database, string $table, string $partition) :array` | [Zeile 641](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L641) |
| `shardTableName()` | `public static function shardTableName(string $table, mixed $key, int $shards = 16) :string` | [Zeile 656](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L656) |
| `insertSharded()` | `public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16) :int` | [Zeile 674](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L674) |
| `getShard()` | `public static function getShard(string $database, string $table, mixed $key, int $shards = 16) :array` | [Zeile 698](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L698) |
| `recoverWalOnly()` | `public static function recoverWalOnly(string $database, string $table) :array` | [Zeile 712](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L712) |
| `appendLog()` | `public static function appendLog(string $database, string $table, int $limit = 100) :array` | [Zeile 725](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L725) |
| `monitor()` | `public static function monitor(string $database, string $table) :array` | [Zeile 739](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L739) |
| `get()` | `public static function get(string $database, string $table, mixed $where = '', mixed $is = '', array $options = []) :mixed` | [Zeile 771](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L771) |
| `insert()` | `public static function insert(string $database, string $table, array $data) :int` | [Zeile 816](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L816) |
| `edit()` | `public static function edit(string $database, string $table, mixed $where, mixed $is, array $data) :bool` | [Zeile 843](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L843) |
| `delete()` | `public static function delete(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 857](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L857) |
| `exists()` | `public static function exists(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 871](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L871) |
| `upsert()` | `public static function upsert(string $database, string $table, mixed $where, mixed $is, array $data) :array` | [Zeile 886](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L886) |
| `stats()` | `public static function stats(string $database, ?string $table = null) :array` | [Zeile 909](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L909) |
| `analyze()` | `public static function analyze(string $database, string $table, int $sampleSize = 1000) :array` | [Zeile 947](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L947) |
| `autoIndex()` | `public static function autoIndex(string $database, string $table, array $columns = []) :array` | [Zeile 1022](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1022) |
| `explain()` | `public static function explain(string $database, string $table, string $where = '', mixed $is = '') :array` | [Zeile 1063](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1063) |
| `newQueryId()` | `public static function newQueryId() :string` | [Zeile 1072](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1072) |
| `activeQueries()` | `public static function activeQueries() :array` | [Zeile 1081](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1081) |
| `killQuery()` | `public static function killQuery(string $queryId) :array` | [Zeile 1092](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1092) |
| `slowQueryLog()` | `public static function slowQueryLog(array $plan, float $seconds) :void` | [Zeile 1104](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1104) |
| `select()` | `public static function select(string $database, string $table, array $options = []) :array` | [Zeile 1129](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1129) |
| `distinct()` | `public static function distinct(string $database, string $table, string $column) :array` | [Zeile 1169](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1169) |
| `aggregate()` | `public static function aggregate(string $database, string $table, string $fn, string $column = '*', ?string $groupBy = null, ?array $having = null) :array` | [Zeile 1193](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_advanced.trait.php#L1193) |

## GBDB_ClusterBackupTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `repairPartitions()` | `public static function repairPartitions(string $database, string $table) :array` | [Zeile 199](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L199) |
| `backupPartition()` | `public static function backupPartition(string $database, string $table, string $partition, string $target = "") :array` | [Zeile 265](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L265) |
| `setPartitionReadOnly()` | `public static function setPartitionReadOnly(string $database, string $table, string $partition, bool $readonly = true) :bool` | [Zeile 328](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L328) |
| `archiveOldPartitions()` | `public static function archiveOldPartitions(string $database, string $table, int $olderThan, bool $readonly = true) :array` | [Zeile 359](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L359) |
| `preparePartitionMerge()` | `public static function preparePartitionMerge(string $database, string $table, array $partitions, string $target) :array` | [Zeile 407](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L407) |
| `preparePartitionSplit()` | `public static function preparePartitionSplit(string $database, string $table, string $partition, array $targets) :array` | [Zeile 432](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L432) |
| `preparePartitionMigration()` | `public static function preparePartitionMigration( string $database, string $table, string $partition, string $targetInstance ) :array` | [Zeile 457](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L457) |
| `defineShardConcept()` | `public static function defineShardConcept(array $config = []) :array` | [Zeile 486](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L486) |
| `registerShard()` | `public static function registerShard(string $name, array $config = []) :array` | [Zeile 510](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L510) |
| `shardMap()` | `public static function shardMap() :array` | [Zeile 532](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L532) |
| `defineShardKey()` | `public static function defineShardKey(string $database, string $table, string $column, string $strategy = "hash") :bool` | [Zeile 546](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L546) |
| `shardForValue()` | `public static function shardForValue(mixed $value, string $strategy = "hash") :string` | [Zeile 569](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L569) |
| `shardRouter()` | `public static function shardRouter(string $database, string $table, array $row) :array` | [Zeile 592](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L592) |
| `prepareUserSharding()` | `public static function prepareUserSharding(string $database, string $table, string $column = "user_id") :bool` | [Zeile 616](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L616) |
| `prepareTenantSharding()` | `public static function prepareTenantSharding(string $database, string $table, string $column = "tenant_id") :bool` | [Zeile 629](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L629) |
| `prepareHashSharding()` | `public static function prepareHashSharding(string $database, string $table, string $column = "id") :bool` | [Zeile 642](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L642) |
| `shardAwareId()` | `public static function shardAwareId(string $database, string $table, array $row = []) :string` | [Zeile 655](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L655) |
| `crossShardQueryRules()` | `public static function crossShardQueryRules(array $rules = []) :array` | [Zeile 668](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L668) |
| `prepareShardRebalancing()` | `public static function prepareShardRebalancing() :array` | [Zeile 704](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L704) |
| `prepareShardMigration()` | `public static function prepareShardMigration(string $fromShard, string $toShard, array $options = []) :array` | [Zeile 734](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L734) |
| `shardHealth()` | `public static function shardHealth() :array` | [Zeile 750](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L750) |
| `prepareCrossShardTransactions()` | `public static function prepareCrossShardTransactions() :array` | [Zeile 774](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L774) |
| `prepareShardAwareBackups()` | `public static function prepareShardAwareBackups() :array` | [Zeile 788](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L788) |
| `prepareShardMonitoring()` | `public static function prepareShardMonitoring() :array` | [Zeile 801](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L801) |
| `definePrimaryReplicaModel()` | `public static function definePrimaryReplicaModel(array $config = []) :array` | [Zeile 821](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L821) |
| `replicationLog()` | `public static function replicationLog() :string` | [Zeile 841](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L841) |
| `replicateAsync()` | `public static function replicateAsync() :array` | [Zeile 870](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L870) |
| `prepareReadReplica()` | `public static function prepareReadReplica(string $name, array $config = []) :array` | [Zeile 902](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L902) |
| `replicaCatchup()` | `public static function replicaCatchup(string $replica) :array` | [Zeile 920](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L920) |
| `replicaLag()` | `public static function replicaLag() :array` | [Zeile 936](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L936) |
| `replicaHealth()` | `public static function replicaHealth() :array` | [Zeile 960](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L960) |
| `prepareReplicaRepair()` | `public static function prepareReplicaRepair(string $replica = "") :array` | [Zeile 984](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L984) |
| `prepareFailover()` | `public static function prepareFailover(string $replica = "") :array` | [Zeile 1001](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1001) |
| `promoteReplicaToPrimary()` | `public static function promoteReplicaToPrimary(string $replica) :array` | [Zeile 1018](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1018) |
| `enableSplitBrainProtection()` | `public static function enableSplitBrainProtection(array $config = []) :array` | [Zeile 1034](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1034) |
| `prepareReplicationSlots()` | `public static function prepareReplicationSlots(array $replicas = []) :array` | [Zeile 1055](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1055) |
| `prepareShardReplication()` | `public static function prepareShardReplication(string $shard = "") :array` | [Zeile 1077](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1077) |
| `prepareTenantReplication()` | `public static function prepareTenantReplication(string $tenant = "") :array` | [Zeile 1093](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1093) |
| `registerClusterNode()` | `public static function registerClusterNode(string $node, array $config = []) :array` | [Zeile 1110](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1110) |
| `clusterNodes()` | `public static function clusterNodes() :array` | [Zeile 1132](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1132) |
| `heartbeatNode()` | `public static function heartbeatNode(string $node = "") :array` | [Zeile 1143](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1143) |
| `prepareLeaderElection()` | `public static function prepareLeaderElection() :array` | [Zeile 1157](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1157) |
| `defineQuorum()` | `public static function defineQuorum(int $minNodes = 1) :array` | [Zeile 1181](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1181) |
| `clusterHealth()` | `public static function clusterHealth() :array` | [Zeile 1198](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1198) |
| `defineNodeRoles()` | `public static function defineNodeRoles(array $roles = []) :array` | [Zeile 1224](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1224) |
| `prepareServiceDiscovery()` | `public static function prepareServiceDiscovery(array $config = []) :array` | [Zeile 1245](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1245) |
| `prepareDistributedLocks()` | `public static function prepareDistributedLocks() :array` | [Zeile 1259](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1259) |
| `setPrimaryNode()` | `public static function setPrimaryNode(string $node) :array` | [Zeile 1276](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1276) |
| `setReplicaNode()` | `public static function setReplicaNode(string $node) :array` | [Zeile 1287](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1287) |
| `setWorkerNode()` | `public static function setWorkerNode(string $node) :array` | [Zeile 1298](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1298) |
| `setMaintenanceNode()` | `public static function setMaintenanceNode(string $node) :array` | [Zeile 1309](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1309) |
| `clusterConfig()` | `public static function clusterConfig(array $config = []) :array` | [Zeile 1320](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1320) |
| `detectSplitBrain()` | `public static function detectSplitBrain() :array` | [Zeile 1341](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1341) |
| `prepareClusterRecovery()` | `public static function prepareClusterRecovery() :array` | [Zeile 1363](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1363) |
| `backupLog()` | `public static function backupLog(string $action, array $payload = []) :bool` | [Zeile 1381](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1381) |
| `fullBackup()` | `public static function fullBackup(string $target = "") :array` | [Zeile 1441](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1441) |
| `prepareIncrementalBackup()` | `public static function prepareIncrementalBackup() :array` | [Zeile 1450](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1450) |
| `prepareDifferentialBackup()` | `public static function prepareDifferentialBackup() :array` | [Zeile 1465](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1465) |
| `snapshotBackup()` | `public static function snapshotBackup(string $target = "") :array` | [Zeile 1482](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1482) |
| `prepareHotBackup()` | `public static function prepareHotBackup() :array` | [Zeile 1491](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1491) |
| `coldBackup()` | `public static function coldBackup(string $target = "") :array` | [Zeile 1510](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1510) |
| `verifyBackup()` | `public static function verifyBackup(string $path) :array` | [Zeile 1521](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1521) |
| `restoreTest()` | `public static function restoreTest(string $backupPath) :array` | [Zeile 1553](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1553) |
| `preparePointInTimeRecovery()` | `public static function preparePointInTimeRecovery(?int $timestamp = null) :array` | [Zeile 1587](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1587) |
| `encryptedBackup()` | `public static function encryptedBackup(string $target = "") :array` | [Zeile 1608](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1608) |
| `prepareRemoteBackups()` | `public static function prepareRemoteBackups(array $config = []) :array` | [Zeile 1636](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1636) |
| `prepareOffsiteBackups()` | `public static function prepareOffsiteBackups(array $config = []) :array` | [Zeile 1657](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1657) |
| `rotateBackups()` | `public static function rotateBackups(int $keep = 10) :array` | [Zeile 1678](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1678) |
| `applyBackupRetention()` | `public static function applyBackupRetention(int $days = 30) :array` | [Zeile 1710](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1710) |
| `tenantBackup()` | `public static function tenantBackup(string $tenant, string $target = "") :array` | [Zeile 1743](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1743) |
| `instanceBackup()` | `public static function instanceBackup(string $instance, string $target = "") :array` | [Zeile 1763](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1763) |
| `tableBackup()` | `public static function tableBackup(string $database, string $table, string $target = "") :array` | [Zeile 1776](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_cluster_backup.trait.php#L1776) |

## GBDB_CrudTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_crud.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `createInstance()` | `public static function createInstance(string $name) :bool` | [Zeile 11](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L11) |
| `deleteInstance()` | `public static function deleteInstance(string $name, bool $force = false) :bool` | [Zeile 49](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L49) |
| `listInstances()` | `public static function listInstances(bool $includeSystem = false) :array` | [Zeile 139](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L139) |
| `listAllInstances()` | `public static function listAllInstances() :array` | [Zeile 190](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L190) |
| `createDatabase()` | `public static function createDatabase(string $name) :bool` | [Zeile 201](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L201) |
| `deleteDatabase()` | `public static function deleteDatabase(string $name) :bool` | [Zeile 240](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L240) |
| `createTable()` | `public static function createTable(string $database, string $table, array $cols) :bool` | [Zeile 303](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L303) |
| `addColumn()` | `public static function addColumn(string $database, string $table, string $column, mixed $default = "") :bool` | [Zeile 376](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L376) |
| `deleteTable()` | `public static function deleteTable(string $database, string $table) :bool` | [Zeile 496](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L496) |
| `insertData()` | `public static function insertData(string $database, string $table, mixed $data) :int` | [Zeile 570](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L570) |
| `deleteData()` | `public static function deleteData(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 740](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L740) |
| `editData()` | `public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData) :bool` | [Zeile 891](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L891) |
| `getData()` | `public static function getData( string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = "" ) :mixed` | [Zeile 1109](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1109) |
| `elementExists()` | `public static function elementExists(string $database, string $table, mixed $where, mixed $is) :bool` | [Zeile 1216](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1216) |
| `listDBs()` | `public static function listDBs() :array` | [Zeile 1227](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1227) |
| `listTables()` | `public static function listTables(string $database, bool $descending = false) :array` | [Zeile 1285](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1285) |
| `deleteAll()` | `public static function deleteAll(string $database) :bool` | [Zeile 1384](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1384) |
| `nextID()` | `public static function nextID(string $database, string $table) :int` | [Zeile 1416](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1416) |
| `getKeys()` | `public static function getKeys(string $database, string $table) :array` | [Zeile 1443](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1443) |
| `query()` | `public static function query(string $script, array $ctx = [], array $params = []) :array` | [Zeile 1463](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1463) |
| `runScript()` | `public static function runScript(string $scriptName, array $params = [], array $ctx = []) :array` | [Zeile 1478](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1478) |
| `replaceTableContents()` | `public static function replaceTableContents(string $database, string $table, array $columns, array $rows) :bool` | [Zeile 1590](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_crud.trait.php#L1590) |

## GBDB_EnterpriseOpsTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L10)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `patternTemplate()` | `public static function patternTemplate(string $name = 'intranet') :array` | [Zeile 156](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L156) |
| `listPatterns()` | `public static function listPatterns(bool $includeInactive = true) :array` | [Zeile 187](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L187) |
| `getPattern()` | `public static function getPattern(string $name) :array` | [Zeile 219](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L219) |
| `savePattern()` | `public static function savePattern(array $pattern) :array` | [Zeile 245](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L245) |
| `deletePattern()` | `public static function deletePattern(string $name) :array` | [Zeile 269](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L269) |
| `renamePattern()` | `public static function renamePattern(string $name, string $newName) :array` | [Zeile 287](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L287) |
| `setPatternActive()` | `public static function setPatternActive(string $name, bool $active) :array` | [Zeile 337](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L337) |
| `installPattern()` | `public static function installPattern(string $patternName, string $instanceName, bool $allowInactive = false) :array` | [Zeile 359](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L359) |
| `installIntranetPattern()` | `public static function installIntranetPattern(string $database = 'intranet') :array` | [Zeile 472](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L472) |
| `intranetAudit()` | `public static function intranetAudit(string $action, array $payload = [], string $database = 'intranet') :array` | [Zeile 600](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L600) |
| `intranetDirectorySearch()` | `public static function intranetDirectorySearch(string $query, string $database = 'intranet', int $limit = 50) :array` | [Zeile 629](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L629) |
| `defineDocumentPermission()` | `public static function defineDocumentPermission( string $docId, string $subjectType, string $subjectId, string $permission, string $database = 'intranet' ) :array` | [Zeile 673](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L673) |
| `defineRetentionRule()` | `public static function defineRetentionRule(string $name, int $days, string $action = 'archive', string $database = 'intranet') :array` | [Zeile 707](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L707) |
| `prepareSsoAdapter()` | `public static function prepareSsoAdapter(string $type, array $config = []) :array` | [Zeile 737](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L737) |
| `prepareLdapAdapter()` | `public static function prepareLdapAdapter(array $config = []) :array` | [Zeile 768](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L768) |
| `prepareSamlAdapter()` | `public static function prepareSamlAdapter(array $config = []) :array` | [Zeile 779](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L779) |
| `prepareOidcAdapter()` | `public static function prepareOidcAdapter(array $config = []) :array` | [Zeile 790](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L790) |
| `prepareOAuth2Adapter()` | `public static function prepareOAuth2Adapter(array $config = []) :array` | [Zeile 801](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L801) |
| `externalUserSync()` | `public static function externalUserSync(array $users, string $database = 'intranet') :array` | [Zeile 813](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L813) |
| `groupSync()` | `public static function groupSync(array $groups, string $database = 'intranet') :array` | [Zeile 862](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L862) |
| `departmentSync()` | `public static function departmentSync(array $departments, string $database = 'intranet') :array` | [Zeile 893](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L893) |
| `roleMapping()` | `public static function roleMapping(array $mapping) :array` | [Zeile 923](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L923) |
| `ssoAudit()` | `public static function ssoAudit(string $provider, string $subject, string $action, array $payload = []) :array` | [Zeile 940](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L940) |
| `createServiceAccount()` | `public static function createServiceAccount(string $name, array $scopes = [], array $options = []) :array` | [Zeile 959](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L959) |
| `createApiToken()` | `public static function createApiToken(string $subject, array $scopes = [], int $ttlSeconds = 2592000) :array` | [Zeile 993](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L993) |
| `validateApiToken()` | `public static function validateApiToken(string $token, string $scope = '') :array` | [Zeile 1031](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1031) |
| `rotateApiToken()` | `public static function rotateApiToken(string $tokenId, int $ttlSeconds = 2592000) :array` | [Zeile 1064](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1064) |
| `registerSession()` | `public static function registerSession(string $uid, array $device = [], int $ttlSeconds = 172800) :array` | [Zeile 1098](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1098) |
| `forceLogout()` | `public static function forceLogout(string $uid) :array` | [Zeile 1127](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1127) |
| `sessionList()` | `public static function sessionList(string $uid = '') :array` | [Zeile 1158](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1158) |
| `tenantPolicy()` | `public static function tenantPolicy(string $tenant, array $policy = []) :array` | [Zeile 1180](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1180) |
| `tenantQuotas()` | `public static function tenantQuotas(string $tenant, array $quotas = []) :array` | [Zeile 1200](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1200) |
| `tenantRestore()` | `public static function tenantRestore(string $tenant, string $backupPath) :array` | [Zeile 1225](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1225) |
| `tenantExport()` | `public static function tenantExport(string $tenant, string $target = '') :array` | [Zeile 1256](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1256) |
| `tenantDelete()` | `public static function tenantDelete(string $tenant, bool $force = false) :array` | [Zeile 1268](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1268) |
| `prepareTenantMigration()` | `public static function prepareTenantMigration(string $tenant, string $targetShard, array $options = []) :array` | [Zeile 1312](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1312) |
| `tenantStorageStats()` | `public static function tenantStorageStats(string $tenant = '') :array` | [Zeile 1330](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1330) |
| `tenantAdminRoles()` | `public static function tenantAdminRoles(string $tenant, array $roles) :array` | [Zeile 1378](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1378) |
| `tenantEncryptionKeysPrepared()` | `public static function tenantEncryptionKeysPrepared(string $tenant, array $options = []) :array` | [Zeile 1393](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1393) |
| `quotaLimits()` | `public static function quotaLimits(array $limits = []) :array` | [Zeile 1412](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1412) |
| `quotaCheck()` | `public static function quotaCheck(string $database = '', string $table = '') :array` | [Zeile 1456](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1456) |
| `quotaDashboard()` | `public static function quotaDashboard(bool $includeTenants = false) :array` | [Zeile 1495](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1495) |
| `rateLimit()` | `public static function rateLimit(string $bucket, string $key, int $limit, int $windowSeconds = 60) :array` | [Zeile 1519](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1519) |
| `configureRateLimits()` | `public static function configureRateLimits(array $limits) :array` | [Zeile 1561](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1561) |
| `rateLimitLogs()` | `public static function rateLimitLogs(int $limit = 100) :array` | [Zeile 1572](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1572) |
| `exportRows()` | `public static function exportRows(string $database, string $table, string $format = 'json', string $target = '') :array` | [Zeile 1590](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1590) |
| `importRows()` | `public static function importRows(string $database, string $table, string $file, string $format = 'json', bool $rollback = true) :array` | [Zeile 1652](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1652) |
| `validateImportRows()` | `public static function validateImportRows(string $database, string $table, array $rows) :array` | [Zeile 1730](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1730) |
| `gbdbDump()` | `public static function gbdbDump(string $target = '') :array` | [Zeile 1768](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1768) |
| `gbdbRestore()` | `public static function gbdbRestore(string $backupPath) :array` | [Zeile 1779](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1779) |
| `streamingImport()` | `public static function streamingImport(string $database, string $table, string $file, string $format = 'ndjson', int $chunkSize = 500) :array` | [Zeile 1794](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1794) |
| `streamingExport()` | `public static function streamingExport(string $database, string $table, string $format = 'ndjson', string $target = '') :array` | [Zeile 1808](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1808) |
| `exportPermissions()` | `public static function exportPermissions(array $rules = []) :array` | [Zeile 1819](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1819) |
| `exportLogs()` | `public static function exportLogs(int $limit = 100) :array` | [Zeile 1833](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1833) |
| `sqlErsatzFeatureMatrix()` | `public static function sqlErsatzFeatureMatrix() :array` | [Zeile 1842](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1842) |
| `sqlErsatzFeatureStatus()` | `public static function sqlErsatzFeatureStatus(string $feature) :array` | [Zeile 1872](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1872) |
| `prepareSqlAdapter()` | `public static function prepareSqlAdapter(string $type, array $config = []) :array` | [Zeile 1890](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1890) |
| `prepareMySQLImportAdapter()` | `public static function prepareMySQLImportAdapter(array $config = []) :array` | [Zeile 1942](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1942) |
| `preparePostgreSQLImportAdapter()` | `public static function preparePostgreSQLImportAdapter(array $config = []) :array` | [Zeile 1953](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1953) |
| `prepareSQLiteImportAdapter()` | `public static function prepareSQLiteImportAdapter(array $config = []) :array` | [Zeile 1964](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1964) |
| `prepareSqlCompatibilityLayer()` | `public static function prepareSqlCompatibilityLayer(array $config = []) :array` | [Zeile 1975](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1975) |
| `prepareSqlToGreenQLTranslator()` | `public static function prepareSqlToGreenQLTranslator(array $config = []) :array` | [Zeile 1986](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1986) |
| `preparePdoLikeAdapter()` | `public static function preparePdoLikeAdapter(array $config = []) :array` | [Zeile 1997](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L1997) |
| `adminUiData()` | `public static function adminUiData() :array` | [Zeile 2010](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2010) |
| `adminUiQueryPlan()` | `public static function adminUiQueryPlan(string $greenql) :array` | [Zeile 2034](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2034) |
| `cliCommand()` | `public static function cliCommand(string $command, array $args = []) :array` | [Zeile 2052](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2052) |
| `transactionApi()` | `public static function transactionApi(callable $callback) :array` | [Zeile 2107](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2107) |
| `cursorApi()` | `public static function cursorApi(string $database, string $table, int $chunkSize = 500) :Generator` | [Zeile 2137](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2137) |
| `indexApi()` | `public static function indexApi(string $database, string $table, string $column) :array` | [Zeile 2154](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2154) |
| `schemaApi()` | `public static function schemaApi(string $database, string $table, array $columns) :array` | [Zeile 2172](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2172) |
| `migrationApi()` | `public static function migrationApi(string $database, string $table, string $id, callable $cb) :array` | [Zeile 2191](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2191) |
| `backupApi()` | `public static function backupApi(string $type = 'full', array $args = []) :array` | [Zeile 2203](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2203) |
| `repairApi()` | `public static function repairApi(string $database = '', string $table = '') :array` | [Zeile 2217](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2217) |
| `explainApi()` | `public static function explainApi(string $database, string $table, array $where = []) :array` | [Zeile 2232](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2232) |
| `statsApi()` | `public static function statsApi() :array` | [Zeile 2241](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2241) |
| `policyApi()` | `public static function policyApi(string $name, array $rules = []) :array` | [Zeile 2253](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2253) |
| `eventApi()` | `public static function eventApi(string $event, string $db, string $table, array $payload = []) :array` | [Zeile 2267](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2267) |
| `queueApi()` | `public static function queueApi(string $action, array $payload = []) :array` | [Zeile 2279](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2279) |
| `mediaApi()` | `public static function mediaApi(array $payload = []) :array` | [Zeile 2295](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2295) |
| `searchApi()` | `public static function searchApi(string $database, string $table, string $query, array $columns = []) :array` | [Zeile 2312](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2312) |
| `cacheApi()` | `public static function cacheApi(string $action, string $key = '', mixed $value = null) :array` | [Zeile 2325](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2325) |
| `documentationIndex()` | `public static function documentationIndex() :array` | [Zeile 2353](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2353) |
| `enterpriseSelfTest()` | `public static function enterpriseSelfTest() :array` | [Zeile 2379](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_enterprise_ops.trait.php#L2379) |

## GBDB_IndexTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_index.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_index.trait.php#L3)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GBDB_InstanceSchemaTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_instance_schema.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `setInstance()` | `public static function setInstance(string $instance) :void` | [Zeile 43](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L43) |
| `isReservedInstance()` | `public static function isReservedInstance(string $instance) :bool` | [Zeile 64](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L64) |
| `sandboxInstances()` | `public static function sandboxInstances(callable $callback) :mixed` | [Zeile 81](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L81) |
| `trustedInstance()` | `public static function trustedInstance(string $instance, callable $callback) :mixed` | [Zeile 101](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L101) |
| `instance()` | `public static function instance(string $instance) :void` | [Zeile 123](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L123) |
| `getInstance()` | `public static function getInstance() :string` | [Zeile 132](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L132) |
| `withInstance()` | `public static function withInstance(string $instance, callable $callback) :mixed` | [Zeile 144](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L144) |
| `schemaTypes()` | `public static function schemaTypes() :array` | [Zeile 434](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L434) |
| `schemaTable()` | `public static function schemaTable(string $database, string $table) :array` | [Zeile 446](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L446) |
| `enableSchemaTypes()` | `public static function enableSchemaTypes(string $database, string $table, bool $enabled = true) :bool` | [Zeile 485](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L485) |
| `setColumnType()` | `public static function setColumnType(string $database, string $table, string $column, string $type, array $options = []) :bool` | [Zeile 505](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L505) |
| `setColumnDefault()` | `public static function setColumnDefault(string $database, string $table, string $column, mixed $default) :bool` | [Zeile 543](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L543) |
| `setSchemaConstraint()` | `public static function setSchemaConstraint(string $database, string $table, string $column, string $constraint, mixed $value = true) :bool` | [Zeile 567](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L567) |
| `createSequence()` | `public static function createSequence(string $database, string $table, string $name, int $start = 0, int $step = 1) :bool` | [Zeile 624](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L624) |
| `nextSequence()` | `public static function nextSequence(string $database, string $table, string $name) :int` | [Zeile 651](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L651) |
| `defineMigration()` | `public static function defineMigration(string $database, string $table, string $name, array $operations) :bool` | [Zeile 674](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L674) |
| `runMigration()` | `public static function runMigration(string $database, string $table, string $name) :array` | [Zeile 694](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L694) |
| `rollbackMigration()` | `public static function rollbackMigration(string $database, string $table, string $name) :array` | [Zeile 749](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L749) |
| `checkSchema()` | `public static function checkSchema(string $database, string $table) :array` | [Zeile 778](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L778) |
| `repairSchema()` | `public static function repairSchema(string $database, string $table) :array` | [Zeile 822](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L822) |
| `typedCompare()` | `public static function typedCompare(string $database, string $table, string $column, mixed $left, string $op, mixed $right) :bool` | [Zeile 853](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L853) |
| `typedSortRows()` | `public static function typedSortRows(string $database, string $table, array &$rows, string $column, string $dir = "ASC") :void` | [Zeile 892](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L892) |
| `migrationHistory()` | `public static function migrationHistory(string $database, string $table) :array` | [Zeile 964](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_instance_schema.trait.php#L964) |

## GBDB_JobsSecurityMediaSocialTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L9)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `enqueueJob()` | `public static function enqueueJob(string $type, array $payload = [], array $options = []) :string` | [Zeile 41](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L41) |
| `delayedJob()` | `public static function delayedJob(string $type, array $payload, int $delaySeconds, array $options = []) :string` | [Zeile 80](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L80) |
| `priorityJob()` | `public static function priorityJob(string $type, array $payload, int $priority = 10, array $options = []) :string` | [Zeile 96](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L96) |
| `scheduleJob()` | `public static function scheduleJob(string $name, string $type, array $payload, string $cronOrInterval, array $options = []) :array` | [Zeile 113](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L113) |
| `recurringJob()` | `public static function recurringJob(string $name, string $type, array $payload, int $everySeconds, array $options = []) :array` | [Zeile 144](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L144) |
| `claimJob()` | `public static function claimJob(string $workerId, string $queue = 'default') :array` | [Zeile 156](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L156) |
| `workerHeartbeat()` | `public static function workerHeartbeat(string $jobId, string $workerId) :bool` | [Zeile 199](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L199) |
| `processDueJobs()` | `public static function processDueJobs(int $limit = 25, string $queue = '') :array` | [Zeile 251](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L251) |
| `workQueue()` | `public static function workQueue(int $limit = 25, string $queue = '') :array` | [Zeile 344](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L344) |
| `finishJob()` | `public static function finishJob(string $jobId, bool $ok = true, array $result = []) :array` | [Zeile 357](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L357) |
| `retryFailedJobs()` | `public static function retryFailedJobs(int $limit = 50) :array` | [Zeile 393](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L393) |
| `queueStats()` | `public static function queueStats() :array` | [Zeile 423](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L423) |
| `jobDashboard()` | `public static function jobDashboard(int $limit = 50) :array` | [Zeile 450](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L450) |
| `queueUiPrepared()` | `public static function queueUiPrepared() :array` | [Zeile 481](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L481) |
| `queueCliPrepared()` | `public static function queueCliPrepared() :array` | [Zeile 493](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L493) |
| `srvQueueBridge()` | `public static function srvQueueBridge(string $action, array $payload = []) :array` | [Zeile 512](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L512) |
| `backgroundIndexUpdate()` | `public static function backgroundIndexUpdate(string $db, string $table) :string` | [Zeile 531](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L531) |
| `backgroundFeedFanout()` | `public static function backgroundFeedFanout(array $payload) :string` | [Zeile 545](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L545) |
| `backgroundMailSending()` | `public static function backgroundMailSending(array $payload) :string` | [Zeile 559](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L559) |
| `backgroundMediaProcessing()` | `public static function backgroundMediaProcessing(array $payload) :string` | [Zeile 573](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L573) |
| `backgroundBackups()` | `public static function backgroundBackups(array $payload = []) :string` | [Zeile 587](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L587) |
| `defineTrigger()` | `public static function defineTrigger(string $db, string $table, string $event, string $name, array $definition) :array` | [Zeile 609](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L609) |
| `runDataTriggers()` | `public static function runDataTriggers(string $event, string $db, string $table, array $context = []) :array` | [Zeile 641](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L641) |
| `enqueueEvent()` | `public static function enqueueEvent(string $event, string $db, string $table, array $payload = []) :string` | [Zeile 701](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L701) |
| `eventLog()` | `public static function eventLog(string $event, string $db, string $table, array $payload = []) :bool` | [Zeile 722](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L722) |
| `triggerDebug()` | `public static function triggerDebug(string $db, string $table) :array` | [Zeile 734](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L734) |
| `schemaDefinedTriggers()` | `public static function schemaDefinedTriggers(string $db, string $table) :array` | [Zeile 751](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L751) |
| `greenqlTriggerFunction()` | `public static function greenqlTriggerFunction(string $name, string $script) :array` | [Zeile 765](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L765) |
| `defineView()` | `public static function defineView(string $name, string $query, array $options = []) :array` | [Zeile 794](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L794) |
| `refreshView()` | `public static function refreshView(string $name) :array` | [Zeile 821](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L821) |
| `getView()` | `public static function getView(string $name, bool $refresh = false) :mixed` | [Zeile 857](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L857) |
| `viewQueryPlanner()` | `public static function viewQueryPlanner(string $name) :array` | [Zeile 874](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L874) |
| `materializedViewIndex()` | `public static function materializedViewIndex(string $name, array $columns) :array` | [Zeile 892](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L892) |
| `defineProcedure()` | `public static function defineProcedure(string $name, string $script, array $options = []) :array` | [Zeile 914](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L914) |
| `callProcedure()` | `public static function callProcedure(string $name, array $params = []) :mixed` | [Zeile 944](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L944) |
| `procedureLogs()` | `public static function procedureLogs(string $name = '') :array` | [Zeile 975](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L975) |
| `procedureDebug()` | `public static function procedureDebug(string $name) :array` | [Zeile 990](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L990) |
| `defineDbRole()` | `public static function defineDbRole(string $role, array $permissions = []) :array` | [Zeile 1011](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1011) |
| `defineDbUser()` | `public static function defineDbUser(string $user, array $roles = ['readonly'], array $options = []) :array` | [Zeile 1036](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1036) |
| `defineApiKeyScope()` | `public static function defineApiKeyScope(string $keyId, array $scopes) :array` | [Zeile 1062](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1062) |
| `defaultDbRoles()` | `public static function defaultDbRoles() :array` | [Zeile 1083](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1083) |
| `checkPermission()` | `public static function checkPermission(string $user, string $action, string $db = '', string $table = '', array $row = []) :bool` | [Zeile 1119](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1119) |
| `permissionAudit()` | `public static function permissionAudit(string $user, string $action, array $context = []) :bool` | [Zeile 1155](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1155) |
| `permissionDebug()` | `public static function permissionDebug(string $user) :array` | [Zeile 1166](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1166) |
| `definePolicy()` | `public static function definePolicy(string $name, array $rules) :array` | [Zeile 1182](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1182) |
| `evaluatePolicy()` | `public static function evaluatePolicy(string $action, array $row, array $context = []) :array` | [Zeile 1208](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1208) |
| `ownerUidPolicy()` | `public static function ownerUidPolicy(string $name = 'owner_uid') :array` | [Zeile 1251](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1251) |
| `visibilityPolicy()` | `public static function visibilityPolicy() :array` | [Zeile 1265](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1265) |
| `tenantIsolationPolicy()` | `public static function tenantIsolationPolicy(string $tenantField = 'tenant_id') :array` | [Zeile 1281](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1281) |
| `policyDebugOutput()` | `public static function policyDebugOutput() :array` | [Zeile 1295](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1295) |
| `auditDataChange()` | `public static function auditDataChange(string $action, string $db, string $table, array $old = [], array $new = [], array $context = []) :bool` | [Zeile 1318](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1318) |
| `auditExport()` | `public static function auditExport(array $filter = []) :array` | [Zeile 1344](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1344) |
| `auditSearch()` | `public static function auditSearch(string $needle) :array` | [Zeile 1361](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1361) |
| `auditRetention()` | `public static function auditRetention(int $days = 365) :array` | [Zeile 1383](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1383) |
| `auditIntegrity()` | `public static function auditIntegrity() :array` | [Zeile 1392](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1392) |
| `markPiiField()` | `public static function markPiiField(string $db, string $table, string $field, string $classification = 'pii') :array` | [Zeile 1412](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1412) |
| `userDataExport()` | `public static function userDataExport(string $userField, mixed $userId, array $tables = []) :array` | [Zeile 1434](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1434) |
| `userDataDelete()` | `public static function userDataDelete(string $userField, mixed $userId, array $tables = []) :array` | [Zeile 1465](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1465) |
| `userDataRedact()` | `public static function userDataRedact(string $db, string $table, string $where, mixed $is, array $fields, string $replacement = '[redacted]') :array` | [Zeile 1499](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1499) |
| `retentionPolicy()` | `public static function retentionPolicy(string $name, array $rule) :array` | [Zeile 1527](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1527) |
| `consentHistory()` | `public static function consentHistory(string $subject, array $entry) :array` | [Zeile 1547](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1547) |
| `encryptionConfig()` | `public static function encryptionConfig(array $cfg = []) :array` | [Zeile 1566](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1566) |
| `deriveKey()` | `public static function deriveKey(string $secret, string $salt = '', int $version = 1) :string` | [Zeile 1592](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1592) |
| `rotateKey()` | `public static function rotateKey(string $name = 'default') :array` | [Zeile 1608](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1608) |
| `scrubSecrets()` | `public static function scrubSecrets(mixed $value) :mixed` | [Zeile 1633](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1633) |
| `securityTests()` | `public static function securityTests() :array` | [Zeile 1652](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1652) |
| `blobPath()` | `public static function blobPath(string $hash = '', bool $ensure = true) :string` | [Zeile 1680](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1680) |
| `putBlob()` | `public static function putBlob(string $sourcePath, string $visibility = 'private', array $meta = []) :array` | [Zeile 1695](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1695) |
| `signedUrl()` | `public static function signedUrl(string $hash, int $ttl = 300) :string` | [Zeile 1749](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1749) |
| `temporaryUrl()` | `public static function temporaryUrl(string $hash, int $ttl = 300) :string` | [Zeile 1765](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1765) |
| `validateMime()` | `public static function validateMime(string $mime, array $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']) :bool` | [Zeile 1777](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1777) |
| `deleteMedia()` | `public static function deleteMedia(string $hash) :array` | [Zeile 1788](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1788) |
| `orphanMediaCleanup()` | `public static function orphanMediaCleanup() :array` | [Zeile 1812](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1812) |
| `mediaPermissions()` | `public static function mediaPermissions(string $hash, array $permissions) :array` | [Zeile 1839](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1839) |
| `mediaAudit()` | `public static function mediaAudit(string $hash, string $action, array $context = []) :bool` | [Zeile 1868](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1868) |
| `mediaProcessingPrepared()` | `public static function mediaProcessingPrepared(string $hash) :string` | [Zeile 1879](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1879) |
| `socialSchemaPatterns()` | `public static function socialSchemaPatterns() :array` | [Zeile 1899](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1899) |
| `installSocialPatterns()` | `public static function installSocialPatterns(string $db = 'social') :array` | [Zeile 1925](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1925) |
| `softDelete()` | `public static function softDelete(string $db, string $table, string $where, mixed $is) :bool` | [Zeile 1954](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1954) |
| `contentVisibilityRules()` | `public static function contentVisibilityRules(array $rules = []) :array` | [Zeile 1968](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1968) |
| `feedFanoutJob()` | `public static function feedFanoutJob(string $postId, array $targets) :string` | [Zeile 1993](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L1993) |
| `fanoutOnRead()` | `public static function fanoutOnRead(array $context) :array` | [Zeile 2008](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L2008) |
| `hybridFeed()` | `public static function hybridFeed(array $context) :array` | [Zeile 2024](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L2024) |
| `moderationQueue()` | `public static function moderationQueue(string $objectType, string $objectId, string $reason, array $context = []) :string` | [Zeile 2043](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_jobs_security_media_social.trait.php#L2043) |

## GBDB_LockingTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_locking.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `lockTimeout()` | `public static function lockTimeout(int $milliseconds) :int` | [Zeile 37](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L37) |
| `cleanupLocks()` | `public static function cleanupLocks(int $olderThanSeconds = 0) :array` | [Zeile 50](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L50) |
| `acquireLock()` | `public static function acquireLock(string $resource, string $type = 'write', int $timeoutMs = 0) :string\|false` | [Zeile 91](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L91) |
| `releaseLock()` | `public static function releaseLock(string $lockId) :bool` | [Zeile 167](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L167) |
| `lockTable()` | `public static function lockTable(string $database, string $table, string $type = 'write', int $timeoutMs = 0) :string\|false` | [Zeile 228](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L228) |
| `lockChunk()` | `public static function lockChunk(string $database, string $table, string\|int $chunk, string $type = 'write', int $timeoutMs = 0) :string\|false` | [Zeile 247](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L247) |
| `lockRow()` | `public static function lockRow(string $database, string $table, int $rowId, string $type = 'write', int $timeoutMs = 0) :string\|false` | [Zeile 266](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L266) |
| `lockPage()` | `public static function lockPage(string $database, string $table, string\|int $page, string $type = 'write', int $timeoutMs = 0) :string\|false` | [Zeile 285](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L285) |
| `lockMonitor()` | `public static function lockMonitor() :array` | [Zeile 298](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L298) |
| `lockStats()` | `public static function lockStats() :array` | [Zeile 331](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L331) |
| `detectDeadlocks()` | `public static function detectDeadlocks(int $olderThanSeconds = 15) :array` | [Zeile 358](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L358) |
| `resolveDeadlocks()` | `public static function resolveDeadlocks(int $olderThanSeconds = 15) :array` | [Zeile 384](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L384) |
| `retryOnLockCollision()` | `public static function retryOnLockCollision(callable $fn, int $tries = 3, int $sleepMs = 50) :mixed` | [Zeile 404](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L404) |
| `maybeEscalateLock()` | `public static function maybeEscalateLock(string $database, string $table, int $threshold = 32) :string\|false` | [Zeile 430](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_locking.trait.php#L430) |

## GBDB_MaintenanceTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_maintenance.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `compactTable()` | `public static function compactTable(string $database, string $table) :bool` | [Zeile 12](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L12) |
| `snapshot()` | `public static function snapshot(string $database, string $table, string $reason = "manual") :string` | [Zeile 102](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L102) |
| `createIndex()` | `public static function createIndex(string $database, string $table, string\|array $column, string $type = 'single', array $options = []) :bool` | [Zeile 138](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L138) |
| `createPrimaryIndex()` | `public static function createPrimaryIndex(string $database, string $table, string $column = 'id') :bool` | [Zeile 245](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L245) |
| `createUniqueIndex()` | `public static function createUniqueIndex(string $database, string $table, string\|array $columns) :bool` | [Zeile 258](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L258) |
| `createCompositeIndex()` | `public static function createCompositeIndex(string $database, string $table, array $columns) :bool` | [Zeile 271](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L271) |
| `createSortedIndex()` | `public static function createSortedIndex(string $database, string $table, string\|array $columns) :bool` | [Zeile 284](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L284) |
| `createRangeIndex()` | `public static function createRangeIndex(string $database, string $table, string $column) :bool` | [Zeile 297](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L297) |
| `createPrefixIndex()` | `public static function createPrefixIndex(string $database, string $table, string $column) :bool` | [Zeile 310](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L310) |
| `createFulltextIndex()` | `public static function createFulltextIndex(string $database, string $table, array $columns = []) :bool` | [Zeile 323](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L323) |
| `dropIndex()` | `public static function dropIndex(string $database, string $table, string $column) :bool` | [Zeile 350](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L350) |
| `listIndexes()` | `public static function listIndexes(string $database, string $table) :array` | [Zeile 397](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L397) |
| `rebuildIndexes()` | `public static function rebuildIndexes(string $database, string $table) :bool` | [Zeile 415](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L415) |
| `repairIndexes()` | `public static function repairIndexes(string $database, string $table) :array` | [Zeile 476](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L476) |
| `verifyIndexes()` | `public static function verifyIndexes(string $database, string $table) :array` | [Zeile 492](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L492) |
| `health()` | `public static function health(string $database, string $table) :array` | [Zeile 523](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L523) |
| `repairTable()` | `public static function repairTable(string $database, string $table) :bool` | [Zeile 608](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L608) |
| `meta()` | `public static function meta(string $database, string $table) :array` | [Zeile 642](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L642) |
| `addConstraint()` | `public static function addConstraint(string $database, string $table, string $column, string $type) :bool` | [Zeile 656](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L656) |
| `dropConstraint()` | `public static function dropConstraint(string $database, string $table, string $column, string $type) :bool` | [Zeile 739](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L739) |
| `listConstraints()` | `public static function listConstraints(string $database, string $table) :array` | [Zeile 784](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L784) |
| `vacuum()` | `public static function vacuum(string $database, string $table) :array` | [Zeile 798](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L798) |
| `restoreSnapshot()` | `public static function restoreSnapshot(string $database, string $table, string $snapshotId) :bool` | [Zeile 831](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_maintenance.trait.php#L831) |

## GBDB_MVCCTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_mvcc.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `isolationLevel()` | `public static function isolationLevel(string $level) :string` | [Zeile 25](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L25) |
| `beginSnapshot()` | `public static function beginSnapshot() :string` | [Zeile 42](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L42) |
| `endSnapshot()` | `public static function endSnapshot(string $snapshotId) :bool` | [Zeile 61](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L61) |
| `transactionSnapshots()` | `public static function transactionSnapshots() :array` | [Zeile 76](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L76) |
| `rowVersions()` | `public static function rowVersions(string $database, string $table, int $rowId) :array` | [Zeile 130](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L130) |
| `getDataSnapshot()` | `public static function getDataSnapshot( string $database, string $table, ?string $snapshotId = null, bool $filter = false, mixed $where = '', mixed $is = '' ) :mixed` | [Zeile 244](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L244) |
| `optimisticEditData()` | `public static function optimisticEditData( string $database, string $table, mixed $where, mixed $is, array $newData, int $expectedVersion ) :bool` | [Zeile 285](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L285) |
| `pessimisticRowLock()` | `public static function pessimisticRowLock(string $database, string $table, int $rowId, int $timeoutMs = 0) :string\|false` | [Zeile 318](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L318) |
| `cleanupRowVersions()` | `public static function cleanupRowVersions(string $database, string $table, int $keepSeconds = 3600) :array` | [Zeile 331](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L331) |
| `versionChains()` | `public static function versionChains(string $database, string $table) :array` | [Zeile 385](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L385) |
| `garbageCollectVersions()` | `public static function garbageCollectVersions(string $database, string $table, int $keepSeconds = 3600) :array` | [Zeile 434](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L434) |
| `mvccCleanup()` | `public static function mvccCleanup(int $keepSeconds = 3600) :array` | [Zeile 445](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L445) |
| `testConsistentReads()` | `public static function testConsistentReads() :array` | [Zeile 474](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L474) |
| `mvccLoadTest()` | `public static function mvccLoadTest(int $writes = 100) :array` | [Zeile 513](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L513) |
| `mvccStats()` | `public static function mvccStats(string $database, string $table) :array` | [Zeile 555](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_mvcc.trait.php#L555) |

## GBDB_PublicV103Trait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_public_v103.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L5)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `getActualInstance()` | `public static function getActualInstance() :string` | [Zeile 11](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L11) |
| `get()` | `public static function get(string $base, string $table, mixed $search = false, mixed $where = '', mixed $is = '', array $options = []) :mixed` | [Zeile 27](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L27) |
| `existsInstance()` | `public static function existsInstance(string $instance) :bool` | [Zeile 76](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L76) |
| `create()` | `public static function create(string $base, string $table = '', bool $useDataTypes = false, array\|object $rows = []) :bool` | [Zeile 92](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L92) |
| `exists()` | `public static function exists(string $base, ?string $table = null, mixed $where = '', mixed $is = '') :bool` | [Zeile 170](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L170) |
| `edit()` | `public static function edit(string $base, string $table, mixed $where, mixed $is, array\|object $newDataAsObject, bool $recursive = false) :bool` | [Zeile 205](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L205) |
| `delete()` | `public static function delete(string $base, ?string $table = null, mixed $where = '', mixed $is = '', bool $recursive = false) :bool` | [Zeile 226](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L226) |
| `getNextId()` | `public static function getNextId(string $base, string $table) :int` | [Zeile 253](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L253) |
| `fullTextSearch()` | `public static function fullTextSearch(string $base, string $table, string $text) :array` | [Zeile 266](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L266) |
| `renameTable()` | `public static function renameTable(string $base, string $table, string $newTableName) :bool` | [Zeile 279](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L279) |
| `renameBase()` | `public static function renameBase(string $base, string $newBaseName) :bool` | [Zeile 313](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L313) |
| `renameInstance()` | `public static function renameInstance(string $instance, string $newInstanceName) :bool` | [Zeile 349](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L349) |
| `moveBase()` | `public static function moveBase(string $base, string $fromInstance, string $toInstance) :bool` | [Zeile 380](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L380) |
| `moveTable()` | `public static function moveTable(string $instance, string $base, string $table, string $toInstance, string $toBase) :bool` | [Zeile 416](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L416) |
| `createBackup()` | `public static function createBackup(string $pathToBackupDir = '') :array` | [Zeile 471](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L471) |
| `runFile()` | `public static function runFile(string $pathToFileWithFilename, array\|object $parametersAsObject = []) :array` | [Zeile 483](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_public_v103.trait.php#L483) |

## GBDB_QueryTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_query.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `queryOptions()` | `public static function queryOptions(array $options = []) :array` | [Zeile 11](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L11) |
| `prepareQuery()` | `public static function prepareQuery(string $name, string $script) :bool` | [Zeile 44](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L44) |
| `executePreparedQuery()` | `public static function executePreparedQuery(string $name, array $params = [], array $ctx = []) :array` | [Zeile 65](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L65) |
| `bindQueryParams()` | `public static function bindQueryParams(string $script, array $params) :string` | [Zeile 90](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L90) |
| `clearQueryCache()` | `public static function clearQueryCache() :void` | [Zeile 116](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L116) |
| `innerJoin()` | `public static function innerJoin( string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = [] ) :array` | [Zeile 132](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L132) |
| `leftJoin()` | `public static function leftJoin( string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, array $options = [] ) :array` | [Zeile 155](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L155) |
| `join()` | `public static function join( string $database, string $leftTable, string $rightTable, string $leftKey, string $rightKey, string $type = 'inner', array $options = [] ) :array` | [Zeile 179](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L179) |
| `joinPlan()` | `public static function joinPlan( string $leftTable, string $rightTable, int $leftRows, int $rightRows, string $leftKey, string $rightKey, string $type = 'inner' ) :array` | [Zeile 321](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L321) |
| `subquery()` | `public static function subquery(callable $query) :array` | [Zeile 377](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L377) |
| `streamQuery()` | `public static function streamQuery(string $database, string $table, callable $callback, int $chunkSize = 500) :array` | [Zeile 408](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L408) |
| `allowFullScan()` | `public static function allowFullScan(string $database, string $table, string $where = '') :bool` | [Zeile 439](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_query.trait.php#L439) |

## GBDB_RecoveryTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_recovery.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `boot()` | `public static function boot() :array` | [Zeile 34](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L34) |
| `shutdown()` | `public static function shutdown() :void` | [Zeile 68](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L68) |
| `dirtyShutdownDetected()` | `public static function dirtyShutdownDetected() :bool` | [Zeile 89](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L89) |
| `recover()` | `public static function recover(bool $auto = false) :array` | [Zeile 124](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L124) |
| `recoverTable()` | `public static function recoverTable(string $database, string $table) :array` | [Zeile 184](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L184) |
| `recoveryReport()` | `public static function recoveryReport() :array` | [Zeile 302](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_recovery.trait.php#L302) |

## GBDB_RelationsIdsStreamingPartitionTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `addForeignKey()` | `public static function addForeignKey( string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = "id", array $options = [] ) :bool` | [Zeile 62](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L62) |
| `defineRelation()` | `public static function defineRelation( string $database, string $table, string $column, string $refDatabase, string $refTable, string $refColumn = "id", array $options = [] ) :bool` | [Zeile 135](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L135) |
| `dropRelation()` | `public static function dropRelation(string $database, string $table, string $name) :bool` | [Zeile 156](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L156) |
| `createRelationIndex()` | `public static function createRelationIndex(string $database, string $table, string $column) :bool` | [Zeile 231](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L231) |
| `checkOrphans()` | `public static function checkOrphans(string $database, string $table) :array` | [Zeile 476](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L476) |
| `repairOrphans()` | `public static function repairOrphans(string $database, string $table, string $mode = "report") :array` | [Zeile 516](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L516) |
| `repairConstraints()` | `public static function repairConstraints(string $database, string $table) :array` | [Zeile 560](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L560) |
| `relationGraph()` | `public static function relationGraph(?string $database = null) :array` | [Zeile 583](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L583) |
| `relationDocs()` | `public static function relationDocs(?string $database = null) :string` | [Zeile 635](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L635) |
| `checkRelations()` | `public static function checkRelations(?string $database = null) :array` | [Zeile 655](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L655) |
| `uuid()` | `public static function uuid() :string` | [Zeile 684](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L684) |
| `ulid()` | `public static function ulid() :string` | [Zeile 693](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L693) |
| `snowflakeId()` | `public static function snowflakeId(int $node = 1) :string` | [Zeile 704](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L704) |
| `distributedId()` | `public static function distributedId(string $tenant = "", string $shard = "") :string` | [Zeile 724](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L724) |
| `idExists()` | `public static function idExists(string $database, string $table, string $column, mixed $id) :bool` | [Zeile 741](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L741) |
| `safeId()` | `public static function safeId(string $database, string $table, string $column = "uid", string $type = "ulid", array $options = []) :string` | [Zeile 756](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L756) |
| `readGenerator()` | `public static function readGenerator(string $database, string $table, int $chunkSize = 500) :Generator` | [Zeile 782](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L782) |
| `chunkedRows()` | `public static function chunkedRows(string $database, string $table, int $chunkSize = 500, ?callable $filter = null) :Generator` | [Zeile 817](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L817) |
| `openCursor()` | `public static function openCursor(string $database, string $table, array $options = []) :array` | [Zeile 847](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L847) |
| `fetchCursor()` | `public static function fetchCursor(string $token) :array` | [Zeile 884](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L884) |
| `getDataGuarded()` | `public static function getDataGuarded(string $database, string $table, int $maxRows = 10000) :array` | [Zeile 948](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L948) |
| `importStreaming()` | `public static function importStreaming(string $database, string $table, iterable $rows, int $chunkSize = 500) :array` | [Zeile 977](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L977) |
| `exportStreaming()` | `public static function exportStreaming(string $database, string $table, callable $writer, int $chunkSize = 500) :array` | [Zeile 1021](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1021) |
| `setMaxRows()` | `public static function setMaxRows(string $database, string $table, int $maxRows) :bool` | [Zeile 1047](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1047) |
| `definePartitioning()` | `public static function definePartitioning(string $database, string $table, string $type, string $column, array $options = []) :bool` | [Zeile 1066](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1066) |
| `partitionByDate()` | `public static function partitionByDate(string $database, string $table, string $column, string $format = "Y-m") :bool` | [Zeile 1104](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1104) |
| `partitionByUser()` | `public static function partitionByUser(string $database, string $table, string $column = "user_id") :bool` | [Zeile 1117](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1117) |
| `partitionByTenant()` | `public static function partitionByTenant(string $database, string $table, string $column = "tenant_id") :bool` | [Zeile 1130](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1130) |
| `partitionByHash()` | `public static function partitionByHash(string $database, string $table, string $column, int $buckets = 16) :bool` | [Zeile 1144](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1144) |
| `partitionKey()` | `public static function partitionKey(string $database, string $table, array $row) :string` | [Zeile 1166](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1166) |
| `partitionStats()` | `public static function partitionStats(string $database, string $table) :array` | [Zeile 1224](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1224) |
| `partitionPrune()` | `public static function partitionPrune(string $database, string $table, array $criteria) :array` | [Zeile 1242](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_relations_ids_streaming_partition.trait.php#L1242) |

## GBDB_StorageTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_storage.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_storage.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `verifyStorage()` | `public static function verifyStorage(string $database, string $table) :array` | [Zeile 510](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_storage.trait.php#L510) |
| `storageStats()` | `public static function storageStats(string $database, string $table) :array` | [Zeile 528](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_storage.trait.php#L528) |

## GBDB_TransactionTrait

Trait · Quelle: [GBDB_GQL/db_engine/gbdb_transaction.trait.php](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `transactionTimeout()` | `public static function transactionTimeout(int $seconds) :int` | [Zeile 15](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L15) |
| `begin()` | `public static function begin() :bool` | [Zeile 140](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L140) |
| `savepoint()` | `public static function savepoint(string $name) :bool` | [Zeile 168](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L168) |
| `rollbackTo()` | `public static function rollbackTo(string $name) :bool` | [Zeile 196](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L196) |
| `commit()` | `public static function commit() :bool` | [Zeile 228](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L228) |
| `rollback()` | `public static function rollback() :bool` | [Zeile 332](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L332) |
| `transactionStatus()` | `public static function transactionStatus() :array` | [Zeile 365](../../PHP/gbdb_framework/GBDB_GQL/db_engine/gbdb_transaction.trait.php#L365) |

