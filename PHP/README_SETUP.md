# GBDB – Setup und eine zentrale PHP-Konfiguration

Die einzige bearbeitbare Framework-Konfiguration ist jetzt:

`gbdb_framework/.config/.framework.env.php`

Sie enthält die gewohnte `class Vars` mit `private const`-Arrays und den bisherigen Methoden. Die bisherige INI-Konfiguration wurde mit der Vars-Konfiguration zusammengeführt. Auch die bisherigen GreenQL-ENV-Werte stehen darin.

## So startest du

1. Die Dateien aus `PHP_TEST/` auf deinen PHP-Webspace hochladen, einschließlich der Dateien und Ordner mit führendem Punkt.
2. Beim Aktualisieren die alten Dateien `gbdb_framework/.config/vars.php` und `gbdb_framework/.config/.greenql.env.php` entfernen, falls sie auf dem Webspace noch vorhanden sind. Sie werden nicht mehr geladen oder neu erzeugt.
3. In `.framework.env.php` die Konstante `UI` suchen und den bereits eingetragenen Wert von `setup_token` kopieren.
4. `gbdb_ui.php` im Browser öffnen. Den Token einfügen, einen Benutzernamen und ein eigenes Passwort mit mindestens **12 Zeichen** angeben.
5. „Admin anlegen“ anklicken. Danach bist du angemeldet; weitere Aufrufe zeigen das normale Login.

Der mitgelieferte Setup-Token wurde für dieses Paket zufällig erzeugt. Du kannst ihn durch einen eigenen zufälligen Wert mit mindestens **24 Zeichen** ersetzen. Er ist nur zur ersten Admin-Anlage erforderlich.

Es sind keine Shell-Skripte und keine Cronjobs für die Einrichtung nötig. PHP muss den Datenordner sowie `.config/.runtime` beschreiben können. Bei einem bestehenden Bestand gehören Daten und Verschlüsselungsschlüssel zusammen: `.config/.runtime` deshalb beim Aktualisieren behalten. Das Paket enthält deinen hochgeladenen Datenbestand und den zugehörigen Schlüssel unverändert, keine Testbenutzer.

## Warum das alte Setup scheiterte

Der eingetragene Setup-Token hatte nur **2 Zeichen**. `createInitialAdmin()` verlangt mindestens **24 Zeichen**, gab aber lediglich `false` zurück. Die Oberfläche konnte dadurch nicht zwischen einem zu kurzen Token, einem falschen Token und einem zu kurzen Passwort unterscheiden.

Jetzt steht bereits im Formular, wo der Token liegt und wie lang das Passwort sein muss. Bei einem ungültigen konfigurierten Token erscheint die tatsächlich konfigurierte Länge. Falsche Token, unterschiedliche Passwörter, zu kurze Passwörter und Speicherfehler erhalten eigene Meldungen. Die Mindestlänge und der CSRF-Schutz bleiben erhalten.

## Einstellungen

| Konstante in der Vars-Klasse | Inhalt |
| --- | --- |
| `APP` | Name, Version, `environment` mit `prod` oder `dev`, Datenbankarchitektur |
| `UI` | Setup-Token, UI-Schalter und Freigaben für Entwicklerfunktionen |
| `AUTH` | Auth-Einstellungen der Anwendung, getrennt vom UI-Admin |
| `STORAGE` | GBDB-Datenpfad, Sperrzeitlimit und Anfragecache |
| `SECURITY` | Verschlüsselung und HTTPS-Weiterleitung |
| `FAILOVER` | SQL-Fallback mit DSN, Benutzer, Passwort und Tabellenpräfix |
| `FAILOVER_DEV` | Optionale DEV-Überschreibungen; leer bedeutet gemeinsame Fallback-Verbindung |
| `SQL` | Direkte SQL-Verbindungen für `SQL::…`, jeweils `prod` und `dev` |
| `GREENQL` | Variablen für `ENV("name")` in GreenQL-Skripten |
| `GDP` | Verteilter Betrieb: Rolle, Hub-URL, Sync-Modus, Limits, Retention (siehe `GDP.md`) |
| `MAIL`, `SRVP` und weitere | Bisherige Framework- und Plugin-Einstellungen |

Die bisherigen `Vars::…`-Aufrufe bleiben nutzbar. `GBDBConfig` ist lediglich der interne Zugriff auf dieselben Konstanten, keine zweite Konfigurationsdatei. Bestehende Umgebungsvariablen wie `GBDB_ENV` und `GBDB_FAILOVER_DSN` funktionieren weiterhin.

Der SQL-Fallback ist wie im Upload zunächst deaktiviert, weil keine Zugangsdaten vorliegen. Für MySQL/MariaDB in `FAILOVER` die DSN und Zugangsdaten eintragen und `enabled` auf `true` setzen. Das Tabellenpräfix muss je unabhängiger Installation eindeutig sein. Änderungen werden während normaler PHP-Anfragen synchronisiert; ohne laufende PHP-Anfrage findet kein Hintergrundprozess statt.

## Konfiguration über die Oberfläche

Admins öffnen die Konfiguration in der Navigation unter **System → Framework-Konfiguration**. Für andere Rollen gibt `UI → allow_config_edit` sie zusätzlich frei; dann zählen das Recht `config` oder die Werkzeugfreigabe `env`. Dort werden die Konstanten als JSON angezeigt und bearbeitet. Beim Speichern erzeugt das Framework daraus wieder PHP-Arrays in derselben `.framework.env.php`. Es entsteht keine zusätzliche JSON-Datei.

Die Schalter `allow_php_exec`, `allow_plugin_upload`, `allow_reinstall` und `allow_config_edit` lassen sich nur direkt in der Datei ändern. So kann kein UI-Konto Code-Ausführung freischalten. PHP Exec, Plugins und Re-Install sind auch bei gesetztem Schalter nur für Admins nutzbar.

Methoden und Zugriffsschutz bleiben dabei erhalten. Datentypen werden geprüft, eingegebener PHP-Code wird nicht ausgeführt. Änderungen an Datenpfad oder Verschlüsselung werden im laufenden Bestand blockiert. Solange SQL der aktive Speicher ist, muss vor einem Wechsel des SQL-Ziels kontrolliert nach GBDB zurückgeschaltet werden.

Die direkte Bearbeitung der PHP-Datei mit deinem Editor funktioniert weiterhin im gewohnten Stil. „GQL-Skripte“ enthält keinen eigenen Konfigurationsbereich mehr; Werte für `ENV("name")` stehen in der Konstante `GREENQL`.

## Benutzer und Rechte

Jedes Konto hat eine Rolle und optional Zusatzrechte:

- **Rollen:** admin hat alles. maintainer hat read, write, structure, maintenance, jobs und backup. editor hat read und write. viewer hat read.
- **Zusatzrechte (`permissions`):** Ein leeres Feld gibt nur die Rechte der Rolle, `*` gibt alle, eine Liste wie `backup,jobs` gibt genau diese.
- **Werkzeuge (`tools`):** Ein leeres Feld gibt alle Werkzeuge frei, die Rolle und Rechte erlauben.
- **Admin-Konten:** Nur Admins verwalten Admin-Konten und vergeben die Rolle `admin`.

Beim ersten Aufruf nach dem Update erhalten bestehende Nicht-Admin-Konten mit leerem Rechtefeld einmalig ausdrücklich `*`, ihr Zugriff bleibt also gleich. Die Benutzerverwaltung listet diese Konten zur Prüfung.

## Oberfläche

Die GBDB-UI verwendet das Designsystem des Betreuerpool-Intranets: Sidebar mit Bereichen (Start, Daten, Betrieb, Zugriff, System), Suche mit Strg + K, Hell/Dunkel-Umschaltung oben rechts, Systemhinweise in der Glocke. Die Styles liegen in `gbdb_framework/public/css/intranet.css` (unverändert aus dem Intranet) und `gbdb_ui.css` (GBDB-Ergänzungen). Eigene Seiten nutzen `gbdbui_layout_start()` und `gbdbui_layout_end()` aus `public/includes/_shared.php`.

## Verteilter Betrieb (GDP)

GDP verbindet optional mehrere GBDB-Installationen (ein Hub, beliebig viele Replikate) mit Journal, Policies und Offline-Betrieb. Standardmäßig ist es aus; alles bleibt lokal. Einrichtung, Policies, Sicherheit und Grenzen stehen in `GDP.md`. Kurz:

1. Auf dem Hub in `.framework.env.php` unter `GDP` `enabled` auf `true` und `role` auf `hub` setzen, in der Oberfläche unter **Betrieb → Distributed (GDP)** eine Instanz freigeben und einen Pairing-Code erzeugen.
2. Auf dem Replikat `enabled` auf `true` und `role` auf `replica` setzen, unter **Knoten & Pairing** Hub-URL (`…/gdp.php`) und Code eintragen und synchronisieren.

`gdp.php` gehört wie `gbdb_ui.php` in den Projektstamm und muss für Replikate per HTTPS erreichbar sein. Ohne Cronjob synchronisiert der Embedded-Modus während normaler Anfragen; für feste Abstände `gbdb_framework/Distributed/bin/gdp_worker.php` dauerhaft laufen lassen.

## Sicherheitshinweise zum Upload

- `gbdb_framework/.htaccess` sperrt den direkten Webzugriff auf den Framework-Ordner (Apache). Unter nginx den Ordner entsprechend sperren; alle Einstiege (`gbdb_ui.php`, `public_api.php`, `backend.php`, `gdp.php`) liegen im Projektstamm.
- Beim Aktualisieren die neuen Platzhalter `gbdb_framework/public/backend.php`, `public/includes/table.php` und `public/includes/index.php` mit hochladen: Sie ersetzen frühere, unsichere Fassungen.
- Änderungen gegenüber dem vorherigen Stand: `CHANGELOG.md`.

## Prüfung

Siehe `TESTBERICHT.md`: Syntax- und Stilprüfung aller 126 PHP-Dateien, 80 bestandene GDP-Integrationsprüfungen mit zwei Knoten, Sicherheits-Gegentests, eine vollständige Prüfung der Oberfläche (Desktop und mobil, hell und dunkel) und Tests der Code-Editoren. Getestet mit PHP 8.4.26. Der SQL-Fallback wurde in diesem Stand nicht ausgeführt, weil auf dem Prüfsystem kein PDO-Treiber installiert ist; die vorherige Prüfung mit PDO/SQLite ist im Bericht dokumentiert.

GDP-Tests lokal ausführen:

```
php tests/gdp_test.php
```
