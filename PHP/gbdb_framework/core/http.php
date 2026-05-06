<?php

class Http {

    /** Führt einen HTTP-GET-Request aus */
    /**
     * Liest Daten aus der angegebenen Quelle.
     * @param string $url Übergabewert.
     * @param array $headers Übergabewert.
     * @param int $timeout Übergabewert.
     * @return string|false Rückgabewert.
     */
    public static function get(string $url, array $headers = [], int $timeout = 10): string|false {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FAILONERROR => false,
                CURLOPT_HTTPHEADER => self::formatHeaders($headers)
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                error_log("[Http::get] cURL Error: " . curl_error($ch));
            }

            curl_close($ch);

            return $response;
        }

        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => self::implodeHeaders($headers),
                'timeout' => $timeout
            ]
        ];

        return @file_get_contents($url, false, stream_context_create($opts));
    }

    /** Führt einen HTTP-POST-Request aus */
    /**
     * Verarbeitet die Funktion post.
     * @param string $url Übergabewert.
     * @param array $data Übergabewert.
     * @param array $headers Übergabewert.
     * @param int $timeout Übergabewert.
     * @return string|false Rückgabewert.
     */
    public static function post(string $url, array $data = [], array $headers = [], int $timeout = 10): string|false {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            $headers["Content-Type"] = "application/json";
            $headers["Content-Length"] = strlen($json);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_FAILONERROR => false,
                CURLOPT_HTTPHEADER => self::formatHeaders($headers)
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                error_log("[Http::post] cURL Error: " . curl_error($ch));
            }

            curl_close($ch);

            return $response;
        }

        $headers["Content-Type"] = "application/json";

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => self::implodeHeaders($headers),
                'content' => $json,
                'timeout' => $timeout
            ]
        ];

        return @file_get_contents($url, false, stream_context_create($opts));
    }

    /**
     * Verarbeitet die Funktion send mail.
     * @param array $mail Übergabewert.
     * @return bool|array Rückgabewert.
     */
    public static function sendMail(array $mail): bool|array {
        $required = ["to_name", "to_email", "from_name", "from_email", "subject", "mail_content"];

        foreach ($required as $key) {
            if (!isset($mail[$key]) || trim($mail[$key]) === "") {
                return ["error" => "Missing required field: $key"];
            }
        }

        $url = "https://museumqr.de/mailing/index.php";

        $headers = [
            "Accept" => "application/json"
        ];

        // Mail abschicken
        $response = self::post($url, $mail, $headers);

        // Fehler bei Request?
        if ($response === false) {
            return ["error" => "No response from mail server"];
        }

        // Deine API gibt "ok" zurück
        if (trim($response) === "ok") {
            return true;
        }

        // Alles andere → Fehler
        return ["error" => "Unexpected response from server", "response" => $response];
    }


    /** JSON Antwort an Browser */
    /**
     * Verarbeitet die Funktion json response.
     * @param array $data Übergabewert.
     * @param int $status Übergabewert.
     * @return void Rückgabewert.
     */
    public static function jsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /** Weiterleitung */
    /**
     * Verarbeitet die Funktion redirect.
     * @param string $url Übergabewert.
     * @param int $status Übergabewert.
     * @return void Rückgabewert.
     */
    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: $url");

        exit;
    }

    /** Request header */
    /**
     * Verarbeitet die Funktion get headers.
     * @return array Rückgabewert.
     */
    public static function getHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace('_', ' ', substr($name, 5));
                $key = str_replace(' ', '-', ucwords(strtolower($key)));

                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /** GET / POST */
    /**
     * Verarbeitet die Funktion method.
     * @return string Rückgabewert.
     */
    public static function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** JSON? */
    /**
     * Verarbeitet die Funktion is json.
     * @return bool Rückgabewert.
     */
    public static function isJson(): bool {
        $ctype = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        return str_contains($ctype, 'application/json');
    }

    /** JSON Body */
    /**
     * Verarbeitet die Funktion json input.
     * @return array Rückgabewert.
     */
    public static function jsonInput(): array {
        $raw = file_get_contents('php://input');
        $parsed = json_decode($raw, true);

        return is_array($parsed) ? $parsed : [];
    }

    /** Header-Tools */
    /**
     * Verarbeitet die Funktion format headers.
     * @param array $headers Übergabewert.
     * @return array Rückgabewert.
     */
    private static function formatHeaders(array $headers): array {
        $result = [];

        foreach ($headers as $k => $v) {
            $result[] = "$k: $v";
        }

        return $result;
    }

    /**
     * Verarbeitet die Funktion implode headers.
     * @param array $headers Übergabewert.
     * @return string Rückgabewert.
     */
    private static function implodeHeaders(array $headers): string {
        $r = "";

        foreach ($headers as $k => $v) {
            $r .= "$k: $v\r\n";
        }

        return $r;
    }
}
