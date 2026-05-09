<?php

trait GreenQL_ParserTrait {

    /**
     * handles unquote.
     *
     * @param string $value value.
     *
     * @return mixed result.
     */
    public static function unquote(string $value): mixed {
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return stripcslashes(substr($value, 1, -1));
            }

        }

        $low = strtolower($value);

        if ($low === "true") return 1;

        if ($low === "false") return 0;

        if ($low === "null") return null;

        if (is_numeric($value)) return $value + 0;

        return $value;
    }

    /**
     * handles strip comments.
     *
     * @param string $script value.
     *
     * @return string result.
     */
    public static function stripComments(string $script): string {
        $lines = preg_split('/\r\n|\r|\n/', $script);
        $out = [];

        foreach ($lines as $line) {
            $clean = "";
            $quote = "";
            $len = strlen((string)$line);

            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];
                $next = $i + 1 < $len ? $line[$i + 1] : "";

                if ($quote !== "") {
                    if ($ch === "\\" && $i + 1 < $len) {
                        $clean .= $ch . $line[$i + 1];
                        $i++;
                        continue;
                    }

                    if ($ch === $quote) {
                        $quote = "";
                    }

                    $clean .= $ch;
                    continue;
                }

                if ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                    $clean .= $ch;
                    continue;
                }

                if ($ch === "#") {
                    break;
                }

                if ($ch === "-" && $next === "-") {
                    break;
                }

                if ($ch === "/" && $next === "/") {
                    break;
                }

                $clean .= $ch;
            }

            $out[] = rtrim($clean);
        }

        return trim(implode("\n", $out));
    }

    /**
     * handles split commands.
     *
     * @param string $script value.
     *
     * @return array result.
     */
    public static function splitCommands(string $script): array {
        $script = self::stripComments($script);
        $commands = [];
        $buffer = '';
        $quote = '';
        $braceDepth = 0;
        $parenDepth = 0;
        $squareDepth = 0;
        $len = strlen($script);

        for ($i = 0; $i < $len; $i++) {
            $ch = $script[$i];

            if ($quote !== '') {
                if ($ch === '\\' && $i + 1 < $len) {
                    $buffer .= $ch . $script[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;
                continue;
            }

            if ($ch === '(') {
                $parenDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === ')') {
                $parenDepth = max(0, $parenDepth - 1);
                $buffer .= $ch;
                continue;
            }

            if ($ch === '[') {
                $squareDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === ']') {
                $squareDepth = max(0, $squareDepth - 1);
                $buffer .= $ch;
                continue;
            }

            if ($ch === '{') {
                $braceDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === '}') {
                $braceDepth = max(0, $braceDepth - 1);
                $buffer .= $ch;

                if ($braceDepth === 0 && $parenDepth === 0 && $squareDepth === 0) {
                    $trim = trim($buffer);
                    $nextRaw = substr($script, $i + 1);
                    $nextTrim = ltrim($nextRaw);

                    if (preg_match('/^IF\b/i', $trim) && preg_match('/^ELSE\b/i', $nextTrim)) {
                        continue;
                    }

                    if (preg_match('/^(IF|FOR|MAP_OBJECT|(?:PUB|PRIV)\s+F|F|C|CLASS\s+[a-zA-Z0-9_\-]+\s*\{)\b/i', $trim)) {
                        $commands[] = $trim;
                        $buffer = '';
                    }

                }

                continue;
            }

            if ($ch === ';' && $braceDepth === 0 && $parenDepth === 0 && $squareDepth === 0) {
                $command = trim($buffer);

                if ($command !== '') {
                    $commands[] = $command;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $buffer = trim($buffer);

        if ($buffer !== '') {
            $commands[] = $buffer;
        }

        return $commands;
    }

    /**
     * handles evaluate value.
     *
     * @param string $value value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return mixed result.
     */
    public static function evaluateValue(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^param\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);

            return $params[$key] ?? null;
        }

        if (preg_match('/^ENV\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);

            return self::readEnvValue($key);
        }

        if (preg_match('/^fusion\((.*)\)$/is', $value, $m)) {
            $out = '';

            foreach (self::splitArguments((string)$m[1]) as $arg) {
                $evaluated = self::evaluateExpression($arg, $vars, $params);
                $raw = strtolower(trim($arg));

                if ($raw === 'true') $out .= 'true';
                else if ($raw === 'false') $out .= 'false';
                else if ($raw === 'null') $out .= 'null';
                else if (is_bool($evaluated)) $out .= $evaluated ? 'true' : 'false';
                else if ($evaluated === null) $out .= 'null';
                else if (is_array($evaluated) || is_object($evaluated)) $out .= json_encode($evaluated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
                else $out .= (string)$evaluated;
            }

            return $out;
        }

        $literal = self::parseLiteral($value, $vars, $params);

        if ($literal !== null) {
            return $literal;
        }

        if (preg_match('/^(fusion|hash_sha256|hash_sha512|hash_md5|hash_adler32|hash_crc32|hash_pass|random_int|hash|len|ENV)\(/i', $value)) {
            return self::evaluateExpression($value, $vars, $params);
        }

        if (preg_match('/^[$]?[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]+\])+$/', $value)) {
            return self::resolveVariablePath($value, $vars, $params);
        }

        if (strtoupper($value) === 'NOW') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^[$]?[a-zA-Z_][a-zA-Z0-9_]*$/', $value) && array_key_exists($value, $vars)) {
            return $vars[$value];
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $value) && array_key_exists('_' . $value, $vars)) {
            return $vars['_' . $value];
        }

        return self::unquote($value);
    }

    /**
     * handles split nested.
     *
     * @param string $raw value.
     *
     * @return array result.
     */
    public static function splitNested(string $raw): array {
        $out = [];
        $buffer = '';
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === '\\' && $i + 1 < $len) {
                    $buffer .= $ch . $raw[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;

                continue;
            }

            if ($ch === '(') $round++;

            if ($ch === ')') $round = max(0, $round - 1);

            if ($ch === '[') $square++;

            if ($ch === ']') $square = max(0, $square - 1);

            if ($ch === '{') $curly++;

            if ($ch === '}') $curly = max(0, $curly - 1);

            if ($ch === ',' && $round === 0 && $square === 0 && $curly === 0) {
                $part = trim($buffer);

                if ($part !== '') $out[] = $part;

                $buffer = '';

                continue;
            }

            $buffer .= $ch;
        }

        $part = trim($buffer);

        if ($part !== '') $out[] = $part;

        return $out;
    }

    private static function findTopLevel(string $raw, string $needle): int {
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);
        $nlen = strlen($needle);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === '(') $round++;

            if ($ch === ')') $round = max(0, $round - 1);

            if ($ch === '[') $square++;

            if ($ch === ']') $square = max(0, $square - 1);

            if ($ch === '{') $curly++;

            if ($ch === '}') $curly = max(0, $curly - 1);

            if ($round === 0 && $square === 0 && $curly === 0 && substr($raw, $i, $nlen) === $needle) {
                return $i;
            }

        }

        return -1;
    }

    /**
     * handles parse literal.
     *
     * @param string $value value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return mixed result.
     */
    public static function parseLiteral(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if ($value[0] === '[' && substr($value, -1) === ']') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') {
                return [];
            }

            $assoc = self::findTopLevel($inner, ':') >= 0;
            $out = [];

            foreach (self::splitNested($inner) as $part) {
                if ($assoc) {
                    $pos = self::findTopLevel($part, ':');

                    if ($pos < 0) continue;

                    $key = trim(substr($part, 0, $pos));
                    $key = (string)self::unquote($key);
                    $out[$key] = self::evaluateValue(substr($part, $pos + 1), $vars, $params);
                } else {
                    $out[] = self::evaluateValue($part, $vars, $params);
                }

            }

            return $out;
        }

        if ($value[0] === '{' && substr($value, -1) === '}') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') {
                return [];
            }

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
                    $out[] = self::parseLiteral($part, $vars, $params);
                }

                return $out;
            }

            $out = [];

            foreach ($parts as $part) {
                $pos = self::findTopLevel($part, ':');

                if ($pos < 0) continue;

                $key = trim(substr($part, 0, $pos));
                $key = (string)self::unquote($key);
                $out[$key] = self::evaluateValue(substr($part, $pos + 1), $vars, $params);
            }

            return $out;
        }

        return null;
    }

    /**
     * handles resolve variable path.
     *
     * @param string $value value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return mixed result.
     */
    public static function resolveVariablePath(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if (!preg_match('/^([$]?[a-zA-Z_][a-zA-Z0-9_]*)(.*)$/s', $value, $m)) {
            return null;
        }

        $name = (string)$m[1];

        if (!array_key_exists($name, $vars)) {
            return null;
        }

        $current = $vars[$name];
        $rest = trim((string)$m[2]);

        while ($rest !== '') {
            if (!preg_match('/^\[([^\]]+)\](.*)$/s', $rest, $p)) {
                return $current;
            }

            $key = self::evaluateValue((string)$p[1], $vars, $params);

            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }

            $rest = trim((string)$p[2]);
        }

        return $current;
    }

    /**
     * handles evaluate expression.
     *
     * @param string $value value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return mixed result.
     */
    public static function evaluateExpression(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') return '';

        if (strlen($value) >= 2 && $value[0] === '(' && substr($value, -1) === ')' && self::findTopLevel(substr($value, 1, -1), '(') < 0) {
            return self::evaluateExpression(substr($value, 1, -1), $vars, $params);
        }

        foreach (['+', '-', '*', '/', '%'] as $op) {
            $pos = self::findMathOperator($value, $op);

            if ($pos >= 0) {
                $left = self::evaluateExpression(substr($value, 0, $pos), $vars, $params);
                $right = self::evaluateExpression(substr($value, $pos + 1), $vars, $params);

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

        if (preg_match('/^!\s*(.+)$/s', $value, $m)) {
            return !((bool)self::evaluateExpression((string)$m[1], $vars, $params));
        }

        foreach (['==', '!=', '>=', '<=', '>', '<'] as $op) {
            $pos = self::findTopLevel($value, $op);

            if ($pos >= 0) {
                $left = self::evaluateExpression(substr($value, 0, $pos), $vars, $params);
                $right = self::evaluateExpression(substr($value, $pos + strlen($op)), $vars, $params);

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

        if (preg_match('/^ENV\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);

            return self::readEnvValue($key);
        }

        if (preg_match('/^now\(\s*\)$/i', $value)) {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^hash_pass\((.*)\)$/is', $value, $m)) {
            $password = (string)self::evaluateValue((string)$m[1], $vars, $params);

            if (class_exists('Auth') && method_exists('Auth', 'hashPass')) {
                return Auth::hashPass($password);
            }

            return hash('sha256', hash('adler32', hash('md5', hash('sha512', $password))));
        }

        if (preg_match('/^random_int\((.*)\)$/is', $value, $m)) {
            $args = self::evalArgs((string)$m[1], $vars, $params);

            if (count($args) >= 2) {
                $min = (int)$args[0];
                $max = (int)$args[1];

                if ($max < $min) [$min, $max] = [$max, $min];

                try {
                    return random_int($min, $max);
                } catch (Throwable $e) {
                    return mt_rand($min, $max);
                }

            }

            $length = max(1, (int)($args[0] ?? 1));

            if ($length === 1) {
                try {
                    return random_int(0, 9);
                } catch (Throwable $e) {
                    return mt_rand(0, 9);
                }

            }

            $out = '';

            for ($i = 0; $i < $length; $i++) {
                try {
                    $out .= (string)random_int(0, 9);
                } catch (Throwable $e) {
                    $out .= (string)mt_rand(0, 9);
                }

            }

            return $out;
        }

        if (preg_match('/^(uni_random|spark_id|fresh_id)\(\s*\)$/i', $value)) {
            try {
                return bin2hex(random_bytes(16)) . dechex((int)(microtime(true) * 1000000));
            } catch (Throwable $e) {
                return sha1(uniqid('', true) . mt_rand());
            }

        }

        if (preg_match('/^uuid\(\s*\)$/i', $value)) {
            try {
                $data = random_bytes(16);
                $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
                $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            } catch (Throwable $e) {
                return sha1(uniqid('', true) . mt_rand());
            }

        }

        if (preg_match('/^hash_(sha256|sha512|md5|adler32|crc32)\((.*)\)$/is', $value, $m)) {
            return hash(strtolower((string)$m[1]), (string)self::evaluateValue((string)$m[2], $vars, $params));
        }

        if (preg_match('/^hash\(([^,]+),(.+)\)$/is', $value, $m)) {
            $algo = strtolower(trim((string)self::evaluateValue((string)$m[1], $vars, $params)));

            if (!in_array($algo, hash_algos(), true)) {
                return '';
            }

            return hash($algo, (string)self::evaluateValue((string)$m[2], $vars, $params));
        }

        if (preg_match('/^hash\((.*)\)$/is', $value, $m)) {
            return hash('sha256', (string)self::evaluateValue((string)$m[1], $vars, $params));
        }

        if (preg_match('/^len\((.*)\)$/is', $value, $m)) {
            $tmp = self::evaluateValue((string)$m[1], $vars, $params);

            return is_array($tmp) || $tmp instanceof Countable ? count($tmp) : strlen((string)$tmp);
        }

        return self::evaluateValue($value, $vars, $params);
    }

    private static function findMathOperator(string $raw, string $needle): int {
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);

        for ($i = $len - 1; $i >= 0; $i--) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === $quote && ($i === 0 || $raw[$i - 1] !== '\\')) $quote = '';
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === ')') $round++;

            if ($ch === '(') $round = max(0, $round - 1);

            if ($ch === ']') $square++;

            if ($ch === '[') $square = max(0, $square - 1);

            if ($ch === '}') $curly++;

            if ($ch === '{') $curly = max(0, $curly - 1);

            if ($round !== 0 || $square !== 0 || $curly !== 0) continue;

            if ($ch !== $needle) continue;

            if (($needle === '+' || $needle === '-') && ($i === 0 || preg_match('/[+\-*\/%%(<>=!,]/', $raw[$i - 1]))) continue;

            return $i;
        }

        return -1;
    }

    private static function parseParamObject(string $raw, array $vars = [], array $params = []): array {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $data = self::parseLiteral($raw, $vars, $params);

        return is_array($data) ? $data : [];
    }

    /**
     * handles parse list.
     *
     * @param string $raw value.
     * @param array $vars value.
     *
     * @return array result.
     */
    public static function parseList(string $raw, array $vars = []): array {
        $parts = preg_split('/\s*,\s*/', trim($raw));
        $out = [];

        foreach ($parts as $part) {
            $part = self::resolveNameToken((string)$part, $vars);

            if ($part === "") {
                continue;
            }

            $out[] = $part;
        }

        return array_values(array_filter($out));
    }

    /**
     * handles split arguments.
     *
     * @param string $raw value.
     *
     * @return array result.
     */
    public static function splitArguments(string $raw): array {
        $raw = trim($raw);

        if ($raw === "") {
            return [];
        }

        $out = [];
        $buffer = "";
        $quote = "";
        $depth = 0;
        $squareDepth = 0;
        $curlyDepth = 0;
        $len = strlen($raw);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== "") {
                if ($ch === "\\" && $i + 1 < $len) {
                    $buffer .= $ch . $raw[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = "";
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === "\"" || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;

                continue;
            }

            if ($ch === "(") {
                $depth++;
            } else if ($ch === ")") {
                $depth = max(0, $depth - 1);
            } else if ($ch === "[") {
                $squareDepth++;
            } else if ($ch === "]") {
                $squareDepth = max(0, $squareDepth - 1);
            } else if ($ch === "{") {
                $curlyDepth++;
            } else if ($ch === "}") {
                $curlyDepth = max(0, $curlyDepth - 1);
            }

            if ($ch === "," && $depth === 0 && $squareDepth === 0 && $curlyDepth === 0) {
                $out[] = trim($buffer);
                $buffer = "";

                continue;
            }

            $buffer .= $ch;
        }

        $buffer = trim($buffer);

        if ($buffer !== "") {
            $out[] = $buffer;
        }

        return $out;
    }

    /**
     * handles parse assignments.
     *
     * @param string $raw value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return array result.
     */
    public static function parseAssignments(string $raw, array $vars = [], array $params = []): array {
        $raw = trim($raw);

        if ($raw === '') return [];

        $out = [];

        foreach (self::splitNested($raw) as $part) {
            $pos = self::findTopLevel($part, '=');

            if ($pos < 0) continue;

            $key = self::cleanName((string)substr($part, 0, $pos));

            if ($key === '' || $key === 'id') continue;

            $out[$key] = self::evaluateExpression(substr($part, $pos + 1), $vars, $params);
        }

        return $out;
    }

    /**
     * Parst WHERE-Bedingungen.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return ?array Rückgabewert.
     */

    private static function parseColumnDefinition(string $raw, array $vars = [], array $params = []): ?array {
        $raw = trim($raw);

        if ($raw === '') return null;

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)(.*)$/s', $raw, $m)) return null;

        $name = trim((string)$m[1]);
        $rest = trim((string)$m[2]);

        if ($name === '' || $name === 'id') return null;

        $knownTypes = ['mixed','string','str','text','email','url','int','integer','float','double','decimal','bool','boolean','datetime','date','time','timestamp','json','array','enum','uuid','ulid','blob_reference','blob'];
        $type = 'mixed';
        $typed = false;
        $options = ['nullable' => true, 'required' => false, 'unique' => false];

        if ($rest !== '' && $rest[0] === ':') {
            $rest = trim(substr($rest, 1));

            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)(.*)$/s', $rest, $tm)) {
                $candidate = strtolower((string)$tm[1]);

                if (in_array($candidate, $knownTypes, true)) {
                    $type = self::normalizeGreenQLType($candidate);
                    $typed = true;
                    $rest = trim((string)$tm[2]);
                }

            }

        } else if ($rest !== '' && preg_match('/^([a-zA-Z_][a-zA-Z0-9_\-]*)(.*)$/s', $rest, $tm)) {
            $candidate = strtolower((string)$tm[1]);

            if (in_array($candidate, $knownTypes, true)) {
                $type = self::normalizeGreenQLType($candidate);
                $typed = true;
                $rest = trim((string)$tm[2]);
            }

        }

        if ($rest !== '' && preg_match('/\bDEFAULT\b\s+(.+)$/is', $rest, $dm, PREG_OFFSET_CAPTURE)) {
            $defaultRaw = trim((string)$dm[1][0]);
            $options['default'] = self::evaluateExpression($defaultRaw, $vars, $params);
            $rest = trim(substr($rest, 0, (int)$dm[0][1]));
        } else {
            $options['default'] = self::defaultForGreenQLType($type);
        }

        if (preg_match('/\bREQUIRED\b|\bNOT\s+NULL\b|\bNOT_NULL\b/i', $rest)) {
            $options['required'] = true;
            $options['not_null'] = true;
            $options['nullable'] = false;
        }

        if (preg_match('/\bUNIQUE\b/i', $rest)) $options['unique'] = true;

        if (preg_match('/\bAUTO_INCREMENT\b|\bAUTOINCREMENT\b/i', $rest)) $options['auto_increment'] = true;

        if (preg_match('/\bNULLABLE\b|\bNULL\b/i', $rest) && !preg_match('/\bNOT\s+NULL\b/i', $rest)) {
            $options['nullable'] = true;
            $options['not_null'] = false;
        }

        return ['name' => $name, 'type' => $type, 'typed' => $typed, 'options' => $options];
    }

    private static function parseTableDefinitions(string $raw, array $vars = [], array $params = []): array {
        $items = self::splitNested($raw);
        $cols = [];
        $schema = [];
        $typed = false;

        foreach ($items as $item) {
            $def = self::parseColumnDefinition((string)$item, $vars, $params);

            if (!is_array($def)) continue;

            $name = (string)$def['name'];

            if (in_array($name, $cols, true)) continue;

            $cols[] = $name;
            $schema[$name] = array_merge(['type' => $def['type']], $def['options']);
            $typed = $typed || (bool)$def['typed'] || (strtolower((string)$def['type']) !== 'mixed');
        }

        return ['cols' => $cols, 'schema' => $schema, 'typed' => $typed];
    }

    private static function normalizeGreenQLType(string $type): string {
        $type = strtolower(trim($type));

        return match ($type) {
            'str' => 'string',
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            'blob' => 'blob_reference',
            default => $type
        };
    }

    private static function defaultForGreenQLType(string $type): mixed {
        return match (self::normalizeGreenQLType($type)) {
            'int', 'timestamp' => 0,
            'float', 'decimal' => 0.0,
            'bool' => false,
            'json', 'array', 'enum' => [],
            default => ''
        };
    }

    private static function tokenizeWhereExpression(string $raw): array {
        $tokens = [];
        $len = strlen($raw);
        $i = 0;

        while ($i < $len) {
            $ch = $raw[$i];

            if (ctype_space($ch)) { $i++; continue; }

            if ($ch === '(' || $ch === ')' || $ch === '[' || $ch === ']' || $ch === ',') {
                $tokens[] = ['type' => $ch, 'value' => $ch];
                $i++;

                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buf = $ch;
                $i++;

                while ($i < $len) {
                    $c = $raw[$i];
                    $buf .= $c;

                    if ($c === '\\' && $i + 1 < $len) {
                        $i++;
                        $buf .= $raw[$i];
                    } else if ($c === $quote) {
                        $i++;
                        break;
                    }

                    $i++;
                }

                $tokens[] = ['type' => 'value', 'value' => $buf];
                continue;
            }

            $two = $i + 1 < $len ? substr($raw, $i, 2) : '';

            if (in_array($two, ['==', '!=', '>=', '<=', '~='], true)) {
                $tokens[] = ['type' => 'op', 'value' => $two];
                $i += 2;
                continue;
            }

            if ($ch === '=' || $ch === '>' || $ch === '<') {
                $tokens[] = ['type' => 'op', 'value' => $ch];
                $i++;
                continue;
            }

            $buf = '';

            while ($i < $len) {
                $c = $raw[$i];

                if (ctype_space($c) || in_array($c, ['(', ')', '[', ']', ','], true)) break;

                if (in_array(substr($raw, $i, 2), ['==', '!=', '>=', '<=', '~='], true) || in_array($c, ['=', '>', '<'], true)) break;

                $buf .= $c;
                $i++;
            }

            if ($buf !== '') {
                $upper = strtoupper($buf);
                $keywords = ['AND','OR','NOT','IN','BETWEEN','IS','NULL','LIKE'];
                $tokens[] = ['type' => in_array($upper, $keywords, true) ? 'kw' : 'value', 'value' => $buf];

                continue;
            }

            $tokens[] = ['type' => 'unknown', 'value' => $ch];
            $i++;
        }

        return $tokens;
    }

    private static function whereTokenIs(array $tokens, int $pos, string $keyword): bool {
        if (!isset($tokens[$pos])) return false;

        return strtoupper((string)$tokens[$pos]['value']) === strtoupper($keyword);
    }

    private static function readWhereValue(array $tokens, int &$pos, array $vars, array $params): mixed {
        if (!isset($tokens[$pos])) return null;

        $value = (string)$tokens[$pos]['value'];
        $pos++;

        return self::evaluateValue($value, $vars, $params);
    }

    private static function readWhereList(array $tokens, int &$pos, array $vars, array $params): array {
        $values = [];

        if (!isset($tokens[$pos]) || $tokens[$pos]['type'] !== '[') return $values;

        $pos++;

        while (isset($tokens[$pos]) && $tokens[$pos]['type'] !== ']') {
            if ($tokens[$pos]['type'] === ',') { $pos++; continue; }
            $values[] = self::readWhereValue($tokens, $pos, $vars, $params);
        }

        if (isset($tokens[$pos]) && $tokens[$pos]['type'] === ']') $pos++;

        return $values;
    }

    private static function parseWhereOr(array $tokens, int &$pos, array $vars, array $params): ?array {
        $left = self::parseWhereAnd($tokens, $pos, $vars, $params);

        while (self::whereTokenIs($tokens, $pos, 'OR')) {
            $pos++;
            $right = self::parseWhereAnd($tokens, $pos, $vars, $params);

            if ($left === null || $right === null) return ['type' => 'invalid'];

            $left = ['type' => 'or', 'left' => $left, 'right' => $right];
        }

        return $left;
    }

    private static function parseWhereAnd(array $tokens, int &$pos, array $vars, array $params): ?array {
        $left = self::parseWhereNot($tokens, $pos, $vars, $params);

        while (self::whereTokenIs($tokens, $pos, 'AND')) {
            $pos++;
            $right = self::parseWhereNot($tokens, $pos, $vars, $params);

            if ($left === null || $right === null) return ['type' => 'invalid'];

            $left = ['type' => 'and', 'left' => $left, 'right' => $right];
        }

        return $left;
    }

    private static function parseWhereNot(array $tokens, int &$pos, array $vars, array $params): ?array {
        if (self::whereTokenIs($tokens, $pos, 'NOT')) {
            $pos++;
            $expr = self::parseWherePrimary($tokens, $pos, $vars, $params);

            return $expr === null ? ['type' => 'invalid'] : ['type' => 'not', 'expr' => $expr];
        }

        return self::parseWherePrimary($tokens, $pos, $vars, $params);
    }

    private static function parseWherePrimary(array $tokens, int &$pos, array $vars, array $params): ?array {
        if (!isset($tokens[$pos])) return null;

        if ($tokens[$pos]['type'] === '(') {
            $pos++;
            $expr = self::parseWhereOr($tokens, $pos, $vars, $params);

            if (!isset($tokens[$pos]) || $tokens[$pos]['type'] !== ')') return ['type' => 'invalid'];

            $pos++;

            return $expr;
        }

        $field = self::cleanName((string)$tokens[$pos]['value']);

        if ($field === '') return ['type' => 'invalid'];

        $pos++;

        if (!isset($tokens[$pos])) return ['type' => 'invalid'];

        if (self::whereTokenIs($tokens, $pos, 'IS')) {
            $pos++;
            $not = false;

            if (self::whereTokenIs($tokens, $pos, 'NOT')) { $not = true; $pos++; }

            if (!self::whereTokenIs($tokens, $pos, 'NULL')) return ['type' => 'invalid'];

            $pos++;

            return ['type' => 'condition', 'field' => $field, 'op' => $not ? 'IS NOT NULL' : 'IS NULL', 'value' => null];
        }

        if (self::whereTokenIs($tokens, $pos, 'IN')) {
            $pos++;

            return ['type' => 'condition', 'field' => $field, 'op' => 'IN', 'value' => self::readWhereList($tokens, $pos, $vars, $params)];
        }

        if (self::whereTokenIs($tokens, $pos, 'BETWEEN')) {
            $pos++;
            $from = self::readWhereValue($tokens, $pos, $vars, $params);

            if (!self::whereTokenIs($tokens, $pos, 'AND')) return ['type' => 'invalid'];

            $pos++;
            $to = self::readWhereValue($tokens, $pos, $vars, $params);

            return ['type' => 'condition', 'field' => $field, 'op' => 'BETWEEN', 'value' => [$from, $to]];
        }

        if (self::whereTokenIs($tokens, $pos, 'LIKE')) {
            $pos++;

            return ['type' => 'condition', 'field' => $field, 'op' => 'LIKE', 'value' => self::readWhereValue($tokens, $pos, $vars, $params)];
        }

        if (($tokens[$pos]['type'] ?? '') !== 'op') return ['type' => 'invalid'];

        $op = (string)$tokens[$pos]['value'];
        $pos++;

        if (!isset($tokens[$pos])) return ['type' => 'invalid'];

        return ['type' => 'condition', 'field' => $field, 'op' => $op, 'value' => self::readWhereValue($tokens, $pos, $vars, $params)];
    }

    /**
     * handles parse where.
     *
     * @param string $raw value.
     * @param array $vars value.
     * @param array $params value.
     *
     * @return ?array result.
     */
    public static function parseWhere(string $raw, array $vars = [], array $params = []): ?array {
        $raw = trim($raw);

        if ($raw === '') return null;

        $tokens = self::tokenizeWhereExpression($raw);
        $pos = 0;
        $ast = self::parseWhereOr($tokens, $pos, $vars, $params);

        if ($ast === null || $pos < count($tokens)) {
            return ['type' => 'invalid', 'raw' => $raw, 'error' => 'invalid_where_syntax'];
        }

        if (($ast['type'] ?? '') === 'condition') {
            $ast['raw'] = $raw;

            return $ast;
        }

        $ast['raw'] = $raw;

        return $ast;
    }

}
