# Installation

## Requirements

- PHP 8.1 or newer
- Apache or Nginx with PHP enabled
- Write permission for the framework storage folder
- Optional: mbstring; fallback functions are included for minimal systems

## Steps

1. Upload the project files.
2. Configure `.framework.env.php`.
3. Ensure the web server can write to `PHP/gbdb_framework/GBDB_GQL/.DB/` and `.logs/`.
4. Open `PHP/gbdb_ui.php` in DEV mode.
5. Create or verify the initial admin/root user if the UI asks for setup.

## Permissions

On Linux, prefer group write permissions instead of `chmod 777`:

```bash
sudo chown -R "$USER":www-data PHP/gbdb_framework
sudo find PHP/gbdb_framework -type d -exec chmod 2775 {} +
sudo find PHP/gbdb_framework -type f -exec chmod 664 {} +
```
