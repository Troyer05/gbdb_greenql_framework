# 03 — Architecture

## Core layers

```text
Web request / PHP script
        │
        ├── autoloader.php
        │       ├── Vars config
        │       ├── GBDB engine traits
        │       ├── GreenQL runtime
        │       ├── core helper classes
        │       ├── SRV / SecondServer classes
        │       └── plugins
        │
        ├── direct GBDB calls
        ├── GreenQL scripts
        ├── PublicAPI module dispatch
        └── SRV backend dispatch
```

## Storage model

GBDB stores data under `GBDB_GQL/.DB/`. The exact physical file names may be tokenized/hashed and may use the configured data extension. The logical model remains stable:

```text
instance
└── base/database
    └── table
        ├── rows
        ├── metadata
        ├── append/wal-style data
        ├── indexes
        ├── locks
        └── schema records
```

The framework includes locking, append logs, metadata, schema tracking, indexes, checksums, recovery and maintenance helpers. Developers should not edit the physical `.DB` files manually unless recovering from a severe failure and after creating a backup.

## Autoloading

`gbdb_framework/autoloader.php` is the normal boot file. It brings the framework classes into scope and enables code such as:

```php
GBDB::setInstance("main");
GreenQL::run("SHOW INSTANCES;");
Auth::init();
```

## Request lifecycle: Public API

1. `public_api.php` includes the framework.
2. `PublicAPI::init()` reads the JSON request body.
3. It parses `do`, for example `gbdb.get`.
4. It authenticates the provided API key against the internal key store.
5. It loads internal and custom modules.
6. It verifies required rights.
7. It dispatches to the module's `handle()` method.
8. It returns a JSON envelope.

## Request lifecycle: SecondServer backend

1. A client calls `SecondServer::...` methods.
2. The client requests a short-lived token using the static secret.
3. The client sends an instruction to `backend.php` / `SRV/backend_api.php`.
4. The backend validates auth and dispatches to `SrvFunctions` or job modules.
5. The backend responds with a status/data envelope.

## Instance safety

Because GBDB is tenant-aware, always be explicit about the active instance in application code:

```php
GBDB::setInstance("customer_a");
// do work
GBDB::setInstance("default");
```

For callbacks or helpers, restore the previous instance after temporary switching. This prevents one tenant's request from accidentally reading another tenant's storage.

## System/internal data

The UI and Public API have system stores that should not be shown as normal customer/application data. The UI helper intentionally hides reserved/system names from standard navigation. When writing custom tools, respect `GreenQLUIv2Helper::reservedInstance()` and `reservedName()` patterns.
