# 04 — GBDB Database Engine

The `GBDB` class is assembled from many traits in `GBDB_GQL/db_engine/`. It provides the actual file-backed database engine.

## Naming

| Term | Meaning |
|---|---|
| Instance | A tenant or isolated workspace. |
| Base / Database | A database inside an instance. |
| Table | A row collection inside a base. |
| Row | Associative data record. |
| Schema | Table column definition and optional constraints. |

## Basic workflow

```php
<?php

require_once __DIR__ . "/gbdb_framework/autoloader.php";

GBDB::createInstance("main");
GBDB::setInstance("main");

GBDB::createDatabase("shop");
GBDB::createTable("shop", "products", ["sku", "name", "price", "active"]);

$id = GBDB::insertData("shop", "products", [
    "sku" => "SKU-001",
    "name" => "Coffee Mug",
    "price" => "9.90",
    "active" => "1"
]);

$rows = GBDB::getData("shop", "products", true, "active", "1");
```

## Instances

Use instances for tenants, customer installations, environments or product workspaces.

Typical methods:

- `GBDB::createInstance($name)`
- `GBDB::setInstance($name)`
- `GBDB::getInstance()`
- `GBDB::listInstances()`
- `GBDB::deleteInstance($name, $force)`
- `GBDB::existsInstance($name)` where available in your version

Use an instance when data must be isolated. Do not simulate tenants only with a `tenant_id` column unless shared-table analytics are more important than isolation.

## Bases and tables

Typical methods:

- `GBDB::createDatabase($database)`
- `GBDB::deleteDatabase($database)`
- `GBDB::listDBs()`
- `GBDB::createTable($database, $table, $columns)`
- `GBDB::deleteTable($database, $table)`
- `GBDB::listTables($database)`
- `GBDB::getKeys($database, $table)`
- `GBDB::addColumn($database, $table, $column, $default)`

Use one base per domain area, for example `auth`, `shop`, `content` or `device`. Use tables for entities.

## CRUD

Common methods:

- `GBDB::insertData($database, $table, $data)`
- `GBDB::getData($database, $table, $filter = false, $where = '', $is = '')`
- `GBDB::editData($database, $table, $where, $is, $data)`
- `GBDB::deleteData($database, $table, $where, $is)`
- `GBDB::nextID($database, $table)`
- `GBDB::elementExists($database, $table, $where, $is)`

Prefer explicit filters instead of fetching all rows when the table may grow.

## Pagination and cursors

Use pagination when a UI table or API response should not load all rows:

```php
$page = GBDB::page("shop", "products", 1, 25);
$cursor = GBDB::cursor("shop", "products", 100, null);
```

Use `page()` for human UI pages. Use `cursor()` for APIs, exports or batch processing.

## Bulk insert and streaming

Use `bulkInsert()` when inserting many rows at once. Use `streamRows()` when processing large tables without building a huge array in memory.

```php
GBDB::bulkInsert("shop", "products", $rows, true);

GBDB::streamRows("shop", "products", function (array $row) {
    // process row
}, 500);
```

## Fulltext search

The engine exposes `fulltext_search()`, `fulltextSearch()` and `fulltext()` aliases. Use them for simple text lookup across selected columns.

```php
$hits = GBDB::fulltext("content", "articles", "museum audio", ["title", "body"], 20);
```

## Indexes

Index support appears in `gbdb_index.trait.php` and related command support in GreenQL.

Use indexes when:

- a table has many rows;
- reads repeatedly filter on the same column;
- sorting or range queries become slow;
- you need uniqueness/lookup acceleration.

GreenQL examples:

```gql
INDEX users email IN app;
UNIQUE INDEX users uid IN app;
FULLTEXT INDEX articles title, body IN content;
SHOW INDEXES FROM users IN app;
REINDEX users IN app;
```

## Constraints and relations

The engine supports relation and constraint helpers through traits such as `gbdb_relations_ids_streaming_partition.trait.php`.

GreenQL examples:

```gql
ALTER TABLE users ADD CONSTRAINT UNIQUE email IN app;
ALTER TABLE posts ADD FOREIGN KEY user_id REFERENCES users(uid) IN app ON DELETE CASCADE;
SHOW RELATIONS FROM posts IN app;
CHECK RELATIONS IN app;
```

Use constraints for data integrity, especially in public API modules where external clients may send invalid data.

## Transactions

The framework includes transaction helpers and GreenQL transaction commands.

```gql
BEGIN TRANSACTION;
SEED users WITH {"uid":"u1", "name":"Max"} IN app;
COMMIT TRANSACTION;
```

Use transactions when multiple writes must succeed or fail together. For simple single-row updates, normal CRUD is usually enough.

## Maintenance and operations

The engine contains advanced maintenance methods:

- `dbHealth()`
- `tableStats()`
- `repairReport()`
- `repairIndex()`
- `repairWal()`
- `autoMaintenance()`
- `autoBackup()`
- `verifyTableChecksum()`
- `verifyForeignKeys()`
- `cleanupJournals()`
- `cleanupVersions()`

Run maintenance before/after migrations, after unexpected shutdowns, and on a schedule for production systems.

## Migration/update helpers

`GBDB::update()` and `GBDB::migrate()` copy missing tree parts and merge schema information from an older DB source into the current storage. Create a backup before using these methods.

## Best practices

- Always select the instance explicitly.
- Never manually edit physical storage files in normal operation.
- Use schema creation scripts for repeatable setup.
- Use indexes for repeated filters.
- Use pagination/cursors for UI/API lists.
- Run backup before update, migration, repair or major schema changes.
- Keep system/internal bases hidden from application-level UIs.
