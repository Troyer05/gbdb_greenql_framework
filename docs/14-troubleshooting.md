# 14 – Fehlerbehebung

## Ersteinrichtung meldet einen ungültigen Token

`UI.setup_token` in `.config/.framework.env.php` ist im Quellpaket leer. Eigenen zufälligen Wert mit mindestens 24 Zeichen eintragen und denselben Wert im Formular verwenden. Der erste Admin braucht mindestens 12 Zeichen Passwort; beide Passwortfelder müssen übereinstimmen. Bei bereits vorhandenen UI-Konten ist keine erneute erste Admin-Anlage möglich.

## Eine dokumentierte Methode fehlt

Den aktuellen [Methodenindex](99-api-reference/all-public-methods.md) verwenden. Lokale `Auth`-Methoden heißen beispielsweise `user_registration()` und `get_user()`. `GreenQL::runScript()` gibt es nicht; Skriptdateien über `GBDB::runScript()` aufrufen. `mRoot`, `ShareSuiteAPI` und `EqrAPI` haben hier noch keine Methoden.

Eine Trait-Deklaration ist kein eigenständiger statisch aufzurufender Dienst. Für Datenzugriff die öffentliche `GBDB`-Klasse verwenden.

## PHP kann nicht in den Datenordner schreiben

`GBDBConfig::dataPath()` und `GBDBConfig::runtimePath()` prüfen. Der PHP-Prozess muss Elternordner durchlaufen sowie benötigte Dateien und Sperren anlegen können. Bei Containern den tatsächlich eingebundenen Mount und dessen Rechte prüfen. Ein Dateipfad außerhalb des Webroots wird durch seine Lage nicht automatisch beschreibbar.

Auch das PHP-Temp-Verzeichnis wird für installationsbezogene Sperren gebraucht. Bei aktivem SQL-Backend zusätzlich Erreichbarkeit und maßgeblichen Speicherstatus prüfen, bevor Dateien kopiert oder Rechte geändert werden.

## Vorhandene Daten sind nach dem Upload unlesbar

Datenpfad und zugehörigen Verschlüsselungsschlüssel vergleichen. Ein neu erzeugtes `.config/.runtime/crypt_key.php` kann den bisherigen Schlüssel nicht ersetzen. Keinen neuen Schlüssel über den alten Bestand schreiben. Daten und ursprünglichen Laufzeitordner aus einer zusammengehörigen Sicherung wiederherstellen.

## Daten fehlen nach dem Einfügen

Aktive Instanz, Datenbank, Tabelle, Schema und Filter prüfen. `insertData()` kann bei Fehlern **`-1`** liefern; eine positive ID erwarten. `getData()` mit Filter kann einen einzelnen Datensatz statt einer Zeilenliste liefern. Bei GDP außerdem Schreibpolicy und blockierten Synchronisationszustand prüfen.

## Public API liefert 401, 403 oder 404

| Status | Prüfen |
| --- | --- |
| 401 | Schlüssel im Body oder Bearer-Header vorhanden und korrekt? |
| 403 | Schlüssel aktiv, nicht abgelaufen und mit dem konkreten Aktionsrecht versehen? Interne Instanz angefordert? |
| 404 | `do` korrekt als `modul.aktion`? Modulklasse geladen? Aktion im Aktionsarray vorhanden? |
| 405 | Tatsächlich HTTP POST verwendet? |
| 413 / 429 | Anfragegröße beziehungsweise Ratenbegrenzung überschritten? |

Für eigene Module müssen `name()`, `requiresRights()` und `handle()` vorhanden sein. `PublicAPI_ModuleHelper::requireParams()` liefert ein Fehlerarray zurück, das die Aktion selbst weitergeben muss.

## Skripttext wird als fehlende Datei behandelt

`GBDB::query()` und `gbdb.query` erhalten Text. `GBDB::runScript()` und `gbdb.run_script` erhalten einen Dateinamen innerhalb der erlaubten Skriptwurzeln. Bei GreenQL-Befehlen Semikolon, Instanz, `IN base`, `WITH feld=wert`-Zuweisungen und Variablennamen prüfen.

## SQL-Fallback lässt sich nicht abschalten

Wenn `mode = sql` ist, den Fallback wieder verfügbar machen und kontrolliert mit `GBDB::switchBackend('gbdb')` zurückschalten. Nicht nur `FAILOVER.enabled = false` setzen: Der Code blockiert das, solange SQL maßgeblich ist. DEV verwendet ohne eigene Überschreibung dieselben Fallback-Zugangsdaten wie PROD.

## SecondServer meldet Konfigurations- oder Anmeldefehler

`SRVP.ip` muss eine vollständige HTTP-/HTTPS-URL sein. Client und Backend brauchen denselben `static_key` mit mindestens 32 Zeichen. `ssl` ersetzt kein URL-Schema. Das Tokenverfahren und die eigentliche Anweisung müssen beide erfolgreich sein. PHP-Serverprotokoll und Antwortformat prüfen.

## Updater meldet `NO_TRUST_ANCHOR`

Im Repository ist kein `updater/trust_anchor.json` enthalten. Einen überprüften Anker des separat betriebenen Release-Managers oder ein offizielles Release verwenden. Keine unbekannten öffentlichen Schlüssel übernehmen, nur um den Fehler zu beseitigen.

Weitere Zustände wie `INVALID_CHECKSUM`, `REVOKED_KEY`, `EXPIRED_METADATA` oder `VERIFIED_API_UNAVAILABLE` haben unterschiedliche Ursachen. Die [Updater-Anleitung](../PHP/FRAMEWORK_UPDATER.md) erklärt, welche Fehler blockieren und wann eine manuelle Entscheidung zulässig ist.

## GDP blockiert Änderungen oder verlangt einen Snapshot

Policy, Eigentümer, Lease, Schema-Version und Journal prüfen. Eine Verbindungsunterbrechung erlaubt nicht automatisch zentrale Schreiboperationen. Konflikte werden nicht still zusammengeführt. Die vorgesehenen Recovery-Aktionen unter **Distributed (GDP)** verwenden; `.gdp` und Tresorschlüssel nicht löschen. Siehe [GDP](../PHP/GDP.md).

## Versionsnummern unterscheiden sich

Die Framework-Version kommt aus `gbdb_framework/version.json`; `APP.version` ist die lokale Anwendungsversion und bleibt bei Updates erhalten. Die VS-Code-Erweiterung hat eine eigene Version in `vsc/package.json`. Die Werte müssen daher nicht identisch sein.
