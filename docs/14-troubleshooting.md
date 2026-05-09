# 14 — Troubleshooting

## `Fatal error: Cannot use 'true' as class name`

This happens when a module/helper class name was generated from a boolean-like value or invalid token. PHP reserves `true`, `false` and `null`. Ensure every module class name starts with a valid prefix such as `PublicAPI_Example` and never uses raw action/right names as class names.

## Tables are cut off in the UI

Common causes:

- table container has fixed height/overflow issues;
- logs take too much vertical space;
- responsive CSS does not allow horizontal scroll;
- long cell content is not wrapped/truncated.

Fix strategy:

- wrap tables in an overflow container;
- make logs collapsible;
- limit log height;
- add `min-width` for wide tables and `overflow-x:auto` on the parent.

## API returns unauthorized

Check:

1. Is `key` present in the JSON body?
2. Is the key active?
3. Is it expired?
4. Does it have the required right for the action?
5. Is the `do` value exactly `module.action`?
6. Is the module loaded and named correctly?

## GreenQL command fails unexpectedly

Check:

- active instance: run `SHOW INSTANCES;` and `USE INSTANCE ...;`
- active base: specify `IN base` explicitly;
- command semicolons;
- JSON object syntax in `WITH` payloads;
- variable names begin with `_` or `$`;
- table/base names only use safe identifier characters.

## Rows do not appear after insert

Check:

- correct instance selected;
- correct base/table names;
- schema columns match inserted keys;
- filters are not excluding the row;
- caches are not stale;
- insert result/error payload.

## Cannot write to database files

Check file permissions for `GBDB_GQL/.DB/`. The web server user must be able to create, modify and lock files.

## Public API custom module is not found

Check:

- file is in `public/includes/public_api/public_api_modules/`;
- class name starts with `PublicAPI_`;
- `name()` returns the module prefix used in `do`;
- `handle()` supports the requested action;
- syntax errors are not stopping module loading.

## SecondServer call fails

Check:

- `Vars::srvp_ip()` points to the correct backend;
- `Vars::srvp_ssl()` matches HTTP/HTTPS reality;
- static keys match on both sides;
- backend entry path is reachable;
- token request succeeds before instruction request;
- server logs for rejected auth.

## Recovery after crash/interrupted write

1. Stop writes if possible.
2. Create a copy of the whole `.DB` directory.
3. Run health/check commands.
4. Run repair report.
5. Repair indexes/WAL/relations as needed.
6. Verify storage/checksums.
7. Resume writes only after checks pass.
