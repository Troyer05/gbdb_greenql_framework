# 07 – Public API

Die Public API verarbeitet JSON-Anfragen per **HTTP POST** an `public_api.php`. Die bereitgestellten Aktionen sind für vertrauenswürdige Integrationen gedacht; für öffentliche Geräte- oder Kundenfunktionen eigene, fachlich begrenzte Module schreiben.

## Anfrage und Antwort

```http
POST /public_api.php
Content-Type: application/json
Authorization: Bearer API_SCHLUESSEL
```

```json
{
  "do": "gbdb.page",
  "instance": "beispiel",
  "base": "shop",
  "table": "produkte",
  "page": 1,
  "per_page": 25
}
```

Alternativ den Schlüssel im JSON-Feld `key` oder `auth_key` senden. Falls mehrere Varianten gesetzt sind, hat `key` Vorrang vor `auth_key` und dem Bearer-Header. `do` hat die Form `modul.aktion`; alternativ werden getrennte Felder `module` und `action` akzeptiert.

Antworten verwenden den tatsächlichen HTTP-Status und diese Hülle:

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "ok": true,
    "page": 1,
    "per_page": 25,
    "total": 0,
    "pages": 0,
    "rows": []
  }
}
```

Das Beispiel zeigt eine leere Tabelle. Der Inhalt von `data` hängt von der Aktion ab. Die äußere Eigenschaft `ok` beschreibt den HTTP-Status; bei Operationen mit eigenem Ergebnisbericht zusätzlich dessen `ok`, `done` oder Fehlerliste auswerten.

Der Dispatcher begrenzt Anfragen auf **4 MiB** und auf **600 Anfragen je IP und Minute**. Diese Grenzen sind im Code festgelegt. Fehlende Schlüssel liefern 401, inaktive/abgelaufene Schlüssel oder fehlende Rechte 403, unbekannte Module/Aktionen 404, falsche HTTP-Methoden 405 und eine Ratenbegrenzung 429.

## Schlüssel und Rechte

Admins erstellen Schlüssel unter **Public API** in der GBDB-Oberfläche. Schlüssel können aktiviert, deaktiviert und mit Ablaufzeit sowie Rechten versehen werden. Die tatsächliche Zuordnung des GBDB-Moduls lautet:

| Aktionen nach `gbdb.` | Erforderliches Recht |
| --- | --- |
| `info`, `list_instances`, `list_bases`, `list_tables`, `keys`, `next_id`, `get`, `exists`, `page`, `cursor`, `fulltext`, `stats` | `gbdb-read` |
| `create_base`, `create_table`, `add_column`, `insert`, `bulk_insert`, `update`, `upsert`, `delete` | `gbdb-write` |
| `create_instance`, `drop_instance`, `drop_base`, `drop_table` | `gbdb-admin` |
| `query`, `run_script` | `gbdb-gql` |
| `backend_status`, `backend_switch`, `backend_sync` | `gbdb-backend` |

`*` gewährt sämtliche Rechte. **`gbdb-structure` wird vom aktuellen GBDB-Modul nicht als separates Aktionsrecht verlangt.** `gbdb-write` erlaubt bereits einige Strukturänderungen. Rechte gelten nicht automatisch hierarchisch: Für Lesen und Schreiben beide Rechte vergeben.

Im Standardmodul gibt es keine allgemeine Instanz- oder Tabellenbindung pro Schlüssel. Ein Leseschlüssel ist daher kein auf eine einzelne Kundentabelle begrenzter Schlüssel. Interne Systeminstanzen bleiben durch die Sandbox gesperrt; die fachliche Mandantentrennung muss ein eigenes Modul durchsetzen.

Eine vollständige Aufstellung aller 29 Aktionen mit Pflichtfeldern, optionalen Werten und Rückgaben steht in der [HTTP-Aktionsreferenz](20-http-api-reference.md).

## Häufige Aktionen

| Aktion | Wesentliche Felder |
| --- | --- |
| `list_bases` | `instance`, optional |
| `list_tables` | `base`, optional `instance`, `desc` |
| `get` | `base`, `table`, optional `where`, `is`, `options` |
| `exists` | `base`, `table`, `where`, `is` |
| `page` | `base`, `table`, optional `page`, `per_page` |
| `cursor` | `base`, `table`, optional `limit`, zurückgelieferter `cursor` |
| `fulltext` | `base`, `table`, `query`, optional `columns`, `limit` |
| `insert` | `base`, `table`, `data` |
| `update` | `base`, `table`, `where`, `is`, `data` |
| `delete` | `base`, `table`, `where`, `is` |
| `query` | Skripttext in `script`, optional `ctx`, `params` |
| `run_script` | Dateiname in `script` oder `script_name`, optional `params`, `ctx` |
| `backend_switch` | `backend` mit `gbdb` oder `sql` |

`database` wird als Alternative zu `base` akzeptiert. Bei fachlichen Datenzugriffen `instance` ausdrücklich übergeben. Die genaue Behandlung weiterer Felder steht in `PHP/gbdb_framework/public/includes/public_api/gbdb.php`.

Schreiben, mit Schlüssel im JSON:

```json
{
  "key": "API_SCHLUESSEL",
  "do": "gbdb.insert",
  "instance": "beispiel",
  "base": "shop",
  "table": "produkte",
  "data": {"sku":"P-004","name":"Glas","preis":"4.90","aktiv":"1"}
}
```

Skripttext und Skriptdatei sind getrennte Aktionen:

```json
{
  "key": "API_SCHLUESSEL",
  "do": "gbdb.query",
  "instance": "beispiel",
  "script": "PICK * FROM produkte IN shop LIMIT 10;"
}
```

```json
{
  "key": "API_SCHLUESSEL",
  "do": "gbdb.run_script",
  "instance": "beispiel",
  "script": "mein_skript.gql",
  "params": {"sprache":"de"}
}
```

Nur voll vertrauenswürdigen Integrationen `gbdb-gql` geben: Darüber sind auch weitreichende Skriptoperationen möglich.

## Ein eigenes Modul

PHP-Dateien unter `gbdb_framework/public/includes/public_api/public_api_modules/` werden geladen. Die Klasse muss mit `PublicAPI_` beginnen und **alle drei** Methoden `name()`, `requiresRights()` und `handle()` besitzen. Ein Recht ist im Modul zu definieren und beim Schlüssel zu vergeben.

```php
<?php

declare(strict_types=1);

class PublicAPI_Demo {
    private const ACTIONS = [
        'ping' => ['method' => 'ping', 'rights' => ['demo-read']],
        'spiegeln' => ['method' => 'spiegeln', 'rights' => ['demo-write']]
    ];

    public static function name() :string {
        return 'demo';
    }

    public static function requiresRights(string $action = '') :array {
        return PublicAPI_ModuleHelper::rights(self::ACTIONS, $action);
    }

    public static function handle(string $action, array $body, array $keyData) :mixed {
        return PublicAPI_ModuleHelper::handle(__CLASS__, self::ACTIONS, $action, $body, $keyData);
    }

    public static function ping(array $body, array $keyData) :array {
        return ['nachricht' => 'Verbindung steht'];
    }

    public static function spiegeln(array $body, array $keyData) :array {
        $pruefung = PublicAPI_ModuleHelper::requireParams($body, ['nachricht']);

        if ($pruefung !== true) {
            return $pruefung;
        }

        if (!is_string($body['nachricht']) || strlen($body['nachricht']) > 1000) {
            return ['status' => 400, 'data' => 'nachricht muss eine Zeichenkette bis 1000 Byte sein'];
        }

        return ['nachricht' => $body['nachricht']];
    }
}
```

Das Aktionsarray enthält jeweils `method` und `rights`; eine Zuordnung nur auf einen Methodennamen reicht für den Helfer nicht aus. `requireParams()` wirft keine Exception, sondern liefert `true` oder ein Fehlerarray. Das Fehlerarray muss zurückgegeben werden, bevor die Aktion weiterläuft.

Das Modul ist anschließend über `demo.ping` beziehungsweise `demo.spiegeln` erreichbar. Für eigene Schreibaktionen zusätzlich Werte, Eigentümer und fachliche Rechte prüfen. `requiresRights()` authentifiziert das externe System, nicht automatisch einen einzelnen Anwendungsbenutzer.

## Protokolle und Fehler

Aufrufe und Schlüsselereignisse werden im internen Systembestand protokolliert. Logs können Schlüsselwerte enthalten und müssen wie Zugangsdaten geschützt werden. API-Antworten nicht blind in öffentliche Fehlerseiten kopieren.

Bei 403 zuerst Schlüsselstatus, Ablaufzeit und konkretes Aktionsrecht prüfen. Bei Skriptfehlern äußeren HTTP-Status und inneres Skriptergebnis getrennt auswerten. [Fehlerbehebung](14-troubleshooting.md) enthält weitere Prüfschritte.
