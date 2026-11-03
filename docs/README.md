# GBDB – Deutsches Handbuch

Diese Dokumentation beschreibt den mitgelieferten Quellstand vom **03.11.2026**. Maßgeblich für die Framework-Version ist `PHP/gbdb_framework/version.json` (**10.0.1**), für die VS-Code-Erweiterung `vsc/package.json` (**2.1.2**). `Vars::app_version()` beschreibt die Anwendung und kann davon abweichen.

## Empfohlene Reihenfolge

Zuerst [Überblick](01-overview.md), [Installation](02-installation-and-bootstrapping.md) und [Architektur](03-architecture.md) lesen. Danach folgen [Datenbank](04-gbdb-database-engine.md), [GreenQL](05-greenql-language.md) und die jeweils benötigten Schnittstellen. Praktische Arbeitsabläufe stehen in den [Rezepten](13-recipes.md).

## Kapitel

| Dokument | Inhalt |
| --- | --- |
| [01 – Überblick](01-overview.md) | Bausteine, Begriffe und Auswahl der passenden Schnittstelle |
| [02 – Installation](02-installation-and-bootstrapping.md) | Voraussetzungen, Hochladen, Admin-Einrichtung und Aktualisierung |
| [03 – Architektur](03-architecture.md) | Verzeichnisse, Ladefolge, Speicher und Anfrageablauf |
| [04 – Datenbank](04-gbdb-database-engine.md) | Instanzen, CRUD, Rückgaben, Transaktionen und Wartung |
| [05 – GreenQL](05-greenql-language.md) | Syntax, Ausführung, Skripte und Verwaltungsbefehle |
| [06 – Anmeldung](06-authentication.md) | Anwendungsanmeldung, Sitzungstoken, 2FA und UI-Konten |
| [07 – Public API](07-public-api.md) | HTTP-Vertrag, Rechte, Aktionen und eigene Module |
| [08 – SecondServer](08-secondserver-srvp.md) | Client, Backend, Authentifizierung und Aufträge |
| [09 – Oberfläche](09-ui-and-admin.md) | Seiten, Rollen, Rechte und Werkzeugfreigaben |
| [10 – Hilfsklassen](10-helper-classes.md) | HTTP, Dateien, Validierung, Routing und weitere Werkzeuge |
| [11 – Plugins](11-plugins-and-products.md) | MuseumQR-Anbindung und tatsächlicher Stand der Platzhalter |
| [12 – Betrieb](12-maintenance-security-performance.md) | Sicherung, Wiederherstellung, Schutz und Leistung |
| [13 – Rezepte](13-recipes.md) | Zusammenhängende Anwendungsbeispiele |
| [14 – Fehlerbehebung](14-troubleshooting.md) | Symptome, Ursachen und Prüfschritte |
| [15 – Konfiguration](15-configuration.md) | Zentrale Vars-Datei, Umgebungsvariablen und Konfigurationseditor |
| [16 – SQL-Fallback](16-sql-failover.md) | Spiegelung, Umschaltung und kontrollierte Rückkehr |
| [17 – Tests und Mitarbeit](17-tests-and-contributing.md) | Reproduzierbare Prüfungen und Dokumentationspflege |
| [18 – Funktionsstand](18-feature-status.md) | Implementierte, vorbereitete und experimentelle Bereiche |
| [GDP-Betriebsanleitung](../PHP/GDP.md) | Pairing, Policies, Journal, Offline-Betrieb und Wiederanlauf |
| [Framework-Updater](../PHP/FRAMEWORK_UPDATER.md) | Vertrauenskette, Releases, Installation und Rollback |
| [19 – Release-Manager](19-release-manager.md) | Separate Anwendung für Paketprüfung und Veröffentlichung |
| [20 – HTTP-Aktionsreferenz](20-http-api-reference.md) | Alle 29 GBDB-Aktionen mit Feldern, Rechten und Rückgaben |
| [Migrationsformat](../PHP/gbdb_framework/updater/migrations/README.md) | PHP-Migrationen für Framework-Releases |
| [Prüfbericht](../PHP/TESTBERICHT.md) | Aktuelle Dokumentationsprüfung und getrennte historische Ergebnisse |
| [VS-Code-Erweiterung](../vsc/README.md) | Installation und Funktionen der Erweiterung 2.1.2 |

## Methoden nach Bereich

| Referenz | Inhalt |
| --- | --- |
| [Gesamtindex](99-api-reference/all-public-methods.md) | Alle Bereiche, Klassen und öffentlichen Deklarationen |
| [GBDB](99-api-reference/gbdb-engine-methods.md) | Öffentliche Fassade, interne Engine und ihre Traits |
| [GreenQL](99-api-reference/greenql-methods.md) | Ausführung, Parser und Laufzeit-Traits |
| [Hilfsklassen und Konfiguration](99-api-reference/core-helper-methods.md) | `Vars`, `Auth`, Kernwerkzeuge und globale Funktionen |
| [Public API](99-api-reference/public-api-methods.md) | Dispatcher, Modulhelfer und GBDB-Modul |
| [SecondServer](99-api-reference/srv-methods.md) | Client, Server und Auftragsklassen |
| [Speicherlaufzeit](99-api-reference/runtime-methods.md) | Konfiguration, Dateizugriff, SQL-Speicher und Sperren |
| [GDP](99-api-reference/gdp-methods.md) | Öffentliche GDP-API, Protokollklassen und Zustands-Enums |
| [Updater](99-api-reference/updater-methods.md) | Prüfung, Installation, Version, Sicherungen und Rollback |
| [Oberfläche](99-api-reference/ui-methods.md) | UI-Helfer und globale UI-Funktionen |
| [Plugins](99-api-reference/plugin-methods.md) | Vorhandene Methoden und Klassen ohne Implementierung |

Die Referenz dokumentiert Deklarationen und genaue Signaturen. Beispiele und Rückgabeverträge erklärt das Handbuch. Eine öffentliche PHP-Sichtbarkeit macht einen internen Speicher- oder Protokollhelfer noch nicht zur empfohlenen Anwendungsschnittstelle.
