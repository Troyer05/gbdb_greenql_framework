# 20 – Vollständige HTTP-Referenz des GBDB-Moduls

Diese Referenz beschreibt alle **29 Aktionen** aus `PublicAPI_GBDB::ACTIONS` des mitgelieferten Quellstands. Einführung, Schlüsselverwaltung und eigene Module: [Public API](07-public-api.md). Der öffentliche HTTP-Einstieg ist `public_api.php`.

## Gemeinsamer Vertrag

Alle Aktionen verwenden HTTP POST und JSON. Den Schlüssel mit `Authorization: Bearer API_SCHLUESSEL` oder im Body als `key` beziehungsweise `auth_key` übergeben. `key` hat vor `auth_key` und dem Header Vorrang; ein ausdrücklich leerer Wert wird nicht automatisch durch den Header ersetzt.

`do` enthält `gbdb.aktion`. Wenn `do` leer ist oder fehlt, können `module: "gbdb"` und `action: "aktion"` verwendet werden. Die Auswahlnamen werden nicht automatisch kleingeschrieben; die dokumentierten Namen exakt übernehmen.

Die Tabellen nennen zusätzliche Body-Felder neben Authentifizierung und Aktionswahl:

- **Pflicht:** Der Handler erwartet diese Felder; zusätzliche Typ- und Inhaltsprüfung kann erforderlich sein.
- **Optional:** Verhalten bei fehlendem Feld steht hinter dem Namen.
- `base` und `database` bezeichnen dieselbe Datenbank; bei zwei gesetzten Werten wird `base` verwendet.
- `instance` bei Fachzugriffen immer ausdrücklich als zulässigen Instanznamen angeben. Ohne Angabe arbeitet die Aktion im aktuellen Anfragekontext, normalerweise `default`.

Die Antwort hat die Hülle `ok`, `status` und `data`. Die Spalte **Ergebnis** beschreibt den Inhalt von `data`. HTTP 200 allein belegt nicht den Erfolg einer Datenänderung: `done`, `id`, innere `ok`-Felder und Fehlerlisten ebenfalls auswerten. Die Schnittstelle übersetzt nicht jedes fachlich negative Ergebnis in HTTP 4xx/5xx.

## Lesen und Diagnose

Alle folgenden Aktionen erfordern **`gbdb-read`**.

| Aktion | Pflicht | Optional | Ergebnis |
| --- | --- | --- | --- |
| `gbdb.info` | Keine weiteren Felder | Keine | `module`, `rights`, `actions` |
| `gbdb.list_instances` | Keine weiteren Felder | `include_system = false` | Aktive `instance` und Instanzliste in `items` |
| `gbdb.list_bases` | Keine weiteren Felder | `instance` | `instance`, `items` |
| `gbdb.list_tables` | `base` | `instance`, `desc = false` | `base`, `items` |
| `gbdb.keys` | `base`, `table` | `instance` | Spaltenliste |
| `gbdb.next_id` | `base`, `table` | `instance` | Numerische nächste ID; keine eigenständige Reservierung |
| `gbdb.get` | `base`, `table` | `instance`, `where`, `is`, `options` | Tabelle oder gefilterter Datensatz; Form prüfen |
| `gbdb.exists` | `base`, `table`, `where`, `is` | `instance` | `exists` als boolescher Wert |
| `gbdb.page` | `base`, `table` | `instance`, `page = 1`, `per_page = 50` | `ok`, `page`, `per_page`, `total`, `pages`, `rows` |
| `gbdb.cursor` | `base`, `table` | `instance`, `limit = 100`, `cursor` | `ok`, `rows`, nächster `cursor`, `offset`, `limit` |
| `gbdb.fulltext` | `base`, `table`, `query` | `instance`, `columns = []`, `limit = 50` | Suchergebnis der Engine als Array |
| `gbdb.stats` | `base` | `instance`, `table` | Statistik einer Tabelle oder der Datenbank |

`list_instances` wechselt nicht zunächst in das optionale Feld `instance`. `include_system` ist kein allgemeiner Berechtigungsschalter, um danach intern gespeicherte Schlüssel oder Benutzer zu lesen; die Instanz-Sandbox bleibt aktiv.

Bei `get` bedeuten `where` und `is` eine Filterspalte und den Vergleichswert. Das optionale Objekt `options` unterstützt `sort`, `dir`, `offset` und `limit` über den GBDB-Kompatibilitätszugriff. Nicht alle Abfrageformen aus GreenQL sind damit automatisch als JSON-Filterobjekt verfügbar.

`page`, `per_page` und `limit` als JSON-Zahlen senden. Die Seiten-/Cursorhelfer begrenzen die Blockgröße auf 1 bis 1000, laden derzeit jedoch intern die Tabelle. Den zurückgegebenen Cursor unverändert wiederverwenden. `cursor: null` beziehungsweise ein leerer Wert bezeichnet den Anfang.

## Anlegen und Ändern

Alle folgenden Aktionen erfordern **`gbdb-write`**. Dieses Recht umfasst bereits die hier aufgeführten Strukturänderungen.

| Aktion | Pflicht | Optional | Ergebnis |
| --- | --- | --- | --- |
| `gbdb.create_base` | `base` | `instance` | `done`, `base` |
| `gbdb.create_table` | `base`, `table`, nicht leeres `columns`-Array | `instance` | `done`, `base`, `table`, `columns` |
| `gbdb.add_column` | `base`, `table`, `column` | `instance`, `default = ""` | `done`, `column`; ungültige Spalte kann als fachliches Fehlerergebnis erscheinen |
| `gbdb.insert` | `base`, `table`, nicht leeres `data`-Objekt | `instance` | `id`; positive Zahl auf Erfolg prüfen |
| `gbdb.bulk_insert` | `base`, `table`, nicht leeres `rows`-Array | `instance`, `transactional = true` | `ok`, `ids`, `errors` |
| `gbdb.update` | `base`, `table`, `where`, `is`, nicht leeres `data`-Objekt | `instance` | `done` |
| `gbdb.upsert` | `base`, `table`, `where`, `is`, nicht leeres `data`-Objekt | `instance` | `ok`, `action` mit `insert` oder `update`, `id` |
| `gbdb.delete` | `base`, `table`, `where`, `is` | `instance` | `done` |

`data` enthält Feldwerte, `columns` die Namen der Spalten und `rows` eine Liste von Datensatzobjekten. `upsert` setzt beim Einfügen die Filterspalte auf den Vergleichswert. Bei mehreren Treffern keine Einzelzeilenwirkung voraussetzen.

Für `bulk_insert` den inneren Bericht prüfen. Bei selbst begonnener Transaktion und Fehlern versucht die Engine den Rollback; ohne Transaktion sind Teiländerungen möglich. Das ersetzt keine über mehrere Anfragen oder GDP-Knoten verteilte Transaktion.

## Instanzen und löschende Strukturaktionen

Alle folgenden Aktionen erfordern **`gbdb-admin`**.

| Aktion | Pflicht | Optional | Ergebnis |
| --- | --- | --- | --- |
| `gbdb.create_instance` | `instance` | Keine | `done`, `instance` |
| `gbdb.drop_instance` | `instance` | `force = false` | `done`, `instance` |
| `gbdb.drop_base` | `base` | `instance` | `done`, `base` |
| `gbdb.drop_table` | `base`, `table` | `instance` | `done`, `base`, `table` |

`force` kann das Entfernen einer gefüllten Instanz erlauben. Diese Aktionen nicht ohne explizite Auswahl und vorherige Sicherung in allgemein zugängliche Oberflächen einbauen. Ein fehlgeschlagenes `done` ist trotz äußerem HTTP 200 auszuwerten.

## Skripte

Beide Aktionen erfordern **`gbdb-gql`**.

| Aktion | Pflicht | Optional | Ergebnis |
| --- | --- | --- | --- |
| `gbdb.query` | `script` mit Skripttext | `instance`, `ctx = {}`, `params = {}` | GreenQL-Bericht mit innerem `ok`, Meldungen und Ergebnissen |
| `gbdb.run_script` | `script` oder alternativ `script_name` mit Dateiname | `instance`, `ctx = {}`, `params = {}` | Ergebnis des Skriptdateiaufrufs |

Ein gesetztes `script` wird nicht allein wegen leerem Inhalt durch `script_name` ersetzt. Dateizugriffe bleiben auf die erlaubten Skriptwurzeln begrenzt. Skriptparameter als Werte in `params` übergeben, nicht ungeprüft in den Skripttext einsetzen. Das Recht erlaubt weitreichende GreenQL-Befehle und ist kein Ersatz für ein eng begrenztes Fachmodul.

## Speicherverwaltung

Alle drei Aktionen erfordern **`gbdb-backend`** und betreffen den Speicher der gesamten Installation.

| Aktion | Pflicht | Optional | Ergebnis |
| --- | --- | --- | --- |
| `gbdb.backend_status` | Keine weiteren Felder | Keine | Status mit `mode`, Bereitschaft, ausstehendem Abgleich und letztem Fehler |
| `gbdb.backend_switch` | `backend` als `gbdb` oder `sql` | Keine | Neuer Backend-Status |
| `gbdb.backend_sync` | Keine weiteren Felder | Keine | Abgleichbericht des maßgeblichen Speichers zum anderen Backend |

Voraussetzungen und Rückschaltung: [SQL-Fallback](16-sql-failover.md). Einen fehlenden oder veralteten Spiegel nicht durch das bloße Umschalten als aktuell behandeln.

## Zusammenhängendes Schreibbeispiel

Voraussetzung: Die Instanz `api_beispiel`, Datenbank `shop` und Tabelle `produkte` mit `sku`, `name`, `preis`, `aktiv` existieren. Ein auf diese Fachaktion begrenztes eigenes Modul ist für öffentliche Clients vorzuziehen. Die folgenden Anfragen benötigen jeweils einen gültigen Bearer-Schlüssel mit `gbdb-write`.

Produkt anlegen oder anhand seiner SKU aktualisieren:

```json
{
  "do": "gbdb.upsert",
  "instance": "api_beispiel",
  "base": "shop",
  "table": "produkte",
  "where": "sku",
  "is": "P-100",
  "data": {"name":"Becher","preis":"8.90","aktiv":"1"}
}
```

Mehrere Produkte einfügen:

```json
{
  "do": "gbdb.bulk_insert",
  "instance": "api_beispiel",
  "base": "shop",
  "table": "produkte",
  "transactional": true,
  "rows": [
    {"sku":"P-101","name":"Teller","preis":"7.50","aktiv":"1"},
    {"sku":"P-102","name":"Schale","preis":"6.90","aktiv":"1"}
  ]
}
```

Abschließend mit einem Schlüssel mit `gbdb-read` lesen:

```json
{
  "do": "gbdb.get",
  "instance": "api_beispiel",
  "base": "shop",
  "table": "produkte",
  "options": {"sort":"sku","dir":"ASC","offset":0,"limit":10}
}
```

Das Ergebnis muss drei Produkte enthalten, wenn die Tabelle vor diesen Anfragen leer war und alle Schreibberichte erfolgreich sind. Die Beispiele sind keine Erlaubnis, Zugangsdaten in Browsercode zu veröffentlichen.

## Grenzen und Fehlerbehandlung

- Rechte beschränken Aktionen, nicht automatisch einzelne Instanzen oder Tabellen. Für Mandantenbindung ein eigenes Modul verwenden.
- `requiresRights()` gehört zum HTTP-Dispatcher-Vertrag. Ein direkter interner Aufruf von `PublicAPI_GBDB::handle()` durch PHP-Code umgeht die vorgeschaltete Schlüsselprüfung und ist kein Ersatz für den HTTP-Einstieg.
- Pflichtfelder werden mit `isset()` geprüft. Ein Feld mit `null` gilt dabei grundsätzlich nicht als vorhandener Pflichtwert; optionale Felder haben die jeweilige Standardbehandlung.
- Einige Felder werden konvertiert oder bereinigt. Das ist keine allgemeine JSON-Schema-Validierung. Anwendungsseitig korrekte Typen und zulässige Bezeichner senden.
- Äußere HTTP-Fehler und innere fachliche Fehler getrennt behandeln. Wiederholungen von Schreibanfragen können doppelte Änderungen erzeugen; die Public API hat keine allgemeine Idempotenzschlüssel-Funktion.
