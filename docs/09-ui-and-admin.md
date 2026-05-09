# 09 — UI and Admin Tools

The framework contains a browser-based GBDB/GreenQL UI under `gbdb_framework/public/`.

## Main pages

| File | Purpose |
|---|---|
| `index.php` | Main public UI entry/router. |
| `includes/table.php` | Table/data navigation. |
| `includes/greenql_v2.page.php` | GreenQL editor page. |
| `includes/gql_scripts.page.php` | Script management/execution. |
| `includes/users.page.php` | UI user management. |
| `includes/public_api.page.php` | API key/module/log management. |
| `includes/backup.page.php` | Backup tools. |
| `includes/import_export.page.php` | Import/export tools. |
| `includes/monitoring.page.php` | Health/monitoring dashboard. |
| `includes/optimize.page.php` | Optimization tools. |
| `includes/migration.page.php` | Migration tools. |
| `includes/enterprise.page.php` | Enterprise/advanced operations. |
| `includes/env.page.php` | Environment/config display/edit helpers. |
| `includes/plugins.page.php` | Plugin tools. |
| `includes/reinstall.page.php` | Reinstall/reset workflows. |
| `includes/php_exec.page.php` | PHP execution tool; production-sensitive. |

## UI helper

`GreenQLUIv2Helper` is the central helper for UI auth, permissions, internal stores, public API key management, script execution and filtering.

Important method groups:

- Boot/session/CSRF: `boot()`, `csrf()`, `checkCsrf()`.
- UI users: `hasUsers()`, `createUser()`, `login()`, `logout()`, `users()`, `updateUser()`, `deleteUser()`, `resetPassword()`.
- Permissions: `isAdmin()`, `canWrite()`, `canStructure()`, `hasPermission()`, `canUseTool()`.
- Access control: `canAccessInstance()`, `canAccessDb()`, `instances()`, `databases()`, `tables()`.
- Public API management: `publicApiKeys()`, `createPublicApiKey()`, `updatePublicApiKey()`, `deletePublicApiKey()`, `publicApiLogs()`, `publicApiModules()`.
- Scripts: `parseParams()`, `scriptAllowed()`, `readScriptPath()`, `runScript()`.

## System data visibility

The UI should hide system/internal databases and tables from normal navigation. Developers extending the UI should filter system names and avoid exposing auth/API key tables to normal users.

## Permission strategy

| Role/use | Permissions |
|---|---|
| Super admin | All tools, all instances, all bases. |
| Data editor | Read/write selected bases, no structure tools. |
| Developer | GreenQL/scripts, structure tools, selected instances. |
| Viewer | Read-only table access. |
| API manager | Public API page only. |

## Production notes

- Disable or protect `php_exec.page.php` in production.
- Keep backup/migration/reinstall pages admin-only.
- CSRF-protect all write forms.
- Do not show system instances to non-admin users.
- Keep public API logs collapsible/limited because logs can grow quickly.
