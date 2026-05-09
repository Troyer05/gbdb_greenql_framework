# 07 — Public API

The Public API lets external clients access framework functionality through JSON requests. It is module-based and key-protected.

## Entry point

Use one of these depending on deployment:

```text
PHP/public_api.php
PHP/gbdb_framework/public/public_api.php
```

A request body normally contains:

```json
{
  "key": "API_KEY",
  "do": "module.action"
}
```

## Response envelope

The API responds with a status/data style envelope. Exact details depend on `PublicAPI::respond()`, but consumers should expect a JSON response with a numeric status and data payload.

## Rights model

The GBDB module uses action-specific rights. Common rights:

```text
gbdb-read
gbdb-write
gbdb-gql
gbdb-admin
gbdb-structure
```

Use the least powerful right set possible:

| Client type | Suggested rights |
|---|---|
| Read-only dashboard | `gbdb-read` |
| Form submitter | `gbdb-write` only for a narrow custom module, not raw GBDB write if possible |
| Admin integration | `gbdb-read`, `gbdb-write`, `gbdb-structure` |
| Migration tool | `gbdb-admin`, `gbdb-gql`, `gbdb-structure` |
| Embedded device | custom module-specific rights, ideally not raw admin rights |

## GBDB API module

The internal module class is `PublicAPI_GBDB` in `public/includes/public_api/gbdb.php`.

Example actions include:

```text
gbdb.info
gbdb.list_instances
gbdb.list_bases
gbdb.list_tables
gbdb.keys
gbdb.next_id
gbdb.get
gbdb.exists
gbdb.page
gbdb.cursor
gbdb.fulltext
gbdb.stats
gbdb.create_instance
gbdb.drop_instance
gbdb.create_base
gbdb.drop_base
gbdb.create_table
gbdb.drop_table
gbdb.add_column
gbdb.insert
gbdb.bulk_insert
gbdb.update
gbdb.upsert
gbdb.delete
gbdb.query
gbdb.run_script
```

## Read examples

```json
{
  "key": "API_KEY",
  "do": "gbdb.get",
  "instance": "main",
  "base": "app",
  "table": "users",
  "where": "active",
  "is": "1"
}
```

```json
{
  "key": "API_KEY",
  "do": "gbdb.page",
  "instance": "main",
  "base": "app",
  "table": "users",
  "page": 1,
  "per_page": 25
}
```

## Write examples

```json
{
  "key": "API_KEY",
  "do": "gbdb.insert",
  "instance": "main",
  "base": "app",
  "table": "users",
  "data": {
    "uid": "u1",
    "username": "max",
    "email": "max@example.com"
  }
}
```

```json
{
  "key": "API_KEY",
  "do": "gbdb.update",
  "instance": "main",
  "base": "app",
  "table": "users",
  "where": "uid",
  "is": "u1",
  "data": {
    "email": "new@example.com"
  }
}
```

## GreenQL through API

```json
{
  "key": "API_KEY",
  "do": "gbdb.run_script",
  "script": "USE INSTANCE main; PICK * FROM users IN app LIMIT 10;"
}
```

Only give `gbdb-gql` to highly trusted tools. GreenQL can perform powerful operations.

## Custom modules

Custom modules live in:

```text
gbdb_framework/public/includes/public_api/public_api_modules/
```

A module class must start with `PublicAPI_`, return a module name through `name()`, optionally declare required rights through `requiresRights()`, and handle actions through `handle()`.

Minimal module:

```php
<?php

class PublicAPI_Demo {
    public static function name() {
        return "demo";
    }

    public static function requiresRights(string $action = "") {
        return PublicAPI_ModuleHelper::rights([
            "ping" => "demo-read",
            "echo" => "demo-write"
        ], $action);
    }

    public static function handle(string $action, array $body, array $keyData) {
        return PublicAPI_ModuleHelper::handle(__CLASS__, [
            "ping" => "ping",
            "echo" => "echo"
        ], $action, $body, $keyData);
    }

    public static function ping(array $body, array $keyData) {
        return ["pong" => true];
    }

    public static function echo(array $body, array $keyData) {
        PublicAPI_ModuleHelper::requireParams($body, ["message"]);

        return ["message" => PublicAPI_ModuleHelper::str($body, "message")];
    }
}
```

## When to use raw GBDB module vs custom module

Use raw `gbdb.*` actions for admin tools, migrations and internal integrations. Use custom modules for public products, devices, customer apps and anything exposed beyond trusted developers. Custom modules can validate inputs and hide internal table names.

## Logging

`PublicAPI::log()` writes API logs into a configured system table. Use logs to debug clients, detect misuse and audit key activity.

## Security rules

- Never give admin rights to browser code.
- Prefer custom modules over raw `gbdb.write` for public clients.
- Validate all required parameters.
- Scope keys by rights and expiration.
- Rotate keys after leaks or deployments.
- Log writes and destructive actions.
- Do not expose system instances/bases through custom modules.
