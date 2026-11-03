<?php

declare(strict_types=1);

/**
 * Ergänzt die lokale .framework.env.php um das, was ein Release neu mitbringt:
 * fehlende Konstanten, fehlende Schlüssel in bestehenden Einstellungs-Arrays, fehlende
 * Methoden und Eigenschaften der Vars-Klasse.
 *
 * Vorhandene Werte, Kommentare und Formatierung bleiben unverändert; es wird nur Text
 * eingefügt. Ausgeführt wird dabei nichts: Werte werden aus den Token gelesen.
 */
final class GBDBUpdateConfigMerger {
    private const SKIP = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    private function __construct() {
    }

    /**
     * @return array{source: string, changes: list<string>, warnings: list<string>}
     */
    public static function merge(string $local, string $template) :array {
        $localClass = self::classMembers($local);
        $templateClass = self::classMembers($template);
        $insertions = [];
        $classBlock = [];
        $changes = [];
        $warnings = [];

        // Konstanten gelten unabhängig von Schreibweise als gleich (FRAMEWORK_UPDATER = FrameworkUpdater).
        $localNames = [];

        foreach (array_keys($localClass['consts']) as $localName) {
            $localNames[self::normalize((string) $localName)] = (string) $localName;
        }

        foreach ($templateClass['consts'] as $name => $templateConst) {
            $name = $localNames[self::normalize((string) $name)] ?? $name;

            if (!isset($localClass['consts'][$name])) {
                $classBlock[] = $templateConst['source'];
                $changes[] = 'Neue Einstellung ' . $name;

                continue;
            }

            $localNode = self::parseValue($localClass['tokens'], $localClass['consts'][$name]['value_start']);
            $templateNode = self::parseValue($templateClass['tokens'], $templateConst['value_start']);

            if ($localNode === null || $templateNode === null) {
                $warnings[] = $name . ' enthält Ausdrücke statt fester Werte; neue Unterschlüssel wurden nicht ergänzt.';

                continue;
            }

            self::missingKeys($localNode, $templateNode, $localClass, $templateClass, $name, $insertions, $changes);
        }

        foreach (['methods' => 'Neue Methode Vars::', 'properties' => 'Neue Eigenschaft Vars::$'] as $kind => $label) {
            foreach ($templateClass[$kind] as $name => $member) {
                if (!isset($localClass[$kind][$name])) {
                    $classBlock[] = $member['source'];
                    $changes[] = $label . $member['name'];
                }
            }
        }

        if ($classBlock !== []) {
            $insertions[] = [$localClass['offsets'][$localClass['close']], "\n    // Ergänzt durch ein Framework-Update am " . gmdate('Y-m-d') . "\n" . implode("\n\n", $classBlock) . "\n"];
        }

        usort($insertions, static fn(array $a, array $b) :int => $b[0] <=> $a[0]);
        $source = $local;

        foreach ($insertions as [$offset, $text]) {
            $source = substr($source, 0, $offset) . $text . substr($source, $offset);
        }

        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $error) {
            throw new RuntimeException('Die ergänzte Konfiguration wäre kein gültiges PHP: ' . $error->getMessage());
        }

        return ['source' => $source, 'changes' => $changes, 'warnings' => $warnings];
    }

    private static function normalize(string $name) :string {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $name));
    }

    /**
     * Konstanten, Methoden und Eigenschaften der Klasse Vars mit Tokenpositionen.
     *
     * @return array<string, mixed>
     */
    private static function classMembers(string $source) :array {
        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $error) {
            throw new RuntimeException('Konfigurationsdatei ist kein gültiges PHP: ' . $error->getMessage());
        }

        $offsets = [];
        $offset = 0;

        foreach ($tokens as $token) {
            $offsets[] = $offset;
            $offset += strlen(is_array($token) ? $token[1] : $token);
        }

        $offsets[] = $offset;
        $count = count($tokens);
        $open = null;

        for ($i = 0; $i < $count && $open === null; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                $name = self::next($tokens, $i + 1);

                if ($name !== null && is_array($tokens[$name]) && $tokens[$name][1] === 'Vars') {
                    for ($open = $name; $open < $count && $tokens[$open] !== '{'; $open++) {
                    }
                }
            }
        }

        if ($open === null || $open >= $count) {
            throw new RuntimeException('Die Konfigurationsdatei enthält keine Klasse Vars.');
        }

        $class = ['tokens' => $tokens, 'offsets' => $offsets, 'source' => $source, 'consts' => [], 'methods' => [], 'properties' => [], 'close' => null];
        $memberStart = null;

        for ($i = $open + 1; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token === '}') {
                $class['close'] = $i;

                break;
            }

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }

            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_FINAL, T_ABSTRACT, T_READONLY], true)) {
                $memberStart ??= $i;

                continue;
            }

            $start = $memberStart ?? $i;
            $memberStart = null;

            if (is_array($token) && $token[0] === T_CONST) {
                $name = self::next($tokens, $i + 1);
                $equals = $name;

                while ($tokens[$equals] !== '=') {
                    $equals++;
                }

                $end = self::statementEnd($tokens, $equals);
                $class['consts'][$tokens[$name][1]] = ['value_start' => (int) self::next($tokens, $equals + 1), 'source' => self::slice($source, $offsets, $start, $end + 1)];
                $i = $end;

                continue;
            }

            if (is_array($token) && $token[0] === T_FUNCTION) {
                $name = self::next($tokens, $i + 1);
                $end = $i;

                while ($end < $count && $tokens[$end] !== '{' && $tokens[$end] !== ';') {
                    $end++;
                }

                if ($tokens[$end] === '{') {
                    $end = self::matchingBrace($tokens, $end);
                }

                $class['methods'][strtolower($tokens[$name][1])] = ['name' => $tokens[$name][1], 'source' => self::slice($source, $offsets, $start, $end + 1)];
                $i = $end;

                continue;
            }

            if (is_array($token) && $token[0] === T_VARIABLE) {
                $end = self::statementEnd($tokens, $i);
                $class['properties'][substr($token[1], 1)] = ['name' => substr($token[1], 1), 'source' => self::slice($source, $offsets, $start, $end + 1)];
                $i = $end;

                continue;
            }

            // Typangaben von Eigenschaften (?bool, array …) und sonstige Token bis zum Semikolon.
            if (is_array($token) || $token === '?') {
                $memberStart = $start;
            }
        }

        if ($class['close'] === null) {
            throw new RuntimeException('Die Klasse Vars ist nicht geschlossen.');
        }

        return $class;
    }

    /** @param list<mixed> $tokens */
    private static function statementEnd(array $tokens, int $index) :int {
        $level = 0;
        $count = count($tokens);

        for (; $index < $count; $index++) {
            $token = $tokens[$index];

            if (in_array($token, ['[', '(', '{'], true)) {
                $level++;
            } elseif (in_array($token, [']', ')', '}'], true)) {
                $level--;
            } elseif ($token === ';' && $level === 0) {
                return $index;
            }
        }

        throw new RuntimeException('Unvollständige Anweisung in der Konfigurationsdatei.');
    }

    /** @param list<mixed> $tokens */
    private static function matchingBrace(array $tokens, int $index) :int {
        $level = 0;
        $count = count($tokens);

        for (; $index < $count; $index++) {
            $token = $tokens[$index];

            if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $level++;
            } elseif ($token === '}') {
                $level--;

                if ($level === 0) {
                    return $index;
                }
            }
        }

        throw new RuntimeException('Unvollständige Methode in der Konfigurationsdatei.');
    }

    /** @param list<int> $offsets */
    private static function slice(string $source, array $offsets, int $from, int $to) :string {
        return '    ' . ltrim(substr($source, $offsets[$from], $offsets[$to] - $offsets[$from]));
    }

    /** @param list<mixed> $tokens */
    private static function next(array $tokens, int $index) :?int {
        $count = count($tokens);

        for (; $index < $count; $index++) {
            if (!is_array($tokens[$index]) || !in_array($tokens[$index][0], self::SKIP, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Literalwert ab Token $index: Arrays, Zeichenketten, Zahlen, true/false/null.
     * null = enthält Ausdrücke (wird nicht zusammengeführt).
     *
     * @param list<mixed> $tokens
     * @return array<string, mixed>|null
     */
    private static function parseValue(array $tokens, int $index) :?array {
        $position = $index;

        try {
            $node = self::value($tokens, $position);
            $after = self::next($tokens, $position);

            return $after !== null && $tokens[$after] === ';' ? $node : null;
        } catch (UnexpectedValueException) {
            return null;
        }
    }

    /**
     * @param list<mixed> $tokens
     * @return array<string, mixed>
     */
    private static function value(array $tokens, int &$position) :array {
        $position = self::next($tokens, $position) ?? throw new UnexpectedValueException();
        $start = $position;
        $token = $tokens[$position];
        $closing = null;

        if ($token === '[') {
            $closing = ']';
        } elseif (is_array($token) && $token[0] === T_ARRAY) {
            $position = self::next($tokens, $position + 1) ?? throw new UnexpectedValueException();

            if ($tokens[$position] !== '(') {
                throw new UnexpectedValueException();
            }

            $closing = ')';
        }

        if ($closing !== null) {
            $items = [];
            $list = true;
            $next = 0;
            $last = $position;
            $position++;

            while (true) {
                $peek = self::next($tokens, $position) ?? throw new UnexpectedValueException();

                if ($tokens[$peek] === $closing) {
                    $position = $peek + 1;

                    return ['type' => 'array', 'list' => $list && $items !== [], 'items' => $items, 'close' => $peek, 'last' => $last, 'start' => $start, 'end' => $peek + 1];
                }

                $position = $peek;
                $first = self::value($tokens, $position);
                $arrow = self::next($tokens, $position);

                if ($arrow !== null && is_array($tokens[$arrow]) && $tokens[$arrow][0] === T_DOUBLE_ARROW) {
                    if ($first['type'] !== 'scalar' || (!is_string($first['value']) && !is_int($first['value']))) {
                        throw new UnexpectedValueException();
                    }

                    $position = $arrow + 1;
                    $item = self::value($tokens, $position);
                    $key = $first['value'];
                    $list = false;
                } else {
                    $item = $first;
                    $key = $next++;
                }

                $item['key_start'] = $first['start'];
                $items[$key] = $item;
                $separator = self::next($tokens, $position) ?? throw new UnexpectedValueException();
                $last = $position - 1;

                if ($tokens[$separator] === ',') {
                    $last = $separator;
                    $position = $separator + 1;
                } elseif ($tokens[$separator] !== $closing) {
                    throw new UnexpectedValueException();
                }
            }
        }

        $negative = false;

        if ($token === '-') {
            $negative = true;
            $position = self::next($tokens, $position + 1) ?? throw new UnexpectedValueException();
            $token = $tokens[$position];
        }

        if (is_array($token) && $token[0] === T_LNUMBER) {
            $value = intval(str_replace('_', '', $token[1]), 0);
        } elseif (is_array($token) && $token[0] === T_DNUMBER) {
            $value = (float) str_replace('_', '', $token[1]);
        } elseif (!$negative && is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $value = self::stringValue($token[1]);
        } elseif (!$negative && is_array($token) && $token[0] === T_STRING && in_array(strtolower($token[1]), ['true', 'false', 'null'], true)) {
            $value = ['true' => true, 'false' => false, 'null' => null][strtolower($token[1])];
        } else {
            throw new UnexpectedValueException();
        }

        $position++;

        return ['type' => 'scalar', 'value' => $negative ? -$value : $value, 'start' => $start, 'end' => $position];
    }

    private static function stringValue(string $literal) :string {
        $body = substr($literal, 1, -1);

        if ($literal[0] === "'") {
            return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
        }

        // Doppelte Anführungszeichen nur ohne Variablen; Escapes wie in PHP.
        if (preg_match('/(?<!\\\\)(?:\\\\\\\\)*\$/', $body) || str_contains($body, '\\u{')) {
            throw new UnexpectedValueException();
        }

        return stripcslashes($body);
    }

    /**
     * Fehlende Schlüssel rekursiv als Text vor der schließenden Klammer einfügen.
     *
     * @param array<string, mixed> $local
     * @param array<string, mixed> $template
     * @param array<string, mixed> $localClass
     * @param array<string, mixed> $templateClass
     * @param list<array{0: int, 1: string}> $insertions
     * @param list<string> $changes
     */
    private static function missingKeys(array $local, array $template, array $localClass, array $templateClass, string $path, array &$insertions, array &$changes) :void {
        if ($local['type'] !== 'array' || $template['type'] !== 'array' || $local['list'] || $template['list']) {
            return;
        }

        $added = [];

        foreach ($template['items'] as $key => $item) {
            if (!array_key_exists($key, $local['items'])) {
                $from = $templateClass['offsets'][$item['key_start']];
                $added[] = substr($templateClass['source'], $from, $templateClass['offsets'][$item['end']] - $from);
                $changes[] = 'Neuer Wert ' . $path . '.' . $key;

                continue;
            }

            self::missingKeys($local['items'][$key], $item, $localClass, $templateClass, $path . '.' . $key, $insertions, $changes);
        }

        if ($added === []) {
            return;
        }

        $source = $localClass['source'];
        $offsets = $localClass['offsets'];
        $closeOffset = $offsets[$local['close']];
        $lineStart = strrpos(substr($source, 0, $closeOffset), "\n");
        $beforeClose = $lineStart === false ? '' : substr($source, $lineStart + 1, $closeOffset - $lineStart - 1);

        if ($local['items'] !== [] && $localClass['tokens'][$local['last']] !== ',') {
            $insertions[] = [$offsets[$local['last'] + 1], ','];
        }

        if ($lineStart !== false && trim($beforeClose) === '') {
            // Schließende Klammer steht auf eigener Zeile: neue Zeilen davor, gleich eingerückt wie die Werte.
            $indent = $beforeClose . '    ';
            $insertions[] = [$lineStart + 1, $indent . implode(",\n" . $indent, $added) . ",\n"];
        } else {
            $insertions[] = [$closeOffset, ($local['items'] !== [] ? ' ' : '') . implode(', ', $added)];
        }
    }
}
