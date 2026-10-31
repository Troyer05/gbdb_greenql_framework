<?php

declare(strict_types=1);

/**
 * Synchronisationsdurchlauf eines Replikats (Konzept Kapitel 16 und 18).
 *
 * Handshake → Push → Pull → lokales Apply → ACK. Netzwerkaufrufe laufen nie
 * innerhalb einer offenen lokalen Speichertransaktion; jeder lokale Schritt
 * wird einzeln atomar abgeschlossen. Embedded- und Worker-Modus verwenden
 * genau diesen Kern, nur der Auslöser unterscheidet sich.
 */
final class GDPSyncEngine {
    private const HUB_CURSOR = 'hub';

    private float $deadline;
    private array $report = ['instances' => [], 'errors' => []];

    private function __construct(private readonly GDPTransport $transport, int $budgetSeconds, private readonly bool $replaceLocal = false) {
        $this->deadline = microtime(true) + max(1, $budgetSeconds);
    }

    public static function hubUrl() :string {
        $configured = GDPConfig::hubUrl();

        if ($configured !== '') {
            return $configured;
        }

        foreach (GDPPeerRegistry::all() as $node) {
            if (($node['role'] ?? '') === GDPNodeRole::HUB->value && is_string($node['url'] ?? null)) {
                return $node['url'];
            }
        }

        return '';
    }

    private static function transport() :GDPHttpTransport {
        $url = GDPStore::read(static fn() :string => self::hubUrl());

        if ($url === '') {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Keine Hub-URL konfiguriert (GDP → hub_url).');
        }

        return new GDPHttpTransport($url);
    }

    /**
     * Ein vollständiger, durch das Zeitbudget begrenzter Durchlauf.
     */
    public static function run(?string $onlyInstance, int $budgetSeconds, bool $manual, bool $replaceLocal = false) :array {
        $role = GDPStore::read(static fn() :GDPNodeRole => GDPNodeIdentity::role());

        if ($role === GDPNodeRole::HUB) {
            return ['role' => 'hub', 'housekeeping' => self::housekeeping()];
        }

        $state = GDPStore::read(static fn() :array => GDPStateManager::get());

        if ($state['preference'] === GDPOperationPreference::FORCE_LOCAL->value) {
            return ['skipped' => 'FORCE_LOCAL: zentrale Kommunikation ist abgeschaltet.'];
        }

        if (!$manual && (int) $state['backoff_until'] > time()) {
            return ['skipped' => 'Backoff bis ' . gmdate('c', (int) $state['backoff_until'])];
        }

        $engine = new self(self::transport(), $budgetSeconds, $replaceLocal);

        try {
            return $engine->cycle($onlyInstance);
        } catch (GDPConnectionException $exception) {
            GDPStore::transaction(static fn() => GDPStateManager::markOffline($exception));
            $engine->report['errors'][] = $exception->toArray();

            return $engine->report;
        } catch (GDPException $exception) {
            // Etwa widerrufener Schlüssel oder ungültige Hub-Antwort: protokollieren, nicht abstürzen.
            GDPStore::transaction(static fn() => GDPStateManager::recordFailure($exception));
            $engine->report['errors'][] = $exception->toArray();

            return $engine->report;
        }
    }

    private function remaining() :bool {
        return microtime(true) < $this->deadline;
    }

    private function cycle(?string $onlyInstance) :array {
        $local = GDPStore::transaction(static function () use ($onlyInstance) :array {
            $result = [];

            foreach (GDPInstanceRegistry::all() as $instanceId => $instance) {
                if (($instance['replication_enabled'] ?? false) !== true || ($onlyInstance !== null && $onlyInstance !== $instanceId)) {
                    continue;
                }

                GBDBEngine::withInstance($instanceId, static fn() => GDPWriteInterceptor::recover($instanceId));
                $sequences = GDPJournal::sequences($instanceId);
                $cursor = GDPCursorManager::get($instanceId, self::HUB_CURSOR);
                $result[$instanceId] = [
                    'instance_id' => $instanceId,
                    'schema_version' => (int) ($instance['schema_version'] ?? 1),
                    'schema_fingerprint' => GDPSchema::fingerprint($instanceId),
                    'policy_version' => (int) GDPPolicyEngine::document($instanceId)['policy_version'],
                    'applied_hub_sequence' => (int) $cursor['applied_hub_sequence'],
                    'local_head_source_sequence' => (int) $sequences['source_sequence'],
                ];

                if (is_int($instance['source_reset'] ?? null)) {
                    $result[$instanceId]['source_reset'] = $instance['source_reset'];
                }
            }

            return $result;
        });

        if ($local === []) {
            return $this->report + ['message' => 'Keine replizierten Instanzen.'];
        }

        $started = time();
        $handshake = $this->transport->call('handshake', ['instances' => array_values($local)]);
        GDPStore::transaction(static fn() => GDPStateManager::markOnline());

        foreach ((array) ($handshake['instances'] ?? []) as $remote) {
            $instanceId = (string) ($remote['instance_id'] ?? '');

            if (!isset($local[$instanceId])) {
                continue;
            }

            try {
                $ready = $this->applyHandshake($instanceId, $remote, (string) ($handshake['hub_node_id'] ?? ''), $started);

                if ($ready === 'bootstrap') {
                    $this->report['instances'][$instanceId]['bootstrap'] = $this->bootstrap($instanceId, $this->replaceLocal);
                    continue;
                }

                if ($ready !== 'ready') {
                    $this->report['instances'][$instanceId]['state'] = $ready;
                    continue;
                }

                $this->report['instances'][$instanceId]['pushed'] = $this->push($instanceId);
                $this->report['instances'][$instanceId]['pulled'] = $this->pull($instanceId);
                $this->finish($instanceId);
            } catch (GDPConnectionException $exception) {
                throw $exception;
            } catch (GDPException $exception) {
                if ($exception->retryClass() === GDPRetryClass::MANUAL) {
                    GDPStore::transaction(static fn() => GDPInstanceRegistry::block($instanceId, $exception));
                } else {
                    GDPStore::transaction(static fn() => GDPStateManager::recordFailure($exception, $instanceId));
                }

                $this->report['errors'][] = $exception->toArray() + ['instance' => $instanceId];
            }
        }

        GDPStore::transaction(static fn() => GDPStateManager::update(['last_successful_sync_at' => time()]));

        return $this->report;
    }

    /**
     * Prüft Grants, Schema, Policies und Checkpoints. Liefert ready, bootstrap,
     * blocked oder resync.
     */
    private function applyHandshake(string $instanceId, array $remote, string $hubNodeId, int $requestStarted) :string {
        return GDPStore::transaction(static function () use ($instanceId, $remote, $hubNodeId, $requestStarted) :string {
            $instance = GDPInstanceRegistry::get($instanceId) ?? [];

            if (($instance['hub_node_id'] ?? '') !== $hubNodeId) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::AUTHENTICATION_FAILED, 'Antwortender Hub ist nicht der gekoppelte Hub dieser Instanz.', ['instance' => $instanceId]);
            }

            if (($remote['allowed'] ?? false) !== true) {
                $error = is_array($remote['error'] ?? null) ? $remote['error'] : [];

                throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Hub verweigert den Zugriff auf diese Instanz.', ['instance' => $instanceId, 'remote' => (string) ($error['code'] ?? '')]);
            }

            if (is_array($remote['stream_blocked'] ?? null)) {
                throw new GDPIntegrityException(GDPErrorCode::INTEGRITY_BLOCKED, 'Hub hat den Replikationsstrom blockiert: ' . (string) ($remote['stream_blocked']['message'] ?? ''), ['instance' => $instanceId, 'remote' => (string) ($remote['stream_blocked']['code'] ?? '')]);
            }

            if (is_array($remote['policies'] ?? null) && (int) ($remote['policies']['policy_version'] ?? 0) !== (int) GDPPolicyEngine::document($instanceId)['policy_version']) {
                GDPPolicyEngine::replaceDocument($instanceId, $remote['policies']);
            }

            $leases = [];

            foreach ((array) ($remote['leases'] ?? []) as $policyId => $seconds) {
                if (is_string($policyId) && is_int($seconds) && $seconds > 0) {
                    $leases[$policyId] = $requestStarted + $seconds;
                }
            }

            GDPPolicyEngine::storeLeases($leases);
            GDPInstanceRegistry::save($instanceId, ['permissions' => (array) ($remote['permissions'] ?? []), 'remote_hub_sequence' => (int) ($remote['hub_sequence'] ?? 0)]);
            $state = GDPInstanceRegistry::state($instanceId);

            if ($state === GDPSyncState::BLOCKED) {
                return 'blocked';
            }

            if (($instance['activation'] ?? null) !== null) {
                GDPSnapshotManager::activate($instanceId, (string) $instance['activation']);
                $state = GDPInstanceRegistry::state($instanceId);
            }

            if ($state === GDPSyncState::INITIALIZING || $state === GDPSyncState::RESYNC) {
                return 'bootstrap';
            }

            if ((int) $remote['schema_version'] !== (int) ($instance['schema_version'] ?? 1) || !hash_equals((string) $remote['schema_fingerprint'], GDPSchema::fingerprint($instanceId))) {
                throw new GDPSchemaMismatchException(GDPErrorCode::SCHEMA_MISMATCH, 'Schema weicht vom Hub ab. Schema angleichen oder Replikat neu initialisieren.', ['instance' => $instanceId, 'hub_schema_version' => (int) $remote['schema_version'], 'local_schema_version' => (int) ($instance['schema_version'] ?? 1)]);
            }

            $cursor = GDPCursorManager::get($instanceId, self::HUB_CURSOR);
            $hubAccepted = (int) $remote['accepted_source_sequence'];

            if (is_int($instance['source_reset'] ?? null) && $hubAccepted >= $instance['source_reset']) {
                GDPInstanceRegistry::save($instanceId, ['source_reset' => null]);
            }
            $localHead = (int) GDPJournal::sequences($instanceId)['source_sequence'];

            if ($hubAccepted < (int) $cursor['accepted_source_sequence'] || $hubAccepted > $localHead) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::CHECKPOINT_MISMATCH, 'Hub und Replikat haben abweichende Quell-Checkpoints (Hub wiederhergestellt?).', ['instance' => $instanceId, 'hub_accepted' => $hubAccepted, 'local_accepted' => (int) $cursor['accepted_source_sequence'], 'local_head' => $localHead]);
            }

            if ((int) $cursor['applied_hub_sequence'] > (int) $remote['hub_sequence']) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::CHECKPOINT_MISMATCH, 'Lokal ist ein Hub-Stand angewendet, den der Hub nicht mehr kennt.', ['instance' => $instanceId, 'local_applied' => (int) $cursor['applied_hub_sequence'], 'hub_head' => (int) $remote['hub_sequence']]);
            }

            if ((int) $cursor['applied_hub_sequence'] + 1 < (int) $remote['retention_floor']) {
                GDPInstanceRegistry::save($instanceId, ['sync_state' => GDPSyncState::RESYNC->value]);

                return 'bootstrap';
            }

            if ($hubAccepted > (int) $cursor['accepted_source_sequence']) {
                // Die Bestätigung ging zuletzt verloren; der Hub hat die Operationen dauerhaft.
                GDPCursorManager::update($instanceId, self::HUB_CURSOR, ['accepted_source_sequence' => $hubAccepted]);
            }

            return 'ready';
        });
    }

    private function push(string $instanceId) :int {
        $total = 0;
        $batch = GDPConfig::int('sync_batch_size', 1, 1000);
        $maxBytes = GDPConfig::int('sync_max_bytes', 4096, 8388608);

        while ($this->remaining()) {
            $outgoing = GDPStore::read(static function () use ($instanceId, $batch, $maxBytes) :array {
                $accepted = (int) GDPCursorManager::get($instanceId, self::HUB_CURSOR)['accepted_source_sequence'];
                $head = (int) GDPJournal::sequences($instanceId)['source_sequence'];

                if ($accepted >= $head) {
                    return [];
                }

                $read = GDPJournal::read($instanceId, GDPJournal::OUTBOX, $accepted + 1, $batch, $maxBytes);

                return [
                    'from_source_sequence' => $accepted,
                    'local_head_source_sequence' => $head,
                    'policy_version' => (int) GDPPolicyEngine::document($instanceId)['policy_version'],
                    'operations' => array_map(static fn(GDPJournalEntry $entry) :array => $entry->toArray(), $read['entries']),
                ];
            });

            if ($outgoing === [] || $outgoing['operations'] === []) {
                break;
            }

            $response = $this->transport->call('sync/push', ['instance_id' => $instanceId] + $outgoing);
            $accepted = (int) ($response['accepted_through_source_sequence'] ?? -1);

            if ($accepted < $outgoing['from_source_sequence'] || $accepted > $outgoing['local_head_source_sequence']) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::CHECKPOINT_MISMATCH, 'Hub bestätigt eine unplausible Quellsequenz.', ['instance' => $instanceId, 'accepted' => $accepted]);
            }

            GDPStore::transaction(static fn() => GDPCursorManager::update($instanceId, self::HUB_CURSOR, ['accepted_source_sequence' => $accepted]));
            $total += $accepted - $outgoing['from_source_sequence'];

            if (($response['rejected_operations'] ?? []) !== []) {
                $rejected = $response['rejected_operations'][0];
                $code = GDPErrorCode::tryFrom((string) ($rejected['code'] ?? '')) ?? GDPErrorCode::INTEGRITY_BLOCKED;

                throw new GDPIntegrityException($code === GDPErrorCode::POLICY_VIOLATION || $code === GDPErrorCode::LEASE_EXPIRED ? GDPErrorCode::INTEGRITY_BLOCKED : $code, 'Hub hat eine lokale Operation abgelehnt: ' . (string) ($rejected['message'] ?? $code->value), ['instance' => $instanceId, 'operation_id' => (string) ($rejected['operation_id'] ?? ''), 'remote' => $code->value]);
            }

            if ($accepted === $outgoing['from_source_sequence']) {
                // Kein Fortschritt (Lücke beim Hub): nicht endlos wiederholen.
                break;
            }
        }

        return $total;
    }

    private function pull(string $instanceId) :int {
        $total = 0;
        $node = GDPStore::read(static fn() :string => GDPNodeIdentity::id());

        while ($this->remaining()) {
            $from = GDPStore::read(static fn() :int => (int) GDPCursorManager::get($instanceId, self::HUB_CURSOR)['applied_hub_sequence']) + 1;
            $response = $this->transport->call('sync/pull', [
                'instance_id' => $instanceId,
                'from_hub_sequence' => $from,
                'limit' => GDPConfig::int('sync_batch_size', 1, 1000),
                'max_bytes' => GDPConfig::int('sync_max_bytes', 4096, 8388608),
            ]);
            $through = (int) ($response['through_hub_sequence'] ?? -1);
            $events = (array) ($response['events'] ?? []);

            if ($through < $from - 1 || $through > (int) ($response['hub_sequence'] ?? 0)) {
                throw new GDPCheckpointMismatchException(GDPErrorCode::CHECKPOINT_MISMATCH, 'Hub liefert einen unplausiblen Pull-Bereich.', ['instance' => $instanceId, 'from' => $from, 'through' => $through]);
            }

            $applied = GDPStore::transaction(static function () use ($instanceId, $events, $from, $through, $node) :int {
                $count = 0;
                $previous = $from - 1;

                foreach ($events as $raw) {
                    $entry = GDPJournalEntry::fromArray(is_array($raw) ? $raw : []);
                    $sequence = (int) $entry->hub_sequence;

                    if ($entry->instance_id !== $instanceId || $sequence <= $previous || $sequence > $through) {
                        throw new GDPIntegrityException(GDPErrorCode::SEQUENCE_GAP, 'Hub-Ereignisse sind nicht streng aufsteigend.', ['instance' => $instanceId, 'hub_sequence' => $sequence]);
                    }

                    $previous = $sequence;

                    // Eigene Ursprungsoperationen kommen zurück: nur den Cursor fortschreiben.
                    if ($entry->origin_node_id !== $node) {
                        GDPApplier::apply($instanceId, $entry);
                        $count++;
                    }
                }

                GDPCursorManager::update($instanceId, self::HUB_CURSOR, ['applied_hub_sequence' => $through]);

                return $count;
            });
            $total += $applied;

            if ($through >= $from) {
                $this->transport->call('sync/ack', ['instance_id' => $instanceId, 'applied_hub_sequence' => $through]);
            }

            if (($response['has_more'] ?? false) !== true || $through < $from) {
                break;
            }
        }

        return $total;
    }

    private function finish(string $instanceId) :void {
        GDPStore::transaction(static function () use ($instanceId) :void {
            $accepted = (int) GDPCursorManager::get($instanceId, self::HUB_CURSOR)['accepted_source_sequence'];
            $head = (int) GDPJournal::sequences($instanceId)['source_sequence'];
            GDPInstanceRegistry::setState($instanceId, $accepted >= $head ? GDPSyncState::IDLE : GDPSyncState::PENDING);
            GDPInstanceRegistry::save($instanceId, ['last_sync_at' => time()]);
            GDPStateManager::recordMetrics($instanceId, ['pending' => $head - $accepted]);
        });
    }

    /**
     * Snapshot-Bootstrap. Vorhandene Bestandsdaten werden nur mit ausdrücklicher
     * Freigabe ersetzt; vorher entsteht automatisch ein vollständiges Backup.
     */
    private function bootstrap(string $instanceId, bool $replaceLocal) :array {
        $resume = GDPSnapshotManager::stagedSnapshotId($instanceId);
        $manifest = (array) ($this->transport->call('snapshot/manifest', ['instance_id' => $instanceId, 'resume_snapshot_id' => $resume])['manifest'] ?? []);
        GDPSnapshotManager::verifyManifest($manifest, $instanceId);
        $preflight = GDPSnapshotManager::preflight($instanceId, $manifest);

        if (($preflight['populated'] !== [] || $preflight['unsent'] > 0) && !$replaceLocal) {
            GDPStore::transaction(static fn() => GDPInstanceRegistry::save($instanceId, ['bootstrap_preflight' => $preflight + ['at' => time()]]));

            return ['status' => 'confirmation_required', 'populated' => $preflight['populated'], 'unsent' => $preflight['unsent']];
        }

        $backup = null;

        if ($preflight['populated'] !== [] || $preflight['unsent'] > 0) {
            $backup = GDPStore::transaction(static fn() :array => GBDBEngine::fullBackup());

            if (($backup['ok'] ?? false) !== true) {
                throw new GDPSnapshotException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Sicherung vor dem Ersetzen fehlgeschlagen; Bootstrap abgebrochen.', ['instance' => $instanceId]);
            }

            GDPAuditLogger::log('snapshot.local_replaced', ['instance' => $instanceId, 'tables' => $preflight['populated'], 'unsent' => $preflight['unsent'], 'backup' => (string) ($backup['path'] ?? '')]);
        }

        GDPSnapshotManager::download($this->transport, $instanceId, $manifest);
        $result = GDPSnapshotManager::activate($instanceId, (string) $manifest['snapshot_id']);
        $result['backup'] = $backup === null ? null : (string) ($backup['path'] ?? '');

        if ($this->remaining()) {
            $result['pulled'] = $this->pull($instanceId);
            $this->finish($instanceId);
        }

        return $result;
    }

    /**
     * Pairing aus Sicht des Replikats (Kapitel 19, Schritte 3–9).
     */
    public static function pair(string $hubUrl, string $code, string $displayName) :array {
        $role = GDPNodeIdentity::role();

        if ($role === GDPNodeRole::HUB) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Ein Hub koppelt sich nicht selbst an einen Hub.');
        }

        $hubUrl = rtrim(trim($hubUrl), '/');

        if ($hubUrl !== '' && !str_ends_with($hubUrl, '.php')) {
            $hubUrl .= '/gdp.php';
        }

        $transport = new GDPHttpTransport($hubUrl);
        $parsed = GDPPairing::parse($code);
        $key = GDPAuthenticator::pairingKey($parsed['secret']);
        $nodeId = GDPStore::transaction(static fn() :string => GDPNodeIdentity::id());
        $payload = [
            'protocol' => GDPConfig::PROTOCOL,
            'pairing_id' => $parsed['pairing_id'],
            'node_id' => $nodeId,
            'display_name' => mb_substr($displayName !== '' ? $displayName : $nodeId, 0, 120),
            'role' => $role->value,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16)),
        ];
        $start = $transport->exchange('pair/start', $payload, ['X-GDP-Pair-Proof' => GDPAuthenticator::sign($key, GDPCanonical::encode($payload))]);

        if (!hash_equals(GDPAuthenticator::sign($key, $start['raw']), (string) ($start['headers']['x-gdp-pair-proof'] ?? ''))) {
            throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Antwort des Hubs passt nicht zum Pairing-Code.');
        }

        $response = $start['body'];
        $hubNodeId = (string) ($response['hub_node_id'] ?? '');
        $keyId = (string) ($response['key_id'] ?? '');

        if (!GDPCanonical::isIdentifier($hubNodeId) || !preg_match('/^[a-zA-Z0-9_-]{8,64}$/D', $keyId)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Unvollständige Pairing-Antwort.');
        }

        $secret = GDPAuthenticator::unwrap($key, (string) ($response['wrapped_secret'] ?? ''), $parsed['pairing_id'] . '|' . $nodeId . '|' . $keyId);
        $credential = ['key_id' => $keyId, 'secret' => $secret, 'hub_node_id' => $hubNodeId, 'paired_at' => time()];
        $complete = $transport->call('pair/complete', ['pairing_id' => $parsed['pairing_id'], 'challenge' => (string) ($response['challenge'] ?? '')], true, [], $credential);
        GDPCredentials::storeHubKey($credential);
        $instances = array_values(array_filter((array) ($complete['instances'] ?? []), [GDPCanonical::class, 'isIdentifier']));

        GDPStore::transaction(static function () use ($hubNodeId, $hubUrl, $response, $instances, $complete) :void {
            GDPPeerRegistry::save($hubNodeId, [
                'display_name' => (string) ($response['hub_display_name'] ?? $hubNodeId),
                'role' => GDPNodeRole::HUB->value,
                'url' => $hubUrl,
                'status' => 'active',
                'enabled' => true,
            ]);

            foreach ($instances as $instanceId) {
                if (GDPInstanceRegistry::get($instanceId) === null || !GDPInstanceRegistry::replicated($instanceId)) {
                    GDPInstanceRegistry::save($instanceId, [
                        'replication_enabled' => true,
                        'hub_node_id' => $hubNodeId,
                        'sync_state' => GDPSyncState::INITIALIZING->value,
                        'blocked' => null,
                    ]);
                }
            }

            GDPAuditLogger::log('pairing.joined', ['hub_node_id' => $hubNodeId, 'instances' => $instances, 'status' => (string) ($complete['status'] ?? '')]);
        });

        return ['hub_node_id' => $hubNodeId, 'status' => (string) ($complete['status'] ?? ''), 'instances' => $instances];
    }

    /**
     * Schlüsselrotation: neuer Schlüssel wird erst nach erfolgreicher Bestätigung genutzt.
     */
    public static function rotateCredential() :array {
        $transport = self::transport();
        $current = GDPCredentials::hubKey();

        if ($current === null) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Kein Hub-Schlüssel vorhanden.');
        }

        $response = $transport->call('credential/rotate', []);
        $keyId = (string) ($response['key_id'] ?? '');
        $secret = GDPAuthenticator::unwrap(hash_hkdf('sha256', $current['secret'], 32, 'gdp1-rotate'), (string) ($response['wrapped_secret'] ?? ''), GDPNodeIdentity::id() . '|' . $keyId);
        $next = ['key_id' => $keyId, 'secret' => $secret, 'hub_node_id' => $current['hub_node_id'] ?? '', 'paired_at' => $current['paired_at'] ?? time(), 'rotated_at' => time()];
        $transport->call('credential/confirm', [], true, [], $next);
        GDPCredentials::storeHubKey($next);
        GDPStore::transaction(static fn() => GDPAuditLogger::log('credential.rotated', ['key_id' => $keyId]));

        return ['key_id' => $keyId];
    }

    /**
     * Aufräumen nach sicheren Retention-Regeln (Kapitel 05, IX und 29).
     */
    public static function housekeeping() :array {
        return GDPStore::transaction(static function () :array {
            $report = [];
            $retention = time() - GDPConfig::int('retention_days', 1, 3650) * 86400;
            $isHub = GDPNodeIdentity::role() === GDPNodeRole::HUB;

            foreach (GDPInstanceRegistry::all() as $instanceId => $instance) {
                if (($instance['replication_enabled'] ?? false) !== true) {
                    continue;
                }

                if ($isHub) {
                    $safe = (int) GDPJournal::sequences($instanceId)['hub_sequence'];

                    foreach (GDPCursorManager::all($instanceId) as $peer => $cursor) {
                        $stale = (int) ($cursor['last_confirmed_at'] ?? 0) < $retention;

                        if (GDPPeerRegistry::active((string) $peer) && !$stale) {
                            $safe = min($safe, (int) $cursor['applied_hub_sequence']);
                        }
                    }

                    $removed = GDPJournal::compact($instanceId, GDPJournal::HUB, $safe);
                } else {
                    $removed = GDPJournal::compact($instanceId, GDPJournal::OUTBOX, (int) GDPCursorManager::get($instanceId, self::HUB_CURSOR)['accepted_source_sequence']);
                }

                $report[$instanceId] = ['segments_removed' => $removed, 'applied_pruned' => GDPAppliedIndex::prune($instanceId, $retention)];
            }

            return $report;
        });
    }
}
