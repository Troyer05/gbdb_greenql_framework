# GBDB / SecondServerModul Framework Documentation

This documentation package is written for developers who are new to the GBDB framework and need to understand the complete system: the file-based database engine, GreenQL scripting language, UI, public API, authentication, remote SecondServer layer, helper classes, plugins, maintenance tools, and recommended usage patterns.

## Start here

1. Read `01-overview.md` to understand what the framework is and how the pieces fit together.
2. Read `02-installation-and-bootstrapping.md` before deploying or moving the framework.
3. Read `03-architecture.md` for the directory layout and request lifecycle.
4. Read `04-gbdb-database-engine.md` for database usage.
5. Read `05-greenql-language.md` for GreenQL scripts and query syntax.
6. Read `07-public-api.md` if external systems should access GBDB.
7. Read `08-secondserver-srvp.md` if one server should talk to another server.
8. Use `99-api-reference/all-public-methods.md` as a method index.

## Documentation map

| File | Purpose |
|---|---|
| `01-overview.md` | Product-level overview and mental model. |
| `02-installation-and-bootstrapping.md` | Setup, includes, paths, config and first-run checklist. |
| `03-architecture.md` | Folder structure, autoloading, storage layout and runtime flow. |
| `04-gbdb-database-engine.md` | Core GBDB API, instances, bases, tables, CRUD, indexes and maintenance. |
| `05-greenql-language.md` | GreenQL syntax, commands, variables, functions, classes and scripts. |
| `06-authentication.md` | Auth class, users, JWT, 2FA, email verification and UI auth. |
| `07-public-api.md` | Public API architecture, rights, key management, GBDB module and custom modules. |
| `08-secondserver-srvp.md` | SRV backend, SrvFunctions and the remote client. |
| `09-ui-and-admin.md` | GBDB UI / GreenQL UI pages and permissions. |
| `10-helper-classes.md` | Core helper classes and when to use them. |
| `11-plugins-and-products.md` | mRoot, MuseumQR, ShareSuite and EventQR plugin wrappers. |
| `12-maintenance-security-performance.md` | Backup, repair, recovery, caching, locks, security and deployment hardening. |
| `13-recipes.md` | Common copy-paste workflows. |
| `14-troubleshooting.md` | Common errors and debugging paths. |
| `99-api-reference/all-public-methods.md` | Generated public method index. |

## Important naming model

GBDB uses three logical levels:

- **Instance**: tenant / isolated workspace.
- **Base**: database inside an instance.
- **Table**: row collection inside a base.

Most application code should select the correct instance first, then operate on a base/table pair.
