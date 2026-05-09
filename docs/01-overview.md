# 01 — Framework Overview

GBDB / SecondServerModul is a PHP framework centered around a file-based database engine and a scripting/query language called GreenQL. It is designed for small to medium web applications, admin tools, API backends, embedded-device dashboards and multi-instance product deployments where a classic SQL server would be unnecessary or too heavy.

The framework combines these subsystems:

- **GBDB database engine**: stores instances, bases and tables as protected files under `GBDB_GQL/.DB/`.
- **GreenQL**: a script/query language for schema creation, CRUD, control flow, functions, classes, remote calls, transactions, indexes and maintenance commands.
- **GBDB UI / GreenQL UI**: browser-based admin and scripting interface with its own internal user store.
- **Public API**: key-based external API with rights such as `gbdb-read`, `gbdb-write`, `gbdb-gql`, `gbdb-admin`, `gbdb-structure` and module-specific rights.
- **SecondServer / SRV / SrvP**: remote backend layer for forwarding database, auth and job instructions to another server.
- **Auth**: user registration, login, JWT cookie handling, email verification and 2FA flows.
- **Core helpers**: HTTP, validation, sessions, cookies, encryption, JSON/file tools, routing, SQL bridge and formatting helpers.
- **Plugins**: wrappers and maintenance layers for mRoot, MuseumQR, ShareSuite and EventQR.

## When should a developer use GBDB?

Use GBDB when you want:

- simple PHP deployment without requiring MySQL/MariaDB/PostgreSQL;
- tenant-aware local data storage;
- encrypted or obfuscated file-backed tables;
- an admin UI for direct data management;
- a script language for migrations and automation;
- a public API that can be extended module-by-module;
- a remote server bridge between installations.

Do not use GBDB as a drop-in replacement for a very high-write, multi-node SQL database. GBDB has strong maintenance and locking utilities, but file-backed storage is best when the write volume is predictable and the deployment is controlled.

## Mental model

A normal application request looks like this:

```php
<?php

require_once __DIR__ . "/gbdb_framework/autoloader.php";

GBDB::setInstance("main");
GBDB::createDatabase("app");
GBDB::createTable("app", "users", ["uid", "name", "email", "active"]);

GBDB::insertData("app", "users", [
    "uid" => Tools::generateId(),
    "name" => "Max",
    "email" => "max@example.com",
    "active" => "1"
]);

$users = GBDB::getData("app", "users");
```

A GreenQL version of the same workflow:

```gql
USE INSTANCE main;
GROW BASE app;
GROW TABLE users WITH uid, name, email, active IN app;
SEED users WITH {"uid":"u1", "name":"Max", "email":"max@example.com", "active":"1"} IN app;
PICK * FROM users IN app WHERE active = "1" LIMIT 50;
```

A Public API request version:

```json
{
  "key": "YOUR_API_KEY",
  "do": "gbdb.insert",
  "instance": "main",
  "base": "app",
  "table": "users",
  "data": {
    "uid": "u1",
    "name": "Max",
    "email": "max@example.com",
    "active": "1"
  }
}
```

## How to choose the right layer

| Situation | Recommended layer |
|---|---|
| Internal PHP code needs CRUD | `GBDB::...` methods. |
| You need migrations, scripts or admin-console operations | GreenQL. |
| A browser/mobile/embedded device calls the app | Public API. |
| One server must forward work to another server | `SecondServer` / SrvP. |
| You need login, JWT, 2FA or user store | `Auth`. |
| You need a simple route-based app | `Route`. |
| You need SQL fallback or migration bridge | `SQL` / `DatabaseBridge`. |
