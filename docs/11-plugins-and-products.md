# 11 — Plugins and Product Integrations

The `plugins/` directory contains product-specific wrappers.

## mRoot

`plugins/mroot.php` contains the `mRoot` class. It handles license/update related tasks such as remote update metadata, download/extract/copy workflows, backup preservation and product auth values from `Vars`.

Use mRoot for:

- application update checks;
- release download/install flows;
- license checks;
- product maintenance.

Always backup before running update operations.

## MuseumQR

`plugins/museumqr.php` contains `MqrApi`, a wrapper for MuseumQR API calls.

Public methods include:

- `getFeedback($item_id = "")`
- `getObjects()`
- `getObject($oid)`
- `getSettings()`
- `getLangs()`
- `getTours()`

Use it when an application needs to fetch MuseumQR objects, language settings, tours or feedback from a configured MuseumQR endpoint.

## ShareSuite

`plugins/sharesuite.php` contains `ShareSuiteAPI`. Use it for ShareSuite-specific API access configured by `Vars::sharesuite_api_url()`, `Vars::sharesuite_api_key()`, `Vars::sharesuite_api_auth()` and `Vars::sharesuite_sid()`.

## EventQR

`plugins/eventqr.php` contains `EqrAPI`. Use it for EventQR-specific API access configured through the EventQR values in `Vars`.

## Plugin best practices

- Keep product API keys in config, not source code.
- Keep wrapper methods small and predictable.
- Return normalized arrays so calling code does not depend on raw third-party API details.
- Log API failures with enough context but without leaking secrets.
- Keep plugin calls out of low-level GBDB engine code.
