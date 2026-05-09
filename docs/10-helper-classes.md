# 10 — Core Helper Classes

The `core/` directory contains utility classes used by GBDB, UI, Public API and applications.

## `Http`

Use for server-side HTTP requests.

Typical situations:

- call external APIs;
- fetch update manifests;
- send SecondServer requests;
- post JSON to another service.

## `Validate`

Use for simple form/API validation.

Examples:

```php
if (!Validate::email($email)) {
    // reject invalid email
}

$missing = Validate::required($_POST, ["username", "email"]);
```

## `FileTool` / `FS`

Use for file-system helpers: reading/writing files, checking paths, copying, deleting, directory operations. Prefer these helpers over ad-hoc file logic when working inside the framework.

## `Json`

Use for JSON encode/decode helpers, especially when respecting framework pretty-print settings.

## `Cookie` and `Session`

Use for request/session state. `Auth` already uses these internally. For application state, keep sensitive data server-side where possible.

## `Cache`

Use for short-lived cached values. GBDB also has runtime/file cache helpers for query results and metadata.

## `Crypt`

Use for encryption/decryption helpers. Do not invent a separate crypto format unless required.

## `Format`

Use for cleaning strings, identifiers and output-friendly formatting. In GBDB/GreenQL contexts, prefer framework clean-name helpers to avoid invalid file/table names.

## `Route`

Small routing helper for simple apps:

```php
Route::get("/", function () {
    echo "home";
});

Route::post("/save", function () {
    echo "saved";
});

Route::dispatch();
```

Use `Route` for small framework-native apps. For a large MVC application, you may still build your own router.

## `SQL` and `DatabaseBridge`

Use when you need a SQL backend or migration bridge. GBDB remains the primary file-backed engine; SQL helpers are useful for hybrid deployments or legacy data.

## `Tools`

General utilities:

- password generation/strength checks;
- domain info;
- IDs/tokens;
- country lookup by IP;
- ping helpers;
- QR/barcode helpers.

## `Converter`

Use for data conversion utilities when present in your workflow.

## `ReCaptcha`

Use for validating reCAPTCHA on public forms.

## `Ref`

Use for current file/path/reference helpers.

## `Time`

Use for human-readable time differences such as `Time::timeAgo($timestamp)`.

## Helper selection guide

| Need | Use |
|---|---|
| Validate API/form input | `Validate` |
| HTTP GET/POST | `Http` |
| Generate token/ID/password | `Tools` |
| Clean table/base/user input | `Format` / GreenQL clean helpers |
| Store request state | `Session`, `Cookie` |
| Encrypt/decrypt framework data | `Crypt` |
| Route small pages | `Route` |
| SQL fallback | `SQL`, `DatabaseBridge` |
| JSON files/config | `Json`, `FileTool` |
