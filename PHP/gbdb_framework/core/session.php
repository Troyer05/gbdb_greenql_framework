<?php

class Session {

    /**
     * start session if not started already and sets cookie to safe-cookies
     * @return void
     */
    public static function handler(): void {

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            "lifetime" => 360 * 24 * 60 * 60, // 360 days
            "path" => "/",
            "secure" => isset($_SERVER["HTTPS"]),
            "httponly" => true,
            "samesite" => "Lax"
        ]);

        session_start();

        if (!isset($_SESSION["_created"])) {
            $_SESSION["_created"] = time();
            $_SESSION["_last_regen"] = time();
        }

        self::autoRegenerate();
    }

    private static function autoRegenerate(): void {
        $interval = 60 * 30; // alle 30 Minuten regenerieren

        if (time() - ($_SESSION["_last_regen"] ?? 0) > $interval) {
            session_regenerate_id(true);
            $_SESSION["_last_regen"] = time();
        }

    }

    /**
     * initializes cookies defined in env
     * @return void
     */
    public static function init(): void {
        foreach (Vars::init_session() as $entry) {
            $_SESSION[$entry["session_name"]] = $entry["session_value"];
        }

    }

    /**
     * get data from session
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed {
        return $_SESSION[$name] ?? null;
    }

    /**
     * adds or updates a session data
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set(string $name, mixed $value): void {
        $_SESSION[$name] = $value;
    }

    /**
     * checks if session data exists
     * @param string $name
     * @return bool
     */
    public static function exists(string $name): bool {
        return array_key_exists($name, $_SESSION);
    }

    /**
     * delete session data
     * @param string $name
     * @return void
     */
    public static function delete(string $name): void {
        unset($_SESSION[$name]);
    }

    /**
     * terminate session
     * @return void
     */
    public static function destroy(): void {
        session_unset();
        session_destroy();
    }

}
