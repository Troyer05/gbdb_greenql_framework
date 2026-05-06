# Plugins

Plugins are loaded from `PHP/gbdb_framework/plugins/` by the autoloader.

A plugin is skipped when:

- the file ends with `.disabled.php`
- the file ends with `.php.disabled`
- a sidecar file named `<plugin>.php.disabled` exists

This allows safe deactivation without deleting plugin code.
