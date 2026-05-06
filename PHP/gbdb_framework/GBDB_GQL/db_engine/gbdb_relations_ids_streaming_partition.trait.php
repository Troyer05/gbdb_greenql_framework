<?php

trait GBDB_RelationsIdsStreamingPartitionTrait {
    private static function normalizeRelationDefinition(array $relation): array {
        $name = Format::cleanString((string)($relation["name"] ?? ""));
        $localColumn = trim((string)($relation["local_column"] ?? $relation["column"] ?? ""));
        $refInstance = Format::cleanString((string)($relation["ref_instance"] ?? $relation["foreign_instance"] ?? self::getInstance()));
        $refDatabase = Format::cleanString((string)($relation["ref_database"] ?? $relation["foreign_database"] ?? $relation["database"] ?? ""));
        $refTable = Format::cleanString((string)($relation["ref_table"] ?? $relation["foreign_table"] ?? $relation["table"] ?? ""));
        $refColumn = trim((string)($relation["ref_column"] ?? $relation["foreign_column"] ?? "id"));
        $onDelete = strtolower(trim((string)($relation["on_delete"] ?? "restrict")));
        $onUpdate = strtolower(trim((string)($relation["on_update"] ?? "restrict")));

        if (!in_array($onDelete, ["cascade", "restrict", "set_null", "set null"], true)) {
            $onDelete = "restrict";
        }

        if (!in_array($onUpdate, ["cascade", "restrict"], true)) {
            $onUpdate = "restrict";
        }

        if ($onDelete === "set null") {
            $onDelete = "set_null";
        }

        if ($refInstance === "") {
            $refInstance = self::getInstance();
        }

        if ($name === "") {
            $name = Format::cleanString($localColumn . "_to_" . $refDatabase . "_" . $refTable . "_" . $refColumn);
        }

        return [
            "name" => $name,
            "type" => "foreign_key",
            "local_column" => $localColumn,
            "ref_instance" => $refInstance,
            "ref_database" => $refDatabase,
            "ref_table" => $refTable,
            "ref_column" => $refColumn === "" ? "id" : $refColumn,
            "on_delete" => $onDelete,
            "on_update" => $onUpdate,
            "active" => (bool)($relation["active"] ?? true),
            "created_at" => (int)($relation["created_at"] ?? time())
        ];
    }

    public static function addForeignKey(
        string $database,
        string $table,
        string $column,
        string $refDatabase,
        string $refTable,
        string $refColumn = "id",
        array $options = []
    ): bool {
        $column = trim($column);
        $refColumn = trim($refColumn) === "" ? "id" : trim($refColumn);

        if ($column === "" || $refDatabase === "" || $refTable === "") {
            return false;
        }

        $schema = self::schemaTable($database, $table);

        if (!isset($schema["relations"]) || !is_array($schema["relations"])) {
            $schema["relations"] = [];
        }

        $relation = self::normalizeRelationDefinition([
            "name" => $options["name"] ?? "",
            "local_column" => $column,
            "ref_instance" => $options["ref_instance"] ?? self::getInstance(),
            "ref_database" => $refDatabase,
            "ref_table" => $refTable,
            "ref_column" => $refColumn,
            "on_delete" => $options["on_delete"] ?? "restrict",
            "on_update" => $options["on_update"] ?? "restrict"
        ]);

        if (($relation["name"] ?? "") === "" || ($relation["local_column"] ?? "") === "") {
            return false;
        }

        $schema["relations"][$relation["name"]] = $relation;
        $schema["constraints"][] = [
            "type" => "foreign_key",
            "name" => $relation["name"],
            "definition" => $relation
        ];
        $schema["version"] = (int)($schema["version"] ?? 1) + 1;

        self::addMigrationHistory($schema, "relation", "add_foreign_key", $relation);

        $ok = self::writeSchemaTable($database, $table, $schema);

        if ($ok) {
            self::syncRelationsToMeta($database, $table, $schema);
        }

        if ($ok) {
            self::createRelationIndex($database, $table, $column);
        }

        return $ok;
    }

    public static function defineRelation(
        string $database,
        string $table,
        string $column,
        string $refDatabase,
        string $refTable,
        string $refColumn = "id",
        array $options = []
    ): bool {
        return self::addForeignKey($database, $table, $column, $refDatabase, $refTable, $refColumn, $options);
    }

    public static function dropRelation(string $database, string $table, string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $schema = self::schemaTable($database, $table);

        if (!isset($schema["relations"][$name])) {
            return false;
        }

        unset($schema["relations"][$name]);

        $schema["version"] = (int)($schema["version"] ?? 1) + 1;

        self::addMigrationHistory($schema, "relation", "drop", ["name" => $name]);

        $ok = self::writeSchemaTable($database, $table, $schema);

        if ($ok) {
            self::syncRelationsToMeta($database, $table, $schema);
        }

        return $ok;
    }

    private static function syncRelationsToMeta(string $database, string $table, ?array $schema = null): bool {
        $schema = $schema ?? self::schemaTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);

        $meta["relations"] = self::schemaRelations($schema);
        $meta["relation_indexes"] = array_values(array_unique(array_map(
            fn($r) => (string)$r["local_column"],
            $meta["relations"]
        )));

        return self::writeMeta($metaFile, $meta);
    }

    private static function schemaRelations(array $schema): array {
        $relations = [];

        foreach (($schema["relations"] ?? []) as $name => $relation) {
            if (!is_array($relation)) {
                continue;
            }

            $rel = self::normalizeRelationDefinition($relation);

            if (($rel["name"] ?? "") === "") {
                $rel["name"] = Format::cleanString((string)$name);
            }

            if (($rel["local_column"] ?? "") === "" || ($rel["ref_database"] ?? "") === "" || ($rel["ref_table"] ?? "") === "") {
                continue;
            }

            $relations[$rel["name"]] = $rel;
        }

        return $relations;
    }

    public static function createRelationIndex(string $database, string $table, string $column): bool {
        if (method_exists(static::class, "createIndex")) {
            return (bool)self::createIndex($database, $table, $column, "single");
        }

        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);

        if (!isset($meta["indexes"]) || !is_array($meta["indexes"])) {
            $meta["indexes"] = [];
        }

        if (!in_array($column, $meta["indexes"], true)) {
            $meta["indexes"][] = $column;
        }

        return self::writeMeta($metaFile, $meta);
    }

    private static function validateForeignKeysForRow(string $database, string $table, array $row): array {
        $schema = self::schemaTable($database, $table);
        $errors = [];

        foreach (self::schemaRelations($schema) as $relation) {
            if (($relation["active"] ?? true) !== true) {
                continue;
            }

            $localColumn = (string)$relation["local_column"];
            $value = $row[$localColumn] ?? null;

            if ($value === null || $value === "") {
                continue;
            }

            $old = self::getInstance();

            self::setInstance((string)$relation["ref_instance"]);

            $found = self::elementExists(
                (string)$relation["ref_database"],
                (string)$relation["ref_table"],
                (string)$relation["ref_column"],
                $value
            );

            self::setInstance($old);

            if (!$found) {
                $errors[] = "foreign_key:" . (string)$relation["name"];
            }
        }

        return [
            "ok" => empty($errors),
            "errors" => $errors
        ];
    }

    private static function incomingRelations(string $database, string $table): array {
        $currentInstance = self::getInstance();
        $schema = self::readSchema();
        $incoming = [];

        foreach ($schema as $instance => $bases) {
            if (!is_array($bases)) {
                continue;
            }

            foreach ($bases as $srcDb => $tables) {
                if (!is_array($tables)) {
                    continue;
                }

                foreach ($tables as $srcTable => $tableSchema) {
                    $relations = self::schemaRelations(self::normalizeSchemaTableEntry($tableSchema));

                    foreach ($relations as $relation) {
                        if (($relation["ref_instance"] ?? $currentInstance) !== $currentInstance) {
                            continue;
                        }

                        if (($relation["ref_database"] ?? "") !== $database) {
                            continue;
                        }

                        if (($relation["ref_table"] ?? "") !== $table) {
                            continue;
                        }

                        $relation["source_instance"] = (string)$instance;
                        $relation["source_database"] = (string)$srcDb;
                        $relation["source_table"] = (string)$srcTable;
                        $incoming[] = $relation;
                    }
                }
            }
        }

        return $incoming;
    }

    private static function beforeRelationDelete(string $database, string $table, array $rows): array {
        foreach (self::incomingRelations($database, $table) as $relation) {
            $refColumn = (string)$relation["ref_column"];
            $values = [];

            foreach ($rows as $row) {
                if (is_array($row) && array_key_exists($refColumn, $row)) {
                    $values[] = $row[$refColumn];
                }
            }

            $values = array_values(array_unique($values, SORT_REGULAR));

            if (empty($values)) {
                continue;
            }

            $old = self::getInstance();

            self::setInstance((string)$relation["source_instance"]);

            foreach ($values as $value) {
                $matches = self::getRowsByColumn(
                    (string)$relation["source_database"],
                    (string)$relation["source_table"],
                    (string)$relation["local_column"],
                    $value
                );

                if (empty($matches)) {
                    continue;
                }

                if (($relation["on_delete"] ?? "restrict") === "restrict") {
                    self::setInstance($old);

                    return [
                        "ok" => false,
                        "error" => "foreign_key_restrict",
                        "relation" => $relation["name"]
                    ];
                }

                if (($relation["on_delete"] ?? "restrict") === "cascade") {
                    self::deleteData(
                        (string)$relation["source_database"],
                        (string)$relation["source_table"],
                        (string)$relation["local_column"],
                        $value
                    );
                }

                if (($relation["on_delete"] ?? "restrict") === "set_null") {
                    self::editData(
                        (string)$relation["source_database"],
                        (string)$relation["source_table"],
                        (string)$relation["local_column"],
                        $value,
                        [(string)$relation["local_column"] => null]
                    );
                }
            }

            self::setInstance($old);
        }

        return ["ok" => true];
    }

    private static function afterRelationUpdate(string $database, string $table, array $beforeRows, array $afterRows): array {
        foreach (self::incomingRelations($database, $table) as $relation) {
            $refColumn = (string)$relation["ref_column"];

            foreach ($beforeRows as $id => $before) {
                $after = $afterRows[$id] ?? null;

                if (!is_array($before) || !is_array($after)) {
                    continue;
                }

                if (!array_key_exists($refColumn, $before) || !array_key_exists($refColumn, $after)) {
                    continue;
                }

                if ($before[$refColumn] == $after[$refColumn]) {
                    continue;
                }

                if (($relation["on_update"] ?? "restrict") === "restrict") {
                    return [
                        "ok" => false,
                        "error" => "foreign_key_update_restrict",
                        "relation" => $relation["name"]
                    ];
                }

                if (($relation["on_update"] ?? "restrict") === "cascade") {
                    $old = self::getInstance();

                    self::setInstance((string)$relation["source_instance"]);

                    self::editData(
                        (string)$relation["source_database"],
                        (string)$relation["source_table"],
                        (string)$relation["local_column"],
                        $before[$refColumn],
                        [(string)$relation["local_column"] => $after[$refColumn]]
                    );

                    self::setInstance($old);
                }
            }
        }

        return ["ok" => true];
    }

    private static function getRowsByColumn(string $database, string $table, string $column, mixed $value): array {
        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            return [];
        }

        $out = [];

        foreach ($rows as $row) {
            if (is_array($row) && array_key_exists($column, $row) && $row[$column] == $value) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public static function checkOrphans(string $database, string $table): array {
        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            $rows = [];
        }

        $orphans = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $check = self::validateForeignKeysForRow($database, $table, $row);

            if (!($check["ok"] ?? false)) {
                $orphans[] = [
                    "id" => $row["id"] ?? null,
                    "errors" => $check["errors"] ?? []
                ];
            }
        }

        return [
            "ok" => empty($orphans),
            "orphans" => $orphans,
            "count" => count($orphans)
        ];
    }

    public static function repairOrphans(string $database, string $table, string $mode = "report"): array {
        $mode = strtolower($mode);
        $report = self::checkOrphans($database, $table);
        $fixed = 0;

        if ($mode === "set_null") {
            $schema = self::schemaTable($database, $table);
            $relations = self::schemaRelations($schema);

            foreach (($report["orphans"] ?? []) as $orphan) {
                foreach ($relations as $relation) {
                    self::editData($database, $table, "id", $orphan["id"], [
                        (string)$relation["local_column"] => null
                    ]);
                }

                $fixed++;
            }
        } elseif ($mode === "delete") {
            foreach (($report["orphans"] ?? []) as $orphan) {
                if (isset($orphan["id"])) {
                    self::deleteData($database, $table, "id", $orphan["id"]);
                    $fixed++;
                }
            }
        }

        return [
            "ok" => true,
            "mode" => $mode,
            "fixed" => $fixed,
            "before" => $report,
            "after" => self::checkOrphans($database, $table)
        ];
    }

    public static function repairConstraints(string $database, string $table): array {
        $schemaRepair = self::repairSchema($database, $table);

        self::syncRelationsToMeta($database, $table);

        foreach (self::schemaRelations(self::schemaTable($database, $table)) as $relation) {
            self::createRelationIndex($database, $table, (string)$relation["local_column"]);
        }

        return [
            "ok" => true,
            "schema" => $schemaRepair,
            "orphans" => self::checkOrphans($database, $table)
        ];
    }

    public static function relationGraph(?string $database = null): array {
        $schema = self::readSchema();
        $instance = self::getInstance();
        $nodes = [];
        $edges = [];

        foreach (($schema[$instance] ?? []) as $db => $tables) {
            if ($database !== null && $database !== $db) {
                continue;
            }

            if (!is_array($tables)) {
                continue;
            }

            foreach ($tables as $table => $tableSchema) {
                $node = $instance . ":" . $db . "." . $table;
                $nodes[$node] = [
                    "instance" => $instance,
                    "database" => $db,
                    "table" => $table
                ];

                foreach (self::schemaRelations(self::normalizeSchemaTableEntry($tableSchema)) as $relation) {
                    $to = $relation["ref_instance"] . ":" . $relation["ref_database"] . "." . $relation["ref_table"];
                    $nodes[$to] = [
                        "instance" => $relation["ref_instance"],
                        "database" => $relation["ref_database"],
                        "table" => $relation["ref_table"]
                    ];
                    $edges[] = [
                        "from" => $node,
                        "to" => $to,
                        "relation" => $relation
                    ];
                }
            }
        }

        return [
            "nodes" => array_values($nodes),
            "edges" => $edges
        ];
    }

    public static function relationDocs(?string $database = null): string {
        $graph = self::relationGraph($database);
        $md = "# GBDB Relation Docs\n\n";

        foreach ($graph["edges"] as $edge) {
            $r = $edge["relation"];
            $md .= "- `" . $edge["from"] . "." . $r["local_column"] . "` → `" . $edge["to"] . "." . $r["ref_column"] . "`";
            $md .= " (`ON DELETE " . strtoupper((string)$r["on_delete"]) . "`, `ON UPDATE " . strtoupper((string)$r["on_update"]) . "`)\n";
        }

        return $md;
    }

    public static function checkRelations(?string $database = null): array {
        $errors = [];

        foreach (self::relationGraph($database)["nodes"] as $node) {
            if (($node["instance"] ?? "") !== self::getInstance()) {
                continue;
            }

            $check = self::checkOrphans((string)$node["database"], (string)$node["table"]);

            if (!($check["ok"] ?? true)) {
                $errors[] = [
                    "table" => $node,
                    "orphans" => $check["orphans"] ?? []
                ];
            }
        }

        return [
            "ok" => empty($errors),
            "errors" => $errors
        ];
    }

    public static function uuid(): string {
        return self::uuidValue();
    }

    public static function ulid(): string {
        return self::ulidValue();
    }

    public static function snowflakeId(int $node = 1): string {
        $node = max(0, min(1023, $node));
        $ms = (int)floor(microtime(true) * 1000) - 1704067200000;
        $seq = random_int(0, 4095);

        if (PHP_INT_SIZE >= 8) {
            return (string)(($ms << 22) | ($node << 12) | $seq);
        }

        return (string)$ms . str_pad((string)$node, 4, "0", STR_PAD_LEFT) . str_pad((string)$seq, 4, "0", STR_PAD_LEFT);
    }

    public static function distributedId(string $tenant = "", string $shard = ""): string {
        $tenant = Format::cleanString($tenant) ?: self::getInstance();
        $shard = Format::cleanString($shard) ?: "s0";

        return $tenant . "_" . $shard . "_" . self::ulidValue();
    }

    public static function idExists(string $database, string $table, string $column, mixed $id): bool {
        return self::elementExists($database, $table, $column, $id);
    }

    public static function safeId(string $database, string $table, string $column = "uid", string $type = "ulid", array $options = []): string {
        for ($i = 0; $i < 50; $i++) {
            $id = match (strtolower($type)) {
                "uuid" => self::uuidValue(),
                "snowflake" => self::snowflakeId((int)($options["node"] ?? 1)),
                "distributed" => self::distributedId((string)($options["tenant"] ?? ""), (string)($options["shard"] ?? "")),
                default => self::ulidValue()
            };

            if (!self::idExists($database, $table, $column, $id)) {
                return $id;
            }
        }

        return self::distributedId((string)($options["tenant"] ?? ""), (string)($options["shard"] ?? "retry"));
    }

    public static function readGenerator(string $database, string $table, int $chunkSize = 500): Generator {
        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            return;
        }

        $chunkSize = max(1, $chunkSize);
        $i = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            yield $row;

            $i++;

            if ($i % $chunkSize === 0 && function_exists("gc_collect_cycles")) {
                gc_collect_cycles();
            }
        }
    }

    public static function chunkedRows(string $database, string $table, int $chunkSize = 500, ?callable $filter = null): Generator {
        $chunk = [];

        foreach (self::readGenerator($database, $table, $chunkSize) as $row) {
            if ($filter !== null && !$filter($row)) {
                continue;
            }

            $chunk[] = $row;

            if (count($chunk) >= max(1, $chunkSize)) {
                yield $chunk;
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            yield $chunk;
        }
    }

    public static function openCursor(string $database, string $table, array $options = []): array {
        $token = "cur_" . bin2hex(random_bytes(16));
        $dir = Vars::DB_PATH() . ".temp/cursors/";

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $state = [
            "token" => $token,
            "instance" => self::getInstance(),
            "database" => Format::cleanString($database),
            "table" => Format::cleanString($table),
            "offset" => 0,
            "chunk_size" => max(1, min(10000, (int)($options["chunk_size"] ?? 500))),
            "max_rows" => max(1, (int)($options["max_rows"] ?? 100000)),
            "created_at" => time(),
            "expires_at" => time() + max(60, (int)($options["ttl"] ?? 3600))
        ];

        GBDBStorage::atomicWrite($dir . $token . ".json", json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            "ok" => true,
            "token" => $token,
            "chunk_size" => $state["chunk_size"],
            "expires_at" => $state["expires_at"]
        ];
    }

    public static function fetchCursor(string $token): array {
        $token = preg_replace('/[^a-zA-Z0-9_\-]/', '', $token) ?? "";
        $file = Vars::DB_PATH() . ".temp/cursors/" . $token . ".json";

        if ($token === "" || !is_file($file)) {
            return [
                "ok" => false,
                "error" => "cursor_not_found",
                "rows" => []
            ];
        }

        $state = json_decode((string)@file_get_contents($file), true);

        if (!is_array($state) || (int)($state["expires_at"] ?? 0) < time()) {
            @unlink($file);

            return [
                "ok" => false,
                "error" => "cursor_expired",
                "rows" => []
            ];
        }

        $old = self::getInstance();

        self::setInstance((string)$state["instance"]);

        $rows = self::getData((string)$state["database"], (string)$state["table"]);

        self::setInstance($old);

        if (!is_array($rows)) {
            $rows = [];
        }

        $rows = array_slice($rows, (int)$state["offset"], (int)$state["chunk_size"]);
        $state["offset"] = (int)$state["offset"] + count($rows);
        $done = empty($rows) || $state["offset"] >= (int)$state["max_rows"];

        if ($done) {
            @unlink($file);
        } else {
            GBDBStorage::atomicWrite($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return [
            "ok" => true,
            "token" => $token,
            "rows" => $rows,
            "next_offset" => $state["offset"],
            "done" => $done
        ];
    }

    public static function getDataGuarded(string $database, string $table, int $maxRows = 10000): array {
        $meta = self::meta($database, $table);

        if ((int)($meta["rows"] ?? 0) > $maxRows) {
            return [
                "ok" => false,
                "error" => "large_table_guard",
                "rows" => [],
                "rows_total" => (int)$meta["rows"]
            ];
        }

        return [
            "ok" => true,
            "rows" => self::getData($database, $table),
            "rows_total" => (int)($meta["rows"] ?? 0)
        ];
    }

    public static function importStreaming(string $database, string $table, iterable $rows, int $chunkSize = 500): array {
        $inserted = 0;
        $failed = 0;
        $chunkSize = max(1, $chunkSize);
        $buffer = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $failed++;
                continue;
            }

            $buffer[] = $row;

            if (count($buffer) >= $chunkSize) {
                foreach ($buffer as $r) {
                    self::insertData($database, $table, $r) > 0 ? $inserted++ : $failed++;
                }

                $buffer = [];
            }
        }

        foreach ($buffer as $r) {
            self::insertData($database, $table, $r) > 0 ? $inserted++ : $failed++;
        }

        return [
            "ok" => $failed === 0,
            "inserted" => $inserted,
            "failed" => $failed
        ];
    }

    public static function exportStreaming(string $database, string $table, callable $writer, int $chunkSize = 500): array {
        $chunks = 0;
        $rows = 0;

        foreach (self::chunkedRows($database, $table, $chunkSize) as $chunk) {
            $writer($chunk);
            $chunks++;
            $rows += count($chunk);
        }

        return [
            "ok" => true,
            "chunks" => $chunks,
            "rows" => $rows
        ];
    }

    public static function setMaxRows(string $database, string $table, int $maxRows): bool {
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);
        $meta["max_rows_per_query"] = max(1, $maxRows);

        return self::writeMeta($metaFile, $meta);
    }

    public static function definePartitioning(string $database, string $table, string $type, string $column, array $options = []): bool {
        $type = strtolower(trim($type));

        if (!in_array($type, ["date", "user", "tenant", "instance", "hash", "bucket"], true)) {
            return false;
        }

        $schema = self::schemaTable($database, $table);
        $schema["partitioning"] = [
            "type" => $type,
            "column" => trim($column),
            "options" => $options,
            "created_at" => time(),
            "active" => true
        ];
        $schema["version"] = (int)($schema["version"] ?? 1) + 1;

        self::addMigrationHistory($schema, "partitioning", "define", $schema["partitioning"]);

        $ok = self::writeSchemaTable($database, $table, $schema);

        if ($ok) {
            self::syncPartitioningToMeta($database, $table, $schema);
        }

        return $ok;
    }

    public static function partitionByDate(string $database, string $table, string $column, string $format = "Y-m"): bool {
        return self::definePartitioning($database, $table, "date", $column, ["format" => $format]);
    }

    public static function partitionByUser(string $database, string $table, string $column = "user_id"): bool {
        return self::definePartitioning($database, $table, "user", $column);
    }

    public static function partitionByTenant(string $database, string $table, string $column = "tenant_id"): bool {
        return self::definePartitioning($database, $table, "tenant", $column);
    }

    public static function partitionByHash(string $database, string $table, string $column, int $buckets = 16): bool {
        return self::definePartitioning($database, $table, "hash", $column, ["buckets" => max(1, $buckets)]);
    }

    private static function syncPartitioningToMeta(string $database, string $table, ?array $schema = null): bool {
        $schema = $schema ?? self::schemaTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);
        $meta["partitioning"] = is_array($schema["partitioning"] ?? null) ? $schema["partitioning"] : [];

        return self::writeMeta($metaFile, $meta);
    }

    public static function partitionKey(string $database, string $table, array $row): string {
        $schema = self::schemaTable($database, $table);
        $p = is_array($schema["partitioning"] ?? null) ? $schema["partitioning"] : [];

        if (empty($p) || empty($p["column"])) {
            return "default";
        }

        $value = $row[(string)$p["column"]] ?? null;

        return match ((string)$p["type"]) {
            "date" => date(
                (string)($p["options"]["format"] ?? "Y-m"),
                is_numeric($value) ? (int)$value : (strtotime((string)$value) ?: time())
            ),
            "user", "tenant", "instance" => Format::cleanString((string)$value) ?: "unknown",
            "hash", "bucket" => "b" . (string)(abs(crc32((string)$value)) % max(1, (int)($p["options"]["buckets"] ?? 16))),
            default => "default"
        };
    }

    private static function recordPartitionStats(string $database, string $table, array $row): void {
        $schema = self::schemaTable($database, $table);

        if (!is_array($schema["partitioning"] ?? null) || empty($schema["partitioning"]["active"])) {
            return;
        }

        $key = self::partitionKey($database, $table, $row);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);

        if (!isset($meta["partitions"]) || !is_array($meta["partitions"])) {
            $meta["partitions"] = [];
        }

        if (!isset($meta["partitions"][$key])) {
            $meta["partitions"][$key] = [
                "rows" => 0,
                "created_at" => time(),
                "updated_at" => time()
            ];
        }

        $meta["partitions"][$key]["rows"] = (int)($meta["partitions"][$key]["rows"] ?? 0) + 1;
        $meta["partitions"][$key]["updated_at"] = time();

        self::writeMeta($metaFile, $meta);
    }

    public static function partitionStats(string $database, string $table): array {
        $meta = self::meta($database, $table);

        return [
            "partitioning" => $meta["partitioning"] ?? [],
            "partitions" => $meta["partitions"] ?? []
        ];
    }

    public static function partitionPrune(string $database, string $table, array $criteria): array {
        $rows = self::getData($database, $table);

        if (!is_array($rows)) {
            return [];
        }

        $schema = self::schemaTable($database, $table);
        $p = is_array($schema["partitioning"] ?? null) ? $schema["partitioning"] : [];

        if (empty($p["column"]) || !array_key_exists((string)$p["column"], $criteria)) {
            return $rows;
        }

        $wanted = self::partitionKey($database, $table, [
            (string)$p["column"] => $criteria[(string)$p["column"]]
        ]);

        return array_values(array_filter(
            $rows,
            fn($row) => is_array($row) && self::partitionKey($database, $table, $row) === $wanted
        ));
    }
}
