# Public API

The public API entrypoint is:

```text
PHP/gbdb_framework/public/public_api.php
```

Access is controlled by `Vars::PUBLIC_API` in `.framework.env.php`.

Use this layer for external systems that need controlled JSON access to GBDB, schema, rows, queries or scripts.
