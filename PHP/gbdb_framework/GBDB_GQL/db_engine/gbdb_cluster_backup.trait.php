<?php
declare(strict_types=1);

trait GBDB_ClusterBackupTrait {
    private static function dbRootPath(string $suffix = "", bool $ensure = false): string {
        $root = rtrim(dirname(rtrim(Vars::DB_PATH(), "/")), "/") . "/";
        $path = $root . ltrim($suffix, "/");

        if ($ensure) {
            $dir = str_ends_with($path, "/") || pathinfo($path, PATHINFO_EXTENSION) === ""
                ? $path
                : dirname($path);

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }

        return $path;
    }

    private static function safeSegment(string $value): string {
        $value = Format::cleanString($value);

        return $value === "" ? "default" : $value;
    }

    private static function readJsonConfig(string $file, array $default = []): array {
        if (!is_file($file)) {
            return $default;
        }

        $json = json_decode((string)@file_get_contents($file), true);

        return is_array($json) ? $json : $default;
    }

    private static function writeJsonConfig(string $file, array $data): bool {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false && GBDBStorage::atomicWrite($file, $json . "\n");
    }

    private static function copyTreeInternal(string $from, string $to, array &$report): void {
        if (!is_dir($from)) {
            $report["errors"][] = "Quelle fehlt: " . $from;
            return;
        }

        if (!is_dir($to)) {
            @mkdir($to, 0777, true);
        }

        $items = scandir($from);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === "." || $item === "..") {
                continue;
            }

            $src = $from . "/" . $item;
            $dst = $to . "/" . $item;

            if (is_dir($src)) {
                self::copyTreeInternal($src, $dst, $report);
                continue;
            }

            if (!is_file($src)) {
                continue;
            }

            if (!is_dir(dirname($dst))) {
                @mkdir(dirname($dst), 0777, true);
            }

            if (@copy($src, $dst)) {
                $report["files"][] = $dst;
            } else {
                $report["errors"][] = "Konnte nicht kopieren: " . $src;
            }
        }
    }

    private static function deleteTreeInternal(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item === "." || $item === "..") {
                    continue;
                }

                $p = $path . "/" . $item;

                if (is_dir($p)) {
                    self::deleteTreeInternal($p);
                } else {
                    @unlink($p);
                }
            }
        }

        @rmdir($path);
    }

    private static function checksumTreeInternal(string $path): array {
        $out = [];

        if (!is_dir($path)) {
            return $out;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $full = $file->getPathname();
            $rel = ltrim(str_replace($path, "", $full), "/");
            $out[$rel] = hash_file("sha256", $full) ?: "";
        }

        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    private static function tableIdent(string $database, string $table): string {
        return self::safeSegment(self::getInstance()) . ":" . self::safeSegment($database) . "." . self::safeSegment($table);
    }

    private static function assertPartitionWritable(string $database, string $table, array $rows): bool {
        $meta = self::meta($database, $table);
        $readonly = $meta["partition_readonly"] ?? [];

        if (!is_array($readonly) || empty($readonly)) {
            return true;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = self::partitionKey($database, $table, $row);

            if (!empty($readonly[$key])) {
                return false;
            }
        }

        return true;
    }

    public static function repairPartitions(string $database, string $table): array {
        self::syncPartitioningToMeta($database, $table);

        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            $rows = [];
        }

        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);
        $partitions = [];

        foreach ($rows as $row) {
            if (!is_array($row) || self::isHeaderRow($row)) {
                continue;
            }

            $key = self::partitionKey($database, $table, $row);

            if (!isset($partitions[$key])) {
                $partitions[$key] = [
                    "rows" => 0,
                    "created_at" => time(),
                    "updated_at" => 0
                ];
            }

            $partitions[$key]["rows"]++;
            $partitions[$key]["updated_at"] = max(
                (int)$partitions[$key]["updated_at"],
                (int)($row["_gbdb_visible_from"] ?? time())
            );
        }

        foreach ($partitions as $key => $data) {
            if ((int)$data["updated_at"] <= 0) {
                $partitions[$key]["updated_at"] = time();
            }
        }

        $before = $meta["partitions"] ?? [];
        $meta["partitions"] = $partitions;
        $meta["partition_readonly"] = is_array($meta["partition_readonly"] ?? null) ? $meta["partition_readonly"] : [];
        $meta["partition_archives"] = is_array($meta["partition_archives"] ?? null) ? $meta["partition_archives"] : [];

        self::writeMeta($metaFile, $meta);

        return [
            "ok" => true,
            "before" => $before,
            "after" => $partitions,
            "count" => count($partitions)
        ];
    }

    public static function backupPartition(string $database, string $table, string $partition, string $target = ""): array {
        $partition = self::safeSegment($partition);
        $target = $target !== ""
            ? rtrim($target, "/")
            : self::dbRootPath(
                ".backups/partitions/" .
                date("Ymd_His") . "_" .
                self::safeSegment(self::getInstance()) . "_" .
                self::safeSegment($database) . "_" .
                self::safeSegment($table) . "_" .
                $partition,
                true
            );

        if (!is_dir($target)) {
            @mkdir($target, 0777, true);
        }

        $rows = [];

        foreach ((array)self::getData($database, $table) as $row) {
            if (is_array($row) && !self::isHeaderRow($row) && self::partitionKey($database, $table, $row) === $partition) {
                $rows[] = $row;
            }
        }

        $meta = [
            "type" => "partition",
            "instance" => self::getInstance(),
            "database" => $database,
            "table" => $table,
            "partition" => $partition,
            "rows" => count($rows),
            "created_at" => time()
        ];

        $ok = self::writeJsonConfig($target . "/rows.json", $rows)
            && self::writeJsonConfig($target . "/metadata.json", $meta);

        self::backupLog("partition_backup", [
            "target" => $target,
            "meta" => $meta,
            "ok" => $ok
        ]);

        return [
            "ok" => $ok,
            "path" => $target,
            "rows" => count($rows),
            "metadata" => $meta
        ];
    }

    public static function setPartitionReadOnly(string $database, string $table, string $partition, bool $readonly = true): bool {
        $partition = self::safeSegment($partition);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);

        if (!isset($meta["partition_readonly"]) || !is_array($meta["partition_readonly"])) {
            $meta["partition_readonly"] = [];
        }

        if ($readonly) {
            $meta["partition_readonly"][$partition] = [
                "readonly" => true,
                "set_at" => time()
            ];
        } else {
            unset($meta["partition_readonly"][$partition]);
        }

        return self::writeMeta($metaFile, $meta);
    }

    public static function archiveOldPartitions(string $database, string $table, int $olderThan, bool $readonly = true): array {
        $repair = self::repairPartitions($database, $table);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);
        $done = [];

        foreach (($meta["partitions"] ?? []) as $key => $part) {
            if ((int)($part["updated_at"] ?? 0) > $olderThan) {
                continue;
            }

            $backup = self::backupPartition($database, $table, (string)$key);
            $meta["partition_archives"][$key][] = [
                "path" => $backup["path"],
                "archived_at" => time(),
                "readonly" => $readonly
            ];

            if ($readonly) {
                self::setPartitionReadOnly($database, $table, (string)$key, true);
            }

            $done[] = [
                "partition" => $key,
                "backup" => $backup
            ];
        }

        self::writeMeta($metaFile, $meta);

        return [
            "ok" => true,
            "repair" => $repair,
            "archived" => $done,
            "count" => count($done)
        ];
    }

    public static function preparePartitionMerge(string $database, string $table, array $partitions, string $target): array {
        $plan = [
            "type" => "partition_merge",
            "instance" => self::getInstance(),
            "database" => $database,
            "table" => $table,
            "from" => array_values(array_map(fn($p) => self::safeSegment((string)$p), $partitions)),
            "to" => self::safeSegment($target),
            "created_at" => time(),
            "status" => "prepared"
        ];

        return self::storeSystemPlan("partition_merge", $plan);
    }

    public static function preparePartitionSplit(string $database, string $table, string $partition, array $targets): array {
        $plan = [
            "type" => "partition_split",
            "instance" => self::getInstance(),
            "database" => $database,
            "table" => $table,
            "from" => self::safeSegment($partition),
            "to" => array_values(array_map(fn($p) => self::safeSegment((string)$p), $targets)),
            "created_at" => time(),
            "status" => "prepared"
        ];

        return self::storeSystemPlan("partition_split", $plan);
    }

    public static function preparePartitionMigration(
        string $database,
        string $table,
        string $partition,
        string $targetInstance
    ): array {
        $backup = self::backupPartition($database, $table, $partition);
        $plan = [
            "type" => "partition_migration",
            "source_instance" => self::getInstance(),
            "target_instance" => self::safeSegment($targetInstance),
            "database" => $database,
            "table" => $table,
            "partition" => self::safeSegment($partition),
            "backup" => $backup,
            "created_at" => time(),
            "status" => "prepared"
        ];

        return self::storeSystemPlan("partition_migration", $plan);
    }

    public static function defineShardConcept(array $config = []): array {
        $file = self::dbRootPath(".system/shards/config.json", true);
        $current = self::readJsonConfig($file, []);
        $current = array_replace_recursive([
            "enabled" => true,
            "strategy" => "hash",
            "default_shard" => "s0",
            "cross_shard_queries" => "blocked_by_default",
            "created_at" => time()
        ], $current, $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $current);

        return $current;
    }

    public static function registerShard(string $name, array $config = []): array {
        $name = self::safeSegment($name);
        $file = self::dbRootPath(".system/shards/map.json", true);
        $map = self::readJsonConfig($file, ["shards" => []]);
        $map["shards"][$name] = array_replace([
            "name" => $name,
            "role" => "primary",
            "weight" => 1,
            "status" => "online",
            "created_at" => time()
        ], $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $map);

        return $map;
    }

    public static function shardMap(): array {
        return self::readJsonConfig(self::dbRootPath(".system/shards/map.json"), ["shards" => []]);
    }

    public static function defineShardKey(string $database, string $table, string $column, string $strategy = "hash"): bool {
        $schema = self::schemaTable($database, $table);
        $schema["sharding"] = [
            "column" => trim($column),
            "strategy" => strtolower(trim($strategy)),
            "active" => true,
            "created_at" => time()
        ];
        $schema["version"] = (int)($schema["version"] ?? 1) + 1;

        self::addMigrationHistory($schema, "sharding", "define_shard_key", $schema["sharding"]);

        return self::writeSchemaTable($database, $table, $schema);
    }

    public static function shardForValue(mixed $value, string $strategy = "hash"): string {
        $map = self::shardMap();
        $names = array_keys((array)($map["shards"] ?? []));

        if (empty($names)) {
            self::registerShard("s0");
            $names = ["s0"];
        }

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names[abs(crc32((string)$value)) % count($names)];
    }

    public static function shardRouter(string $database, string $table, array $row): array {
        $schema = self::schemaTable($database, $table);
        $sharding = is_array($schema["sharding"] ?? null) ? $schema["sharding"] : [];
        $column = (string)($sharding["column"] ?? "id");
        $strategy = (string)($sharding["strategy"] ?? "hash");
        $value = $row[$column] ?? ($row["id"] ?? "");

        return [
            "shard" => self::shardForValue($value, $strategy),
            "strategy" => $strategy,
            "column" => $column,
            "value" => $value
        ];
    }

    public static function prepareUserSharding(string $database, string $table, string $column = "user_id"): bool {
        return self::defineShardKey($database, $table, $column, "user");
    }

    public static function prepareTenantSharding(string $database, string $table, string $column = "tenant_id"): bool {
        return self::defineShardKey($database, $table, $column, "tenant");
    }

    public static function prepareHashSharding(string $database, string $table, string $column = "id"): bool {
        return self::defineShardKey($database, $table, $column, "hash");
    }

    public static function shardAwareId(string $database, string $table, array $row = []): string {
        $route = self::shardRouter($database, $table, $row);

        return self::distributedId(self::getInstance(), (string)$route["shard"]);
    }

    public static function crossShardQueryRules(array $rules = []): array {
        $file = self::dbRootPath(".system/shards/query_rules.json", true);
        $current = self::readJsonConfig($file, [
            "allow_cross_shard" => false,
            "max_shards" => 1,
            "requires_admin" => true
        ]);
        $current = array_replace($current, $rules, ["updated_at" => time()]);

        self::writeJsonConfig($file, $current);

        return $current;
    }

    private static function storeSystemPlan(string $prefix, array $plan): array {
        $file = self::dbRootPath(
            ".system/plans/" .
            self::safeSegment($prefix) . "_" .
            date("Ymd_His") . "_" .
            substr(hash("sha1", json_encode($plan)), 0, 10) .
            ".json",
            true
        );

        self::writeJsonConfig($file, $plan);

        $plan["file"] = $file;

        return $plan;
    }

    public static function prepareShardRebalancing(): array {
        $map = self::shardMap();
        $plan = [
            "type" => "shard_rebalancing",
            "map" => $map,
            "steps" => [],
            "created_at" => time(),
            "status" => "prepared"
        ];

        foreach (($map["shards"] ?? []) as $name => $cfg) {
            $plan["steps"][] = [
                "shard" => $name,
                "action" => "measure_load_then_move_buckets",
                "weight" => $cfg["weight"] ?? 1
            ];
        }

        return self::storeSystemPlan("shard_rebalance", $plan);
    }

    public static function prepareShardMigration(string $fromShard, string $toShard, array $options = []): array {
        return self::storeSystemPlan("shard_migration", [
            "type" => "shard_migration",
            "from" => self::safeSegment($fromShard),
            "to" => self::safeSegment($toShard),
            "options" => $options,
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function shardHealth(): array {
        $map = self::shardMap();
        $out = [];

        foreach (($map["shards"] ?? []) as $name => $cfg) {
            $out[$name] = [
                "ok" => (($cfg["status"] ?? "online") === "online"),
                "status" => $cfg["status"] ?? "unknown",
                "role" => $cfg["role"] ?? "primary",
                "updated_at" => $cfg["updated_at"] ?? $cfg["created_at"] ?? 0
            ];
        }

        return [
            "ok" => !in_array(false, array_column($out, "ok"), true),
            "shards" => $out
        ];
    }

    public static function prepareCrossShardTransactions(): array {
        return self::storeSystemPlan("cross_shard_transactions", [
            "type" => "cross_shard_transactions",
            "mode" => "two_phase_commit_prepared",
            "enabled" => false,
            "created_at" => time()
        ]);
    }

    public static function prepareShardAwareBackups(): array {
        return self::storeSystemPlan("shard_backups", [
            "type" => "shard_aware_backups",
            "shards" => self::shardMap()["shards"] ?? [],
            "created_at" => time()
        ]);
    }

    public static function prepareShardMonitoring(): array {
        return self::storeSystemPlan("shard_monitoring", [
            "type" => "shard_monitoring",
            "metrics" => [
                "health",
                "lag",
                "size",
                "ops"
            ],
            "created_at" => time()
        ]);
    }

    public static function definePrimaryReplicaModel(array $config = []): array {
        $file = self::dbRootPath(".system/replication/config.json", true);
        $current = self::readJsonConfig($file, [
            "mode" => "primary_replica",
            "primary" => self::safeSegment(gethostname() ?: "node0"),
            "replicas" => [],
            "async" => true
        ]);
        $current = array_replace_recursive($current, $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $current);

        return $current;
    }

    public static function replicationLog(): string {
        return self::dbRootPath(".system/replication/log.jsonl", true);
    }

    private static function appendReplicationEvent(string $action, array $payload): bool {
        $cfg = self::readJsonConfig(self::dbRootPath(".system/replication/config.json"), []);

        if (empty($cfg) || (($cfg["enabled"] ?? true) === false)) {
            return true;
        }

        $entry = [
            "lsn" => time() . "-" . bin2hex(random_bytes(4)),
            "action" => $action,
            "instance" => self::getInstance(),
            "payload" => $payload,
            "ts" => time()
        ];

        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false && GBDBStorage::appendLine(self::replicationLog(), $json . "\n");
    }

    public static function replicateAsync(): array {
        $log = self::replicationLog();
        $cfg = self::definePrimaryReplicaModel();
        $entries = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $slots = self::readJsonConfig(self::dbRootPath(".system/replication/slots.json", true), ["slots" => []]);

        foreach ((array)($cfg["replicas"] ?? []) as $replica) {
            $name = self::safeSegment((string)($replica["name"] ?? $replica));
            $slots["slots"][$name] = [
                "replica" => $name,
                "last_lsn" => count($entries),
                "updated_at" => time()
            ];
        }

        self::writeJsonConfig(self::dbRootPath(".system/replication/slots.json", true), $slots);

        return [
            "ok" => true,
            "entries" => count($entries),
            "slots" => $slots
        ];
    }

    public static function prepareReadReplica(string $name, array $config = []): array {
        $cfg = self::definePrimaryReplicaModel();
        $cfg["replicas"][] = array_replace([
            "name" => self::safeSegment($name),
            "read_only" => true,
            "status" => "catchup"
        ], $config);

        return self::definePrimaryReplicaModel($cfg);
    }

    public static function replicaCatchup(string $replica): array {
        $replica = self::safeSegment($replica);
        $sync = self::replicateAsync();

        return [
            "ok" => true,
            "replica" => $replica,
            "synced" => $sync
        ];
    }

    public static function replicaLag(): array {
        $entries = is_file(self::replicationLog())
            ? count(file(self::replicationLog(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            : 0;

        $slots = self::readJsonConfig(self::dbRootPath(".system/replication/slots.json"), ["slots" => []]);
        $lag = [];

        foreach (($slots["slots"] ?? []) as $name => $slot) {
            $lag[$name] = max(0, $entries - (int)($slot["last_lsn"] ?? 0));
        }

        return [
            "ok" => true,
            "entries" => $entries,
            "lag" => $lag
        ];
    }

    public static function replicaHealth(): array {
        $lag = self::replicaLag();
        $health = [];

        foreach (($lag["lag"] ?? []) as $name => $value) {
            $health[$name] = [
                "ok" => $value < 1000,
                "lag" => $value
            ];
        }

        return [
            "ok" => !in_array(false, array_column($health, "ok"), true),
            "replicas" => $health
        ];
    }

    public static function prepareReplicaRepair(string $replica = ""): array {
        return self::storeSystemPlan("replica_repair", [
            "type" => "replica_repair",
            "replica" => self::safeSegment($replica),
            "lag" => self::replicaLag(),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function prepareFailover(string $replica = ""): array {
        return self::storeSystemPlan("failover", [
            "type" => "failover",
            "candidate" => self::safeSegment($replica),
            "health" => self::replicaHealth(),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function promoteReplicaToPrimary(string $replica): array {
        $cfg = self::definePrimaryReplicaModel();
        $cfg["old_primary"] = $cfg["primary"] ?? "";
        $cfg["primary"] = self::safeSegment($replica);
        $cfg["promoted_at"] = time();

        return self::definePrimaryReplicaModel($cfg);
    }

    public static function enableSplitBrainProtection(array $config = []): array {
        $file = self::dbRootPath(".system/replication/split_brain.json", true);
        $cfg = array_replace([
            "enabled" => true,
            "requires_quorum" => true,
            "fencing" => "manual",
            "created_at" => time()
        ], $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function prepareReplicationSlots(array $replicas = []): array {
        $slots = ["slots" => []];

        foreach ($replicas as $replica) {
            $slots["slots"][self::safeSegment((string)$replica)] = [
                "last_lsn" => 0,
                "created_at" => time()
            ];
        }

        self::writeJsonConfig(self::dbRootPath(".system/replication/slots.json", true), $slots);

        return $slots;
    }

    public static function prepareShardReplication(string $shard = ""): array {
        return self::storeSystemPlan("shard_replication", [
            "type" => "shard_replication",
            "shard" => self::safeSegment($shard),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function prepareTenantReplication(string $tenant = ""): array {
        return self::storeSystemPlan("tenant_replication", [
            "type" => "tenant_replication",
            "tenant" => self::safeSegment($tenant),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function registerClusterNode(string $node, array $config = []): array {
        $node = self::safeSegment($node);
        $file = self::dbRootPath(".system/cluster/nodes.json", true);
        $nodes = self::readJsonConfig($file, ["nodes" => []]);
        $nodes["nodes"][$node] = array_replace([
            "name" => $node,
            "role" => "worker",
            "status" => "online",
            "last_heartbeat" => time(),
            "created_at" => time()
        ], $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $nodes);

        return $nodes;
    }

    public static function clusterNodes(): array {
        return self::readJsonConfig(self::dbRootPath(".system/cluster/nodes.json"), ["nodes" => []]);
    }

    public static function heartbeatNode(string $node = ""): array {
        $node = self::safeSegment($node !== "" ? $node : (gethostname() ?: "node0"));

        return self::registerClusterNode($node, [
            "last_heartbeat" => time(),
            "status" => "online"
        ]);
    }

    public static function prepareLeaderElection(): array {
        $nodes = self::clusterNodes();
        $names = array_keys((array)($nodes["nodes"] ?? []));

        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        $leader = $names[0] ?? self::safeSegment(gethostname() ?: "node0");

        return self::storeSystemPlan("leader_election", [
            "type" => "leader_election",
            "candidate" => $leader,
            "nodes" => $names,
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function defineQuorum(int $minNodes = 1): array {
        $file = self::dbRootPath(".system/cluster/quorum.json", true);
        $cfg = [
            "min_nodes" => max(1, $minNodes),
            "updated_at" => time()
        ];

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function clusterHealth(): array {
        $nodes = self::clusterNodes();
        $now = time();
        $health = [];

        foreach (($nodes["nodes"] ?? []) as $name => $node) {
            $health[$name] = [
                "ok" => ($now - (int)($node["last_heartbeat"] ?? 0)) <= 60,
                "role" => $node["role"] ?? "worker",
                "last_heartbeat" => $node["last_heartbeat"] ?? 0
            ];
        }

        return [
            "ok" => !in_array(false, array_column($health, "ok"), true),
            "nodes" => $health
        ];
    }

    public static function defineNodeRoles(array $roles = []): array {
        $file = self::dbRootPath(".system/cluster/roles.json", true);
        $cfg = array_replace([
            "primary" => [],
            "replica" => [],
            "worker" => [],
            "maintenance" => []
        ], $roles, ["updated_at" => time()]);

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function prepareServiceDiscovery(array $config = []): array {
        return self::storeSystemPlan("service_discovery", array_replace([
            "type" => "service_discovery",
            "driver" => "file_registry",
            "created_at" => time(),
            "status" => "prepared"
        ], $config));
    }

    public static function prepareDistributedLocks(): array {
        return self::storeSystemPlan("distributed_locks", [
            "type" => "distributed_locks",
            "driver" => "lease_file",
            "ttl" => 30,
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function setPrimaryNode(string $node): array {
        return self::registerClusterNode($node, ["role" => "primary"]);
    }

    public static function setReplicaNode(string $node): array {
        return self::registerClusterNode($node, ["role" => "replica"]);
    }

    public static function setWorkerNode(string $node): array {
        return self::registerClusterNode($node, ["role" => "worker"]);
    }

    public static function setMaintenanceNode(string $node): array {
        return self::registerClusterNode($node, ["role" => "maintenance"]);
    }

    public static function clusterConfig(array $config = []): array {
        $file = self::dbRootPath(".system/cluster/config.json", true);
        $cfg = array_replace_recursive(
            self::readJsonConfig($file, [
                "enabled" => true,
                "mode" => "single_primary"
            ]),
            $config,
            ["updated_at" => time()]
        );

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function detectSplitBrain(): array {
        $nodes = self::clusterNodes();
        $primaries = [];

        foreach (($nodes["nodes"] ?? []) as $name => $node) {
            if (($node["role"] ?? "") === "primary" && (($node["status"] ?? "online") === "online")) {
                $primaries[] = $name;
            }
        }

        return [
            "ok" => count($primaries) <= 1,
            "primaries" => $primaries,
            "split_brain" => count($primaries) > 1
        ];
    }

    public static function prepareClusterRecovery(): array {
        return self::storeSystemPlan("cluster_recovery", [
            "type" => "cluster_recovery",
            "health" => self::clusterHealth(),
            "split_brain" => self::detectSplitBrain(),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function backupLog(string $action, array $payload = []): bool {
        $entry = [
            "action" => $action,
            "instance" => self::getInstance(),
            "payload" => $payload,
            "ts" => time()
        ];

        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false && GBDBStorage::appendLine(
            self::dbRootPath(".backups/logs/backup.log", true),
            $json . "\n"
        );
    }

    private static function createBackupFromPath(string $source, string $type, string $target = ""): array {
        $target = $target !== ""
            ? rtrim($target, "/")
            : self::dbRootPath(
                ".backups/" .
                self::safeSegment($type) . "/" .
                date("Ymd_His") . "_" .
                substr(hash("sha1", $source . microtime(true)), 0, 8),
                true
            );

        $report = [
            "ok" => true,
            "type" => $type,
            "source" => $source,
            "path" => $target,
            "files" => [],
            "errors" => [],
            "created_at" => time()
        ];

        self::copyTreeInternal($source, $target . "/data", $report);

        $report["ok"] = empty($report["errors"]);
        $report["checksums"] = self::checksumTreeInternal($target . "/data");

        self::writeJsonConfig($target . "/metadata.json", $report);
        self::backupLog($type, [
            "path" => $target,
            "ok" => $report["ok"],
            "files" => count($report["files"])
        ]);

        return $report;
    }

    public static function fullBackup(string $target = ""): array {
        return self::createBackupFromPath(self::dbRootPath(""), "full", $target);
    }

    public static function prepareIncrementalBackup(): array {
        return self::storeSystemPlan("incremental_backup", [
            "type" => "incremental_backup",
            "base" => "last_full_backup",
            "source" => self::dbRootPath(""),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function prepareDifferentialBackup(): array {
        return self::storeSystemPlan("differential_backup", [
            "type" => "differential_backup",
            "base" => "last_full_backup",
            "source" => self::dbRootPath(""),
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function snapshotBackup(string $target = ""): array {
        return self::createBackupFromPath(Vars::DB_PATH(), "snapshot", $target);
    }

    public static function prepareHotBackup(): array {
        return self::storeSystemPlan("hot_backup", [
            "type" => "hot_backup",
            "requires" => [
                "short_metadata_lock",
                "copy_on_write_snapshot"
            ],
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function coldBackup(string $target = ""): array {
        return self::createBackupFromPath(self::dbRootPath(""), "cold", $target);
    }

    public static function verifyBackup(string $path): array {
        $path = rtrim($path, "/");
        $meta = self::readJsonConfig($path . "/metadata.json", []);
        $expected = is_array($meta["checksums"] ?? null) ? $meta["checksums"] : [];
        $actual = self::checksumTreeInternal($path . "/data");
        $missing = array_diff_key($expected, $actual);
        $changed = [];

        foreach ($expected as $file => $sum) {
            if (isset($actual[$file]) && $actual[$file] !== $sum) {
                $changed[$file] = [
                    "expected" => $sum,
                    "actual" => $actual[$file]
                ];
            }
        }

        return [
            "ok" => empty($missing) && empty($changed),
            "missing" => $missing,
            "changed" => $changed,
            "files" => count($actual)
        ];
    }

    public static function restoreTest(string $backupPath): array {
        $verify = self::verifyBackup($backupPath);
        $target = self::dbRootPath(
            ".temp/restore_tests/" .
            date("Ymd_His") . "_" .
            substr(hash("sha1", $backupPath), 0, 8),
            true
        );

        $report = [
            "ok" => true,
            "files" => [],
            "errors" => []
        ];

        if ($verify["ok"] ?? false) {
            self::copyTreeInternal(rtrim($backupPath, "/") . "/data", $target, $report);
        }

        return [
            "ok" => ($verify["ok"] ?? false) && empty($report["errors"]),
            "verify" => $verify,
            "target" => $target,
            "copy" => $report
        ];
    }

    public static function preparePointInTimeRecovery(?int $timestamp = null): array {
        return self::storeSystemPlan("pitr", [
            "type" => "point_in_time_recovery",
            "target_ts" => $timestamp ?? time(),
            "uses" => [
                "full_backup",
                "replication_log",
                "wal"
            ],
            "created_at" => time(),
            "status" => "prepared"
        ]);
    }

    public static function encryptedBackup(string $target = ""): array {
        $backup = self::fullBackup($target);
        $manifest = json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $enc = class_exists("Crypt")
            ? Crypt::encode($manifest === false ? "{}" : $manifest)
            : base64_encode($manifest === false ? "{}" : $manifest);

        $file = rtrim((string)$backup["path"], "/") . "/metadata.enc";
        $ok = GBDBStorage::atomicWrite($file, $enc);

        $backup["encrypted_metadata"] = $file;
        $backup["encrypted"] = $ok;

        self::backupLog("encrypted_backup", [
            "path" => $backup["path"],
            "ok" => $ok
        ]);

        return $backup;
    }

    public static function prepareRemoteBackups(array $config = []): array {
        $file = self::dbRootPath(".backups/remote.json", true);
        $cfg = array_replace([
            "enabled" => false,
            "driver" => "manual",
            "target" => "",
            "created_at" => time()
        ], $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function prepareOffsiteBackups(array $config = []): array {
        $file = self::dbRootPath(".backups/offsite.json", true);
        $cfg = array_replace([
            "enabled" => false,
            "driver" => "manual",
            "retention_days" => 30,
            "created_at" => time()
        ], $config, ["updated_at" => time()]);

        self::writeJsonConfig($file, $cfg);

        return $cfg;
    }

    public static function rotateBackups(int $keep = 10): array {
        $base = self::dbRootPath(".backups");
        $dirs = glob($base . "/*/*", GLOB_ONLYDIR) ?: [];

        usort($dirs, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $deleted = [];

        foreach (array_slice($dirs, max(0, $keep)) as $dir) {
            self::deleteTreeInternal($dir);
            $deleted[] = $dir;
        }

        self::backupLog("backup_rotation", [
            "keep" => $keep,
            "deleted" => $deleted
        ]);

        return [
            "ok" => true,
            "deleted" => $deleted,
            "kept" => min($keep, count($dirs))
        ];
    }

    public static function applyBackupRetention(int $days = 30): array {
        $base = self::dbRootPath(".backups");
        $dirs = glob($base . "/*/*", GLOB_ONLYDIR) ?: [];
        $limit = time() - max(1, $days) * 86400;
        $deleted = [];

        foreach ($dirs as $dir) {
            if ((int)@filemtime($dir) < $limit) {
                self::deleteTreeInternal($dir);
                $deleted[] = $dir;
            }
        }

        self::backupLog("backup_retention", [
            "days" => $days,
            "deleted" => $deleted
        ]);

        return [
            "ok" => true,
            "deleted" => $deleted,
            "days" => $days
        ];
    }

    public static function tenantBackup(string $tenant, string $target = ""): array {
        $old = self::getInstance();

        self::setInstance($tenant);

        $path = self::instancePath(false);

        self::setInstance($old);

        return self::createBackupFromPath($path, "tenant_" . self::safeSegment($tenant), $target);
    }

    public static function instanceBackup(string $instance, string $target = ""): array {
        return self::tenantBackup($instance, $target);
    }

    public static function tableBackup(string $database, string $table, string $target = ""): array {
        $dir = dirname(self::makePath($database, $table, true));
        $name = basename(self::makePath($database, $table, true));
        $target = $target !== ""
            ? rtrim($target, "/")
            : self::dbRootPath(
                ".backups/table/" .
                date("Ymd_His") . "_" .
                self::safeSegment(self::getInstance()) . "_" .
                self::safeSegment($database) . "_" .
                self::safeSegment($table),
                true
            );

        $report = [
            "ok" => true,
            "type" => "table",
            "path" => $target,
            "files" => [],
            "errors" => [],
            "created_at" => time()
        ];

        if (!is_dir($target . "/data")) {
            @mkdir($target . "/data", 0777, true);
        }

        foreach (glob($dir . "/" . $name . "*") ?: [] as $file) {
            $dst = $target . "/data/" . basename($file);

            if (@copy($file, $dst)) {
                $report["files"][] = $dst;
            } else {
                $report["errors"][] = $file;
            }
        }

        $report["checksums"] = self::checksumTreeInternal($target . "/data");
        $report["ok"] = empty($report["errors"]);

        self::writeJsonConfig($target . "/metadata.json", $report);
        self::backupLog("table_backup", [
            "path" => $target,
            "ok" => $report["ok"]
        ]);

        return $report;
    }
}
