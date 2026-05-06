<?php

trait GBDB_TransactionTrait {
    /** Prüft ob eine Transaktion aktiv ist. */
    private static function inTransaction(): bool {
        return self::$txActive && !self::$txCommitting;
    }

    /** Setzt den globalen Transaktions-Timeout. */
    public static function transactionTimeout(int $seconds): int {
        self::$txTimeout = max(1, $seconds);

        return self::$txTimeout;
    }

    /** Prüft den Transaktions-Timeout und rollt bei Ablauf automatisch zurück. */
    private static function checkTransactionTimeout(): bool {
        if (!self::$txActive) {
            return true;
        }

        if (self::$txStartedAt <= 0) {
            return true;
        }

        if ((time() - self::$txStartedAt) <= self::$txTimeout) {
            return true;
        }

        self::journalTransaction('timeout', ['timeout' => self::$txTimeout]);
        self::rollback();

        return false;
    }

    /** Reserviert eine ID innerhalb einer laufenden Transaktion. */
    private static function reserveTransactionId(string $database, string $table, array $data): int {
        if (isset($data['id']) && (int)$data['id'] > 0) {
            return (int)$data['id'];
        }

        $next = self::nextID($database, $table);

        foreach (self::$txOps as $op) {
            if (($op['type'] ?? '') !== 'insert') {
                continue;
            }

            if (($op['instance'] ?? '') !== self::getInstance()) {
                continue;
            }

            if (($op['db'] ?? '') !== $database || ($op['table'] ?? '') !== $table) {
                continue;
            }

            $next = max($next, (int)($op['id'] ?? 0) + 1);
        }

        return max(1, $next);
    }

    /** Baut einen Transaktions-Journal-Pfad. */
    private static function transactionJournalDir(): string {
        $dir = Vars::DB_PATH() . '.journal/transactions';

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /** Schreibt einen Transaction-Journal-Eintrag. */
    private static function journalTransaction(string $state, array $payload = []): void {
        if (self::$txId === '') {
            return;
        }

        GBDBStorage::journal(self::transactionJournalDir() . '/' . self::$txId . '.journal', [
            'tx' => self::$txId,
            'state' => $state,
            'instance' => self::getInstance(),
            'payload' => $payload
        ]);
    }

    /** Erzeugt Snapshots für alle betroffenen Tabellen. */
    private static function createTransactionSnapshots(): array {
        $snapshots = [];

        foreach (self::$txOps as $op) {
            $instance = (string)($op['instance'] ?? self::getInstance());
            $db = (string)($op['db'] ?? '');
            $table = (string)($op['table'] ?? '');

            if ($db === '' || $table === '') {
                continue;
            }

            $key = $instance . '.' . $db . '.' . $table;

            if (isset($snapshots[$key])) {
                continue;
            }

            $snapshots[$key] = self::withInstance($instance, function () use ($instance, $db, $table) {
                return [
                    'instance' => $instance,
                    'db' => $db,
                    'table' => $table,
                    'snapshot' => self::snapshot($db, $table, 'before_tx_' . self::$txId)
                ];
            });
        }

        return $snapshots;
    }

    /** Stellt Transaktions-Snapshots wieder her. */
    private static function restoreTransactionSnapshots(array $snapshots): void {
        foreach ($snapshots as $item) {
            $instance = (string)($item['instance'] ?? self::getInstance());
            $db = (string)($item['db'] ?? '');
            $table = (string)($item['table'] ?? '');
            $snapshot = (string)($item['snapshot'] ?? '');

            if ($db !== '' && $table !== '' && $snapshot !== '') {
                self::withInstance($instance, function () use ($db, $table, $snapshot) {
                    self::restoreSnapshot($db, $table, $snapshot);
                });
            }
        }
    }

    /** Startet eine echte GBDB-Transaktion. */
    public static function begin(): bool {
        if (self::$txActive) {
            return false;
        }

        self::$txActive = true;
        self::$txCommitting = false;
        self::$txId = 'tx_' . bin2hex(random_bytes(12));
        self::$txOps = [];
        self::$txStartedAt = time();
        self::$txSavepoints = [];
        self::$txSnapshots = [];

        self::journalTransaction('begin', [
            'started_at' => self::$txStartedAt,
            'timeout' => self::$txTimeout
        ]);

        return true;
    }

    /** Legt einen Savepoint an. */
    public static function savepoint(string $name): bool {
        if (!self::$txActive || !self::checkTransactionTimeout()) {
            return false;
        }

        $name = Format::cleanString($name);

        if ($name === '') {
            return false;
        }

        self::$txSavepoints[$name] = count(self::$txOps);

        self::journalTransaction('savepoint', [
            'name' => $name,
            'op_index' => count(self::$txOps)
        ]);

        return true;
    }

    /** Rollt zurueck bis zu einem Savepoint. */
    public static function rollbackTo(string $name): bool {
        if (!self::$txActive) {
            return false;
        }

        $name = Format::cleanString($name);

        if (!isset(self::$txSavepoints[$name])) {
            return false;
        }

        self::$txOps = array_slice(self::$txOps, 0, (int)self::$txSavepoints[$name]);

        foreach (array_keys(self::$txSavepoints) as $sp) {
            if ((int)self::$txSavepoints[$sp] > count(self::$txOps)) {
                unset(self::$txSavepoints[$sp]);
            }
        }

        self::journalTransaction('rollback_to', [
            'name' => $name,
            'op_index' => count(self::$txOps)
        ]);

        return true;
    }

    /** Schreibt alle Transaktions-Operationen gesammelt fest. */
    public static function commit(): bool {
        if (!self::$txActive || !self::checkTransactionTimeout()) {
            return false;
        }

        $ops = self::$txOps;

        self::journalTransaction('prepare_commit', ['ops' => count($ops)]);

        $snapshots = self::createTransactionSnapshots();
        self::$txSnapshots = $snapshots;
        self::$txCommitting = true;
        $ok = true;

        try {
            foreach ($ops as $op) {
                $instance = (string)($op['instance'] ?? self::getInstance());
                $type = (string)($op['type'] ?? '');
                $db = (string)($op['db'] ?? '');
                $table = (string)($op['table'] ?? '');

                self::journalTransaction('operation', $op);

                $ok = (bool)self::withInstance($instance, function () use ($type, $db, $table, $op) {
                    if ($type === 'insert') {
                        return self::insertData($db, $table, (array)($op['data'] ?? [])) > 0;
                    }

                    if ($type === 'edit') {
                        return self::editData($db, $table, $op['where'] ?? '', $op['is'] ?? '', (array)($op['data'] ?? []));
                    }

                    if ($type === 'delete') {
                        return self::deleteData($db, $table, $op['where'] ?? '', $op['is'] ?? '');
                    }

                    return false;
                });

                if (!$ok) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $ok = false;

            self::journalTransaction('error', [
                'message' => $e->getMessage()
            ]);
        }

        if (!$ok) {
            self::restoreTransactionSnapshots($snapshots);

            self::journalTransaction('rollback_after_commit_error', [
                'snapshots' => count($snapshots)
            ]);

            if (method_exists(static::class, 'runDataTriggers')) {
                self::runDataTriggers('onRollback', '_gbdb_transaction', 'commit', [
                    'tx' => self::$txId,
                    'ops' => count($ops)
                ]);
            }
        } else {
            self::journalTransaction('commit_marker', [
                'committed_at' => time(),
                'ops' => count($ops)
            ]);

            if (method_exists(static::class, 'runDataTriggers')) {
                self::runDataTriggers('onCommit', '_gbdb_transaction', 'commit', [
                    'tx' => self::$txId,
                    'ops' => count($ops)
                ]);
            }
        }

        self::$txActive = false;
        self::$txCommitting = false;
        self::$txId = '';
        self::$txOps = [];
        self::$txStartedAt = 0;
        self::$txSavepoints = [];
        self::$txSnapshots = [];

        return $ok;
    }

    /** Verwirft eine laufende Transaktion. */
    public static function rollback(): bool {
        if (!self::$txActive) {
            return false;
        }

        self::journalTransaction('rollback_marker', [
            'rolled_back_at' => time(),
            'ops' => count(self::$txOps)
        ]);

        if (method_exists(static::class, 'runDataTriggers')) {
            self::runDataTriggers('onRollback', '_gbdb_transaction', 'rollback', [
                'tx' => self::$txId,
                'ops' => count(self::$txOps)
            ]);
        }

        self::$txActive = false;
        self::$txCommitting = false;
        self::$txId = '';
        self::$txOps = [];
        self::$txStartedAt = 0;
        self::$txSavepoints = [];
        self::$txSnapshots = [];

        return true;
    }

    /** Gibt Transaktions-Statusdaten zurück. */
    public static function transactionStatus(): array {
        return [
            'active' => self::$txActive,
            'id' => self::$txId,
            'ops' => count(self::$txOps),
            'instance' => self::getInstance(),
            'started_at' => self::$txStartedAt,
            'timeout' => self::$txTimeout,
            'savepoints' => array_keys(self::$txSavepoints)
        ];
    }
}
