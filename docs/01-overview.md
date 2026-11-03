# 01 – Überblick und Begriffe

GBDB verbindet lokale Datenhaltung und PHP-Anwendungswerkzeuge. Eine Installation funktioniert ohne externen Datenbankserver. SQL-Fallback, GDP und entfernte Schnittstellen werden gezielt hinzugeschaltet.

## Die Bausteine

| Baustein | Aufgabe | Einstieg |
| --- | --- | --- |
| `GBDB` | Öffentliche Datenbankschnittstelle mit einheitlichem Speicherzugriff | `runtime/facade.php` |
| `GBDBEngine` | Interne dateibasierte Engine aus mehreren Traits | `GBDB_GQL/db_engine/gbdb.php` |
| `GBDBRuntime` | Sperren, aktives Speicherbackend, Spiegelung und Anfragecache | `runtime/runtime.php` |
| `GreenQL` | Abfragen und mehrteilige Skripte | `GBDB_GQL/greenql/greenql.php` |
| `Auth` | Benutzer und Anmeldung der eigenen Anwendung | `runtime/auth_facade.php` |
| GBDB Control Center | Verwaltung mit eigenen Konten und Rechten | `PHP/gbdb_ui.php` |
| Public API | Externe JSON-Aufrufe mit API-Schlüsseln | `PHP/public_api.php` |
| SecondServer | Aufträge und Anmeldeaufrufe an ein anderes Backend | `PHP/backend.php` |
| GDP/1 | Replikation zwischen einem Hub und mehreren Replikaten | `PHP/gdp.php` |
| Framework-Updater | Signierte Framework-Releases prüfen und installieren | `updater/` |

Pfade ohne `PHP/` beziehen sich in dieser Tabelle auf `PHP/gbdb_framework/`.

## Datenmodell

| Begriff | Bedeutung |
| --- | --- |
| Instanz | Getrennter logischer Datenbereich, etwa für Mandanten oder Anwendungen |
| Base / Datenbank | Sammlung von Tabellen innerhalb einer Instanz |
| Tabelle | Schema und Datensätze innerhalb einer Datenbank |
| Datensatz | Assoziatives Array mit Feldern; die Engine verwaltet eine numerische `id` |
| Physischer Speicher | Dateien unter `STORAGE.path`, standardmäßig `GBDB_GQL/.DB` relativ zum Framework |

Eine Instanz ist eine logische Trennung. Die Anwendung muss weiterhin prüfen, welcher angemeldete Benutzer auf welche Instanz zugreifen darf. Die Admin-UI und die Public API haben zusätzliche eigene Zugriffskontrollen.

## Welche Schnittstelle passt?

| Aufgabe | Schnittstelle |
| --- | --- |
| Daten aus eigenem PHP-Code lesen oder ändern | `GBDB::…` |
| Schema, Migrationen oder Verwaltungsabläufe skripten | `GBDB::query()` und `GBDB::runScript()` |
| Browser, Geräte oder externe Systeme anbinden | Public API, vorzugsweise fachlich begrenzte eigene Module |
| Remote-Anmeldung oder einen Auftrag auf einem anderen Server auslösen | `SecondServer::…` |
| Daten zwischen unabhängigen GBDB-Installationen replizieren | GDP |
| Lokalen Speicherausfall innerhalb einer Installation abfangen | SQL-Fallback |

SQL-Fallback und GDP erfüllen unterschiedliche Aufgaben. Der Fallback hält das GBDB-Dateiformat in SQL vor. GDP überträgt fachliche Änderungen zwischen Knoten und erzwingt dabei Schreibregeln.

## Rahmen des Projekts

Die Engine enthält zahlreiche Verwaltungsfunktionen, darunter Indizes, Schemata, Transaktionen, Sicherungen und vorbereitete Enterprise-Adapter. Ihr Reifegrad ist unterschiedlich. Die [Statusübersicht](18-feature-status.md) übernimmt die Einschränkungen aus dem Code und trennt vorhandene Funktionen von bloßen Platzhaltern.

Eine bestimmte maximale Datenmenge, feste Antwortzeit oder ausfallsichere Cluster-Skalierung ist damit nicht zugesichert. Besonders `page()`, `cursor()` und `streamRows()` laden derzeit intern die Tabelle; ihre Namen garantieren keinen begrenzten Speicherbedarf.
