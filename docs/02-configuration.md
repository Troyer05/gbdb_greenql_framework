# Configuration

The main framework config is `PHP/gbdb_framework/.config/.framework.env.php` and defines the `Vars` class.

## Important settings

- `Vars::app_version()` returns the app version.
- `Vars::db_arch()` returns `GBDB` or `SQL`.
- `Vars::DB_PATH()` returns the storage path for GBDB instances.
- `Vars::crypt_data()` controls encrypted/tokenized storage names.
- `Vars::AUTH()` controls auth database, JWT cookie name and root user defaults.
- `Vars::PUBLIC_API` controls public API access.
- `Vars::SRVP` controls remote SecondServer access.

## GreenQL ENV

GreenQL ENV values live in:

```text
PHP/gbdb_framework/.config/.greenql.env.php
```

Example:

```php
<?php
return [
    'api_auth' => 'secret'
];
```

GreenQL usage:

```gql
OUTPUT ENV("api_auth");
```
