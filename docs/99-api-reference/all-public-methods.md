# Öffentliche PHP-Deklarationen – Gesamtindex

Dieser Index und die Teilreferenzen werden aus dem aktuellen Quellcode erzeugt. Erfasst sind öffentliche Methoden einschließlich Rückgabetypen, deklarierte Enum-Fälle und globale Funktionen. Private und geschützte Methoden sowie Laufzeitdaten werden ausgelassen. Geerbte Methoden erscheinen an ihrer Deklarationsstelle.

Neuerzeugung: `php docs/tools/generate-api-reference.php` im Repository-Stamm. Prüfung ohne Schreiben: `php docs/tools/generate-api-reference.php --check`.

Die Tabellen sind ein Signatur- und Quellennachweis, keine Zusicherung von Seiteneffektfreiheit, Reifegrad oder freier Aufrufbarkeit interner Bausteine. Verhalten und Beispiele stehen im [Handbuch](../README.md) und in der [Statusübersicht](../18-feature-status.md).

| Bereich | Klassen / Gruppen | Methoden / Funktionen |
| --- | --- | --- |
| [GBDB – Fassade, Engine und Traits](gbdb-engine-methods.md) | 20 | 1087 |
| [GreenQL – öffentliche Deklarationen](greenql-methods.md) | 7 | 24 |
| [Konfiguration, Auth und Hilfsklassen](core-helper-methods.md) | 25 | 295 |
| [Public API – Dispatcher und Module](public-api-methods.md) | 4 | 16 |
| [SecondServer – Client, Backend und Aufträge](srv-methods.md) | 7 | 61 |
| [Interne Speicherlaufzeit](runtime-methods.md) | 8 | 70 |
| [GDP – API, Protokoll und Zustände](gdp-methods.md) | 48 | 247 |
| [Framework-Updater – öffentliche Deklarationen](updater-methods.md) | 14 | 103 |
| [Verwaltungsoberfläche – Helfer und Funktionen](ui-methods.md) | 15 | 181 |
| [Produkt-Plugins](plugin-methods.md) | 4 | 6 |

## Namen nach Bereich

### GBDB – Fassade, Engine und Traits

`GBDB`, `GBDBEngine`, `GBDBInstanceAccessDenied`, `GBDB_AdminOpsTrait`, `GBDB_AdvancedTrait`, `GBDB_ClusterBackupTrait`, `GBDB_CrudTrait`, `GBDB_EnterpriseOpsTrait`, `GBDB_IndexTrait`, `GBDB_InstanceSchemaTrait`, `GBDB_JobsSecurityMediaSocialTrait`, `GBDB_LockingTrait`, `GBDB_MVCCTrait`, `GBDB_MaintenanceTrait`, `GBDB_PublicV103Trait`, `GBDB_QueryTrait`, `GBDB_RecoveryTrait`, `GBDB_RelationsIdsStreamingPartitionTrait`, `GBDB_StorageTrait`, `GBDB_TransactionTrait`.

### GreenQL – öffentliche Deklarationen

`GreenQL`, `GreenQL_BaseTrait`, `GreenQL_ExecutionTrait`, `GreenQL_IoTrait`, `GreenQL_ParserTrait`, `GreenQL_RowsTrait`, `GreenQL_RuntimeTrait`.

### Konfiguration, Auth und Hilfsklassen

`Auth`, `Cache`, `Converter`, `Cookie`, `Crypt`, `DatabaseBridge`, `FileTool`, `Format`, `GBDBAuthEngine`, `GBDBQueryBuilder`, `GBDBStorage`, `GetForm`, `Globale Funktionen`, `Globale Funktionen`, `Http`, `Json`, `ReCaptcha`, `Ref`, `Route`, `SQL`, `Session`, `Time`, `Tools`, `Validate`, `Vars`.

### Public API – Dispatcher und Module

`PublicAPI`, `PublicAPI_GBDB`, `PublicAPI_ModuleHelper`, `PublicAPI_Test`.

### SecondServer – Client, Backend und Aufträge

`SRVJob`, `SRVJob_Scripts`, `SRVJob_Test`, `SecondServer`, `Srv`, `SrvAuth`, `SrvFunctions`.

### Interne Speicherlaufzeit

`GBDBConfig`, `GBDBDataStream`, `GBDBFilesystem`, `GBDBPrimaryFailure`, `GBDBRateLimiter`, `GBDBRuntime`, `GBDBSQLStore`, `GBDBWriteRequired`.

### GDP – API, Protokoll und Zustände

`GDP`, `GDPAppliedIndex`, `GDPApplier`, `GDPAuditLogger`, `GDPAuthenticator`, `GDPAuthorizationException`, `GDPCanonical`, `GDPCheckpointMismatchException`, `GDPConfig`, `GDPConnectionException`, `GDPConnectionState`, `GDPCredentials`, `GDPCursorManager`, `GDPErrorCode`, `GDPException`, `GDPGrants`, `GDPHttpTransport`, `GDPHubService`, `GDPInstanceRegistry`, `GDPIntegrityException`, `GDPJournal`, `GDPJournalEntry`, `GDPNodeIdentity`, `GDPNodeRole`, `GDPNonceCache`, `GDPOperationPreference`, `GDPPairing`, `GDPPeerRegistry`, `GDPPolicyDecision`, `GDPPolicyEngine`, `GDPPolicyViolationException`, `GDPProtocolException`, `GDPRetryClass`, `GDPSchema`, `GDPSchemaMismatchException`, `GDPServer`, `GDPSnapshotException`, `GDPSnapshotManager`, `GDPStateManager`, `GDPStorageState`, `GDPStorageUnavailableException`, `GDPStore`, `GDPSyncEngine`, `GDPSyncState`, `GDPTransport`, `GDPVault`, `GDPWriteInterceptor`, `GDPWriteMode`.

### Framework-Updater – öffentliche Deklarationen

`GBDBFrameworkUpdater`, `GBDBUpdateConfigMerger`, `GBDBUpdateGate`, `GBDBUpdateHttp`, `GBDBUpdateInstaller`, `GBDBUpdateRollback`, `GBDBUpdateSettings`, `GBDBUpdateSignature`, `GBDBUpdateState`, `GBDBUpdateTrust`, `GBDBUpdateTrustException`, `GBDBUpdateVersion`, `GBDBUpdateZip`, `GBDBUpdateZipWriter`.

### Verwaltungsoberfläche – Helfer und Funktionen

`Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `Globale Funktionen`, `GreenQLUIv2Helper`.

### Produkt-Plugins

`EqrAPI`, `MqrApi`, `ShareSuiteAPI`, `mRoot`.

