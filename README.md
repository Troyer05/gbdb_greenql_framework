# greenbucket® GBDB Framework / SecondServerModul

This package is a PHP 8.1+ framework bundle around GBDB, GreenQL, a developer UI, a public API layer and a remote SecondServer bridge.

## What is included

- **GBDB**: file-based, instance-aware database engine with CRUD, schema handling, indexes, backups, recovery, MVCC/transaction helpers and maintenance APIs.
- **GreenQL**: a script/query language for GBDB with variables, constants, database commands, function calls, ENV access and script execution.
- **GBDB UI**: developer interface for databases, GreenQL Studio, scripts, PHP execution, users, plugins, migration, backup, crypt, optimization, enterprise tools and monitoring.
- **Enterprise Ops**: pattern installation, quotas, rate limits, jobs, queue monitoring, intranet/social patterns and operations helpers.
- **SecondServer/SrvP**: remote JSON bridge and local service jobs for distributed or separated deployments.
- **Public API**: controlled JSON endpoints with auth gates for external integrations.

## Quick start

1. Place the `PHP/` folder in your web root.
2. Include the framework through:

```php
require_once __DIR__ . '/PHP/gbdb_framework/gbdb.php';
```

3. Configure the project in:

```text
PHP/gbdb_framework/.config/.framework.env.php
```

4. Open the UI in DEV mode through:

```text
PHP/gbdb_ui.php
```

## Configuration files

The framework no longer uses `_config.inc.php`. The active configuration entrypoint is:

```text
PHP/gbdb_framework/autoloader.php
PHP/gbdb_framework/.config/.framework.env.php
PHP/gbdb_framework/.config/.greenql.env.php
```

`Vars::db_arch()` controls whether the runtime uses `GBDB` or `SQL` where bridge code supports both modes.

## Basic GBDB example

```php
GBDB::setInstance('demo');
GBDB::createInstance('demo');
GBDB::create('main', 'users', true, [
    'uid' => 'string',
    'name' => 'string',
    'active' => 'bool'
]);
GBDB::insertData('main', 'users', [
    'uid' => 'u_1',
    'name' => 'Max M.',
    'active' => true
]);
$users = GBDB::get('main', 'users');
```

## GreenQL example

```gql
GROW INSTANCE demo;
USE INSTANCE demo;
GROW BASE main;
ROOT main;
GROW TABLE users (uid, name, active);
SEED users WITH uid="u_1", name="Max M.", active=true;
PICK * FROM users;
```

## Documentation

Detailed docs are in `docs/`.
