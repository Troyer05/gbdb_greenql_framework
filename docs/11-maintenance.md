# Maintenance

## Backups

```php
GBDB::createBackup();
GBDB::fullBackup('/path/to/backups');
```

## Migration

```php
GBDB::update('/path/to/old/assets/DB/GBDB');
GBDB::migrate('/path/to/old/assets/DB/GBDB');
```

The migration creates `.DB/.system`, `.storage`, `.scripts`, `.temp`, `.backups` and `.media`, and creates `.config/.greenql.env.php` if missing.
