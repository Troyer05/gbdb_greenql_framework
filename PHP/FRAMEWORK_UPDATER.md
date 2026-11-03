# Signierte Framework-Updates

Dokumentationsstand: **03.11.2026**, mitgeliefertes Framework **10.0.1**.

Der Updater prüft signierte Release-Metadaten, lädt freigegebene Pakete und installiert sie mit Sicherung, Selbstprüfung und Wiederherstellung. Er liegt unter `gbdb_framework/updater/` und wird über den Autoloader eingebunden. Der **Release-Manager ist eine separate Anwendung** im danebenliegenden Paketordner `gbdb_release_manager/`; er gehört nicht zur Framework-Laufzeit. Seine [eigene Anleitung](../docs/19-release-manager.md) beschreibt Einrichtung und Veröffentlichung. Hier stehen der Framework-Client und sein Veröffentlichungsvertrag.

[Handbuch](../docs/README.md) · [Updater-Methodenreferenz](../docs/99-api-reference/updater-methods.md) · [Prüfbericht](TESTBERICHT.md)

## Voraussetzungen und Versionsquelle

- PHP ab 8.1 und die für das Framework benötigten Erweiterungen.
- `sodium` für Ed25519-Signaturen, `zlib` für ZIP-Inhalte und Tokenizer für PHP-Syntaxprüfungen.
- HTTP-Zugriff über die unterstützten Transportwege; in PROD HTTPS mit Zertifikatsprüfung.
- Schreibrechte auf betroffene Framework-Dateien, Konfiguration und den Arbeitsordner sowie ausreichend Platz für Download, Staging und Sicherungen.
- Ein überprüfter Vertrauensanker oder bereits gespeicherter Vertrauensstand.

Die installierte Framework-Version kommt aus **`gbdb_framework/version.json`**, nicht aus der lokalen Anwendungsversion in Vars. Das Quellpaket enthält `10.0.1`, `release_sequence = 0` und noch keinen eingebauten `trust_anchor.json`. Es ist damit kein offiziell gestempeltes Installationsrelease.

Die voreingestellten Adressen sind:

| Aufgabe | Wert im Code |
| --- | --- |
| Update-Verzeichnis | `https://mamueller.de/gbdb/updates/` |
| Verify-API | `https://mamueller.de/gbdb/verify.php` |

Diese Werte dokumentieren die Konfiguration. Die aktuelle Erreichbarkeit externer Server wurde im Rahmen dieser Dokumentationsüberarbeitung nicht geprüft.

## Bedienung

Admins öffnen **System → Framework-Updates**. Eine neue angebotene Version erscheint zusätzlich als Hinweis und in den Systemmeldungen. Dort lassen sich Suche, Installation, Überspringen und vorhandene Wiederherstellungspunkte bedienen.

Die Installation durchläuft getrennte Phasen:

1. **Download und Prüfung:** Vertrauenskette, Katalog und Manifest prüfen, ZIP laden und Prüfsummen sowie Verify-API auswerten.
2. **Vorabprüfung:** PHP-Version, Erweiterungen, Mindestversion, Dateiplan, lokale Änderungen, Rechte und Platzbedarf prüfen.
3. **Sicherung:** Betroffene Dateien und Konfiguration in den Arbeitsordner kopieren.
4. **Vorbereitung:** Paket in Staging entpacken, Dateimanifest und PHP-Syntax prüfen sowie die Konfiguration abgleichen.
5. **Einspielen:** Wartungsmodus aktivieren, erneut prüfen und Dateien einzeln atomar ersetzen; bei anstehenden Migrationen den Datenordner sichern.
6. **Selbstprüfung:** Eine Folgeanfrage lädt den neuen Code und prüft Klassen, Version, Dateiplan, Konfigurationsbereiche und Datenzugriff. Danach laufen ausstehende Migrationen. Bei Erfolg abschließen, bei Fehlern den vorgesehenen Rollback ausführen.

Die Phasen verteilen Arbeit auf mehrere Anfragen. Einzelne Sicherungs-, Entpack- oder Migrationsschritte können trotzdem Hosting-Zeit- oder Speichergrenzen erreichen. Nach dem Einspielen ist eine Folgeanfrage für den Abschluss erforderlich.

### Manuelle Entscheidungen

| Situation | Verhalten |
| --- | --- |
| Verify-API technisch nicht erreichbar | `VERIFIED_API_UNAVAILABLE`; manuell bestätigen oder abbrechen |
| Lokale Änderungen oder Fremddateien auf neuen Release-Pfaden | Dateiliste prüfen; ausdrücklich ersetzen und vorher sichern oder abbrechen |
| Kein Ausgangsmanifest vorhanden | Erstes Update des nicht gestempelten Bestands verlangt eine Entscheidung |
| Ungültige Signatur, Prüfsumme, Schlüssel, Metadaten oder Inkompatibilität | Installation wird blockiert; keine normale Umgehungsschaltfläche |

Eine ausdrückliche Ablehnung der Verify-API ist kein Erreichbarkeitsproblem und darf nicht wie eines bestätigt werden. Automatische Updates beantworten keine Rückfragen.

### Wiederherstellungspunkte

Die Oberfläche kann den Stand vor dem zuletzt installierten Update kontrolliert wiederherstellen. Der bestätigte Vertrauensstand bleibt erhalten. Ein Release mit Datenmigration lässt sich nicht wie ein reines Code-Update über diese Funktion zurücknehmen.

## Konfiguration

Der Bereich **`FRAMEWORK_UPDATER`** liegt in `.config/.framework.env.php`. Die alternative Schreibweise `FrameworkUpdater` wird ebenfalls erkannt und beim Abgleich demselben Bereich zugeordnet.

| Schlüssel | Bedeutung / Standard |
| --- | --- |
| `check_for_updates` | Automatische Suche aktiv, standardmäßig `true` |
| `update_server`, `verify_api` | Voreingestellte Serveradressen |
| `force_ssl` | HTTPS erzwingen; `false` wirkt nur in DEV |
| `check_interval` | Suchabstand in Sekunden, Standard 21600, mindestens 300 |
| `auto_updating.enabled` | Automatische Installation, Standard `false` |
| `auto_updating.patch_day` | Nächster zulässiger Termin, etwa `Fridays, 21:30:00` |
| `verification` | Signatur-, Metadaten-, Widerrufs- und Rollback-Prüfungen |
| `installation` | Sicherung, Erhalt lokaler Daten/Konfiguration und Fehlerwiederherstellung |
| `work_dir` | Leer: `.gbdb_updater` neben `gbdb_framework`; darf nicht im Framework liegen |
| `http_timeout` | HTTP-Zeitlimit, Standard 15 Sekunden |
| `keep_backups` | Aufbewahrte Update-Sicherungen, Standard 3 |

Die in `GBDBUpdateSettings::ENFORCED` genannten Sicherheitsprüfungen sind nicht abschaltbar. Bei `false` erzwingt der Code weiterhin `true` und meldet eine Warnung. `root_public_key` muss `BUILTIN` sein; die Algorithmen sind `sha256` und `ed25519`.

`verification.verify_via_api` ist ein Sonderfall: Bei `false` bleibt das Ergebnis `VERIFIED_API_UNAVAILABLE`, damit sind Bestätigung und manuelle Installation nötig. Es wird dadurch kein voll verifiziertes automatisches Update.

Ältere Konfigurationen ohne diesen Bereich verwenden die im Code hinterlegten Standards; beim Update kann der Konfigurationsabgleich fehlende Einträge ergänzen.

## Betrieb ohne Cronjob

Die UI kann eine fällige Suche über eine zusätzliche Anfrage anstoßen. Unter PHP-FPM registriert das Gate außerdem Arbeit nach Auslieferung der Antwort über `fastcgi_finish_request()`. Welche Wege verfügbar sind, hängt vom Hosting ab.

Ein erfolgreich geprüftes automatisches Update wird für den nächsten `patch_day` eingeplant. Unterstützt werden etwa `Fridays, 21:30:00`, `Freitags, 21:30` oder `daily, 03:00`. Ausgewertet wird die im PHP-Prozess aktive Zeitzone. Ab Fälligkeit startet die Installation bei einer geeigneten Anfrage; ohne Anfragen gibt es keine minutengenaue Ausführung. Automatisch installiert wird nur bei ausreichender Verifikation, erfolgreicher Sicherung und ohne offene Konfliktrückfrage.

`state/update.lock` serialisiert Update-Aktionen. Der Wartungsmodus liefert für andere Framework-Anfragen 503. Ein liegengebliebener Einspielvorgang wird beim nächsten Aufruf über das Gate behandelt; eine fehlende Folgeanfrage kann diesen Abschluss verzögern.

## Welche Dateien werden verändert?

| Kategorie | Behandlung |
| --- | --- |
| Core im neuen Manifest | Aktualisieren beziehungsweise neu anlegen |
| Alter unveränderter Core, im neuen Stand entfernt | Gemäß Dateiplan entfernen |
| Lokal veränderter Core | Bei Überschneidung mit einer Release-Änderung Rückfrage und Sicherung |
| Eigene Datei auf einem neuen Release-Pfad | Konfliktrückfrage und Sicherung |
| Andere eigene Dateien | Vom normalen Core-Dateiplan nicht verwaltet |
| Konfiguration | Erhalten und kontrolliert um neue Schlüssel/Bereiche/Methoden ergänzen |
| Daten, `.config/.runtime`, `.logs`, Arbeitsordner | Vom normalen Core-Ersetzen geschützt |
| Einstiegsdateien | Nur an den vom Installer vorgesehenen bereits vorhandenen Nachbarpfaden verwaltet |

Eine pauschale Zusage für beliebig verschachtelte eigene Projektlayouts ergibt sich daraus nicht. Vorab den angezeigten Dateiplan prüfen. Der installierte Ausgangsstand liegt in `state/installed.json` im Arbeitsordner.

## Vertrauenskette und Dateiformate

Prüfweg: **Anker → Vertrauensmetadaten → autorisierte Release-/Katalogschlüssel → Katalog → Manifest → ZIP → Verify-API**. Maßgeblich sind insbesondere `signature.php`, `trust.php`, `zip.php`, `updater.php` und `installer.php`.

### Signaturen

Eine `.sig`-Datei enthält JSON, beispielsweise:

```json
{
  "format": "gbdb-sig/1",
  "context": "gbdb-trust/1",
  "signatures": [{"key_id":"SCHLUESSEL_ID","signature":"BASE64_SIGNATUR"}]
}
```

Die Werte sind Platzhalter. Ed25519 signiert die exakten Dateibytes mit Domänentrennung aus `gbdb-sig/1`, NUL, Kontext, NUL und Inhalt. Kontexte sind `gbdb-trust/1`, `gbdb-catalog/1` und `gbdb-release/1`. JSON darf vor der Signaturprüfung nicht neu serialisiert werden.

Öffentliche Schlüssel sind die 32 Rohbytes in Standard-Base64. Schlüssel-IDs sind an den öffentlichen Schlüssel gebunden; Signaturen und Rollen werden unabhängig vom lesbaren Namen geprüft.

### Dateien im Veröffentlichungsvertrag

| Datei | Inhalt |
| --- | --- |
| `updater/trust_anchor.json` im Release | Ursprüngliche Root-/Recovery-Schlüssel und Schwellenwerte |
| `trust.json`, `trust.json.sig` | Signierte Vertrauensmetadaten, Versionen, Gültigkeit, Rollen und Widerrufe |
| `trust.<version>.json` mit Signatur | Zwischenstände für die nachvollziehbare Root-Fortschreibung |
| `releases.json`, `releases.json.sig` | Signierter Katalog mit monotoner Sequenz und freigegebenen Releases |
| `v<version>.json` mit Signatur | Manifest mit Paket, Dateihashes, Kompatibilität und Migrationen |
| `v<version>.zip` | Framework-Dateien passend zum Manifest |

Ein erster Anker muss aus einer überprüften Quelle stammen. Danach gilt der gespeicherte bestätigte Zustand in `state/trust_state.json`. Eine vom Dateiverzeichnis allein angebotene ZIP ist kein freigegebenes Release.

Die Vertrauensprüfung weist rückläufige Versionen/Sequenzen und widersprüchliche Inhalte zurück. Root-Wechsel benötigen die vorgeschriebenen alten beziehungsweise Recovery-Signaturen und den Besitznachweis der neuen Root-Schlüssel. Widerrufene oder abgelaufene Schlüssel und zurückgezogene Releases werden blockiert.

### Manifest

Wesentliche Felder sind `type`, `manifest_version`, `version`, `release_sequence`, `filename`, `sha256`, `size`, `published_at`, `minimum_php`, `minimum_gbdb`, `requires_migration`, `migrations`, `compatibility`, `framework_dir`, `config_files`, `entry_files`, `removed_files`, `files`, `signing_key_id` und `trust_version`.

Der Katalog bindet den Hash des Manifests. Das Manifest bindet Größe und Hash der ZIP sowie die erwarteten Einzeldateien. Paketpfade, Größen, Prüfsummen und Datei-/Ordner-Konflikte werden geprüft. Die mitgelieferte `release_rules.json` definiert erlaubte Core-/Konfigurationsmuster und Anforderungen für die Paketbildung durch den separaten Herausgeber.

### Ergebniszustände

| Zustand | Bedeutung |
| --- | --- |
| `VERIFIED` | Erfolgreich verifiziert; weitere Kompatibilitäts-/Installationsprüfungen bleiben erforderlich |
| `VERIFIED_API_UNAVAILABLE` | Kryptografische Prüfung erfolgt, ergänzende API nicht erreichbar beziehungsweise ausdrücklich deaktiviert |
| `NO_TRUST_ANCHOR`, `CRYPTO_UNAVAILABLE` | Anker oder erforderliche Kryptografie fehlt |
| `INVALID_SIGNATURE`, `UNTRUSTED_KEY`, `REVOKED_KEY` | Signatur oder Schlüssel nicht zulässig |
| `INVALID_CHECKSUM`, `INVALID_METADATA` | Dateiinhalt oder Metadaten widersprüchlich/ungültig |
| `EXPIRED_METADATA`, `CLOCK_INVALID` | Ablauf oder Systemzeitproblem |
| `ROLLBACK_DETECTED` | Rückläufiger bestätigter Stand erkannt |
| `API_REJECTED`, `RELEASE_REVOKED` | API lehnt das Release ab beziehungsweise Release zurückgezogen |
| `INCOMPATIBLE` | Mindestversionen oder Erweiterungen passen nicht |

Bei einem Fehler die genaue Meldung und den bestätigten Zustand prüfen, statt Trust-Dateien zu löschen. Sicherheitsereignisse werden unter `logs/security.jsonl` protokolliert.

### Verify-API

Der Client sendet per POST `version`, `filename` und `sha256`. Erwartet werden `ok`, `verified` und gegebenenfalls `reason`. Eindeutige Ablehnungen wie `unknown_version`, `checksum_mismatch`, `filename_mismatch` oder `release_revoked` blockieren. Netzwerk-/Serverfehler werden anders behandelt. Die API ersetzt nie die lokale Signaturprüfung.

Der beigelegte Release-Manager erzeugt und verwaltet die Schlüssel serverseitig. Das ist kein automatisch offline gehaltener Root-Schlüssel. Betriebsmodell und Recovery stehen in seiner [Anleitung](../docs/19-release-manager.md); ein produktiver Server wurde hier nicht geprüft.

## Migrationen

Dateien unter `updater/migrations/` liefern ein Array mit einer `up`-Funktion und optional einer `down`-Funktion. Das Release-Manifest ordnet Migrationen über `id`, `file` und `since` zu. Ausgeführt werden noch nicht angewendete Migrationen nach der installierten Ausgangsversion, auch bei übersprungenen Zwischenversionen.

Vorher sichert der Installer den **physischen** GBDB-Datenordner im Wartungsmodus. Nach der Selbstprüfung laufen Migrationen, erfolgreiche IDs werden in `state/migrations.json` vermerkt. Bei Fehlern versucht der Ablauf frühere `down`-Funktionen und die Wiederherstellung aus der Sicherung. Format und Grenzen: [Migrationsanleitung](gbdb_framework/updater/migrations/README.md).

## Arbeitsordner

Standard: **`.gbdb_updater/` neben `gbdb_framework`**. Bei Hosting mit öffentlichem Zugriff den Ordner zusätzlich wirksam sperren; eine enthaltene Apache-`.htaccess` genügt nur bei aktiver Auswertung.

| Pfad im Arbeitsordner | Aufgabe |
| --- | --- |
| `state/trust_state.json` | Bestätigter Vertrauensstand; nicht durch normales Code-Rollback zurücksetzen |
| `state/trust.json` und Signatur | Bestätigte Metadaten |
| `state/status.json` | Letzte Suche, verfügbare/übersprungene Versionen und Planung |
| `state/installed.json` | Installierte Version und Ausgangsmanifest |
| `state/job.json`, `state/history.jsonl` | Auftrag und Historie |
| `state/migrations.json` | Angewendete Migrationen |
| `maintenance.json` | Aktiver Wartungsmodus |
| `backups/`, `staging/`, `downloads/`, `logs/`, `rollback/` | Sicherungen und temporäre Abläufe |

## Grenzen der Wiederherstellung

Ein Datei-Rollback ist kein allgemeiner verteilter Transaktionsrollback. Migrationen mit aktiver SQL-Autorität, GDP-Replikation, E-Mails oder anderen externen Nebenwirkungen gesondert planen und testen. Eine physische Dateisicherung allein setzt keine bereits versendete Nachricht und keinen fremden Knoten zurück.

Selbstprüfung und Gate behandeln vorgesehene Fehlerpfade. Beliebige Datenträger-, PHP-Prozess- oder Hostingfehler sind damit nicht vollständig ausgeschlossen. Die mitgelieferten Tests prüfen Kernbausteine; externe Veröffentlichung, produktives HTTPS, Apache/FPM und Browser benötigen eigene passende Abnahme.
