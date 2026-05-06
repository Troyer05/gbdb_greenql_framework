# GBDB Core API

GBDB is the main database facade. Use only `GBDB::...`; versioned classes are intentionally not exposed.

## Instances

```php
GBDB::createInstance('client_a');
GBDB::setInstance('client_a');
GBDB::getInstance();
GBDB::listInstances();
GBDB::deleteInstance('client_a', true);
```

`listInstances()` hides internal framework/system instances by default. Use `listAllInstances()` only for internal diagnostics.

## Bases and tables

```php
GBDB::create('main');
GBDB::create('main', 'users', false, ['uid', 'name']);
GBDB::create('main', 'typed_users', true, ['uid' => 'string', 'active' => 'bool']);
GBDB::renameBase('main', 'archive');
GBDB::renameTable('main', 'users', 'members');
```

## CRUD

```php
GBDB::insertData('main', 'users', ['uid' => 'u1', 'name' => 'Max']);
GBDB::get('main', 'users');
GBDB::get('main', 'users', true, 'uid', 'u1');
GBDB::edit('main', 'users', 'uid', 'u1', ['name' => 'Max M.']);
GBDB::delete('main', 'users', 'uid', 'u1');
```
