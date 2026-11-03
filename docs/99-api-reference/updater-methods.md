# Framework-Updater – öffentliche Deklarationen

Installation und Wiederherstellung regulär über die Admin-Oberfläche bedienen. Die aufgeführten Klassen sind interne Prüf- und Ablaufbausteine; ihre Sichtbarkeit erlaubt keine Umgehung der Vertrauenskette.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## GBDBUpdateConfigMerger

Klasse · Quelle: [updater/config_merger.php](../../PHP/gbdb_framework/updater/config_merger.php#L13)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `merge()` | `public static function merge(string $local, string $template) :array` | [Zeile 22](../../PHP/gbdb_framework/updater/config_merger.php#L22) |

## GBDBUpdateGate

Klasse · Quelle: [updater/gate.php](../../PHP/gbdb_framework/updater/gate.php#L17)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `register()` | `public static function register() :void` | [Zeile 39](../../PHP/gbdb_framework/updater/gate.php#L39) |
| `boot()` | `public static function boot() :void` | [Zeile 52](../../PHP/gbdb_framework/updater/gate.php#L52) |
| `afterBoot()` | `public static function afterBoot() :void` | [Zeile 110](../../PHP/gbdb_framework/updater/gate.php#L110) |
| `onShutdown()` | `public static function onShutdown() :void` | [Zeile 147](../../PHP/gbdb_framework/updater/gate.php#L147) |
| `rollback()` | `public static function rollback(string $jobId, string $reason, bool $restoreData) :array` | [Zeile 173](../../PHP/gbdb_framework/updater/gate.php#L173) |

## GBDBUpdateHttp

Klasse · Quelle: [updater/http.php](../../PHP/gbdb_framework/updater/http.php#L9)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `get()` | `public static function get(string $url, int $maxBytes, ?int $timeout = null) :array` | [Zeile 16](../../PHP/gbdb_framework/updater/http.php#L16) |
| `postJson()` | `public static function postJson(string $url, array $payload, int $maxBytes = 65536, ?int $timeout = null) :array` | [Zeile 24](../../PHP/gbdb_framework/updater/http.php#L24) |
| `download()` | `public static function download(string $url, string $target, int $maxBytes, ?int $timeout = null) :array` | [Zeile 33](../../PHP/gbdb_framework/updater/http.php#L33) |

## GBDBUpdateInstaller

Klasse · Quelle: [updater/installer.php](../../PHP/gbdb_framework/updater/installer.php#L18)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `job()` | `public static function job() :array` | [Zeile 50](../../PHP/gbdb_framework/updater/installer.php#L50) |
| `isActive()` | `public static function isActive(array $job) :bool` | [Zeile 54](../../PHP/gbdb_framework/updater/installer.php#L54) |
| `start()` | `public static function start(string $version, string $trigger) :array` | [Zeile 73](../../PHP/gbdb_framework/updater/installer.php#L73) |
| `confirm()` | `public static function confirm(string $what) :array` | [Zeile 115](../../PHP/gbdb_framework/updater/installer.php#L115) |
| `cancel()` | `public static function cancel() :array` | [Zeile 142](../../PHP/gbdb_framework/updater/installer.php#L142) |
| `step()` | `public static function step() :array` | [Zeile 165](../../PHP/gbdb_framework/updater/installer.php#L165) |
| `runAutomatic()` | `public static function runAutomatic(string $version) :void` | [Zeile 210](../../PHP/gbdb_framework/updater/installer.php#L210) |
| `target()` | `public static function target(string $path) :?string` | [Zeile 433](../../PHP/gbdb_framework/updater/installer.php#L433) |
| `protectedReason()` | `public static function protectedReason(string $target) :string` | [Zeile 442](../../PHP/gbdb_framework/updater/installer.php#L442) |
| `plan()` | `public static function plan(array $manifest, array $baseline) :array` | [Zeile 475](../../PHP/gbdb_framework/updater/installer.php#L475) |
| `verifyPending()` | `public static function verifyPending() :array` | [Zeile 996](../../PHP/gbdb_framework/updater/installer.php#L996) |
| `backups()` | `public static function backups() :array` | [Zeile 1234](../../PHP/gbdb_framework/updater/installer.php#L1234) |
| `restoreLatest()` | `public static function restoreLatest(string $jobId) :array` | [Zeile 1256](../../PHP/gbdb_framework/updater/installer.php#L1256) |
| `pendingMigrations()` | `public static function pendingMigrations(array $manifest, string $from) :array` | [Zeile 1290](../../PHP/gbdb_framework/updater/installer.php#L1290) |
| `bytes()` | `public static function bytes(int $bytes) :string` | [Zeile 1317](../../PHP/gbdb_framework/updater/installer.php#L1317) |

## GBDBUpdateRollback

Klasse · Quelle: [updater/rollback.php](../../PHP/gbdb_framework/updater/rollback.php#L17)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `run()` | `public static function run(string $workDir, string $jobId, string $reason, bool $restoreData = false) :array` | [Zeile 24](../../PHP/gbdb_framework/updater/rollback.php#L24) |

## GBDBUpdateSettings

Klasse · Quelle: [updater/settings.php](../../PHP/gbdb_framework/updater/settings.php#L12)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `all()` | `public static function all() :array` | [Zeile 64](../../PHP/gbdb_framework/updater/settings.php#L64) |
| `reset()` | `public static function reset() :void` | [Zeile 125](../../PHP/gbdb_framework/updater/settings.php#L125) |
| `frameworkRoot()` | `public static function frameworkRoot() :string` | [Zeile 129](../../PHP/gbdb_framework/updater/settings.php#L129) |
| `projectRoot()` | `public static function projectRoot() :string` | [Zeile 134](../../PHP/gbdb_framework/updater/settings.php#L134) |
| `workDir()` | `public static function workDir() :string` | [Zeile 139](../../PHP/gbdb_framework/updater/settings.php#L139) |
| `url()` | `public static function url(string $name) :string` | [Zeile 161](../../PHP/gbdb_framework/updater/settings.php#L161) |

## GBDBUpdateSignature

Klasse · Quelle: [updater/signature.php](../../PHP/gbdb_framework/updater/signature.php#L21)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `available()` | `public static function available() :bool` | [Zeile 31](../../PHP/gbdb_framework/updater/signature.php#L31) |
| `message()` | `public static function message(string $context, string $bytes) :string` | [Zeile 35](../../PHP/gbdb_framework/updater/signature.php#L35) |
| `generate()` | `public static function generate(string $role, ?int $time = null) :array` | [Zeile 48](../../PHP/gbdb_framework/updater/signature.php#L48) |
| `keyId()` | `public static function keyId(string $role, string $publicRaw, int $time) :string` | [Zeile 66](../../PHP/gbdb_framework/updater/signature.php#L66) |
| `keyIdMatches()` | `public static function keyIdMatches(string $keyId, string $publicRaw) :bool` | [Zeile 71](../../PHP/gbdb_framework/updater/signature.php#L71) |
| `decodePublicKey()` | `public static function decodePublicKey(mixed $value) :string` | [Zeile 76](../../PHP/gbdb_framework/updater/signature.php#L76) |
| `decodeSecretKey()` | `public static function decodeSecretKey(string $value) :string` | [Zeile 86](../../PHP/gbdb_framework/updater/signature.php#L86) |
| `sign()` | `public static function sign(string $context, string $bytes, string $secretBase64) :string` | [Zeile 96](../../PHP/gbdb_framework/updater/signature.php#L96) |
| `verify()` | `public static function verify(string $context, string $bytes, string $signatureRaw, string $publicRaw) :bool` | [Zeile 107](../../PHP/gbdb_framework/updater/signature.php#L107) |
| `buildFile()` | `public static function buildFile(string $context, string $bytes, array $signers) :string` | [Zeile 126](../../PHP/gbdb_framework/updater/signature.php#L126) |
| `parseFile()` | `public static function parseFile(string $file, string $expectedContext) :array` | [Zeile 141](../../PHP/gbdb_framework/updater/signature.php#L141) |
| `validSigners()` | `public static function validSigners(string $context, string $bytes, array $signatures, array $keys) :array` | [Zeile 179](../../PHP/gbdb_framework/updater/signature.php#L179) |

## GBDBUpdateState

Klasse · Quelle: [updater/state.php](../../PHP/gbdb_framework/updater/state.php#L20)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `dir()` | `public static function dir(string $sub = '') :string` | [Zeile 24](../../PHP/gbdb_framework/updater/state.php#L24) |
| `ensure()` | `public static function ensure() :void` | [Zeile 28](../../PHP/gbdb_framework/updater/state.php#L28) |
| `writeFile()` | `public static function writeFile(string $path, string $content) :void` | [Zeile 48](../../PHP/gbdb_framework/updater/state.php#L48) |
| `read()` | `public static function read(string $name, array $default = []) :array` | [Zeile 67](../../PHP/gbdb_framework/updater/state.php#L67) |
| `write()` | `public static function write(string $name, array $data) :void` | [Zeile 80](../../PHP/gbdb_framework/updater/state.php#L80) |
| `remove()` | `public static function remove(string $name) :void` | [Zeile 85](../../PHP/gbdb_framework/updater/state.php#L85) |
| `lock()` | `public static function lock()` | [Zeile 97](../../PHP/gbdb_framework/updater/state.php#L97) |
| `unlock()` | `public static function unlock($handle) :void` | [Zeile 115](../../PHP/gbdb_framework/updater/state.php#L115) |
| `isLocked()` | `public static function isLocked() :bool` | [Zeile 122](../../PHP/gbdb_framework/updater/state.php#L122) |
| `trustState()` | `public static function trustState() :array` | [Zeile 141](../../PHP/gbdb_framework/updater/state.php#L141) |
| `saveTrustState()` | `public static function saveTrustState(array $state, ?string $trustBytes = null, ?string $trustSignature = null) :void` | [Zeile 176](../../PHP/gbdb_framework/updater/state.php#L176) |
| `addHistory()` | `public static function addHistory(array $entry) :void` | [Zeile 207](../../PHP/gbdb_framework/updater/state.php#L207) |
| `history()` | `public static function history(int $limit = 50) :array` | [Zeile 216](../../PHP/gbdb_framework/updater/state.php#L216) |
| `log()` | `public static function log(string $jobId, string $message) :void` | [Zeile 232](../../PHP/gbdb_framework/updater/state.php#L232) |
| `logText()` | `public static function logText(string $jobId) :string` | [Zeile 240](../../PHP/gbdb_framework/updater/state.php#L240) |
| `securityEvent()` | `public static function securityEvent(string $status, string $message, string $keyId = '') :void` | [Zeile 247](../../PHP/gbdb_framework/updater/state.php#L247) |
| `maintenance()` | `public static function maintenance() :?array` | [Zeile 258](../../PHP/gbdb_framework/updater/state.php#L258) |
| `setMaintenance()` | `public static function setMaintenance(string $jobId) :void` | [Zeile 270](../../PHP/gbdb_framework/updater/state.php#L270) |
| `clearMaintenance()` | `public static function clearMaintenance() :void` | [Zeile 274](../../PHP/gbdb_framework/updater/state.php#L274) |
| `removeTree()` | `public static function removeTree(string $path) :void` | [Zeile 280](../../PHP/gbdb_framework/updater/state.php#L280) |
| `newId()` | `public static function newId() :string` | [Zeile 298](../../PHP/gbdb_framework/updater/state.php#L298) |

## GBDBUpdateTrust

Klasse · Quelle: [updater/trust.php](../../PHP/gbdb_framework/updater/trust.php#L20)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `isVersion()` | `public static function isVersion(mixed $value) :bool` | [Zeile 53](../../PHP/gbdb_framework/updater/trust.php#L53) |
| `compareVersions()` | `public static function compareVersions(string $left, string $right) :int` | [Zeile 57](../../PHP/gbdb_framework/updater/trust.php#L57) |
| `parseTime()` | `public static function parseTime(mixed $value, string $field) :int` | [Zeile 62](../../PHP/gbdb_framework/updater/trust.php#L62) |
| `formatTime()` | `public static function formatTime(int $time) :string` | [Zeile 72](../../PHP/gbdb_framework/updater/trust.php#L72) |
| `decodeJson()` | `public static function decodeJson(string $bytes, int $limit, string $what) :array` | [Zeile 79](../../PHP/gbdb_framework/updater/trust.php#L79) |
| `keySet()` | `public static function keySet(mixed $list, string $field) :array` | [Zeile 120](../../PHP/gbdb_framework/updater/trust.php#L120) |
| `keyList()` | `public static function keyList(array $keys) :array` | [Zeile 152](../../PHP/gbdb_framework/updater/trust.php#L152) |
| `anchorState()` | `public static function anchorState(string $bytes) :array` | [Zeile 179](../../PHP/gbdb_framework/updater/trust.php#L179) |
| `parseTrust()` | `public static function parseTrust(string $bytes) :array` | [Zeile 210](../../PHP/gbdb_framework/updater/trust.php#L210) |
| `verifyTrust()` | `public static function verifyTrust(string $bytes, string $signatureFile, array $trusted, int $now, callable $history, bool $rejectExpired = true) :array` | [Zeile 360](../../PHP/gbdb_framework/updater/trust.php#L360) |
| `keyStatus()` | `public static function keyStatus(array $trust, string $keyId, string $role, int $now) :string` | [Zeile 425](../../PHP/gbdb_framework/updater/trust.php#L425) |
| `verifyCatalog()` | `public static function verifyCatalog(string $bytes, string $signatureFile, array $trust, array $seen, int $now, bool $rejectExpired = true) :array` | [Zeile 488](../../PHP/gbdb_framework/updater/trust.php#L488) |
| `verifyManifest()` | `public static function verifyManifest(string $bytes, string $signatureFile, array $trust, array $entry, int $now, bool $allowRevoked = false) :array` | [Zeile 574](../../PHP/gbdb_framework/updater/trust.php#L574) |
| `verifyZip()` | `public static function verifyZip(string $file, array $manifest) :void` | [Zeile 671](../../PHP/gbdb_framework/updater/trust.php#L671) |

## GBDBUpdateTrustException

Klasse · Quelle: [updater/trust.php](../../PHP/gbdb_framework/updater/trust.php#L6)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct(public readonly string $status, string $message, public readonly string $keyId = '')` | [Zeile 7](../../PHP/gbdb_framework/updater/trust.php#L7) |

## GBDBFrameworkUpdater

Klasse · Quelle: [updater/updater.php](../../PHP/gbdb_framework/updater/updater.php#L13)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `status()` | `public static function status() :array` | [Zeile 42](../../PHP/gbdb_framework/updater/updater.php#L42) |
| `check()` | `public static function check(bool $force = false) :array` | [Zeile 84](../../PHP/gbdb_framework/updater/updater.php#L84) |
| `parseListing()` | `public static function parseListing(string $html) :array` | [Zeile 140](../../PHP/gbdb_framework/updater/updater.php#L140) |
| `verifiedMetadata()` | `public static function verifiedMetadata() :array` | [Zeile 178](../../PHP/gbdb_framework/updater/updater.php#L178) |
| `verifiedManifest()` | `public static function verifiedManifest(array $metadata, string $version, bool $retry = true) :array` | [Zeile 237](../../PHP/gbdb_framework/updater/updater.php#L237) |
| `compatibility()` | `public static function compatibility(array $manifest) :array` | [Zeile 279](../../PHP/gbdb_framework/updater/updater.php#L279) |
| `trustSummary()` | `public static function trustSummary(array $trust) :array` | [Zeile 405](../../PHP/gbdb_framework/updater/updater.php#L405) |
| `skip()` | `public static function skip(string $version) :void` | [Zeile 432](../../PHP/gbdb_framework/updater/updater.php#L432) |
| `unskip()` | `public static function unskip(string $version) :void` | [Zeile 436](../../PHP/gbdb_framework/updater/updater.php#L436) |
| `nextPatchTime()` | `public static function nextPatchTime(string $pattern, int $now) :?int` | [Zeile 468](../../PHP/gbdb_framework/updater/updater.php#L468) |
| `tick()` | `public static function tick(bool $longRunning) :void` | [Zeile 533](../../PHP/gbdb_framework/updater/updater.php#L533) |
| `tickDue()` | `public static function tickDue() :bool` | [Zeile 552](../../PHP/gbdb_framework/updater/updater.php#L552) |

## GBDBUpdateVersion

Klasse · Quelle: [updater/version.php](../../PHP/gbdb_framework/updater/version.php#L10)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `info()` | `public static function info() :array` | [Zeile 20](../../PHP/gbdb_framework/updater/version.php#L20) |
| `current()` | `public static function current() :string` | [Zeile 56](../../PHP/gbdb_framework/updater/version.php#L56) |
| `reset()` | `public static function reset() :void` | [Zeile 60](../../PHP/gbdb_framework/updater/version.php#L60) |

## GBDBUpdateZip

Klasse · Quelle: [updater/zip.php](../../PHP/gbdb_framework/updater/zip.php#L16)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `entries()` | `public static function entries(string $file, array $limits = []) :array` | [Zeile 28](../../PHP/gbdb_framework/updater/zip.php#L28) |
| `read()` | `public static function read(string $file, array $entry) :string` | [Zeile 154](../../PHP/gbdb_framework/updater/zip.php#L154) |
| `extractTo()` | `public static function extractTo(string $file, string $target, array $limits = []) :array` | [Zeile 190](../../PHP/gbdb_framework/updater/zip.php#L190) |
| `checkPath()` | `public static function checkPath(string $path) :void` | [Zeile 225](../../PHP/gbdb_framework/updater/zip.php#L225) |

## GBDBUpdateZipWriter

Klasse · Quelle: [updater/zip_writer.php](../../PHP/gbdb_framework/updater/zip_writer.php#L11)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__construct()` | `public function __construct(private readonly string $file, private readonly ?int $fixedTime = null)` | [Zeile 25](../../PHP/gbdb_framework/updater/zip_writer.php#L25) |
| `addContent()` | `public function addContent(string $name, string $content, ?int $mtime = null) :void` | [Zeile 35](../../PHP/gbdb_framework/updater/zip_writer.php#L35) |
| `addFile()` | `public function addFile(string $name, string $path) :void` | [Zeile 78](../../PHP/gbdb_framework/updater/zip_writer.php#L78) |
| `close()` | `public function close() :array` | [Zeile 97](../../PHP/gbdb_framework/updater/zip_writer.php#L97) |
| `abort()` | `public function abort() :void` | [Zeile 109](../../PHP/gbdb_framework/updater/zip_writer.php#L109) |

