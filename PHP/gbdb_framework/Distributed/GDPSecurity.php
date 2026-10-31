<?php

declare(strict_types=1);

/**
 * Knotenauthentifizierung (Konzept Kapitel 19).
 *
 * GDP1-HMAC-SHA256 über Methode, kanonischen Pfad, Body-Hash, Knoten,
 * Schlüssel-ID, Zeitstempel und Nonce. Jeder Knoten hat eigene, zufällige
 * 256-Bit-Geheimnisse. Auch Antworten werden signiert und an die Nonce der
 * Anfrage gebunden, sodass ein Replikat nur echte Hub-Antworten verarbeitet.
 */
final class GDPAuthenticator {
    public const SCHEME = 'GDP1-HMAC-SHA256';

    public static function stringToSign(string $method, string $path, string $body, string $nodeId, string $keyId, int $timestamp, string $nonce) :string {
        return implode("\n", [self::SCHEME, strtoupper($method), $path, hash('sha256', $body), $nodeId, $keyId, (string) $timestamp, $nonce, GDPConfig::PROTOCOL]);
    }

    public static function sign(string $secret, string $data) :string {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $secret, true)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, string> Header einer ausgehenden Anfrage.
     */
    public static function requestHeaders(string $method, string $path, string $body, string $nodeId, string $keyId, string $secret) :array {
        $timestamp = time();
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');

        return [
            'X-GDP-Protocol' => GDPConfig::PROTOCOL,
            'X-GDP-Node' => $nodeId,
            'X-GDP-Key' => $keyId,
            'X-GDP-Timestamp' => (string) $timestamp,
            'X-GDP-Nonce' => $nonce,
            'X-GDP-Signature' => self::sign($secret, self::stringToSign($method, $path, $body, $nodeId, $keyId, $timestamp, $nonce)),
        ];
    }

    /**
     * Prüft eine eingehende Anfrage. Liefert node_id und key_id.
     *
     * @param array<string, string> $headers Kleingeschriebene Headernamen.
     * @return array{node_id:string, key_id:string, nonce:string, secret:string}
     */
    public static function verifyRequest(string $method, string $path, string $body, array $headers) :array {
        $nodeId = (string) ($headers['x-gdp-node'] ?? '');
        $keyId = (string) ($headers['x-gdp-key'] ?? '');
        $timestamp = (string) ($headers['x-gdp-timestamp'] ?? '');
        $nonce = (string) ($headers['x-gdp-nonce'] ?? '');
        $signature = (string) ($headers['x-gdp-signature'] ?? '');
        $fail = static fn(string $reason) => throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Authentifizierung fehlgeschlagen.', ['reason' => $reason, 'node_id' => GDPCanonical::isIdentifier($nodeId) ? $nodeId : '']);

        if (($headers['x-gdp-protocol'] ?? '') !== GDPConfig::PROTOCOL) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Nicht unterstützte Protokollversion.', ['supported' => GDPConfig::PROTOCOL]);
        }

        if (!GDPCanonical::isIdentifier($nodeId) || !preg_match('/^[a-zA-Z0-9_-]{8,64}$/D', $keyId) || !ctype_digit($timestamp) || !preg_match('/^[A-Za-z0-9_-]{16,64}$/D', $nonce) || $signature === '') {
            $fail('malformed_headers');
        }

        $skew = GDPConfig::int('clock_skew_seconds', 30, 3600);

        if (abs(time() - (int) $timestamp) > $skew) {
            $fail('clock_skew');
        }

        $credential = GDPCredentials::peerKey($nodeId, $keyId);

        if ($credential === null) {
            $fail('unknown_key');
        }

        $expected = self::sign($credential['secret'], self::stringToSign($method, $path, $body, $nodeId, $keyId, (int) $timestamp, $nonce));

        if (!hash_equals($expected, $signature)) {
            $fail('bad_signature');
        }

        // Erst nach gültiger Signatur speichern: Fremde können den Cache nicht füllen.
        if (!GDPNonceCache::remember($nodeId, $nonce, 2 * $skew)) {
            $fail('replay');
        }

        return ['node_id' => $nodeId, 'key_id' => $keyId, 'nonce' => $nonce, 'secret' => $credential['secret'], 'status' => $credential['status']];
    }

    public static function responseSignature(string $secret, string $requestNonce, string $body) :string {
        return self::sign($secret, implode("\n", [self::SCHEME . '-RESPONSE', $requestNonce, hash('sha256', $body)]));
    }

    /**
     * Schlüsselübergabe beim Pairing: AES-256-GCM, Schlüssel aus dem Pairing-Code abgeleitet.
     */
    public static function wrap(string $key, string $plain, string $associated) :string {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $associated);

        if ($cipher === false) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'OpenSSL mit AES-256-GCM wird für das Pairing benötigt.');
        }

        return base64_encode($iv . $tag . $cipher);
    }

    public static function unwrap(string $key, string $wrapped, string $associated) :string {
        $raw = base64_decode($wrapped, true);
        $plain = $raw === false || strlen($raw) < 29 ? false : openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $associated);

        if ($plain === false) {
            throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Schlüsselübergabe konnte nicht geprüft werden.');
        }

        return $plain;
    }

    public static function pairingKey(string $secret) :string {
        return hash_hkdf('sha256', $secret, 32, 'gdp1-pairing');
    }
}

/**
 * Verwaltung der Knotenschlüssel im GDP-Tresor (Kapitel 19/20).
 */
final class GDPCredentials {
    public const ACTIVE = 'active';
    public const PENDING = 'pending';
    public const ROTATING = 'rotating';

    public static function newSecret() :string {
        return bin2hex(random_bytes(32));
    }

    public static function newKeyId() :string {
        return 'k' . bin2hex(random_bytes(8));
    }

    /**
     * Hub: Schlüssel eines Replikats, sofern aktiv, ausstehend oder in Rotation.
     */
    public static function peerKey(string $nodeId, string $keyId) :?array {
        $keys = GDPVault::all()['peers'][$nodeId] ?? [];
        $key = $keys[$keyId] ?? null;

        if (!is_array($key) || !in_array($key['status'] ?? '', [self::ACTIVE, self::PENDING, self::ROTATING], true)) {
            return null;
        }

        if (($key['expires_at'] ?? 0) > 0 && $key['expires_at'] < time()) {
            return null;
        }

        return $key;
    }

    public static function storePeerKey(string $nodeId, string $keyId, string $secret, string $status, int $expiresAt = 0) :void {
        GDPVault::update(static function (array &$data) use ($nodeId, $keyId, $secret, $status, $expiresAt) :void {
            $data['peers'][$nodeId][$keyId] = ['secret' => $secret, 'status' => $status, 'created_at' => time(), 'expires_at' => $expiresAt];
        });
    }

    public static function setPeerKeyStatus(string $nodeId, string $keyId, string $status) :void {
        GDPVault::update(static function (array &$data) use ($nodeId, $keyId, $status) :void {
            if (isset($data['peers'][$nodeId][$keyId])) {
                $data['peers'][$nodeId][$keyId]['status'] = $status;
                $data['peers'][$nodeId][$keyId]['expires_at'] = 0;
            }
        });
    }

    /**
     * Entzieht alle Schlüssel eines Knotens (Widerruf) oder alle außer einem (Rotation).
     */
    public static function revokePeerKeys(string $nodeId, string $exceptKeyId = '') :int {
        return (int) GDPVault::update(static function (array &$data) use ($nodeId, $exceptKeyId) :int {
            $count = 0;

            foreach (array_keys($data['peers'][$nodeId] ?? []) as $keyId) {
                if ($keyId !== $exceptKeyId) {
                    unset($data['peers'][$nodeId][$keyId]);
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Öffentliche Sicht ohne Geheimnis.
     */
    public static function peerKeys(string $nodeId) :array {
        $result = [];

        foreach (GDPVault::all()['peers'][$nodeId] ?? [] as $keyId => $key) {
            $result[] = ['key_id' => $keyId, 'status' => $key['status'], 'created_at' => $key['created_at'], 'expires_at' => $key['expires_at']];
        }

        return $result;
    }

    /**
     * Replikat: eigener Schlüssel gegenüber dem Hub.
     */
    public static function hubKey() :?array {
        $hub = GDPVault::all()['hub'] ?? null;

        return is_array($hub) && isset($hub['key_id'], $hub['secret']) ? $hub : null;
    }

    public static function storeHubKey(array $hub) :void {
        GDPVault::update(static function (array &$data) use ($hub) :void {
            $data['hub'] = $hub;
        });
    }

    public static function clearHubKey() :void {
        GDPVault::update(static function (array &$data) :void {
            unset($data['hub']);
        });
    }
}

/**
 * Einmalige, zeitlich begrenzte Pairing-Freigaben (Kapitel 19).
 */
final class GDPPairing {
    public const CODE_PREFIX = 'GDP1';

    /**
     * Hub: neue Freigabe. Der Code wird nur dieses eine Mal angezeigt.
     *
     * @return array{code:string, pairing_id:string, expires_at:int}
     */
    public static function create(array $instances, string $role, array $permissions, bool $requireApproval, ?array $scope = null) :array {
        $role = GDPNodeRole::tryFrom($role);

        if ($role === null || $role === GDPNodeRole::HUB) {
            throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Pairing ist nur für Replica oder Observer möglich.');
        }

        foreach ($instances as $instanceId) {
            if (!is_string($instanceId) || !GDPInstanceRegistry::replicated($instanceId)) {
                throw new GDPPolicyViolationException(GDPErrorCode::POLICY_VIOLATION, 'Instanz ist auf diesem Hub nicht zur Replikation freigegeben: ' . (string) $instanceId);
            }
        }

        if ($role === GDPNodeRole::OBSERVER) {
            $permissions = array_values(array_diff($permissions, ['push']));
        }

        $pairingId = 'p' . bin2hex(random_bytes(6));
        $secret = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $expires = time() + GDPConfig::int('pairing_ttl', 60, 86400);
        GDPVault::update(static function (array &$data) use ($pairingId, $secret, $expires, $instances, $role, $permissions, $requireApproval, $scope) :void {
            foreach ($data['pairing'] ?? [] as $id => $session) {
                if (($session['expires_at'] ?? 0) < time() - 86400) {
                    unset($data['pairing'][$id]);
                }
            }

            $data['pairing'][$pairingId] = [
                'key' => base64_encode(GDPAuthenticator::pairingKey($secret)),
                'instances' => array_values($instances),
                'role' => $role->value,
                'permissions' => array_values(array_intersect(GDPGrants::PERMISSIONS, $permissions)),
                'scope' => $scope,
                'administrator_approval' => $requireApproval,
                'expires_at' => $expires,
                'used_at' => 0,
                'node_id' => '',
                'created_at' => time(),
            ];
        });
        GDPAuditLogger::log('pairing.created', ['pairing_id' => $pairingId, 'instances' => $instances, 'role' => $role->value, 'expires_at' => $expires]);

        return ['code' => self::CODE_PREFIX . '-' . $pairingId . '-' . $secret, 'pairing_id' => $pairingId, 'expires_at' => $expires];
    }

    /**
     * @return array{pairing_id:string, secret:string}
     */
    public static function parse(string $code) :array {
        if (!preg_match('/^GDP1-(p[0-9a-f]{12})-([A-Za-z0-9_-]{32})$/D', trim($code), $matches)) {
            throw new GDPAuthorizationException(GDPErrorCode::PAIRING_EXPIRED, 'Pairing-Code hat ein ungültiges Format.');
        }

        return ['pairing_id' => $matches[1], 'secret' => $matches[2]];
    }

    public static function session(string $pairingId) :?array {
        $session = GDPVault::all()['pairing'][$pairingId] ?? null;

        return is_array($session) ? $session : null;
    }

    /**
     * Öffentliche Liste für die UI, ohne Schlüsselmaterial.
     */
    public static function sessions() :array {
        $result = [];

        foreach (GDPVault::all()['pairing'] ?? [] as $pairingId => $session) {
            unset($session['key']);
            $result[$pairingId] = $session;
        }

        return $result;
    }

    public static function update(string $pairingId, array $values) :void {
        GDPVault::update(static function (array &$data) use ($pairingId, $values) :void {
            if (isset($data['pairing'][$pairingId])) {
                $data['pairing'][$pairingId] = array_replace($data['pairing'][$pairingId], $values);
            }
        });
    }

    public static function revoke(string $pairingId) :void {
        self::update($pairingId, ['expires_at' => 0]);
        GDPAuditLogger::log('pairing.revoked', ['pairing_id' => $pairingId]);
    }
}
