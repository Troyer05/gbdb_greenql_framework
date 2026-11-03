# 02 – Installation und Ersteinrichtung

## Voraussetzungen

| Bestandteil | Voraussetzung |
| --- | --- |
| PHP | Ab 8.1; Enums, Union-Typen und neuere Sprachfunktionen werden verwendet |
| Verschlüsselung und GDP-Pairing | OpenSSL, insbesondere AES-256-GCM |
| HTTP-Aufrufe | cURL oder aktiviertes `allow_url_fopen`; die MuseumQR-Anbindung benötigt cURL |
| Framework-Updates | `sodium` für Ed25519, `zlib` für ZIP-Kompression und PHP-Tokenizer für Syntaxprüfungen |
| Unicode | `mbstring` empfohlen; der Autoloader ersetzt einige Funktionen sonst durch einfache Byte-Funktionen |
| SQL-Fallback, optional | PDO mit `pdo_mysql` für MySQL/MariaDB oder `pdo_sqlite` |
| Schreibzugriff | Datenordner, `.config/.runtime`, PHP-Temp-Verzeichnis und bei Updates der Arbeitsordner sowie betroffene Framework-Dateien |
| Webserver | PHP-fähiger Webspace; Apache-Zugriffsschutz oder entsprechende Regeln des eingesetzten Servers |

Eine Shell wird für die Einrichtung nicht benötigt. CLI-Befehle in der Dokumentation dienen Entwicklung und Tests oder einem optionalen GDP-Worker.

## Dateien hochladen

Aus dem Repository den Inhalt von `PHP/` in das gewünschte Anwendungsverzeichnis übernehmen. Versteckte Dateien und Ordner mit führendem Punkt mitkopieren. Die vier Einstiegsdateien liegen regulär neben `gbdb_framework`:

| Datei | Zweck |
| --- | --- |
| `gbdb_ui.php` | Browseroberfläche und Ersteinrichtung |
| `public_api.php` | Public API |
| `backend.php` | SecondServer-Backend |
| `gdp.php` | GDP-Endpunkt |

Die Einstiegsdateien suchen das Framework auch in übergeordneten Verzeichnissen, unter anderem unter `PHP/gbdb_framework` und `assets/php/inc/gbdb_framework`. Bei abweichender Ablage vor dem Einstieg `GBDB_FRAMEWORK_PATH` als Konstante oder Umgebungsvariable auf den absoluten Framework-Ordner setzen. Eigener Anwendungscode lädt den Autoloader mit einem eindeutig bestimmten Pfad.

## Ersten UI-Admin anlegen

1. `gbdb_framework/.config/.framework.env.php` öffnen. Das ist die zentrale bearbeitbare Konfigurationsdatei.
2. In `UI` den Wert `setup_token` durch einen eigenen zufälligen Token mit mindestens **24 Zeichen** ersetzen. Im vorliegenden Quellpaket ist dieser Wert **leer**; ein gemeinsamer voreingestellter Token wird nicht mitgeliefert.
3. Falls nötig `STORAGE.path` einstellen und dem PHP-Prozess Schreibrechte auf die benötigten Ordner geben.
4. `gbdb_ui.php` im Browser öffnen. Solange noch kein UI-Konto existiert, erscheint die Ersteinrichtung.
5. Token, Benutzernamen und ein eigenes Passwort mit mindestens **12 Zeichen** eingeben; die Passwortwiederholung muss übereinstimmen. Als Sprache kann Deutsch gewählt werden.
6. Nach erfolgreicher Anlage erfolgt die Anmeldung. Bei weiteren Aufrufen erscheint die normale Anmeldung.

Ein zufälliger Token lässt sich bei Bedarf mit PHP erzeugen:

```php
<?php

echo bin2hex(random_bytes(24));
```

Die erste Admin-Anlage ist serverseitig gesperrt, sobald Konten vorhanden sind. Ein Quellpaket mit vorhandenem Datenbestand wird durch erneutes Hochladen nicht automatisch auf Einrichtung zurückgesetzt.

## Daten und Schlüssel erhalten

Vor einer Aktualisierung Daten und Konfiguration gemeinsam sichern:

| Bestand | Warum erhalten? |
| --- | --- |
| Der tatsächlich konfigurierte Datenordner | Tabellen, Schema, Skripte, Sicherungen und gegebenenfalls GDP-Journal |
| `.config/.framework.env.php` | Lokale Einstellungen |
| `.config/.runtime/` | Verschlüsselungs- und Installationsschlüssel, Speicherstatus sowie gegebenenfalls GDP-Tresor |
| `.gbdb_updater/` neben dem Framework | Vertrauensstand, Update-Zustand und Wiederherstellungspunkte |
| Eigene Module und Plugins | Anwendungsspezifischer Code |

Ein neuer Schlüssel macht vorhandene verschlüsselte Daten nicht wieder lesbar. `.runtime` deshalb nicht unabhängig vom Datenbestand austauschen. Die überarbeitete Dokumentation führt weder einen Datenreset noch eine Bereinigung von lokalen Zugangsdaten durch.

Bei älteren Installationen werden `.config/vars.php` und `.config/.greenql.env.php` nicht mehr als zentrale Konfiguration geladen. Eigene Werte vor dem Entfernen in die aktuelle Vars-Struktur übernehmen. GreenQL-Umgebungswerte stehen jetzt im Bereich `GREENQL`.

## Zugriffsschutz prüfen

`gbdb_framework/.htaccess` enthält `Require all denied`. Auf Apache muss der Verzeichnisschutz tatsächlich ausgewertet werden. Statische UI-Dateien werden über `gbdb_ui.php?gbdb_asset=…` ausgeliefert; ein direkter Webzugriff auf das Framework ist nicht erforderlich.

Bei Nginx oder deaktivierter `.htaccess`-Auswertung entsprechende Sperren für den Framework-Ordner, externe Datenpfade und den Updater-Arbeitsordner konfigurieren. Im Browser darf eine direkte Anfrage auf Konfiguration, Daten, Schlüssel oder Sicherungen keinen Dateiinhalt liefern. PHPs eingebauter Testserver wertet `.htaccess` nicht aus.

Schreibrechte über Eigentümer, Gruppe oder ACL passend zum Hosting vergeben. Ein Datenpfad außerhalb des Webroots muss trotzdem erreichbar und beschreibbar sein. Container brauchen dafür einen persistenten Bind-Mount oder ein Volume; ein im Container angelegter Ordner allein überlebt keine Neuerstellung.

## Framework in eine Anwendung laden

Dieses Beispiel wird neben `gbdb_framework` gespeichert:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/gbdb_framework/autoloader.php';

GBDB::createInstance('beispiel');
GBDB::withInstance('beispiel', static function () :void {
    GBDB::createDatabase('app');
    GBDB::createTable('app', 'einstellungen', ['schluessel', 'wert']);
});
```

`withInstance()` stellt die vorherige Instanz anschließend auch bei einer Exception wieder her. Das Schema einmalig im Installer oder einer Migration anlegen.

## Bestehendes Framework aktualisieren

Für signierte Releases den [Framework-Updater](../PHP/FRAMEWORK_UPDATER.md) verwenden. Im Quellpaket fehlt ein Vertrauensanker; seine Herkunft muss vor der ersten Nutzung feststehen. Für eine manuelle Aktualisierung zuerst sichern, anschließend Framework-Code austauschen und lokale Konfiguration, Laufzeitordner und Daten erhalten.

Vor einem Git-Push lokale Schlüssel, Daten und Zugangsdaten im eigenen Arbeitsbaum sowie bereits versionierte Dateien prüfen. Diese Dokumentationsüberarbeitung verändert keine bestehenden Git-Tracking-Regeln und bereinigt keine Git-Historie.
