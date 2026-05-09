# 02 — Installation and Bootstrapping

## Expected directory layout

The uploaded package contains the framework under:

```text
SecondServerModul/
├── PHP/
│   ├── backend.php
│   ├── gbdb_ui.php
│   ├── public_api.php
│   └── gbdb_framework/
│       ├── autoloader.php
│       ├── functions.php
│       ├── .config/
│       │   ├── .framework.env.php
│       │   └── .greenql.env.php
│       ├── GBDB_GQL/
│       │   ├── db_engine/
│       │   ├── greenql/
│       │   └── .DB/
│       ├── core/
│       ├── SRV/
│       ├── plugins/
│       └── public/
└── vsc/
    └── greenql-language-*.vsix
```

## Entry points

| Entry point | Purpose |
|---|---|
| `PHP/gbdb_framework/autoloader.php` | Main include for normal PHP code. |
| `PHP/gbdb_ui.php` | Top-level entry to the GBDB UI. |
| `PHP/public_api.php` | Top-level public API entry. |
| `PHP/backend.php` | SecondServer backend entry. |
| `PHP/gbdb_framework/public/index.php` | Internal UI router. |
| `PHP/gbdb_framework/public/public_api.php` | Framework-local public API entry. |

## Minimal include

For application code, include the autoloader once:

```php
<?php

require_once __DIR__ . "/gbdb_framework/autoloader.php";

GBDB::setInstance("main");
```

The autoloader loads configuration, the database engine, GreenQL, core helpers, SRV classes and plugins.

## Configuration files

### `.config/.framework.env.php`

Defines class `Vars`. It contains application version, DB architecture, auth settings, email settings, SecondServer settings, JSON paths, SQL fallback credentials, reCAPTCHA, crypt settings and product API URLs.

Important methods include:

| Method | Meaning |
|---|---|
| `Vars::app_version()` | Framework/app version string. |
| `Vars::db_arch()` | Selected database architecture. |
| `Vars::main_instance()` | Default application instance. |
| `Vars::auth_instance()` | Instance used by the `Auth` system. |
| `Vars::DB_PATH()` | Physical GBDB storage path. |
| `Vars::crypt_data()` / `Vars::cryptKey()` | Storage encryption/obfuscation settings. |
| `Vars::srvp_ip()` / `Vars::srvp_ssl()` / `Vars::srvp_static_key()` | Remote SecondServer settings. |
| `Vars::init_cookies()` / `Vars::init_session()` | Cookie/session boot switches. |

Never commit real production secrets in this file. Treat default values as placeholders.

### `.config/.greenql.env.php`

GreenQL-specific environment file. Use it for script-level settings and paths consumed by the GreenQL runtime.

## First-run checklist

1. Place the `PHP/` directory under a web-accessible or application-controlled location.
2. Ensure PHP can read framework files and write to `GBDB_GQL/.DB/`.
3. Configure `.framework.env.php` with safe app, auth, DB, mail and SecondServer values.
4. Confirm `GBDB_GQL/.DB/.htaccess` or web server rules prevent direct public access to DB files.
5. Open the GBDB UI and create/verify the internal UI user store.
6. Run a small test script to create an instance, base, table and row.
7. Configure Public API keys only after the UI login is secured.

## Permission recommendation

On a Linux/Apache setup, the web server user needs write access to the DB storage and temp paths. Prefer group/ACL-based write permissions over `chmod 777`.

Typical pattern:

```bash
sudo chown -R youruser:www-data /var/www/html/SecondServerModul/PHP
sudo chmod -R 2775 /var/www/html/SecondServerModul/PHP/gbdb_framework/GBDB_GQL/.DB
sudo setfacl -R -m g:www-data:rwx /var/www/html/SecondServerModul/PHP/gbdb_framework/GBDB_GQL/.DB
sudo setfacl -R -d -m g:www-data:rwx /var/www/html/SecondServerModul/PHP/gbdb_framework/GBDB_GQL/.DB
```

## Production safety

- Disable development-only routes/pages.
- Keep `.config` outside public indexes when possible.
- Block direct web access to `.DB`, `.config`, temp, lock and backup directories.
- Rotate API keys and static SecondServer secrets before production.
- Use HTTPS for human/admin interfaces and any external API call that carries credentials.
