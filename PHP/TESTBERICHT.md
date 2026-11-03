# Prüfbericht

## 03.11.2026 – Dokumentation gegen den gelieferten Code geprüft

Gegenstand: beide Quellordner der hochgeladenen ZIP, Framework-Version `10.0.1` und separat beigelegter Release-Manager mit interner Versionszeichenfolge `1.0.0`. Die Aktualisierung verändert Dokumentation und ergänzt Werkzeuge zur Erzeugung der Methodenreferenz sowie zur Prüfung von Markdown, Beispielen und Links. Framework-/Release-Manager-Anwendungscode, lokale Konfiguration, Daten und Lizenztext bleiben unverändert.

Prüfumgebung: **PHP 8.3.6 CLI**, lokaler PHP-Testserver, OpenSSL, sodium, zlib, Tokenizer, mbstring, cURL und PDO/SQLite; **Node 24.22.1**. Ausführbare Beispiele liefen auf einer getrennten Kopie mit eigenen Datenpfaden. Es wurde keine echte Veröffentlichung ausgelöst und kein externer Produktdienst aufgerufen.

| Prüfung | Ergebnis |
| --- | --- |
| Syntax der 157 bisherigen PHP-Dateien und des neuen Dokumentationsprüfers, insgesamt 158 Dateien beider Quellordner; Daten-/Schlüsseldateien ausgenommen | Ohne Syntaxfehler |
| Alle 32 PHP-Codeblöcke der Dokumentation | Syntax gültig; zwei Konfigurationsausschnitte im passenden Klassenkontext geprüft |
| Alle 11 JSON-Codeblöcke | Gültiges JSON |
| Markdown-Codeblöcke, lokale Dateiverweise und Quellzeilenverweise | Geprüft, keine offenen Codeblöcke oder fehlenden Zielpfade |
| Referenzgenerator mit `--check` | 11 Referenzdateien aktuell, 2090 öffentliche Methoden/Funktionen in 152 Klassen/Gruppen |
| Unabhängiger Abgleich über PHP-Reflection | 1331 öffentliche Methoden der zentralen Klassen mit ihrer tatsächlichen Deklarationsstelle in der Referenz vorhanden |
| README-Beispiel und Wiederherstellung des Instanzkontexts | 2 Prüfungen bestanden |
| Datenbankbeispiele: CRUD, Rückgabeform, Sammelimport, Verarbeitung, Seite, Cursor und Suche | 8 Prüfungen bestanden |
| Ausführbare GreenQL-Blöcke des Sprachkapitels | 11 Blöcke erfolgreich; Zuweisungen bei `SEED`/`RESHAPE` auf tatsächlich unterstützte Syntax korrigiert |
| Eigenes Public-API-Modul | 8 Prüfungen bestanden: Rechte, Erfolg, Pflichtfeld, Typ, Länge und unbekannte Aktion |
| Auth-API ohne Mailversand | 3 Prüfungen bestanden: Registrierung, Benutzerlesen und Anmeldung |
| Dokumentierte manuelle Speicheraktionen mit PDO/SQLite | 6 Prüfungen bestanden: Spiegel bereit, Wechsel nach SQL, Änderung, Abgleich, kontrollierte Rückkehr und erhaltene Daten |
| `PHP/tests/updater_test.php` | **58 von 58 bestanden** |
| `PHP/tests/gdp_test.php` | **80 von 80 bestanden**, letzter vollständiger Lauf 15,3 Sekunden |
| Gemeinsame Prüfklassen von Framework und Release-Manager | Vier Dateien bytegleich; `tools/sync-shared.php --check` erfolgreich |
| `vsc/src/extension.js` mit `node --check` | Syntax gültig |
| Vergleich mit der Eingangs-ZIP | Anwendungscode, Konfiguration, Daten und Lizenz unverändert; in der enthaltenen Release-Manager-ZIP und im VSIX jeweils nur die README geändert |

Der GDP-Größenlimit-Test erzeugt eine 9-MiB-Anfrage. Ein erster Lauf mit dem niedrigeren PHP-Standardlimit brach an diesem Szenario ab. Der vollständige erfolgreiche Lauf verwendete im lokalen Testserver `post_max_size = 32M` und Fehlerausgabe auf stderr, sodass die Anfrage die GDP-eigene Größenprüfung erreichen konnte. Dafür wurde keine Projektdatei geändert.

### Fortsetzung am 03.11.2026 – HTTP-Vertrag und eingebettete Anleitung

Die zusätzliche [HTTP-Referenz](../docs/20-http-api-reference.md) nennt alle 29 im Code registrierten GBDB-Aktionen mit Pflichtfeldern, optionalen Werten, Rechten und Ergebnisformen. Der Abgleich der Aktionsliste verwendet die tatsächliche Registry des Moduls.

Auf einer neuen getrennten Kopie wurden **41 lokale HTTP-Anfragen mit 75 bestandenen Prüfungen** ausgeführt. Jede der 29 Aktionen war mindestens einmal enthalten. Geprüft wurden insbesondere die drei neuen JSON-Beispiele, Lese-/Schreib-/Strukturrechte, fehlende und leere Schlüssel, Pflichtfeld- und Datenfehler, interne Instanzsperre, unbekannte Aktionen, HTTP-Methoden und Speicherwechsel nach SQL sowie zurück nach GBDB mit PDO/SQLite. Das Serverprotokoll enthielt keine PHP-Warnungen oder Fatal Errors. Der Lauf veröffentlichte nichts auf einem externen Dienst.

Die README im mitgelieferten `greenql-language-2.1.2.vsix` nannte noch Version 2.0.0 und den falschen Installationsdateinamen. Sie ist jetzt mit der deutschen README des Quellordners identisch. Der Vergleich der entpackten Einträge bestätigt, dass ausschließlich `extension/README.md` verändert wurde; alle JavaScript-, Manifest-, Grammatik- und Theme-Dateien sind unverändert.

`php docs/tools/check-documentation.php` prüft reproduzierbar die **40 Markdown-Dateien des eigenständigen Framework-Repositorys**, 2384 lokale Verweise, 32 PHP- und 11 JSON-Beispiele. Der Lauf ist erfolgreich und führt die Beispiele nicht aus. PHP-Syntaxprüfung und der unveränderte Referenzgenerator sind ebenfalls erfolgreich. Die separat danebenliegende Release-Manager-Anleitung wird außerhalb dieses Repository-Prüfers behandelt.

### Was diese Prüfung nicht abdeckt

Kein neuer vollständiger Browser-/Layouttest, kein produktiver Apache-/Nginx-/FPM-Betrieb, keine reale HTTPS-Veröffentlichung und keine Prüfung der Erreichbarkeit der voreingestellten Server. Der SQLite-Lauf prüft die dokumentierten manuellen Speicheraktionen, nicht MySQL/MariaDB, alle automatischen Ausfallszenarien oder die Kombination aus SQL-Fallback, GDP und Update-Migration.

GreenQL-Datei-/Pattern-Beispiele mit ausdrücklich vorausgesetzten eigenen Dateien wurden nicht als vorhandene Installationsdateien ausgegeben oder ausgeführt. Die MuseumQR-Gegenstelle, E-Mail-Zustellung und das VSIX im tatsächlichen VS-Code-Client wurden nicht getestet. PHP-Sichtbarkeit und ein Eintrag in der Referenz sind keine allgemeine Funktions- oder Produktionsfreigabe.

Die separat beigelegte `gbdb_release_manager.zip` enthält bereits im Eingangspaket einen teilweise anderen Anwendungscode als der Quellordner. Dieser Code wurde erhalten und der Unterschied in beiden Anleitungen kenntlich gemacht. Eine neu geschriebene README macht dieses alte Verteilungspaket nicht zu einem neu gebauten Release.

## Historische Prüfberichte

Die folgenden Abschnitte stammen aus dem gelieferten Dokument. Ihre Testergebnisse und Umgebungen beziehen sich auf die jeweils genannten früheren Läufe; sie wurden durch die Dokumentationsüberarbeitung nicht pauschal neu bestätigt. Bezeichnungen wie `PHP_TEST`, externe Testpfade und frühere Dateizahlen sind historisch. Die genannten Browser-, Rechte-, Stil- und End-to-End-Hilfsskripte sind teilweise nicht im aktuellen Paket enthalten.

---

## Stand 02.11.2026 – Framework-Update-System

Laufzeit: PHP 8.4.27 (CLI und eingebauter Webserver). Release-Manager und Framework liefen als getrennte Server; das Update-Verzeichnis wurde wie Apache mit `mod_autoindex` ausgeliefert.

| Prüfung | Ergebnis |
| --- | --- |
| Unit-Tests `php tests/updater_test.php` (Signaturen, Kette, Rotation, Recovery, Rollback-Schutz, Ablauf, Widerruf, zurückgezogene Releases, Schlüsselbeginn, Einzel-Hashes, ZIP-Schutz inkl. Datei/Ordner-Konflikt, Listing, Konfigurationsabgleich inkl. FrameworkUpdater, Patch-Day) | 58 von 58 |
| End-to-End (`testumgebung/gbdb-updater/e2e.php`, echte HTTP-Anfragen) | 124 von 124 |
| GDP-Integrationstests (Regression wegen geändertem Autoloader) | 80 von 80 |
| PHP-Syntax aller neuen und geänderten Dateien | ohne Fehler |
| Server-Logs nach dem E2E-Lauf | keine Warnungen; einziger Fatal Error ist der gewollte im Ladefehler-Szenario |

**End-to-End abgedeckt:** Einrichtung des Release-Managers mit Schlüsselerzeugung, Recovery-Schlüssel herunterladen und vom Server löschen, Upload einer Repository-ZIP mit Bereinigung (Schlüssel, Datenbestand, Fremddateien, Zugangsdaten), doppelter Inhalt abgewiesen, Erstinstallation aus dem Release, Update mit neuer, geänderter und entfernter Datei, Konfigurationsabgleich und Migration, Erhalt von Fremdcode und lokal geänderten Dateien, Konfliktrückfrage mit Abbruch und Bestätigung, manipulierte ZIP (`INVALID_CHECKSUM`), Verify-API nicht erreichbar (`VERIFIED_API_UNAVAILABLE` mit Bestätigung), zurückgespielter Katalog (`ROLLBACK_DETECTED`), nicht freigegebene ZIP im Verzeichnis, gescheiterte Migration mit Code- und Datenrollback, fataler Fehler im neuen Code mit Rollback, Widerruf des Release-Schlüssels, Root-Wechsel, Notfall-Wiederherstellung per Recovery-Schlüssel, zurückgezogenes Release, Überspringen, Wiederherstellungspunkt, automatisches Update zum Patch-Day mit Abschluss durch eine beliebige Folgeanfrage.

**Nach dem Review ergänzt:** Einrichtung nur mit Token, Geheimnis im Core blockiert, `FrameworkUpdater`-Schreibweise behält `BUILTIN`, lokale Daten bleiben draußen, Verweis auf ausgeschlossene Datei verlangt Bestätigung, vorhandene Versionsdatei blockiert, beschädigte Release-Datenbank stoppt alles, Journal räumt abgebrochene Veröffentlichung auf, unveröffentlichter Root-Wechsel wird verworfen, abgelaufener Katalog wird über das Lebenszeichen der Installation erneuert.

**Nach dem zweiten Review ergänzt:** harte Abbrüche (Prozessende ohne Aufräumen) nach Veröffentlichung der neuen `trust.json`, nach dem Katalog vor der Datenbank und mitten im Neusignieren – jeweils vom nächsten Aufruf korrekt abgeschlossen; Interface statt Gate-Klasse, fehlende statische Pflichtmethode, nicht mitgeliefertes und mitgeliefertes `vendor/`, kurzes Passwort in Variablenzuweisung.

**Nicht geprüft:** echtes Apache mit `.htaccess` (lokal `AllowOverride None`), HTTPS mit echtem Zertifikat, PHP-FPM-Hintergrundlauf (`fastcgi_finish_request`), Firefox/Safari. Die Oberfläche wurde über HTTP geprüft (Statuscodes, Inhalte, Formulare), nicht per Sichtprüfung im Browser.

---

## Stand 26.10.2026 (Nachtrag 2) – Rechte verschärft

| Prüfung | Ergebnis |
| --- | --- |
| Migrationstest (frische Kopie, Altkonten direkt in der Engine angelegt: leer, `-header-`, Liste, `*`, deaktiviert, Admin mit leerem Feld) | 37 von 37 bestanden |
| Rechte-Matrix (2 Schalter × 6 Kontotypen) | 24 von 24; der zuvor offene Fall `tools = monitoring` mit leeren Rechten ist jetzt gesperrt |
| PHP-Parser, Code-Stil | 126 Dateien OK |
| GDP-Integrationstests | 80 von 80 |

**Migrationstest im Einzelnen:**

- Leere, `-header-`- und deaktivierte Nicht-Admin-Konten erhalten `*`. Listen, `*` und Admins bleiben unverändert.
- Der Merker nennt genau diese Konten. Ein umgestelltes Konto behält seine Rechte.
- Neue Konten mit leerem Feld werden nicht mehr zu `*` und haben nur ihre Rollenrechte: kein `backup`, keine Benutzerverwaltung bei `tools = monitoring`.
- Ein zweiter Lauf ändert nichts. Ein abgebrochener Lauf wird für genau die gelisteten Konten fortgesetzt.
- Ein Nicht-Admin kann den Hinweis nicht ausblenden, ein Admin kann es.
- Ein Nicht-Admin kann kein Admin-Konto anlegen, sich nicht befördern, kein Admin-Passwort zurücksetzen und kein Admin-Konto löschen. Der root-Passworthash bleibt unverändert, Nicht-Admin-Konten darf er weiter verwalten.

**Im Browser (Testkopie):**

- Der Hinweis nennt die drei umgestellten Konten, und die Glocke meldet die Umstellung.
- Ein Konto mit leerem Rechtefeld wird als leer gespeichert und als „nur Rolle“ angezeigt. Nach dem Quittieren verschwinden Hinweis und Glockeneintrag.
- Als Nicht-Admin fehlt die Rolle `admin`, und Admin-Konten zeigen „nur Admins“.
- Selbst gebaute POST-Anfragen (eigene Beförderung, root-Passwort zurücksetzen) bleiben wirkungslos; der Hash ist mit einer unberührten Kopie verglichen.
- Layout-Check ohne Befund.

**Bestand in `PHP_TEST`** (auf einer Kopie ausgewertet): Es gibt nur `root` (Admin, `*`). Die Umstellung betrifft kein Konto und legt nur den Merker an.

---

## Stand 26.10.2026 (Nachtrag) – Oberfläche, Framework-Konfiguration, Code-Editoren

Laufzeit: PHP 8.4.26, Node 20 und das Chromium-Browserfenster der Entwicklungsumgebung. Alle Tests liefen auf einer Kopie. Bestandsdaten und Schlüssel in `PHP_TEST` wurden nicht verändert.

| Prüfung | Ergebnis |
| --- | --- |
| PHP-Parser, 126 Dateien | keine Syntaxfehler |
| Code-Stil (`tests/style.php`: K&R, `) :typ`, kurze Arrays, keine Tabs) | 126 Dateien OK; geänderte Dateien ohne Leerzeichen am Zeilenende, mit abschließendem Umbruch |
| JavaScript-Syntax (`node --check`) | OK |
| GDP-Integrationstests (`php tests/gdp_test.php`) | 80 von 80 bestanden |

### Code-Editoren

- **Text-Treue der Highlighter:** Jede Ausgabe wurde ohne Tags mit der Eingabe verglichen: 154 Projektdateien (`.gql`, `.php`, `.json`) und je Sprache 30 000 Zufallseingaben aus Sprachfragmenten. Neue Fassung: **0 Abweichungen**. Alte Fassung: 17 Abweichungen in GQL, darunter beide mitgelieferten Skripte.
- **Im Browser, alle fünf Editoren** (GQL-Skripte, Studio, PHP Exec, Konfiguration, Enterprise):
  - Eingabe und Anzeige enthalten identischen Text.
  - Die 18 Schrift- und Box-Eigenschaften (Schrift, Größe, Zeilenhöhe, Laufweite, Ligaturen, Abstände, Maße) stimmen überein.
  - Kein Token weicht in der Schriftstärke ab, und die Anzeige folgt dem Scrollen.
- **Tipptest:**
  - Enter am Ende der ersten Zeile, dann `if (x) {` und `}`, Enter zwischen den Klammern: drei korrekt eingerückte Zeilen.
  - Tab und zweimal Umschalt + Tab über eine Auswahl rücken mehrere Zeilen ein und aus.
  - Zweimal Strg + Z macht beide Schritte rückgängig. Die Anzeige bleibt dabei synchron.
- **Speichern:** Ein Skript mit zwei Leerzeilen am Anfang wurde gespeichert und neu geladen. Es ist unverändert, 21 Byte mit LF. Vorher schlug das Speichern still fehl.

### Rechte und Konfiguration

- **Rechte-Matrix** (Schalter `allow_config_edit` und `allow_php_exec` je an/aus × sechs Kontotypen):
  - Admins öffnen die Konfiguration in allen Kombinationen.
  - Nicht-Admins öffnen sie nur mit `allow_config_edit` und passender Freigabe.
  - PHP Exec ist nur für Admins mit Schalter erreichbar.
- **Konfigurationseditor:**
  - Eine Änderung von `UI.allow_php_exec` über die Oberfläche wird abgewiesen.
  - Eine harmlose Änderung wird gespeichert, die erzeugte Datei ist gültiges PHP. Die Änderung wurde danach zurückgesetzt.
  - Ungültiges JSON bleibt zum Korrigieren im Editor.
- Die Sperrseite eines per Schalter deaktivierten Werkzeugs nennt den Schalter.
- **SRV-Einmal-Token:** Nach `delete_token()` war der Token bisher weiter gültig, jetzt ist er ungültig. `atomic_write()` der Konverter schreibt nachweislich über `gbdbdata://`.

### Oberfläche

- **Automatischer Layout-Check** auf 21 Seitenzuständen bei 1440 px und 375 px: kein horizontales Scrollen, keine überlaufenden Elemente, keine versetzten oder leeren Rasterelemente. Übrig bleiben nur bewusste Ausrichtungen von 1–4 px bei Checkboxen und die Abstände der Intranet-Kennzahlkarten.
- **Sichtprüfung** hell und dunkel: Übersicht, Studio (Tabellen- und Abfrageansicht, leere Base und Tabelle), GQL-Skripte, Import/Export, Public API, Enterprise, Benutzer, Verschlüsselung, Konfiguration und PHP Exec.

### Nicht geprüft

- Firefox und Safari: Geprüft wurde nur Chromium. Die Editor-Engine fällt ohne `execCommand` auf `setRangeText` zurück, dann ohne Rückgängig-Verlauf.
- Apache-spezifisches Verhalten; der PHP-Testserver wertet `.htaccess` nicht aus.
- Der Konverter für Verschlüsselung und Migration als Gesamtlauf; geprüft wurde die geänderte Schreibfunktion.

---

## Stand 26.10.2026 – Intranet-Design, Sicherheitskorrekturen, GDP/1

Laufzeit: PHP 8.4.26 (CLI und eingebauter Webserver). Alle Tests liefen auf Kopien; Bestandsdaten, `crypt_key.php` und die übrigen Dateien unter `.config/.runtime` des Pakets sind unverändert (Verzeichnisvergleich mit dem gelieferten ZIP).

### Statische Prüfungen

| Prüfung | Ergebnis |
| --- | --- |
| PHP-Parser über alle 126 PHP-Dateien | keine Syntaxfehler |
| Code-Stil (`tests/style.php` des Gesamtpakets: K&R, `) :typ`, kurze Arrays, keine Tabs) | 126 Dateien OK |
| Aufrufe nicht existierender Methoden/Funktionen (eigener Token-Scan) | ein echter Fehler gefunden und behoben (`GBDB::migrate_to_v2`); übrige Treffer sind Enum-Builtins oder durch `method_exists` abgesichert |
| Keine Leerzeichen am Zeilenende, abschließender Zeilenumbruch (`.editorconfig`) | OK für alle neuen Dateien |

### GDP-Integrationstests (`php tests/gdp_test.php`)

Zwei getrennte Installationen (Hub, Replica) aus diesem Paket, je eigener PHP-Webserver, echte signierte HTTP-Anfragen. **80 von 80 Prüfungen bestanden** (rund 16 s). Abgedeckt (Nummern nach Konzept Kapitel 31):

- Nicht replizierte Instanz unverändert (1); Hub-Einrichtung, Policies je Tabelle.
- Pairing: ungültiger Code, Einmalcode, verbrauchter Code (14); Snapshot-Bootstrap mit Bestandsdaten.
- CENTRAL_OWNER offline/online gesperrt auf dem Replikat (8); NODE_OWNER über normale GBDB-Aufrufe mit automatischem Journal; Hub darf fremde NODE_OWNER-Daten nicht schreiben; APPEND_ONLY nur INSERT; LOCAL_ONLY nie journalisiert; Strukturänderungen ohne Wartungsmodus blockiert.
- Push/Pull, eigene Operation kehrt ohne Doppel-Apply zurück (19); UPDATE mit Vorversion, DELETE-Tombstone (18); Transaktionsgruppe.
- Gleiche operation_id mit gleichem Hash → ACK ohne Ausführung (02/03), mit anderem Hash → `DUPLICATE_PAYLOAD_MISMATCH` (04); Quellsequenzlücke (05); Replay einer signierten Anfrage abgewiesen (14).
- Unsignierte, defekte, zu große Nachrichten und unbekannte Endpunkte früh abgewiesen (22).
- Simulierter Abbruch zwischen Fachänderung und Journal → Intent-Recovery überträgt die Änderung (06).
- Hub-Ausfall: CONNECTION_UNAVAILABLE, OFFLINE, gesperrte und erlaubte Offline-Schreibvorgänge, PENDING, Nachreichen nach Rückkehr (01/07/08/09).
- Lease mit Datensatz-Eigentümer, abgelaufene Lease → `LEASE_EXPIRED` (15).
- Direkte Datenänderung an GDP vorbei → `VERSION_CONFLICT`, Blockade statt Überschreiben, Admin-Recovery mit Bestätigung, automatischer Vollsicherung und Re-Snapshot, Replikation läuft danach weiter (10/13/17).
- Schemaänderung über den Wartungsmodus → `SCHEMA_MISMATCH` → Re-Snapshot mit neuem Schema (12).
- Mandantentrennung: nicht freigegebene Instanz → 403 (21).
- Manipulierte Journalzeile von der Integritätsprüfung erkannt (17).
- Schlüsselrotation und Sync mit neuem Schlüssel (23); Eigentümerwechsel mit Fencing; Widerruf eines Knotens (13).
- Diagnose beschreibt die Grenzen des Sync-Modus ohne Echtzeitversprechen (25).

### Sicherheits-Gegentests (Testkopie, HTTP)

- Public-API-Schlüssel mit `gbdb-read`: `instance=__greenql_ui_v2_system` → 403 (vorher: Zugriff auf alle API-Schlüssel und Passwort-Hashes).
- Public-API-Schlüssel mit `gbdb-gql`: `USE INSTANCE __greenql_ui_v2_system` → 403.
- Erlaubte Instanzen funktionieren unverändert.

### Oberfläche (Browser und HTTP)

- Alle 18 Seiten und alle neun GDP-Ansichten liefern HTTP 200 (gesperrte Werkzeuge 403) ohne PHP-Warnungen, Notices, Deprecations oder Fatal Errors; Server-Log ohne Fehler.
- Im Browser geprüft: Login, Übersicht, Benutzer, GreenQL Studio (Instanz, Base, Tabelle anlegen, Datensatz einfügen, Bearbeiten-Dialog), Speicher, Backup (erstellt), GQL-Skripte mit Editor, PHP Exec (Ausführung), Konfiguration, Public API, Enterprise, Monitoring, Optimierung, Migration, Verschlüsselung, Distributed (Übersicht, Policies, Pairing-Code erzeugt, Sicherheit), Hell/Dunkel (bleibt gespeichert), Strg + K, Mobilansicht 375 px mit Menü und ohne horizontales Scrollen.
- Konfigurationseditor mit dem neuen Bereich `GDP`: Rundlauf erzeugt gültiges PHP, falsche Datentypen werden abgewiesen.

### Nicht geprüft

- **SQL-Failover**: Auf dem Prüfsystem ist kein PDO-Treiber (SQLite/MySQL) installiert; Spiegelung, Umschaltung und die neue SQL-Transaktion je Schreibabschnitt wurden daher nicht ausgeführt. Die frühere Prüfung (unten) lief mit PDO/SQLite.
- Konzept-Szenario 20 (1000 parallele Sync-Requests) und ein echter Prozessabbruch mitten im Schreibvorgang (statt des simulierten Intents).
- Der Worker als Dauerprozess sowie Webspace-spezifische Rechte und Apache-`.htaccess`-Auswertung (der PHP-Testserver wertet `.htaccess` nicht aus).

---

## Stand 24.10.2026 – Setup-Reparatur

Laufzeit: PHP 8.3.6. Alle damals 105 PHP-Dateien ohne Syntaxfehler. 30 Prüfungen bestanden: eine einzige PHP-Konfiguration, DEV/PROD-Auswahl, automatische Initialisierung, 404 für die Konfigurationsdatei, konkrete Setup-Fehlermeldungen (Token, Passwortlänge, Passwortwiederholung, CSRF), Admin-Anlage und Login über getrennte Anfragen, Schutz vor zweiter Ersteinrichtung, Logout und falsches Passwort, Erreichbarkeit der UI-Bereiche, ENV-Editor (Datenpfad geschützt, Typprüfung, Sonderzeichen, veraltete Version), keine PHP-Warnungen, unveränderte Bestandsdaten, SQL-Spiegel ohne Cronjob, Wechsel nach SQL und kontrollierte Rückkehr. Der SQL-Test verwendete PDO/SQLite; ein externer MySQL-/MariaDB-Server wurde nicht geprüft.
