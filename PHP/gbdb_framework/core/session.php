<?php

class Session {

    /**
     * Startet die Session sicher, falls sie nicht aktiv ist.
     * Aktiviert sichere Cookie-Parameter.
     */
    public static function handler(): void {

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Sichere Cookie-Settings
        session_set_cookie_params([
            "lifetime" => 360 * 24 * 60 * 60, // 360 Tage
            "path" => "/",
            "secure" => isset($_SERVER["HTTPS"]),
            "httponly" => true,
            "samesite" => "Lax"
        ]);

        session_start();

        // Wenn neue Session → Metadata setzen
        if (!isset($_SESSION["_created"])) {
            $_SESSION["_created"] = time();
            $_SESSION["_last_regen"] = time();
        }

        self::autoRegenerate();
    }


    /**
     * Regeneriert regelmäßig die Session-ID zum Schutz vor Hijacking.
     */
    private static function autoRegenerate(): void {
        $interval = 60 * 30; // alle 30 Minuten regenerieren

        if (time() - ($_SESSION["_last_regen"] ?? 0) > $interval) {
            session_regenerate_id(true);
            $_SESSION["_last_regen"] = time();
        }
    }


    /**
     * Initialisiert alle Framework-Session-Werte aus Vars::init_session()
     */
    public static function init(): void {
        foreach (Vars::init_session() as $entry) {
            $_SESSION[$entry["session_name"]] = $entry["session_value"];
        }
    }


    /**
     * Holt den Wert einer Session-Variable
     */
    public static function get(string $name): mixed {
        return $_SESSION[$name] ?? null;
    }


    /**
     * Setzt oder überschreibt eine Session-Variable
     */
    public static function set(string $name, mixed $value): void {
        $_SESSION[$name] = $value;
    }


    /**
     * Prüft ob Session-Variable existiert
     */
    public static function exists(string $name): bool {
        return array_key_exists($name, $_SESSION);
    }


    /**
     * Löscht eine Session-Variable
     */
    public static function delete(string $name): void {
        unset($_SESSION[$name]);
    }


    /**
     * Zerstört die ganze Session (Logout)
     */
    public static function destroy(): void {
        session_unset();
        session_destroy();
    }
}
