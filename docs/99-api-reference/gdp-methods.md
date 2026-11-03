# GDP – API, Protokoll und Zustände

GDP ist die öffentliche Fassade. Die übrigen Klassen und Enums bilden Protokoll, Speicher, Policies und Synchronisation ab. Transportzugriffe müssen die vorgesehenen Authentifizierungs- und Zustandsprüfungen durchlaufen.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## GDP

Klasse · Quelle: [Distributed/GDP.php](../../PHP/gbdb_framework/Distributed/GDP.php#L13)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `enabled()` | `public static function enabled() :bool` | [Zeile 16](../../PHP/gbdb_framework/Distributed/GDP.php#L16) |
| `init()` | `public static function init() :array` | [Zeile 47](../../PHP/gbdb_framework/Distributed/GDP.php#L47) |
| `getStatus()` | `public static function getStatus() :array` | [Zeile 61](../../PHP/gbdb_framework/Distributed/GDP.php#L61) |
| `isOnline()` | `public static function isOnline() :bool` | [Zeile 144](../../PHP/gbdb_framework/Distributed/GDP.php#L144) |
| `isSynchronized()` | `public static function isSynchronized(string $instanceId) :bool` | [Zeile 152](../../PHP/gbdb_framework/Distributed/GDP.php#L152) |
| `sync()` | `public static function sync(?string $instanceId = null, int $budgetSeconds = 30) :array` | [Zeile 158](../../PHP/gbdb_framework/Distributed/GDP.php#L158) |
| `canWrite()` | `public static function canWrite(string $instanceId, string $database, string $table) :bool` | [Zeile 164](../../PHP/gbdb_framework/Distributed/GDP.php#L164) |
| `explainWrite()` | `public static function explainWrite(string $instanceId, string $database, string $table) :array` | [Zeile 168](../../PHP/gbdb_framework/Distributed/GDP.php#L168) |
| `getNodes()` | `public static function getNodes() :array` | [Zeile 176](../../PHP/gbdb_framework/Distributed/GDP.php#L176) |
| `getReplicas()` | `public static function getReplicas(string $instanceId) :array` | [Zeile 189](../../PHP/gbdb_framework/Distributed/GDP.php#L189) |
| `enableInstanceReplication()` | `public static function enableInstanceReplication(string $instanceId, array $options = []) :array` | [Zeile 219](../../PHP/gbdb_framework/Distributed/GDP.php#L219) |
| `disableInstanceReplication()` | `public static function disableInstanceReplication(string $instanceId) :void` | [Zeile 250](../../PHP/gbdb_framework/Distributed/GDP.php#L250) |
| `setPolicy()` | `public static function setPolicy(string $instanceId, string $database, string $table, array $options) :array` | [Zeile 264](../../PHP/gbdb_framework/Distributed/GDP.php#L264) |
| `removePolicy()` | `public static function removePolicy(string $instanceId, string $database, string $table) :void` | [Zeile 274](../../PHP/gbdb_framework/Distributed/GDP.php#L274) |
| `setDefaultPolicyMode()` | `public static function setDefaultPolicyMode(string $instanceId, GDPWriteMode $mode) :void` | [Zeile 279](../../PHP/gbdb_framework/Distributed/GDP.php#L279) |
| `getPolicy()` | `public static function getPolicy(string $instanceId, string $database, string $table) :array` | [Zeile 284](../../PHP/gbdb_framework/Distributed/GDP.php#L284) |
| `getPolicies()` | `public static function getPolicies(string $instanceId) :array` | [Zeile 288](../../PHP/gbdb_framework/Distributed/GDP.php#L288) |
| `validatePolicy()` | `public static function validatePolicy(string $instanceId, string $database, string $table, array $options) :array` | [Zeile 295](../../PHP/gbdb_framework/Distributed/GDP.php#L295) |
| `transferOwnership()` | `public static function transferOwnership(string $instanceId, string $database, string $table, string $newOwner) :array` | [Zeile 299](../../PHP/gbdb_framework/Distributed/GDP.php#L299) |
| `forceCompleteTransfers()` | `public static function forceCompleteTransfers(string $instanceId) :int` | [Zeile 308](../../PHP/gbdb_framework/Distributed/GDP.php#L308) |
| `getPendingOperations()` | `public static function getPendingOperations(string $instanceId, int $limit = 100) :array` | [Zeile 314](../../PHP/gbdb_framework/Distributed/GDP.php#L314) |
| `getCheckpoints()` | `public static function getCheckpoints(string $instanceId) :array` | [Zeile 323](../../PHP/gbdb_framework/Distributed/GDP.php#L323) |
| `journal()` | `public static function journal(string $instanceId, string $log, int $from, int $limit = 50, string $table = '') :array` | [Zeile 330](../../PHP/gbdb_framework/Distributed/GDP.php#L330) |
| `createSnapshot()` | `public static function createSnapshot(string $instanceId) :array` | [Zeile 341](../../PHP/gbdb_framework/Distributed/GDP.php#L341) |
| `bootstrapReplica()` | `public static function bootstrapReplica(string $instanceId, bool $replaceLocal = false, int $budgetSeconds = 120) :array` | [Zeile 351](../../PHP/gbdb_framework/Distributed/GDP.php#L351) |
| `resolveBlock()` | `public static function resolveBlock(string $instanceId, string $action) :void` | [Zeile 365](../../PHP/gbdb_framework/Distributed/GDP.php#L365) |
| `unblockPeerStream()` | `public static function unblockPeerStream(string $instanceId, string $nodeId) :void` | [Zeile 382](../../PHP/gbdb_framework/Distributed/GDP.php#L382) |
| `schemaMaintenance()` | `public static function schemaMaintenance(string $instanceId, callable $change) :mixed` | [Zeile 394](../../PHP/gbdb_framework/Distributed/GDP.php#L394) |
| `createPairing()` | `public static function createPairing(array $instances, string $role = 'replica', array $permissions = GDPGrants::PERMISSIONS, bool $requireApproval = false, ?array $scope = null) :array` | [Zeile 407](../../PHP/gbdb_framework/Distributed/GDP.php#L407) |
| `pairingSessions()` | `public static function pairingSessions() :array` | [Zeile 413](../../PHP/gbdb_framework/Distributed/GDP.php#L413) |
| `revokePairing()` | `public static function revokePairing(string $pairingId) :void` | [Zeile 417](../../PHP/gbdb_framework/Distributed/GDP.php#L417) |
| `pairWithHub()` | `public static function pairWithHub(string $hubUrl, string $code, string $displayName = '') :array` | [Zeile 422](../../PHP/gbdb_framework/Distributed/GDP.php#L422) |
| `approveNode()` | `public static function approveNode(string $nodeId) :void` | [Zeile 428](../../PHP/gbdb_framework/Distributed/GDP.php#L428) |
| `unregisterNode()` | `public static function unregisterNode(string $nodeId) :void` | [Zeile 443](../../PHP/gbdb_framework/Distributed/GDP.php#L443) |
| `regenerateIdentity()` | `public static function regenerateIdentity() :array` | [Zeile 456](../../PHP/gbdb_framework/Distributed/GDP.php#L456) |
| `rotateCredential()` | `public static function rotateCredential() :array` | [Zeile 460](../../PHP/gbdb_framework/Distributed/GDP.php#L460) |
| `forceLocalMode()` | `public static function forceLocalMode() :void` | [Zeile 466](../../PHP/gbdb_framework/Distributed/GDP.php#L466) |
| `preferCentralMode()` | `public static function preferCentralMode() :void` | [Zeile 470](../../PHP/gbdb_framework/Distributed/GDP.php#L470) |
| `returnToAutoMode()` | `public static function returnToAutoMode() :void` | [Zeile 474](../../PHP/gbdb_framework/Distributed/GDP.php#L474) |
| `auditLog()` | `public static function auditLog(int $limit = 100) :array` | [Zeile 478](../../PHP/gbdb_framework/Distributed/GDP.php#L478) |
| `getHealth()` | `public static function getHealth() :array` | [Zeile 485](../../PHP/gbdb_framework/Distributed/GDP.php#L485) |
| `testConnection()` | `public static function testConnection(string $hubEndpoint = '') :array` | [Zeile 513](../../PHP/gbdb_framework/Distributed/GDP.php#L513) |
| `getDiagnostics()` | `public static function getDiagnostics(bool $network = false) :array` | [Zeile 582](../../PHP/gbdb_framework/Distributed/GDP.php#L582) |
| `verifyJournal()` | `public static function verifyJournal(string $instanceId) :array` | [Zeile 641](../../PHP/gbdb_framework/Distributed/GDP.php#L641) |
| `runBackgroundCycle()` | `public static function runBackgroundCycle(int $budgetSeconds) :array` | [Zeile 664](../../PHP/gbdb_framework/Distributed/GDP.php#L664) |
| `bootEmbedded()` | `public static function bootEmbedded() :void` | [Zeile 698](../../PHP/gbdb_framework/Distributed/GDP.php#L698) |

## GDPCanonical

Klasse · Quelle: [Distributed/GDPCanonical.php](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L14)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `encode()` | `public static function encode(mixed $value) :string` | [Zeile 17](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L17) |
| `hash()` | `public static function hash(mixed $value) :string` | [Zeile 28](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L28) |
| `decode()` | `public static function decode(string $json, int $depth = 64) :array` | [Zeile 32](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L32) |
| `uuid7()` | `public static function uuid7() :string` | [Zeile 69](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L69) |
| `isUuid()` | `public static function isUuid(string $value) :bool` | [Zeile 81](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L81) |
| `isIdentifier()` | `public static function isIdentifier(string $value) :bool` | [Zeile 88](../../PHP/gbdb_framework/Distributed/GDPCanonical.php#L88) |

## GDPConfig

Klasse · Quelle: [Distributed/GDPConfig.php](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L11)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get(string $name) :mixed` | [Zeile 39](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L39) |
| `enabled()` | `public static function enabled() :bool` | [Zeile 43](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L43) |
| `reset()` | `public static function reset() :void` | [Zeile 50](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L50) |
| `flag()` | `public static function flag(string $name) :bool` | [Zeile 54](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L54) |
| `int()` | `public static function int(string $name, int $min, int $max) :int` | [Zeile 58](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L58) |
| `role()` | `public static function role() :GDPNodeRole` | [Zeile 62](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L62) |
| `syncMode()` | `public static function syncMode() :string` | [Zeile 66](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L66) |
| `hubUrl()` | `public static function hubUrl() :string` | [Zeile 75](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L75) |
| `assertTransportUrl()` | `public static function assertTransportUrl(string $url) :void` | [Zeile 88](../../PHP/gbdb_framework/Distributed/GDPConfig.php#L88) |

## GDPAuthorizationException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L52)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPCheckpointMismatchException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L70)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPConnectionException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L49)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct( private readonly GDPErrorCode $errorCode, string $message, private readonly array $context = [], ?Throwable $previous = null )` | [Zeile 9](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L9) |
| `errorCode()` | `public function errorCode() :GDPErrorCode` | [Zeile 18](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L18) |
| `retryClass()` | `public function retryClass() :GDPRetryClass` | [Zeile 22](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L22) |
| `context()` | `public function context() :array` | [Zeile 31](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L31) |
| `toArray()` | `public function toArray() :array` | [Zeile 38](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L38) |

## GDPIntegrityException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L58)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPPolicyViolationException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L61)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPProtocolException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L73)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPSchemaMismatchException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L55)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPSnapshotException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L67)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPStorageUnavailableException

Klasse · Quelle: [Distributed/GDPExceptions.php](../../PHP/gbdb_framework/Distributed/GDPExceptions.php#L64)

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPHubService

Klasse · Quelle: [Distributed/GDPHub.php](../../PHP/gbdb_framework/Distributed/GDPHub.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `handshake()` | `public static function handshake(array $peer, array $body) :array` | [Zeile 55](../../PHP/gbdb_framework/Distributed/GDPHub.php#L55) |
| `push()` | `public static function push(array $peer, array $body) :array` | [Zeile 134](../../PHP/gbdb_framework/Distributed/GDPHub.php#L134) |
| `pull()` | `public static function pull(array $peer, array $body) :array` | [Zeile 231](../../PHP/gbdb_framework/Distributed/GDPHub.php#L231) |
| `ack()` | `public static function ack(array $peer, array $body) :array` | [Zeile 252](../../PHP/gbdb_framework/Distributed/GDPHub.php#L252) |
| `snapshotManifest()` | `public static function snapshotManifest(array $peer, array $body) :array` | [Zeile 273](../../PHP/gbdb_framework/Distributed/GDPHub.php#L273) |
| `snapshotChunk()` | `public static function snapshotChunk(array $peer, array $body) :array` | [Zeile 283](../../PHP/gbdb_framework/Distributed/GDPHub.php#L283) |
| `pairStart()` | `public static function pairStart(array $body, string $proof) :array` | [Zeile 299](../../PHP/gbdb_framework/Distributed/GDPHub.php#L299) |
| `pairComplete()` | `public static function pairComplete(array $peer, array $body) :array` | [Zeile 357](../../PHP/gbdb_framework/Distributed/GDPHub.php#L357) |
| `rotate()` | `public static function rotate(array $peer) :array` | [Zeile 400](../../PHP/gbdb_framework/Distributed/GDPHub.php#L400) |
| `confirmRotation()` | `public static function confirmRotation(array $peer) :array` | [Zeile 414](../../PHP/gbdb_framework/Distributed/GDPHub.php#L414) |

## GDPServer

Klasse · Quelle: [Distributed/GDPHub.php](../../PHP/gbdb_framework/Distributed/GDPHub.php#L432)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `handle()` | `public static function handle() :void` | [Zeile 436](../../PHP/gbdb_framework/Distributed/GDPHub.php#L436) |

## GDPAppliedIndex

Klasse · Quelle: [Distributed/GDPJournal.php](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L415)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `lookup()` | `public static function lookup(string $instanceId, string $operationId) :?array` | [Zeile 420](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L420) |
| `mark()` | `public static function mark(string $instanceId, GDPJournalEntry $entry) :void` | [Zeile 426](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L426) |
| `check()` | `public static function check(string $instanceId, GDPJournalEntry $entry) :bool` | [Zeile 436](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L436) |
| `prune()` | `public static function prune(string $instanceId, int $olderThan) :int` | [Zeile 450](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L450) |

## GDPCursorManager

Klasse · Quelle: [Distributed/GDPJournal.php](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L385)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `all()` | `public static function all(string $instanceId) :array` | [Zeile 390](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L390) |
| `get()` | `public static function get(string $instanceId, string $peerNodeId) :array` | [Zeile 394](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L394) |
| `update()` | `public static function update(string $instanceId, string $peerNodeId, array $values) :array` | [Zeile 402](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L402) |

## GDPJournal

Klasse · Quelle: [Distributed/GDPJournal.php](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L155)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `sequences()` | `public static function sequences(string $instanceId) :array` | [Zeile 178](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L178) |
| `appendLocal()` | `public static function appendLocal(string $instanceId, array $entries, bool $isHub) :array` | [Zeile 210](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L210) |
| `appendHub()` | `public static function appendHub(string $instanceId, GDPJournalEntry $entry) :GDPJournalEntry` | [Zeile 235](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L235) |
| `read()` | `public static function read(string $instanceId, string $log, int $from, int $limit, int $maxBytes, ?callable $filter = null) :array` | [Zeile 261](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L261) |
| `tail()` | `public static function tail(string $instanceId, string $log, int $count) :array` | [Zeile 324](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L324) |
| `containsOperation()` | `public static function containsOperation(string $instanceId, string $log, string $operationId, int $window = 200) :bool` | [Zeile 340](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L340) |
| `compact()` | `public static function compact(string $instanceId, string $log, int $safeThrough) :int` | [Zeile 361](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L361) |

## GDPJournalEntry

Klasse · Quelle: [Distributed/GDPJournal.php](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `create()` | `public static function create( string $instanceId, string $originNodeId, string $operation, string $database, string $table, string $recordId, int $schemaVersion, int $authorityEpoch, int $previousVersion, int $newVersion, ?array $payload, ?string $transactionGroupId ) :self` | [Zeile 17](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L17) |
| `fromArray()` | `public static function fromArray(array $data, bool $verifyHash = true) :self` | [Zeile 56](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L56) |
| `computeHash()` | `public function computeHash() :string` | [Zeile 101](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L101) |
| `withSourceSequence()` | `public function withSourceSequence(int $sequence) :self` | [Zeile 108](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L108) |
| `withHubSequence()` | `public function withHubSequence(int $sequence) :self` | [Zeile 117](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L117) |
| `__get()` | `public function __get(string $name) :mixed` | [Zeile 125](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L125) |
| `toArray()` | `public function toArray() :array` | [Zeile 133](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L133) |
| `summary()` | `public function summary() :array` | [Zeile 140](../../PHP/gbdb_framework/Distributed/GDPJournal.php#L140) |

## GDPPolicyDecision

Klasse · Quelle: [Distributed/GDPPolicyEngine.php](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct( public readonly bool $allowed, public readonly bool $journal, public readonly GDPWriteMode $mode, public readonly array $rule, public readonly ?GDPErrorCode $code = null, public readonly string $reason = '' )` | [Zeile 9](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L9) |
| `assert()` | `public function assert(array $context = []) :void` | [Zeile 19](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L19) |
| `toArray()` | `public function toArray() :array` | [Zeile 25](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L25) |

## GDPPolicyEngine

Klasse · Quelle: [Distributed/GDPPolicyEngine.php](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L43)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `document()` | `public static function document(string $instanceId) :array` | [Zeile 50](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L50) |
| `replaceDocument()` | `public static function replaceDocument(string $instanceId, array $document) :void` | [Zeile 61](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L61) |
| `validate()` | `public static function validate(string $instanceId, string $database, string $table, array $options) :array` | [Zeile 83](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L83) |
| `setPolicy()` | `public static function setPolicy(string $instanceId, string $database, string $table, array $options) :array` | [Zeile 160](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L160) |
| `setDefaultMode()` | `public static function setDefaultMode(string $instanceId, GDPWriteMode $mode) :void` | [Zeile 195](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L195) |
| `removePolicy()` | `public static function removePolicy(string $instanceId, string $database, string $table) :void` | [Zeile 203](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L203) |
| `resolve()` | `public static function resolve(string $instanceId, string $database, string $table) :array` | [Zeile 234](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L234) |
| `mode()` | `public static function mode(array $rule) :GDPWriteMode` | [Zeile 265](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L265) |
| `decideLocal()` | `public static function decideLocal(string $instanceId, string $database, string $table, string $operation, array $rows, array $after = []) :GDPPolicyDecision` | [Zeile 276](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L276) |
| `authorizeRemote()` | `public static function authorizeRemote(string $instanceId, GDPJournalEntry $entry, string $peerNodeId, ?array $existing) :void` | [Zeile 368](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L368) |
| `issueLeases()` | `public static function issueLeases(string $instanceId, string $peerNodeId) :array` | [Zeile 433](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L433) |
| `leases()` | `public static function leases() :array` | [Zeile 452](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L452) |
| `storeLeases()` | `public static function storeLeases(array $leases) :void` | [Zeile 456](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L456) |
| `transferOwnership()` | `public static function transferOwnership(string $instanceId, string $database, string $table, string $newOwner) :array` | [Zeile 473](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L473) |
| `completeTransfers()` | `public static function completeTransfers(string $instanceId, ?string $peerNodeId, ?array $peerState, bool $force = false) :int` | [Zeile 513](../../PHP/gbdb_framework/Distributed/GDPPolicyEngine.php#L513) |

## GDPAuditLogger

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L439)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `log()` | `public static function log(string $event, array $metadata = [], string $actor = '') :void` | [Zeile 442](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L442) |
| `recent()` | `public static function recent(int $limit = 100) :array` | [Zeile 489](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L489) |

## GDPGrants

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L291)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `all()` | `public static function all() :array` | [Zeile 298](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L298) |
| `grant()` | `public static function grant(string $nodeId, string $instanceId, array $permissions, ?array $scope = null, int $expiresAt = 0) :array` | [Zeile 302](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L302) |
| `revokeNode()` | `public static function revokeNode(string $nodeId) :int` | [Zeile 346](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L346) |
| `revokeInstance()` | `public static function revokeInstance(string $instanceId) :int` | [Zeile 362](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L362) |
| `active()` | `public static function active(string $nodeId, string $instanceId) :?array` | [Zeile 379](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L379) |
| `instancesFor()` | `public static function instancesFor(string $nodeId) :array` | [Zeile 395](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L395) |
| `authorize()` | `public static function authorize(string $nodeId, string $permission, string $instanceId) :array` | [Zeile 410](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L410) |
| `inScope()` | `public static function inScope(array $grant, string $database, string $table) :bool` | [Zeile 422](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L422) |

## GDPInstanceRegistry

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L182)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get(string $instanceId) :?array` | [Zeile 189](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L189) |
| `all()` | `public static function all() :array` | [Zeile 206](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L206) |
| `replicated()` | `public static function replicated(string $instanceId) :bool` | [Zeile 220](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L220) |
| `save()` | `public static function save(string $instanceId, array $values) :array` | [Zeile 226](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L226) |
| `state()` | `public static function state(string $instanceId) :GDPSyncState` | [Zeile 250](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L250) |
| `setState()` | `public static function setState(string $instanceId, GDPSyncState $state) :void` | [Zeile 254](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L254) |
| `block()` | `public static function block(string $instanceId, GDPException $exception) :void` | [Zeile 269](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L269) |
| `unblock()` | `public static function unblock(string $instanceId, GDPSyncState $state) :void` | [Zeile 279](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L279) |
| `forget()` | `public static function forget() :void` | [Zeile 283](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L283) |

## GDPNodeIdentity

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get() :array` | [Zeile 20](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L20) |
| `configDrift()` | `public static function configDrift() :array` | [Zeile 56](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L56) |
| `regenerable()` | `public static function regenerable() :bool` | [Zeile 76](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L76) |
| `regenerate()` | `public static function regenerate() :array` | [Zeile 83](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L83) |
| `id()` | `public static function id() :string` | [Zeile 97](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L97) |
| `role()` | `public static function role() :GDPNodeRole` | [Zeile 105](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L105) |
| `roleMismatch()` | `public static function roleMismatch() :bool` | [Zeile 109](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L109) |
| `rename()` | `public static function rename(string $displayName) :void` | [Zeile 113](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L113) |
| `forget()` | `public static function forget() :void` | [Zeile 120](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L120) |

## GDPPeerRegistry

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L128)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `all()` | `public static function all() :array` | [Zeile 133](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L133) |
| `get()` | `public static function get(string $nodeId) :?array` | [Zeile 137](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L137) |
| `save()` | `public static function save(string $nodeId, array $values) :array` | [Zeile 143](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L143) |
| `active()` | `public static function active(string $nodeId) :bool` | [Zeile 166](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L166) |
| `touch()` | `public static function touch(string $nodeId) :void` | [Zeile 172](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L172) |

## GDPStateManager

Klasse · Quelle: [Distributed/GDPRegistry.php](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L515)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get() :array` | [Zeile 522](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L522) |
| `update()` | `public static function update(array $values) :array` | [Zeile 538](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L538) |
| `preference()` | `public static function preference() :GDPOperationPreference` | [Zeile 545](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L545) |
| `connection()` | `public static function connection() :GDPConnectionState` | [Zeile 549](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L549) |
| `offline()` | `public static function offline() :bool` | [Zeile 556](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L556) |
| `storage()` | `public static function storage() :GDPStorageState` | [Zeile 564](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L564) |
| `markOnline()` | `public static function markOnline() :void` | [Zeile 574](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L574) |
| `markOffline()` | `public static function markOffline(GDPException $exception) :void` | [Zeile 586](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L586) |
| `recordFailure()` | `public static function recordFailure(GDPException $exception, string $instanceId = '') :void` | [Zeile 598](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L598) |
| `recordMetrics()` | `public static function recordMetrics(string $instanceId, array $metrics) :void` | [Zeile 609](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L609) |
| `setPreference()` | `public static function setPreference(GDPOperationPreference $preference) :void` | [Zeile 615](../../PHP/gbdb_framework/Distributed/GDPRegistry.php#L615) |

## GDPAuthenticator

Klasse · Quelle: [Distributed/GDPSecurity.php](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L13)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `stringToSign()` | `public static function stringToSign(string $method, string $path, string $body, string $nodeId, string $keyId, int $timestamp, string $nonce) :string` | [Zeile 16](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L16) |
| `sign()` | `public static function sign(string $secret, string $data) :string` | [Zeile 20](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L20) |
| `requestHeaders()` | `public static function requestHeaders(string $method, string $path, string $body, string $nodeId, string $keyId, string $secret) :array` | [Zeile 27](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L27) |
| `verifyRequest()` | `public static function verifyRequest(string $method, string $path, string $body, array $headers) :array` | [Zeile 47](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L47) |
| `responseSignature()` | `public static function responseSignature(string $secret, string $requestNonce, string $body) :string` | [Zeile 89](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L89) |
| `wrap()` | `public static function wrap(string $key, string $plain, string $associated) :string` | [Zeile 96](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L96) |
| `unwrap()` | `public static function unwrap(string $key, string $wrapped, string $associated) :string` | [Zeile 108](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L108) |
| `pairingKey()` | `public static function pairingKey(string $secret) :string` | [Zeile 119](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L119) |

## GDPCredentials

Klasse · Quelle: [Distributed/GDPSecurity.php](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L127)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `newSecret()` | `public static function newSecret() :string` | [Zeile 132](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L132) |
| `newKeyId()` | `public static function newKeyId() :string` | [Zeile 136](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L136) |
| `peerKey()` | `public static function peerKey(string $nodeId, string $keyId) :?array` | [Zeile 143](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L143) |
| `storePeerKey()` | `public static function storePeerKey(string $nodeId, string $keyId, string $secret, string $status, int $expiresAt = 0) :void` | [Zeile 158](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L158) |
| `setPeerKeyStatus()` | `public static function setPeerKeyStatus(string $nodeId, string $keyId, string $status) :void` | [Zeile 164](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L164) |
| `revokePeerKeys()` | `public static function revokePeerKeys(string $nodeId, string $exceptKeyId = '') :int` | [Zeile 176](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L176) |
| `peerKeys()` | `public static function peerKeys(string $nodeId) :array` | [Zeile 194](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L194) |
| `hubKey()` | `public static function hubKey() :?array` | [Zeile 207](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L207) |
| `storeHubKey()` | `public static function storeHubKey(array $hub) :void` | [Zeile 213](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L213) |
| `clearHubKey()` | `public static function clearHubKey() :void` | [Zeile 219](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L219) |

## GDPPairing

Klasse · Quelle: [Distributed/GDPSecurity.php](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L229)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `create()` | `public static function create(array $instances, string $role, array $permissions, bool $requireApproval, ?array $scope = null) :array` | [Zeile 237](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L237) |
| `parse()` | `public static function parse(string $code) :array` | [Zeile 285](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L285) |
| `session()` | `public static function session(string $pairingId) :?array` | [Zeile 293](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L293) |
| `sessions()` | `public static function sessions() :array` | [Zeile 302](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L302) |
| `update()` | `public static function update(string $pairingId, array $values) :void` | [Zeile 313](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L313) |
| `revoke()` | `public static function revoke(string $pairingId) :void` | [Zeile 321](../../PHP/gbdb_framework/Distributed/GDPSecurity.php#L321) |

## GDPSchema

Klasse · Quelle: [Distributed/GDPSnapshot.php](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `tables()` | `public static function tables(string $instanceId) :array` | [Zeile 14](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L14) |
| `fingerprint()` | `public static function fingerprint(string $instanceId) :string` | [Zeile 39](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L39) |
| `populatedTables()` | `public static function populatedTables(string $instanceId, array $tables) :array` | [Zeile 54](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L54) |

## GDPSnapshotManager

Klasse · Quelle: [Distributed/GDPSnapshot.php](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L80)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `currentManifest()` | `public static function currentManifest(string $instanceId, string $resumeSnapshotId = '') :array` | [Zeile 100](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L100) |
| `stagedSnapshotId()` | `public static function stagedSnapshotId(string $instanceId) :string` | [Zeile 114](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L114) |
| `create()` | `public static function create(string $instanceId) :array` | [Zeile 129](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L129) |
| `chunk()` | `public static function chunk(string $instanceId, string $snapshotId, int $index) :array` | [Zeile 214](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L214) |
| `verifyManifest()` | `public static function verifyManifest(array $manifest, string $instanceId) :void` | [Zeile 234](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L234) |
| `download()` | `public static function download(GDPTransport $transport, string $instanceId, array $manifest) :void` | [Zeile 249](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L249) |
| `preflight()` | `public static function preflight(string $instanceId, array $manifest) :array` | [Zeile 299](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L299) |
| `activate()` | `public static function activate(string $instanceId, string $snapshotId) :array` | [Zeile 315](../../PHP/gbdb_framework/Distributed/GDPSnapshot.php#L315) |

## GDPNonceCache

Klasse · Quelle: [Distributed/GDPStore.php](../../PHP/gbdb_framework/Distributed/GDPStore.php#L280)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `remember()` | `public static function remember(string $nodeId, string $nonce, int $ttl) :bool` | [Zeile 281](../../PHP/gbdb_framework/Distributed/GDPStore.php#L281) |

## GDPStore

Klasse · Quelle: [Distributed/GDPStore.php](../../PHP/gbdb_framework/Distributed/GDPStore.php#L14)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `root()` | `public static function root() :string` | [Zeile 17](../../PHP/gbdb_framework/Distributed/GDPStore.php#L17) |
| `path()` | `public static function path(string ...$segments) :string` | [Zeile 21](../../PHP/gbdb_framework/Distributed/GDPStore.php#L21) |
| `transaction()` | `public static function transaction(callable $callback) :mixed` | [Zeile 34](../../PHP/gbdb_framework/Distributed/GDPStore.php#L34) |
| `read()` | `public static function read(callable $callback) :mixed` | [Zeile 41](../../PHP/gbdb_framework/Distributed/GDPStore.php#L41) |
| `exists()` | `public static function exists(string $path) :bool` | [Zeile 45](../../PHP/gbdb_framework/Distributed/GDPStore.php#L45) |
| `readJson()` | `public static function readJson(string $path, array $default = []) :array` | [Zeile 49](../../PHP/gbdb_framework/Distributed/GDPStore.php#L49) |
| `writeJson()` | `public static function writeJson(string $path, array $value, bool $sealed = false) :void` | [Zeile 73](../../PHP/gbdb_framework/Distributed/GDPStore.php#L73) |
| `appendLine()` | `public static function appendLine(string $path, string $line) :void` | [Zeile 83](../../PHP/gbdb_framework/Distributed/GDPStore.php#L83) |
| `readLines()` | `public static function readLines(string $path) :array` | [Zeile 99](../../PHP/gbdb_framework/Distributed/GDPStore.php#L99) |
| `delete()` | `public static function delete(string $path) :void` | [Zeile 113](../../PHP/gbdb_framework/Distributed/GDPStore.php#L113) |
| `listDirectory()` | `public static function listDirectory(string $path) :array` | [Zeile 122](../../PHP/gbdb_framework/Distributed/GDPStore.php#L122) |
| `removeTree()` | `public static function removeTree(string $path) :void` | [Zeile 139](../../PHP/gbdb_framework/Distributed/GDPStore.php#L139) |
| `ensureDirectory()` | `public static function ensureDirectory(string $path) :void` | [Zeile 153](../../PHP/gbdb_framework/Distributed/GDPStore.php#L153) |
| `seal()` | `public static function seal(string $plain) :string` | [Zeile 162](../../PHP/gbdb_framework/Distributed/GDPStore.php#L162) |
| `unseal()` | `public static function unseal(string $stored) :string` | [Zeile 166](../../PHP/gbdb_framework/Distributed/GDPStore.php#L166) |

## GDPVault

Klasse · Quelle: [Distributed/GDPStore.php](../../PHP/gbdb_framework/Distributed/GDPStore.php#L193)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `all()` | `public static function all() :array` | [Zeile 221](../../PHP/gbdb_framework/Distributed/GDPStore.php#L221) |
| `get()` | `public static function get(string $section, string $key) :mixed` | [Zeile 245](../../PHP/gbdb_framework/Distributed/GDPStore.php#L245) |
| `update()` | `public static function update(callable $mutator) :mixed` | [Zeile 252](../../PHP/gbdb_framework/Distributed/GDPStore.php#L252) |
| `forget()` | `public static function forget() :void` | [Zeile 272](../../PHP/gbdb_framework/Distributed/GDPStore.php#L272) |

## GDPSyncEngine

Klasse · Quelle: [Distributed/GDPSyncEngine.php](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L13)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `hubUrl()` | `public static function hubUrl() :string` | [Zeile 23](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L23) |
| `run()` | `public static function run(?string $onlyInstance, int $budgetSeconds, bool $manual, bool $replaceLocal = false) :array` | [Zeile 52](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L52) |
| `pair()` | `public static function pair(string $hubUrl, string $code, string $displayName) :array` | [Zeile 420](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L420) |
| `rotateCredential()` | `public static function rotateCredential() :array` | [Zeile 495](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L495) |
| `housekeeping()` | `public static function housekeeping() :array` | [Zeile 517](../../PHP/gbdb_framework/Distributed/GDPSyncEngine.php#L517) |

## GDPHttpTransport

Klasse · Quelle: [Distributed/GDPTransport.php](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L18)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct(private readonly string $baseUrl)` | [Zeile 26](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L26) |
| `canonicalPath()` | `public static function canonicalPath(string $endpoint) :string` | [Zeile 30](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L30) |
| `call()` | `public function call(string $endpoint, array $body, bool $authenticated = true, array $extraHeaders = [], ?array $credential = null) :array` | [Zeile 37](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L37) |
| `checkHealth()` | `public function checkHealth() :array` | [Zeile 83](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L83) |
| `exchange()` | `public function exchange(string $endpoint, array $payload, array $headers) :array` | [Zeile 92](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L92) |

## GDPTransport

Interface · Quelle: [Distributed/GDPTransport.php](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L8)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `call()` | `public function call(string $endpoint, array $body, bool $authenticated = true, array $extraHeaders = [], ?array $credential = null) :array` | [Zeile 9](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L9) |
| `checkHealth()` | `public function checkHealth() :array` | [Zeile 11](../../PHP/gbdb_framework/Distributed/GDPTransport.php#L11) |

## GDPConnectionState

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L45)

Deklarierte Enum-Fälle: `ONLINE`, `OFFLINE`.

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPErrorCode

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L85)

Deklarierte Enum-Fälle: `CONNECTION_UNAVAILABLE`, `AUTHENTICATION_FAILED`, `AUTHORIZATION_DENIED`, `PAIRING_EXPIRED`, `POLICY_VIOLATION`, `LEASE_EXPIRED`, `SCHEMA_MISMATCH`, `SEQUENCE_GAP`, `VERSION_CONFLICT`, `DUPLICATE_PAYLOAD_MISMATCH`, `CHECKPOINT_MISMATCH`, `SNAPSHOT_REQUIRED`, `SNAPSHOT_CHECKSUM_FAILED`, `STORAGE_TRANSACTION_FAILED`, `STORAGE_STATE_UNCERTAIN`, `INTEGRITY_BLOCKED`, `RATE_LIMITED`, `PROTOCOL_ERROR`, `PAYLOAD_TOO_LARGE`, `NOT_CONFIGURED`.

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `retryClass()` | `public function retryClass() :GDPRetryClass` | [Zeile 107](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L107) |
| `httpStatus()` | `public function httpStatus() :int` | [Zeile 116](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L116) |

## GDPNodeRole

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L31)

Deklarierte Enum-Fälle: `HUB`, `REPLICA`, `OBSERVER`.

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `label()` | `public function label() :string` | [Zeile 36](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L36) |

## GDPOperationPreference

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L67)

Deklarierte Enum-Fälle: `AUTO`, `FORCE_LOCAL`, `PREFER_CENTRAL`.

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPRetryClass

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L76)

Deklarierte Enum-Fälle: `RETRY`, `CHECK`, `MANUAL`.

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPStorageState

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L58)

Deklarierte Enum-Fälle: `GBDB`, `SQL`, `UNAVAILABLE`.

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPSyncState

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L50)

Deklarierte Enum-Fälle: `INITIALIZING`, `IDLE`, `PENDING`, `RESYNC`, `BLOCKED`.

Keine eigenen öffentlichen Methoden deklariert. Geerbte oder per Trait eingebundene Methoden sind damit nicht ausgeschlossen.

## GDPWriteMode

Enum · Quelle: [Distributed/GDPTypes.php](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L8)

Deklarierte Enum-Fälle: `CENTRAL_OWNER`, `NODE_OWNER`, `APPEND_ONLY`, `LOCAL_ONLY`.

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `label()` | `public function label() :string` | [Zeile 14](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L14) |
| `replicated()` | `public function replicated() :bool` | [Zeile 23](../../PHP/gbdb_framework/Distributed/GDPTypes.php#L23) |

## GDPApplier

Klasse · Quelle: [Distributed/GDPWriteInterceptor.php](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L470)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `apply()` | `public static function apply(string $instanceId, GDPJournalEntry $entry) :string` | [Zeile 474](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L474) |

## GDPWriteInterceptor

Klasse · Quelle: [Distributed/GDPWriteInterceptor.php](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L24)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `engaged()` | `public static function engaged(string $instanceId) :bool` | [Zeile 37](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L37) |
| `applying()` | `public static function applying(callable $callback) :mixed` | [Zeile 56](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L56) |
| `maintenance()` | `public static function maintenance(callable $callback) :mixed` | [Zeile 69](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L69) |
| `assertStructureChange()` | `public static function assertStructureChange(string $instanceId, string $action, string $database, string $table) :void` | [Zeile 79](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L79) |
| `assertInstanceRemovable()` | `public static function assertInstanceRemovable(string $instanceId) :void` | [Zeile 95](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L95) |
| `beginGroup()` | `public static function beginGroup() :void` | [Zeile 104](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L104) |
| `endGroup()` | `public static function endGroup(bool $committed) :void` | [Zeile 110](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L110) |
| `insert()` | `public static function insert(string $instanceId, string $database, string $table, mixed $data, callable $local) :int` | [Zeile 133](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L133) |
| `update()` | `public static function update(string $instanceId, string $database, string $table, mixed $where, mixed $is, mixed $newData, callable $local) :bool` | [Zeile 173](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L173) |
| `delete()` | `public static function delete(string $instanceId, string $database, string $table, mixed $where, mixed $is, callable $local) :bool` | [Zeile 196](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L196) |
| `recover()` | `public static function recover(string $instanceId) :void` | [Zeile 407](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L407) |
| `hasPendingIntent()` | `public static function hasPendingIntent(string $instanceId) :bool` | [Zeile 436](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L436) |
| `payload()` | `public static function payload(array $row) :array` | [Zeile 449](../../PHP/gbdb_framework/Distributed/GDPWriteInterceptor.php#L449) |

