# GDP/1 – GBDB Distributed Protocol

Dokumentation geprüft am **03.11.2026** für den mitgelieferten Framework-Stand **10.0.1**. Kapitelnummern mit dem Zusatz „Konzept“ beziehen sich auf das ursprüngliche Architekturpapier; für die Benutzung ist es nicht erforderlich. Das Framework liegt in diesem Repository unter `PHP/`.

[Handbuch](../docs/README.md) · [GDP-Methodenreferenz](../docs/99-api-reference/gdp-methods.md) · [Prüfbericht](TESTBERICHT.md)

GDP ist ein **optionaler** Bestandteil von GBDB. Ohne `GDP → enabled = true` in `.config/.framework.env.php` verhält sich jede GBDB-Installation exakt wie bisher. Auch mit aktiviertem GDP werden nur ausdrücklich zur Replikation freigegebene Instanzen verwaltet.

> Leitentscheidung aus dem Konzept: GDP gehört ins GBDB-Framework, kennt aber keine Geschäftslogik. Anwendungen schreiben weiter über `GBDB::insertData()`, `editData()` und `deleteData()`.

## Inhalt

1. Schnellstart
2. Architektur im echten Code
3. Datenhaltung und Atomarität
4. Policies, Offline-Betrieb und Eigentümerwechsel
5. Protokoll, Endpunkte und Sicherheit
6. Snapshot-Bootstrap und Recovery
7. Betrieb: Embedded, Worker, Trigger, Docker
8. Öffentliche PHP-API und Fehlercodes
9. Oberfläche „Distributed (GDP)“
10. Tests
11. Architekturentscheidungen (Konzept Kapitel 34)
12. Abnahmestatus (Konzept Kapitel 35) und bekannte Grenzen

## 1. Schnellstart

**Hub (zentraler Knoten)** – in `.framework.env.php`:

```php
private const GDP = [
    "enabled" => true,
    "role" => "hub",
    "node_name" => "Zentrale",
    // übrige Werte wie ausgeliefert
];
```

Dann in der Oberfläche unter **Betrieb → Distributed (GDP)**:

1. *Replikate* → GBDB-Instanz zur Replikation freigeben (Daten bleiben unverändert).
2. *Policies* → Schreibautorität je Tabelle festlegen (Standard: `central_owner`).
3. *Knoten & Pairing* → Einmalcode erzeugen (15 Minuten gültig, nur einmal nutzbar).

**Replica (lokaler Knoten)**:

```php
private const GDP = [
    "enabled" => true,
    "role" => "replica",
    "node_name" => "Filiale Berlin",
    "sync_mode" => "embedded", // oder worker / manual
];
```

*Knoten & Pairing* → Hub-URL (`https://hub.example.com/gdp.php`) und Code eintragen → *Synchronisation* → „Jetzt synchronisieren“. Die Erstsynchronisation übernimmt einen Snapshot; danach werden Deltas übertragen. Die folgenden Konfigurationsblöcke zeigen nur die zu ändernden Einträge. Die übrigen Einträge des vorhandenen `GDP`-Arrays erhalten.

Per Code:

```php
GDP::enableInstanceReplication('ea-tickets', ['default_mode' => 'central_owner']);
$code = GDP::createPairing(['ea-tickets']);                          // Hub
GDP::pairWithHub('https://hub.example.com/gdp.php', $code['code']);  // Replica
GDP::sync();                                                          // Replica
```

Der Endpunkt `gdp.php` liegt neben `gbdb_ui.php` und muss für Replikate per HTTPS erreichbar sein. Er funktioniert ohne Rewrite-Regeln (`gdp.php?r=v1/sync/push`).

## 2. Architektur im echten Code

| Konzept (Kapitel 22) | Umsetzung in `gbdb_framework/Distributed/` |
| --- | --- |
| `GDP`, `GDPManager` | `GDP.php` – öffentliche API, Lebenszyklus, Embedded-Auslöser |
| `GDPConfig` | `GDPConfig.php` – Bereich `GDP` der zentralen Konfiguration, ENV-Overrides `GBDB_GDP_*` |
| Typen (Kapitel 23) | `GDPTypes.php` – Enums `GDPWriteMode`, `GDPNodeRole`, `GDPConnectionState`, `GDPSyncState`, `GDPStorageState`, `GDPOperationPreference`, `GDPErrorCode` |
| Fehlerklassen (Kapitel 25) | `GDPExceptions.php` – `GDPException` und die acht vorgeschlagenen Unterklassen plus `GDPProtocolException` |
| Storage-Adapter, Transaktion | `GDPStore.php` – Ablage über `gbdbdata://`, exklusiver Abschnitt über den GBDB-Speicher-Lock, `GDPVault` (Geheimnisse), `GDPNonceCache` |
| `GDPJournal`, `GDPJournalEntry`, `GDPCursorManager` | `GDPJournal.php` – Ereignis, segmentiertes Journal, Cursor, Duplikatindex `GDPAppliedIndex` |
| `GDPNodeManager`, `GDPPeerRegistry`, `GDPStateManager`, `GDPAuditLogger` | `GDPRegistry.php` |
| `GDPPolicyEngine` | `GDPPolicyEngine.php` – inkl. `GDPPolicyDecision`, Leases, Eigentümerwechsel |
| `GDPWriteInterceptor` | `GDPWriteInterceptor.php` – Schreibpfad, Intent, Recovery; `GDPApplier` – getrennter Apply-Pfad |
| `GDPAuthenticator`, `GDPAuthorization` | `GDPSecurity.php` (HMAC, Schlüssel, Pairing) und `GDPGrants` in `GDPRegistry.php` |
| `GDPTransport`, `GDPHttpTransport` | `GDPTransport.php` |
| Push/Pull-Handler | `GDPHub.php` – `GDPHubService` (Hub-Seite) und `GDPServer` (HTTP-Einstieg) |
| `GDPSyncEngine` | `GDPSyncEngine.php` – Replica-Seite, Pairing-Client, Schlüsselrotation, Housekeeping |
| `GDPSnapshotManager` | `GDPSnapshot.php` – mit `GDPSchema` (Schema-Fingerprint) |

Namensräume werden nicht verwendet, weil die gesamte vorhandene GBDB-Codebasis globale, präfixierte Klassen nutzt (`GBDBRuntime`, `GBDBSQLStore`). Der Aufruf `GDP::init()` aus dem Konzept funktioniert unverändert.

**Integration in die Engine** (Kapitel 24): Alle fachlichen Schreibwege der Engine laufen über `insertData`, `editData` und `deleteData` in `gbdb_crud.trait.php` – auch `insert`, `upsert`, `bulkInsert`, Partitionen, Shards, `optimisticEditData`, GDPR-Redaktion, Relations-Kaskaden und das Commit von `GBDB::begin()/commit()`. Genau dort sitzt der Interceptor. Strukturänderungen (`createTable`, `addColumn`, `deleteTable`, `deleteDatabase`, `deleteAll`, `restoreSnapshot`, `replaceTableContents`, `deleteInstance`) prüft ein Struktur-Guard. Für nicht replizierte Instanzen kostet die Prüfung einen gecachten Dateistatus.

## 3. Datenhaltung und Atomarität

Alle GDP-Daten liegen unter `<Datenbestand>/.gdp/` und werden ausschließlich über den bestehenden `gbdbdata://`-Speicher geschrieben:

```
.gdp/
  node.json            Knotenidentität (node_id, Rolle, Protokoll)
  state.json           Verbindung, Präferenz, Backoff, letzte Fehler, Metriken
  nodes.json           bekannte Gegenstellen
  leases.json          Schreib-Leases (Replica)
  security/grants.json Grants je Knoten und Instanz (Hub)
  audit/JJJJ-MM.log    Audit-Log
  instances/<id>/
    instance.json      Replikationszustand, Schema-Version, Blockade
    policies.json      Policy-Dokument (Version, Standardmodus, Regeln)
    sequence.json      höchste Quell-/Hubsequenz, Retention-Grenzen
    outbox/*.log       eigene Ereignisse nach Quellsequenz (Replica)
    hub/*.log          Hub-Journal nach Hubsequenz (Hub)
    cursors.json       Cursor je Gegenstelle
    applied/xx.json    Duplikatindex operation_id → payload_hash
    intent.json        offener Schreibvorgang (nur während eines Writes)
    snapshots/<id>/    Manifest und Chunks (Hub)
  staging/<id>/<snap>/ Snapshot-Download (Replica)
```

Journal und Cursor verwenden dieselben Speicherwege wie Nutzdaten: temporäre Dateien und `rename`, Synchronisierung auf den Datenträger soweit vom Laufzeitpfad unterstützt, installationsbezogene Sperren und bei eingerichtetem SQL-Fallback dessen Spiegelung. **Ein Wechsel auf SQL erzeugt keine zweite Journalhistorie** (Kapitel 21), weil das Journal Teil des gespiegelten Bestands ist. Ist `crypt_data` aktiv, werden Journalzeilen, Intents und Snapshot-Chunks wie Tabellen verschlüsselt.

**Atomare Commit-Grenze** (Kapitel 09). Ein normaler Dateilock garantiert keinen atomaren Multi-Datei-Commit. GDP nutzt deshalb ein Intent-Protokoll:

1. Policy prüfen (unter dem exklusiven Lock).
2. Intent mit dem Vorher-Zustand der betroffenen Datensätze (ID und Version) schreiben.
3. Fachänderung über den bisherigen Engine-Pfad.
4. Nachher-Zustand lesen und mit dem Vorher-Zustand vergleichen: daraus entstehen die Ereignisse („Journal = Realität“).
5. Ereignisse anhängen, Quellsequenz vergeben, Intent löschen.

Bricht PHP zwischen 2 und 5 ab, gleicht `GDPWriteInterceptor::recover()` beim nächsten Zugriff den Datenstand mit dem Intent ab. Dieses Wiederanlaufverfahren soll Datenänderung und Journal wieder zusammenführen; es ist keine pauschale Garantie für beliebige Datenträger-, Prozess- und Mehrknotenfehler. Den zugehörigen Testumfang beschreibt der Prüfbericht. Im SQL-Betrieb läuft zusätzlich jeder schreibende Runtime-Abschnitt in einer SQL-Transaktion, sodass Daten, Meta und Journal dort echt gemeinsam committen.

Mehrere zusammengehörige Änderungen (mehrzeilige Updates, Relations-Kaskaden, `GBDB::begin()/commit()`) erhalten eine gemeinsame `transaction_group_id`. Gruppen werden beim Lesen nie auf zwei Antworten verteilt und beim Empfänger unter einem exklusiven Lock angewendet. Schlägt ein Engine-Commit fehl und spielt die Engine ihre Snapshots zurück, journalisiert GDP nur die tatsächlich verbliebenen Nettoänderungen.

**Kanonische Serialisierung** (Kapitel 09/15): Objektschlüssel binär sortiert, Listen in Reihenfolge, UTF-8 ohne Escaping, `JSON_PRESERVE_ZERO_FRACTION`, `serialize_precision = -1`. Der `payload_hash` (`sha256:…`) umfasst alle fachlich festlegenden Felder, nicht aber Hubsequenz und Annahmezeit. Operations-IDs sind UUIDv7.

## 4. Policies, Offline-Betrieb und Eigentümerwechsel

| Modus | Lokal schreiben | Offline | Hub-Prüfung |
| --- | --- | --- | --- |
| `central_owner` | nur Hub | gesperrt | Replikat-Operationen werden abgewiesen |
| `node_owner` | nur der Eigentümer (`owner_node_id`) oder je Datensatz (`owner_column` = node_id) | nur mit `offline_permission` bzw. gültiger Lease | Eigentümer und `authority_epoch` |
| `append_only` | nur INSERT, optional nur `writers` | mit `offline_permission` (`offline_create`) | nur INSERT, Schreiberliste |
| `local_only` | jeder | ja | wird nie journalisiert oder übertragen |

Auflösung: Tabelle vor Datenbank (`*`) vor Instanz (`*`/`*`) vor Standardmodus (`central_owner`). Interne Bases (`_gbdb_*`, `system`) sind immer `local_only`. Die Prüfung läuft im Schreibpfad; `GDP::canWrite()` dient nur der Anzeige. Verletzungen werfen `GDPPolicyViolationException` – bewusst nur in GDP-verwalteten Instanzen, damit bestehende Anwendungen unverändert bleiben.

**Leases** (Kapitel 13/20): Mit `lease_seconds > 0` darf ein Eigentümer nur schreiben, solange die zuletzt beim Handshake erhaltene Lease gilt. Der Hub überträgt Laufzeiten, keine Zeitpunkte; das Replikat rechnet ab dem Beginn seiner Anfrage (Uhrzeitdrift). Damit kann ein isolierter Knoten nach einem Widerruf höchstens bis zum Lease-Ablauf weiterschreiben.

**Eigentümerwechsel mit Fencing**: `GDP::transferOwnership()` erhöht die Epoche und sperrt den Bereich für alle. Der alte Eigentümer überträgt seine offenen Operationen (die alte Epoche wird für ihn bis zum Abschluss angenommen) und meldet die neue Policy-Version; dann übernimmt der neue Eigentümer. Ist der alte Knoten dauerhaft weg, schließt `GDP::forceCompleteTransfers()` nach Ablauf seiner Lease ab; spätere Operationen mit alter Epoche werden abgewiesen und blockieren dessen Strom.

Die Betriebspräferenz (`AUTO`, `FORCE_LOCAL`, `PREFER_CENTRAL`) beeinflusst nur die Kommunikation und erteilt **keine** Schreibrechte. `FORCE_LOCAL` gilt für Schreibregeln wie offline.

## 5. Protokoll, Endpunkte und Sicherheit

`POST gdp.php?r=v1/<endpunkt>` (oder `gdp.php/v1/<endpunkt>`), JSON mit `"protocol": "GDP/1"`:

| Endpunkt | Zweck |
| --- | --- |
| `pair/start`, `pair/complete` | Pairing mit Einmalcode |
| `handshake` | Grants, Schema, Policies, Leases, Checkpoints, Retention-Grenze |
| `sync/push` | Ursprungsoperationen lückenlos annehmen (dauerhaftes ACK) |
| `sync/pull` | Hub-Ereignisse ab Cursor, begrenzt nach Anzahl und Bytes |
| `sync/ack` | angewendeten Hub-Cursor bestätigen |
| `snapshot/manifest`, `snapshot/chunk` | Bootstrap |
| `credential/rotate`, `credential/confirm` | Schlüsselrotation |
| `health` (GET) | Protokoll und Rolle, keine Geheimnisse |
| `trigger` | optionaler externer Sync-Auslöser (`Authorization: Bearer <trigger_token>`) |

**Authentifizierung** (Kapitel 19): `GDP1-HMAC-SHA256` über Methode, kanonischen Pfad, SHA-256 des Bodys, `node_id`, Schlüssel-ID, Zeitstempel, Nonce und Protokollversion; je Knoten eigene 256-Bit-Geheimnisse. Der Server prüft Zeitfenster (`clock_skew_seconds`), Replay (Nonce-Cache, erst nach gültiger Signatur), Signatur mit `hash_equals`. **Antworten** des Hubs sind ebenfalls signiert und an die Nonce der Anfrage gebunden; ein Replikat verarbeitet nur echte Hub-Antworten.

**Pairing**: Der Code `GDP1-<pairing_id>-<geheimnis>` verlässt das Replikat nie. Beide Seiten leiten daraus per HKDF einen Schlüssel ab; damit sind die Pairing-Anfrage und die Hub-Antwort gegenseitig authentifiziert und der neue Knotenschlüssel mit AES-256-GCM übergeben. `pair/complete` beweist den Besitz des neuen Schlüssels über eine Challenge und verbraucht den Code. Optional ist danach eine Admin-Freigabe nötig. Ein noch nicht aktivierter Schlüssel ist nur für `pair/complete` und `credential/confirm` gültig.

**Autorisierung** (Kapitel 20): Grants je Knoten und Instanz mit Rechten `pull`, `push`, `snapshot`, `status` und optionalem Tabellenbereich. Gesperrte Knoten, widerrufene Grants und ausstehende Freigaben werden abgewiesen und auditiert.

**Geheimnisse** liegen nicht im Datenbestand, sondern in `.config/.runtime/gdp-vault.php` (exit-Guard, `.htaccess`, verschlüsselt mit dem Installationsschlüssel, Rechte 0600). Logs und Audit-Einträge enthalten nie Codes, Schlüssel oder Tokens.

**Transport**: TLS-Zertifikatsprüfung ist immer aktiv. Reines HTTP nur mit `allow_insecure_http = true` (lokale Tests); die Oberfläche warnt dann sichtbar. Weitere Grenzen: `max_request_bytes`, Rate-Limits je IP (allgemein 1200/min, Pairing 10/15 min), JSON-Tiefe 64, höchstens 1000 Operationen je Push.

## 6. Snapshot-Bootstrap und Recovery

Der Hub schreibt unter dem exklusiven Lock ein Abbild aller replizierten Tabellen bei Hubsequenz S (Kapitel 17), in Chunks mit SHA-256 und Manifest-Hash. Das Replikat lädt fehlende Chunks in `staging`, prüft jede Prüfsumme, aktiviert dann alle Tabellen über `replaceTableContents()` und holt danach alle Ereignisse ab S + 1. Ein abgebrochener Download wird mit derselben Snapshot-ID fortgesetzt; eine abgebrochene Aktivierung wird aus dem Staging wiederholt. Eine neue Initialisierung fordert einen passenden Snapshot an.

**Bestandsdaten**: Enthält eine replizierte Tabelle lokal bereits Daten oder liegen noch nicht übertragene Operationen vor, bricht der Bootstrap mit `confirmation_required` ab. Erst mit ausdrücklicher Freigabe (`GDP::bootstrapReplica($id, true)` bzw. „Lokalen Stand ersetzen“) wird ersetzt – vorher entsteht automatisch eine Vollsicherung. Nach dem Ersetzen meldet das Replikat dem Hub, dass es die nie übertragenen eigenen Sequenzen überspringt (`source_reset`, nur vorwärts, nur für den eigenen Strom).

**Recovery-Fälle** (Kapitel 18):

| Situation | Verhalten |
| --- | --- |
| Verbindungsabbruch | lokale Arbeit nach Policy weiter, Journal dauerhaft, exponentielles Backoff mit Jitter (max. 5 min) |
| Antwort nach dauerhaftem Apply verloren | Wiederholung wird über `applied`-Index oder Zustandsvergleich erkannt, ACK ohne zweite Ausführung |
| Absturz während eines lokalen Writes | Intent-Recovery beim nächsten Zugriff |
| Absturz nach Empfang, vor ACK | Cursor nicht fortgeschrieben; erneute Lieferung ist idempotent |
| Absturz während Snapshot | Staging isoliert, Aktivierung wird wiederholt |
| gleiche `operation_id`, anderer Hash | `DUPLICATE_PAYLOAD_MISMATCH`, Strom blockiert |
| Lücke in der Quellsequenz | `SEQUENCE_GAP`, Cursor springt nicht |
| Versionskonflikt (Lost Update) | `VERSION_CONFLICT`, Blockade statt Überschreiben |
| Schema weicht ab | `SCHEMA_MISMATCH`, Blockade bis Re-Snapshot |
| Hub aus Backup zurückgesetzt | `CHECKPOINT_MISMATCH` |
| Replikat länger offline als Retention | `RESYNC`, automatischer Re-Snapshot |

Blockaden lösen Administratoren in der Oberfläche („Erneut versuchen“, „Neu initialisieren“, „Lokalen Stand ersetzen“, Hub: Strom freigeben). Es gibt kein stilles „last write wins“.

**Schemaänderungen** an replizierten Tabellen laufen auf dem Hub über `GDP::schemaMaintenance($id, fn() => GBDB::addColumn(...))`. Die Schema-Version steigt, Replikate blockieren mit `SCHEMA_MISMATCH` und werden neu initialisiert.

## 7. Betrieb: Embedded, Worker, Trigger, Docker

- **Embedded** (`sync_mode = embedded`, Standard): Am Ende normaler Webanfragen läuft höchstens alle `embedded_interval` Sekunden ein Zyklus mit `embedded_budget_seconds` Zeitbudget; ein Prozess zur Zeit. Kein Cron, keine Shell nötig. **Grenze:** Ohne eingehende Anfragen findet keine Synchronisation statt; GDP verspricht keine Echtzeit.
- **Worker** (`sync_mode = worker`): `php gbdb_framework/Distributed/bin/gdp_worker.php` (dauerhaft, `--interval=5`) oder `--once` aus einem Timer. Gleicher Synchronisationskern.
- **Trigger**: `trigger_token` (≥ 32 Zeichen) setzen und `POST gdp.php?r=v1/trigger` mit `Authorization: Bearer <token>` aufrufen, z. B. von einem externen Monitoring.
- **Manuell**: Oberfläche oder `GDP::sync()`.
- **Docker**: Webserver-Container mit `sync_mode = worker` plus zweiter Prozess `php …/gdp_worker.php`; die Daten- und `.config/.runtime`-Verzeichnisse als Volumes behalten.
- **Housekeeping**: Hub-Journalsegmente werden erst entfernt, wenn alle aktiven Replikate sie bestätigt haben (oder ein Replikat länger als `retention_days` nichts bestätigt hat – es wird dann neu initialisiert). Replikate entfernen bestätigte Ausgangssegmente.

## 8. Öffentliche PHP-API und Fehlercodes

Die öffentliche Fassade heißt `GDP`. Für genaue Parameter und Rückgabetypen die [aus dem Code erzeugte Referenz](../docs/99-api-reference/gdp-methods.md) verwenden.

| Aufgabe | Methoden |
| --- | --- |
| Zustand und Diagnose | `init()`, `getStatus()`, `getHealth()`, `isOnline()`, `isSynchronized()`, `getDiagnostics()` |
| Synchronisation und Schreibprüfung | `sync()`, `canWrite()`, `explainWrite()`, `getPendingOperations()` |
| Replikationsfreigabe und Regeln | `enableInstanceReplication()`, `disableInstanceReplication()`, `setPolicy()`, `getPolicy()`, `validatePolicy()`, `removePolicy()` |
| Journal und Checkpoints | `journal()`, `verifyJournal()`, `getCheckpoints()` |
| Snapshot und Wiederanlauf | `createSnapshot()`, `bootstrapReplica()`, `resolveBlock()`, `unblockPeerStream()` |
| Pairing und Knoten | `createPairing()`, `pairWithHub()`, `approveNode()`, `unregisterNode()`, `rotateCredential()` |
| Eigentümer und Schema | `transferOwnership()`, `forceCompleteTransfers()`, `schemaMaintenance()` |
| Betriebspräferenz | `forceLocalMode()`, `preferCentralMode()`, `returnToAutoMode()` |

Beispiel einer Schemaänderung auf dem Hub, mit vorhandener replizierter Instanz und Tabelle:

```php
GDP::schemaMaintenance('ea-tickets', static function () :bool {
    return GBDB::addColumn('tickets', 'vorgaenge', 'prioritaet', 'normal');
});
```

Vorhandene Bestände und noch nicht übertragene Operationen vor einer ersetzenden Neuinitialisierung prüfen. `resolveBlock($instanceId, 'retry')` und `resolveBlock($instanceId, 'resnapshot')` sind unterschiedliche administrative Entscheidungen, keine zwei gleichzeitig zu übergebenden Werte.

Jeder Fehler ist eine `GDPException` mit `errorCode()` (Enum `GDPErrorCode`), `retryClass()` (`retry`, `check`, `manual`) und `context()` ohne Geheimnisse: `CONNECTION_UNAVAILABLE`, `AUTHENTICATION_FAILED`, `AUTHORIZATION_DENIED`, `PAIRING_EXPIRED`, `POLICY_VIOLATION`, `LEASE_EXPIRED`, `SCHEMA_MISMATCH`, `SEQUENCE_GAP`, `VERSION_CONFLICT`, `DUPLICATE_PAYLOAD_MISMATCH`, `CHECKPOINT_MISMATCH`, `SNAPSHOT_REQUIRED`, `SNAPSHOT_CHECKSUM_FAILED`, `STORAGE_TRANSACTION_FAILED`, `STORAGE_STATE_UNCERTAIN`, `INTEGRITY_BLOCKED`, `RATE_LIMITED`, `PROTOCOL_ERROR`, `PAYLOAD_TOO_LARGE`, `NOT_CONFIGURED`.

## 9. Oberfläche „Distributed (GDP)“

Unter **Betrieb → Distributed (GDP)** (nur Admins): Übersicht (Knoten, Verbindung, Speicher, ausstehende Operationen, Instanztabelle mit Checkpoints und Verzug, letzte Fehler), Knoten & Pairing, Replikate, Policies, Synchronisation (manueller Sync, Präferenz, Recovery-Aktionen mit Erklärung), Journal, Snapshots, Sicherheit (Audit-Log, Grants) und Diagnose (inkl. Verbindungstest und Journal-Integritätsprüfung). Folgenreiche Aktionen fragen mit Begründung nach. „Synchron“ erscheint nur nach Cursor-Abgleich. Die Glocke in der Kopfzeile zeigt blockierte Instanzen und eine fehlende Hub-Verbindung.

## 10. Tests

`php PHP/tests/gdp_test.php` im Repository-Stamm startet zwei getrennte Installationen (Hub und Replica) mit eigenen PHP-Webservern in einem temporären Ordner und prüft mit echten, signierten HTTP-Anfragen die mitgelieferten Szenarien. Aktuelles Ergebnis, Laufzeitumgebung und Grenzen: [Prüfbericht](TESTBERICHT.md). Für den Größenlimit-Test muss das PHP-POST-Limit oberhalb der erzeugten 9-MiB-Anfrage liegen, damit der Anwendungstest die GDP-Grenze selbst erreicht.

## 11. Architekturentscheidungen (Konzept Kapitel 34)

| Frage | Entscheidung |
| --- | --- |
| PHP-Version, Klassenstruktur | PHP 8.1+, globale präfixierte Klassen wie im übrigen Framework, Enums für Zustände |
| Atomare Garantien des nativen Storage | atomares Ersetzen einzelner Dateien; Multi-Datei-Konsistenz über das Intent-Protokoll, nicht über Dateilocks |
| SQL-Failover und Journal | Journal liegt im gespiegelten Bestand; im SQL-Betrieb zusätzlich eine SQL-Transaktion je schreibendem Abschnitt |
| Physische Ablage | `.gdp/` im GBDB-Datenbestand, Geheimnisse im Runtime-Tresor |
| Kanonisches Format und Hash | siehe Kapitel 3 dieses Dokuments |
| Transaktionsgruppen | Gruppe = Engine-Transaktion, Mehrzeilen-Update oder Kaskade; nie über Antworten verteilt |
| Datensatz-Policies | `owner_column` mit node_id je Datensatz |
| Leases und Zeitdrift | relative Laufzeiten, lokale Prüfung; Uhrzeitdrift der Signaturen standardmäßig 300 s |
| HMAC vs. Signaturen | HMAC-SHA256 je Knoten (überall verfügbar), plus signierte Antworten; AES-256-GCM nur für die Schlüsselübergabe |
| Rotation und Restore | Rotation über Hub-Endpunkte; nach einem Restore der Replica: Re-Pairing bzw. Re-Snapshot |
| Snapshot-Konsistenz | Erstellung unter dem exklusiven Speicher-Lock |
| Abweichende Bestandsdaten | nur mit ausdrücklicher Freigabe ersetzt, vorher Vollsicherung |
| GDPR-Löschung | Löschungen werden als Tombstone repliziert; Journal-Payloads verschwinden mit der Retention. Für sofortige Löschung aus dem Journal: Retention verkürzen und Replikate neu initialisieren |
| Hosting-Mindestanforderungen | PHP 8.1, OpenSSL, cURL oder `allow_url_fopen`, beschreibbarer Daten- und Runtime-Ordner; kein Cron, keine Shell |
| UI-Integration | eigener Bereich in der GBDB-UI im Intranet-Design, Admin-Recht |
| Trennung Dev/Test/Prod | je Umgebung eigene Installation mit eigener node_id; Pairing-Codes sind instanz- und knotengebunden |

## 12. Historischer Abnahmestatus und bekannte Grenzen

Die folgende Tabelle übernimmt den Abnahmestand des ursprünglichen Berichts vom 26.10.2026. „Getestet“ und die damaligen Testzahlen beziehen sich auf diesen historischen Lauf. Aktuelle Prüfergebnisse stehen getrennt im [Prüfbericht](TESTBERICHT.md).

| Kriterium | Status |
| --- | --- |
| 1 Bestehende App ohne GDP unverändert | erfüllt; getestet |
| 2 Zwei Instanzen auf getrennten Knoten koppeln | erfüllt; getestet |
| 3 Snapshot und Delta | erfüllt; getestet |
| 4 Automatische Journalisierung | erfüllt; getestet |
| 5 Push/Pull/ACK crashfest und idempotent | erfüllt; Duplikate, Hash-Manipulation, Lücken, Replay und Abbruch vor dem Journal getestet |
| 6 Getrennte Zustände | erfüllt (Verbindung, Sync, Storage) |
| 7/8 Offline-Policies im Backend, zentrale Writes offline gesperrt | erfüllt; getestet |
| 9 Offline-Operationen gehen nicht verloren | erfüllt; getestet (Hub-Ausfall) |
| 10 SQL-Failover ohne doppelte/verlorene Journalhistorie | gemeinsamer Speicherweg vorgesehen (Journal im gespiegelten Bestand); im damaligen Lauf mangels PDO-Treiber **nicht ausgeführt** |
| 11 Pairing, Rotation, Grants | erfüllt; getestet |
| 12 Nicht autorisierte Knoten erhalten keine Daten | erfüllt; getestet |
| 13 Inkonsistenzen werden erkannt und blockiert | erfüllt; getestet |
| 14 Diagnose und Wiederherstellung dokumentiert | dieses Dokument und die Oberfläche |
| 15/16 Embedded ohne Shell, Worker mit gleichem Kern | umgesetzt; der Worker wurde nicht als Dauerprozess getestet |
| 17 Testsuite | 80 Prüfungen bestanden; Lastszenario 20 (1000 parallele Requests) und ein echter Prozessabbruch mitten im Schreiben (statt simuliertem Intent) wurden nicht ausgeführt |
| 18 Upgrade/Backup/Rollback | GDP startet deaktiviert. Zum Pausieren `enabled = false` setzen; Journal, Identität und Tresor erhalten. Ein Rollback oder Entkoppeln braucht einen geplanten Datenabgleich und eine Sicherung, kein pauschales Löschen von `.gdp/` |
| 19 API dokumentiert und versioniert | Protokoll `GDP/1`, Kapitel 8 |
| 20 Kein Schreibpfad ungetestet umgangen | alle Engine-Schreibwege laufen über den Interceptor; direkte Dateimanipulation außerhalb der API erkennt GDP nur, soweit sie zu Versionskonflikten oder Hash-Fehlern führt |

Weitere bewusste Grenzen von GDP/1: genau ein Hub, keine automatische Leader-Wahl, keine Multi-Master-Zusammenführung, keine Echtzeitgarantie im Embedded-Modus. Typisierte Schemadefinitionen (Spaltentypen, Constraints) werden beim Snapshot nicht übertragen, nur Spalten und Daten; die Typprüfung bleibt Aufgabe des schreibenden Knotens. Das Snapshot-Erstellen lädt jeweils eine Tabelle vollständig in den Speicher (wie die übrige Engine).
