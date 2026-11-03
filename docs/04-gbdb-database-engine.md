# 04 – Die GBDB-Datenbank

Anwendungscode verwendet die öffentliche Klasse **`GBDB`** aus `runtime/facade.php`. Sie führt Engine-Aufrufe innerhalb der Speicherlaufzeit aus. Die interne Klasse `GBDBEngine` und ihre Traits sollten Anwendungen nicht direkt aufrufen.

## Instanzen und Schema

```php
<?php

require_once __DIR__ . '/gbdb_framework/autoloader.php';

GBDB::createInstance('beispiel');

GBDB::withInstance('beispiel', static function () :void {
    GBDB::createDatabase('shop');
    GBDB::createTable('shop', 'produkte', ['sku', 'name', 'preis', 'aktiv']);
});
```

Das Beispiel liegt neben `gbdb_framework`. Instanzen trennen logische Datenbereiche; Datenbanken gruppieren Tabellen. `createTable()` erhält eine Spaltenliste. Eine numerische `id` wird von der Engine verwaltet und muss nicht als eigene Spalte definiert werden.

| Methode | Verwendung |
| --- | --- |
| `createInstance($name)` | Instanz anlegen |
| `existsInstance($name)` | Vorhandensein prüfen |
| `setInstance($name)` / `getInstance()` | Aktiven Kontext setzen oder lesen |
| `withInstance($name, $callback)` | Temporär wechseln und vorherigen Kontext wiederherstellen |
| `listInstances($includeSystem = false)` | Instanzen auflisten |
| `listDBs()` / `listTables($database)` | Datenbanken oder Tabellen auflisten |
| `getKeys($database, $table)` | Spalten lesen |
| `addColumn($database, $table, $column, $default)` | Spalte ergänzen |

Löschmethoden wie `deleteInstance()`, `deleteDatabase()` und `deleteTable()` verändern den Bestand dauerhaft. Ihre genauen Parameter stehen in der [Methodenreferenz](99-api-reference/gbdb-engine-methods.md).

## Schreiben, Lesen, Ändern und Löschen

Die folgenden Aufrufe setzen die aktive Instanz `beispiel` und die oben angelegte Tabelle voraus:

```php
GBDB::setInstance('beispiel');

$id = GBDB::insertData('shop', 'produkte', [
    'sku' => 'P-001',
    'name' => 'Tasse',
    'preis' => '9.90',
    'aktiv' => '1'
]);

$alle = GBDB::getData('shop', 'produkte');
$aktive = GBDB::getData('shop', 'produkte', true, 'aktiv', '1');

$geaendert = GBDB::editData('shop', 'produkte', 'id', $id, ['preis' => '10.90']);
$vorhanden = GBDB::elementExists('shop', 'produkte', 'id', $id);
```

| Aufruf | Rückgabe / Besonderheit |
| --- | --- |
| `insertData()` | Numerische Datensatz-ID; Fehlerpfade und Exceptions beachten |
| `getData()` | `mixed`; je nach Filter und Trefferzahl Liste oder einzelner Datensatz |
| `editData()` / `deleteData()` | Boolesches Ergebnis; die angegebenen Filter bestimmen die betroffenen Zeilen |
| `elementExists()` | Boolesche Existenzprüfung |
| `nextID()` | Nächste ID ermitteln; eine vorher gelesene ID ist keine separate Reservierung |

Die genaue Form von `getData()` vor dem Iterieren prüfen. Wer immer eine Liste erwartet, kann das Ergebnis normalisieren:

```php
$treffer = GBDB::getData('shop', 'produkte', true, 'sku', 'P-001');
$liste = !is_array($treffer) || $treffer === []
    ? []
    : (array_key_exists('id', $treffer) ? [$treffer] : array_values($treffer));
```

`GBDB::get()` unterstützt zusätzlich eine ältere Kurzform mit Filterspalte als drittem Parameter. Für neue Beispiele ist die explizite `getData()`-Form leichter nachvollziehbar. PHP-Methodennamen unterscheiden keine Groß- und Kleinschreibung.

## Seiten und Cursor

```php
$seite = GBDB::page('shop', 'produkte', 1, 25);
$ersterBlock = GBDB::cursor('shop', 'produkte', 100, null);

if ($ersterBlock['cursor'] !== null) {
    $naechsterBlock = GBDB::cursor('shop', 'produkte', 100, $ersterBlock['cursor']);
}
```

`page()` liefert `ok`, `page`, `per_page`, `total`, `pages` und `rows`. `cursor()` liefert `ok`, `rows`, `cursor`, `offset` und `limit`. Der zurückgegebene Cursor ist ein Base64-kodierter Offset; er wird unverändert weitergereicht. Eine feste Datenansicht über mehrere Anfragen ist damit nicht zugesichert.

**Speichergrenze:** Beide Methoden lesen derzeit zunächst die gesamte Tabelle über `getData()`. Sie verkleinern die Antwort, nicht automatisch den internen Speicherbedarf.

## Sammeloperationen und zeilenweise Verarbeitung

```php
$bericht = GBDB::bulkInsert('shop', 'produkte', [
    ['sku' => 'P-002', 'name' => 'Teller', 'preis' => '7.50', 'aktiv' => '1'],
    ['sku' => 'P-003', 'name' => 'Schale', 'preis' => '6.90', 'aktiv' => '1']
], true);

$verarbeitet = GBDB::streamRows('shop', 'produkte', static function (array $zeile, int $index) :void {
    // Die jeweilige Zeile verarbeiten.
}, 500);
```

`bulkInsert()` liefert einen Bericht mit `ok`, `ids` und `errors`. `streamRows()` ruft einen Callback je Zeile auf, lädt derzeit aber ebenfalls vorab die Tabelle und teilt sie dann in Blöcke. Es ist kein durchgehend speicherkonstanter Dateistream.

## Suche, Indizes und Schemata

```php
$treffer = GBDB::fulltext('shop', 'produkte', 'Tasse', ['name', 'sku'], 20);
```

Für Suche mit Spalten und Limit `fulltext()` oder `fulltext_search()` verwenden. Die ebenfalls vorhandene Methode `fullTextSearch()` hat in der öffentlichen Fassade nur drei Parameter.

GreenQL kann beispielsweise Indizes und Regeln anlegen:

```gql
USE INSTANCE beispiel;
INDEX produkte sku IN shop;
UNIQUE INDEX produkte sku IN shop;
SHOW INDEXES FROM produkte IN shop;
ALTER TABLE produkte ADD CONSTRAINT REQUIRED name IN shop;
```

Typisierte Schemata, Constraints und aktive Indexnutzung sind laut interner Statusmatrix teilweise experimentell. Auf Datenintegrität angewiesene Anwendungen müssen diese Regeln zusätzlich in ihrer Fachlogik prüfen. Eine angelegte Indexdefinition ist keine Zusicherung, dass jede Abfrage den Index verwendet.

## Transaktionen

Vorhanden sind `begin()`, `commit()`, `rollback()`, `savepoint()` und `rollbackTo()` sowie entsprechende GreenQL-Befehle. Die Laufzeit und Engine verwenden eigene Sperren, Snapshots und Wiederherstellungsmechanismen. Das ersetzt keine allgemeine ACID-Zusage für mehrere Dateien, mehrere HTTP-Anfragen oder mehrere GDP-Knoten.

Transaktionsabläufe bei Fehlern ausdrücklich abbrechen und für das eigene Schema prüfen. Die [Statusübersicht](18-feature-status.md) nennt die noch experimentelle Freigabe der Engine-Transaktionen. Im SQL-Fallback führt die Speicherlaufzeit einen schreibenden Abschnitt zusätzlich in einer SQL-Transaktion aus.

## Wartung und Migration

`dbHealth()`, `tableStats()`, `repairReport()`, `repairIndex()`, `repairWal()` und `autoMaintenance()` unterstützen die Diagnose und Wartung. Manche Prüffunktionen können Metadaten anlegen oder aktualisieren; vor Reparaturen sichern.

`GBDB::update()` und `GBDB::migrate()` übernehmen Datenstrukturen aus einem älteren Speicher. Sie sind **Datenmigrationen**, keine Installation eines Framework-Releases. Dafür ist der [Framework-Updater](../PHP/FRAMEWORK_UPDATER.md) zuständig.

GDP setzt auf denselben CRUD-Schreibwegen auf. Für replizierte Instanzen gelten zusätzlich Policies und Strukturwächter; siehe [GDP](../PHP/GDP.md).
