# 05 – GreenQL

GreenQL ist die Abfrage- und Skriptsprache von GBDB. Sie enthält Befehle für Schema, Daten, Verwaltungsfunktionen und prozedurale Abläufe. Befehle und eingebaute Funktionsnamen bleiben in der dokumentierten Schreibweise erhalten.

## Ausführung und Ergebnis

Für Anwendungscode die GBDB-Fassade verwenden:

```php
$ergebnis = GBDB::query('SHOW INSTANCES;');
$dateiErgebnis = GBDB::runScript('init_auth.gql', ['instance' => 'auth']);
```

`query($script, $ctx = [], $params = [])` erhält Skripttext. `runScript($scriptName, $params = [], $ctx = [])` lädt eine Datei aus den erlaubten Skriptwurzeln. Die Reihenfolge von Kontext und Parametern unterscheidet sich. `GreenQL::run()` führt ebenfalls Text aus; eine Methode `GreenQL::runScript()` existiert nicht.

Das Ergebnis enthält unter anderem `ok`, Meldungen, Einzelergebnisse sowie letzte Tabellenzeilen und Ausgaben. `ok` und die Ergebnisse der einzelnen Befehle auswerten. Ein Skript ist nicht allein dadurch atomar, dass es in einem einzigen String übergeben wird.

## Vollständiges Einstiegsbeispiel

```gql
GROW INSTANCE beispiel;
USE INSTANCE beispiel;
GROW BASE app;
GROW TABLE benutzer WITH uid, name, email, aktiv IN app;
SEED benutzer WITH uid="u1", name="Max", email="max@example.com", aktiv="1" IN app;
PICK * FROM benutzer IN app WHERE aktiv = "1" SORT name ASC LIMIT 50;
```

Befehle mit Semikolon abschließen. `SEED` und `RESHAPE` verwenden nach `WITH` Zuweisungen wie `name="Max", aktiv="1"`; ein vollständiges JSON-Objekt an dieser Stelle wird nicht als Datensatz übernommen. In Beispielen ist die Datenbank über `IN app` ausdrücklich angegeben; das vermeidet Abhängigkeiten vom zuvor gesetzten Kontext. Für weitere Beispiele werden diese Instanz, Datenbank und Tabelle vorausgesetzt.

## Variablen, Konstanten und Typen

```gql
DECLARE _name = "Max";
DECLARE :int _alter = 30;
DECLARE :bool _aktiv = true;
DECLARE $APP = "beispiel";
OUTPUT fusion("Hallo ", _name, " / aktiv=", _aktiv);
```

Variablen beginnen mit `_`, Konstanten mit `$`. Eine bereits gesetzte Konstante darf nicht überschrieben werden. Neben `DECLARE` akzeptiert der Parser die historischen Schreibweisen `DECALRE` und `DELACE`; neue Skripte sollten `DECLARE` nutzen.

Typen umfassen `mixed`, `string`, `int`, `float`, `bool`, `array` und `json` sowie Aliasse wie `integer`, `boolean`, `arr`, `object`, `text`, `email`, `url`, `date`, `datetime`, `uuid` und `blob_reference`. Ein Typname ist nicht automatisch eine umfassende fachliche Validierung; etwa Datumsformate zusätzlich passend zur Anwendung prüfen.

Häufige Ausdrücke sind `now()`, `param("name")`, `ENV("name")`, `len()`, `fusion()`, `uuid()`, `hash_sha256()` und `hash_pass()`. Werte für `ENV()` stehen im Konfigurationsbereich `GREENQL`. `param()` liest die beim Skriptaufruf übergebenen Parameter.

## Instanzen und Schema

```gql
SHOW INSTANCES;
SHOW BASES;
SHOW TABLES IN app;
DESCRIBE benutzer IN app;
ALTER TABLE benutzer ADD COLUMN letzter_login DEFAULT "" IN app;
```

`GROW INSTANCE`, `GROW BASE` und `GROW TABLE` legen Strukturen an; `USE INSTANCE` wechselt die Instanz. `ROOT` und `BRANCH` setzen Skriptkontext. `DROP INSTANCE … FORCE`, `DROP BASE` und `DROP TABLE` sind löschende Verwaltungsbefehle und gehören nicht in einen ungeschützten Benutzerablauf.

Umbenennen ist mit `RENAME INSTANCE … INTO …`, `RENAME BASE … INTO …` und `RENAME TABLE … INTO … IN …` möglich. Vorher sichern und Verweise in der Anwendung anpassen.

## Daten ändern und abfragen

```gql
PICK uid, name FROM benutzer IN app WHERE name ~= "Ma" SORT name ASC LIMIT 20 OFFSET 0;
RESHAPE benutzer WITH email="neu@example.com" WHERE uid = "u1" IN app;
```

Vergleiche umfassen `=`, `==`, `!=`, `>`, `<`, `>=`, `<=` und `~=` für Teilstrings. Der Parser enthält außerdem komplexere WHERE-Ausdrücke; diese sind in der internen Statusmatrix als Beta markiert. GreenQL ist kein vollständig SQL-kompatibler Parser.

Aggregationen und eindeutige Werte:

```gql
COUNT(*) FROM benutzer IN app;
COUNT(uid) FROM benutzer IN app GROUP BY aktiv;
DISTINCT aktiv FROM benutzer IN app;
```

Außerdem gibt es `SUM`, `AVG`, `MIN` und `MAX`.

## Seiten, Cursor und Textsuche

```gql
PAGE benutzer IN app PAGE 1 LIMIT 25;
CURSOR benutzer IN app LIMIT 100;
FULLTEXT "Max" FROM benutzer IN app COLUMNS name,email LIMIT 20;
```

Für eine weitere Cursor-Seite den erhaltenen Cursor mit `AFTER` übergeben. Ein beliebiger numerischer Wert ist kein gültiger Ersatz für den erzeugten Cursor. Diese Befehle verwenden die Engine-Helfer und deren [Speichergrenzen](04-gbdb-database-engine.md).

## Bedingungen, Schleifen und Funktionen

```gql
DECLARE :bool _aktiv = true;
IF (_aktiv == true) {
    OUTPUT "aktiv";
} ELSE {
    OUTPUT "inaktiv";
}

FOR (_i = 0; _i < 3; _i++) {
    OUTPUT _i;
}

F begruessen(?_name:string = "Welt") {
    BACK fusion("Hallo ", _name);
}
DECLARE _text = CALL begruessen("Max");
OUTPUT _text;
```

Tabellen lassen sich mit `FOR (_zeile; benutzer FROM app) { … }` durchlaufen. Klassen werden mit `C` oder `CLASS` deklariert; Methoden über `CALL Klassenname/methode(…)` aufgerufen. `PUB` und `PRIV` steuern Sichtbarkeit innerhalb dieser Skriptklassen.

## Dateien, Patterns und Protokollierung

```gql
FILE.INCLUDE weiteres_skript.gql;
FILE.RUN setup.gql {"instance":"beispiel"};
EXECUTE PATTERN used_by_auth_class;
SET_LOGFILE("installation.log");
LOG("Einrichtung gestartet");
```

Diese Befehle setzen vorhandene, erlaubte Dateien beziehungsweise Patterns voraus. Die mitgelieferten Patterns liegen unter `gbdb_framework/json/patterns/`; Skripte üblicherweise unter dem Datenordner in `.scripts/`. Dateioperationen bleiben auf die erlaubten Wurzeln begrenzt. Dateinamen und Parameter aus externen Anfragen nicht ungeprüft übernehmen.

## Indizes, Beziehungen und Transaktionen

```gql
INDEX benutzer email IN app;
UNIQUE INDEX benutzer uid IN app;
SHOW INDEXES FROM benutzer IN app;
REINDEX benutzer IN app;
ALTER TABLE benutzer ADD CONSTRAINT REQUIRED name IN app;
SHOW CONSTRAINTS FROM benutzer IN app;
```

Beziehungen verwenden etwa `ALTER TABLE … ADD FOREIGN KEY … REFERENCES … IN … ON DELETE …`. `CHECK RELATIONS` und `REPAIR RELATIONS … MODE REPORT` unterstützen die Prüfung. Reifegrad und Einschränkungen sind in der [Statusübersicht](18-feature-status.md) beschrieben.

```gql
BEGIN TRANSACTION;
SAVEPOINT vor_aenderung;
RESHAPE benutzer WITH aktiv="0" WHERE uid = "u1" IN app;
COMMIT TRANSACTION;
```

Bei einem fehlerhaften Zwischenschritt `ROLLBACK` oder `ROLLBACK TO vor_aenderung` verwenden; Ergebnisse prüfen. Die Engine-Transaktionen sind kein verteilter Commit über GDP-Knoten.

Abfragen können mit `PREPARE name AS …` registriert und mit `EXECUTE name` ausgeführt werden. Das ist die GreenQL-Vorbereitung innerhalb der Laufzeit und kein PDO-Prepared-Statement für eine SQL-Datenbank.

## Diagnose und Betrieb

```gql
STATS benutzer IN app;
HEALTH benutzer IN app;
CHECK benutzer IN app;
SHOW META FROM benutzer IN app;
SUGGEST INDEXES benutzer IN app;
```

Daneben enthält der Parser Befehle für Sicherungen, Partitionen, Shards, Wartung und Wiederherstellung. Solche Befehle können Daten verändern und müssen für den eigenen Bestand geprüft werden. Eine vollständige Clustersteuerung oder SQL-Engine ist dadurch nicht zugesichert.

SecondServer-Aufrufe sind beispielsweise `Srv/ping();` oder `SRV/startJob("Test", {"name":"Max"});`. Voraussetzung ist ein eingerichtetes [SecondServer-Backend](08-secondserver-srvp.md).

## Einen Datensatz löschen

Nach den Lese- und Änderungsbeispielen lässt sich der Testdatensatz gezielt entfernen:

```gql
USE INSTANCE beispiel;
DELETE FROM benutzer WHERE uid = "u1" IN app;
```

Der Löschbefehl ist dauerhaft. Für weitere Versuche den Testdatensatz erneut anlegen.
