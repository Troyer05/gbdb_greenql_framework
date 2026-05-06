# Overview

GBDB Framework is structured as a compact PHP framework with a file-based database engine, a query language, a UI and integration helpers.

## Main folders

- `PHP/gbdb_framework/.config/`: framework configuration and GreenQL ENV.
- `PHP/gbdb_framework/GBDB_GQL/db_engine/`: GBDB engine traits and facade class.
- `PHP/gbdb_framework/GBDB_GQL/greenql/`: GreenQL parser, runtime and execution logic.
- `PHP/gbdb_framework/core/`: helper classes such as Auth, Http, Cache, Validate, Json and Route.
- `PHP/gbdb_framework/public/`: developer UI, public API and browser-facing assets.
- `PHP/gbdb_framework/SRV/`: SecondServer service runtime and job modules.
- `PHP/gbdb_framework/json/patterns/`: reusable database structure patterns.

## Runtime loading

Use `PHP/gbdb_framework/gbdb.php` or `PHP/gbdb_framework/autoloader.php`. The old `_config.inc.php` entrypoint was removed.
