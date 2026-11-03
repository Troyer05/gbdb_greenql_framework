# 19 – Release-Manager

Version laut Code: **1.0.0** · Dokumentationsstand: **03.11.2026**.

Die vollständige Lieferung enthält den Release-Manager als eigenen Ordner neben dem Framework. Dieses Kapitel bleibt für die eigenständige Framework-Dokumentation im Repository erhalten. Alle Pfade und Entwicklungsbefehle in diesem Kapitel beziehen sich auf den Release-Manager-Ordner.

Diese eigenständige PHP-Anwendung nimmt Framework-ZIPs entgegen, prüft und bereinigt den vorgesehenen Release-Inhalt und veröffentlicht signierte Pakete, Manifeste und Kataloge. Sie ist vom Framework-Client getrennt. Dessen Verfahren steht in der [Updater-Anleitung](../PHP/FRAMEWORK_UPDATER.md).

## Quellordner und beigelegte ZIP

Maßgeblicher Stand dieses Ordners ist **`gbdb/` samt `tools/` und dieser Anleitung**. Die danebenliegende **`gbdb_release_manager.zip` enthält teilweise älteren Code**: `index.php` und mehrere Dateien unter `lib/` unterscheiden sich bereits im gelieferten Paket vom Quellordner. Die gleiche interne Versionsnummer belegt daher keine identischen Dateien.

Die Dokumentationsüberarbeitung aktualisiert auch die README innerhalb dieser ZIP, baut deren Anwendungscode aber nicht neu. Für einen aktuellen Upload den Quellordner `gbdb/` verwenden. Vor einer neuen Veröffentlichung des separaten ZIP-Pakets dessen Code bewusst aus dem vorgesehenen Quellstand neu packen und prüfen.

## Voraussetzungen

| Bestandteil | Voraussetzung |
| --- | --- |
| PHP | Ab 8.1 |
| Erweiterungen | `sodium`, `zlib`, `mbstring`, `json` und Tokenizer für PHP-Quellprüfungen |
| PHP-Uploadgrenzen | `upload_max_filesize`, `post_max_size`, Speicher und Zeitlimits passend zur Paketgröße |
| Webserver | Apache mit wirksamer `.htaccess` und `mod_autoindex` für die öffentliche Update-Liste; bei anderen Servern gleichwertige Konfiguration |
| Speicher | Schreibbarer privater Datenordner und öffentliches `updates/` |
| Zugriff | HTTPS für Verwaltung und Veröffentlichungsserver |

Die ZIP-Erweiterung von PHP ist nicht erforderlich. Für die Einrichtung sind weder SSH noch Cronjobs nötig. CLI-Befehle weiter unten dienen Entwicklung und Prüfung.

## Einrichtung

1. Den Quellordner **`gbdb/`** samt `.htaccess` und `lib/.htaccess` an die gewünschte Adresse hochladen. Die voreingestellte Framework-Konfiguration erwartet `https://mamueller.de/gbdb/updates/` und `https://mamueller.de/gbdb/verify.php`; bei einer eigenen Adresse den Client passend konfigurieren.
2. In `gbdb/config.php` `setup_token` durch einen **eigenen zufälligen Wert mit mindestens 24 Zeichen** ersetzen. Im mitgelieferten Arbeitsstand ist bereits ein Token eingetragen; ein weitergegebenes Quellpaket ist keine geheime Tokenquelle.
3. Bei Bedarf `private_dir` auf einen absoluten beschreibbaren Ordner außerhalb des Webroots setzen. Leer bedeutet automatische Auswahl, bevorzugt neben dem Document Root, sonst `gbdb/.private/`.
4. Die Verwaltungsadresse im Browser öffnen und den ersten Admin mit Token, Benutzernamen und Passwort ab **12 Zeichen** anlegen.
5. Die Anwendung erzeugt Root-, Recovery- und Release-Schlüssel, Vertrauensmetadaten, Katalog und Update-Verzeichnis. Den Recovery-Schlüssel herunterladen, offline sichern und anschließend über die Oberfläche vom Server löschen.
6. Eine Framework-ZIP hochladen, Vorschau und Meldungen prüfen und die Veröffentlichung ausdrücklich auslösen.

Die Oberfläche und Verify-API verwenden im Code **UTC**. Ein leerer oder zu kurzer Setup-Token sperrt die Ersteinrichtung.

## Paketprüfung

Der Packager liest Inhalte aus der ZIP, ohne das hochgeladene Archiv direkt in einen Webordner zu entpacken. Er unterstützt den vorgesehenen Repository-/`PHP`-/Framework-Aufbau und verlangt `gbdb_framework/updater/release_rules.json`.

| Kategorie | Behandlung |
| --- | --- |
| In den Release-Regeln deklarierter Core | Prüfen und übernehmen |
| Daten, `.config/.runtime`, `.logs`, Git-/IDE-Zustand, Sicherungen und temporäre Dateien | Ausschließen |
| Vorhandene `version.json` und `trust_anchor.json` | Nicht als lokalen Ausgangswert übernehmen; beim Veröffentlichen neu stempeln |
| Dafür vorgesehene Konfigurationsdateien | Erkannte sensible Werte leeren |
| Geheimnisfund in sonstigem Core | Veröffentlichung blockieren |
| Nicht deklarierte Dateitypen oder Ergänzungen | Ausschließen und in der Vorschau aufführen |
| Entfernte Release-Dateien oder Verweise auf ausgeschlossene Dateien | Zusätzliche Prüfung beziehungsweise ausdrückliche Bestätigung |

Der ZIP-Prüfer weist unter anderem unsichere Pfade, symbolische Links, Verschlüsselung, ZIP64, Größenüberschreitungen und Datei-/Ordner-Konflikte zurück. Der Packager prüft PHP-Syntax, erforderliche Dateien, konkrete Pflichtklassen und Pflichtmethoden, die Einbindung des Update-Gates und erkannte Abhängigkeiten.

Die Geheimnissuche arbeitet mit Mustern. Sie ersetzt keine manuelle Prüfung und bereinigt keine bereits veröffentlichte Git-Historie. Die normalen Dokumentationsänderungen im Framework führen selbst keine solche Paketbereinigung aus.

## Veröffentlichung

Version, Mindestversionen und Änderungstext werden in der Vorschau vorgeschlagen beziehungsweise ergänzt. Nach Bestätigung baut die Anwendung eine Release-ZIP, stempelt Version und Vertrauensanker, erstellt Manifest und Katalog, signiert die Metadaten und prüft die Kette mit den gemeinsamen Framework-Prüfklassen.

Eine Versionsnummer darf nicht erneut vergeben werden. Der Veröffentlichungszustand wird mit Journal und Sequenzen fortgeschrieben; bei einer unterbrochenen Veröffentlichung versucht der nächste Aufruf den vorgesehenen Abschluss oder die Rücknahme. Beschädigte Ablagen werden nicht still als leer behandelt.

## Ablage und Sicherung

| Ort | Inhalt |
| --- | --- |
| `gbdb/updates/` | Öffentliche ZIPs, Manifeste, Katalog und Vertrauensmetadaten mit Signaturen |
| `private_dir` | Admins, Schlüssel, Release-Datenbank, Zustand, Uploads und Protokolle |
| `gbdb/config.php` | Lokale Konfiguration und Einrichtungs-Token |

Der private Bereich verwendet PHP-Guards und zusätzliche Serverregeln. Trotzdem PHP-Ausführung und effektiven HTTP-Zugriffsschutz prüfen; insbesondere Backup-Dateien dürfen nicht ungeschützt veröffentlicht werden.

Privaten Zustand, Konfiguration und öffentlichen Release-Bestand zusammen sichern. Einen verlorenen privaten Schlüsselbestand nicht durch eine beliebige neue Einrichtung ersetzen, wenn vorhandene Installationen bereits dem bisherigen Root vertrauen.

## Schlüssel und Erneuerung

Root- und Release-Schlüssel liegen verschlüsselt auf dem Server; die Webanwendung kann sie entschlüsseln. Dieses Modell hält Root-Schlüssel nicht offline und schützt nicht pauschal vor einer vollständigen Serverübernahme. Der offline gesicherte Recovery-Schlüssel ermöglicht den vorgesehenen Notfallwechsel.

Die Oberfläche unterstützt Release-Schlüsselwechsel, Widerruf, Root-Wechsel und Recovery. Einen `trust_anchor.json` für Framework-Quellstände kann man unter **Schlüssel & Vertrauen** herunterladen und nach `gbdb_framework/updater/trust_anchor.json` übernehmen. Die Quelle des ersten Ankers muss überprüft sein.

Standardwerte aus `config.php`:

| Wert | Standard |
| --- | --- |
| Uploadgröße | 100 MiB zusätzlich zu PHP-Grenzen |
| Vertrauensmetadaten | 730 Tage gültig, Erneuerung ab 180 Tagen Restlaufzeit |
| Katalog | 120 Tage gültig, Erneuerung ab 90 Tagen Restlaufzeit |
| Release-Schlüssel | 730 Tage gültig, Erneuerung ab 120 Tagen Restlaufzeit |
| Anmeldeversuche | 5 je vorgesehenem Zeitfenster |

Erneuerungen werden bei Aufrufen der Verwaltung oder Verify-API angestoßen. Ohne Aufrufe gibt es keinen dauerhaft laufenden Erneuerungsdienst. Ein Client kann über einen GET auf `verify.php` ein Lebenszeichen auslösen und danach Metadaten erneut prüfen.

## Verify-API

`GET verify.php` liefert einen einfachen Bereitschaftsstatus. `POST verify.php` erwartet JSON mit `version`, `filename` und einem SHA-256-Hash in `sha256`. Der Server vergleicht Release-Datenbank, Zuordnung und den erneut berechneten Hash der veröffentlichten ZIP.

Der POST-Eingang ist auf 4096 Byte begrenzt und verwendet 30 Prüfungen je IP und Minute. Eine gültige Prüfanfrage kann mit HTTP 200 und `verified = false` abgelehnt werden. Clients müssen deshalb `verified` und `reason` auswerten, nicht nur den HTTP-Status.

## Entwicklung und Prüfungen

Aus **diesem** Ordner:

```bash
php tools/sync-shared.php --check
php ../gbdb_greenql_framework/PHP/tests/updater_test.php
```

`sync-shared.php --check` vergleicht `signature.php`, `zip.php`, `zip_writer.php` und `trust.php` mit dem benachbarten Framework. Ohne `--check` werden die gemeinsamen Dateien **überschrieben**. Ein abweichender Framework-Pfad ist als weiteres Argument möglich.

Am 03.11.2026 wurden die vier gemeinsamen Dateien auf Bytegleichheit geprüft. PHP-Syntax und die mitgelieferte Updater-Testsuite sind im [Framework-Prüfbericht](../PHP/TESTBERICHT.md) ausgewiesen. Der dort historisch genannte externe End-to-End-Test liegt nicht im Paket und ist kein aus dieser ZIP reproduzierbarer Test.

Diese Dokumentationsüberarbeitung nimmt keine neue Live-Veröffentlichung und keinen vollständigen Browser-/HTTPS-/Apache-Abnahmelauf des Release-Managers vor.
