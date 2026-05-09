<?php

trait GreenQL_BaseTrait {

    /**
     * handles clean name.
     *
     * @param string $name value.
     *
     * @return string result.
     */
    public static function cleanName(string $name): string {
        $name = trim($name);

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?? '';
    }

    private static function db(): string {
        return self::$driver;
    }

    private static function syncInstance(array $ctx = []): void {
        $instance = self::cleanName((string)($ctx["instance"] ?? self::$instance));

        if ($instance !== "" && class_exists("GBDB")) {
            self::$driver = "GBDB";
            self::$instance = $instance;

            GBDB::setInstance($instance);

            return;
        }

        self::$driver = "GBDB";
    }

    private static function useInstance(string $instance, array &$ctx = []): bool {
        $instance = self::cleanName($instance);

        if ($instance === "" || !class_exists("GBDB")) {
            return false;
        }

        self::$driver = "GBDB";
        self::$instance = $instance;

        GBDB::setInstance($instance);

        $ctx["instance"] = $instance;

        return true;
    }

    /**
     * handles resolve name token.
     *
     * @param string $token value.
     * @param array $vars value.
     *
     * @return string result.
     */
    public static function resolveNameToken(string $token, array $vars = []): string {
        $token = trim($token);

        if ($token === "") {
            return "";
        }

        if (($token[0] ?? '') === '$' || ($token[0] ?? '') === '_') {
            $varName = self::cleanVarName($token);

            if (array_key_exists($varName, $vars)) {
                return self::cleanName((string)$vars[$varName]);
            }

        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $token) && array_key_exists($token, $vars)) {
            return self::cleanName((string)$vars[$token]);
        }

        return self::cleanName($token);
    }

    private static function optionalDbMatch(array $m, int $index, array $ctx): string {
        return isset($m[$index]) && trim((string)$m[$index]) !== '' ? (string)$m[$index] : (string)($ctx['db'] ?? '');
    }

}
