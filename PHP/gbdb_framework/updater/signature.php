<?php

declare(strict_types=1);

/**
 * Ed25519-Signaturen des Framework-Update-Systems (libsodium, keine eigene Kryptografie).
 *
 * Signiert wird immer: "gbdb-sig/1" NUL Kontext NUL exakter Byteinhalt der Datei.
 * Der Kontext trennt Vertrauensmetadaten, Katalog und Manifest, damit keine Signatur
 * für einen anderen Metadatentyp wiederverwendet werden kann.
 *
 * Signaturdatei (*.sig), UTF-8-JSON:
 * {"format":"gbdb-sig/1","context":"gbdb-trust/1","signatures":[{"key_id":"…","signature":"<Base64, 64 Byte>"}]}
 *
 * Öffentliche Schlüssel: Standard-Base64 der 32 Rohbytes. Schlüssel-ID:
 * "gbdb-<rolle>-<JJJJ>-<MM>-<erste 12 Hex-Zeichen von SHA-256 des Rohschlüssels>".
 * Die Endung bindet die ID an den Schlüssel; eine ID kann nicht auf einen fremden Schlüssel zeigen.
 *
 * Diese Datei ist identisch im Release-Manager enthalten (lib/shared).
 */
final class GBDBUpdateSignature {
    public const FORMAT = 'gbdb-sig/1';
    public const CONTEXT_TRUST = 'gbdb-trust/1';
    public const CONTEXT_CATALOG = 'gbdb-catalog/1';
    public const CONTEXT_RELEASE = 'gbdb-release/1';
    public const MAX_SIGNATURE_FILE = 65536;

    private function __construct() {
    }

    public static function available() :bool {
        return function_exists('sodium_crypto_sign_verify_detached') && function_exists('sodium_crypto_sign_detached') && defined('SODIUM_CRYPTO_SIGN_BYTES');
    }

    public static function message(string $context, string $bytes) :string {
        if (!in_array($context, [self::CONTEXT_TRUST, self::CONTEXT_CATALOG, self::CONTEXT_RELEASE], true)) {
            throw new InvalidArgumentException('Unbekannter Signaturkontext.');
        }

        return self::FORMAT . "\0" . $context . "\0" . $bytes;
    }

    /**
     * Neues Schlüsselpaar: [Schlüssel-ID, öffentlicher Schlüssel (Base64), geheimer Schlüssel (Base64)].
     *
     * @return array{key_id: string, public_key: string, secret_key: string}
     */
    public static function generate(string $role, ?int $time = null) :array {
        self::requireSodium();

        if (!preg_match('/^[a-z]{3,12}$/', $role)) {
            throw new InvalidArgumentException('Ungültige Schlüsselrolle.');
        }

        $pair = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($pair);
        $secret = sodium_crypto_sign_secretkey($pair);

        return [
            'key_id' => self::keyId($role, $public, $time ?? time()),
            'public_key' => base64_encode($public),
            'secret_key' => base64_encode($secret),
        ];
    }

    public static function keyId(string $role, string $publicRaw, int $time) :string {
        return 'gbdb-' . $role . '-' . gmdate('Y-m', $time) . '-' . substr(hash('sha256', $publicRaw), 0, 12);
    }

    /** Prüft Form der Schlüssel-ID und ihre Bindung an den öffentlichen Schlüssel. */
    public static function keyIdMatches(string $keyId, string $publicRaw) :bool {
        return preg_match('/^gbdb-[a-z]{3,12}-\d{4}-\d{2}-([0-9a-f]{12})$/D', $keyId, $match) === 1
            && hash_equals(substr(hash('sha256', $publicRaw), 0, 12), $match[1]);
    }

    public static function decodePublicKey(mixed $value) :string {
        $raw = is_string($value) ? base64_decode($value, true) : false;

        if ($raw === false || strlen($raw) !== 32 || base64_encode($raw) !== $value) {
            throw new InvalidArgumentException('Öffentlicher Schlüssel ist nicht korrekt kodiert.');
        }

        return $raw;
    }

    public static function decodeSecretKey(string $value) :string {
        $raw = base64_decode($value, true);

        if ($raw === false || strlen($raw) !== 64) {
            throw new InvalidArgumentException('Geheimer Schlüssel ist nicht korrekt kodiert.');
        }

        return $raw;
    }

    public static function sign(string $context, string $bytes, string $secretBase64) :string {
        self::requireSodium();
        $secret = self::decodeSecretKey($secretBase64);

        try {
            return sodium_crypto_sign_detached(self::message($context, $bytes), $secret);
        } finally {
            sodium_memzero($secret);
        }
    }

    public static function verify(string $context, string $bytes, string $signatureRaw, string $publicRaw) :bool {
        self::requireSodium();

        if (strlen($signatureRaw) !== 64 || strlen($publicRaw) !== 32) {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached($signatureRaw, self::message($context, $bytes), $publicRaw);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Signaturdatei erzeugen.
     *
     * @param list<array{key_id: string, secret_key: string}> $signers
     */
    public static function buildFile(string $context, string $bytes, array $signers) :string {
        $signatures = [];

        foreach ($signers as $signer) {
            $signatures[] = ['key_id' => $signer['key_id'], 'signature' => base64_encode(self::sign($context, $bytes, $signer['secret_key']))];
        }

        return json_encode(['format' => self::FORMAT, 'context' => $context, 'signatures' => $signatures], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * Signaturdatei lesen. Der erwartete Kontext kommt vom Prüfer, nicht aus der Datei.
     *
     * @return array<string, string> Schlüssel-ID => Rohsignatur
     */
    public static function parseFile(string $file, string $expectedContext) :array {
        if (strlen($file) > self::MAX_SIGNATURE_FILE) {
            throw new InvalidArgumentException('Signaturdatei ist zu groß.');
        }

        $data = json_decode($file, true, 8);

        if (!is_array($data) || ($data['format'] ?? null) !== self::FORMAT || ($data['context'] ?? null) !== $expectedContext || !is_array($data['signatures'] ?? null) || !array_is_list($data['signatures'])) {
            throw new InvalidArgumentException('Signaturdatei hat ein ungültiges Format.');
        }

        $result = [];

        foreach ($data['signatures'] as $entry) {
            $keyId = is_array($entry) ? ($entry['key_id'] ?? null) : null;
            $signature = is_array($entry) && is_string($entry['signature'] ?? null) ? base64_decode($entry['signature'], true) : false;

            if (!is_string($keyId) || $signature === false || strlen($signature) !== 64 || isset($result[$keyId]) || count($entry) !== 2) {
                throw new InvalidArgumentException('Signaturdatei enthält einen ungültigen Eintrag.');
            }

            $result[$keyId] = $signature;
        }

        if ($result === []) {
            throw new InvalidArgumentException('Signaturdatei enthält keine Signatur.');
        }

        return $result;
    }

    /**
     * Schlüssel-IDs, deren Signatur über die Bytes gültig ist.
     *
     * @param array<string, string> $signatures Schlüssel-ID => Rohsignatur
     * @param array<string, string> $keys Schlüssel-ID => Roh-Public-Key
     * @return list<string>
     */
    public static function validSigners(string $context, string $bytes, array $signatures, array $keys) :array {
        $valid = [];

        foreach ($signatures as $keyId => $signature) {
            if (isset($keys[$keyId]) && self::verify($context, $bytes, $signature, $keys[$keyId])) {
                $valid[] = (string) $keyId;
            }
        }

        return $valid;
    }

    private static function requireSodium() :void {
        if (!self::available()) {
            throw new RuntimeException('Die PHP-Erweiterung sodium (Ed25519) ist nicht verfügbar.');
        }
    }
}
