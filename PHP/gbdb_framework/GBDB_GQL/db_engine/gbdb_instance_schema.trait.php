<?php

trait GBDB_InstanceSchemaTrait {

    /**
     * Gibt den Projekt-Root zurück.
     * @return string Rückgabewert.
     */
    private static function rootPath(): string {
        $dir = __DIR__;

        while ($dir !== dirname($dir)) {
            if (is_dir($dir . "/GBDB_GQL") && is_dir($dir . "/.config")) {
                return $dir;
            }

            if (is_dir($dir . "/PHP/gbdb_framework")) {
                return $dir . "/PHP/gbdb_framework";
            }

            $dir = dirname($dir);
        }

        return dirname(__DIR__, 2);
    }


    /**
     * Gibt den Pfad zur Schema-Datei zurück.
     * @return string Rückgabewert.
     */
    private static function schemaPath(): string {
        return self::rootPath() . "/" . self::SCHEMA_FILE;
    }


    /**
     * Gibt die aktive Instanz bereinigt zurück.
     * @return string Rückgabewert.
     */
    private static function instanceName(): string {
        $instance = Format::cleanString(self::$instance);

        if ($instance === "") {
            return "default";
        }

        return $instance;
    }


    /**
     * Setzt die aktive Instanz.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    public static function setInstance(string $instance): void {
        $instance = Format::cleanString($instance);

        if ($instance === "") {
            $instance = "default";
        }

        self::$instance = $instance;
    }


    /**
     * Alias für setInstance.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    public static function instance(string $instance): void {
        self::setInstance($instance);
    }


    /**
     * Gibt die aktive Instanz zurück.
     * @return string Rückgabewert.
     */
    public static function getInstance(): string {
        return self::instanceName();
    }


    /**
     * Führt einen Callback in einer temporären Instanz aus und setzt danach zurück.
     * @param string $instance Instanzname.
     * @param callable $callback Callback.
     * @return mixed Rückgabewert des Callbacks.
     */
    public static function withInstance(string $instance, callable $callback): mixed {
        $old = self::getInstance();
        self::setInstance($instance);

        try {
            return $callback();
        } finally {
            self::setInstance($old);
        }
    }


    /**
     * Liest die Schema-Datei.
     * @return array Rückgabewert.
     */
    private static function readSchema(): array {
        $file = self::schemaPath();

        if (!is_file($file)) {
            return [];
        }

        $json = json_decode((string)@file_get_contents($file), true);

        return is_array($json) ? $json : [];
    }


    /**
     * Schreibt die Schema-Datei.
     * @param array $schema Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeSchema(array $schema): bool {
        $file = self::schemaPath();
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        ksort($schema, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($schema as $instance => $databases) {
            if (!is_array($databases)) {
                unset($schema[$instance]);
                continue;
            }

            ksort($databases, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($databases as $database => $tables) {
                if (!is_array($tables)) {
                    unset($databases[$database]);
                    continue;
                }

                ksort($tables, SORT_NATURAL | SORT_FLAG_CASE);
                $databases[$database] = $tables;
            }

            $schema[$instance] = $databases;
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {
            return false;
        }

        return GBDBStorage::atomicWrite($file, $json . "\n");
    }


    /**
     * Setzt eine Tabelle im Schema.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return void Rückgabewert.
     */
    private static function setSchemaTable(string $database, string $table, array $cols): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($instance === "" || $database === "" || $table === "") {
            return;
        }

        $schema = self::readSchema();

        if (!isset($schema[$instance]) || !is_array($schema[$instance])) {
            $schema[$instance] = [];
        }

        if (!isset($schema[$instance][$database]) || !is_array($schema[$instance][$database])) {
            $schema[$instance][$database] = [];
        }

        $tableSchema = self::normalizeSchemaTableEntry($schema[$instance][$database][$table] ?? []);

        foreach ($cols as $col => $default) {
            if (is_int($col)) {
                $col = (string)$default;
                $default = "";
            }

            $col = trim((string)$col);

            if ($col === "" || $col === "id") {
                continue;
            }

            if (!array_key_exists($col, $tableSchema["columns"])) {
                $tableSchema["columns"][$col] = [
                    "type" => "mixed",
                    "default" => $default,
                    "nullable" => true,
                    "required" => false,
                    "unique" => false
                ];
            }
        }

        $tableSchema["columns"] = self::normalizeSchemaColumns($tableSchema["columns"]);
        $schema[$instance][$database][$table] = $tableSchema;
        self::writeSchema($schema);
    }


    /**
     * Entfernt eine Tabelle aus dem Schema.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaTable(string $database, string $table): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($instance === "" || $database === "" || $table === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance][$database][$table])) {
            unset($schema[$instance][$database][$table]);
        }

        if (isset($schema[$instance][$database]) && empty($schema[$instance][$database])) {
            unset($schema[$instance][$database]);
        }

        if (isset($schema[$instance]) && empty($schema[$instance])) {
            unset($schema[$instance]);
        }

        self::writeSchema($schema);
    }


    /**
     * Entfernt eine Datenbank aus dem Schema.
     * @param string $database Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaDatabase(string $database): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);

        if ($instance === "" || $database === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance][$database])) {
            unset($schema[$instance][$database]);
        }

        if (isset($schema[$instance]) && empty($schema[$instance])) {
            unset($schema[$instance]);
        }

        self::writeSchema($schema);
    }


    /**
     * Entfernt eine Instanz aus dem Schema.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaInstance(string $instance): void {
        $instance = Format::cleanString($instance);

        if ($instance === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance])) {
            unset($schema[$instance]);
            self::writeSchema($schema);
        }
    }


    /**
     * Normalisiert einen Tabellen-Schema-Eintrag auf Schema 2.0.
     * @param mixed $entry Schema-Eintrag.
     * @return array Normalisiertes Schema.
     */
    private static function normalizeSchemaTableEntry(mixed $entry): array {
        $now = time();

        if (!is_array($entry)) {
            $entry = [];
        }

        if (($entry["format"] ?? "") === "2.0" && isset($entry["columns"]) && is_array($entry["columns"])) {
            $entry["version"] = max(1, (int)($entry["version"] ?? 1));
            $entry["types_enabled"] = (bool)($entry["types_enabled"] ?? false);
            $entry["columns"] = self::normalizeSchemaColumns($entry["columns"]);
            $entry["constraints"] = is_array($entry["constraints"] ?? null) ? $entry["constraints"] : [];
            $entry["relations"] = is_array($entry["relations"] ?? null) ? $entry["relations"] : [];
            $entry["partitioning"] = is_array($entry["partitioning"] ?? null) ? $entry["partitioning"] : [];
            $entry["sequences"] = is_array($entry["sequences"] ?? null) ? $entry["sequences"] : [];
            $entry["migration_history"] = is_array($entry["migration_history"] ?? null) ? $entry["migration_history"] : [];
            $entry["updated_at"] = (int)($entry["updated_at"] ?? $now);
            $entry["created_at"] = (int)($entry["created_at"] ?? $now);
            return $entry;
        }

        $columns = [];
        foreach ($entry as $col => $default) {
            if (is_int($col)) {
                $col = (string)$default;
                $default = "";
            }

            $col = trim((string)$col);
            if ($col === "" || $col === "id") continue;
            $columns[$col] = [
                "type" => "mixed",
                "default" => $default,
                "nullable" => true,
                "required" => false,
                "unique" => false
            ];
        }

        return [
            "format" => "2.0",
            "version" => 1,
            "types_enabled" => false,
            "columns" => self::normalizeSchemaColumns($columns),
            "constraints" => [],
            "relations" => [],
            "partitioning" => [],
            "sequences" => [],
            "migration_history" => [],
            "created_at" => $now,
            "updated_at" => $now
        ];
    }

    /**
     * Normalisiert Schema-Spalten.
     * @param array $columns Spalten.
     * @return array Normalisierte Spalten.
     */
    private static function normalizeSchemaColumns(array $columns): array {
        $out = [];

        foreach ($columns as $col => $def) {
            if (is_int($col)) {
                $col = (string)$def;
                $def = [];
            }

            $col = trim((string)$col);
            if ($col === "" || $col === "id") continue;

            if (!is_array($def)) {
                $def = ["default" => $def];
            }

            $type = strtolower(trim((string)($def["type"] ?? "mixed")));
            if (!in_array($type, self::schemaTypes(), true)) $type = "mixed";

            $out[$col] = [
                "type" => $type,
                "default" => $def["default"] ?? null,
                "nullable" => (bool)($def["nullable"] ?? true),
                "not_null" => (bool)($def["not_null"] ?? false),
                "required" => (bool)($def["required"] ?? false),
                "unique" => (bool)($def["unique"] ?? false),
                "auto_increment" => (bool)($def["auto_increment"] ?? false),
                "sequence" => (string)($def["sequence"] ?? ""),
                "generated" => $def["generated"] ?? null,
                "id_generator" => (string)($def["id_generator"] ?? ""),
                "enum" => is_array($def["enum"] ?? null) ? array_values($def["enum"]) : [],
                "check" => is_array($def["check"] ?? null) ? $def["check"] : []
            ];
        }

        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    /**
     * Unterstützte Schema-Datentypen.
     * @return array Typen.
     */
    public static function schemaTypes(): array {
        return ["mixed", "string", "text", "email", "url", "int", "float", "decimal", "bool", "datetime", "date", "time", "timestamp", "json", "array", "enum", "uuid", "ulid", "blob_reference"];
    }

    /**
     * Holt das Schema einer Tabelle im Schema-2.0-Format.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Schema.
     */
    public static function schemaTable(string $database, string $table): array {
        $schema = self::readSchema();
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);
        return self::normalizeSchemaTableEntry($schema[$instance][$database][$table] ?? []);
    }

    /**
     * Schreibt das Schema einer Tabelle.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param array $tableSchema Tabellen-Schema.
     * @return bool true bei Erfolg.
     */
    private static function writeSchemaTable(string $database, string $table, array $tableSchema): bool {
        $schema = self::readSchema();
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if (!isset($schema[$instance]) || !is_array($schema[$instance])) $schema[$instance] = [];
        if (!isset($schema[$instance][$database]) || !is_array($schema[$instance][$database])) $schema[$instance][$database] = [];

        $tableSchema = self::normalizeSchemaTableEntry($tableSchema);
        $tableSchema["updated_at"] = time();
        $schema[$instance][$database][$table] = $tableSchema;

        return self::writeSchema($schema);
    }

    /**
     * Aktiviert oder deaktiviert Datentyp-Prüfung pro Tabelle.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param bool $enabled Status.
     * @return bool true bei Erfolg.
     */
    public static function enableSchemaTypes(string $database, string $table, bool $enabled = true): bool {
        $schema = self::schemaTable($database, $table);
        $schema["types_enabled"] = $enabled;
        $schema["version"] = (int)$schema["version"] + 1;
        self::addMigrationHistory($schema, "types", $enabled ? "enable" : "disable", []);
        return self::writeSchemaTable($database, $table, $schema);
    }

    /**
     * Setzt Typ/Optionen einer Spalte im Schema 2.0.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $type Datentyp.
     * @param array $options Zusätzliche Optionen.
     * @return bool true bei Erfolg.
     */
    public static function setColumnType(string $database, string $table, string $column, string $type, array $options = []): bool {
        $column = trim($column);
        $type = strtolower(trim($type));
        if ($column === "" || $column === "id" || !in_array($type, self::schemaTypes(), true)) return false;

        $schema = self::schemaTable($database, $table);
        if (!isset($schema["columns"][$column])) $schema["columns"][$column] = [];
        $current = is_array($schema["columns"][$column]) ? $schema["columns"][$column] : [];
        $current["type"] = $type;
        foreach ($options as $key => $value) $current[$key] = $value;
        $schema["columns"][$column] = $current;
        $schema["columns"] = self::normalizeSchemaColumns($schema["columns"]);
        $schema["types_enabled"] = (bool)($options["types_enabled"] ?? $schema["types_enabled"] ?? true);
        $schema["version"] = (int)$schema["version"] + 1;
        self::addMigrationHistory($schema, "column_type", "set", ["column" => $column, "type" => $type, "options" => $options]);
        return self::writeSchemaTable($database, $table, $schema);
    }

    /**
     * Setzt einen Default-Wert für eine Spalte.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param mixed $default Default-Wert.
     * @return bool true bei Erfolg.
     */
    public static function setColumnDefault(string $database, string $table, string $column, mixed $default): bool {
        $schema = self::schemaTable($database, $table);
        if (!isset($schema["columns"][$column])) return false;
        $schema["columns"][$column]["default"] = $default;
        $schema["version"] = (int)$schema["version"] + 1;
        self::addMigrationHistory($schema, "default", "set", ["column" => $column, "default" => $default]);
        return self::writeSchemaTable($database, $table, $schema);
    }

    /**
     * Setzt ein Constraint direkt im Schema und spiegelt es in die Tabellen-Meta.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $constraint Constraint.
     * @param mixed $value Wert.
     * @return bool true bei Erfolg.
     */
    public static function setSchemaConstraint(string $database, string $table, string $column, string $constraint, mixed $value = true): bool {
        $column = trim($column);
        $constraint = strtolower(trim($constraint));
        if ($column === "" || $column === "id") return false;
        if (!in_array($constraint, ["not_null", "required", "unique", "auto_increment", "sequence", "generated", "check"], true)) return false;

        $schema = self::schemaTable($database, $table);
        if (!isset($schema["columns"][$column])) $schema["columns"][$column] = ["type" => "mixed"];

        if ($constraint === "not_null") $schema["columns"][$column]["not_null"] = (bool)$value;
        elseif ($constraint === "required") $schema["columns"][$column]["required"] = (bool)$value;
        elseif ($constraint === "unique") $schema["columns"][$column]["unique"] = (bool)$value;
        elseif ($constraint === "auto_increment") $schema["columns"][$column]["auto_increment"] = (bool)$value;
        elseif ($constraint === "sequence") $schema["columns"][$column]["sequence"] = (string)$value;
        elseif ($constraint === "generated") $schema["columns"][$column]["generated"] = $value;
        elseif ($constraint === "check") $schema["columns"][$column]["check"] = is_array($value) ? $value : [];

        $schema["columns"] = self::normalizeSchemaColumns($schema["columns"]);
        $schema["version"] = (int)$schema["version"] + 1;
        self::addMigrationHistory($schema, "constraint", "set", ["column" => $column, "constraint" => $constraint, "value" => $value]);
        $ok = self::writeSchemaTable($database, $table, $schema);
        if ($ok) self::syncSchemaConstraintsToMeta($database, $table, $schema);
        return $ok;
    }


    /**
     * Erstellt oder aktualisiert eine Sequence im Tabellen-Schema.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $name Sequence-Name.
     * @param int $start Startwert.
     * @param int $step Schrittweite.
     * @return bool true bei Erfolg.
     */
    public static function createSequence(string $database, string $table, string $name, int $start = 0, int $step = 1): bool {
        $name = Format::cleanString($name);
        if ($name === "") return false;
        $schema = self::schemaTable($database, $table);
        if (!isset($schema["sequences"]) || !is_array($schema["sequences"])) $schema["sequences"] = [];
        $schema["sequences"][$name] = ["current" => $start, "step" => max(1, $step), "created_at" => time()];
        $schema["version"] = (int)$schema["version"] + 1;
        self::addMigrationHistory($schema, "sequence", "create", ["name" => $name, "start" => $start, "step" => $step]);
        return self::writeSchemaTable($database, $table, $schema);
    }

    /**
     * Holt den nächsten Wert einer Sequence und speichert ihn sofort.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $name Sequence-Name.
     * @return int Nächster Wert.
     */
    public static function nextSequence(string $database, string $table, string $name): int {
        $name = Format::cleanString($name);
        if ($name === "") return 0;
        $schema = self::schemaTable($database, $table);
        $next = self::nextSchemaSequence($schema, $name);
        self::writeSchemaTable($database, $table, $schema);
        return $next;
    }

    /**
     * Legt eine Migration im Schema ab.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $name Name.
     * @param array $operations Operationen.
     * @return bool true bei Erfolg.
     */
    public static function defineMigration(string $database, string $table, string $name, array $operations): bool {
        $schema = self::schemaTable($database, $table);
        if (!isset($schema["migrations"]) || !is_array($schema["migrations"])) $schema["migrations"] = [];
        $schema["migrations"][$name] = ["name" => $name, "operations" => $operations, "created_at" => time(), "applied" => false];
        return self::writeSchemaTable($database, $table, $schema);
    }

    /**
     * Führt eine definierte Schema-Migration aus.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $name Name.
     * @return array Bericht.
     */
    public static function runMigration(string $database, string $table, string $name): array {
        $schema = self::schemaTable($database, $table);
        $migration = $schema["migrations"][$name] ?? null;
        if (!is_array($migration)) return ["ok" => false, "error" => "migration_not_found"];
        if (($migration["applied"] ?? false) === true) return ["ok" => true, "already_applied" => true];

        $backup = $schema;
        foreach (($migration["operations"] ?? []) as $op) {
            if (!is_array($op)) continue;
            $type = (string)($op["type"] ?? "");
            $col = (string)($op["column"] ?? "");
            if ($type === "add_column" && $col !== "") {
                $schema["columns"][$col] = $op["definition"] ?? ["type" => "mixed", "default" => null];
                self::addColumn($database, $table, $col, $schema["columns"][$col]["default"] ?? null);
            }
            if ($type === "set_type" && $col !== "") {
                $schema["columns"][$col]["type"] = (string)($op["type_name"] ?? "mixed");
            }
            if ($type === "set_constraint" && $col !== "") {
                $schema["columns"][$col][(string)($op["constraint"] ?? "required")] = $op["value"] ?? true;
            }
        }

        $schema["columns"] = self::normalizeSchemaColumns($schema["columns"] ?? []);
        $schema["version"] = (int)$schema["version"] + 1;
        $schema["migrations"][$name]["applied"] = true;
        $schema["migrations"][$name]["applied_at"] = time();
        self::addMigrationHistory($schema, "migration", "run", ["name" => $name, "before" => $backup]);
        self::writeSchemaTable($database, $table, $schema);
        self::syncSchemaConstraintsToMeta($database, $table, $schema);
        return ["ok" => true, "migration" => $name, "version" => $schema["version"]];
    }

    /**
     * Rollt die letzte Migration zurück, soweit der vorherige Schema-Snapshot vorhanden ist.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $name Migration.
     * @return array Bericht.
     */
    public static function rollbackMigration(string $database, string $table, string $name): array {
        $schema = self::schemaTable($database, $table);
        $history = array_reverse($schema["migration_history"] ?? []);
        foreach ($history as $entry) {
            if (($entry["area"] ?? "") === "migration" && ($entry["payload"]["name"] ?? "") === $name && isset($entry["payload"]["before"])) {
                $before = $entry["payload"]["before"];
                if (is_array($before)) {
                    self::addMigrationHistory($before, "migration", "rollback", ["name" => $name]);
                    self::writeSchemaTable($database, $table, $before);
                    self::syncSchemaConstraintsToMeta($database, $table, $before);
                    return ["ok" => true, "migration" => $name, "rolled_back" => true];
                }
            }
        }
        return ["ok" => false, "error" => "rollback_snapshot_not_found"];
    }

    /**
     * Prüft das Schema gegen Tabellen-Header, Typen und Constraints.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Prüfbericht.
     */
    public static function checkSchema(string $database, string $table): array {
        $schema = self::schemaTable($database, $table);
        $file = self::makePath($database, $table);
        $errors = [];
        if (!is_file($file)) return ["ok" => false, "errors" => ["table_not_found"]];
        $base = self::ini($file);
        $full = self::applyOps($base, self::readAppendOps(self::appendFileForTable($database, $table)));
        $header = $full[0] ?? [];
        foreach ($schema["columns"] as $col => $def) {
            if (!is_array($header) || !array_key_exists($col, $header)) $errors[] = "missing_column:" . $col;
        }
        foreach ($full as $i => $row) {
            if (!is_array($row)) continue;
            if ($i === 0 && self::isHeaderRow($row)) continue;
            $prepared = self::prepareSchemaRow($database, $table, $row, $full, (int)($row["id"] ?? 0), false);
            if (!($prepared["ok"] ?? false)) $errors[] = "row:" . (string)($row["id"] ?? "?") . ":" . implode(",", $prepared["errors"] ?? []);
        }
        return ["ok" => empty($errors), "errors" => $errors, "schema_version" => $schema["version"]];
    }

    /**
     * Repariert fehlende Schema-Spalten im Tabellen-Header.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Bericht.
     */
    public static function repairSchema(string $database, string $table): array {
        $schema = self::schemaTable($database, $table);
        $added = [];
        foreach ($schema["columns"] as $col => $def) {
            $keys = self::getKeys($database, $table);
            if (!in_array($col, $keys, true)) {
                if (self::addColumn($database, $table, $col, $def["default"] ?? null)) $added[] = $col;
            }
        }
        self::syncSchemaConstraintsToMeta($database, $table, $schema);
        return ["ok" => true, "added_columns" => $added, "check" => self::checkSchema($database, $table)];
    }


    /**
     * Vergleicht Werte typgerecht anhand des Schema-2.0-Datentyps.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param mixed $left Linker Wert.
     * @param string $op Operator.
     * @param mixed $right Rechter Wert.
     * @return bool Ergebnis.
     */
    public static function typedCompare(string $database, string $table, string $column, mixed $left, string $op, mixed $right): bool {
        $schema = self::schemaTable($database, $table);
        $def = $schema["columns"][$column] ?? ["type" => "mixed"];
        $type = (string)($def["type"] ?? "mixed");
        $lc = self::castSchemaValue($left, $type, $def);
        $rc = self::castSchemaValue($right, $type, $def);
        if ($lc["ok"] ?? false) $left = $lc["value"];
        if ($rc["ok"] ?? false) $right = $rc["value"];
        $cmp = self::typedCompareValue($left, $right, $type);

        return match ($op) {
            "=", "==" => $cmp === 0,
            "!=" => $cmp !== 0,
            ">" => $cmp > 0,
            "<" => $cmp < 0,
            ">=" => $cmp >= 0,
            "<=" => $cmp <= 0,
            "~=" => mb_stripos((string)$left, (string)$right) !== false,
            default => false
        };
    }

    /**
     * Sortiert Zeilen typgerecht anhand des Schema-2.0-Datentyps.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param array $rows Zeilen per Referenz.
     * @param string $column Spalte.
     * @param string $dir Richtung.
     * @return void
     */
    public static function typedSortRows(string $database, string $table, array &$rows, string $column, string $dir = "ASC"): void {
        $schema = self::schemaTable($database, $table);
        $def = $schema["columns"][$column] ?? ["type" => "mixed"];
        $type = (string)($def["type"] ?? "mixed");
        usort($rows, function ($a, $b) use ($column, $dir, $type, $def) {
            $av = is_array($a) ? ($a[$column] ?? null) : null;
            $bv = is_array($b) ? ($b[$column] ?? null) : null;
            $ac = self::castSchemaValue($av, $type, $def);
            $bc = self::castSchemaValue($bv, $type, $def);
            if ($ac["ok"] ?? false) $av = $ac["value"];
            if ($bc["ok"] ?? false) $bv = $bc["value"];
            $cmp = self::typedCompareValue($av, $bv, $type);
            return strtoupper($dir) === "DESC" ? -$cmp : $cmp;
        });
    }

    /**
     * Vergleicht zwei Werte ohne Operator.
     * @param mixed $a Wert A.
     * @param mixed $b Wert B.
     * @param string $type Typ.
     * @return int Vergleich.
     */
    private static function typedCompareValue(mixed $a, mixed $b, string $type = "mixed"): int {
        if ($a === null && $b === null) return 0;
        if ($a === null) return -1;
        if ($b === null) return 1;
        if (in_array($type, ["int", "float", "decimal", "timestamp"], true) || (is_numeric($a) && is_numeric($b))) return ((float)$a) <=> ((float)$b);
        if (in_array($type, ["datetime", "date", "time"], true)) return strtotime((string)$a) <=> strtotime((string)$b);
        if ($type === "bool") return ((bool)$a) <=> ((bool)$b);
        return strnatcasecmp((string)$a, (string)$b);
    }

    /**
     * Fügt einen Migration-History-Eintrag hinzu.
     * @param array $schema Schema per Referenz.
     * @param string $area Bereich.
     * @param string $action Aktion.
     * @param array $payload Nutzdaten.
     * @return void
     */
    private static function addMigrationHistory(array &$schema, string $area, string $action, array $payload): void {
        if (!isset($schema["migration_history"]) || !is_array($schema["migration_history"])) $schema["migration_history"] = [];
        $schema["migration_history"][] = [
            "id" => "mig_" . bin2hex(random_bytes(8)),
            "area" => $area,
            "action" => $action,
            "payload" => $payload,
            "created_at" => time()
        ];
    }

    /**
     * Gibt die Migration-History einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array History.
     */
    public static function migrationHistory(string $database, string $table): array {
        $schema = self::schemaTable($database, $table);
        return $schema["migration_history"] ?? [];
    }

    /**
     * Spiegelt Schema-Constraints in die Meta-Datei.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param array|null $schema Schema.
     * @return bool true bei Erfolg.
     */
    private static function syncSchemaConstraintsToMeta(string $database, string $table, ?array $schema = null): bool {
        $schema = $schema ?? self::schemaTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table, true);
        $meta = self::readMeta($metaFile);
        $constraints = $meta["constraints"] ?? [];

        foreach (($schema["columns"] ?? []) as $col => $def) {
            if (!isset($constraints[$col]) || !is_array($constraints[$col])) $constraints[$col] = [];
            if (($def["required"] ?? false) || ($def["not_null"] ?? false)) $constraints[$col]["required"] = true;
            if (($def["unique"] ?? false) === true) $constraints[$col]["unique"] = true;
            if (empty($constraints[$col])) unset($constraints[$col]);
        }

        $meta["constraints"] = $constraints;
        $meta["schema_version"] = (int)($schema["version"] ?? 1);
        return self::writeMeta($metaFile, $meta);
    }

    /**
     * Bereitet eine Zeile anhand von Schema 2.0 vor: Defaults, Generated Values, Casting, Checks.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param array $row Zeile.
     * @param array $rows vorhandene Zeilen.
     * @param int|null $excludeId auszuschließende ID.
     * @param bool $writeGenerated ob generierte Werte gesetzt werden sollen.
     * @return array Ergebnis.
     */
    private static function prepareSchemaRow(string $database, string $table, array $row, array $rows = [], ?int $excludeId = null, bool $writeGenerated = true): array {
        $schema = self::schemaTable($database, $table);
        $errors = [];
        $typesEnabled = (bool)($schema["types_enabled"] ?? false);

        foreach (($schema["columns"] ?? []) as $col => $def) {
            $exists = array_key_exists($col, $row);
            $value = $exists ? $row[$col] : null;

            if ($writeGenerated && (!$exists || $value === "" || $value === null || $value === "-header-")) {
                if (($def["auto_increment"] ?? false) === true) $value = self::nextSchemaSequence($schema, $col);
                elseif ((string)($def["sequence"] ?? "") !== "") $value = self::nextSchemaSequence($schema, (string)$def["sequence"]);
                elseif (($def["generated"] ?? null) === "uuid" || ($def["id_generator"] ?? "") === "uuid") $value = self::uuidValue();
                elseif (($def["generated"] ?? null) === "ulid" || ($def["id_generator"] ?? "") === "ulid") $value = self::ulidValue();
                elseif (($def["generated"] ?? null) === "snowflake" || ($def["id_generator"] ?? "") === "snowflake") $value = self::snowflakeId();
                elseif (($def["generated"] ?? null) === "distributed" || ($def["id_generator"] ?? "") === "distributed") $value = self::distributedId();
                elseif (array_key_exists("default", $def)) $value = self::schemaDefaultValue($def["default"]);
            }

            if (($def["required"] ?? false) === true && (!$exists || $value === "" || $value === null || $value === "-header-")) {
                $errors[] = "required:" . $col;
                continue;
            }

            if (($def["not_null"] ?? false) === true && ($value === null || $value === "-header-")) {
                $errors[] = "not_null:" . $col;
                continue;
            }

            if ($value !== null && $value !== "-header-" && ($typesEnabled || ($def["type"] ?? "mixed") !== "mixed")) {
                $cast = self::castSchemaValue($value, (string)($def["type"] ?? "mixed"), $def);
                if (!$cast["ok"]) {
                    $errors[] = "type:" . $col . ":" . (string)($def["type"] ?? "mixed");
                    continue;
                }
                $value = $cast["value"];
            }

            if (!self::checkSchemaValue($value, $def)) {
                $errors[] = "check:" . $col;
                continue;
            }

            $row[$col] = $value;
        }

        $schema["columns"] = self::normalizeSchemaColumns($schema["columns"] ?? []);
        self::writeSchemaTable($database, $table, $schema);
        self::syncSchemaConstraintsToMeta($database, $table, $schema);

        $constraints = [];
        foreach (($schema["columns"] ?? []) as $col => $def) {
            if (($def["required"] ?? false) || ($def["not_null"] ?? false)) $constraints[$col]["required"] = true;
            if (($def["unique"] ?? false) === true) $constraints[$col]["unique"] = true;
        }
        if (!GBDBStorage::validateConstraints($rows, $row, $constraints, $excludeId)) $errors[] = "constraint";

        if (method_exists(static::class, "validateForeignKeysForRow")) {
            $fk = self::validateForeignKeysForRow($database, $table, $row);
            if (!($fk["ok"] ?? false)) $errors = array_merge($errors, $fk["errors"] ?? []);
        }

        return ["ok" => empty($errors), "row" => $row, "errors" => $errors];
    }

    /**
     * Liefert den nächsten Schema-Sequence-Wert.
     * @param array $schema Schema per Referenz.
     * @param string $name Sequence.
     * @return int Wert.
     */
    private static function nextSchemaSequence(array &$schema, string $name): int {
        if (!isset($schema["sequences"]) || !is_array($schema["sequences"])) $schema["sequences"] = [];
        if (!isset($schema["sequences"][$name]) || !is_array($schema["sequences"][$name])) $schema["sequences"][$name] = ["current" => 0, "step" => 1];
        $current = (int)($schema["sequences"][$name]["current"] ?? 0) + max(1, (int)($schema["sequences"][$name]["step"] ?? 1));
        $schema["sequences"][$name]["current"] = $current;
        return $current;
    }

    /**
     * Gibt Default-Werte dynamisch zurück.
     * @param mixed $default Default.
     * @return mixed Wert.
     */
    private static function schemaDefaultValue(mixed $default): mixed {
        if (is_string($default)) {
            $up = strtoupper($default);
            if ($up === "NOW" || $up === "CURRENT_TIMESTAMP") return date("Y-m-d H:i:s");
            if ($up === "CURRENT_DATE") return date("Y-m-d");
            if ($up === "CURRENT_TIME") return date("H:i:s");
            if ($up === "UUID") return self::uuidValue();
            if ($up === "ULID") return self::ulidValue();
        }
        return $default;
    }

    /**
     * Castet Schema-Werte.
     * @param mixed $value Wert.
     * @param string $type Typ.
     * @param array $def Definition.
     * @return array Ergebnis.
     */
    private static function castSchemaValue(mixed $value, string $type, array $def = []): array {
        $type = strtolower($type);
        if ($type === "mixed") return ["ok" => true, "value" => $value];
        if ($type === "string" || $type === "text" || $type === "email" || $type === "url" || $type === "blob_reference") $value = (string)$value;
        elseif ($type === "int") { if (!is_numeric($value)) return ["ok" => false]; $value = (int)$value; }
        elseif ($type === "float" || $type === "decimal") { if (!is_numeric($value)) return ["ok" => false]; $value = (float)$value; }
        elseif ($type === "bool") $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        elseif ($type === "timestamp") { if (!is_numeric($value)) return ["ok" => false]; $value = (int)$value; }
        elseif (in_array($type, ["datetime", "date", "time"], true)) {
            $ts = is_numeric($value) ? (int)$value : strtotime((string)$value);
            if ($ts === false) return ["ok" => false];
            $value = $type === "date" ? date("Y-m-d", $ts) : ($type === "time" ? date("H:i:s", $ts) : date("Y-m-d H:i:s", $ts));
        } elseif ($type === "json") {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) return ["ok" => false];
                $value = $decoded;
            }
        } elseif ($type === "array") {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $value = $decoded;
                else $value = array_map("trim", explode(",", $value));
            }
            if (!is_array($value)) return ["ok" => false];
        } elseif ($type === "enum") {
            $allowed = $def["enum"] ?? [];
            if (!empty($allowed) && !in_array($value, $allowed, true)) return ["ok" => false];
        } elseif ($type === "uuid") {
            $value = (string)$value;
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) return ["ok" => false];
        } elseif ($type === "ulid") {
            $value = strtoupper((string)$value);
            if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) return ["ok" => false];
        }

        if ($type === "email" && !filter_var($value, FILTER_VALIDATE_EMAIL)) return ["ok" => false];
        if ($type === "url" && !filter_var($value, FILTER_VALIDATE_URL)) return ["ok" => false];
        if ($type === "bool" && $value === null) return ["ok" => false];
        return ["ok" => true, "value" => $value];
    }

    /**
     * Prüft Schema-Checks wie min/max/regex/values.
     * @param mixed $value Wert.
     * @param array $def Definition.
     * @return bool true wenn gültig.
     */
    private static function checkSchemaValue(mixed $value, array $def): bool {
        if ($value === null) return true;
        $check = is_array($def["check"] ?? null) ? $def["check"] : [];
        if (isset($check["min"]) && is_numeric($value) && $value < $check["min"]) return false;
        if (isset($check["max"]) && is_numeric($value) && $value > $check["max"]) return false;
        if (isset($check["min_length"]) && is_string($value) && mb_strlen($value) < (int)$check["min_length"]) return false;
        if (isset($check["max_length"]) && is_string($value) && mb_strlen($value) > (int)$check["max_length"]) return false;
        if (isset($check["regex"]) && is_string($value) && @preg_match((string)$check["regex"], "") !== false && !preg_match((string)$check["regex"], $value)) return false;
        if (isset($check["values"]) && is_array($check["values"]) && !in_array($value, $check["values"], true)) return false;
        return true;
    }

    /** Erzeugt eine UUID v4. */
    private static function uuidValue(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** Erzeugt eine ULID-nahe ID. */
    private static function ulidValue(): string {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = (int)floor(microtime(true) * 1000);
        $out = '';
        for ($i = 9; $i >= 0; $i--) { $out = $chars[$time % 32] . $out; $time = intdiv($time, 32); }
        for ($i = 0; $i < 16; $i++) $out .= $chars[random_int(0, 31)];
        return $out;
    }


    /**
     * Komprimiert eine Tabelle automatisch.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function autoCompactTableInternal(string $database, string $table): void {
        $appendFile = self::appendFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $meta = self::readMeta($metaFile);

        if (GBDBStorage::shouldCompact($meta, $appendFile)) {
            self::compactTable($database, $table);
        }
    }
}
