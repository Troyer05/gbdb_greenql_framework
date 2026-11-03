# 13 – Rezepte

Die PHP-Beispiele setzen den geladenen Autoloader voraus. Für Versuche eine getrennte Kopie verwenden. Änderungen an Schema und Daten sind echte Schreiboperationen.

## Einen Datenbereich einrichten

Im GreenQL Studio ausführen:

```gql
GROW INSTANCE beispiel;
USE INSTANCE beispiel;
GROW BASE shop;
GROW TABLE produkte WITH sku, name, preis, aktiv IN shop;
SEED produkte WITH sku="P-001", name="Tasse", preis="9.90", aktiv="1" IN shop;
PICK * FROM produkte IN shop;
```

Danach aus PHP lesen:

```php
$produkte = GBDB::withInstance('beispiel', static function () :array {
    return GBDB::getData('shop', 'produkte');
});
```

## Ein Produkt ändern und wieder lesen

```php
$ergebnis = GBDB::withInstance('beispiel', static function () :mixed {
    if (!GBDB::editData('shop', 'produkte', 'sku', 'P-001', ['preis' => '10.90'])) {
        throw new RuntimeException('Produkt konnte nicht geändert werden.');
    }

    return GBDB::getData('shop', 'produkte', true, 'sku', 'P-001');
});
```

Ein gefiltertes Ergebnis kann ein einzelner Datensatz sein. Es muss nicht die gleiche Form haben wie die vollständige Tabelle.

## Leseschlüssel über die Oberfläche

1. Als Admin die Seite **Public API** öffnen.
2. Einen aktiven Schlüssel mit `gbdb-read` und passender Ablaufzeit anlegen.
3. Eine POST-Anfrage senden:

```json
{
  "key": "API_SCHLUESSEL",
  "do": "gbdb.page",
  "instance": "beispiel",
  "base": "shop",
  "table": "produkte",
  "page": 1,
  "per_page": 10
}
```

Der Schlüssel ist im Standardmodul nicht auf `beispiel/shop/produkte` beschränkt. Für einen öffentlich genutzten Produktkatalog ein eigenes Modul schreiben, das die Instanz und Tabelle serverseitig festlegt. Ein vollständiges Modulbeispiel steht im [API-Kapitel](07-public-api.md).

## Eine Textsuche ausführen

```gql
USE INSTANCE beispiel;
FULLTEXT "Tasse" FROM produkte IN shop COLUMNS name,sku LIMIT 20;
```

Oder über die Public API:

```json
{
  "key": "API_SCHLUESSEL",
  "do": "gbdb.fulltext",
  "instance": "beispiel",
  "base": "shop",
  "table": "produkte",
  "query": "Tasse",
  "columns": ["name", "sku"],
  "limit": 20
}
```

## Schema per Skript ergänzen

```gql
USE INSTANCE beispiel;
ALTER TABLE produkte ADD COLUMN beschreibung DEFAULT "" IN shop;
DESCRIBE produkte IN shop;
```

Das ist eine Schemaänderung. Bei replizierten Instanzen muss sie auf dem Hub im GDP-Wartungsablauf erfolgen, beispielsweise über `GDP::schemaMaintenance()`. Für Framework-Releases eine [Updater-Migration](../PHP/gbdb_framework/updater/migrations/README.md) schreiben und die Zielinstanz ausdrücklich wählen.

## Speicherstatus anzeigen

```php
$status = GBDB::backendStatus();
$modus = $status['mode'];
$abgleichAusstehend = $status['sync_pending'];
```

Eine Umschaltung ist ein administrativer Vorgang. Einrichtung, Bedeutung des maßgeblichen Datenstands und Rückkehr beschreibt das [Fallback-Kapitel](16-sql-failover.md).

## SecondServer prüfen

```php
if (SecondServer::ping()) {
    $treiber = SecondServer::driver();
}
```

URL und statischer Schlüssel müssen auf beiden Seiten konfiguriert sein. Einen Testauftrag mit `SecondServer::startJob('Test', ['name' => 'Max'])` starten. Der Auftrag wird im aktuellen Backend-Aufruf ausgeführt.

## Zwei Knoten verbinden

Auf dem Hub die gewünschte Instanz für Replikation freigeben und einen Einmalcode erstellen. Auf dem Replikat die vollständige Hub-URL und den Code in **Distributed (GDP)** eintragen und synchronisieren. Vorhandene lokale Tabellen werden nicht ohne Freigabe ersetzt. Detaillierter Ablauf: [GDP-Schnellstart](../PHP/GDP.md).
