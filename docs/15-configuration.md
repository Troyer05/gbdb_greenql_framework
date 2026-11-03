# 15 – Zentrale Konfiguration

Die bearbeitbare Hauptkonfiguration ist **`PHP/gbdb_framework/.config/.framework.env.php`**. Sie definiert `Vars` mit Konfigurationsarrays und Zugriffsmethoden. `GBDBConfig` bildet dieselben Werte auf interne Bereiche ab; es ist keine zweite Konfigurationsdatei.

## Bereiche

| Konstante | Aufgabe |
| --- | --- |
| `APP` | Anwendung, Umgebung, lokale Anwendungsversion und Architekturwahl |
| `CACHE` | Cache-Instanz |
| `AUTH` | Anwendungsanmeldung, Ablaufzeiten, öffentliche Ausnahmen und anfängliche Kontowerte |
| `UI` | UI-Aktivierung, erster Setup-Token und Freigabeschalter |
| `STORAGE` | Physischer Datenpfad, Sperrzeitlimit und Anfragecache |
| `SECURITY` | HTTPS-Weiterleitung, Datenverschlüsselung und Schlüsselvorgaben |
| `FAILOVER` | SQL-Dateispiegel, Zugangsdaten, Präfix und Wiederholungsabstand |
| `FAILOVER_DEV` | Optionale DEV-Überschreibungen für denselben Fallback |
| `SQL` | Direkte SQL-Verbindungen für `prod` und `dev` |
| `GDP` | Optionaler verteilter Betrieb |
| `FRAMEWORK_UPDATER` | Suche, Prüfung und Installation signierter Framework-Releases |
| `GREENQL` | Werte für GreenQL `ENV("name")` |
| `MAIL` | Mail-API |
| `SRVP` | SecondServer-URL und statischer Schlüssel |
| `MROOT`, `UPDATE` | Vorhandene Produktkonfiguration, obwohl das mRoot-Plugin noch leer ist |
| `MQR` | MuseumQR-API |
| `SHARESUTE` | Vorhandener Konfigurationsname für ShareSuite; Schreibweise im Code beibehalten |
| `EQR_API` | Vorhandene EventQR-Konfiguration |
| `RECAPTCHA` | reCAPTCHA-Schlüssel |
| `LEGACY` | Unterstützung älterer Konvertierungsabläufe |
| `INIT_COOKIES`, `INIT_SESSION` | Werte für die vorhandenen Cookie-/Sitzungshelfer |

Die beiden letzten Konstanten sind Arrays mit Einträgen, keine allgemeinen booleschen Startschalter. Produktkonfiguration ist keine Aussage über den Implementierungsstand einer Anbindung.

## Datenpfad und Schlüssel

`STORAGE.path` ist entweder absolut oder relativ zum Ordner `gbdb_framework`. Der Standard lautet `GBDB_GQL/.DB`. Für einen persistenten Container-Mount kann beispielsweise `/var/www/.dta` gewählt werden; der Mount und dessen Rechte müssen tatsächlich vorhanden sein.

| Zugriff | Ergebnis |
| --- | --- |
| `GBDBConfig::dataPath()` | Physischer Datenpfad |
| `GBDBConfig::runtimePath()` | Lokaler Ordner `.config/.runtime` |
| `Vars::data_root()` | Logischer Speichereinstieg über `gbdbdata://store` |
| `Vars::DB_PATH()` | Logischer Tabellen-/Instanzspeicher unterhalb des Datenbestands |
| `Vars::cryptKey()` | Konfigurierter oder einmalig lokal erzeugter Verschlüsselungsschlüssel |

Bei leerem Schlüsselwert wird der benötigte Installationsschlüssel lokal erzeugt und gespeichert. Nicht bei jedem Deployment ersetzen. Für einen Datenumzug Bestand, Konfiguration, Runtime-Schlüssel und aktiven Backend-Zustand zusammen übernehmen.

## DEV und PROD

`APP.environment` legt die Umgebung fest. Für die Umgebungsauswahl haben `GBDB_ENV` und danach `GBDB_DEV` Vorrang; akzeptierte `GBDB_ENV`-Werte sind unter anderem `dev`, `development`, `local`, `prod` und `production`.

DEV verwendet **ohne eigene `FAILOVER_DEV`-Werte dieselbe Fallback-Verbindung** wie PROD. Unabhängige Installationen oder Testkopien brauchen getrennte Datenpfade und SQL-Präfixe beziehungsweise Datenbanken. Das bloße Umschalten der Umgebungsbezeichnung isoliert keine vorhandenen Daten.

## Umgebungsvariablen

Zugriffe über `GBDBConfig::get($section, $name)` akzeptieren Überschreibungen nach dem Muster:

`GBDB_` + Bereich und Name in Großbuchstaben, Punkte durch Unterstriche ersetzt.

| Beispiel | Überschreibt |
| --- | --- |
| `GBDB_STORAGE_PATH` | `storage.path` |
| `GBDB_UI_SETUP_TOKEN` | `ui.setup_token` |
| `GBDB_FAILOVER_ENABLED` | `failover.enabled` |
| `GBDB_FAILOVER_DSN` | `failover.dsn` |
| `GBDB_FAILOVER_DEV_PREFIX` | `failover.dev.prefix` |
| `GBDB_GDP_ENABLED` | `gdp.enabled` |
| `GBDB_GDP_HUB_URL` | `gdp.hub_url` |

Nicht jede ältere `Vars`-Methode liest über `GBDBConfig`; einige greifen unmittelbar auf Konstanten zu. Deshalb nicht voraussetzen, dass beliebige `GBDB_*`-Namen jede Produktmethode überschreiben. Verschachtelte Arrays werden über den jeweiligen übergeordneten Zugriff gelesen; ein frei erfundener Name für ein tiefes Blatt ist keine zugesicherte Überschreibung.

## Bearbeitung über die Oberfläche

Unter **System → Framework-Konfiguration** werden die Konstanten als JSON angezeigt. Beim Speichern entstehen wieder PHP-Arrays in derselben Vars-Datei. Die Methoden und Guards bleiben erhalten, Datentypen und vorhandene Schlüssel werden geprüft. Es wird kein eingegebener PHP-Code ausgeführt.

Admins dürfen die Konfiguration immer öffnen. Andere Konten brauchen zusätzlich `UI.allow_config_edit` und eine passende Freigabe. Die Schalter `allow_php_exec`, `allow_plugin_upload`, `allow_reinstall` und `allow_config_edit` sind nur direkt in der Datei änderbar.

Datenpfad- und Schlüsselwechsel werden über die Oberfläche im laufenden Bestand blockiert. Ist SQL maßgeblich, vor einer Änderung des SQL-Ziels kontrolliert nach GBDB zurückschalten. Direkte Dateibearbeitung umgeht diese Oberflächenprüfung und verlangt entsprechend einen geplanten Umzug.

## Versionsquellen

`APP.version` beziehungsweise `Vars::app_version()` gehört zur Anwendung. Die Framework-Version kommt aus `version.json` und wird über `GBDBUpdateVersion` gelesen. Im vorliegenden Quellpaket lautet sie `10.0.1`; `release_sequence = 0` kennzeichnet keinen offiziell gestempelten Release-Stand. Die Aufbewahrung lokaler Vars-Werte bei Updates ist gerade der Grund für die getrennte Versionsdatei.
