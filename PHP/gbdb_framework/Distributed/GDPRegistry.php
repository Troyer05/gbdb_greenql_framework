<?php

declare(strict_types=1);

/**
 * Dauerhafte Identität dieser Installation (Konzept Kapitel 07).
 */
final class GDPNodeIdentity {
    private static ?array $identity = null;

    private static function file() :string {
        return GDPStore::path('node.json');
    }

    /**
     * Liest die Identität; beim ersten Aufruf wird sie erzeugt und gespeichert.
     *
     * @return array{node_id:string, display_name:string, role:string, created_at:int, protocol_major:int, protocol_minor:int}
     */
    public static function get() :array {
        if (self::$identity !== null) {
            return self::$identity;
        }

        $identity = GDPStore::readJson(self::file(), []);

        if (($identity['node_id'] ?? '') === '') {
            $identity = self::create();
        }

        return self::$identity = $identity;
    }

    private static function create() :array {
        $name = trim((string) GDPConfig::get('node_name'));
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name !== '' ? $name : (string) (gethostname() ?: 'gbdb')) ?? '', '-'));
        $identity = [
            'node_id' => 'node-' . substr($slug !== '' ? $slug : 'gbdb', 0, 40) . '-' . bin2hex(random_bytes(3)),
            'display_name' => $name !== '' ? $name : (string) (gethostname() ?: 'GBDB'),
            'role' => GDPConfig::role()->value,
            'created_at' => time(),
            'protocol_major' => GDPConfig::PROTOCOL_MAJOR,
            'protocol_minor' => GDPConfig::PROTOCOL_MINOR,
        ];
        GDPStore::writeJson(self::file(), $identity);
        GDPAuditLogger::log('node.identity_created', ['node_id' => $identity['node_id'], 'role' => $identity['role']]);

        return $identity;
    }

    /**
     * Abweichungen der gespeicherten Identität von der Konfiguration, typisch nach dem Kopieren einer Installation.
     *
     * @return list<string>
     */
    public static function configDrift() :array {
        $identity = self::get();
        $drift = [];

        if ((string) $identity['role'] !== GDPConfig::role()->value) {
            $drift[] = 'Rolle „' . $identity['role'] . '“ statt „' . GDPConfig::role()->value . '“';
        }

        $name = trim((string) GDPConfig::get('node_name'));

        if ($name !== '' && (string) $identity['display_name'] !== $name) {
            $drift[] = 'Name „' . $identity['display_name'] . '“ statt „' . $name . '“';
        }

        return $drift;
    }

    /**
     * Eine neue Identität ist nur gefahrlos, solange der Knoten mit niemandem verbunden ist.
     */
    public static function regenerable() :bool {
        return GDPCredentials::hubKey() === null && GDPPeerRegistry::all() === [] && GDPInstanceRegistry::all() === [];
    }

    /**
     * Neue Knotenidentität nach Konfiguration, z. B. für eine kopierte Installation.
     */
    public static function regenerate() :array {
        return GDPStore::transaction(static function () :array {
            if (!self::regenerable()) {
                throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Der Knoten ist bereits gekoppelt oder hat Replikate/Instanzen; eine neue Identität würde den Verbund trennen.');
            }

            $old = self::id();
            self::$identity = self::create();
            GDPAuditLogger::log('node.identity_regenerated', ['old_node_id' => $old, 'node_id' => self::$identity['node_id']]);

            return self::$identity;
        });
    }

    public static function id() :string {
        return (string) self::get()['node_id'];
    }

    /**
     * Die Rolle steht in der Konfiguration. Ein stiller Rollenwechsel mit
     * bestehenden Replikaten wäre gefährlich und wird als Fehler gemeldet.
     */
    public static function role() :GDPNodeRole {
        return GDPConfig::role();
    }

    public static function roleMismatch() :bool {
        return (string) self::get()['role'] !== GDPConfig::role()->value && GDPInstanceRegistry::all() !== [];
    }

    public static function rename(string $displayName) :void {
        $identity = self::get();
        $identity['display_name'] = mb_substr(trim($displayName), 0, 120);
        GDPStore::writeJson(self::file(), $identity);
        self::$identity = $identity;
    }

    public static function forget() :void {
        self::$identity = null;
    }
}

/**
 * Bekannte Gegenstellen: auf dem Hub seine Replikate, auf einem Replikat der Hub.
 */
final class GDPPeerRegistry {
    private static function file() :string {
        return GDPStore::path('nodes.json');
    }

    public static function all() :array {
        return GDPStore::readJson(self::file(), []);
    }

    public static function get(string $nodeId) :?array {
        $node = self::all()[$nodeId] ?? null;

        return is_array($node) ? $node : null;
    }

    public static function save(string $nodeId, array $values) :array {
        if (!GDPCanonical::isIdentifier($nodeId)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültige node_id.');
        }

        $all = self::all();
        $node = array_replace($all[$nodeId] ?? [
            'node_id' => $nodeId,
            'display_name' => $nodeId,
            'role' => GDPNodeRole::REPLICA->value,
            'enabled' => true,
            'status' => 'active',
            'protocol_major' => GDPConfig::PROTOCOL_MAJOR,
            'protocol_minor' => GDPConfig::PROTOCOL_MINOR,
            'created_at' => time(),
            'last_contact_at' => 0,
        ], $values, ['updated_at' => time()]);
        $all[$nodeId] = $node;
        GDPStore::writeJson(self::file(), $all);

        return $node;
    }

    public static function active(string $nodeId) :bool {
        $node = self::get($nodeId);

        return $node !== null && ($node['enabled'] ?? false) === true && ($node['status'] ?? '') === 'active';
    }

    public static function touch(string $nodeId) :void {
        if (self::get($nodeId) !== null) {
            self::save($nodeId, ['last_contact_at' => time()]);
        }
    }
}

/**
 * Registrierte logische Instanzen und ihr Replikationszustand.
 */
final class GDPInstanceRegistry {
    private static array $cache = [];

    private static function file(string $instanceId) :string {
        return GDPStore::path('instances', $instanceId, 'instance.json');
    }

    public static function get(string $instanceId) :?array {
        if (!GDPCanonical::isIdentifier($instanceId)) {
            return null;
        }

        if (array_key_exists($instanceId, self::$cache)) {
            return self::$cache[$instanceId];
        }

        $data = GDPStore::readJson(self::file($instanceId), []);

        return self::$cache[$instanceId] = $data === [] ? null : $data;
    }

    /**
     * @return array<string, array>
     */
    public static function all() :array {
        $result = [];

        foreach (GDPStore::listDirectory(GDPStore::path('instances')) as $instanceId) {
            $data = self::get($instanceId);

            if ($data !== null) {
                $result[$instanceId] = $data;
            }
        }

        return $result;
    }

    public static function replicated(string $instanceId) :bool {
        $data = self::get($instanceId);

        return $data !== null && ($data['replication_enabled'] ?? false) === true;
    }

    public static function save(string $instanceId, array $values) :array {
        if (!GDPCanonical::isIdentifier($instanceId)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Ungültige instance_id.');
        }

        $data = array_replace(self::get($instanceId) ?? [
            'instance_id' => $instanceId,
            'display_name' => $instanceId,
            'replication_enabled' => false,
            'hub_node_id' => '',
            'schema_version' => 1,
            'sync_state' => GDPSyncState::INITIALIZING->value,
            'blocked' => null,
            'bootstrap_generation' => 0,
            'current_snapshot_id' => '',
            'created_at' => time(),
            'last_sync_at' => 0,
        ], $values, ['updated_at' => time()]);
        GDPStore::writeJson(self::file($instanceId), $data);
        self::$cache[$instanceId] = $data;

        return $data;
    }

    public static function state(string $instanceId) :GDPSyncState {
        return GDPSyncState::tryFrom((string) (self::get($instanceId)['sync_state'] ?? '')) ?? GDPSyncState::INITIALIZING;
    }

    public static function setState(string $instanceId, GDPSyncState $state) :void {
        $current = self::get($instanceId);

        if ($current === null || (($current['sync_state'] ?? '') === GDPSyncState::BLOCKED->value && $state !== GDPSyncState::BLOCKED)) {
            return;
        }

        if (($current['sync_state'] ?? '') !== $state->value) {
            self::save($instanceId, ['sync_state' => $state->value]);
        }
    }

    /**
     * Bei Inkonsistenz blockieren, nachvollziehbar protokollieren (Kapitel 05, XI).
     */
    public static function block(string $instanceId, GDPException $exception) :void {
        if (self::get($instanceId) === null) {
            return;
        }

        self::save($instanceId, ['sync_state' => GDPSyncState::BLOCKED->value, 'blocked' => $exception->toArray()]);
        GDPAuditLogger::log('instance.blocked', ['instance' => $instanceId, 'code' => $exception->errorCode()->value, 'context' => $exception->context()]);
        GDPStateManager::recordFailure($exception, $instanceId);
    }

    public static function unblock(string $instanceId, GDPSyncState $state) :void {
        self::save($instanceId, ['sync_state' => $state->value, 'blocked' => null]);
    }

    public static function forget() :void {
        self::$cache = [];
    }
}

/**
 * Knotengebundene Rechte auf Instanzen (Kapitel 20). Nur auf dem Hub.
 */
final class GDPGrants {
    public const PERMISSIONS = ['pull', 'push', 'snapshot', 'status'];

    private static function file() :string {
        return GDPStore::path('security', 'grants.json');
    }

    public static function all() :array {
        return GDPStore::readJson(self::file(), []);
    }

    public static function grant(string $nodeId, string $instanceId, array $permissions, ?array $scope = null, int $expiresAt = 0) :array {
        $permissions = array_values(array_intersect(self::PERMISSIONS, $permissions));
        $all = self::all();

        foreach ($all as $id => $grant) {
            if ($grant['node_id'] === $nodeId && $grant['instance_id'] === $instanceId && ($grant['revoked_at'] ?? 0) === 0) {
                $all[$id]['revoked_at'] = time();
            }
        }

        $grant = [
            'grant_id' => 'grant-' . bin2hex(random_bytes(8)),
            'node_id' => $nodeId,
            'instance_id' => $instanceId,
            'permissions' => $permissions,
            'allowed_scope' => self::normalizeScope($scope),
            'policy_version' => GDPPolicyEngine::document($instanceId)['policy_version'],
            'expires_at' => $expiresAt,
            'revoked_at' => 0,
            'created_at' => time(),
        ];
        $all[$grant['grant_id']] = $grant;
        GDPStore::writeJson(self::file(), $all);
        GDPAuditLogger::log('grant.created', ['node_id' => $nodeId, 'instance' => $instanceId, 'permissions' => $permissions]);

        return $grant;
    }

    private static function normalizeScope(?array $scope) :?array {
        if ($scope === null) {
            return null;
        }

        $tables = [];

        foreach ((array) ($scope['tables'] ?? []) as $entry) {
            if (is_string($entry) && preg_match('/^[a-zA-Z0-9_\-äöüÄÖÜß]+\.(\*|[a-zA-Z0-9_\-äöüÄÖÜß]+)$/uD', $entry)) {
                $tables[] = $entry;
            }
        }

        return ['tables' => array_values(array_unique($tables))];
    }

    public static function revokeNode(string $nodeId) :int {
        $all = self::all();
        $count = 0;

        foreach ($all as $id => $grant) {
            if ($grant['node_id'] === $nodeId && ($grant['revoked_at'] ?? 0) === 0) {
                $all[$id]['revoked_at'] = time();
                $count++;
            }
        }

        GDPStore::writeJson(self::file(), $all);

        return $count;
    }

    public static function revokeInstance(string $instanceId) :int {
        $all = self::all();
        $count = 0;

        foreach ($all as $id => $grant) {
            if ($grant['instance_id'] === $instanceId && ($grant['revoked_at'] ?? 0) === 0) {
                $all[$id]['revoked_at'] = time();
                $count++;
            }
        }

        GDPStore::writeJson(self::file(), $all);
        GDPAuditLogger::log('grant.revoked_instance', ['instance' => $instanceId, 'grants' => $count]);

        return $count;
    }

    public static function active(string $nodeId, string $instanceId) :?array {
        foreach (self::all() as $grant) {
            if ($grant['node_id'] === $nodeId && $grant['instance_id'] === $instanceId && ($grant['revoked_at'] ?? 0) === 0
                && (($grant['expires_at'] ?? 0) === 0 || $grant['expires_at'] > time())) {
                return $grant;
            }
        }

        return null;
    }

    /**
     * Instanzen, auf die ein Knoten zugreifen darf.
     *
     * @return list<string>
     */
    public static function instancesFor(string $nodeId) :array {
        $result = [];

        foreach (self::all() as $grant) {
            if ($grant['node_id'] === $nodeId && self::active($nodeId, $grant['instance_id']) !== null) {
                $result[] = $grant['instance_id'];
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * authorize(peer, action, instance, scope) – wirft bei fehlender Berechtigung.
     */
    public static function authorize(string $nodeId, string $permission, string $instanceId) :array {
        $grant = GDPPeerRegistry::active($nodeId) ? self::active($nodeId, $instanceId) : null;

        if ($grant === null || !in_array($permission, $grant['permissions'], true)) {
            GDPAuditLogger::log('access.denied', ['node_id' => $nodeId, 'instance' => $instanceId, 'permission' => $permission]);

            throw new GDPAuthorizationException(GDPErrorCode::AUTHORIZATION_DENIED, 'Keine Berechtigung für diese Instanz oder Aktion.', ['instance' => $instanceId, 'permission' => $permission]);
        }

        return $grant;
    }

    public static function inScope(array $grant, string $database, string $table) :bool {
        $scope = $grant['allowed_scope'] ?? null;

        if ($scope === null) {
            return true;
        }

        $tables = $scope['tables'] ?? [];

        return in_array($database . '.' . $table, $tables, true) || in_array($database . '.*', $tables, true);
    }
}

/**
 * Sicherheits- und Verwaltungsprotokoll, getrennt vom Debug-Log (Kapitel 30).
 * Es nimmt nie Codes, Tokens oder Schlüssel auf.
 */
final class GDPAuditLogger {
    private const SECRET_KEYS = ['secret', 'code', 'token', 'key', 'password', 'signature', 'proof', 'wrapped'];

    public static function log(string $event, array $metadata = [], string $actor = '') :void {
        try {
            $line = json_encode([
                'event_id' => GDPCanonical::uuid7(),
                'event_type' => $event,
                'actor' => $actor !== '' ? $actor : self::actor(),
                'metadata' => self::scrub($metadata),
                'created_at' => gmdate('c'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            GDPStore::appendLine(GDPStore::path('audit', gmdate('Y-m') . '.log'), $line);
        } catch (Throwable $exception) {
            error_log('[GDP] Audit-Eintrag konnte nicht geschrieben werden: ' . $event);
        }
    }

    private static function actor() :string {
        if (class_exists('GreenQLUIv2Helper', false) && session_status() === PHP_SESSION_ACTIVE) {
            $user = GreenQLUIv2Helper::user();

            if (($user['username'] ?? '') !== '') {
                return 'admin:' . $user['username'];
            }
        }

        return PHP_SAPI === 'cli' ? 'cli' : 'system';
    }

    private static function scrub(array $metadata) :array {
        foreach ($metadata as $key => $value) {
            foreach (self::SECRET_KEYS as $secret) {
                if (is_string($key) && str_contains(strtolower($key), $secret) && $key !== 'key_id') {
                    $metadata[$key] = '[entfernt]';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $metadata[$key] = self::scrub($value);
            }
        }

        return $metadata;
    }

    /**
     * @return list<array>
     */
    public static function recent(int $limit = 100) :array {
        $directory = GDPStore::path('audit');
        $files = array_reverse(GDPStore::listDirectory($directory));
        $result = [];

        foreach ($files as $file) {
            foreach (array_reverse(GDPStore::readLines($directory . '/' . $file)) as $line) {
                $entry = json_decode($line, true);

                if (is_array($entry)) {
                    $result[] = $entry;
                }

                if (count($result) >= $limit) {
                    return $result;
                }
            }
        }

        return $result;
    }
}

/**
 * Drei unabhängige Dimensionen (Kapitel 26): Verbindung, Sync und Storage.
 */
final class GDPStateManager {
    private const MAX_FAILURES = 50;

    private static function file() :string {
        return GDPStore::path('state.json');
    }

    public static function get() :array {
        return GDPStore::readJson(self::file(), []) + [
            'connection_state' => GDPConnectionState::OFFLINE->value,
            'preference' => GDPOperationPreference::AUTO->value,
            'last_contact_at' => 0,
            'last_successful_sync_at' => 0,
            'last_error_code' => '',
            'last_error_at' => 0,
            'consecutive_failures' => 0,
            'backoff_until' => 0,
            'embedded_last_run' => 0,
            'failures' => [],
            'metrics' => [],
        ];
    }

    public static function update(array $values) :array {
        $state = array_replace(self::get(), $values);
        GDPStore::writeJson(self::file(), $state);

        return $state;
    }

    public static function preference() :GDPOperationPreference {
        return GDPOperationPreference::tryFrom((string) self::get()['preference']) ?? GDPOperationPreference::AUTO;
    }

    public static function connection() :GDPConnectionState {
        return GDPConnectionState::tryFrom((string) self::get()['connection_state']) ?? GDPConnectionState::OFFLINE;
    }

    /**
     * Gilt der Knoten für Schreibregeln als offline? FORCE_LOCAL zählt als offline.
     */
    public static function offline() :bool {
        if (GDPNodeIdentity::role() === GDPNodeRole::HUB) {
            return false;
        }

        return self::preference() === GDPOperationPreference::FORCE_LOCAL || self::connection() === GDPConnectionState::OFFLINE;
    }

    public static function storage() :GDPStorageState {
        try {
            $status = GBDBRuntime::status();

            return GDPStorageState::tryFrom((string) $status['mode']) ?? GDPStorageState::UNAVAILABLE;
        } catch (Throwable $exception) {
            return GDPStorageState::UNAVAILABLE;
        }
    }

    public static function markOnline() :void {
        self::update([
            'connection_state' => GDPConnectionState::ONLINE->value,
            'last_contact_at' => time(),
            'consecutive_failures' => 0,
            'backoff_until' => 0,
        ]);
    }

    /**
     * Exponentielles Backoff mit Jitter, höchstens fünf Minuten.
     */
    public static function markOffline(GDPException $exception) :void {
        $state = self::get();
        $failures = (int) $state['consecutive_failures'] + 1;
        $delay = min(300, 2 ** min($failures, 8)) * (random_int(500, 1000) / 1000);
        self::update([
            'connection_state' => GDPConnectionState::OFFLINE->value,
            'consecutive_failures' => $failures,
            'backoff_until' => time() + (int) ceil($delay),
        ]);
        self::recordFailure($exception);
    }

    public static function recordFailure(GDPException $exception, string $instanceId = '') :void {
        $state = self::get();
        $failures = $state['failures'];
        array_unshift($failures, $exception->toArray() + ['instance' => $instanceId]);
        self::update([
            'failures' => array_slice($failures, 0, self::MAX_FAILURES),
            'last_error_code' => $exception->errorCode()->value,
            'last_error_at' => time(),
        ]);
    }

    public static function recordMetrics(string $instanceId, array $metrics) :void {
        $state = self::get();
        $state['metrics'][$instanceId] = $metrics + ['at' => time()];
        self::update(['metrics' => $state['metrics']]);
    }

    public static function setPreference(GDPOperationPreference $preference) :void {
        self::update(['preference' => $preference->value]);
        GDPAuditLogger::log('node.preference_changed', ['preference' => $preference->value]);
    }
}
