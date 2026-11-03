# GBDB – Einrichtung und Aktualisierung

Die vollständige Anleitung steht im [Installationskapitel](../docs/02-installation-and-bootstrapping.md). Dieses Dokument fasst die Schritte für den Upload zusammen.

## Neue Installation

1. Den Inhalt von **`PHP/`** einschließlich versteckter Dateien hochladen. `gbdb_ui.php`, `public_api.php`, `backend.php` und `gdp.php` liegen neben dem Ordner `gbdb_framework`.
2. `gbdb_framework/.config/.framework.env.php` öffnen. Es gibt keine zusätzliche zentrale INI- oder GreenQL-Konfigurationsdatei.
3. Unter `UI.setup_token` einen eigenen zufälligen Token mit mindestens **24 Zeichen** eintragen. Im Quellpaket ist er leer.
4. Datenpfad und Schreibrechte prüfen. PHP benötigt Zugriff auf den Datenordner, `.config/.runtime` und sein temporäres Verzeichnis.
5. `gbdb_ui.php` öffnen, Deutsch als Sprache wählen und den ersten Admin mit Token und Passwort ab **12 Zeichen** anlegen.

Die Einrichtung benötigt weder SSH noch Cronjobs. Existieren bereits UI-Konten, erscheint die Anmeldung; ein vorhandener Datenbestand wird nicht gelöscht.

## Konfiguration

| Bereich | Inhalt |
| --- | --- |
| `APP`, `AUTH`, `UI` | Umgebung, Anwendung, Anwendungsanmeldung und getrennte UI-Einrichtung |
| `STORAGE`, `SECURITY` | Datenpfad, Sperren, Cache und Verschlüsselung |
| `FAILOVER`, `FAILOVER_DEV` | Optionaler SQL-Spiegel und Umschaltverhalten |
| `SQL` | Direkte SQL-Verbindungen; getrennt vom Fallback |
| `GREENQL` | Werte für `ENV("name")` in Skripten |
| `GDP` | Optionale Hub-/Replikat-Konfiguration |
| `FRAMEWORK_UPDATER` | Suche nach signierten Framework-Releases und Installation |
| `MAIL`, `SRVP`, Produktbereiche | Mail-, SecondServer- und Produktanbindungen |

Alle Bereiche und Umgebungsüberschreibungen: [Konfiguration](../docs/15-configuration.md).

## Bestehenden Bestand erhalten

Datenordner, `.framework.env.php`, **`.config/.runtime`**, eigene Erweiterungen und **`.gbdb_updater`** sichern und erhalten. Daten und zugehörige Schlüssel gehören zusammen. Der Datenpfad kann außerhalb des Frameworks liegen, etwa in einem persistenten Container-Mount.

Alte Werte aus `vars.php` oder `.greenql.env.php` vor deren Entfernung in die zentrale Vars-Datei übertragen. Diese Dateien werden nicht mehr als zentrale Konfiguration geladen.

## Weiterführende Anleitungen

- [Rollen, Rechte und Werkzeuge](../docs/09-ui-and-admin.md)
- [SQL-Fallback und kontrollierte Rückschaltung](../docs/16-sql-failover.md)
- [GDP-Pairing, Policies und Offline-Betrieb](GDP.md)
- [Framework-Updates und Vertrauensanker](FRAMEWORK_UPDATER.md)
- [Änderungen der Dokumentation](../CHANGELOG.md)
- [Prüfbericht und historische Ergebnisse](TESTBERICHT.md)

Ein Repository-Stand enthält keinen eingebauten Vertrauensanker für den Updater. Für die erste Nutzung ein offizielles Release oder einen überprüften Anker aus dem separat betriebenen Release-Manager verwenden. Die in der Konfiguration genannten Serveradressen sind Vorgaben des Codes, keine hier bestätigte Aussage über deren aktuelle Erreichbarkeit.
