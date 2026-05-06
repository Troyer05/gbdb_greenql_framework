<?php

class ReCaptcha {

    /**
     * Verarbeitet die Funktion load js api.
     * @return void Rückgabewert.
     */
    public function loadJsApi(): void {
        echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    /**
     * Name der POST Variable
     */
    public static function postName(): string {
        return "g-recaptcha-response";
    }

    /**
     * Gibt das HTML-Element für reCAPTCHA aus
     */
    public static function checkBox(string $callbackJs = ""): string {
        $wc = Vars::reCaptcha_website_key();
        $cb = htmlspecialchars($callbackJs, ENT_QUOTES);

        return '<div class="g-recaptcha" data-sitekey="' . $wc . '" data-callback="' . $cb . '" data-action="FORM"></div>';
    }

    /**
     * Verifiziert das reCAPTCHA Token
     * @param string|null $token Das POST Token von Google
     * @return bool TRUE wenn verifiziert
     */
    public static function verify(?string $token): bool {
        if (empty($token)) {
            return false;
        }

        $url = "https://www.google.com/recaptcha/api/siteverify";

        $payload = [
            "secret"   => Vars::reCaptcha_secret_key(),
            "response" => $token,
            "remoteip" => Vars::client_ip()
        ];

        // Anfrage an Google
        $response = Http::post($url, $payload);

        if ($response === false) {
            error_log("[ReCaptcha::verify] Fehler bei Anfrage an Google");
            return false;
        }

        $json = json_decode($response, true);

        if (!is_array($json)) {
            error_log("[ReCaptcha::verify] Ungültige Google Antwort: " . $response);
            return false;
        }

        return !empty($json["success"]);
    }
}
