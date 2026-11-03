# 18 – Funktionsstand und bekannte Grenzen

Stand der Dokumentation: **03.11.2026**, mitgeliefertes Framework **10.0.1**. Diese Übersicht beschreibt den Codebestand und seine eigenen Statusangaben. Sie ist keine pauschale Produktionsfreigabe oder Leistungszusage.

## Vorhandene Bereiche

| Bereich | Stand |
| --- | --- |
| Dateibasierte GBDB-Engine | Vorhanden mit öffentlicher Laufzeitfassade |
| GreenQL | Vorhanden; einfache Abfragen sowie umfangreiche Skript- und Verwaltungsbefehle |
| UI und getrennte UI-Konten | Vorhanden, einschließlich Einrichtung, Rechte und Konfigurationseditor |
| Anwendungsanmeldung | Vorhanden, einschließlich E-Mail-/2FA-Abläufen bei eingerichteter Mailanbindung |
| Public API | Vorhanden mit Schlüsseln und Aktionsrechten; kein allgemeines Tabellen-Scoping pro Schlüssel |
| SecondServer | Vorhanden; Aufträge werden im jeweiligen Backend-Aufruf ausgeführt |
| SQL-Fallback | Vorhanden für MySQL/MariaDB und SQLite als Speicher des GBDB-Dateiformats |
| GDP/1 | Vorhanden für einen Hub mit Replikaten; standardmäßig deaktiviert |
| Framework-Updater | Vorhanden; benötigt überprüften Vertrauensanker und `sodium` |
| MuseumQR-Wrapper | Sechs Leseaufrufe implementiert |
| `mRoot`, `ShareSuiteAPI`, `EqrAPI` | Leere Platzhalterklassen |

## Interne Reifegradmatrix

`GBDB::sqlErsatzFeatureMatrix()` in `gbdb_enterprise_ops.trait.php` bewertet den weiteren SQL-Ersatz-Ausbau selbst. Die wesentlichen Angaben:

| Bereich | Status im Code | Einschränkung |
| --- | --- | --- |
| Einfaches GreenQL `PICK` | `stable` | Einziger hier als `production_ready = true` markierter Matrixbereich |
| Komplexe WHERE-Ausdrücke | `beta` | Parser vorhanden, weitere Absicherung erforderlich |
| Schemata und Constraints | `experimental` | Noch keine umfassend freigegebene harte Integritätsgarantie |
| Primär-/Unique-Indizes | `experimental` | Pflege und Durchsetzung weiter auszubauen |
| Abfrageplanung und Indexnutzung | `experimental` | Nicht für alle Abfragepfade vollständig verdrahtet |
| Joins | `experimental` | Keine vollständige SQL-Join-Engine |
| Transaktionen und Recovery | `experimental` | Bausteine vorhanden; keine allgemeine ACID-/Crash-Sicherheitszusage |
| `DatabaseBridge`, Public API | `beta` | Vorhanden, weitere Vereinheitlichung und Erweiterung vorgesehen |
| QueryBuilder-Komfort-API | `prepared` | Noch nicht final |
| SQL-Kompatibilität, Übersetzer, PDO-ähnlicher Adapter | `prepared` | Vorbereitete Schnittstellen, kein vollwertiger SQL-Ersatz |
| SQLite-Importadapter | `prepared` | Endgültiger Prüf-/Importablauf offen |
| MySQL-Dump-Import, systematische Benchmarks | `missing` | In der Matrix als fehlend markiert |
| Backup/Restore/PITR-Ausbau | `experimental` | Sicherungen vorhanden; vollständige zeitpunktbezogene Wiederherstellung nicht final |

Vorbereitende Methoden können Konfigurationen speichern und `ok = true` liefern, ohne die benannte externe Integration auszuführen. Rückgaben wie `prepared`, `production_ready` und erläuternde Felder immer auswerten. Das betrifft unter anderem SSO-/LDAP-/SAML-/OIDC-Vorbereitungen und SQL-Importadapter.

Die Matrix enthält außerdem eine historische Bewertung der Dokumentation. Sie wird durch diese reine Dokumentationsänderung im Laufzeitcode nicht überschrieben.

## Betriebliche Grenzen

- `page()`, `cursor()` und `streamRows()` laden derzeit die Tabelle vorab. Eine kleine Antwort ist kein Beleg für begrenzten internen Speicherverbrauch.
- GDP unterstützt einen Hub, keine automatische Leader-Wahl und keine konfliktauflösende Multi-Master-Zusammenführung. Im eingebetteten Modus gibt es ohne Anfragen keine Synchronisation.
- GDP-Snapshots übertragen Spalten und Daten, nicht die vollständigen typisierten Schemadefinitionen. Änderungen und Wiederherstellung auf die eigene Anwendung abstimmen.
- Update-Phasen verteilen Arbeit auf mehrere Anfragen. Einzelne Sicherungs-, Entpack- oder Migrationsschritte können trotzdem Hosting-Zeit- oder Speichergrenzen erreichen.
- Updater-Datensicherungen beziehen sich auf den physischen GBDB-Datenordner. Bei aktivem SQL-Fallback, GDP oder externen Nebenwirkungen keinen vollständigen verteilten Rollback voraussetzen.
- Die ZIP ist ein Quell-/Arbeitsstand mit vorhandenem lokalem Daten- und Laufzeitbestand, kein allein durch diese Dokumentationsänderung bereinigtes Veröffentlichungsrelease.

Aktuell ausgeführte Prüfungen und nicht geprüfte Umgebungen stehen im [Prüfbericht](../PHP/TESTBERICHT.md). Der separat beigelegte Release-Manager wird in seiner [Anleitung](19-release-manager.md) behandelt. Seine gepackte Kopie unterscheidet sich vom Quellordner; externe Produktserver und ein produktiver Veröffentlichungsserver wurden nicht geprüft.
