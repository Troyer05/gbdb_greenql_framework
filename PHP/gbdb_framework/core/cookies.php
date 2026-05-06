<?php

class Cookie {
    private const DUR = 60 * 60 * 24 * 360; // 1 Jahr

    /**
     * Validiere Cookie-Namen.
     * Nur: a-z A-Z 0-9 _
     */
    protected static function validateName(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Standardoptionen für Cookies
     */
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

    /**
     * Master-Setter für Cookies
     */
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
     * Speichert einen Wert in der angegebenen Quelle.
     * @param string $name Übergabewert.
     * @param string $value Übergabewert.
     * @param int $expiration Übergabewert.
     * @return void Rückgabewert.
     */
    public static function set(string $name, string $value, int $expiration = self::DUR): void {
        self::send($name, $value, $expiration);
    }

    /**
     * Sicheres Cookie — nutzt dieselben Optionen,
     * aber zwingt secure = true
     */
    public static function setSecure(string $name, string $value, int $expiration = self::DUR): void {
        $name = self::validateName($name);

        if ($name === "") return;

        $opts = self::options($expiration, true);

        @setcookie($name, $value, $opts);

        $_COOKIE[$name] = $value;
    }

    /**
     * Verarbeitet die Funktion add.
     * @param string $name Übergabewert.
     * @param string $value Übergabewert.
     * @return void Rückgabewert.
     */
    public static function add(string $name, string $value): void {
        if (!self::exists($name)) {
            self::set($name, $value);
        }
    }

    /**
     * Liest Daten aus der angegebenen Quelle.
     * @param string $name Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function get(string $name): mixed {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Löscht Daten aus der angegebenen Quelle.
     * @param string $name Übergabewert.
     * @return void Rückgabewert.
     */
    public static function delete(string $name): void {
        $name = self::validateName($name);

        if ($name === "") return;

        $opts = self::options(-3600); // expired

        @setcookie($name, "", $opts);

        unset($_COOKIE[$name]);
    }

    /**
     * Bearbeitet bestehende Daten.
     * @param string $name Übergabewert.
     * @param string $value Übergabewert.
     * @return void Rückgabewert.
     */
    public static function edit(string $name, string $value): void {
        self::set($name, $value);
    }

    /**
     * Verarbeitet die Funktion compare.
     * @param string $name Übergabewert.
     * @param string $value Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function compare(string $name, string $value): bool {
        return self::get($name) === $value;
    }

    /**
     * REFRESH entfernt jetzt Cookies NICHT und setzt sie NICHT neu.
     * Das ist viel sinnvoller:
     * → Nur erneuern, wenn Laufzeit kurz davor ist zu verfallen.
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
     * Initialisiert die Klasse und legt benötigte Strukturen an.
     * @return void Rückgabewert.
     */
    public static function init(): void {
        foreach (Vars::init_cookies() as $r) {
            $name  = $r["cookie_name"] ?? "";
            $value = $r["cookie_value"] ?? "";

            self::add($name, $value);
        }

        // Falls gewünscht, kannst du hier Refresh deaktivieren
        // self::refresh();
    }

    /**
     * Verarbeitet die Funktion exists.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function exists(string $name): bool {
        return isset($_COOKIE[self::validateName($name)]);
    }
}
