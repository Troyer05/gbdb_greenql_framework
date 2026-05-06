<?php

trait GreenQL_BaseTrait {

    /**
     * Bereinigt Namen für Datenbanken, Tabellen, Felder und Instanzen.
     * @param string $name Übergabewert.
     * @return string Rückgabewert.
     */
    public static function cleanName(string $name): string {
        $name = trim($name);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?? '';
    }


    /**
     * Gibt den aktiven Datenbank-Treiber zurück.
     * @return string Rückgabewert.
     */
    private static function db(): string {
        return self::$driver;
    }


    /**
     * Synchronisiert den aktiven Treiber anhand des Contextes.
     * @param array $ctx Übergabewert.
     * @return void Rückgabewert.
     */
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


    /**
     * Aktiviert eine GBDB-Instanz.
     * @param string $instance Übergabewert.
     * @param array $ctx Übergabewert.
     * @return bool Rückgabewert.
     */
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
     * Löst einen Namen aus Token oder Variable auf.
     * @param string $token Übergabewert.
     * @param array $vars Übergabewert.
     * @return string Rückgabewert.
     */
    public static function resolveNameToken(string $token, array $vars = []): string {
        $token = trim($token);

        if ($token === "") {
            return "";
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $token) && array_key_exists($token, $vars)) {
            return self::cleanName((string)$vars[$token]);
        }

        return self::cleanName($token);
    }


    /**
     * Gibt einen optionalen Regex-Treffer zurück oder fällt auf die aktive Base zurück.
     * @param array $m Regex-Treffer.
     * @param int $index Treffer-Index.
     * @param array $ctx Aktueller Context.
     * @return string Wert.
     */
    private static function optionalDbMatch(array $m, int $index, array $ctx): string {
        return isset($m[$index]) && trim((string)$m[$index]) !== '' ? (string)$m[$index] : (string)($ctx['db'] ?? '');
    }
}
