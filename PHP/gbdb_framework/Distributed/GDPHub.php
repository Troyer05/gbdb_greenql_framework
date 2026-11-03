<?php

declare(strict_types=1);

/**
 * Serverseitige GDP-Handler eines Hubs (Konzept Kapitel 15–17).
 */
final class GDPHubService {
    private const MAX_INSTANCES = 100;
    private const MAX_OPERATIONS = 1000;

    private static function assertHub() :void {
        if (GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Dieser Knoten ist kein Hub.');
        }
    }

    private static function instanceId(array $body) :string {
        $instanceId = $body['instance_id'] ?? null;

        if (!is_string($instanceId) || !GDPCanonical::isIdentifier($instanceId) || !GDPInstanceRegistry::replicated($instanceId)) {
            throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Instanz ist nicht freigegeben.');
        }

        return $instanceId;
    }

    private static function int(array $body, string $name, int $min = 0) :int {
        $value = $body[$name] ?? null;

        if (!is_int($value) || $value < $min) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, $name . ' muss eine ganze Zahl ≥ ' . $min . ' sein.');
        }

        return $value;
    }

    /**
     * Streams eines Replikats, die der Hub blockiert hat, bleiben bis zur Admin-Aktion gesperrt.
     */
    private static function assertStreamOpen(string $instanceId, string $peer) :void {
        $cursor = GDPCursorManager::get($instanceId, $peer);

        if (is_array($cursor['blocked'] ?? null)) {
            throw new GDPIntegrityException(GDPErrorCode::INTEGRITY_BLOCKED, 'Der Replikationsstrom dieses Knotens ist blockiert. Administrator-Freigabe oder neuer Snapshot nötig.', ['instance' => $instanceId, 'reason' => $cursor['blocked']['code'] ?? '']);
        }
    }

    private static function blockStream(string $instanceId, string $peer, GDPException $exception) :void {
        GDPCursorManager::update($instanceId, $peer, ['blocked' => $exception->toArray()]);
        GDPAuditLogger::log('stream.blocked', ['instance' => $instanceId, 'node_id' => $peer, 'code' => $exception->errorCode()->value, 'context' => $exception->context()]);
        GDPStateManager::recordFailure($exception, $instanceId);
    }

    public static function handshake(array $peer, array $body) :array {
        self::assertHub();
        $instances = $body['instances'] ?? [];

        if (!is_array($instances) || count($instances) > self::MAX_INSTANCES) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'instances muss eine Liste mit höchstens ' . self::MAX_INSTANCES . ' Einträgen sein.');
        }

        return GDPStore::transaction(static function () use ($peer, $instances) :array {
            $result = [];
            GDPPeerRegistry::touch($peer['node_id']);

            foreach ($instances as $item) {
                $instanceId = is_array($item) && is_string($item['instance_id'] ?? null) ? $item['instance_id'] : '';

                try {
                    if (!GDPCanonical::isIdentifier($instanceId) || !GDPInstanceRegistry::replicated($instanceId)) {
                        throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Instanz ist nicht freigegeben.');
                    }

                    $grant = GDPGrants::authorize($peer['node_id'], 'status', $instanceId);
                } catch (GDPException $exception) {
                    $result[] = ['instance_id' => $instanceId, 'allowed' => false, 'error' => $exception->toArray()];
                    continue;
                }

                if (is_int($item['local_head_source_sequence'] ?? null) && is_int($item['policy_version'] ?? null)) {
                    $cursor = GDPCursorManager::get($instanceId, $peer['node_id']);
                    GDPPolicyEngine::completeTransfers($instanceId, $peer['node_id'], [
                        'local_head' => $item['local_head_source_sequence'],
                        'accepted' => (int) $cursor['accepted_source_sequence'],
                        'policy_version' => $item['policy_version'],
                    ]);
                }

                $cursor = GDPCursorManager::get($instanceId, $peer['node_id']);

                // Nach einem Re-Snapshot überspringt das Replikat nie übertragene eigene
                // Operationen. Nur vorwärts und nur für den eigenen Quellstrom.
                if (is_int($item['source_reset'] ?? null) && $item['source_reset'] > (int) $cursor['accepted_source_sequence'] && !is_array($cursor['blocked'] ?? null)) {
                    GDPCursorManager::update($instanceId, $peer['node_id'], ['accepted_source_sequence' => $item['source_reset']]);
                    GDPAuditLogger::log('stream.source_reset', ['instance' => $instanceId, 'node_id' => $peer['node_id'], 'from' => (int) $cursor['accepted_source_sequence'], 'to' => $item['source_reset']]);
                }

                $instance = GDPInstanceRegistry::get($instanceId) ?? [];
                $cursor = GDPCursorManager::get($instanceId, $peer['node_id']);
                $sequences = GDPJournal::sequences($instanceId);
                $result[] = [
                    'instance_id' => $instanceId,
                    'allowed' => true,
                    'permissions' => $grant['permissions'],
                    'allowed_scope' => $grant['allowed_scope'],
                    'schema_version' => (int) ($instance['schema_version'] ?? 1),
                    'schema_fingerprint' => GDPSchema::fingerprint($instanceId),
                    'policies' => GDPPolicyEngine::document($instanceId),
                    'leases' => GDPPolicyEngine::issueLeases($instanceId, $peer['node_id']),
                    'hub_sequence' => (int) $sequences['hub_sequence'],
                    'retention_floor' => (int) $sequences['floor_hub'],
                    'accepted_source_sequence' => (int) $cursor['accepted_source_sequence'],
                    'applied_hub_sequence' => (int) $cursor['applied_hub_sequence'],
                    'stream_blocked' => $cursor['blocked'] ?? null,
                ];
            }

            return [
                'hub_node_id' => GDPNodeIdentity::id(),
                'protocol_major' => GDPConfig::PROTOCOL_MAJOR,
                'protocol_minor' => GDPConfig::PROTOCOL_MINOR,
                'features' => ['transaction_groups', 'leases', 'snapshots', 'credential_rotation'],
                'server_time' => time(),
                'instances' => $result,
            ];
        });
    }

    /**
     * Nimmt Ursprungsoperationen eines Replikats lückenlos und dauerhaft an.
     * ACK erst nach dauerhafter Übernahme (Kapitel 05, II).
     */
    public static function push(array $peer, array $body) :array {
        self::assertHub();
        $instanceId = self::instanceId($body);
        GDPGrants::authorize($peer['node_id'], 'push', $instanceId);
        $operations = $body['operations'] ?? null;

        if (!is_array($operations) || !array_is_list($operations) || count($operations) > self::MAX_OPERATIONS) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'operations muss eine Liste mit höchstens ' . self::MAX_OPERATIONS . ' Einträgen sein.');
        }

        return GDPStore::transaction(static function () use ($peer, $body, $instanceId, $operations) :array {
            $node = $peer['node_id'];
            self::assertStreamOpen($instanceId, $node);
            $accepted = (int) GDPCursorManager::get($instanceId, $node)['accepted_source_sequence'];
            $duplicates = [];
            $rejected = [];
            $error = null;

            foreach ($operations as $raw) {
                $entry = GDPJournalEntry::fromArray(is_array($raw) ? $raw : []);

                if ($entry->instance_id !== $instanceId || $entry->origin_node_id !== $node) {
                    throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Operation gehört nicht zu diesem Knoten oder dieser Instanz.', ['operation_id' => $entry->operation_id]);
                }

                if ($entry->source_sequence <= $accepted) {
                    $known = GDPAppliedIndex::lookup($instanceId, $entry->operation_id);

                    if ($known !== null && !hash_equals($known['payload_hash'], $entry->payload_hash)) {
                        $exception = new GDPIntegrityException(GDPErrorCode::DUPLICATE_PAYLOAD_MISMATCH, 'Bereits angenommene Sequenz mit abweichendem Inhalt.', ['operation_id' => $entry->operation_id, 'source_sequence' => $entry->source_sequence]);
                        self::blockStream($instanceId, $node, $exception);
                        $rejected[] = ['operation_id' => $entry->operation_id, 'code' => $exception->errorCode()->value];
                        break;
                    }

                    $duplicates[] = $entry->operation_id;
                    continue;
                }

                if ($entry->source_sequence !== $accepted + 1) {
                    $error = ['code' => GDPErrorCode::SEQUENCE_GAP->value, 'expected' => $accepted + 1, 'received' => $entry->source_sequence];
                    break;
                }

                try {
                    $existing = $entry->operation === 'INSERT'
                        ? null
                        : GBDBEngine::withInstance($instanceId, static fn() :mixed => GBDBEngine::getData($entry->database, $entry->table, true, 'id', (int) $entry->record_id));
                    GDPPolicyEngine::authorizeRemote($instanceId, $entry, $node, is_array($existing) && $existing !== [] ? $existing : null);
                    self::acceptOperation($instanceId, $entry);
                } catch (GDPException $exception) {
                    self::blockStream($instanceId, $node, $exception);
                    $rejected[] = ['operation_id' => $entry->operation_id, 'code' => $exception->errorCode()->value, 'message' => $exception->getMessage()];
                    break;
                }

                $accepted = $entry->source_sequence;
                GDPCursorManager::update($instanceId, $node, ['accepted_source_sequence' => $accepted]);
            }

            if (is_int($body['local_head_source_sequence'] ?? null) && is_int($body['policy_version'] ?? null)) {
                GDPPolicyEngine::completeTransfers($instanceId, $node, [
                    'local_head' => $body['local_head_source_sequence'],
                    'accepted' => $accepted,
                    'policy_version' => $body['policy_version'],
                ]);
            }

            return [
                'accepted_through_source_sequence' => $accepted,
                'duplicate_operations' => $duplicates,
                'rejected_operations' => $rejected,
                'gap' => $error,
                'hub_sequence' => (int) GDPJournal::sequences($instanceId)['hub_sequence'],
            ];
        });
    }

    /**
     * Wendet eine fremde Operation an und vergibt ihre Hubsequenz.
     */
    private static function acceptOperation(string $instanceId, GDPJournalEntry $entry) :void {
        $knownBefore = GDPAppliedIndex::lookup($instanceId, $entry->operation_id) !== null;
        $result = GDPApplier::apply($instanceId, $entry);

        if ($knownBefore) {
            return;
        }

        // Nach einem Absturz kann die Operation schon im Hub-Journal stehen.
        if ($result === 'duplicate' && GDPJournal::containsOperation($instanceId, GDPJournal::HUB, $entry->operation_id, 1000)) {
            return;
        }

        GDPJournal::appendHub($instanceId, $entry);
    }

    public static function pull(array $peer, array $body) :array {
        self::assertHub();
        $instanceId = self::instanceId($body);
        $grant = GDPGrants::authorize($peer['node_id'], 'pull', $instanceId);
        $from = self::int($body, 'from_hub_sequence', 1);
        $limit = max(1, min(self::MAX_OPERATIONS, is_int($body['limit'] ?? null) ? $body['limit'] : 100));
        $maxBytes = max(1024, min(8388608, is_int($body['max_bytes'] ?? null) ? $body['max_bytes'] : 1048576));

        return GDPStore::read(static function () use ($instanceId, $peer, $grant, $from, $limit, $maxBytes) :array {
            self::assertStreamOpen($instanceId, $peer['node_id']);
            $read = GDPJournal::read($instanceId, GDPJournal::HUB, $from, $limit, $maxBytes, static fn(GDPJournalEntry $entry) :bool => GDPGrants::inScope($grant, $entry->database, $entry->table));

            return [
                'events' => array_map(static fn(GDPJournalEntry $entry) :array => $entry->toArray(), $read['entries']),
                'through_hub_sequence' => $read['through'],
                'has_more' => $read['more'],
                'hub_sequence' => (int) GDPJournal::sequences($instanceId)['hub_sequence'],
            ];
        });
    }

    public static function ack(array $peer, array $body) :array {
        self::assertHub();
        $instanceId = self::instanceId($body);
        GDPGrants::authorize($peer['node_id'], 'pull', $instanceId);
        $applied = self::int($body, 'applied_hub_sequence', 0);

        return GDPStore::transaction(static function () use ($instanceId, $peer, $applied) :array {
            $head = (int) GDPJournal::sequences($instanceId)['hub_sequence'];

            if ($applied > $head) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::CHECKPOINT_MISMATCH, 'Bestätigte Hubsequenz liegt hinter dem Journalkopf.', ['instance' => $instanceId, 'applied' => $applied, 'head' => $head]);
            }

            $cursor = GDPCursorManager::get($instanceId, $peer['node_id']);
            $value = max((int) $cursor['applied_hub_sequence'], $applied);
            GDPCursorManager::update($instanceId, $peer['node_id'], ['applied_hub_sequence' => $value]);

            return ['applied_hub_sequence' => $value];
        });
    }

    public static function snapshotManifest(array $peer, array $body) :array {
        self::assertHub();
        $instanceId = self::instanceId($body);
        GDPGrants::authorize($peer['node_id'], 'snapshot', $instanceId);

        $resume = is_string($body['resume_snapshot_id'] ?? null) ? $body['resume_snapshot_id'] : '';

        return ['manifest' => GDPSnapshotManager::currentManifest($instanceId, $resume)];
    }

    public static function snapshotChunk(array $peer, array $body) :array {
        self::assertHub();
        $instanceId = self::instanceId($body);
        GDPGrants::authorize($peer['node_id'], 'snapshot', $instanceId);
        $snapshotId = $body['snapshot_id'] ?? null;

        if (!is_string($snapshotId) || !GDPCanonical::isIdentifier($snapshotId)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'snapshot_id fehlt.');
        }

        return GDPSnapshotManager::chunk($instanceId, $snapshotId, self::int($body, 'index', 0));
    }

    /**
     * Pairing Schritt 1: Code prüfen, Knotenschlüssel erzeugen und verschlüsselt übergeben.
     */
    public static function pairStart(array $body, string $proof) :array {
        self::assertHub();
        $pairingId = $body['pairing_id'] ?? null;
        $nodeId = $body['node_id'] ?? null;
        $displayName = $body['display_name'] ?? null;

        if (!is_string($pairingId) || !is_string($nodeId) || !GDPCanonical::isIdentifier($nodeId) || !is_string($displayName) || !is_int($body['timestamp'] ?? null)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'pairing_id, node_id, display_name und timestamp erwartet.');
        }

        if (abs(time() - $body['timestamp']) > GDPConfig::int('clock_skew_seconds', 30, 3600)) {
            throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Pairing-Anfrage ist zu alt.');
        }

        return GDPStore::transaction(static function () use ($pairingId, $nodeId, $displayName, $body, $proof) :array {
            $session = GDPPairing::session($pairingId);
            $expired = $session === null || ($session['used_at'] ?? 0) > 0 || ($session['expires_at'] ?? 0) < time();
            $key = $session === null ? str_repeat("\0", 32) : (string) base64_decode((string) $session['key'], true);
            $expected = GDPAuthenticator::sign($key, GDPCanonical::encode($body));

            if ($expired || !hash_equals($expected, $proof)) {
                GDPAuditLogger::log('pairing.rejected', ['pairing_id' => $pairingId, 'node_id' => $nodeId]);

                throw new GDPAuthorizationException(GDPErrorCode::PAIRING_EXPIRED, 'Pairing-Code ungültig, abgelaufen oder bereits verwendet.');
            }

            if (($session['node_id'] ?? '') !== '' && $session['node_id'] !== $nodeId) {
                throw new GDPAuthorizationException(GDPErrorCode::PAIRING_EXPIRED, 'Dieser Pairing-Code wird bereits von einem anderen Knoten verwendet.');
            }

            if ($nodeId === GDPNodeIdentity::id() || (GDPPeerRegistry::get($nodeId)['status'] ?? '') === 'active') {
                throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'node_id ist bereits vergeben.');
            }

            $keyId = GDPCredentials::newKeyId();
            $secret = GDPCredentials::newSecret();
            $challenge = bin2hex(random_bytes(24));
            GDPCredentials::revokePeerKeys($nodeId);
            GDPCredentials::storePeerKey($nodeId, $keyId, $secret, GDPCredentials::PENDING, (int) $session['expires_at']);
            GDPPairing::update($pairingId, ['node_id' => $nodeId, 'challenge' => hash('sha256', $challenge), 'display_name' => mb_substr($displayName, 0, 120)]);
            GDPAuditLogger::log('pairing.started', ['pairing_id' => $pairingId, 'node_id' => $nodeId]);

            return [
                'hub_node_id' => GDPNodeIdentity::id(),
                'hub_display_name' => (string) GDPNodeIdentity::get()['display_name'],
                'key_id' => $keyId,
                'wrapped_secret' => GDPAuthenticator::wrap($key, $secret, $pairingId . '|' . $nodeId . '|' . $keyId),
                'challenge' => $challenge,
                'instances' => $session['instances'],
                'role' => $session['role'],
                'permissions' => $session['permissions'],
            ];
        });
    }

    /**
     * Pairing Schritt 2: Besitz des neuen Schlüssels nachweisen, Code verbrauchen.
     */
    public static function pairComplete(array $peer, array $body) :array {
        self::assertHub();
        $pairingId = $body['pairing_id'] ?? null;
        $challenge = $body['challenge'] ?? null;

        if (!is_string($pairingId) || !is_string($challenge)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'pairing_id und challenge erwartet.');
        }

        return GDPStore::transaction(static function () use ($peer, $pairingId, $challenge) :array {
            $session = GDPPairing::session($pairingId);

            if ($session === null || ($session['used_at'] ?? 0) > 0 || ($session['expires_at'] ?? 0) < time()
                || ($session['node_id'] ?? '') !== $peer['node_id'] || !hash_equals((string) ($session['challenge'] ?? ''), hash('sha256', $challenge))) {
                throw new GDPAuthorizationException(GDPErrorCode::PAIRING_EXPIRED, 'Pairing kann nicht abgeschlossen werden.');
            }

            $status = $session['administrator_approval'] ? 'pending_approval' : 'active';
            GDPPairing::update($pairingId, ['used_at' => time()]);
            GDPCredentials::setPeerKeyStatus($peer['node_id'], $peer['key_id'], GDPCredentials::ACTIVE);
            GDPPeerRegistry::save($peer['node_id'], [
                'display_name' => (string) ($session['display_name'] ?? $peer['node_id']),
                'role' => $session['role'],
                'enabled' => true,
                'status' => $status,
                'credential_key_id' => $peer['key_id'],
                'paired_at' => time(),
            ]);

            foreach ($session['instances'] as $instanceId) {
                GDPGrants::grant($peer['node_id'], $instanceId, $session['permissions'], $session['scope'] ?? null);
                GDPCursorManager::update($instanceId, $peer['node_id'], ['accepted_source_sequence' => 0, 'applied_hub_sequence' => 0, 'blocked' => null]);
            }

            GDPAuditLogger::log('pairing.completed', ['pairing_id' => $pairingId, 'node_id' => $peer['node_id'], 'status' => $status]);

            return ['status' => $status, 'instances' => $session['instances']];
        });
    }

    /**
     * Schlüsselrotation (Kapitel 20): neuer Schlüssel, mit dem alten verschlüsselt übergeben.
     */
    public static function rotate(array $peer) :array {
        self::assertHub();

        return GDPStore::transaction(static function () use ($peer) :array {
            $keyId = GDPCredentials::newKeyId();
            $secret = GDPCredentials::newSecret();
            GDPCredentials::storePeerKey($peer['node_id'], $keyId, $secret, GDPCredentials::PENDING, time() + 3600);
            GDPAuditLogger::log('credential.rotation_started', ['node_id' => $peer['node_id'], 'key_id' => $keyId]);
            $wrapKey = hash_hkdf('sha256', $peer['secret'], 32, 'gdp1-rotate');

            return ['key_id' => $keyId, 'wrapped_secret' => GDPAuthenticator::wrap($wrapKey, $secret, $peer['node_id'] . '|' . $keyId)];
        });
    }

    public static function confirmRotation(array $peer) :array {
        self::assertHub();

        return GDPStore::transaction(static function () use ($peer) :array {
            GDPCredentials::setPeerKeyStatus($peer['node_id'], $peer['key_id'], GDPCredentials::ACTIVE);
            $revoked = GDPCredentials::revokePeerKeys($peer['node_id'], $peer['key_id']);
            GDPPeerRegistry::save($peer['node_id'], ['credential_key_id' => $peer['key_id']]);
            GDPAuditLogger::log('credential.rotated', ['node_id' => $peer['node_id'], 'key_id' => $peer['key_id'], 'revoked' => $revoked]);

            return ['key_id' => $peer['key_id']];
        });
    }
}

/**
 * HTTP-Einstieg gdp.php: Größenlimits, Rate-Limits, Authentifizierung, Routing
 * und maschinenlesbare Fehler. Logs enthalten keine Geheimnisse.
 */
final class GDPServer {
    private const UNAUTHENTICATED = ['health', 'pair/start', 'trigger'];
    private const PENDING_KEY_ENDPOINTS = ['pair/complete', 'credential/confirm'];

    public static function handle() :void {
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('Content-Type: application/json; charset=utf-8');
        $nonce = '';
        $secret = '';

        try {
            if (!GDPConfig::enabled()) {
                throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'GDP ist auf diesem Knoten nicht aktiviert.');
            }

            $route = self::route();

            if (!GBDBRateLimiter::allow('gdp', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 1200, 60)) {
                header('Retry-After: 60');
                throw new GDPException(GDPErrorCode::RATE_LIMITED, 'Zu viele Anfragen.');
            }

            $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

            if ($route === 'health') {
                $identity = GDPStore::read(static fn() :array => GDPNodeIdentity::get());
                self::respond(200, ['success' => true, 'protocol' => GDPConfig::PROTOCOL, 'role' => GDPNodeIdentity::role()->value, 'node_id' => $identity['node_id'], 'display_name' => $identity['display_name'], 'server_time' => time()]);
            }

            if ($method !== 'POST') {
                header('Allow: POST');
                throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'POST erforderlich.');
            }

            $raw = self::body();
            $body = GDPCanonical::decode($raw === '' ? '{}' : $raw);

            if (($body['protocol'] ?? GDPConfig::PROTOCOL) !== GDPConfig::PROTOCOL) {
                throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Nicht unterstützte Protokollversion.', ['supported' => GDPConfig::PROTOCOL]);
            }

            if ($route === 'trigger') {
                self::respond(200, ['success' => true] + self::trigger());
            }

            if ($route === 'pair/start') {
                if (!GBDBRateLimiter::allow('gdp-pair', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 10, 900)) {
                    throw new GDPException(GDPErrorCode::RATE_LIMITED, 'Zu viele Pairing-Versuche.');
                }

                $headers = self::headers();
                $result = GDPHubService::pairStart($body, (string) ($headers['x-gdp-pair-proof'] ?? ''));
                $session = GDPPairing::session((string) $body['pairing_id']);
                $json = self::json(['success' => true] + $result);
                header('X-GDP-Pair-Proof: ' . GDPAuthenticator::sign((string) base64_decode((string) ($session['key'] ?? ''), true), $json));
                http_response_code(200);
                echo $json;
                exit;
            }

            $peer = GDPAuthenticator::verifyRequest($method, GDPHttpTransport::canonicalPath($route), $raw, self::headers());
            $nonce = $peer['nonce'];
            $secret = $peer['secret'];

            if ($peer['status'] === GDPCredentials::PENDING && !in_array($route, self::PENDING_KEY_ENDPOINTS, true)) {
                throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Schlüssel ist noch nicht aktiviert.');
            }

            if (!in_array($route, ['pair/complete', 'credential/confirm'], true) && !GDPPeerRegistry::active($peer['node_id'])) {
                throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Knoten ist gesperrt oder wartet auf Freigabe.');
            }

            $result = match ($route) {
                'handshake' => GDPHubService::handshake($peer, $body),
                'sync/push' => GDPHubService::push($peer, $body),
                'sync/pull' => GDPHubService::pull($peer, $body),
                'sync/ack' => GDPHubService::ack($peer, $body),
                'snapshot/manifest' => GDPHubService::snapshotManifest($peer, $body),
                'snapshot/chunk' => GDPHubService::snapshotChunk($peer, $body),
                'pair/complete' => GDPHubService::pairComplete($peer, $body),
                'credential/rotate' => GDPHubService::rotate($peer),
                'credential/confirm' => GDPHubService::confirmRotation($peer),
            };

            self::respond(200, ['success' => true] + $result, $secret, $nonce);
        } catch (GDPException $exception) {
            if (in_array($exception->errorCode(), [GDPErrorCode::AUTHENTICATION_FAILED, GDPErrorCode::AUTHORIZATION_DENIED], true)) {
                GDPAuditLogger::log('access.failed', ['code' => $exception->errorCode()->value, 'context' => $exception->context(), 'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '')]);
            }

            self::respond($exception->errorCode()->httpStatus(), ['success' => false, 'error' => $exception->toArray()], $secret, $nonce);
        } catch (JsonException $exception) {
            self::respond(400, ['success' => false, 'error' => (new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültiges JSON.'))->toArray()], $secret, $nonce);
        } catch (Throwable $exception) {
            error_log('[GDP] Interner Fehler: ' . get_class($exception) . ' in ' . basename($exception->getFile()) . ':' . $exception->getLine());
            self::respond(500, ['success' => false, 'error' => (new GDPException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Interner Fehler. Server-Protokoll prüfen.'))->toArray()], $secret, $nonce);
        }
    }

    private static function route() :string {
        $route = (string) ($_GET['r'] ?? ($_SERVER['PATH_INFO'] ?? ''));
        $route = trim($route, '/');

        if (str_starts_with($route, 'gdp/')) {
            $route = substr($route, 4);
        }

        if (!str_starts_with($route, 'v1/') || !in_array(substr($route, 3), GDPHttpTransport::ENDPOINTS, true)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Unbekannter Endpunkt.');
        }

        return substr($route, 3);
    }

    private static function body() :string {
        $limit = GDPConfig::int('max_request_bytes', 65536, 67108864);

        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) {
            throw new GDPProtocolException(GDPErrorCode::PAYLOAD_TOO_LARGE, 'Anfrage ist zu groß.');
        }

        $raw = file_get_contents('php://input', false, null, 0, $limit + 1);

        if ($raw === false || strlen($raw) > $limit) {
            throw new GDPProtocolException(GDPErrorCode::PAYLOAD_TOO_LARGE, 'Anfrage ist zu groß.');
        }

        return $raw;
    }

    /**
     * @return array<string, string>
     */
    private static function headers() :array {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (is_string($name) && str_starts_with($name, 'HTTP_X_GDP_') && is_string($value)) {
                $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
            }
        }

        return $headers;
    }

    private static function trigger() :array {
        $token = (string) GDPConfig::get('trigger_token');
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        $given = preg_match('/^Bearer\s+(\S+)$/D', $authorization, $matches) ? $matches[1] : '';

        if (strlen($token) < 32 || !hash_equals($token, $given)) {
            throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Trigger ist nicht freigegeben.');
        }

        return ['report' => GDP::runBackgroundCycle(GDPConfig::int('embedded_budget_seconds', 1, 60) * 5)];
    }

    private static function json(array $payload) :string {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private static function respond(int $status, array $payload, string $secret = '', string $nonce = '') :never {
        $json = self::json($payload);

        if ($secret !== '' && $nonce !== '') {
            header('X-GDP-Response-Signature: ' . GDPAuthenticator::responseSignature($secret, $nonce, $json));
        }

        http_response_code($status);
        echo $json;
        exit;
    }
}
