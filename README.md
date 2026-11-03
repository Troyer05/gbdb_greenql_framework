# GBDB Framework

**GBDB** steht für **GreenBucket Database**. Das PHP-Framework kombiniert eine dateibasierte Datenbank, die Abfrage- und Skriptsprache **GreenQL**, eine Verwaltungsoberfläche, Anwendungsanmeldung und erweiterbare HTTP-Schnittstellen. Optional kommen ein SQL-Fallback und die Replikation über **GDP/1** hinzu.

Dokumentationsstand: **03.11.2026** · Framework-Version im Quellpaket: **10.0.1** · GreenQL-Erweiterung für VS Code: **2.1.2**.

## Einstieg

- [Installation und Ersteinrichtung](docs/02-installation-and-bootstrapping.md)
- [Vollständiges deutsches Handbuch](docs/README.md)
- [PHP-Methodenreferenz](docs/99-api-reference/all-public-methods.md)
- [Konfiguration](docs/15-configuration.md) und [SQL-Fallback](docs/16-sql-failover.md)
- [GDP: verteilte Replikation](PHP/GDP.md)
- [Signierte Framework-Updates](PHP/FRAMEWORK_UPDATER.md) und [beigelegter Release-Manager](docs/19-release-manager.md)
- [Tests und Mitarbeit](docs/17-tests-and-contributing.md)
- [Funktionsstand und Grenzen](docs/18-feature-status.md)

## Was enthalten ist

| Bereich | Funktion |
| --- | --- |
| `GBDB` | Instanzen, Datenbanken, Tabellen, CRUD, Schemata sowie Verwaltungs- und Wartungsmethoden |
| GreenQL | Abfragen, Skripte, Variablen, Funktionen, Klassen und administrative Befehle |
| GBDB Control Center | Einrichtung, Datenverwaltung, Benutzer, Skripte, Speicher, GDP und Updates im Browser |
| `Auth` | Anwendungsbenutzer, Anmeldung, serverseitige Sitzungstoken, E-Mail-Bestätigung und 2FA |
| Public API | JSON über HTTP POST, API-Schlüssel, Aktionsrechte und eigene Module |
| SecondServer / SRV | Aufrufe eines entfernten Backends für Anmeldung, Daten und Aufträge |
| SQL-Fallback | Optionaler Spiegel des GBDB-Dateiformats in MySQL/MariaDB oder SQLite |
| GDP/1 | Optionaler Hub mit Replikaten, Journal, Schreibregeln und geregeltem Offline-Betrieb |
| Framework-Updater | Signierte Metadaten, Integritätsprüfung, Sicherung und Wiederherstellung |
| VS-Code-Erweiterung | Hervorhebung, Vorschläge, Diagnose und Formatierung für GreenQL |

Vorhandene Methoden sind keine pauschale Freigabe für jeden Produktionseinsatz. Die [Statusübersicht](docs/18-feature-status.md) nennt auch vorbereitete oder experimentelle Funktionen. `mRoot`, `ShareSuiteAPI` und `EqrAPI` sind in diesem Stand Platzhalter; der Framework-Updater arbeitet unabhängig davon.

## Voraussetzungen und Einrichtung

PHP **8.1 oder neuer**, OpenSSL und ein beschreibbarer Daten-, Laufzeit- und temporärer Ordner. Für Updates wird zusätzlich `sodium` benötigt; weitere Voraussetzungen stehen in der [Installationsanleitung](docs/02-installation-and-bootstrapping.md).

Die Dateien aus `PHP/` einschließlich versteckter Dateien hochladen. In `gbdb_framework/.config/.framework.env.php` unter `UI.setup_token` einen eigenen zufälligen Token mit mindestens **24 Zeichen** setzen. Anschließend `gbdb_ui.php` öffnen und den ersten Admin mit einem Passwort ab **12 Zeichen** anlegen. Die Einrichtung benötigt weder SSH noch Cronjobs.

Ein vorhandener Datenbestand wird dabei weiterverwendet. Dieses Quellpaket ist kein automatisch bereinigtes Installationsrelease. Hinweise zum Aktualisieren und zum Umgang mit lokalen Daten stehen in der Installationsanleitung.

## Verwendung aus PHP

Das Beispiel liegt neben dem Ordner `gbdb_framework`, etwa innerhalb von `PHP/`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/gbdb_framework/autoloader.php';

GBDB::createInstance('beispiel');

$produkte = GBDB::withInstance('beispiel', static function () :array {
    GBDB::createDatabase('shop');
    GBDB::createTable('shop', 'produkte', ['name', 'preis']);
    GBDB::insertData('shop', 'produkte', ['name' => 'Tasse', 'preis' => '9.90']);

    return GBDB::getData('shop', 'produkte');
});
```

Die Beispiele legen Daten an. In Anwendungen das Schema einmalig beim Einrichten oder bei Migrationen erzeugen.

## Repository

| Pfad | Inhalt |
| --- | --- |
| `PHP/` | Laufzeit, vier HTTP-Einstiege, Tests und ausführliche Betriebsanleitungen |
| `docs/` | Handbuch, Rezepte und aus dem Quellcode erzeugte Methodenreferenz |
| `vsc/` | Quellcode und VSIX-Paket der GreenQL-Erweiterung |
| `LICENSE.txt` | Maßgeblicher Lizenztext im unveränderten Original |
| `CHANGELOG.md` | Änderungen dieser Dokumentationsüberarbeitung |

Autor: Markus A. Müller. Der Lizenztext bleibt in seiner Originalsprache erhalten; Erläuterungen und Anleitungen sind deutsch. API-Namen, Befehle und tatsächliche Menübezeichnungen werden unverändert wiedergegeben.
