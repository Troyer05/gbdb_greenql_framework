<?php

trait GBDB_LockingTrait {
    private static array $lockHandles = [];
    private static int $lockDefaultTimeoutMs = 5000;
    private static int $lockStaleAfter = 60;

    /** Gibt den technischen Lock-Root zurück. */
    private static function lockRoot(): string {
        $dir = dirname(Vars::DB_PATH()) . '/.temp/locks';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /** Gibt den Besitzer dieses PHP-Prozesses zurück. */
    private static function lockOwner(): string {
        $host = function_exists('gethostname') ? (string)gethostname() : 'host';

        return $host . ':' . getmypid();
    }

    /** Normalisiert einen Lock-Key zu einem Dateinamen. */
    private static function lockKey(string $resource): string {
        $key = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $resource) ?: 'resource';

        if (strlen($key) > 120) {
            $key = substr($key, 0, 80) . '_' . hash('sha256', $resource);
        }

        return $key;
    }

    /** Setzt den Standard-Lock-Timeout in Millisekunden. */
    public static function lockTimeout(int $milliseconds): int {
        self::$lockDefaultTimeoutMs = max(1, $milliseconds);

        return self::$lockDefaultTimeoutMs;
    }

    /** Entfernt verwaiste Lock-Meta-Dateien. */
    public static function cleanupLocks(int $olderThanSeconds = 0): array {
        $olderThanSeconds = $olderThanSeconds > 0 ? $olderThanSeconds : self::$lockStaleAfter;
        $dir = self::lockRoot();
        $removed = 0;

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string)@file_get_contents($file), true);
            $updated = (int)($data['updated_at'] ?? $data['created_at'] ?? 0);

            if ($updated > 0 && (time() - $updated) > $olderThanSeconds) {
                @unlink($file);
                $removed++;
            }
        }

        return [
            'ok' => true,
            'removed' => $removed,
            'dir' => $dir
        ];
    }

    /** Schreibt einen wartenden Lock in die Queue. */
    private static function enqueueLock(string $key, string $type): void {
        $file = self::lockRoot() . '/' . $key . '.queue';

        GBDBStorage::appendLine($file, json_encode([
            'owner' => self::lockOwner(),
            'type' => $type,
            'queued_at' => time()
        ], JSON_UNESCAPED_UNICODE) . "\n");
    }

    /** Erwirbt einen Read-/Write-Lock. Viele Leser sind erlaubt, Writer exklusiv. */
    public static function acquireLock(string $resource, string $type = 'write', int $timeoutMs = 0): string|false {
        $type = strtolower($type) === 'read' ? 'read' : 'write';
        $timeoutMs = $timeoutMs > 0 ? $timeoutMs : self::$lockDefaultTimeoutMs;
        $key = self::lockKey($resource);
        $file = self::lockRoot() . '/' . $key . '.lock';
        $metaFile = self::lockRoot() . '/' . $key . '.json';
        $handle = @fopen($file, 'c+');

        if (!$handle) {
            return false;
        }

        $start = microtime(true);
        $queued = false;
        $flag = $type === 'read' ? LOCK_SH : LOCK_EX;

        while (true) {
            if (@flock($handle, $flag | LOCK_NB)) {
                break;
            }

            if (!$queued) {
                self::enqueueLock($key, $type);
                $queued = true;
            }

            if (((microtime(true) - $start) * 1000) >= $timeoutMs) {
                @fclose($handle);

                return false;
            }

            usleep(25000);
        }

        $id = 'lk_' . bin2hex(random_bytes(8));
        self::$lockHandles[$id] = [
            'handle' => $handle,
            'resource' => $resource,
            'key' => $key,
            'type' => $type,
            'started_at' => time()
        ];

        GBDBStorage::atomicWrite($metaFile, json_encode([
            'id' => $id,
            'resource' => $resource,
            'type' => $type,
            'owner' => self::lockOwner(),
            'pid' => getmypid(),
            'created_at' => time(),
            'updated_at' => time(),
            'timeout_ms' => $timeoutMs
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        return $id;
    }

    /** Gibt einen Lock frei. */
    public static function releaseLock(string $lockId): bool {
        if (!isset(self::$lockHandles[$lockId])) {
            return false;
        }

        $item = self::$lockHandles[$lockId];
        $handle = $item['handle'];

        @flock($handle, LOCK_UN);
        @fclose($handle);
        @unlink(self::lockRoot() . '/' . $item['key'] . '.json');

        unset(self::$lockHandles[$lockId]);

        return true;
    }

    /** Führt eine Aktion unter einem Read-Lock aus. */
    private static function withReadLock(string $resource, callable $fn, int $timeoutMs = 0): mixed {
        $lock = self::acquireLock($resource, 'read', $timeoutMs);

        if ($lock === false) {
            return false;
        }

        try {
            return $fn();
        } finally {
            self::releaseLock($lock);
        }
    }

    /** Führt eine Aktion unter einem Write-Lock aus. */
    private static function withWriteLock(string $resource, callable $fn, int $timeoutMs = 0): mixed {
        $lock = self::acquireLock($resource, 'write', $timeoutMs);

        if ($lock === false) {
            return false;
        }

        try {
            return $fn();
        } finally {
            self::releaseLock($lock);
        }
    }

    /** Table-Level Lock Wrapper. */
    public static function lockTable(string $database, string $table, string $type = 'write', int $timeoutMs = 0): string|false {
        return self::acquireLock(
            'table:' . self::getInstance() . ':' . Format::cleanString($database) . ':' . Format::cleanString($table),
            $type,
            $timeoutMs
        );
    }

    /** Segment-/Chunk-Level Lock Wrapper. */
    public static function lockChunk(string $database, string $table, string|int $chunk, string $type = 'write', int $timeoutMs = 0): string|false {
        return self::acquireLock(
            'chunk:' . self::getInstance() . ':' . Format::cleanString($database) . ':' . Format::cleanString($table) . ':' . self::lockKey((string)$chunk),
            $type,
            $timeoutMs
        );
    }

    /** Row-Level Lock Vorbereitung. */
    public static function lockRow(string $database, string $table, int $rowId, string $type = 'write', int $timeoutMs = 0): string|false {
        return self::acquireLock(
            'row:' . self::getInstance() . ':' . Format::cleanString($database) . ':' . Format::cleanString($table) . ':' . max(0, $rowId),
            $type,
            $timeoutMs
        );
    }

    /** Page-Level Lock Vorbereitung. */
    public static function lockPage(string $database, string $table, string|int $page, string $type = 'write', int $timeoutMs = 0): string|false {
        return self::acquireLock(
            'page:' . self::getInstance() . ':' . Format::cleanString($database) . ':' . Format::cleanString($table) . ':' . self::lockKey((string)$page),
            $type,
            $timeoutMs
        );
    }

    /** Gibt aktive und wartende Locks zurück. */
    public static function lockMonitor(): array {
        self::cleanupLocks();

        $dir = self::lockRoot();
        $active = [];
        $queues = [];

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string)@file_get_contents($file), true);

            if (is_array($data)) {
                $active[] = $data;
            }
        }

        foreach (glob($dir . '/*.queue') ?: [] as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $queues[basename($file)] = count($lines);
        }

        return [
            'ok' => true,
            'active' => $active,
            'queues' => $queues,
            'open_in_process' => count(self::$lockHandles)
        ];
    }

    /** Gibt Lock-Statistiken zurück. */
    public static function lockStats(): array {
        $monitor = self::lockMonitor();
        $byType = [
            'read' => 0,
            'write' => 0
        ];

        foreach ($monitor['active'] as $item) {
            $type = (string)($item['type'] ?? 'write');
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        return [
            'ok' => true,
            'active' => count($monitor['active']),
            'by_type' => $byType,
            'queued_resources' => count($monitor['queues'])
        ];
    }

    /** Einfache Deadlock-Erkennung über wartende Queue-Dateien und zu alte Writer. */
    public static function detectDeadlocks(int $olderThanSeconds = 15): array {
        $monitor = self::lockMonitor();
        $suspects = [];

        foreach ($monitor['active'] as $item) {
            $age = time() - (int)($item['created_at'] ?? time());

            if ($age >= $olderThanSeconds && ($item['type'] ?? '') === 'write') {
                $suspects[] = $item;
            }
        }

        return [
            'ok' => true,
            'deadlock_suspects' => $suspects,
            'count' => count($suspects)
        ];
    }

    /** Deadlock-Resolution: verwaiste Meta/Queue-Daten entfernen, OS-flock bleibt Prozess-sicher. */
    public static function resolveDeadlocks(int $olderThanSeconds = 15): array {
        $dead = self::detectDeadlocks($olderThanSeconds);
        $cleanup = self::cleanupLocks($olderThanSeconds);

        return [
            'ok' => true,
            'suspects' => $dead['count'],
            'cleanup' => $cleanup
        ];
    }

    /** Retry-Strategie bei Lock-Kollisionen. */
    public static function retryOnLockCollision(callable $fn, int $tries = 3, int $sleepMs = 50): mixed {
        $tries = max(1, $tries);

        for ($i = 0; $i < $tries; $i++) {
            $res = $fn($i + 1);

            if ($res !== false) {
                return $res;
            }

            usleep(max(1, $sleepMs) * 1000);
            $sleepMs *= 2;
        }

        return false;
    }

    /** Lock-Escalation Vorbereitung: eskaliert Row/Chunk/Page zu Table-Lock, wenn viele Einzel-Locks offen sind. */
    public static function maybeEscalateLock(string $database, string $table, int $threshold = 32): string|false {
        $count = 0;
        $prefix = ':' . self::getInstance() . ':' . Format::cleanString($database) . ':' . Format::cleanString($table) . ':';

        foreach (self::$lockHandles as $item) {
            if (str_contains((string)($item['resource'] ?? ''), $prefix)) {
                $count++;
            }
        }

        if ($count < $threshold) {
            return false;
        }

        return self::lockTable($database, $table, 'write');
    }
}
