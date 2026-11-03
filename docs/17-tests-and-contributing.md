# 17 – Tests und Mitarbeit

## Voraussetzungen der Entwicklung

Die Anwendung selbst braucht keine CLI-Einrichtung. Die folgenden Befehle sind für Entwicklung oder eine lokale Prüfkopie gedacht. Sie werden, sofern nicht anders angegeben, **im Repository-Stamm** ausgeführt.

Für die Tests PHP CLI mit OpenSSL, `sodium`, `zlib`, Tokenizer und den jeweils benötigten Standardfunktionen bereitstellen. GDP-Integrationstests starten lokale PHP-Webserver über `proc_open`, benötigen freie lokale Ports und einen beschreibbaren temporären Ordner. Ein SQL-Fallback-Test benötigt zusätzlich einen PDO-Treiber und ein getrenntes Datenziel. Für den GDP-Größenlimit-Test `post_max_size` des lokalen Testservers oberhalb von 9 MiB setzen, beispielsweise auf 32 MiB, damit nicht schon PHP die erzeugte Testanfrage abfängt.

## Mitgelieferte Tests

```bash
php PHP/tests/updater_test.php
php PHP/tests/gdp_test.php
```

| Test | Umfang |
| --- | --- |
| `updater_test.php` | Kryptografie, Vertrauenskette, Metadaten, ZIP-Prüfung, Konfigurationsabgleich und Update-Planung; ohne externen Server |
| `gdp_test.php` | Zwei getrennte temporäre Installationen, lokale HTTP-Anfragen, Pairing, Policies, Journal, Ausfälle und Wiederanlauf |
| `gdp_node.php` | Hilfseinstieg für den Integrationstest; kein zusätzlicher eigenständiger Abnahmetest |

Bei abweichendem PHP-Startprogramm unterstützt der GDP-Test `GBDB_TEST_PHP`. Die Testskripte arbeiten in temporären Kopien. Trotzdem keine produktiven Zugangsdaten oder gemeinsamen Fallback-Präfixe für eigene Erweiterungstests verwenden.

Ältere Berichte nennen zusätzliche Browser-, Rechte-, Stil- und End-to-End-Prüfungen. Diese Hilfsskripte sind teilweise nicht Bestandteil dieses Repositorys und lassen sich aus der ZIP allein nicht reproduzieren. Der [Prüfbericht](../PHP/TESTBERICHT.md) trennt neue Ergebnisse von historischen Angaben.

## Dokumentation prüfen und neu erzeugen

```bash
php docs/tools/generate-api-reference.php
php docs/tools/generate-api-reference.php --check
php docs/tools/check-documentation.php
```

Der Generator liest PHP-Dateien mit `PhpToken::tokenize()` ein. Er führt weder Framework noch Konfiguration aus. Er berücksichtigt Klassen, Traits, Interfaces, Enums und globale Funktionen, erfasst öffentliche Signaturen einschließlich Rückgabetyp und schreibt die Teilreferenzen sowie den Gesamtindex.

Der Prüflauf mit `--check` schreibt nichts und endet bei Abweichungen mit Fehlerstatus. Nach API-Änderungen erst generieren, dann prüfen. Die generierten Tabellen nicht von Hand bearbeiten.

`check-documentation.php` prüft die Markdown-Dateien innerhalb des Framework-Repositorys: geschlossene Codeblöcke, gültiges JSON, PHP-Syntax, vorhandene lokale Linkziele und Quellzeilen sowie abschließende Zeilenumbrüche und Leerraum am Zeilenende. Konfigurationsausschnitte werden nur für die Syntaxprüfung in einen Klassenkontext gesetzt. Beispiele werden dabei nicht ausgeführt und externe Adressen nicht abgefragt. Das Werkzeug setzt Tokenizer voraus und endet bei Befunden mit Fehlerstatus.

Ein Signaturindex ersetzt keine fachliche Dokumentation: Verhalten, Eingaben, Rückgaben, Seiteneffekte und Grenzen im passenden Handbuchkapitel ergänzen. In internen Traits deklarierte Methoden werden über ihre nutzende Klasse aufgerufen; die Referenz kennzeichnet diese Trennung.

## JavaScript

```bash
node --check vsc/src/extension.js
```

Die UI-Skripte bei eigenen Änderungen ebenfalls mit `node --check` prüfen. Ein neu gebautes VSIX-Paket ist ein eigener Build-Schritt. In diesem Dokumentationsstand wurde die eingebettete README ausdrücklich abgeglichen; eine spätere Änderung der Quelldatei aktualisiert das VSIX nicht automatisch.

## Beiträge

Beschreibende Texte auf Deutsch schreiben. API-Namen, Konfigurationsschlüssel, Befehle und tatsächliche Menübezeichnungen erhalten. Codebeispiele müssen zur vorhandenen Signatur passen und ihre benötigte Instanz, Tabelle oder Konfiguration nennen.

Öffnende geschweifte Klammern stehen in derselben Zeile wie die Deklaration oder Bedingung. Kurze Arrays, lesbare Einrückung und ein abschließender Zeilenumbruch sind der Stil der Beispiele. Geheimnisse, lokale Daten und Laufzeitzustände nicht in neue Beispiele kopieren.

Für Fehlerberichte reproduzierbare Schritte, tatsächliche Version, PHP-Version und eine bereinigte Fehlermeldung angeben. Testergebnisse mit Datum, Umgebung und Grenzen dokumentieren. Keine historischen Ergebnisse als frisch ausgeführt kennzeichnen.
