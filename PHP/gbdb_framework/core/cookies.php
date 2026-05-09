<?php

class Cookie {
    private const DUR = 60 * 60 * 24 * 360; // 1 year

    protected static function validateName(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    protected static function options(
        int $expiration,
        ?bool $secureOverride = null
    ): array {
        $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");

        // Automatische HTTPS / DEV-Behandlung
        $secure = $secureOverride ??
                  ($https && !Vars::__DEV__());

        return [
            "expires"  => time() + $expiration,
            "path"     => "/",
            "domain"   => "",          // leer = aktuelle Domain
            "secure"   => $secure,     // Cookie nur über https
            "httponly" => true,        // nicht in JS verfügbar
            "samesite" => "Lax",       // modern default
        ];
    }

    protected static function send(string $name, string $value, int $expiration): void {
        $name = self::validateName($name);

        if ($name === "") return;

        $opts = self::options($expiration);

        // PHP 7.3+ Syntax: setcookie(name, value, optionsArray)
        @setcookie($name, $value, $opts);

        // Lokale $_COOKIE synchron halten
        $_COOKIE[$name] = $value;
    }

    /**
     * sets or updates a cookie
     * @param string $name
     * @param string $value
     * @param int $expiration
     * @return void
     */
    public static function set(string $name, string $value, int $expiration = self::DUR): void {
        self::send($name, $value, $expiration);
    }

    /**
     * sets or updates a secure
     * @param string $name
     * @param string $value
     * @param int $expiration
     * @return void
     */
    public static function setSecure(string $name, string $value, int $expiration = self::DUR): void {
        $name = self::validateName($name);

        if ($name === "") return;

        $opts = self::options($expiration, true);

        @setcookie($name, $value, $opts);

        $_COOKIE[$name] = $value;
    }

    /**
     * Adds a new cookie if not exists
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function add(string $name, string $value): void {
        if (!self::exists($name)) {
            self::set($name, $value);
        }

    }

    /**
     * get value of a cookie
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * deletes a cookie right now
     * @param string $name
     * @return void
     */
    public static function delete(string $name): void {
        $name = self::validateName($name);

        if ($name === "") return;

        $opts = self::options(-3600); // expired

        @setcookie($name, "", $opts);

        unset($_COOKIE[$name]);
    }

    /**
     * updates a cookie
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function edit(string $name, string $value): void {
        self::set($name, $value);
    }

    /**
     * renew cookie-lifetime to $thresholdSeconds from now
     * @param int $thresholdSeconds
     * @return void
     */
    public static function refresh(int $thresholdSeconds = 3600): void {
        foreach ($_COOKIE as $name => $value) {
            $nameClean = self::validateName($name);

            if ($nameClean === "") continue;

            // Wir kennen das Ablaufdatum nicht → nur erneuern, wenn sinnvoll
            self::set($nameClean, $value);
        }

    }

    /**
     * initializes cookies from env
     * @return void
     */
    public static function init(): void {
        foreach (Vars::init_cookies() as $r) {
            $name  = $r["cookie_name"] ?? "";
            $value = $r["cookie_value"] ?? "";

            self::add($name, $value);
        }

    }

    /**
     * does this cookie exists?
     * @param string $name
     * @return bool
     */
    public static function exists(string $name): bool {
        return isset($_COOKIE[self::validateName($name)]);
    }

}
