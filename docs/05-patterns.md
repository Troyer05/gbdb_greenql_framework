# Enterprise Patterns

Patterns are JSON files in:

```text
PHP/gbdb_framework/json/patterns/
```

They define instances, bases, tables and optional typed columns.

## Pattern format

```json
{
  "name": "intranet",
  "active": true,
  "description": "Example pattern",
  "structure": [
    {
      "base": "main",
      "tables": [
        {
          "name": "users",
          "useDataTypes": true,
          "rows": [
            {"rowName": "name", "defaultValue": "Max M.", "dataType": "string"}
          ]
        }
      ]
    }
  ]
}
```

If `dataType` is missing, the column is treated as type-less/mixed.

## API

```php
GBDB::listPatterns();
GBDB::savePattern($patternArray);
GBDB::deletePattern('intranet');
GBDB::setPatternActive('intranet', true);
GBDB::installPattern('intranet', 'customer_instance');
GBDB::patternTemplate('new_pattern');
```
