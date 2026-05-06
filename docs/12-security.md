# Security

## Storage protection

GBDB can tokenize/encrypt storage names via `Vars::crypt_data()` and `Vars::crypt_key()`.

## API keys

Do not ship default demo keys in production. Replace public API, SrvP and update keys before deployment.

## UI access

The developer UI is intended for DEV mode. Production deployments should restrict access at the web server level as well.

## Internal instances

System instances such as `gbdb-system`, `greenqluiv2system` and internal `__...` instances are hidden by `GBDB::listInstances()` by default.
