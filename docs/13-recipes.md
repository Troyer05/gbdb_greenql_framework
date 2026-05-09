# 13 — Recipes

## Create a tenant and app schema

```gql
GROW INSTANCE customer_a;
USE INSTANCE customer_a;
GROW BASE app;
GROW TABLE users WITH uid, username, email, role, active, created_at IN app;
GROW TABLE settings WITH key, value IN app;
INDEX users uid IN app;
UNIQUE INDEX users email IN app;
```

## Insert and fetch a row in PHP

```php
GBDB::setInstance("customer_a");

GBDB::insertData("app", "users", [
    "uid" => Tools::generateId(),
    "username" => "max",
    "email" => "max@example.com",
    "role" => "admin",
    "active" => "1",
    "created_at" => date("Y-m-d H:i:s")
]);

$user = GBDB::getData("app", "users", true, "email", "max@example.com");
```

## Create a read-only API key workflow

1. Open the GBDB UI.
2. Go to Public API.
3. Create a key with `gbdb-read` only.
4. Test:

```json
{
  "key": "API_KEY",
  "do": "gbdb.page",
  "instance": "customer_a",
  "base": "app",
  "table": "users",
  "page": 1,
  "per_page": 10
}
```

## Build a safe custom device endpoint

Instead of giving a device raw `gbdb-write`, create a custom module such as `device.heartbeat` that only writes the needed row.

```php
class PublicAPI_Device {
    public static function name() {
        return "device";
    }

    public static function requiresRights(string $action = "") {
        return PublicAPI_ModuleHelper::rights([
            "heartbeat" => "device-write"
        ], $action);
    }

    public static function handle(string $action, array $body, array $keyData) {
        return PublicAPI_ModuleHelper::handle(__CLASS__, [
            "heartbeat" => "heartbeat"
        ], $action, $body, $keyData);
    }

    public static function heartbeat(array $body, array $keyData) {
        PublicAPI_ModuleHelper::requireParams($body, ["uid"]);

        GBDB::setInstance("main");
        GBDB::editData("devices", "state", "uid", PublicAPI_ModuleHelper::str($body, "uid"), [
            "last" => time(),
            "state" => PublicAPI_ModuleHelper::str($body, "state", "normal")
        ]);

        return ["ok" => true, "last" => time()];
    }
}
```

## Run a migration script safely

```gql
SET_LOGFILE("migration.log");
LOG("migration start");

USE INSTANCE main;
BEGIN TRANSACTION;
ALTER TABLE users ADD COLUMN last_login DEFAULT "" IN app;
COMMIT TRANSACTION;

LOG("migration done");
```

## Add public search with fulltext

```gql
FULLTEXT pages SEARCH "audio guide" COLUMNS title,body LIMIT 20;
```

or through API:

```json
{
  "key": "API_KEY",
  "do": "gbdb.fulltext",
  "instance": "main",
  "base": "content",
  "table": "pages",
  "query": "audio guide",
  "columns": ["title", "body"],
  "limit": 20
}
```

## Check if a table needs optimization

```gql
STATS users IN app;
SUGGEST INDEXES users IN app;
MONITOR users IN app;
```

## Remote login via SecondServer

```php
$login = SecondServer::login("max", "secret");

if (($login["ok"] ?? false) === true) {
    // use remote user/session data
}
```
