<?php

declare(strict_types=1);

/** Fehler der Vertrauensprüfung mit Ergebniszustand (z. B. INVALID_SIGNATURE). */
final class GBDBUpdateTrustException extends RuntimeException {
    public function __construct(public readonly string $status, string $message, public readonly string $keyId = '') {
        parent::__construct($message);
    }
}

/**
 * Release-Vertrauenskette (Konzept Abschnitt 5): Root-Anker → trust.json → Release-Schlüssel
 * → releases.json → Manifest → ZIP. Reine Prüflogik ohne Netzwerk und ohne Dateizugriffe;
 * Zustände kommen als Arrays herein und gehen als Arrays heraus.
 *
 * Diese Datei ist identisch im Release-Manager enthalten (lib/shared), der damit jede
 * Veröffentlichung vor der Freigabe genauso prüft wie ein Framework.
 */
final class GBDBUpdateTrust {
    public const VERIFIED = 'VERIFIED';
    public const VERIFIED_API_UNAVAILABLE = 'VERIFIED_API_UNAVAILABLE';
    public const INVALID_SIGNATURE = 'INVALID_SIGNATURE';
    public const UNTRUSTED_KEY = 'UNTRUSTED_KEY';
    public const REVOKED_KEY = 'REVOKED_KEY';
    public const INVALID_CHECKSUM = 'INVALID_CHECKSUM';
    public const EXPIRED_METADATA = 'EXPIRED_METADATA';
    public const ROLLBACK_DETECTED = 'ROLLBACK_DETECTED';
    public const INCOMPATIBLE = 'INCOMPATIBLE';
    // Technische Zusatzzustände: alle blockieren die Installation.
    public const INVALID_METADATA = 'INVALID_METADATA';
    public const NO_TRUST_ANCHOR = 'NO_TRUST_ANCHOR';
    public const CRYPTO_UNAVAILABLE = 'CRYPTO_UNAVAILABLE';
    public const CLOCK_INVALID = 'CLOCK_INVALID';
    public const API_REJECTED = 'API_REJECTED';
    public const RELEASE_REVOKED = 'RELEASE_REVOKED';

    public const SPEC = 1;
    public const MAX_TRUST_BYTES = 262144;
    public const MAX_CATALOG_BYTES = 4194304;
    public const MAX_MANIFEST_BYTES = 16777216;
    /** Nur für die Plausibilität der Systemzeit (Ausstellungsdaten), nie für Schlüsselgültigkeit. */
    public const CLOCK_TOLERANCE = 86400;
    /** Gültigkeitsbeginn eines Schlüssels: höchstens 5 Minuten Uhrenabweichung. */
    public const KEY_START_TOLERANCE = 300;
    public const MAX_ROTATIONS = 50;

    private function __construct() {
    }

    // ------------------------------------------------------------------ Hilfen

    public static function isVersion(mixed $value) :bool {
        return is_string($value) && preg_match('/^(0|[1-9]\d{0,5})\.(0|[1-9]\d{0,5})\.(0|[1-9]\d{0,5})$/D', $value) === 1;
    }

    public static function compareVersions(string $left, string $right) :int {
        return version_compare($left, $right);
    }

    /** UTC-Zeitpunkt im Format 2026-11-02T20:00:00Z. */
    public static function parseTime(mixed $value, string $field) :int {
        $parsed = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC')) : false;

        if ($parsed === false || $parsed->format('Y-m-d\TH:i:s\Z') !== $value) {
            throw new GBDBUpdateTrustException(self::INVALID_METADATA, $field . ' ist kein gültiger UTC-Zeitpunkt.');
        }

        return $parsed->getTimestamp();
    }

    public static function formatTime(int $time) :string {
        return gmdate('Y-m-d\TH:i:s\Z', $time);
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeJson(string $bytes, int $limit, string $what) :array {
        if (strlen($bytes) > $limit) {
            throw new GBDBUpdateTrustException(self::INVALID_METADATA, $what . ' ist zu groß.');
        }

        try {
            $data = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GBDBUpdateTrustException(self::INVALID_METADATA, $what . ' ist kein gültiges JSON.');
        }

        if (!is_array($data) || array_is_list($data)) {
            throw new GBDBUpdateTrustException(self::INVALID_METADATA, $what . ' hat einen ungültigen Aufbau.');
        }

        return $data;
    }

    private static function requireSodium() :void {
        if (!GBDBUpdateSignature::available()) {
            throw new GBDBUpdateTrustException(self::CRYPTO_UNAVAILABLE, 'Die PHP-Erweiterung sodium fehlt; Signaturen können nicht geprüft werden.');
        }
    }

    private static function fail(string $status, string $message, string $keyId = '') :never {
        throw new GBDBUpdateTrustException($status, $message, $keyId);
    }

    private static function int(mixed $value, string $field, int $min = 0) :int {
        if (!is_int($value) || $value < $min) {
            self::fail(self::INVALID_METADATA, $field . ' muss eine ganze Zahl ab ' . $min . ' sein.');
        }

        return $value;
    }

    /**
     * Schlüsselliste [{key_id, public_key}] → [ID => Rohschlüssel].
     *
     * @return array<string, string>
     */
    public static function keySet(mixed $list, string $field) :array {
        if (!is_array($list) || !array_is_list($list) || $list === [] || count($list) > 16) {
            self::fail(self::INVALID_METADATA, $field . ' enthält keine gültige Schlüsselliste.');
        }

        $keys = [];

        foreach ($list as $entry) {
            if (!is_array($entry) || !is_string($entry['key_id'] ?? null) || isset($keys[$entry['key_id']])) {
                self::fail(self::INVALID_METADATA, $field . ' enthält einen ungültigen oder doppelten Schlüssel.');
            }

            try {
                $raw = GBDBUpdateSignature::decodePublicKey($entry['public_key'] ?? null);
            } catch (InvalidArgumentException $exception) {
                self::fail(self::INVALID_METADATA, $field . ': ' . $exception->getMessage());
            }

            if (!GBDBUpdateSignature::keyIdMatches($entry['key_id'], $raw)) {
                self::fail(self::INVALID_METADATA, $field . ': Schlüssel-ID ' . $entry['key_id'] . ' passt nicht zum Schlüssel.');
            }

            $keys[$entry['key_id']] = $raw;
        }

        return $keys;
    }

    /**
     * @param array<string, string> $keys
     * @return list<array{key_id: string, public_key: string}>
     */
    public static function keyList(array $keys) :array {
        $list = [];

        foreach ($keys as $id => $raw) {
            $list[] = ['key_id' => (string) $id, 'public_key' => base64_encode($raw)];
        }

        return $list;
    }

    private static function threshold(mixed $value, int $keyCount, string $field) :int {
        $threshold = self::int($value, $field, 1);

        if ($threshold > $keyCount) {
            self::fail(self::INVALID_METADATA, $field . ' ist höher als die Zahl der Schlüssel.');
        }

        return $threshold;
    }

    // ------------------------------------------------------------------ Schritt 1: Vertrauensanker

    /**
     * Fest integrierter Anker (updater/trust_anchor.json) → vertrauenswürdiger Ausgangszustand.
     *
     * @return array{root_keys: array<string, string>, root_threshold: int, recovery_keys: array<string, string>, recovery_threshold: int, trust_version: int, trust_hash: string}
     */
    public static function anchorState(string $bytes) :array {
        $anchor = self::decodeJson($bytes, 65536, 'Der Vertrauensanker');

        if (($anchor['type'] ?? null) !== 'gbdb-trust-anchor' || ($anchor['spec'] ?? null) !== self::SPEC) {
            self::fail(self::NO_TRUST_ANCHOR, 'Der Vertrauensanker hat ein unbekanntes Format.');
        }

        if (($anchor['root_keys'] ?? []) === []) {
            self::fail(self::NO_TRUST_ANCHOR, 'Diese Installation enthält noch keinen Root-Schlüssel. Ein offizielles Release vom Update-Server installieren.');
        }

        $root = self::keySet($anchor['root_keys'], 'Anker-Root-Schlüssel');
        $recovery = ($anchor['recovery_keys'] ?? []) === [] ? [] : self::keySet($anchor['recovery_keys'], 'Anker-Recovery-Schlüssel');

        return [
            'root_keys' => $root,
            'root_threshold' => self::threshold($anchor['root_threshold'] ?? null, count($root), 'root_threshold'),
            'recovery_keys' => $recovery,
            'recovery_threshold' => $recovery === [] ? 0 : self::threshold($anchor['recovery_threshold'] ?? null, count($recovery), 'recovery_threshold'),
            'trust_version' => self::int($anchor['trust_version'] ?? 0, 'trust_version'),
            'trust_hash' => '',
        ];
    }

    // ------------------------------------------------------------------ Schritt 2 und 3: Vertrauensmetadaten

    /**
     * trust.json strukturell lesen (ohne Signaturprüfung).
     *
     * @return array<string, mixed>
     */
    public static function parseTrust(string $bytes) :array {
        $trust = self::decodeJson($bytes, self::MAX_TRUST_BYTES, 'trust.json');

        if (($trust['type'] ?? null) !== 'gbdb-trust' || ($trust['spec'] ?? null) !== self::SPEC || ($trust['algorithm'] ?? null) !== 'Ed25519') {
            self::fail(self::INVALID_METADATA, 'trust.json hat einen unbekannten Typ, Aufbau oder Algorithmus.');
        }

        $trust['_version'] = self::int($trust['version'] ?? null, 'trust.version', 1);
        $trust['_issued'] = self::parseTime($trust['issued_at'] ?? null, 'trust.issued_at');
        $trust['_expires'] = self::parseTime($trust['expires'] ?? null, 'trust.expires');
        $trust['_root'] = self::keySet($trust['root']['keys'] ?? null, 'trust.root');
        $trust['_root_threshold'] = self::threshold($trust['root']['threshold'] ?? null, count($trust['_root']), 'trust.root.threshold');
        $recovery = $trust['recovery']['keys'] ?? [];
        $trust['_recovery'] = $recovery === [] ? [] : self::keySet($recovery, 'trust.recovery');
        $trust['_recovery_threshold'] = $recovery === [] ? 0 : self::threshold($trust['recovery']['threshold'] ?? null, count($trust['_recovery']), 'trust.recovery.threshold');
        $history = $trust['root_history'] ?? [];

        if (!is_array($history) || !array_is_list($history) || count($history) > self::MAX_ROTATIONS) {
            self::fail(self::INVALID_METADATA, 'trust.root_history ist ungültig.');
        }

        $previous = 0;

        foreach ($history as $entry) {
            $version = self::int(is_array($entry) ? ($entry['version'] ?? null) : null, 'root_history.version', 1);

            if ($version <= $previous || $version > $trust['_version']) {
                self::fail(self::INVALID_METADATA, 'trust.root_history ist nicht aufsteigend.');
            }

            $previous = $version;
        }

        $trust['_history'] = array_map(static fn(array $entry) :int => $entry['version'], $history);
        $thresholds = $trust['thresholds'] ?? null;

        if (!is_array($thresholds)) {
            self::fail(self::INVALID_METADATA, 'trust.thresholds fehlt.');
        }

        $trust['_thresholds'] = ['release' => self::int($thresholds['release'] ?? null, 'thresholds.release', 1), 'catalog' => self::int($thresholds['catalog'] ?? null, 'thresholds.catalog', 1)];
        $trust['_release_keys'] = self::parseReleaseKeys($trust['release_keys'] ?? null, array_merge(array_values($trust['_root']), array_values($trust['_recovery'])));

        return $trust;
    }

    /**
     * @param list<string> $forbidden Rohschlüssel (Root/Recovery), die kein Release-Schlüssel sein dürfen
     * @return array<string, array{public: string, from: int, until: int, revoked: bool, roles: list<string>, reason: string}>
     */
    private static function parseReleaseKeys(mixed $list, array $forbidden) :array {
        if (!is_array($list) || !array_is_list($list) || count($list) > 64) {
            self::fail(self::INVALID_METADATA, 'trust.release_keys ist ungültig.');
        }

        $keys = [];

        foreach ($list as $entry) {
            if (!is_array($entry)) {
                self::fail(self::INVALID_METADATA, 'trust.release_keys enthält einen ungültigen Eintrag.');
            }

            $id = $entry['key_id'] ?? null;
            $raw = self::keySet([['key_id' => $id, 'public_key' => $entry['public_key'] ?? null]], 'trust.release_keys')[$id];
            $roles = $entry['roles'] ?? null;

            if (isset($keys[$id]) || in_array($raw, $forbidden, true)) {
                self::fail(self::INVALID_METADATA, 'Release-Schlüssel ' . $id . ' ist doppelt oder zugleich Root-Schlüssel.');
            }

            if (!is_array($roles) || $roles === [] || array_diff($roles, ['release', 'catalog']) !== [] || !is_bool($entry['revoked'] ?? null)) {
                self::fail(self::INVALID_METADATA, 'Release-Schlüssel ' . $id . ' hat ungültige Rollen oder Widerrufsangaben.');
            }

            $keys[$id] = [
                'public' => $raw,
                'from' => self::parseTime($entry['valid_from'] ?? null, $id . '.valid_from'),
                'until' => self::parseTime($entry['valid_until'] ?? null, $id . '.valid_until'),
                'revoked' => $entry['revoked'],
                'roles' => array_values($roles),
                'reason' => is_string($entry['revocation_reason'] ?? null) ? $entry['revocation_reason'] : '',
            ];
        }

        return $keys;
    }

    /**
     * Ein Schritt der Kette: Kandidat gegen den bisher vertrauenswürdigen Zustand.
     * Gleicher Root-Satz → Root-Signaturen genügen. Neuer Root-Satz → bisheriger Root (oder
     * Recovery-Schlüssel) UND Besitznachweis aller neuen Root-Schlüssel bis zur Schwelle.
     *
     * @param array<string, mixed> $trusted
     * @param array<string, mixed> $candidate geparste trust.json
     * @param array<string, string> $signatures
     * @return array<string, mixed> neuer vertrauenswürdiger Zustand
     */
    private static function acceptStep(array $trusted, array $candidate, string $bytes, array $signatures, bool $current) :array {
        $context = GBDBUpdateSignature::CONTEXT_TRUST;
        $byOldRoot = count(GBDBUpdateSignature::validSigners($context, $bytes, $signatures, $trusted['root_keys']));
        $byRecovery = $trusted['recovery_keys'] === [] ? 0 : count(GBDBUpdateSignature::validSigners($context, $bytes, $signatures, $trusted['recovery_keys']));
        $byNewRoot = count(GBDBUpdateSignature::validSigners($context, $bytes, $signatures, $candidate['_root']));
        $oldRootOk = $byOldRoot >= $trusted['root_threshold'];
        $recoveryOk = $trusted['recovery_threshold'] > 0 && $byRecovery >= $trusted['recovery_threshold'];
        $sameRoot = $candidate['_root'] == $trusted['root_keys'] && $candidate['_root_threshold'] === $trusted['root_threshold'];
        $sameRecovery = $candidate['_recovery'] == $trusted['recovery_keys'] && $candidate['_recovery_threshold'] === $trusted['recovery_threshold'];

        if ($sameRoot) {
            if (!$oldRootOk && !$recoveryOk) {
                $unknown = array_diff(array_keys($signatures), array_keys($trusted['root_keys']), array_keys($trusted['recovery_keys']));

                self::fail($unknown !== [] && $byOldRoot === 0 ? self::UNTRUSTED_KEY : self::INVALID_SIGNATURE,
                    'trust.json ist nicht gültig mit dem vertrauenswürdigen Root-Schlüssel signiert.', (string) (reset($unknown) ?: ''));
            }
        } else {
            if ($candidate['_version'] <= $trusted['trust_version']) {
                self::fail(self::ROLLBACK_DETECTED, 'Root-Wechsel ohne höhere Versionsnummer der Vertrauensmetadaten.');
            }

            if (!$oldRootOk && !$recoveryOk) {
                self::fail(self::UNTRUSTED_KEY, 'Neuer Root-Schlüssel ist weder vom bisherigen Root- noch von einem Recovery-Schlüssel bestätigt.');
            }

            if ($byNewRoot < $candidate['_root_threshold']) {
                self::fail(self::INVALID_SIGNATURE, 'Der neue Root-Schlüssel hat seinen Besitz nicht durch eine eigene Signatur nachgewiesen.');
            }
        }

        // Recovery-Schlüssel lassen sich nur mit den bisherigen Recovery-Schlüsseln ändern.
        if (!$sameRecovery && $trusted['recovery_keys'] !== [] && !$recoveryOk) {
            self::fail(self::UNTRUSTED_KEY, 'Änderung der Recovery-Schlüssel ist nicht von den bisherigen Recovery-Schlüsseln bestätigt.');
        }

        return [
            'root_keys' => $candidate['_root'],
            'root_threshold' => $candidate['_root_threshold'],
            'recovery_keys' => $candidate['_recovery'],
            'recovery_threshold' => $candidate['_recovery_threshold'],
            'trust_version' => $candidate['_version'],
            'trust_hash' => $current ? hash('sha256', $bytes) : '',
        ];
    }

    /**
     * trust.json vollständig prüfen, einschließlich Root-Rotationen und Rollback-Schutz.
     *
     * @param array<string, mixed> $trusted vertrauenswürdiger Zustand (anchorState() oder gespeichert)
     * @param callable(int): array{0: string, 1: string} $history liefert [trust.<v>.json, trust.<v>.json.sig]
     * @return array{trust: array<string, mixed>, state: array<string, mixed>, rotated: bool}
     */
    public static function verifyTrust(string $bytes, string $signatureFile, array $trusted, int $now, callable $history, bool $rejectExpired = true) :array {
        self::requireSodium();
        $candidate = self::parseTrust($bytes);

        try {
            $signatures = GBDBUpdateSignature::parseFile($signatureFile, GBDBUpdateSignature::CONTEXT_TRUST);
        } catch (InvalidArgumentException $exception) {
            self::fail(self::INVALID_SIGNATURE, 'trust.json.sig: ' . $exception->getMessage());
        }

        if ($candidate['_version'] < $trusted['trust_version']) {
            self::fail(self::ROLLBACK_DETECTED, 'trust.json Version ' . $candidate['_version'] . ' ist älter als die bereits bestätigte Version ' . $trusted['trust_version'] . '.');
        }

        if ($candidate['_version'] === $trusted['trust_version'] && $trusted['trust_hash'] !== '' && !hash_equals($trusted['trust_hash'], hash('sha256', $bytes))) {
            self::fail(self::ROLLBACK_DETECTED, 'trust.json hat dieselbe Versionsnummer wie der bestätigte Stand, aber anderen Inhalt.');
        }

        if ($candidate['_issued'] > $now + self::CLOCK_TOLERANCE) {
            self::fail(self::CLOCK_INVALID, 'Die Systemzeit liegt vor dem Ausstellungsdatum der Vertrauensmetadaten. Uhrzeit des Servers prüfen.');
        }

        $start = $trusted['trust_version'];
        $rotated = false;

        // Zwischenschritte: jeder archivierte Root-Wechsel zwischen bestätigtem und aktuellem Stand.
        foreach ($candidate['_history'] as $version) {
            if ($version <= $start || $version >= $candidate['_version']) {
                continue;
            }

            [$stepBytes, $stepSignature] = $history($version);
            $step = self::parseTrust($stepBytes);

            if ($step['_version'] !== $version) {
                self::fail(self::INVALID_METADATA, 'Archivierte trust.' . $version . '.json trägt eine andere Versionsnummer.');
            }

            try {
                $stepSignatures = GBDBUpdateSignature::parseFile($stepSignature, GBDBUpdateSignature::CONTEXT_TRUST);
            } catch (InvalidArgumentException $exception) {
                self::fail(self::INVALID_SIGNATURE, 'trust.' . $version . '.json.sig: ' . $exception->getMessage());
            }

            $before = $trusted['root_keys'];
            $trusted = self::acceptStep($trusted, $step, $stepBytes, $stepSignatures, false);
            $rotated = $rotated || $before != $trusted['root_keys'];
        }

        $before = $trusted['root_keys'];
        $state = self::acceptStep($trusted, $candidate, $bytes, $signatures, true);
        $rotated = $rotated || $before != $state['root_keys'];

        if ($rejectExpired && $candidate['_expires'] <= $now) {
            self::fail(self::EXPIRED_METADATA, 'Die Vertrauensmetadaten sind am ' . gmdate('d.m.Y H:i', $candidate['_expires']) . ' UTC abgelaufen.');
        }

        return ['trust' => $candidate, 'state' => $state, 'rotated' => $rotated];
    }

    /**
     * Status eines Release-Schlüssels für eine Rolle.
     *
     * @param array<string, mixed> $trust geparste trust.json
     */
    public static function keyStatus(array $trust, string $keyId, string $role, int $now) :string {
        $key = $trust['_release_keys'][$keyId] ?? null;

        if ($key === null || !in_array($role, $key['roles'], true)) {
            return self::UNTRUSTED_KEY;
        }

        if ($key['revoked']) {
            return self::REVOKED_KEY;
        }

        if ($now < $key['from'] - self::KEY_START_TOLERANCE || $now > $key['until']) {
            return self::UNTRUSTED_KEY;
        }

        return self::VERIFIED;
    }

    /**
     * Gültige Signaturen einer Rolle prüfen und die Schwelle durchsetzen.
     *
     * @param array<string, mixed> $trust
     * @param array<string, string> $signatures
     * @return list<string> gültige Schlüssel-IDs
     */
    private static function requireRoleSignatures(array $trust, string $role, string $context, string $bytes, array $signatures, int $now, string $what) :array {
        $valid = [];
        $problem = null;

        foreach ($signatures as $keyId => $signature) {
            $status = self::keyStatus($trust, (string) $keyId, $role, $now);

            if ($status !== self::VERIFIED) {
                $problem ??= [$status, (string) $keyId];

                continue;
            }

            if (!GBDBUpdateSignature::verify($context, $bytes, $signature, $trust['_release_keys'][$keyId]['public'])) {
                self::fail(self::INVALID_SIGNATURE, 'Die Signatur von ' . $what . ' (Schlüssel ' . $keyId . ') ist ungültig.', (string) $keyId);
            }

            $valid[] = (string) $keyId;
        }

        if (count($valid) < $trust['_thresholds'][$role]) {
            if ($problem !== null) {
                self::fail($problem[0], $what . ' ist mit einem ' . ($problem[0] === self::REVOKED_KEY ? 'widerrufenen' : 'nicht autorisierten') . ' Schlüssel signiert: ' . $problem[1], $problem[1]);
            }

            self::fail(self::UNTRUSTED_KEY, $what . ' hat nicht genügend gültige Signaturen.');
        }

        return $valid;
    }

    // ------------------------------------------------------------------ Schritt 4: Release-Katalog

    /**
     * @param array<string, mixed> $trust geprüfte trust.json
     * @param array{catalog_sequence: int, catalog_hash: string, highest_release_sequence: int} $seen
     * @return array<string, mixed> Katalog mit '_releases' (Version => Eintrag)
     */
    public static function verifyCatalog(string $bytes, string $signatureFile, array $trust, array $seen, int $now, bool $rejectExpired = true) :array {
        self::requireSodium();
        $catalog = self::decodeJson($bytes, self::MAX_CATALOG_BYTES, 'releases.json');

        try {
            $signatures = GBDBUpdateSignature::parseFile($signatureFile, GBDBUpdateSignature::CONTEXT_CATALOG);
        } catch (InvalidArgumentException $exception) {
            self::fail(self::INVALID_SIGNATURE, 'releases.json.sig: ' . $exception->getMessage());
        }

        if (($catalog['type'] ?? null) !== 'gbdb-catalog' || ($catalog['spec'] ?? null) !== self::SPEC) {
            self::fail(self::INVALID_METADATA, 'releases.json hat einen unbekannten Typ oder Aufbau.');
        }

        $catalog['_signers'] = self::requireRoleSignatures($trust, 'catalog', GBDBUpdateSignature::CONTEXT_CATALOG, $bytes, $signatures, $now, 'releases.json');
        $sequence = self::int($catalog['sequence'] ?? null, 'releases.sequence', 1);
        $created = self::parseTime($catalog['created_at'] ?? null, 'releases.created_at');
        $expires = self::parseTime($catalog['expires'] ?? null, 'releases.expires');
        $trustVersion = self::int($catalog['trust_version'] ?? null, 'releases.trust_version', 1);

        if ($trustVersion > $trust['_version']) {
            self::fail(self::INVALID_METADATA, 'releases.json verweist auf Vertrauensmetadaten, die neuer sind als trust.json.');
        }

        if ($sequence < $seen['catalog_sequence']) {
            self::fail(self::ROLLBACK_DETECTED, 'releases.json Sequenz ' . $sequence . ' ist älter als die bereits bestätigte Sequenz ' . $seen['catalog_sequence'] . '.');
        }

        if ($sequence === $seen['catalog_sequence'] && $seen['catalog_hash'] !== '' && !hash_equals($seen['catalog_hash'], hash('sha256', $bytes))) {
            self::fail(self::ROLLBACK_DETECTED, 'releases.json hat dieselbe Sequenz wie der bestätigte Stand, aber anderen Inhalt.');
        }

        if ($created > $now + self::CLOCK_TOLERANCE) {
            self::fail(self::CLOCK_INVALID, 'Die Systemzeit liegt vor dem Erstellungszeitpunkt des Release-Katalogs. Uhrzeit des Servers prüfen.');
        }

        if ($rejectExpired && $expires <= $now) {
            self::fail(self::EXPIRED_METADATA, 'Der Release-Katalog ist am ' . gmdate('d.m.Y H:i', $expires) . ' UTC abgelaufen.');
        }

        $releases = [];
        $sequences = [];
        $highest = 0;

        foreach (is_array($catalog['releases'] ?? null) && array_is_list($catalog['releases']) ? $catalog['releases'] : self::fail(self::INVALID_METADATA, 'releases.json enthält keine Release-Liste.') as $entry) {
            $version = is_array($entry) ? ($entry['version'] ?? null) : null;

            if (!self::isVersion($version) || isset($releases[$version])) {
                self::fail(self::INVALID_METADATA, 'releases.json enthält eine ungültige oder doppelte Version.');
            }

            $releaseSequence = self::int($entry['release_sequence'] ?? null, $version . '.release_sequence', 1);

            if (isset($sequences[$releaseSequence]) || ($entry['filename'] ?? null) !== 'v' . $version . '.zip' || ($entry['manifest'] ?? null) !== 'v' . $version . '.json'
                || !preg_match('/^[0-9a-f]{64}$/D', (string) ($entry['manifest_sha256'] ?? '')) || !preg_match('/^[0-9a-f]{64}$/D', (string) ($entry['sha256'] ?? ''))
                || !in_array($entry['status'] ?? null, ['published', 'revoked'], true) || !is_string($entry['signing_key_id'] ?? null)) {
                self::fail(self::INVALID_METADATA, 'releases.json: Eintrag ' . $version . ' ist widersprüchlich oder unvollständig.');
            }

            self::int($entry['size'] ?? null, $version . '.size', 1);
            $sequences[$releaseSequence] = true;
            $highest = max($highest, $releaseSequence);
            $releases[$version] = $entry;
        }

        if ($highest < $seen['highest_release_sequence']) {
            self::fail(self::ROLLBACK_DETECTED, 'releases.json kennt weniger Veröffentlichungen als bereits bestätigt (Sequenz ' . $highest . ' < ' . $seen['highest_release_sequence'] . ').');
        }

        $catalog['_releases'] = $releases;
        $catalog['_sequence'] = $sequence;
        $catalog['_expires'] = $expires;
        $catalog['_created'] = $created;
        $catalog['_hash'] = hash('sha256', $bytes);
        $catalog['_highest'] = $highest;

        return $catalog;
    }

    // ------------------------------------------------------------------ Schritt 5: Release-Manifest

    /**
     * @param array<string, mixed> $trust
     * @param array<string, mixed> $entry Katalogeintrag der Version
     * @return array<string, mixed>
     */
    public static function verifyManifest(string $bytes, string $signatureFile, array $trust, array $entry, int $now, bool $allowRevoked = false) :array {
        self::requireSodium();

        // Nur freigegebene Releases sind installierbar; zurückgezogene nur zur Neusignierung im Release-Manager.
        if (($entry['status'] ?? null) !== 'published' && !$allowRevoked) {
            self::fail(self::RELEASE_REVOKED, 'Version ' . ($entry['version'] ?? '?') . ' wurde vom Herausgeber zurückgezogen.');
        }

        if (!hash_equals((string) $entry['manifest_sha256'], hash('sha256', $bytes))) {
            self::fail(self::INVALID_METADATA, 'Das Manifest von ' . $entry['version'] . ' stimmt nicht mit dem signierten Release-Katalog überein.');
        }

        $manifest = self::decodeJson($bytes, self::MAX_MANIFEST_BYTES, 'Das Release-Manifest');

        try {
            $signatures = GBDBUpdateSignature::parseFile($signatureFile, GBDBUpdateSignature::CONTEXT_RELEASE);
        } catch (InvalidArgumentException $exception) {
            self::fail(self::INVALID_SIGNATURE, 'Manifest-Signatur: ' . $exception->getMessage());
        }

        $keyId = (string) ($manifest['signing_key_id'] ?? '');

        if ($keyId === '' || !isset($signatures[$keyId]) || $keyId !== $entry['signing_key_id']) {
            self::fail(self::UNTRUSTED_KEY, 'Das Manifest nennt keinen passenden Signaturschlüssel.', $keyId);
        }

        $status = self::keyStatus($trust, $keyId, 'release', $now);

        if ($status !== self::VERIFIED) {
            self::fail($status, 'Das Manifest ist mit einem ' . ($status === self::REVOKED_KEY ? 'widerrufenen' : 'nicht autorisierten') . ' Schlüssel signiert: ' . $keyId, $keyId);
        }

        $manifest['_signers'] = self::requireRoleSignatures($trust, 'release', GBDBUpdateSignature::CONTEXT_RELEASE, $bytes, $signatures, $now, 'Das Manifest');

        if (($manifest['type'] ?? null) !== 'gbdb-release' || ($manifest['manifest_version'] ?? null) !== 1) {
            self::fail(self::INVALID_METADATA, 'Das Manifest hat einen unbekannten Typ oder Aufbau.');
        }

        foreach (['version', 'release_sequence', 'filename', 'sha256', 'size'] as $field) {
            if (($manifest[$field] ?? null) !== ($entry[$field] ?? null)) {
                self::fail(self::INVALID_METADATA, 'Manifest und Release-Katalog widersprechen sich bei „' . $field . '“.');
            }
        }

        if (!self::isVersion($manifest['minimum_php'] ?? null) || !self::isVersion($manifest['minimum_gbdb'] ?? null) || !is_bool($manifest['requires_migration'] ?? null)
            || ($manifest['framework_dir'] ?? null) !== 'gbdb_framework') {
            self::fail(self::INVALID_METADATA, 'Das Manifest enthält ungültige Kompatibilitätsangaben.');
        }

        if (self::parseTime($manifest['published_at'] ?? null, 'published_at') > $now + self::CLOCK_TOLERANCE) {
            self::fail(self::CLOCK_INVALID, 'Die Systemzeit liegt vor dem Veröffentlichungsdatum des Releases. Uhrzeit des Servers prüfen.');
        }

        $files = $manifest['files'] ?? null;

        if (!is_array($files) || $files === [] || array_is_list($files)) {
            self::fail(self::INVALID_METADATA, 'Das Manifest enthält kein Dateimanifest.');
        }

        foreach ($files as $path => $info) {
            try {
                GBDBUpdateZip::checkPath((string) $path);
            } catch (InvalidArgumentException $exception) {
                self::fail(self::INVALID_METADATA, $exception->getMessage());
            }

            if (!is_array($info) || !preg_match('/^[0-9a-f]{64}$/D', (string) ($info['sha256'] ?? '')) || !is_int($info['size'] ?? null) || $info['size'] < 0) {
                self::fail(self::INVALID_METADATA, 'Dateimanifest: ungültiger Eintrag für ' . $path);
            }
        }

        foreach (['config_files', 'entry_files', 'removed_files'] as $list) {
            if (!is_array($manifest[$list] ?? null) || !array_is_list($manifest[$list])) {
                self::fail(self::INVALID_METADATA, 'Das Manifest enthält keine gültige Liste „' . $list . '“.');
            }
        }

        foreach (array_merge($manifest['config_files'], $manifest['entry_files']) as $path) {
            if (!is_string($path) || !isset($files[$path])) {
                self::fail(self::INVALID_METADATA, 'Das Manifest nennt eine Datei, die nicht im Dateimanifest steht.');
            }
        }

        foreach ($manifest['migrations'] ?? [] as $migration) {
            if (!is_array($migration) || !preg_match('/^[A-Za-z0-9_.-]{1,80}$/D', (string) ($migration['id'] ?? '')) || !isset($files[(string) ($migration['file'] ?? '')])) {
                self::fail(self::INVALID_METADATA, 'Das Manifest enthält eine ungültige Migrationsangabe.');
            }
        }

        $manifest['_hash'] = hash('sha256', $bytes);

        return $manifest;
    }

    // ------------------------------------------------------------------ Schritt 6: ZIP-Integrität

    /** @param array<string, mixed> $manifest */
    public static function verifyZip(string $file, array $manifest) :void {
        clearstatcache(true, $file);

        if (!is_file($file) || filesize($file) !== $manifest['size']) {
            self::fail(self::INVALID_CHECKSUM, 'Die Größe der heruntergeladenen ZIP stimmt nicht mit dem signierten Manifest überein.');
        }

        $hash = hash_file('sha256', $file);

        if ($hash === false || !hash_equals((string) $manifest['sha256'], $hash)) {
            self::fail(self::INVALID_CHECKSUM, 'Die SHA-256-Checksum der heruntergeladenen ZIP stimmt nicht mit dem signierten Manifest überein.');
        }

        // Archivinhalt gegen das Dateimanifest: nichts fehlt, nichts kommt hinzu, jede Datei
        // hat exakt den signierten SHA-256 (Einträge werden gelesen, CRC und Größe geprüft).
        $expected = $manifest['files'];

        try {
            $entries = GBDBUpdateZip::entries($file);
        } catch (InvalidArgumentException $exception) {
            self::fail(self::INVALID_CHECKSUM, 'Die ZIP ist strukturell ungültig: ' . $exception->getMessage());
        }

        foreach ($entries as $entry) {
            if (!isset($expected[$entry['name']]) || $expected[$entry['name']]['size'] !== $entry['size']) {
                self::fail(self::INVALID_CHECKSUM, 'Die ZIP enthält eine Datei, die nicht im signierten Dateimanifest steht: ' . $entry['name']);
            }

            try {
                $content = GBDBUpdateZip::read($file, $entry);
            } catch (InvalidArgumentException $exception) {
                self::fail(self::INVALID_CHECKSUM, $exception->getMessage());
            }

            if (!hash_equals((string) $expected[$entry['name']]['sha256'], hash('sha256', $content))) {
                self::fail(self::INVALID_CHECKSUM, 'Inhalt weicht vom signierten Dateimanifest ab: ' . $entry['name']);
            }

            unset($expected[$entry['name']]);
        }

        if ($expected !== []) {
            self::fail(self::INVALID_CHECKSUM, 'In der ZIP fehlen Dateien aus dem signierten Dateimanifest.');
        }
    }
}
