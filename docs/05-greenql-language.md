# 05 — GreenQL Language

GreenQL is the framework's database scripting language. It can create instances, bases and tables; query rows; mutate data; run procedural logic; call functions/classes; operate indexes; run maintenance; and call SecondServer actions.

## Running GreenQL

From PHP:

```php
$result = GreenQL::runScript('setup.gql');
$result = GreenQL::run('SHOW INSTANCES;');
```

From the UI, use the GreenQL editor/scripts page.

## Basic script

```gql
USE INSTANCE main;
GROW BASE app;
GROW TABLE users WITH uid, username, email, active IN app;
SEED users WITH {"uid":"u1", "username":"max", "email":"max@example.com", "active":"1"} IN app;
PICK * FROM users IN app WHERE active = "1" SORT username ASC LIMIT 50;
```

## Variables and constants

```gql
DECLARE _name = "Max";
DECLARE :int _age = 30;
DECLARE :bool _active = true;
DECLARE $APP = "demo";

OUTPUT fusion("Hello ", _name, " / active=", _active);
```

Supported aliases: `DECLARE`, `DECALRE`, `DELACE`.

Supported type aliases include:

```text
mixed, any, var, string, str, text, email, url, date, time, datetime,
datetype, timetype, timestamp, uuid, ulid, enum, blob_reference, blob,
int, integer, float, double, decimal, number, bool, boolean, array, arr,
json, obj, object, map
```

Variable names should start with `_`. Constants start with `$` and cannot be overwritten once set.

## Built-in expressions

The runtime supports expression evaluation, variables, strings, numbers, booleans, JSON-like arrays/objects and calls to built-ins. Common built-ins include:

```text
now()
param("name")
ENV("name")
len(value)
fusion(a, b, c, ...)
uuid()
hash(value)
hash_sha256(value)
hash_sha512(value)
hash_md5(value)
hash_adler32(value)
hash_crc32(value)
hash_pass(value)
uni_random()
random_int(length)
spark_id()
fresh_id()
```

Use `fusion()` when you want predictable string concatenation with booleans/null rendered as readable text.

## Instances, bases and tables

```gql
SHOW INSTANCES;
GROW INSTANCE customer_a;
USE INSTANCE customer_a;
ROOT INSTANCE customer_a;
DROP INSTANCE customer_a FORCE;

SHOW BASES;
GROW BASE content;
ROOT content;
DROP BASE content;

SHOW TABLES IN content;
GROW TABLE pages WITH uid, slug, title, body, active IN content;
GROW TABLE pages (uid, slug, title, body, active) IN content;
ALTER TABLE pages ADD COLUMN created_at DEFAULT "" IN content;
EDIT TABLE pages ADD updated_at "" IN content;
DROP TABLE pages IN content;
DESCRIBE pages IN content;
```

Use `USE INSTANCE` first in multi-tenant scripts.

## CRUD commands

```gql
SEED users WITH {"uid":"u1", "username":"max", "email":"max@example.com"} IN app;
PICK * FROM users IN app WHERE uid = "u1" LIMIT 1;
PICK uid, username FROM users IN app WHERE username ~= "ma" SORT username ASC LIMIT 20 OFFSET 0;
RESHAPE users WITH {"email":"new@example.com"} WHERE uid = "u1" IN app;
DELETE FROM users WHERE uid = "u1" IN app;
ERASE FROM users WHERE active = "0" IN app;
```

Comparators:

```text
=, ==, !=, >, <, >=, <=, ~=
```

`~=` is useful for contains-like matching.

## Aggregates and distinct values

```gql
COUNT(*) FROM users IN app;
COUNT(uid) FROM users IN app GROUP BY active;
SUM(amount) FROM transactions IN app GROUP BY user_id;
AVG(amount) FROM transactions IN app;
MIN(amount) FROM transactions IN app;
MAX(amount) FROM transactions IN app;
DISTINCT role FROM users IN app;
```

Use aggregates for dashboards and summaries instead of fetching all rows into PHP.

## Pagination, cursors and fulltext

```gql
PAGE users IN app PAGE 1 LIMIT 25;
CURSOR users IN app LIMIT 100 AFTER 1000;
FULLTEXT users SEARCH "max" COLUMNS username,email LIMIT 20;
FULLTEXT "max" FROM users IN app COLUMNS username,email LIMIT 20;
```

Use `PAGE` for UI pages and `CURSOR` for machine-driven iteration.

## Control flow

```gql
IF (_active == true) {
    OUTPUT "active";
} ELSE {
    OUTPUT "inactive";
}

FOR (_i = 0; _i < 10; _i++) {
    LOG(_i);
}

FOR (_row; users FROM app) {
    OUTPUT _row;
}
```

## Functions and classes

```gql
F greet(?_name:string = "World") {
    BACK fusion("Hello ", _name);
}

DECLARE _text = CALL greet("Max");
OUTPUT _text;

C Demo {
    PUB DECLARE :string _prefix = "Hi";

    PUB F say(?_name:string = "World") {
        BACK fusion(this._prefix, " ", _name);
    }
}

CALL Demo/say("Max");
CLASS Demo/say("Max");
```

Use functions/classes for reusable scripts, installers and application-specific operations.

## File and pattern commands

```gql
FILE.INCLUDE other_script.gql;
FILE.RUN setup.gql {"instance":"main"};
EXECUTE PATTERN intranet IN app;
EXEC PATTERN used_by_auth_class;
```

Use patterns for repeatable setup tasks, such as auth/system database initialization.

## Logging commands

```gql
SET_LOGFILE("install.log");
LOG("start");
CLEAR_LOG();
DELETE_LOG_FILE();
```

Use logs in long-running setup/migration scripts.

## Transactions and prepared commands

```gql
BEGIN TRANSACTION;
SAVEPOINT before_users;
SEED users WITH {"uid":"u1", "username":"max"} IN app;
COMMIT TRANSACTION;

PREPARE getUsers AS PICK * FROM users IN app LIMIT 10;
EXECUTE getUsers;
```

Use transactions around multi-step writes.

## Indexes, constraints and relations

```gql
INDEX users email IN app;
UNIQUE INDEX users uid IN app;
FULLTEXT INDEX pages title, body IN content;
SHOW INDEXES FROM users IN app;
REINDEX users IN app;
DROP INDEX ON users email IN app;

ALTER TABLE users ADD CONSTRAINT UNIQUE email IN app;
ALTER TABLE users ADD CONSTRAINT REQUIRED username IN app;
SHOW CONSTRAINTS FROM users IN app;

ALTER TABLE posts ADD FOREIGN KEY user_id REFERENCES users(uid) IN app ON DELETE CASCADE;
SHOW RELATIONS FROM posts IN app;
CHECK RELATIONS IN app;
REPAIR RELATIONS posts IN app MODE REPORT;
```

## Maintenance and diagnostics commands

```gql
CHECK users IN app;
HEALTH users IN app;
REPAIR users IN app;
SNAPSHOT users IN app;
SHOW META FROM users IN app;
STATS users IN app;
ANALYZE users IN app;
SUGGEST INDEXES users IN app;
AUTO INDEX users IN app COLUMNS email, username;
MONITOR users IN app;
RECOVER users IN app;
```

Use these commands from admin tooling, not from public user-triggered requests unless access is tightly controlled.

## Partitioning, sharding and backups

```gql
PARTITION TABLE logs BY DATE created_at IN app;
SHOW PARTITIONS logs IN app;
REPAIR PARTITIONS logs IN app;
BACKUP PARTITION p2026 FROM logs IN app;
SET PARTITION p2026 FROM logs IN app READONLY;

REGISTER SHARD node_1 ROLE PRIMARY;
SHOW SHARDS;
SHARD TABLE logs BY HASH uid IN app;
SHARD HEALTH;
CLUSTER HEALTH;
HEARTBEAT NODE node_1;

BACKUP FULL;
BACKUP SNAPSHOT;
BACKUP COLD;
BACKUP ENCRYPTED;
BACKUP TABLE users IN app;
BACKUP INSTANCE main;
```

These features are for operational workflows. Test them on staging before relying on them in production.

## SecondServer calls from GreenQL

```gql
Srv/ping();
SRV/startJob("test_job", {"x":1});
SecondServer/registerUser({"username":"max", "email":"max@example.com", "password":"secret"});
```

Supported prefixes include `SecondServer`, `secondServer`, `secondserver`, `Secondserver`, `SRV`, `Srv` and `srv`.

## Renaming

```gql
RENAME INSTANCE old_name INTO new_name;
RENAME BASE old_base INTO new_base;
RENAME TABLE old_table INTO new_table IN app;
```

Always backup before renaming production objects.
