<?php

trait GreenQL_RuntimeTrait {


    /**
     * Normalisiert GreenQL-Variablennamen inklusive Konstanten-Prefix.
     * @param string $name Variablenname.
     * @return string Bereinigter Name.
     */
    private static function cleanVarName(string $name): string {
        $name = trim($name);
        $prefix = str_starts_with($name, '$') ? '$' : '';
        $name = ltrim($name, '$');
        $name = self::cleanName($name);

        return $prefix . $name;
    }


    /**
     * Prüft, ob ein Variablenname GreenQL-konform ist.
     * @param string $name Variablenname.
     * @return bool Ergebnis.
     */
    private static function validVarName(string $name): bool {
        return $name !== '' && ($name[0] === '_' || $name[0] === '$');
    }


    /**
     * Setzt eine Variable oder Konstante sicher.
     * @param string $name Variablenname.
     * @param mixed $value Wert.
     * @param array $ctx Context.
     * @param array $vars Variablen.
     * @return array Ergebnis.
     */
    private static function setVar(string $name, mixed $value, array &$ctx, array &$vars): array {
        $name = self::cleanVarName($name);

        if (!self::validVarName($name)) {
            return ['ok' => false, 'message' => 'GreenQL-Variablen müssen mit _ beginnen. Konstanten beginnen mit $: ' . $name, 'ctx' => $ctx];
        }

        if (!isset($ctx['consts']) || !is_array($ctx['consts'])) {
            $ctx['consts'] = [];
        }

        if (isset($ctx['consts'][$name]) && array_key_exists($name, $vars)) {
            return ['ok' => false, 'message' => 'Konstante kann nicht überschrieben werden: ' . $name, 'ctx' => $ctx];
        }

        $vars[$name] = $value;

        if ($name[0] === '$') {
            $ctx['consts'][$name] = true;
        }

        return ['ok' => true, 'message' => 'Variable gesetzt: ' . $name, 'ctx' => $ctx, 'vars' => $vars, 'result' => $value];
    }


    /**
     * Konvertiert Funktionsargumente in ausgewertete Werte.
     * @param string $raw Argument-String.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return array Werte.
     */
    private static function evalArgs(string $raw, array $vars = [], array $params = []): array {
        $out = [];

        foreach (self::splitArguments($raw) as $arg) {
            $out[] = self::evaluateExpression($arg, $vars, $params);
        }

        return $out;
    }


    /**
     * Konvertiert Funktionsargumente mit vollständiger Runtime-Schachtellogik.
     * @param string $raw Argument-String.
     * @param array $ctx Context.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return array Werte.
     */
    private static function evalRuntimeArgs(string $raw, array &$ctx, array &$vars, array $params = []): array {
        $out = [];

        foreach (self::splitArguments($raw) as $arg) {
            $out[] = self::evalRuntimeExpression($arg, $ctx, $vars, $params);
        }

        return $out;
    }


    /**
     * Prüft, ob ein Ausdruck komplett von einer äußeren Klammer umschlossen ist.
     * @param string $value Ausdruck.
     * @return bool Ergebnis.
     */
    private static function runtimeWrappedByOuterParens(string $value): bool {
        $value = trim($value);

        if (strlen($value) < 2 || $value[0] !== '(' || substr($value, -1) !== ')') return false;

        $quote = '';
        $depth = 0;
        $len = strlen($value);

        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];

            if ($quote !== '') {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }

                if ($ch === $quote) $quote = '';

                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === '(') $depth++;
            if ($ch === ')') $depth--;
            if ($depth === 0 && $i < $len - 1) return false;
        }

        return $depth === 0;
    }


    /**
     * Parst Array-/Objekt-Literale mit vollständiger Runtime-Auswertung der Werte.
     * @param string $value Ausdruck.
     * @param array $ctx Context.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return mixed Wert oder null.
     */
    private static function parseRuntimeLiteral(string $value, array &$ctx, array &$vars, array $params = []): mixed {
        $value = trim($value);

        if ($value === '') return '';

        if ($value[0] === '[' && substr($value, -1) === ']') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') return [];

            $assoc = self::findTopLevel($inner, ':') >= 0;
            $out = [];

            foreach (self::splitNested($inner) as $part) {
                if ($assoc) {
                    $pos = self::findTopLevel($part, ':');

                    if ($pos < 0) continue;

                    $key = (string)self::unquote(trim(substr($part, 0, $pos)));
                    $out[$key] = self::evalRuntimeExpression(substr($part, $pos + 1), $ctx, $vars, $params);
                } else {
                    $out[] = self::evalRuntimeExpression($part, $ctx, $vars, $params);
                }
            }

            return $out;
        }

        if ($value[0] === '{' && substr($value, -1) === '}') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') return [];

            $parts = self::splitNested($inner);
            $allBracketObjects = !empty($parts);

            foreach ($parts as $part) {
                $part = trim($part);

                if (!($part !== '' && $part[0] === '[' && substr($part, -1) === ']')) {
                    $allBracketObjects = false;
                    break;
                }
            }

            if ($allBracketObjects) {
                $out = [];

                foreach ($parts as $part) {
                    $out[] = self::parseRuntimeLiteral($part, $ctx, $vars, $params);
                }

                return $out;
            }

            $out = [];

            foreach ($parts as $part) {
                $pos = self::findTopLevel($part, ':');

                if ($pos < 0) continue;

                $key = (string)self::unquote(trim(substr($part, 0, $pos)));
                $out[$key] = self::evalRuntimeExpression(substr($part, $pos + 1), $ctx, $vars, $params);
            }

            return $out;
        }

        return null;
    }


    /**
     * Ermittelt Instance/Base/Table aus Funktionsargumenten oder aktivem Context.
     * @param array $args Argumente.
     * @param array $ctx Context.
     * @return array Aufgelöste Zielwerte.
     */
    private static function resolveTargetArgs(array $args, array $ctx): array {
        $instance = self::cleanName((string)($ctx['instance'] ?? self::$instance));
        $base = self::cleanName((string)($ctx['db'] ?? ''));
        $table = self::cleanName((string)($ctx['table'] ?? ''));
        $filter = [];

        if (count($args) >= 4) {
            $instance = self::cleanName((string)$args[0]);
            $base = self::cleanName((string)$args[1]);
            $table = self::cleanName((string)$args[2]);
            $filter = is_array($args[3]) ? $args[3] : [];
        } elseif (count($args) >= 3) {
            $base = self::cleanName((string)$args[0]);
            $table = self::cleanName((string)$args[1]);
            $filter = is_array($args[2]) ? $args[2] : [];
        } elseif (count($args) >= 2) {
            $table = self::cleanName((string)$args[0]);
            $filter = is_array($args[1]) ? $args[1] : [];
        } elseif (count($args) === 1) {
            $table = self::cleanName((string)$args[0]);
        }

        return [$instance, $base, $table, $filter];
    }


    /**
     * Aktiviert temporär eine Instanz und gibt den vorherigen Zustand zurück.
     * @param string $instance Instanzname.
     * @param array $ctx Context.
     * @return array Vorheriger Zustand.
     */
    private static function pushInstance(string $instance, array &$ctx): array {
        $old = ['driver' => self::$driver, 'instance' => self::$instance, 'ctx_instance' => $ctx['instance'] ?? ''];

        if ($instance !== '' && class_exists('GBDB')) {
            self::useInstance($instance, $ctx);
        } else {
            self::$driver = 'GBDB';
            self::$instance = '';

            unset($ctx['instance']);
        }

        return $old;
    }


    /**
     * Stellt eine zuvor aktive Instanz wieder her.
     * @param array $old Zustand.
     * @param array $ctx Context.
     * @return void
     */
    private static function popInstance(array $old, array &$ctx): void {
        self::$driver = (string)($old['driver'] ?? 'GBDB');
        self::$instance = (string)($old['instance'] ?? '');

        if ((string)($old['ctx_instance'] ?? '') !== '' && class_exists('GBDB')) {
            self::useInstance((string)$old['ctx_instance'], $ctx);
        } else {
            unset($ctx['instance']);
            self::$driver = 'GBDB';
            self::$instance = '';
        }
    }


    /**
     * Filtert Zeilen über ein Objekt wie ["uid": _uid].
     * @param array $rows Zeilen.
     * @param array $filter Filter.
     * @return array Treffer.
     */
    private static function filterRowsByObject(array $rows, array $filter = []): array {
        if (empty($filter)) return array_values(array_filter($rows, 'is_array'));

        return array_values(array_filter($rows, function ($row) use ($filter) {
            if (!is_array($row)) return false;

            foreach ($filter as $key => $value) {
                if (($row[$key] ?? null) != $value) return false;
            }
            return true;
        }));
    }


    /**
     * Holt den ersten Key/Value aus einem Filterobjekt.
     * @param array $filter Filter.
     * @return array|null Key/Value oder null.
     */
    private static function firstFilterPair(array $filter): ?array {
        foreach ($filter as $key => $value) {
            return [(string)$key, $value];
        }

        return null;
    }


    /**
     * Prüft, ob ein Zieldatensatz readonly ist.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $filter Filter.
     * @return bool Ergebnis.
     */
    private static function rowIsReadonly(string $db, string $table, array $filter): bool {
        $rows = self::filterRowsByObject(self::getRows($db, $table), $filter);

        foreach ($rows as $row) {
            if (!empty($row['_readonly']) || !empty($row['readonly'])) return true;
        }

        return false;
    }


    /**
     * Löscht eine Spalte über eine Tabellen-Neuanlage.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @return bool Ergebnis.
     */
    private static function deleteColumnRuntime(string $db, string $table, string $column): bool {
        $driver = self::db();
        $column = self::cleanName($column);

        if ($db === '' || $table === '' || $column === '' || $column === 'id') return false;

        $keys = array_values(array_filter(self::getTableKeys($db, $table), fn($k) => (string)$k !== $column && (string)$k !== 'id'));

        if (empty($keys)) return false;

        $rows = self::getRows($db, $table);
        $tmp = '__gql_tmp_' . self::cleanName($table) . '_' . substr(hash('sha256', microtime(true) . random_int(1, PHP_INT_MAX)), 0, 8);

        if (!$driver::createTable($db, $tmp, $keys)) return false;

        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            unset($row[$column], $row['id']);
            $driver::insertData($db, $tmp, $row);
        }

        if (!$driver::deleteTable($db, $table)) {
            $driver::deleteTable($db, $tmp);
            return false;
        }

        if (!$driver::createTable($db, $table, $keys)) return false;

        $newRows = $driver::getData($db, $tmp);

        foreach ($newRows as $row) {
            if (!is_array($row)) continue;
            unset($row['id']);
            $driver::insertData($db, $table, $row);
        }

        $driver::deleteTable($db, $tmp);
        return true;
    }


    /**
     * Kopiert eine Tabelle in eine andere Tabelle.
     * @param string $fromBase Quellbase.
     * @param string $fromTable Quelltabelle.
     * @param string $toBase Zielbase.
     * @param string $toTable Zieltabelle.
     * @param bool $deleteSource Quelle löschen.
     * @return bool Ergebnis.
     */
    private static function copyTableRuntime(string $fromBase, string $fromTable, string $toBase, string $toTable, bool $deleteSource = false): bool {
        $driver = self::db();
        $fromBase = self::cleanName($fromBase);
        $fromTable = self::cleanName($fromTable);
        $toBase = self::cleanName($toBase);
        $toTable = self::cleanName($toTable);

        if ($fromBase === '' || $fromTable === '' || $toBase === '' || $toTable === '') return false;

        $keys = array_values(array_filter(self::getTableKeys($fromBase, $fromTable), fn($k) => (string)$k !== 'id'));

        if (empty($keys)) return false;

        $driver::createDatabase($toBase);

        if (!in_array($toTable, $driver::listTables($toBase), true)) {
            if (!$driver::createTable($toBase, $toTable, $keys)) return false;
        }

        foreach (self::getRows($fromBase, $fromTable) as $row) {
            if (!is_array($row)) continue;
            unset($row['id']);
            if ($driver::insertData($toBase, $toTable, $row) <= 0) return false;
        }

        if ($deleteSource) {
            return $driver::deleteTable($fromBase, $fromTable);
        }

        return true;
    }


    /**
     * Ruft eine externe JSON-API aus GreenQL heraus auf.
     * @param string $url Ziel-URL.
     * @param array $body Request-Body.
     * @param array $headers Request-Header.
     * @return mixed Antwort als Array oder Rohtext.
     */
    private static function fetchApiRuntime(string $url, array $body = [], array $headers = []): mixed {
        $url = trim($url);

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Ungültige API-URL.'
            ];
        }

        if (!class_exists('Http')) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Http-Klasse nicht verfügbar.'
            ];
        }

        $response = empty($body) ? Http::get($url, $headers, 15) : Http::post($url, $body, $headers, 15);

        if ($response === false || $response === '') {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Keine Antwort von API.',
                'raw' => $response
            ];
        }

        $json = json_decode((string)$response, true);

        if (is_array($json)) {
            return $json;
        }

        return (string)$response;
    }


    /**
     * Wertet GreenQL-Laufzeitfunktionen und EXISTS-Ausdrücke aus.
     * @param string $value Ausdruck.
     * @param array $ctx Context.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return mixed Wert.
     */
    private static function evalRuntimeExpression(string $value, array &$ctx, array &$vars, array $params = []): mixed {
        $value = trim($value);

        if ($value === '') return '';

        while (self::runtimeWrappedByOuterParens($value)) {
            $value = trim(substr($value, 1, -1));
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command('CLASS ' . $m[1] . '/' . $m[2] . '(' . $m[3] . ')', $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command('CALL ' . $m[1] . '(' . $m[2] . ')', $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (preg_match('/^CLASS\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command($value, $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command($value, $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        foreach (['||', 'OR'] as $op) {
            $pos = self::findTopLevel($value, $op);

            if ($pos >= 0) {
                $left = self::evalRuntimeExpression(substr($value, 0, $pos), $ctx, $vars, $params);
                if ((bool)$left) return true;
                return (bool)self::evalRuntimeExpression(substr($value, $pos + strlen($op)), $ctx, $vars, $params);
            }
        }

        foreach (['&&', 'AND'] as $op) {
            $pos = self::findTopLevel($value, $op);
            if ($pos >= 0) {
                $left = self::evalRuntimeExpression(substr($value, 0, $pos), $ctx, $vars, $params);
                if (!(bool)$left) return false;
                return (bool)self::evalRuntimeExpression(substr($value, $pos + strlen($op)), $ctx, $vars, $params);
            }
        }

        if (preg_match('/^!\s*(.+)$/s', $value, $m)) {
            return !((bool)self::evalRuntimeExpression((string)$m[1], $ctx, $vars, $params));
        }

        foreach (['==', '!=', '>=', '<=', '>', '<'] as $op) {
            $pos = self::findTopLevel($value, $op);

            if ($pos >= 0) {
                $left = self::evalRuntimeExpression(substr($value, 0, $pos), $ctx, $vars, $params);
                $right = self::evalRuntimeExpression(substr($value, $pos + strlen($op)), $ctx, $vars, $params);

                return match ($op) {
                    '==' => $left == $right,
                    '!=' => $left != $right,
                    '>=' => $left >= $right,
                    '<=' => $left <= $right,
                    '>' => $left > $right,
                    '<' => $left < $right,
                    default => false
                };
            }
        }

        foreach (['+', '-', '*', '/', '%'] as $op) {
            $pos = self::findMathOperator($value, $op);

            if ($pos >= 0) {
                $left = self::evalRuntimeExpression(substr($value, 0, $pos), $ctx, $vars, $params);
                $right = self::evalRuntimeExpression(substr($value, $pos + 1), $ctx, $vars, $params);

                if (!is_numeric($left) || !is_numeric($right)) {
                    if ($op === '+') return (string)$left . (string)$right;
                    return 0;
                }

                return match ($op) {
                    '+' => $left + $right,
                    '-' => $left - $right,
                    '*' => $left * $right,
                    '/' => (float)$right == 0.0 ? 0 : $left / $right,
                    '%' => (int)$right === 0 ? 0 : (int)$left % (int)$right,
                    default => 0
                };
            }
        }

        $literal = self::parseRuntimeLiteral($value, $ctx, $vars, $params);

        if ($literal !== null) {
            return $literal;
        }

        if (preg_match('/^EXISTS\s+(INSTANCE|BASE|TABLE|DATA)\s+(.+)$/is', $value, $m)) {
            return self::existsRuntime(strtoupper((string)$m[1]), trim((string)$m[2]), $ctx, $vars, $params);
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command('CLASS ' . $m[1] . '/' . $m[2] . '(' . $m[3] . ')', $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (preg_match('/^CLASS\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            $res = self::command($value, $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (preg_match('/^FILE\.RUN\s+(.+?)(?:\s+(\{.*\}|\[.*\]))?$/is', $value, $m)) {
            $file = self::resolveScriptPath((string)$m[1]);

            if ($file === '') {
                return null;
            }

            $runParams = isset($m[2]) ? self::parseParamObject((string)$m[2], $vars, $params) : [];
            $runCtx = $ctx;
            $res = self::run((string)file_get_contents($file), $runCtx, $runParams);
            $ctx = $runCtx;

            if (!empty($res['ok']) && array_key_exists('back', $res)) {
                return $res['back'];
            }

            return $res;
        }

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $value, $m)) {
            return self::evaluateExpression($value, $vars, $params);
        }

        $fn = strtolower((string)$m[1]);
        $args = self::evalRuntimeArgs((string)$m[2], $ctx, $vars, $params);

        if (isset($ctx['functions'][$fn]) && is_array($ctx['functions'][$fn])) {
            $res = self::command('CALL ' . $fn . '(' . (string)$m[2] . ')', $ctx, $vars, $params);
            if (!($res['ok'] ?? false)) return null;
            return $res['back'] ?? ($res['result'] ?? null);
        }

        if (in_array($fn, ['uni_random', 'spark_id', 'fresh_id'], true)) {
            return bin2hex(random_bytes(16)) . dechex((int)(microtime(true) * 1000000));
        }

        if (in_array($fn, ['fetch_api', 'api_fetch', 'call_api'], true)) {
            $url = (string)($args[0] ?? '');
            $body = is_array($args[1] ?? null) ? $args[1] : [];
            $headers = is_array($args[2] ?? null) ? $args[2] : [];

            return self::fetchApiRuntime($url, $body, $headers);
        }

        if (in_array($fn, ['get_instances', 'instances'], true)) {
            return class_exists('GBDB') ? GBDB::listInstances() : [];
        }

        if (in_array($fn, ['get_bases', 'bases'], true)) {
            $instance = isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $old = self::pushInstance($instance, $ctx);
            $out = self::db()::listDBs();

            self::popInstance($old, $ctx);
            return $out;
        }

        if (in_array($fn, ['get_tables', 'tables'], true)) {
            $instance = count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $base = count($args) >= 2 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $old = self::pushInstance($instance, $ctx);
            $out = $base !== '' ? self::db()::listTables($base) : [];

            self::popInstance($old, $ctx);
            return $out;
        }

        if (in_array($fn, ['get_data', 'fetch_data', 'fetch'], true)) {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $old = self::pushInstance($instance, $ctx);
            $rows = ($db !== '' && $table !== '') ? self::filterRowsByObject(self::getRows($db, $table), $filter) : [];

            self::popInstance($old, $ctx);
            return empty($filter) ? $rows : ($rows[0] ?? null);
        }

        if (in_array($fn, ['count_data', 'tally_data'], true)) {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $old = self::pushInstance($instance, $ctx);
            $out = ($db !== '' && $table !== '') ? count(self::filterRowsByObject(self::getRows($db, $table), $filter)) : 0;

            self::popInstance($old, $ctx);
            return $out;
        }

        if (in_array($fn, ['last_added', 'last_data'], true)) {
            [$instance, $db, $table] = self::resolveTargetArgs($args, $ctx);
            $old = self::pushInstance($instance, $ctx);
            $rows = ($db !== '' && $table !== '') ? self::getRows($db, $table) : [];

            self::popInstance($old, $ctx);
            return empty($rows) ? null : $rows[count($rows) - 1];
        }

        if (in_array($fn, ['add_data', 'plant_data', 'seed_data'], true)) {
            [$instance, $db, $table] = self::resolveTargetArgs($args, $ctx);
            $data = count($args) >= 4 ? $args[3] : (count($args) >= 3 ? $args[2] : []);
            $old = self::pushInstance($instance, $ctx);
            $id = (is_array($data) && $db !== '' && $table !== '') ? self::db()::insertData($db, $table, $data) : 0;

            self::popInstance($old, $ctx);
            return $id > 0;
        }

        if (in_array($fn, ['editdata', 'edit_data', 'reshape_data'], true)) {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $data = count($args) >= 5 ? $args[4] : (count($args) >= 4 ? $args[3] : []);
            $pair = self::firstFilterPair($filter);
            $old = self::pushInstance($instance, $ctx);
            $ok = $pair !== null && is_array($data) && !self::rowIsReadonly($db, $table, $filter) ? self::db()::editData($db, $table, $pair[0], $pair[1], $data) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['delete_data', 'erase_data', 'delete_data_recursive'], true)) {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $pair = self::firstFilterPair($filter);
            $old = self::pushInstance($instance, $ctx);
            $ok = $pair !== null && !self::rowIsReadonly($db, $table, $filter) ? self::db()::deleteData($db, $table, $pair[0], $pair[1]) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['new_column', 'sprout_column'], true)) {
            $instance = count($args) >= 5 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 5 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 5 ? self::cleanName((string)$args[2]) : (isset($args[1]) ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $column = count($args) >= 5 ? self::cleanName((string)$args[3]) : (isset($args[2]) ? self::cleanName((string)$args[2]) : '');
            $default = count($args) >= 5 ? $args[4] : ($args[3] ?? '');
            $old = self::pushInstance($instance, $ctx);
            $ok = $db !== '' && $table !== '' && $column !== '' ? self::db()::addColumn($db, $table, $column, $default) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['delete_column', 'prune_column'], true)) {
            $instance = count($args) >= 4 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 4 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 4 ? self::cleanName((string)$args[2]) : (isset($args[1]) ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $column = count($args) >= 4 ? self::cleanName((string)$args[3]) : (isset($args[2]) ? self::cleanName((string)$args[2]) : '');
            $old = self::pushInstance($instance, $ctx);
            $ok = self::deleteColumnRuntime($db, $table, $column);

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['delete_instance', 'drop_instance'], true)) {
            return class_exists('GBDB') && isset($args[0]) ? GBDB::deleteInstance(self::cleanName((string)$args[0]), true) : false;
        }

        if (in_array($fn, ['delete_base', 'drop_base'], true)) {
            $instance = count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 2 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $old = self::pushInstance($instance, $ctx);

            foreach (self::db()::listTables($db) as $tbl) self::db()::deleteTable($db, $tbl);

            $ok = $db !== '' ? self::db()::deleteDatabase($db) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['delete_table', 'drop_table'], true)) {
            $instance = count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 3 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 3 ? self::cleanName((string)$args[2]) : (isset($args[1]) ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $old = self::pushInstance($instance, $ctx);
            $ok = $db !== '' && $table !== '' ? self::db()::deleteTable($db, $table) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['rename_table'], true)) {
            $instance = count($args) >= 4 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 4 ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['db'] ?? ''));
            $oldName = count($args) >= 4 ? self::cleanName((string)$args[2]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : '');
            $newName = count($args) >= 4 ? self::cleanName((string)$args[3]) : (isset($args[1]) ? self::cleanName((string)$args[1]) : '');
            $old = self::pushInstance($instance, $ctx);
            $ok = self::copyTableRuntime($db, $oldName, $db, $newName, true);

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['rename_base'], true)) {
            $instance = count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $oldBase = count($args) >= 3 ? self::cleanName((string)$args[1]) : (isset($args[0]) ? self::cleanName((string)$args[0]) : '');
            $newBase = count($args) >= 3 ? self::cleanName((string)$args[2]) : (isset($args[1]) ? self::cleanName((string)$args[1]) : '');
            $old = self::pushInstance($instance, $ctx);
            $ok = $oldBase !== '' && $newBase !== '' && self::db()::createDatabase($newBase);

            if ($ok) {
                foreach (self::db()::listTables($oldBase) as $tbl) $ok = $ok && self::copyTableRuntime($oldBase, $tbl, $newBase, $tbl, false);

                if ($ok) foreach (self::db()::listTables($oldBase) as $tbl) self::db()::deleteTable($oldBase, $tbl);
                if ($ok) $ok = self::db()::deleteDatabase($oldBase);
            }

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['rename_instance'], true)) {
            if (!class_exists('GBDB') || count($args) < 2) return false;

            $oldInst = self::cleanName((string)$args[0]);
            $newInst = self::cleanName((string)$args[1]);

            if ($oldInst === '' || $newInst === '' || !GBDB::createInstance($newInst)) return false;

            $oldCurrent = GBDB::getInstance();

            GBDB::setInstance($oldInst);

            $bases = GBDB::listDBs();

            foreach ($bases as $base) {
                $tables = GBDB::listTables($base);

                GBDB::setInstance($newInst);
                GBDB::createDatabase($base);
                GBDB::setInstance($oldInst);

                foreach ($tables as $tbl) {
                    $keys = array_values(array_filter(GBDB::getKeys($base, $tbl), fn($k) => (string)$k !== 'id'));
                    $rows = GBDB::getData($base, $tbl);

                    GBDB::setInstance($newInst);
                    GBDB::createTable($base, $tbl, $keys);

                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            unset($row['id']);
                            GBDB::insertData($base, $tbl, $row);
                        }
                    }

                    GBDB::setInstance($oldInst);
                }
            }
            GBDB::setInstance($oldCurrent);
            return GBDB::deleteInstance($oldInst, true);
        }

        if (in_array($fn, ['transfer_data', 'copy_data', 'transfer_data_delete', 'move_data'], true)) {
            $from = is_array($args[0] ?? null) ? $args[0] : [];
            $to = is_array($args[1] ?? null) ? $args[1] : [];
            $delete = in_array($fn, ['transfer_data_delete', 'move_data'], true);
            $old = self::pushInstance(self::cleanName((string)($from['instance'] ?? $ctx['instance'] ?? '')), $ctx);
            $ok = self::copyTableRuntime((string)($from['base'] ?? ''), (string)($from['table'] ?? ''), (string)($to['base'] ?? ''), (string)($to['table'] ?? ''), $delete);

            self::popInstance($old, $ctx);
            return $ok;
        }

        if (in_array($fn, ['set_data_readonly', 'lock_data'], true)) {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $readonly = (bool)(count($args) >= 5 ? $args[4] : ($args[3] ?? true));
            $pair = self::firstFilterPair($filter);
            $old = self::pushInstance($instance, $ctx);

            if ($db !== '' && $table !== '' && !in_array('_readonly', self::getTableKeys($db, $table), true)) self::db()::addColumn($db, $table, '_readonly', 0);

            $ok = $pair !== null ? self::db()::editData($db, $table, $pair[0], $pair[1], ['_readonly' => $readonly ? 1 : 0]) : false;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if ($fn === 'instance_exists') {
            $name = self::cleanName((string)($args[0] ?? ''));
            return $name !== '' && class_exists('GBDB') && in_array($name, GBDB::listInstances(), true);
        }

        if ($fn === 'base_exists') {
            $instance = count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $base = count($args) >= 2 ? self::cleanName((string)$args[1]) : self::cleanName((string)($args[0] ?? $ctx['db'] ?? ''));
            $old = self::pushInstance($instance, $ctx);
            $ok = $base !== '' && in_array($base, self::db()::listDBs(), true);

            self::popInstance($old, $ctx);
            return $ok;
        }

        if ($fn === 'table_exists') {
            $instance = count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $base = count($args) >= 3 ? self::cleanName((string)$args[1]) : (count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 3 ? self::cleanName((string)$args[2]) : (count($args) >= 2 ? self::cleanName((string)$args[1]) : self::cleanName((string)($args[0] ?? $ctx['table'] ?? '')));
            $old = self::pushInstance($instance, $ctx);
            $ok = $base !== '' && $table !== '' && in_array($table, self::db()::listTables($base), true);

            self::popInstance($old, $ctx);
            return $ok;
        }

        if ($fn === 'data_exists') {
            [$instance, $db, $table, $filter] = self::resolveTargetArgs($args, $ctx);
            $old = self::pushInstance($instance, $ctx);
            $ok = $db !== '' && $table !== '' && count(self::filterRowsByObject(self::getRows($db, $table), is_array($filter) ? $filter : [])) > 0;

            self::popInstance($old, $ctx);
            return $ok;
        }

        if ($fn === 'monitor') {
            $instance = count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 3 ? self::cleanName((string)$args[1]) : (count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 3 ? self::cleanName((string)$args[2]) : (count($args) >= 2 ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $old = self::pushInstance($instance, $ctx);

            if ($db !== '' && $table !== '') {
                $out = method_exists(self::db(), 'monitor') ? self::db()::monitor($db, $table) : [];
                self::popInstance($old, $ctx);
                return $out;
            }

            $out = [];

            foreach (self::db()::listDBs() as $dbName) {
                foreach (self::db()::listTables($dbName) as $tableName) {
                    $out[] = method_exists(self::db(), 'monitor') ? self::db()::monitor($dbName, $tableName) : ['database' => $dbName, 'table' => $tableName];
                }
            }

            self::popInstance($old, $ctx);
            return $out;
        }

        if ($fn === 'recover') {
            $instance = count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 3 ? self::cleanName((string)$args[1]) : (count($args) >= 2 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 3 ? self::cleanName((string)$args[2]) : (count($args) >= 2 ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $old = self::pushInstance($instance, $ctx);
            $out = $db !== '' && $table !== '' && method_exists(self::db(), 'recoverTable') ? self::db()::recoverTable($db, $table) : ['ok' => false, 'error' => 'target_missing'];

            self::popInstance($old, $ctx);
            return $out;
        }

        if ($fn === 'page') {
            $instance = count($args) >= 5 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 5 ? self::cleanName((string)$args[1]) : (count($args) >= 4 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 5 ? self::cleanName((string)$args[2]) : (count($args) >= 4 ? self::cleanName((string)$args[1]) : self::cleanName((string)($args[0] ?? $ctx['table'] ?? '')));
            $page = (int)(count($args) >= 5 ? $args[3] : (count($args) >= 4 ? $args[2] : ($args[1] ?? 1)));
            $size = (int)(count($args) >= 5 ? $args[4] : (count($args) >= 4 ? $args[3] : ($args[2] ?? 50)));
            $old = self::pushInstance($instance, $ctx);
            $out = $db !== '' && $table !== '' && method_exists(self::db(), 'page') ? self::db()::page($db, $table, $page, $size) : ['ok' => false, 'rows' => []];

            self::popInstance($old, $ctx);
            return $out;
        }

        if ($fn === 'cursor') {
            $instance = count($args) >= 5 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 5 ? self::cleanName((string)$args[1]) : (count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 5 ? self::cleanName((string)$args[2]) : (count($args) >= 3 ? self::cleanName((string)$args[1]) : self::cleanName((string)($args[0] ?? $ctx['table'] ?? '')));
            $size = (int)(count($args) >= 5 ? $args[3] : (count($args) >= 3 ? $args[2] : ($args[1] ?? 100)));
            $cursor = count($args) >= 5 ? (string)($args[4] ?? '') : (string)($args[3] ?? '');
            $old = self::pushInstance($instance, $ctx);
            $out = $db !== '' && $table !== '' && method_exists(self::db(), 'cursor') ? self::db()::cursor($db, $table, $size, $cursor !== '' ? $cursor : null) : ['ok' => false, 'rows' => []];

            self::popInstance($old, $ctx);
            return $out;
        }

        if ($fn === 'fulltext_search') {
            $instance = count($args) >= 6 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['instance'] ?? ''));
            $db = count($args) >= 6 ? self::cleanName((string)$args[1]) : (count($args) >= 3 ? self::cleanName((string)$args[0]) : self::cleanName((string)($ctx['db'] ?? '')));
            $table = count($args) >= 6 ? self::cleanName((string)$args[2]) : (count($args) >= 3 ? self::cleanName((string)$args[1]) : self::cleanName((string)($ctx['table'] ?? '')));
            $query = count($args) >= 6 ? (string)$args[3] : (string)($args[2] ?? $args[0] ?? '');
            $columns = count($args) >= 6 ? (is_array($args[4] ?? null) ? $args[4] : []) : (is_array($args[3] ?? null) ? $args[3] : []);
            $limit = (int)(count($args) >= 6 ? $args[5] : ($args[4] ?? 50));
            $old = self::pushInstance($instance, $ctx);
            $out = $db !== '' && $table !== '' && method_exists(self::db(), 'fulltext_search') ? self::db()::fulltext_search($db, $table, $query, $columns, $limit) : [];

            self::popInstance($old, $ctx);
            return $out;
        }

        return self::evaluateExpression($value, $vars, $params);
    }

    /**
     * Prüft EXISTS-Ausdrücke.
     * @param string $type Typ.
     * @param string $raw Rohwert.
     * @param array $ctx Context.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return bool Ergebnis.
     */
    private static function existsRuntime(string $type, string $raw, array &$ctx, array &$vars, array $params = []): bool {
        $type = strtoupper($type);
        $raw = trim($raw);

        if ($type === 'INSTANCE') {
            $name = self::cleanName((string)self::evaluateValue($raw, $vars, $params));
            return class_exists('GBDB') && in_array($name, GBDB::listInstances(), true);
        }

        if ($type === 'BASE') {
            $name = self::cleanName((string)self::evaluateValue($raw, $vars, $params));
            return in_array($name, self::db()::listDBs(), true);
        }

        if ($type === 'TABLE') {
            $name = self::cleanName((string)self::evaluateValue($raw, $vars, $params));
            $db = self::cleanName((string)($ctx['db'] ?? ''));
            return $db !== '' && in_array($name, self::db()::listTables($db), true);
        }

        if ($type === 'DATA') {
            $filter = [];
            $table = self::cleanName((string)($ctx['table'] ?? ''));

            if (preg_match('/^(\[.*\]|\{.*\})\s+IN\s+([a-zA-Z0-9_\-]+)$/is', $raw, $m)) {
                $filter = self::parseParamObject((string)$m[1], $vars, $params);
                $table = self::resolveNameToken((string)$m[2], $vars);
            } else {
                $tmp = self::parseParamObject($raw, $vars, $params);
                $filter = is_array($tmp) ? $tmp : [];
            }

            $db = self::cleanName((string)($ctx['db'] ?? ''));
            return $db !== '' && $table !== '' && count(self::filterRowsByObject(self::getRows($db, $table), $filter)) > 0;
        }

        return false;
    }
}
