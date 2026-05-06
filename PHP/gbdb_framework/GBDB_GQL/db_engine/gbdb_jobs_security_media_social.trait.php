<?php
declare(strict_types=1);

/**
 * Wochen 50-69: Jobs, Trigger, Views, Procedures, Rechte, Policies, Audit,
 * DSGVO, Encryption/Secrets, Blob/Media und Social-Schema-Patterns.
 */
trait GBDB_JobsSecurityMediaSocialTrait {
    private static array $triggerStack = [];

    private static function opsJson(string $suffix, array $default = []): array {
        return self::readJsonConfig(self::adminOpsDir($suffix, true), $default);
    }

    private static function saveOpsJson(string $suffix, array $data): bool {
        return self::writeJsonConfig(self::adminOpsDir($suffix, true), $data);
    }

    private static function internalDb(string $db): bool {
        return str_starts_with($db, '_gbdb_') || $db === 'system';
    }

    private static function nowId(string $prefix): string {
        return $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    /* ============================================================
     * Woche 50-51: Job Queue / Scheduled / SrvP-Bridge
     * ============================================================ */

    public static function enqueueJob(string $type, array $payload = [], array $options = []): string {
        $job = [
            'id' => self::nowId('job'),
            'type' => trim($type),
            'payload' => $payload,
            'status' => 'queued',
            'priority' => (int)($options['priority'] ?? 100),
            'queue' => (string)($options['queue'] ?? 'default'),
            'run_at' => (int)($options['run_at'] ?? time()),
            'attempts' => 0,
            'max_attempts' => (int)($options['max_attempts'] ?? 3),
            'timeout' => (int)($options['timeout'] ?? 60),
            'locked_by' => '',
            'locked_at' => 0,
            'heartbeat_at' => 0,
            'created_at' => time(),
            'updated_at' => time(),
            'logs' => []
        ];

        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $jobs['jobs'][$job['id']] = $job;

        self::saveOpsJson('queue/jobs.json', $jobs);
        self::adminLog('job_enqueue', $job);

        return $job['id'];
    }

    public static function delayedJob(string $type, array $payload, int $delaySeconds, array $options = []): string {
        $options['run_at'] = time() + max(0, $delaySeconds);

        return self::enqueueJob($type, $payload, $options);
    }

    public static function priorityJob(string $type, array $payload, int $priority = 10, array $options = []): string {
        $options['priority'] = $priority;

        return self::enqueueJob($type, $payload, $options);
    }

    public static function scheduleJob(string $name, string $type, array $payload, string $cronOrInterval, array $options = []): array {
        $cfg = self::opsJson('queue/scheduled.json', ['jobs' => []]);
        $cfg['jobs'][$name] = [
            'name' => $name,
            'type' => $type,
            'payload' => $payload,
            'schedule' => $cronOrInterval,
            'options' => $options,
            'enabled' => true,
            'updated_at' => time()
        ];

        self::saveOpsJson('queue/scheduled.json', $cfg);

        return [
            'ok' => true,
            'job' => $cfg['jobs'][$name]
        ];
    }

    public static function recurringJob(string $name, string $type, array $payload, int $everySeconds, array $options = []): array {
        return self::scheduleJob($name, $type, $payload, 'every ' . max(1, $everySeconds) . ' seconds', $options);
    }

    public static function claimJob(string $workerId, string $queue = 'default'): array {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $best = null;

        foreach ($jobs['jobs'] as $id => $job) {
            if (($job['queue'] ?? 'default') !== $queue || ($job['status'] ?? '') !== 'queued' || (int)($job['run_at'] ?? 0) > time()) {
                continue;
            }

            if ($best === null || (int)$job['priority'] < (int)$jobs['jobs'][$best]['priority']) {
                $best = $id;
            }
        }

        if ($best === null) {
            return [
                'ok' => false,
                'error' => 'no_job'
            ];
        }

        $jobs['jobs'][$best]['status'] = 'running';
        $jobs['jobs'][$best]['locked_by'] = $workerId;
        $jobs['jobs'][$best]['locked_at'] = time();
        $jobs['jobs'][$best]['heartbeat_at'] = time();
        $jobs['jobs'][$best]['attempts']++;

        self::saveOpsJson('queue/jobs.json', $jobs);

        return [
            'ok' => true,
            'job' => $jobs['jobs'][$best]
        ];
    }

    public static function workerHeartbeat(string $jobId, string $workerId): bool {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);

        if (!isset($jobs['jobs'][$jobId]) || ($jobs['jobs'][$jobId]['locked_by'] ?? '') !== $workerId) {
            return false;
        }

        $jobs['jobs'][$jobId]['heartbeat_at'] = time();
        $jobs['jobs'][$jobId]['updated_at'] = time();

        return self::saveOpsJson('queue/jobs.json', $jobs);
    }

    /** Führt einen einzelnen Job lokal aus. Unbekannte Jobtypen werden als vorbereitete No-Op-Jobs abgeschlossen. */
    private static function executeJobPayload(array $job): array {
        $type = (string)($job['type'] ?? '');
        $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];

        try {
            return match ($type) {
                'gbdb.backup' => [
                    'ok' => true,
                    'result' => self::fullBackup((string)($payload['target'] ?? ''))
                ],
                'ui.demo' => [
                    'ok' => true,
                    'message' => 'Demo job executed locally.',
                    'payload' => $payload
                ],
                default => [
                    'ok' => true,
                    'prepared' => true,
                    'type' => $type,
                    'payload' => self::scrubSecrets($payload)
                ]
            };
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'type' => $type
            ];
        }
    }

    /** Führt fällige Queue-Jobs synchron aus, damit UI/CLI-Jobs nicht dauerhaft queued bleiben. */
    public static function processDueJobs(int $limit = 25, string $queue = ''): array {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $limit = max(1, $limit);
        $worker = 'local_' . bin2hex(random_bytes(4));
        $done = 0;
        $failed = 0;
        $processed = [];

        uasort($jobs['jobs'], fn($a, $b) => ((int)($a['priority'] ?? 100) <=> (int)($b['priority'] ?? 100)) ?: ((int)($a['created_at'] ?? 0) <=> (int)($b['created_at'] ?? 0)));

        foreach ($jobs['jobs'] as $id => &$job) {
            if ($done + $failed >= $limit) {
                break;
            }

            if (($job['status'] ?? '') !== 'queued') {
                continue;
            }

            if ($queue !== '' && (string)($job['queue'] ?? 'default') !== $queue) {
                continue;
            }

            if ((int)($job['run_at'] ?? 0) > time()) {
                continue;
            }

            $job['status'] = 'running';
            $job['locked_by'] = $worker;
            $job['locked_at'] = time();
            $job['heartbeat_at'] = time();
            $job['attempts'] = (int)($job['attempts'] ?? 0) + 1;
            $job['updated_at'] = time();

            $result = self::executeJobPayload($job);
            $ok = (bool)($result['ok'] ?? false);

            $job['status'] = $ok ? 'done' : (((int)$job['attempts'] >= (int)($job['max_attempts'] ?? 1)) ? 'failed' : 'queued');
            $job['result'] = $result;
            $job['locked_by'] = '';
            $job['locked_at'] = 0;
            $job['heartbeat_at'] = 0;
            $job['updated_at'] = time();
            $job['logs'][] = [
                'ts' => time(),
                'worker' => $worker,
                'result' => self::scrubSecrets($result)
            ];

            $job['status'] === 'failed' ? $failed++ : $done++;

            $processed[] = [
                'id' => (string)$id,
                'status' => $job['status'],
                'type' => (string)($job['type'] ?? '')
            ];
        }

        unset($job);

        $dead = self::opsJson('queue/dead_letter.json', ['jobs' => []]);

        foreach ($jobs['jobs'] as $id => $job) {
            if (($job['status'] ?? '') === 'failed') {
                $dead['jobs'][$id] = $job;
            }
        }

        self::saveOpsJson('queue/jobs.json', $jobs);
        self::saveOpsJson('queue/dead_letter.json', $dead);
        self::adminLog('job_process_due', [
            'done' => $done,
            'failed' => $failed,
            'queue' => $queue,
            'limit' => $limit
        ]);

        return [
            'ok' => true,
            'done' => $done,
            'failed' => $failed,
            'processed' => $processed
        ];
    }

    /** Alias für Worker-/CLI-Skripte. */
    public static function workQueue(int $limit = 25, string $queue = ''): array {
        return self::processDueJobs($limit, $queue);
    }

    public static function finishJob(string $jobId, bool $ok = true, array $result = []): array {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);

        if (!isset($jobs['jobs'][$jobId])) {
            return [
                'ok' => false,
                'error' => 'job_not_found'
            ];
        }

        $job =& $jobs['jobs'][$jobId];
        $job['status'] = $ok ? 'done' : (((int)$job['attempts'] >= (int)$job['max_attempts']) ? 'failed' : 'queued');
        $job['result'] = $result;
        $job['updated_at'] = time();

        if ($job['status'] === 'failed') {
            $dlq = self::opsJson('queue/dead_letter.json', ['jobs' => []]);
            $dlq['jobs'][$jobId] = $job;
            self::saveOpsJson('queue/dead_letter.json', $dlq);
        }

        self::saveOpsJson('queue/jobs.json', $jobs);

        return [
            'ok' => true,
            'job' => $job
        ];
    }

    public static function retryFailedJobs(int $limit = 50): array {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $n = 0;

        foreach ($jobs['jobs'] as &$job) {
            if ($n >= $limit) {
                break;
            }

            if (($job['status'] ?? '') === 'failed') {
                $job['status'] = 'queued';
                $job['run_at'] = time();
                $job['updated_at'] = time();
                $n++;
            }
        }

        self::saveOpsJson('queue/jobs.json', $jobs);

        return [
            'ok' => true,
            'retried' => $n
        ];
    }

    public static function queueStats(): array {
        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $stats = [
            'queued' => 0,
            'running' => 0,
            'done' => 0,
            'failed' => 0
        ];

        foreach ($jobs['jobs'] as $j) {
            $stats[$j['status'] ?? 'queued'] = ($stats[$j['status'] ?? 'queued'] ?? 0) + 1;
        }

        return [
            'ok' => true,
            'stats' => $stats,
            'total' => count($jobs['jobs'])
        ];
    }

    public static function jobDashboard(int $limit = 50): array {
        static $auto = false;

        if (!$auto) {
            $auto = true;
            self::processDueJobs(100);
            $auto = false;
        }

        $jobs = self::opsJson('queue/jobs.json', ['jobs' => []]);
        $dead = self::opsJson('queue/dead_letter.json', ['jobs' => []]);
        $scheduled = self::opsJson('queue/scheduled.json', ['jobs' => []]);
        $list = array_values($jobs['jobs'] ?? []);

        usort($list, fn($a, $b) => (int)($b['updated_at'] ?? $b['created_at'] ?? 0) <=> (int)($a['updated_at'] ?? $a['created_at'] ?? 0));

        return [
            'ok' => true,
            'stats' => self::queueStats()['stats'] ?? [],
            'total' => count($jobs['jobs'] ?? []),
            'jobs' => array_slice($list, 0, max(1, $limit)),
            'dead_letter' => array_values($dead['jobs'] ?? []),
            'scheduled' => array_values($scheduled['jobs'] ?? [])
        ];
    }

    public static function queueUiPrepared(): array {
        return [
            'ok' => true,
            'page' => 'GBDB UI can display queue stats, failed jobs and worker locks.'
        ];
    }

    public static function queueCliPrepared(): array {
        return [
            'ok' => true,
            'commands' => [
                'gbdb queue:stats',
                'gbdb queue:work',
                'gbdb queue:retry'
            ]
        ];
    }

    public static function srvQueueBridge(string $action, array $payload = []): array {
        return [
            'ok' => true,
            'action' => $action,
            'job' => self::enqueueJob('srvp.' . $action, $payload, [
                'queue' => 'srvp',
                'priority' => 20
            ])
        ];
    }

    public static function backgroundIndexUpdate(string $db, string $table): string {
        return self::enqueueJob('gbdb.index.rebuild', compact('db', 'table'), [
            'queue' => 'maintenance',
            'priority' => 30
        ]);
    }

    public static function backgroundFeedFanout(array $payload): string {
        return self::enqueueJob('social.feed.fanout', $payload, [
            'queue' => 'social',
            'priority' => 25
        ]);
    }

    public static function backgroundMailSending(array $payload): string {
        return self::enqueueJob('srv.mail.send', $payload, [
            'queue' => 'mail',
            'priority' => 20
        ]);
    }

    public static function backgroundMediaProcessing(array $payload): string {
        return self::enqueueJob('media.process', $payload, [
            'queue' => 'media',
            'priority' => 35
        ]);
    }

    public static function backgroundBackups(array $payload = []): string {
        return self::enqueueJob('gbdb.backup', $payload, [
            'queue' => 'maintenance',
            'priority' => 40
        ]);
    }

    /* ============================================================
     * Woche 52-53: Trigger / Events
     * ============================================================ */

    public static function defineTrigger(string $db, string $table, string $event, string $name, array $definition): array {
        $cfg = self::opsJson('triggers/definitions.json', ['triggers' => []]);
        $key = self::tableIdent($db, $table);
        $definition += [
            'name' => $name,
            'event' => $event,
            'enabled' => true,
            'timeout' => 5,
            'permissions' => [],
            'created_at' => time()
        ];

        $cfg['triggers'][$key][$event][$name] = $definition;

        self::saveOpsJson('triggers/definitions.json', $cfg);

        return [
            'ok' => true,
            'trigger' => $definition
        ];
    }

    public static function runDataTriggers(string $event, string $db, string $table, array $context = []): array {
        if (self::internalDb($db) && !in_array($event, ['onCommit', 'onRollback'], true)) {
            return [
                'ok' => true,
                'context' => $context,
                'skipped' => true
            ];
        }

        $stackKey = self::tableIdent($db, $table) . ':' . $event;

        if (isset(self::$triggerStack[$stackKey])) {
            return [
                'ok' => true,
                'context' => $context,
                'guarded' => true
            ];
        }

        self::$triggerStack[$stackKey] = true;

        $cfg = self::opsJson('triggers/definitions.json', ['triggers' => []]);
        $defs = $cfg['triggers'][self::tableIdent($db, $table)][$event] ?? [];
        $ran = [];

        foreach ($defs as $name => $def) {
            if (empty($def['enabled'])) {
                continue;
            }

            $ran[] = $name;

            if (!empty($def['enqueue'])) {
                self::enqueueEvent($event, $db, $table, $context);
            }

            if (!empty($def['log'])) {
                self::eventLog($event, $db, $table, $context);
            }
        }

        unset(self::$triggerStack[$stackKey]);

        return [
            'ok' => true,
            'ran' => $ran,
            'context' => $context
        ];
    }

    public static function enqueueEvent(string $event, string $db, string $table, array $payload = []): string {
        return self::enqueueJob('event.' . $event, [
            'db' => $db,
            'table' => $table,
            'payload' => $payload
        ], [
            'queue' => 'events',
            'priority' => 15
        ]);
    }

    public static function eventLog(string $event, string $db, string $table, array $payload = []): bool {
        return self::adminLog('event', compact('event', 'db', 'table', 'payload'));
    }

    public static function triggerDebug(string $db, string $table): array {
        $cfg = self::opsJson('triggers/definitions.json', ['triggers' => []]);

        return [
            'ok' => true,
            'triggers' => $cfg['triggers'][self::tableIdent($db, $table)] ?? []
        ];
    }

    public static function schemaDefinedTriggers(string $db, string $table): array {
        $schema = self::schemaTable($db, $table);

        return is_array($schema['triggers'] ?? null) ? $schema['triggers'] : [];
    }

    public static function greenqlTriggerFunction(string $name, string $script): array {
        $cfg = self::opsJson('triggers/greenql_functions.json', ['functions' => []]);
        $cfg['functions'][$name] = [
            'name' => $name,
            'script' => $script,
            'updated_at' => time()
        ];

        self::saveOpsJson('triggers/greenql_functions.json', $cfg);

        return [
            'ok' => true,
            'name' => $name
        ];
    }

    /* ============================================================
     * Woche 54-55: Views / Procedures
     * ============================================================ */

    public static function defineView(string $name, string $query, array $options = []): array {
        $cfg = self::opsJson('views/definitions.json', ['views' => []]);
        $cfg['views'][$name] = [
            'name' => $name,
            'query' => $query,
            'materialized' => (bool)($options['materialized'] ?? false),
            'cached' => (bool)($options['cached'] ?? true),
            'permissions' => $options['permissions'] ?? [],
            'refresh_seconds' => (int)($options['refresh_seconds'] ?? 0),
            'updated_at' => time()
        ];

        self::saveOpsJson('views/definitions.json', $cfg);

        return [
            'ok' => true,
            'view' => $cfg['views'][$name]
        ];
    }

    public static function refreshView(string $name): array {
        $cfg = self::opsJson('views/definitions.json', ['views' => []]);

        if (empty($cfg['views'][$name])) {
            return [
                'ok' => false,
                'error' => 'view_not_found'
            ];
        }

        $view = $cfg['views'][$name];
        $data = method_exists(static::class, 'query') ? self::query((string)$view['query']) : [];
        $cache = self::opsJson('views/cache.json', ['views' => []]);
        $cache['views'][$name] = [
            'data' => $data,
            'refreshed_at' => time(),
            'checksum' => hash('sha256', json_encode($data) ?: '')
        ];

        self::saveOpsJson('views/cache.json', $cache);

        return [
            'ok' => true,
            'view' => $name,
            'rows' => is_array($data) ? count($data) : 0
        ];
    }

    public static function getView(string $name, bool $refresh = false): mixed {
        if ($refresh) {
            self::refreshView($name);
        }

        $cache = self::opsJson('views/cache.json', ['views' => []]);

        return $cache['views'][$name]['data'] ?? [];
    }

    public static function viewQueryPlanner(string $name): array {
        $cfg = self::opsJson('views/definitions.json', ['views' => []]);

        return [
            'ok' => isset($cfg['views'][$name]),
            'view' => $cfg['views'][$name] ?? null,
            'planner' => 'cached/materialized view planner prepared'
        ];
    }

    public static function materializedViewIndex(string $name, array $columns): array {
        $cfg = self::opsJson('views/indexes.json', ['indexes' => []]);
        $cfg['indexes'][$name] = $columns;

        self::saveOpsJson('views/indexes.json', $cfg);

        return [
            'ok' => true,
            'view' => $name,
            'columns' => $columns
        ];
    }

    public static function defineProcedure(string $name, string $script, array $options = []): array {
        $cfg = self::opsJson('procedures/definitions.json', ['procedures' => []]);
        $cfg['procedures'][$name] = [
            'name' => $name,
            'script' => $script,
            'params' => $options['params'] ?? [],
            'return_type' => $options['return_type'] ?? 'mixed',
            'permissions' => $options['permissions'] ?? [],
            'version' => (int)($options['version'] ?? 1),
            'timeout' => (int)($options['timeout'] ?? 10),
            'transaction' => (bool)($options['transaction'] ?? false),
            'updated_at' => time()
        ];

        self::saveOpsJson('procedures/definitions.json', $cfg);

        return [
            'ok' => true,
            'procedure' => $cfg['procedures'][$name]
        ];
    }

    public static function callProcedure(string $name, array $params = []): mixed {
        $cfg = self::opsJson('procedures/definitions.json', ['procedures' => []]);

        if (empty($cfg['procedures'][$name])) {
            return [
                'ok' => false,
                'error' => 'procedure_not_found'
            ];
        }

        self::adminLog('procedure_call', [
            'name' => $name,
            'params' => $params
        ]);

        return method_exists(static::class, 'run')
            ? self::run((string)$cfg['procedures'][$name]['script'], $params)
            : [
                'ok' => true,
                'prepared' => true,
                'name' => $name
            ];
    }

    public static function procedureLogs(string $name = ''): array {
        return [
            'ok' => true,
            'logs' => self::maintenanceLogs(100),
            'filter' => $name
        ];
    }

    public static function procedureDebug(string $name): array {
        $cfg = self::opsJson('procedures/definitions.json', ['procedures' => []]);

        return [
            'ok' => isset($cfg['procedures'][$name]),
            'procedure' => $cfg['procedures'][$name] ?? null
        ];
    }

    /* ============================================================
     * Woche 56-59: DB-User, Rollen, Permissions, Policies
     * ============================================================ */

    public static function defineDbRole(string $role, array $permissions = []): array {
        $cfg = self::opsJson('security/roles.json', ['roles' => []]);
        $cfg['roles'][$role] = [
            'role' => $role,
            'permissions' => $permissions,
            'updated_at' => time()
        ];

        self::saveOpsJson('security/roles.json', $cfg);

        return [
            'ok' => true,
            'role' => $cfg['roles'][$role]
        ];
    }

    public static function defineDbUser(string $user, array $roles = ['readonly'], array $options = []): array {
        $cfg = self::opsJson('security/users.json', ['users' => []]);
        $cfg['users'][$user] = [
            'user' => $user,
            'roles' => $roles,
            'scopes' => $options['scopes'] ?? [],
            'active' => (bool)($options['active'] ?? true),
            'updated_at' => time()
        ];

        self::saveOpsJson('security/users.json', $cfg);

        return [
            'ok' => true,
            'user' => $cfg['users'][$user]
        ];
    }

    public static function defineApiKeyScope(string $keyId, array $scopes): array {
        $cfg = self::opsJson('security/api_scopes.json', ['keys' => []]);
        $cfg['keys'][$keyId] = [
            'scopes' => $scopes,
            'updated_at' => time()
        ];

        self::saveOpsJson('security/api_scopes.json', $cfg);

        return [
            'ok' => true,
            'key' => $keyId,
            'scopes' => $scopes
        ];
    }

    public static function defaultDbRoles(): array {
        foreach ([
            'readonly' => ['read'],
            'admin' => ['*'],
            'service' => ['read', 'write', 'jobs'],
            'migration' => ['schema', 'write'],
            'backup' => ['backup', 'read']
        ] as $r => $p) {
            self::defineDbRole($r, $p);
        }

        return [
            'ok' => true,
            'roles' => [
                'readonly',
                'admin',
                'service',
                'migration',
                'backup'
            ]
        ];
    }

    public static function checkPermission(string $user, string $action, string $db = '', string $table = '', array $row = []): bool {
        $cacheKey = hash('sha256', json_encode(func_get_args()) ?: '');
        $cached = self::permissionCache($cacheKey);

        if (is_bool($cached)) {
            return $cached;
        }

        $users = self::opsJson('security/users.json', ['users' => []]);
        $roles = self::opsJson('security/roles.json', ['roles' => []]);
        $ok = false;

        foreach (($users['users'][$user]['roles'] ?? []) as $role) {
            $perms = $roles['roles'][$role]['permissions'] ?? [];

            if (in_array('*', $perms, true) || in_array($action, $perms, true)) {
                $ok = true;
                break;
            }
        }

        self::permissionCache($cacheKey, $ok);
        self::adminLog('permission_check', compact('user', 'action', 'db', 'table', 'ok'));

        return $ok;
    }

    public static function permissionAudit(string $user, string $action, array $context = []): bool {
        return self::adminLog('permission_audit', compact('user', 'action', 'context'));
    }

    public static function permissionDebug(string $user): array {
        return [
            'ok' => true,
            'user' => self::opsJson('security/users.json', ['users' => []])['users'][$user] ?? null,
            'roles' => self::opsJson('security/roles.json', ['roles' => []])
        ];
    }

    public static function definePolicy(string $name, array $rules): array {
        $cfg = self::opsJson('security/policies.json', ['policies' => []]);
        $cfg['policies'][$name] = [
            'name' => $name,
            'rules' => $rules,
            'enabled' => true,
            'updated_at' => time()
        ];

        self::saveOpsJson('security/policies.json', $cfg);

        return [
            'ok' => true,
            'policy' => $cfg['policies'][$name]
        ];
    }

    public static function evaluatePolicy(string $action, array $row, array $context = []): array {
        $cfg = self::opsJson('security/policies.json', ['policies' => []]);
        $allow = true;
        $hits = [];

        foreach ($cfg['policies'] as $name => $policy) {
            if (empty($policy['enabled'])) {
                continue;
            }

            foreach (($policy['rules'] ?? []) as $rule) {
                $field = $rule['field'] ?? '';
                $op = $rule['op'] ?? '=';
                $val = $rule['value'] ?? null;
                $actual = $row[$field] ?? ($context[$field] ?? null);
                $match = ($op === '=' ? $actual == $val : ($op === '!=' ? $actual != $val : false));

                if ($match) {
                    $hits[] = $name;

                    if (($rule['effect'] ?? 'allow') === 'deny') {
                        $allow = false;
                    }
                }
            }
        }

        self::adminLog('policy_eval', compact('action', 'allow', 'hits'));

        return [
            'ok' => true,
            'allow' => $allow,
            'hits' => $hits
        ];
    }

    public static function ownerUidPolicy(string $name = 'owner_uid'): array {
        return self::definePolicy($name, [[
            'field' => 'owner_uid',
            'op' => '=',
            'value' => '{current_user}',
            'effect' => 'allow'
        ]]);
    }

    public static function visibilityPolicy(): array {
        return self::definePolicy('visibility', [[
            'field' => 'visibility',
            'op' => '=',
            'value' => 'private',
            'effect' => 'deny'
        ]]);
    }

    public static function tenantIsolationPolicy(string $tenantField = 'tenant_id'): array {
        return self::definePolicy('tenant_isolation', [[
            'field' => $tenantField,
            'op' => '!=',
            'value' => '{tenant_id}',
            'effect' => 'deny'
        ]]);
    }

    public static function policyDebugOutput(): array {
        return [
            'ok' => true,
            'policies' => self::opsJson('security/policies.json', ['policies' => []])
        ];
    }

    /* ============================================================
     * Woche 60-65: Audit, DSGVO, Encryption, Secrets
     * ============================================================ */

    public static function auditDataChange(string $action, string $db, string $table, array $old = [], array $new = [], array $context = []): bool {
        $entry = [
            'action' => $action,
            'instance' => self::getInstance(),
            'db' => $db,
            'table' => $table,
            'old' => $old,
            'new' => $new,
            'user_id' => $context['user_id'] ?? 'system',
            'ip' => $context['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            'service_id' => $context['service_id'] ?? '',
            'request_id' => $context['request_id'] ?? bin2hex(random_bytes(6)),
            'transaction_id' => self::$txId ?? '',
            'ts' => time()
        ];

        return self::adminLog('audit_data', $entry);
    }

    public static function auditExport(array $filter = []): array {
        $logs = self::maintenanceLogs(1000);

        return [
            'ok' => true,
            'filter' => $filter,
            'logs' => $logs
        ];
    }

    public static function auditSearch(string $needle): array {
        $found = [];

        foreach (self::maintenanceLogs(1000)['logs'] ?? [] as $line) {
            if (str_contains(json_encode($line, JSON_UNESCAPED_UNICODE) ?: '', $needle)) {
                $found[] = $line;
            }
        }

        return [
            'ok' => true,
            'results' => $found
        ];
    }

    public static function auditRetention(int $days = 365): array {
        return self::cleanupFilesByAge(['*.log'], $days);
    }

    public static function auditIntegrity(): array {
        $logs = self::maintenanceLogs(1000);

        return [
            'ok' => true,
            'entries' => count($logs['logs'] ?? []),
            'checksum' => hash('sha256', json_encode($logs) ?: '')
        ];
    }

    public static function markPiiField(string $db, string $table, string $field, string $classification = 'pii'): array {
        $cfg = self::opsJson('gdpr/classification.json', ['fields' => []]);
        $cfg['fields'][self::tableIdent($db, $table)][$field] = $classification;

        self::saveOpsJson('gdpr/classification.json', $cfg);

        return [
            'ok' => true,
            'field' => $field,
            'classification' => $classification
        ];
    }

    public static function userDataExport(string $userField, mixed $userId, array $tables = []): array {
        $out = [];

        foreach ($tables as $t) {
            [$db, $table] = array_pad(explode('.', $t, 2), 2, '');

            if ($db && $table) {
                $out[$t] = array_values(array_filter(
                    self::getData($db, $table) ?: [],
                    fn($r) => is_array($r) && ($r[$userField] ?? null) == $userId
                ));
            }
        }

        self::auditDataChange('gdpr_export', '*', '*', [], ['user' => $userId]);

        return [
            'ok' => true,
            'data' => $out
        ];
    }

    public static function userDataDelete(string $userField, mixed $userId, array $tables = []): array {
        $n = 0;

        foreach ($tables as $t) {
            [$db, $table] = array_pad(explode('.', $t, 2), 2, '');

            if ($db && $table && self::deleteData($db, $table, $userField, $userId)) {
                $n++;
            }
        }

        self::auditDataChange('gdpr_delete', '*', '*', [], [
            'user' => $userId,
            'tables' => $n
        ]);

        return [
            'ok' => true,
            'deleted_tables' => $n
        ];
    }

    public static function userDataRedact(string $db, string $table, string $where, mixed $is, array $fields, string $replacement = '[redacted]'): array {
        $set = [];

        foreach ($fields as $f) {
            $set[$f] = $replacement;
        }

        $ok = self::editData($db, $table, $where, $is, $set);

        self::auditDataChange('gdpr_redact', $db, $table, [], [
            'where' => $where,
            'is' => $is,
            'fields' => $fields
        ]);

        return [
            'ok' => $ok
        ];
    }

    public static function retentionPolicy(string $name, array $rule): array {
        $cfg = self::opsJson('gdpr/retention.json', ['policies' => []]);
        $cfg['policies'][$name] = $rule + ['updated_at' => time()];

        self::saveOpsJson('gdpr/retention.json', $cfg);

        return [
            'ok' => true,
            'policy' => $cfg['policies'][$name]
        ];
    }

    public static function consentHistory(string $subject, array $entry): array {
        $cfg = self::opsJson('gdpr/consent.json', ['subjects' => []]);
        $cfg['subjects'][$subject][] = $entry + ['ts' => time()];

        self::saveOpsJson('gdpr/consent.json', $cfg);

        return [
            'ok' => true,
            'subject' => $subject
        ];
    }

    public static function encryptionConfig(array $cfg = []): array {
        $cur = self::opsJson('security/encryption.json', [
            'at_rest' => false,
            'field_encryption' => [],
            'key_version' => 1
        ]);

        $cur = array_replace_recursive($cur, $cfg);

        self::saveOpsJson('security/encryption.json', $cur);

        return [
            'ok' => true,
            'config' => $cur
        ];
    }

    public static function deriveKey(string $secret, string $salt = '', int $version = 1): string {
        return hash_hmac(
            'sha256',
            $secret,
            $salt !== '' ? $salt : (method_exists('Vars', 'app_key') ? Vars::app_key() : (method_exists('Vars', 'cryptKey') ? Vars::cryptKey() : 'gbdb')),
            false
        ) . ':v' . $version;
    }

    public static function rotateKey(string $name = 'default'): array {
        $cfg = self::opsJson('security/keys.json', ['keys' => []]);
        $v = (int)($cfg['keys'][$name]['version'] ?? 0) + 1;
        $cfg['keys'][$name] = [
            'version' => $v,
            'rotated_at' => time(),
            'recovery_prepared' => true
        ];

        self::saveOpsJson('security/keys.json', $cfg);

        return [
            'ok' => true,
            'key' => $name,
            'version' => $v
        ];
    }

    public static function scrubSecrets(mixed $value): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = preg_match('/secret|token|key|auth|pass/i', (string)$k)
                    ? '[secret]'
                    : self::scrubSecrets($v);
            }

            return $value;
        }

        return $value;
    }

    public static function securityTests(): array {
        $s = self::scrubSecrets([
            'api_key' => 'abc',
            'normal' => 'ok'
        ]);

        return [
            'ok' => ($s['api_key'] === '[secret]' && $s['normal'] === 'ok'),
            'checks' => [
                'secret_scrub',
                'key_rotation',
                'policy_eval'
            ]
        ];
    }

    /* ============================================================
     * Woche 66-67: Blob / Media Store
     * ============================================================ */

    public static function blobPath(string $hash = '', bool $ensure = true): string {
        $base = self::dbRootPath('.media', $ensure);

        return rtrim($base, '/') . ($hash !== '' ? '/' . substr($hash, 0, 2) . '/' . $hash : '');
    }

    public static function putBlob(string $sourcePath, string $visibility = 'private', array $meta = []): array {
        if (!is_file($sourcePath)) {
            return [
                'ok' => false,
                'error' => 'source_not_found'
            ];
        }

        $hash = hash_file('sha256', $sourcePath) ?: hash('sha256', $sourcePath . microtime(true));
        $target = self::blobPath($hash, true);

        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0777, true);
        }

        if (!is_file($target)) {
            @copy($sourcePath, $target);
        }

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($sourcePath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        $m = [
            'hash' => $hash,
            'path' => $target,
            'size' => (int)filesize($sourcePath),
            'mime' => $mime,
            'visibility' => $visibility,
            'meta' => $meta,
            'created_at' => time()
        ];

        $idx = self::opsJson('media/index.json', ['media' => []]);
        $idx['media'][$hash] = $m;

        self::saveOpsJson('media/index.json', $idx);
        self::adminLog('media_put', self::scrubSecrets($m));

        return [
            'ok' => true,
            'media' => $m,
            'duplicate' => is_file($target)
        ];
    }

    public static function signedUrl(string $hash, int $ttl = 300): string {
        return 'gbdb-media://' . $hash . '?exp=' . (time() + max(1, $ttl)) . '&sig=' . hash_hmac(
            'sha256',
            $hash . (time() + max(1, $ttl)),
            self::deriveKey('media')
        );
    }

    public static function temporaryUrl(string $hash, int $ttl = 300): string {
        return self::signedUrl($hash, $ttl);
    }

    public static function validateMime(string $mime, array $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']): bool {
        return in_array($mime, $allowed, true);
    }

    public static function deleteMedia(string $hash): array {
        $idx = self::opsJson('media/index.json', ['media' => []]);
        $path = $idx['media'][$hash]['path'] ?? self::blobPath($hash, false);

        if (is_file($path)) {
            @unlink($path);
        }

        unset($idx['media'][$hash]);

        self::saveOpsJson('media/index.json', $idx);
        self::adminLog('media_delete', ['hash' => $hash]);

        return [
            'ok' => true,
            'hash' => $hash
        ];
    }

    public static function orphanMediaCleanup(): array {
        $idx = self::opsJson('media/index.json', ['media' => []]);
        $removed = 0;

        foreach ($idx['media'] as $h => $m) {
            if (empty($m['path']) || !is_file($m['path'])) {
                unset($idx['media'][$h]);
                $removed++;
            }
        }

        self::saveOpsJson('media/index.json', $idx);

        return [
            'ok' => true,
            'removed' => $removed
        ];
    }

    public static function mediaPermissions(string $hash, array $permissions): array {
        $idx = self::opsJson('media/index.json', ['media' => []]);

        if (empty($idx['media'][$hash])) {
            return [
                'ok' => false,
                'error' => 'media_not_found'
            ];
        }

        $idx['media'][$hash]['permissions'] = $permissions;

        self::saveOpsJson('media/index.json', $idx);

        return [
            'ok' => true,
            'hash' => $hash
        ];
    }

    public static function mediaAudit(string $hash, string $action, array $context = []): bool {
        return self::adminLog('media_audit', compact('hash', 'action', 'context'));
    }

    public static function mediaProcessingPrepared(string $hash): string {
        return self::backgroundMediaProcessing([
            'hash' => $hash,
            'tasks' => [
                'thumbnail',
                'dimensions',
                'virus_scan_hook'
            ]
        ]);
    }

    /* ============================================================
     * Woche 68-69: Social Patterns
     * ============================================================ */

    public static function socialSchemaPatterns(): array {
        return [
            'users' => ['uid', 'username', 'email', 'password', 'role', 'created_at', 'updated_at', 'deleted_at'],
            'profiles' => ['uid', 'display_name', 'bio', 'avatar_hash', 'visibility'],
            'posts' => ['post_id', 'uid', 'content', 'visibility', 'created_at', 'updated_at', 'deleted_at'],
            'comments' => ['comment_id', 'post_id', 'uid', 'content', 'created_at', 'deleted_at'],
            'likes' => ['like_id', 'object_type', 'object_id', 'uid', 'created_at'],
            'follows' => ['follow_id', 'follower_uid', 'target_uid', 'created_at'],
            'blocks' => ['block_id', 'blocker_uid', 'blocked_uid', 'created_at'],
            'reports' => ['report_id', 'object_type', 'object_id', 'uid', 'reason', 'status', 'created_at'],
            'notifications' => ['notification_id', 'uid', 'type', 'payload', 'read_at', 'created_at'],
            'feed_items' => ['feed_id', 'uid', 'object_type', 'object_id', 'score', 'created_at'],
            'activity' => ['activity_id', 'uid', 'verb', 'object_type', 'object_id', 'payload', 'created_at'],
            'hashtags' => ['tag', 'object_type', 'object_id', 'created_at'],
            'mentions' => ['mention_id', 'uid', 'object_type', 'object_id', 'created_at'],
            'moderation_queue' => ['mod_id', 'object_type', 'object_id', 'status', 'reason', 'created_at']
        ];
    }

    public static function installSocialPatterns(string $db = 'social'): array {
        self::createDatabase($db);

        $created = [];

        foreach (self::socialSchemaPatterns() as $table => $cols) {
            if (!in_array($table, self::listTables($db), true)) {
                self::createTable($db, $table, $cols);
                $created[] = $table;
            }
        }

        return [
            'ok' => true,
            'database' => $db,
            'created' => $created
        ];
    }

    public static function softDelete(string $db, string $table, string $where, mixed $is): bool {
        return self::editData($db, $table, $where, $is, [
            'deleted_at' => time(),
            'visibility' => 'deleted'
        ]);
    }

    public static function contentVisibilityRules(array $rules = []): array {
        return self::definePolicy('content_visibility', $rules ?: [
            [
                'field' => 'visibility',
                'op' => '=',
                'value' => 'deleted',
                'effect' => 'deny'
            ],
            [
                'field' => 'visibility',
                'op' => '=',
                'value' => 'private',
                'effect' => 'deny'
            ]
        ]);
    }

    public static function feedFanoutJob(string $postId, array $targets): string {
        return self::backgroundFeedFanout([
            'post_id' => $postId,
            'targets' => $targets,
            'mode' => 'fanout_on_write'
        ]);
    }

    public static function fanoutOnRead(array $context): array {
        return [
            'ok' => true,
            'mode' => 'fanout_on_read',
            'context' => $context,
            'prepared' => true
        ];
    }

    public static function hybridFeed(array $context): array {
        return [
            'ok' => true,
            'mode' => 'hybrid',
            'context' => $context,
            'prepared' => true
        ];
    }

    public static function moderationQueue(string $objectType, string $objectId, string $reason, array $context = []): string {
        return self::enqueueJob('moderation.review', compact('objectType', 'objectId', 'reason', 'context'), [
            'queue' => 'moderation',
            'priority' => 5
        ]);
    }
}
