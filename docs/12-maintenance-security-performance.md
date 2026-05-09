# 12 — Maintenance, Security and Performance

## Backups

Create backups before:

- framework updates;
- schema migrations;
- mass imports;
- destructive GreenQL scripts;
- repair/recovery operations;
- changing encryption settings.

Use GBDB backup helpers, GreenQL backup commands or the UI backup page.

## Health checks

Useful GreenQL commands:

```gql
CHECK users IN app;
HEALTH users IN app;
STATS users IN app;
MONITOR users IN app;
SHOW META FROM users IN app;
```

Useful PHP methods include `GBDB::dbHealth()`, `GBDB::tableStats()`, `GBDB::repairReport()` and `GBDB::dashboardData()`.

## Repair workflow

Recommended safe order:

1. Create a full backup.
2. Run health/check commands.
3. Generate a repair report.
4. Repair indexes/relations/WAL if needed.
5. Verify checksums and storage checks.
6. Re-run health checks.
7. Keep logs and backup until the issue is fully resolved.

## Caching

Use caching for repeated reads, metadata, permissions, counters and query results. Invalidate caches after writes.

Examples:

```php
$value = GBDB::queryResultCache("users-active", function () {
    return GBDB::getData("app", "users", true, "active", "1");
}, 60, ["users"]);

GBDB::cacheInvalidateTag("users");
```

## Indexing strategy

Add indexes for columns used in frequent `WHERE`, `SORT`, joins or lookup operations. Avoid indexing every column by default.

Good index candidates:

- `uid`
- `email`
- `slug`
- `created_at`
- `user_id`
- status/active fields used heavily in dashboards

## Public API hardening

- Use least-privilege rights.
- Prefer custom modules for public clients.
- Validate request bodies.
- Rate-limit sensitive actions at web server or module level.
- Log writes and destructive actions.
- Never expose secrets in API responses.

## UI hardening

- Disable PHP execution tools in production or restrict to super admins.
- Hide system instances/bases.
- Protect all writes with CSRF.
- Use strong admin passwords.
- Use HTTPS for admin access.

## Storage hardening

- Prevent direct web access to `.DB`, `.config`, temp, lock and backup folders.
- Use file permissions that allow the web server to write but do not expose files to all local users.
- Keep backup files outside public web roots when possible.

## Performance checklist

- Use `getData()` filters instead of full-table reads.
- Use `page()`/`cursor()` for large tables.
- Use `bulkInsert()` for imports.
- Use `streamRows()` for batch processing.
- Add indexes to repeated lookup/filter columns.
- Keep logs compact and rotate old logs.
- Run auto maintenance on a schedule.
- Avoid running heavy repair/backup tasks during peak request time.
