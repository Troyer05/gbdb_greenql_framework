<?php
declare(strict_types=1);

/**
 * Wochen 70-89: Enterprise-/Intranet-Pattern, SSO-Vorbereitung, Service-Accounts,
 * Tenant-Isolation, Quotas, Rate-Limits, Import/Export, SQL-Adapter-Vorbereitung,
 * Admin-/CLI-/Developer-APIs, Doku- und Test-Helfer.
 */
trait GBDB_EnterpriseOpsTrait {
    /** Legt eine Enterprise-Konfiguration im Admin-Ops-Bereich ab. */
    private static function enterpriseConfig(string $name, array $data = [], bool $merge = true): array {
        $file = self::adminOpsDir('enterprise/' . self::safeSegment($name) . '.json', true);
        $old = $merge ? self::readJsonConfig($file, []) : [];
        $cfg = array_replace_recursive($old, $data, ['updated_at' => time()]);

        if (!isset($cfg['created_at'])) {
            $cfg['created_at'] = time();
        }

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    /** Gibt einen für API-Tokens geeigneten Hash zurück. */
    private static function tokenHash(string $token): string {
        return hash('sha256', $token);
    }

    /** Erstellt eine sichere Token-Zeichenfolge. */
    private static function makeSecretToken(string $prefix = 'gbdb'): string {
        return $prefix . '_' . bin2hex(random_bytes(24));
    }

    /** Erzeugt eine Tabelle nur dann, wenn sie fehlt. */
    private static function ensureEnterpriseTable(string $db, string $table, array $columns): bool {
        if (!in_array($db, self::listDBs(), true)) {
            self::createDatabase($db);
        }

        if (!in_array($table, self::listTables($db), true)) {
            return self::createTable($db, $table, $columns);
        }

        foreach ($columns as $col) {
            if ($col !== 'id' && !in_array($col, self::getKeys($db, $table), true)) {
                self::addColumn($db, $table, (string)$col);
            }
        }

        return true;
    }

    /* ============================================================
     * Enterprise Pattern Manager
     * ============================================================ */

    private static function patternDir(): string {
        $dir = self::rootPath() . '/json/patterns';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir;
    }

    private static function normalizePatternName(string $name): string {
        $name = Format::cleanString($name);

        return $name !== '' ? $name : 'pattern';
    }

    private static function patternFile(string $name): string {
        return self::patternDir() . '/' . self::normalizePatternName($name) . '.json';
    }

    private static function normalizePattern(array $pattern): array {
        $name = self::normalizePatternName((string)($pattern['name'] ?? 'pattern'));
        $out = [
            'name' => $name,
            'active' => array_key_exists('active', $pattern) ? (bool)$pattern['active'] : true,
            'description' => (string)($pattern['description'] ?? ''),
            'structure' => []
        ];

        foreach (($pattern['structure'] ?? []) as $baseDef) {
            if (!is_array($baseDef)) {
                continue;
            }

            $base = Format::cleanString((string)($baseDef['base'] ?? ''));

            if ($base === '') {
                continue;
            }

            $tables = [];

            foreach (($baseDef['tables'] ?? []) as $tableDef) {
                if (!is_array($tableDef)) {
                    continue;
                }

                $table = Format::cleanString((string)($tableDef['name'] ?? ''));

                if ($table === '') {
                    continue;
                }

                $rows = [];

                foreach (($tableDef['rows'] ?? []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $rowName = Format::cleanString((string)($row['rowName'] ?? $row['name'] ?? ''));

                    if ($rowName === '' || $rowName === 'id') {
                        continue;
                    }

                    $entry = [
                        'rowName' => $rowName,
                        'defaultValue' => $row['defaultValue'] ?? ''
                    ];

                    if (isset($row['dataType']) && trim((string)$row['dataType']) !== '') {
                        $entry['dataType'] = trim((string)$row['dataType']);
                    }

                    $rows[] = $entry;
                }

                $tables[] = [
                    'name' => $table,
                    'useDataTypes' => (bool)($tableDef['useDataTypes'] ?? false),
                    'rows' => $rows
                ];
            }

            $out['structure'][] = [
                'base' => $base,
                'tables' => $tables
            ];
        }

        return $out;
    }

    public static function patternTemplate(string $name = 'intranet'): array {
        $name = self::normalizePatternName($name);

        return [
            'name' => $name,
            'active' => true,
            'description' => 'GBDB Pattern Template',
            'structure' => [[
                'base' => 'main',
                'tables' => [[
                    'name' => 'users',
                    'useDataTypes' => true,
                    'rows' => [
                        ['rowName' => 'uid', 'defaultValue' => '', 'dataType' => 'string'],
                        ['rowName' => 'name', 'defaultValue' => 'Max M.', 'dataType' => 'string'],
                        ['rowName' => 'email', 'defaultValue' => '', 'dataType' => 'string'],
                        ['rowName' => 'active', 'defaultValue' => true, 'dataType' => 'bool'],
                        ['rowName' => 'created_at', 'defaultValue' => 0, 'dataType' => 'int']
                    ]
                ]]
            ]]
        ];
    }

    public static function listPatterns(bool $includeInactive = true): array {
        $patterns = [];

        foreach (glob(self::patternDir() . '/*.json') ?: [] as $file) {
            $data = json_decode((string)@file_get_contents($file), true);

            if (!is_array($data)) {
                continue;
            }

            $pattern = self::normalizePattern($data);

            if (!$includeInactive && empty($pattern['active'])) {
                continue;
            }

            $pattern['_file'] = basename($file);
            $patterns[] = $pattern;
        }

        usort($patterns, fn($a, $b) => strnatcasecmp((string)$a['name'], (string)$b['name']));

        return $patterns;
    }

    public static function getPattern(string $name): array {
        $file = self::patternFile($name);

        if (!is_file($file)) {
            return ['ok' => false, 'error' => 'pattern_not_found'];
        }

        $data = json_decode((string)@file_get_contents($file), true);

        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'pattern_invalid'];
        }

        return [
            'ok' => true,
            'pattern' => self::normalizePattern($data)
        ];
    }

    public static function savePattern(array $pattern): array {
        $pattern = self::normalizePattern($pattern);
        $json = json_encode($pattern, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return ['ok' => false, 'error' => 'json_encode_failed'];
        }

        $ok = @file_put_contents(self::patternFile((string)$pattern['name']), $json . PHP_EOL, LOCK_EX) !== false;

        return [
            'ok' => $ok,
            'pattern' => $pattern,
            'error' => $ok ? '' : 'pattern_write_failed'
        ];
    }

    public static function deletePattern(string $name): array {
        $file = self::patternFile($name);
        $ok = is_file($file) && @unlink($file);

        return [
            'ok' => $ok,
            'name' => self::normalizePatternName($name)
        ];
    }

    public static function renamePattern(string $name, string $newName): array {
        $oldName = self::normalizePatternName($name);
        $newName = self::normalizePatternName($newName);

        if ($oldName === '' || $newName === '' || $oldName === $newName) {
            return ['ok' => false, 'error' => 'invalid_pattern_name'];
        }

        $loaded = self::getPattern($oldName);

        if (!($loaded['ok'] ?? false)) {
            return $loaded;
        }

        $target = self::patternFile($newName);

        if (is_file($target)) {
            return ['ok' => false, 'error' => 'pattern_exists'];
        }

        $pattern = $loaded['pattern'];
        $pattern['name'] = $newName;
        $saved = self::savePattern($pattern);

        if (!($saved['ok'] ?? false)) {
            return $saved;
        }

        $oldFile = self::patternFile($oldName);

        if (is_file($oldFile)) {
            @unlink($oldFile);
        }

        return [
            'ok' => true,
            'old' => $oldName,
            'name' => $newName,
            'pattern' => $pattern
        ];
    }

    public static function setPatternActive(string $name, bool $active): array {
        $loaded = self::getPattern($name);

        if (!($loaded['ok'] ?? false)) {
            return $loaded;
        }

        $pattern = $loaded['pattern'];
        $pattern['active'] = $active;

        return self::savePattern($pattern);
    }

    public static function installPattern(string $patternName, string $instanceName, bool $allowInactive = false): array {
        $loaded = self::getPattern($patternName);

        if (!($loaded['ok'] ?? false)) {
            return $loaded;
        }

        $pattern = $loaded['pattern'];

        if (empty($pattern['active']) && !$allowInactive) {
            return ['ok' => false, 'error' => 'pattern_inactive'];
        }

        $instanceName = Format::cleanString($instanceName);

        if ($instanceName === '' || self::isInternalInstanceName($instanceName)) {
            return ['ok' => false, 'error' => 'invalid_instance'];
        }

        $old = self::getInstance();
        self::createInstance($instanceName);
        self::setInstance($instanceName);

        $created = [
            'bases' => [],
            'tables' => []
        ];

        try {
            foreach ($pattern['structure'] as $baseDef) {
                $base = (string)$baseDef['base'];

                if (!self::exists($base)) {
                    self::createDatabase($base);
                    $created['bases'][] = $base;
                }

                foreach (($baseDef['tables'] ?? []) as $tableDef) {
                    $table = (string)$tableDef['name'];
                    $rows = is_array($tableDef['rows'] ?? null) ? $tableDef['rows'] : [];
                    $columns = array_values(array_filter(
                        array_map(fn($r) => (string)($r['rowName'] ?? ''), $rows),
                        fn($c) => $c !== '' && $c !== 'id'
                    ));

                    if (empty($columns)) {
                        $columns = ['name', 'value'];
                    }

                    if (!self::exists($base, $table)) {
                        self::createTable($base, $table, $columns);
                        $created['tables'][] = $base . '.' . $table;
                    }

                    foreach ($columns as $column) {
                        if (!in_array($column, self::getKeys($base, $table), true)) {
                            self::addColumn($base, $table, $column);
                        }
                    }

                    if (!empty($tableDef['useDataTypes'])) {
                        $schema = self::readSchema();
                        $schema[$instanceName][$base][$table] = self::normalizeSchemaTableEntry($schema[$instanceName][$base][$table] ?? []);

                        foreach ($rows as $row) {
                            $col = (string)($row['rowName'] ?? '');

                            if ($col === '' || $col === 'id') {
                                continue;
                            }

                            $schema[$instanceName][$base][$table]['columns'][$col] = [
                                'type' => (string)($row['dataType'] ?? 'mixed'),
                                'default' => $row['defaultValue'] ?? '',
                                'nullable' => true,
                                'required' => false,
                                'unique' => false
                            ];
                        }

                        self::writeSchema($schema);
                    }
                }
            }
        } finally {
            self::setInstance($old);
        }

        self::adminLog('pattern_install', [
            'pattern' => $pattern['name'],
            'instance' => $instanceName,
            'created' => $created
        ]);

        return [
            'ok' => true,
            'pattern' => $pattern['name'],
            'instance' => $instanceName,
            'created' => $created
        ];
    }

    /* ============================================================
     * Woche 70-71: Intranet Pattern
     * ============================================================ */

    public static function installIntranetPattern(string $database = 'intranet'): array {
        $tables = [
            'users' => ['uid', 'username', 'email', 'display_name', 'department_id', 'team_id', 'role_ids', 'active', 'sso_subject', 'manager_uid', 'created_at', 'updated_at'],
            'groups' => ['gid', 'name', 'description', 'member_uids', 'owner_uid', 'created_at', 'updated_at'],
            'departments' => ['did', 'name', 'parent_id', 'lead_uid', 'policy_ids', 'created_at', 'updated_at'],
            'teams' => ['tid', 'department_id', 'name', 'lead_uid', 'member_uids', 'created_at', 'updated_at'],
            'roles' => ['rid', 'name', 'description', 'permission_ids', 'scope', 'created_at', 'updated_at'],
            'permissions' => ['pid', 'key', 'description', 'scope', 'created_at', 'updated_at'],
            'documents' => ['doc_id', 'title', 'slug', 'owner_uid', 'department_id', 'classification', 'status', 'current_version', 'retention_rule', 'created_at', 'updated_at'],
            'document_versions' => ['version_id', 'doc_id', 'version', 'content_path', 'checksum', 'author_uid', 'created_at'],
            'announcements' => ['aid', 'title', 'body', 'target_scope', 'starts_at', 'ends_at', 'author_uid', 'created_at', 'updated_at'],
            'workflows' => ['wid', 'name', 'target_type', 'steps_json', 'active', 'created_at', 'updated_at'],
            'approvals' => ['approval_id', 'workflow_id', 'target_type', 'target_id', 'requester_uid', 'approver_uid', 'status', 'comment', 'created_at', 'updated_at'],
            'comments' => ['comment_id', 'target_type', 'target_id', 'uid', 'body', 'parent_id', 'created_at', 'updated_at'],
            'audit_logs' => ['log_id', 'actor_uid', 'action', 'target_type', 'target_id', 'payload_json', 'ip', 'created_at'],
            'sso_user_mapping' => ['map_id', 'provider', 'subject', 'uid', 'email', 'last_seen_at', 'created_at', 'updated_at'],
            'department_policies' => ['policy_id', 'department_id', 'name', 'rules_json', 'created_at', 'updated_at'],
            'document_permissions' => ['dp_id', 'doc_id', 'subject_type', 'subject_id', 'permission', 'created_at'],
            'retention_rules' => ['rule_id', 'name', 'days', 'action', 'created_at', 'updated_at'],
            'admin_delegations' => ['delegation_id', 'uid', 'scope_type', 'scope_id', 'permissions_json', 'created_at', 'updated_at']
        ];

        $created = [];

        foreach ($tables as $table => $columns) {
            $exists = in_array($table, self::listTables($database), true);

            self::ensureEnterpriseTable($database, $table, $columns);

            if (!$exists && in_array($table, self::listTables($database), true)) {
                $created[] = $table;
            }
        }

        $seeded = 0;

        if (empty(self::getData($database, 'roles') ?: [])) {
            foreach ([
                ['rid' => 'role_admin', 'name' => 'Admin', 'description' => 'Full intranet administration', 'permission_ids' => 'perm_all', 'scope' => '*'],
                ['rid' => 'role_editor', 'name' => 'Editor', 'description' => 'Create and maintain content', 'permission_ids' => 'perm_read,perm_write', 'scope' => 'content'],
                ['rid' => 'role_reader', 'name' => 'Reader', 'description' => 'Read-only intranet access', 'permission_ids' => 'perm_read', 'scope' => 'content']
            ] as $row) {
                self::insertData($database, 'roles', $row + [
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
                $seeded++;
            }
        }

        if (empty(self::getData($database, 'permissions') ?: [])) {
            foreach ([
                ['pid' => 'perm_all', 'key' => '*', 'description' => 'All permissions', 'scope' => '*'],
                ['pid' => 'perm_read', 'key' => 'read', 'description' => 'Read content and directory data', 'scope' => 'content'],
                ['pid' => 'perm_write', 'key' => 'write', 'description' => 'Create and update content', 'scope' => 'content'],
                ['pid' => 'perm_approve', 'key' => 'approve', 'description' => 'Approve workflows and documents', 'scope' => 'workflow']
            ] as $row) {
                self::insertData($database, 'permissions', $row + [
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
                $seeded++;
            }
        }

        if (empty(self::getData($database, 'retention_rules') ?: [])) {
            self::insertData($database, 'retention_rules', [
                'rule_id' => self::nowId('ret'),
                'name' => 'Default 365 days',
                'days' => 365,
                'action' => 'archive',
                'created_at' => time(),
                'updated_at' => time()
            ]);
            $seeded++;
        }

        if (empty(self::getData($database, 'audit_logs') ?: [])) {
            self::insertData($database, 'audit_logs', [
                'log_id' => self::nowId('audit'),
                'actor_uid' => 'system',
                'action' => 'intranet_pattern_install',
                'target_type' => 'database',
                'target_id' => $database,
                'payload_json' => json_encode(['tables' => array_keys($tables)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                'ip' => '',
                'created_at' => time()
            ]);
            $seeded++;
        }

        self::enterpriseConfig('intranet_pattern', [
            'database' => $database,
            'tables' => array_keys($tables),
            'created_tables' => $created,
            'seeded_rows' => $seeded,
            'status' => 'installed'
        ]);

        self::adminLog('intranet_pattern_install', [
            'database' => $database,
            'tables' => count($tables),
            'created' => count($created),
            'seeded' => $seeded
        ]);

        return [
            'ok' => true,
            'database' => $database,
            'tables' => array_keys($tables),
            'created' => $created,
            'seeded' => $seeded
        ];
    }

    public static function intranetAudit(string $action, array $payload = [], string $database = 'intranet'): array {
        self::installIntranetPattern($database);

        $id = self::insertData($database, 'audit_logs', [
            'log_id' => self::nowId('audit'),
            'actor_uid' => (string)($payload['actor_uid'] ?? ''),
            'action' => $action,
            'target_type' => (string)($payload['target_type'] ?? ''),
            'target_id' => (string)($payload['target_id'] ?? ''),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'ip' => (string)($payload['ip'] ?? ''),
            'created_at' => time()
        ]);

        return [
            'ok' => $id > 0,
            'id' => $id
        ];
    }

    public static function intranetDirectorySearch(string $query, string $database = 'intranet', int $limit = 50): array {
        self::installIntranetPattern($database);

        $q = mb_strtolower(trim($query));
        $out = [];

        foreach (self::getData($database, 'users') ?: [] as $row) {
            if (!is_array($row) || self::isHeaderRow($row)) {
                continue;
            }

            $hay = mb_strtolower(implode(' ', array_map('strval', [
                $row['username'] ?? '',
                $row['email'] ?? '',
                $row['display_name'] ?? ''
            ])));

            if ($q === '' || str_contains($hay, $q)) {
                $out[] = $row;
            }

            if (count($out) >= max(1, $limit)) {
                break;
            }
        }

        return [
            'ok' => true,
            'query' => $query,
            'rows' => $out
        ];
    }

    public static function defineDocumentPermission(
        string $docId,
        string $subjectType,
        string $subjectId,
        string $permission,
        string $database = 'intranet'
    ): array {
        self::installIntranetPattern($database);

        $id = self::insertData($database, 'document_permissions', [
            'dp_id' => self::nowId('dp'),
            'doc_id' => $docId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'permission' => $permission,
            'created_at' => time()
        ]);

        return [
            'ok' => $id > 0,
            'id' => $id
        ];
    }

    public static function defineRetentionRule(string $name, int $days, string $action = 'archive', string $database = 'intranet'): array {
        self::installIntranetPattern($database);

        $id = self::insertData($database, 'retention_rules', [
            'rule_id' => self::nowId('ret'),
            'name' => $name,
            'days' => max(1, $days),
            'action' => $action,
            'created_at' => time(),
            'updated_at' => time()
        ]);

        return [
            'ok' => $id > 0,
            'id' => $id
        ];
    }

    /* ============================================================
     * Woche 72: LDAP/SAML/OIDC/OAuth2 Sync-Vorbereitung
     * ============================================================ */

    public static function prepareSsoAdapter(string $type, array $config = []): array {
        $type = strtolower(trim($type));

        if (!in_array($type, ['ldap', 'saml', 'oidc', 'oauth2'], true)) {
            return [
                'ok' => false,
                'error' => 'unsupported_adapter'
            ];
        }

        $defaults = [
            'ldap' => ['enabled' => false, 'host' => '', 'base_dn' => '', 'user_filter' => '(uid={username})', 'tls' => true],
            'saml' => ['enabled' => false, 'entity_id' => '', 'sso_url' => '', 'certificate_path' => '', 'attribute_uid' => 'NameID'],
            'oidc' => ['enabled' => false, 'issuer' => '', 'client_id' => '', 'client_secret_ref' => '', 'scopes' => ['openid', 'email', 'profile']],
            'oauth2' => ['enabled' => false, 'authorize_url' => '', 'token_url' => '', 'client_id' => '', 'client_secret_ref' => '', 'scopes' => []]
        ];

        return [
            'ok' => true,
            'adapter' => $type,
            'config' => self::enterpriseConfig('sso_' . $type, array_replace_recursive($defaults[$type], $config))
        ];
    }

    public static function prepareLdapAdapter(array $config = []): array {
        return self::prepareSsoAdapter('ldap', $config);
    }

    public static function prepareSamlAdapter(array $config = []): array {
        return self::prepareSsoAdapter('saml', $config);
    }

    public static function prepareOidcAdapter(array $config = []): array {
        return self::prepareSsoAdapter('oidc', $config);
    }

    public static function prepareOAuth2Adapter(array $config = []): array {
        return self::prepareSsoAdapter('oauth2', $config);
    }

    public static function externalUserSync(array $users, string $database = 'intranet'): array {
        self::installIntranetPattern($database);

        $done = 0;

        foreach ($users as $u) {
            if (!is_array($u)) {
                continue;
            }

            $email = (string)($u['email'] ?? '');

            if ($email === '') {
                continue;
            }

            $exists = self::getData($database, 'users', true, 'email', $email);

            if (is_array($exists) && !empty($exists)) {
                continue;
            }

            self::insertData($database, 'users', array_replace([
                'uid' => self::nowId('uid'),
                'username' => $email,
                'email' => $email,
                'display_name' => $email,
                'active' => '1',
                'created_at' => time(),
                'updated_at' => time()
            ], $u));

            $done++;
        }

        return [
            'ok' => true,
            'inserted' => $done
        ];
    }

    public static function groupSync(array $groups, string $database = 'intranet'): array {
        self::installIntranetPattern($database);

        $n = 0;

        foreach ($groups as $g) {
            if (is_array($g) && !empty($g['name'])) {
                self::insertData($database, 'groups', array_replace([
                    'gid' => self::nowId('grp'),
                    'created_at' => time(),
                    'updated_at' => time()
                ], $g));

                $n++;
            }
        }

        return [
            'ok' => true,
            'inserted' => $n
        ];
    }

    public static function departmentSync(array $departments, string $database = 'intranet'): array {
        self::installIntranetPattern($database);

        $n = 0;

        foreach ($departments as $d) {
            if (is_array($d) && !empty($d['name'])) {
                self::insertData($database, 'departments', array_replace([
                    'did' => self::nowId('dep'),
                    'created_at' => time(),
                    'updated_at' => time()
                ], $d));

                $n++;
            }
        }

        return [
            'ok' => true,
            'inserted' => $n
        ];
    }

    public static function roleMapping(array $mapping): array {
        return [
            'ok' => true,
            'mapping' => self::enterpriseConfig('sso_role_mapping', ['mapping' => $mapping])
        ];
    }

    public static function ssoAudit(string $provider, string $subject, string $action, array $payload = []): array {
        return [
            'ok' => self::adminLog('sso_audit', compact('provider', 'subject', 'action', 'payload'))
        ];
    }

    /* ============================================================
     * Woche 73: Service Accounts / API Tokens / Sessions
     * ============================================================ */

    public static function createServiceAccount(string $name, array $scopes = [], array $options = []): array {
        $cfg = self::opsJson('auth/service_accounts.json', ['accounts' => []]);
        $id = self::nowId('svc');
        $cfg['accounts'][$id] = [
            'id' => $id,
            'name' => $name,
            'scopes' => $scopes,
            'active' => (bool)($options['active'] ?? true),
            'owner' => (string)($options['owner'] ?? ''),
            'created_at' => time(),
            'updated_at' => time()
        ];

        self::saveOpsJson('auth/service_accounts.json', $cfg);
        self::adminLog('service_account_create', [
            'id' => $id,
            'name' => $name
        ]);

        return [
            'ok' => true,
            'account' => $cfg['accounts'][$id]
        ];
    }

    public static function createApiToken(string $subject, array $scopes = [], int $ttlSeconds = 2592000): array {
        $plain = self::makeSecretToken('gbdbtok');
        $cfg = self::opsJson('auth/api_tokens.json', ['tokens' => []]);
        $id = self::nowId('tok');
        $cfg['tokens'][$id] = [
            'id' => $id,
            'subject' => $subject,
            'hash' => self::tokenHash($plain),
            'scopes' => $scopes,
            'active' => true,
            'created_at' => time(),
            'expires_at' => time() + max(60, $ttlSeconds),
            'rotated_from' => ''
        ];

        self::saveOpsJson('auth/api_tokens.json', $cfg);
        self::adminLog('api_token_create', [
            'id' => $id,
            'subject' => $subject,
            'scopes' => $scopes
        ]);

        return [
            'ok' => true,
            'id' => $id,
            'token' => $plain,
            'expires_at' => $cfg['tokens'][$id]['expires_at']
        ];
    }

    public static function validateApiToken(string $token, string $scope = ''): array {
        $hash = self::tokenHash($token);
        $cfg = self::opsJson('auth/api_tokens.json', ['tokens' => []]);

        foreach ($cfg['tokens'] as $id => $t) {
            if (($t['hash'] ?? '') === $hash && !empty($t['active']) && (int)($t['expires_at'] ?? 0) >= time()) {
                $ok = $scope === ''
                    || in_array('*', $t['scopes'] ?? [], true)
                    || in_array($scope, $t['scopes'] ?? [], true);

                return [
                    'ok' => $ok,
                    'id' => $id,
                    'subject' => $t['subject'] ?? '',
                    'scopes' => $t['scopes'] ?? []
                ];
            }
        }

        return [
            'ok' => false,
            'error' => 'invalid_token'
        ];
    }

    public static function rotateApiToken(string $tokenId, int $ttlSeconds = 2592000): array {
        $cfg = self::opsJson('auth/api_tokens.json', ['tokens' => []]);

        if (!isset($cfg['tokens'][$tokenId])) {
            return [
                'ok' => false,
                'error' => 'token_not_found'
            ];
        }

        $old = $cfg['tokens'][$tokenId];
        $cfg['tokens'][$tokenId]['active'] = false;
        $new = self::createApiToken((string)$old['subject'], is_array($old['scopes'] ?? null) ? $old['scopes'] : [], $ttlSeconds);
        $cfg2 = self::opsJson('auth/api_tokens.json', ['tokens' => []]);
        $cfg2['tokens'][$new['id']]['rotated_from'] = $tokenId;

        self::saveOpsJson('auth/api_tokens.json', $cfg2);

        return [
            'ok' => true,
            'old' => $tokenId,
            'new' => $new
        ];
    }

    public static function registerSession(string $uid, array $device = [], int $ttlSeconds = 172800): array {
        $cfg = self::opsJson('auth/sessions.json', ['sessions' => []]);
        $sid = self::nowId('sess');
        $cfg['sessions'][$sid] = [
            'id' => $sid,
            'uid' => $uid,
            'device' => $device,
            'active' => true,
            'created_at' => time(),
            'last_seen_at' => time(),
            'expires_at' => time() + max(60, $ttlSeconds),
            'forced_logout' => false
        ];

        self::saveOpsJson('auth/sessions.json', $cfg);

        return [
            'ok' => true,
            'session' => $cfg['sessions'][$sid]
        ];
    }

    public static function forceLogout(string $uid): array {
        $cfg = self::opsJson('auth/sessions.json', ['sessions' => []]);
        $n = 0;

        foreach ($cfg['sessions'] as &$s) {
            if (($s['uid'] ?? '') === $uid) {
                $s['active'] = false;
                $s['forced_logout'] = true;
                $n++;
            }
        }

        self::saveOpsJson('auth/sessions.json', $cfg);
        self::adminLog('forced_logout', [
            'uid' => $uid,
            'sessions' => $n
        ]);

        return [
            'ok' => true,
            'sessions' => $n
        ];
    }

    public static function sessionList(string $uid = ''): array {
        $cfg = self::opsJson('auth/sessions.json', ['sessions' => []]);
        $rows = array_values(array_filter($cfg['sessions'], fn($s) => $uid === '' || ($s['uid'] ?? '') === $uid));

        return [
            'ok' => true,
            'sessions' => $rows
        ];
    }

    /* ============================================================
     * Woche 74-77: Tenant Isolation, Quotas, Limits, Rate Limits
     * ============================================================ */

    public static function tenantPolicy(string $tenant, array $policy = []): array {
        return [
            'ok' => true,
            'tenant' => self::safeSegment($tenant),
            'policy' => self::enterpriseConfig('tenant_policy_' . self::safeSegment($tenant), array_replace_recursive([
                'hard_isolation' => true,
                'allow_cross_tenant_reads' => false,
                'allow_cross_tenant_writes' => false
            ], $policy))
        ];
    }

    public static function tenantQuotas(string $tenant, array $quotas = []): array {
        return [
            'ok' => true,
            'tenant' => $tenant,
            'quotas' => self::enterpriseConfig('tenant_quota_' . self::safeSegment($tenant), array_replace([
                'max_storage_bytes' => 1073741824,
                'max_rows_per_table' => 100000,
                'max_users' => 5000,
                'max_tables' => 500,
                'max_indexes' => 1000,
                'max_backups' => 20,
                'max_media_bytes' => 52428800,
                'max_api_calls_per_minute' => 600
            ], $quotas))
        ];
    }

    public static function tenantRestore(string $tenant, string $backupPath): array {
        $target = self::dbRootPath('.temp/tenant_restore_' . self::safeSegment($tenant) . '_' . date('Ymd_His'), true);
        $report = [
            'ok' => true,
            'files' => [],
            'errors' => []
        ];

        self::copyTreeInternal(rtrim($backupPath, '/') . '/data', $target, $report);
        self::adminLog('tenant_restore_prepare', [
            'tenant' => $tenant,
            'backup' => $backupPath,
            'target' => $target
        ]);

        return [
            'ok' => empty($report['errors']),
            'prepared' => true,
            'target' => $target,
            'copy' => $report
        ];
    }

    public static function tenantExport(string $tenant, string $target = ''): array {
        return self::tenantBackup($tenant, $target);
    }

    public static function tenantDelete(string $tenant, bool $force = false): array {
        $tenant = self::safeSegment($tenant);

        if (!$force && $tenant === 'default') {
            return [
                'ok' => false,
                'error' => 'default_requires_force'
            ];
        }

        $old = self::getInstance();
        self::setInstance($tenant);
        $path = self::instancePath(false);
        self::setInstance($old);

        $backup = self::tenantBackup($tenant);

        if ($force && is_dir($path)) {
            self::deleteTreeInternal($path);
        }

        self::adminLog('tenant_delete', [
            'tenant' => $tenant,
            'force' => $force,
            'backup' => $backup['path'] ?? ''
        ]);

        return [
            'ok' => true,
            'tenant' => $tenant,
            'deleted' => $force,
            'backup' => $backup
        ];
    }

    public static function prepareTenantMigration(string $tenant, string $targetShard, array $options = []): array {
        return self::storeSystemPlan('tenant_migration_' . self::safeSegment($tenant), [
            'type' => 'tenant_migration',
            'tenant' => $tenant,
            'target_shard' => $targetShard,
            'options' => $options,
            'status' => 'prepared',
            'created_at' => time()
        ]);
    }

    public static function tenantStorageStats(string $tenant = ''): array {
        $old = self::getInstance();

        if ($tenant !== '') {
            self::setInstance($tenant);
        }

        $path = self::instancePath(false);
        $size = is_dir($path) ? self::folderSize($path) : 0;

        self::setInstance($old);

        return [
            'ok' => true,
            'tenant' => $tenant !== '' ? $tenant : $old,
            'path' => $path,
            'bytes' => $size
        ];
    }

    private static function folderSize(string $path): int {
        $size = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $f) {
            if ($f->isFile()) {
                $size += (int)$f->getSize();
            }
        }

        return $size;
    }

    public static function tenantAdminRoles(string $tenant, array $roles): array {
        return [
            'ok' => true,
            'roles' => self::enterpriseConfig('tenant_admin_roles_' . self::safeSegment($tenant), ['roles' => $roles])
        ];
    }

    public static function tenantEncryptionKeysPrepared(string $tenant, array $options = []): array {
        return [
            'ok' => true,
            'config' => self::enterpriseConfig('tenant_encryption_' . self::safeSegment($tenant), array_replace([
                'enabled' => false,
                'key_ref' => '',
                'rotation_days' => 90,
                'status' => 'prepared'
            ], $options))
        ];
    }

    public static function quotaLimits(array $limits = []): array {
        $defaults = [
            'max_rows_per_table' => 100000,
            'max_storage_per_instance' => 1073741824,
            'max_storage_per_tenant' => 1073741824,
            'max_query_time' => 10,
            'max_query_memory' => 33554432,
            'max_upload_size' => 52428800,
            'max_jobs' => 10000,
            'max_api_calls' => 100000,
            'max_users' => 10000,
            'max_tables' => 1000,
            'max_indexes' => 2000,
            'max_backups' => 50,
            'max_media_size' => 104857600,
            'warnings' => true,
            'enforce' => true
        ];

        $file = self::adminOpsDir('enterprise/' . self::safeSegment('quota_limits') . '.json', true);
        $current = self::readJsonConfig($file, []);

        if ($limits === []) {
            $cfg = array_replace($defaults, is_array($current) ? $current : []);
            self::writeJsonConfig($file, $cfg);

            return $cfg;
        }

        $cfg = array_replace($defaults, is_array($current) ? $current : [], $limits);

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function quotaCheck(string $database = '', string $table = ''): array {
        $limits = self::quotaLimits();
        $warnings = [];
        $errors = [];

        if ($database !== '' && $table !== '') {
            $rows = count(self::getData($database, $table) ?: []);

            if ($rows >= (int)$limits['max_rows_per_table']) {
                $errors[] = 'max_rows_per_table';
            } elseif ($rows >= (int)($limits['max_rows_per_table'] * .8)) {
                $warnings[] = 'max_rows_per_table_80';
            }
        }

        $storage = self::tenantStorageStats();

        if ($storage['bytes'] >= (int)$limits['max_storage_per_instance']) {
            $errors[] = 'max_storage_per_instance';
        } elseif ($storage['bytes'] >= (int)($limits['max_storage_per_instance'] * .8)) {
            $warnings[] = 'max_storage_per_instance_80';
        }

        return [
            'ok' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors,
            'limits' => $limits,
            'storage' => $storage
        ];
    }

    public static function quotaDashboard(bool $includeTenants = false): array {
        $tenants = $includeTenants
            ? array_map(fn($i) => self::tenantStorageStats((string)$i), self::listInstances())
            : [];

        return [
            'ok' => true,
            'limits' => self::quotaLimits(),
            'current' => self::quotaCheck(),
            'tenants' => $tenants,
            'tenants_loaded' => $includeTenants
        ];
    }

    public static function rateLimit(string $bucket, string $key, int $limit, int $windowSeconds = 60): array {
        $bucket = self::safeSegment($bucket);
        $hash = hash('sha256', $bucket . '|' . $key);
        $file = self::adminOpsDir('rate_runtime/' . $bucket . '_' . $hash . '.json', true);
        $now = time();
        $data = self::readJsonConfig($file, [
            'count' => 0,
            'reset_at' => $now + $windowSeconds
        ]);

        if ((int)$data['reset_at'] <= $now) {
            $data = [
                'count' => 0,
                'reset_at' => $now + $windowSeconds
            ];
        }

        $data['count'] = (int)$data['count'] + 1;

        self::writeJsonConfig($file, $data);

        $ok = $data['count'] <= max(1, $limit);

        if (!$ok) {
            self::adminLog('rate_limit_hit', compact('bucket', 'key', 'limit', 'windowSeconds'));
        }

        return [
            'ok' => $ok,
            'count' => $data['count'],
            'limit' => $limit,
            'reset_at' => $data['reset_at']
        ];
    }

    public static function configureRateLimits(array $limits): array {
        return self::enterpriseConfig('rate_limits_policy', ['limits' => $limits]);
    }

    public static function rateLimitLogs(int $limit = 100): array {
        return self::maintenanceLogs($limit);
    }

    /* ============================================================
     * Woche 78-79: Import/Export und Adapter-Vorbereitung
     * ============================================================ */

    public static function exportRows(string $database, string $table, string $format = 'json', string $target = ''): array {
        $rows = self::getData($database, $table) ?: [];
        $format = strtolower($format);
        $target = $target !== ''
            ? $target
            : self::adminOpsDir('exports/' . date('Ymd_His') . '_' . self::safeSegment($database) . '_' . self::safeSegment($table) . '.' . $format, true);

        $payload = '';

        if ($format === 'json') {
            $payload = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        } elseif ($format === 'ndjson') {
            foreach ($rows as $r) {
                if (is_array($r) && !self::isHeaderRow($r)) {
                    $payload .= (json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') . "\n";
                }
            }
        } elseif ($format === 'csv') {
            $keys = self::getKeys($database, $table);
            $fh = fopen('php://temp', 'r+');

            fputcsv($fh, $keys);

            foreach ($rows as $r) {
                if (is_array($r) && !self::isHeaderRow($r)) {
                    fputcsv($fh, array_map(fn($k) => $r[$k] ?? '', $keys));
                }
            }

            rewind($fh);
            $payload = (string)stream_get_contents($fh);
            fclose($fh);
        } else {
            return [
                'ok' => false,
                'error' => 'unsupported_format'
            ];
        }

        $ok = GBDBStorage::atomicWrite($target, $payload);

        self::adminLog('export', compact('database', 'table', 'format', 'target'));

        return [
            'ok' => $ok,
            'path' => $target,
            'rows' => count($rows),
            'format' => $format
        ];
    }

    public static function importRows(string $database, string $table, string $file, string $format = 'json', bool $rollback = true): array {
        if (!is_file($file)) {
            return [
                'ok' => false,
                'error' => 'file_not_found'
            ];
        }

        $format = strtolower($format);
        $rows = [];

        if ($format === 'json') {
            $d = json_decode((string)file_get_contents($file), true);
            $rows = is_array($d) ? $d : [];
        } elseif ($format === 'ndjson') {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                $d = json_decode($line, true);

                if (is_array($d)) {
                    $rows[] = $d;
                }
            }
        } elseif ($format === 'csv') {
            $fh = fopen($file, 'r');

            if (!$fh) {
                return [
                    'ok' => false,
                    'error' => 'csv_open_failed'
                ];
            }

            $head = fgetcsv($fh) ?: [];

            while (($r = fgetcsv($fh)) !== false) {
                $rows[] = array_combine($head, $r) ?: [];
            }

            fclose($fh);
        } else {
            return [
                'ok' => false,
                'error' => 'unsupported_format'
            ];
        }

        $valid = self::validateImportRows($database, $table, $rows);

        if (!($valid['ok'] ?? false)) {
            return $valid;
        }

        $backup = $rollback ? self::tableBackup($database, $table) : null;
        $res = self::bulkInsert($database, $table, $rows, true);

        if (!($res['ok'] ?? false) && $rollback) {
            self::adminLog('import_rollback_prepare', ['backup' => $backup]);
        }

        self::adminLog('import', compact('database', 'table', 'file', 'format'));

        return [
            'ok' => $res['ok'] ?? false,
            'inserted' => count($res['ids'] ?? []),
            'errors' => $res['errors'] ?? [],
            'backup' => $backup
        ];
    }

    public static function validateImportRows(string $database, string $table, array $rows): array {
        $keys = self::getKeys($database, $table);
        $bad = [];

        foreach ($rows as $i => $r) {
            if (!is_array($r)) {
                $bad[] = [
                    'index' => $i,
                    'error' => 'row_not_array'
                ];
                continue;
            }

            foreach (array_keys($r) as $k) {
                if ($k !== 'id' && !in_array($k, $keys, true)) {
                    $bad[] = [
                        'index' => $i,
                        'error' => 'unknown_column',
                        'column' => $k
                    ];
                }
            }
        }

        return [
            'ok' => empty($bad),
            'errors' => $bad,
            'rows' => count($rows)
        ];
    }

    public static function gbdbDump(string $target = ''): array {
        return self::fullBackup($target);
    }

    public static function gbdbRestore(string $backupPath): array {
        return self::restoreTest($backupPath);
    }

    public static function streamingImport(string $database, string $table, string $file, string $format = 'ndjson', int $chunkSize = 500): array {
        return self::importRows($database, $table, $file, $format, true);
    }

    public static function streamingExport(string $database, string $table, string $format = 'ndjson', string $target = ''): array {
        return self::exportRows($database, $table, $format, $target);
    }

    public static function exportPermissions(array $rules = []): array {
        return [
            'ok' => true,
            'config' => self::enterpriseConfig('export_permissions', ['rules' => $rules])
        ];
    }

    public static function exportLogs(int $limit = 100): array {
        return self::maintenanceLogs($limit);
    }

    /** Gibt die ehrliche SQL-Ersatz-Feature-Matrix zurueck. */
    public static function sqlErsatzFeatureMatrix(): array {
        return [
            'greenql_basic_pick' => ['status' => 'stable', 'production_ready' => true, 'note' => 'PICK mit einfacher und komplexer WHERE-Filterung.'],
            'greenql_where_complex' => ['status' => 'beta', 'production_ready' => false, 'note' => 'AND, OR, NOT, Klammern, IN, BETWEEN, NULL und LIKE werden als AST geparst.'],
            'schema_constraints' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'Schema-Typen existieren teilweise; harte Engine-Constraints sind noch auszubauen.'],
            'primary_unique_indexes' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'Index-/Meta-Strukturen vorhanden, produktionsharte PK/Unique-Pflege noch offen.'],
            'query_planner_indexes' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'QueryPlan existiert, aktive Index-Nutzung muss weiter verdrahtet werden.'],
            'querybuilder_2' => ['status' => 'prepared', 'production_ready' => false, 'note' => 'QueryBuilder ist vorhanden, 2.0-Komfort-API noch nicht final.'],
            'joins' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'Join-Funktionen sind nicht als vollstaendige SQL-Join-Engine freigegeben.'],
            'transactions_recovery' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'Transaktions-/WAL-Bausteine vorhanden, Crash-Recovery-Tests fehlen noch.'],
            'database_bridge' => ['status' => 'beta', 'production_ready' => false, 'note' => 'Bridge existiert; SQL-Zweige und Fehlernormalisierung noch nicht komplett.'],
            'sql_compat' => ['status' => 'prepared', 'production_ready' => false, 'note' => 'SQL-Kompatibilitaet ist vorbereitet, keine vollstaendige SQL-Engine.'],
            'sql_to_greenql' => ['status' => 'prepared', 'production_ready' => false, 'note' => 'Translator ist als Feature geplant/vorbereitet.'],
            'pdo_like' => ['status' => 'prepared', 'production_ready' => false, 'note' => 'PDO-aehnlicher Adapter ist vorbereitet, nicht produktiv.'],
            'sqlite_import' => ['status' => 'prepared', 'production_ready' => false, 'note' => 'SQLite-Import ist vorbereitet, finaler Dry-Run/Report noch offen.'],
            'mysql_import' => ['status' => 'missing', 'production_ready' => false, 'note' => 'MySQL-Dump-Light-Import ist noch nicht implementiert.'],
            'public_api_v1' => ['status' => 'beta', 'production_ready' => false, 'note' => 'Public API existiert, Versionierung/Batch/Cursor/Audit noch ausbauen.'],
            'backup_restore_pitr' => ['status' => 'experimental', 'production_ready' => false, 'note' => 'Backup/Restore vorhanden, PITR und Restore-Dry-Run noch nicht final.'],
            'benchmarks' => ['status' => 'missing', 'production_ready' => false, 'note' => 'Systematische 1k/10k/100k Benchmarks fehlen.'],
            'documentation' => ['status' => 'beta', 'production_ready' => false, 'note' => 'Doku existiert; SQL-Ersatz-Roadmap wurde ergaenzt.'],
        ];
    }

    /** Liefert einen einzelnen Feature-Status aus der Matrix. */
    public static function sqlErsatzFeatureStatus(string $feature): array {
        $matrix = self::sqlErsatzFeatureMatrix();

        return $matrix[$feature] ?? [
            'status' => 'missing',
            'production_ready' => false,
            'note' => 'Feature ist nicht in der Matrix definiert.'
        ];
    }

    /** Bereitet SQL-/Import-Adapter ehrlich als nicht-produktive Konfiguration vor. */
    public static function prepareSqlAdapter(string $type, array $config = []): array {
        $type = strtolower($type);
        $map = [
            'mysql' => 'mysql_import',
            'postgresql' => 'missing',
            'sqlite' => 'sqlite_import',
            'sql_compat' => 'sql_compat',
            'sql_to_greenql' => 'sql_to_greenql',
            'pdo_like' => 'pdo_like'
        ];

        if (!isset($map[$type])) {
            return [
                'ok' => false,
                'error' => 'unsupported_adapter'
            ];
        }

        $status = $map[$type] === 'missing'
            ? [
                'status' => 'missing',
                'production_ready' => false,
                'note' => 'Adapter ist noch nicht implementiert.'
            ]
            : self::sqlErsatzFeatureStatus($map[$type]);

        $cfg = self::enterpriseConfig('adapter_' . $type, array_replace([
            'enabled' => false,
            'status' => $status['status'],
            'production_ready' => (bool)$status['production_ready'],
            'readonly' => true,
            'visible_label' => strtoupper($type) . ' Adapter (' . $status['status'] . ')',
            'message' => $status['note'],
            'fake_ready_blocked' => true
        ], $config));

        return [
            'ok' => true,
            'adapter' => $type,
            'status' => $cfg['status'] ?? $status['status'],
            'production_ready' => (bool)($cfg['production_ready'] ?? false),
            'config' => $cfg
        ];
    }

    public static function prepareMySQLImportAdapter(array $config = []): array {
        return self::prepareSqlAdapter('mysql', $config);
    }

    public static function preparePostgreSQLImportAdapter(array $config = []): array {
        return self::prepareSqlAdapter('postgresql', $config);
    }

    public static function prepareSQLiteImportAdapter(array $config = []): array {
        return self::prepareSqlAdapter('sqlite', $config);
    }

    public static function prepareSqlCompatibilityLayer(array $config = []): array {
        return self::prepareSqlAdapter('sql_compat', $config);
    }

    public static function prepareSqlToGreenQLTranslator(array $config = []): array {
        return self::prepareSqlAdapter('sql_to_greenql', $config);
    }

    public static function preparePdoLikeAdapter(array $config = []): array {
        return self::prepareSqlAdapter('pdo_like', $config);
    }

    /* ============================================================
     * Woche 80-85: Admin-/CLI-/Developer-APIs
     * ============================================================ */

    public static function adminUiData(): array {
        return [
            'ok' => true,
            'dashboard' => self::dashboardData(),
            'tables' => array_map(
                fn($db) => [
                    'database' => $db,
                    'tables' => self::listTables((string)$db)
                ],
                self::listDBs()
            ),
            'schema' => self::readSchema(),
            'quota' => self::quotaDashboard(),
            'rate_limits' => self::enterpriseConfig('rate_limits_policy')
        ];
    }

    public static function adminUiQueryPlan(string $greenql): array {
        return method_exists(static::class, 'explainGreenQL')
            ? self::explainGreenQL($greenql)
            : [
                'ok' => true,
                'prepared' => true,
                'query' => $greenql
            ];
    }

    public static function cliCommand(string $command, array $args = []): array {
        $cmd = strtolower(trim($command));

        return match ($cmd) {
            'status' => [
                'ok' => true,
                'instance' => self::getInstance(),
                'dbs' => self::listDBs()
            ],
            'health' => self::dbHealth(true),
            'migrate' => [
                'ok' => true,
                'message' => 'Nutze GBDB::migrate($db,$table,$id,$callback) oder gbdb migrate <db> <table>.'
            ],
            'backup' => self::fullBackup($args['target'] ?? ''),
            'restore' => isset($args['path'])
                ? self::gbdbRestore((string)$args['path'])
                : [
                    'ok' => false,
                    'error' => 'path_missing'
                ],
            'repair' => self::repairMode(true),
            'compact' => self::autoCompact([]),
            'vacuum' => self::autoCompact([]),
            'analyze' => self::refreshStats(),
            'stats' => self::dashboardData(),
            'locks' => self::lockStats(),
            'slowqueries' => self::queryStats(),
            'export' => self::exportRows(
                (string)($args['db'] ?? ''),
                (string)($args['table'] ?? ''),
                (string)($args['format'] ?? 'json'),
                (string)($args['target'] ?? '')
            ),
            'import' => self::importRows(
                (string)($args['db'] ?? ''),
                (string)($args['table'] ?? ''),
                (string)($args['file'] ?? ''),
                (string)($args['format'] ?? 'json')
            ),
            default => [
                'ok' => false,
                'error' => 'unknown_command',
                'command' => $cmd
            ]
        };
    }

    public static function transactionApi(callable $callback): array {
        self::begin();

        try {
            $result = $callback();
            $ok = self::commit();

            return [
                'ok' => $ok,
                'result' => $result
            ];
        } catch (Throwable $e) {
            self::rollback();

            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function cursorApi(string $database, string $table, int $chunkSize = 500): Generator {
        foreach (self::chunkedRows($database, $table, $chunkSize) as $chunk) {
            foreach ($chunk as $row) {
                yield $row;
            }
        }
    }

    public static function indexApi(string $database, string $table, string $column): array {
        $ok = self::createIndex($database, $table, $column);

        return [
            'ok' => $ok,
            'index' => [$database, $table, $column]
        ];
    }

    public static function schemaApi(string $database, string $table, array $columns): array {
        self::ensureEnterpriseTable($database, $table, $columns);

        return [
            'ok' => true,
            'schema' => self::schemaTable($database, $table)
        ];
    }

    public static function migrationApi(string $database, string $table, string $id, callable $cb): array {
        return self::migrate($database, $table, $id, $cb);
    }

    public static function backupApi(string $type = 'full', array $args = []): array {
        return $type === 'tenant'
            ? self::tenantBackup((string)($args['tenant'] ?? self::getInstance()), (string)($args['target'] ?? ''))
            : self::fullBackup((string)($args['target'] ?? ''));
    }

    public static function repairApi(string $database = '', string $table = ''): array {
        return $database !== '' && $table !== ''
            ? self::repairReport($database, $table)
            : self::repairMode(true);
    }

    public static function explainApi(string $database, string $table, array $where = []): array {
        return self::explain($database, $table, (string)($where['where'] ?? ''), $where['is'] ?? '');
    }

    public static function statsApi(): array {
        return self::dashboardData();
    }

    public static function policyApi(string $name, array $rules = []): array {
        return self::definePolicy($name, $rules);
    }

    public static function eventApi(string $event, string $db, string $table, array $payload = []): array {
        return self::runDataTriggers($event, $db, $table, $payload);
    }

    public static function queueApi(string $action, array $payload = []): array {
        return $action === 'enqueue'
            ? [
                'ok' => true,
                'id' => self::enqueueJob((string)($payload['type'] ?? 'manual'), $payload)
            ]
            : self::queueStats();
    }

    public static function mediaApi(array $payload = []): array {
        return [
            'ok' => true,
            'job' => self::backgroundMediaProcessing($payload)
        ];
    }

    public static function searchApi(string $database, string $table, string $query, array $columns = []): array {
        return self::fulltextSearch($database, $table, $query, $columns);
    }

    public static function cacheApi(string $action, string $key = '', mixed $value = null): array {
        if ($action === 'set') {
            return ['ok' => self::fileCacheSet($key, $value, 300)];
        }

        if ($action === 'get') {
            return [
                'ok' => true,
                'value' => self::fileCacheGet($key)
            ];
        }

        if ($action === 'repair') {
            return self::cacheRepair();
        }

        return self::cacheStats();
    }

    /* ============================================================
     * Woche 86-89: Doku/Test-Registry
     * ============================================================ */

    public static function documentationIndex(): array {
        $dir = self::rootPath() . '/../../docs';

        if (!is_dir($dir)) {
            $dir = self::rootPath() . '/docs';
        }

        $docs = [];

        foreach (glob($dir . '/*.md') ?: [] as $file) {
            $docs[] = 'docs/' . basename($file);
        }

        sort($docs, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'ok' => true,
            'docs' => $docs
        ];
    }

    public static function enterpriseSelfTest(): array {
        $tests = [
            'quota' => self::quotaCheck(),
            'rate' => self::rateLimit('selftest', 'local', 100, 60),
            'sso' => self::prepareOidcAdapter(['enabled' => false]),
            'adapter' => self::prepareSQLiteImportAdapter(),
            'docs' => self::documentationIndex()
        ];

        $ok = true;

        foreach ($tests as $t) {
            if (is_array($t) && ($t['ok'] ?? true) === false) {
                $ok = false;
            }
        }

        return [
            'ok' => $ok,
            'tests' => $tests
        ];
    }
}
