<?php

class Http {

    /**
     * fetches an api with GET-methos
     * @param string $url
     * @param array $headers
     * @param int $timeout
     * @return bool|string
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

    /**
     * fetches an api with POST-method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param int $timeout
     * @return bool|string
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
     * uses my private-open mail API to send a mail (the api basicly just uses PHPMailer)
     * @param array $mail
     * @return array{error: string, response: bool|string|array{error: string}|bool}
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

        $response = self::post($url, $mail, $headers);

        if ($response === false) {
            return ["error" => "No response from mail server"];
        }

        if (trim($response) === "ok") {
            return true;
        }

        return ["error" => "Unexpected response from server", "response" => $response];
    }

    /**
     * json response to a browser
     * @param array $data
     * @param int $status
     * @return never
     */
    public static function jsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * redirect
     * @param string $url
     * @param int $status
     * @return never
     */
    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: $url");

        exit;
    }

    /**
     * get all headers
     * @return array
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

    /**
     * gets a request method
     * @return string
     */
    public static function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * is it valid json?
     * @return bool
     */
    public static function isJson(): bool {
        $ctype = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

        return str_contains($ctype, 'application/json');
    }

    /**
     * gets body of request
     * @return array
     */
    public static function jsonInput(): array {
        $raw = file_get_contents('php://input');
        $parsed = json_decode($raw, true);

        return is_array($parsed) ? $parsed : [];
    }

    private static function formatHeaders(array $headers): array {
        $result = [];

        foreach ($headers as $k => $v) {
            $result[] = "$k: $v";
        }

        return $result;
    }

    private static function implodeHeaders(array $headers): string {
        $r = "";

        foreach ($headers as $k => $v) {
            $r .= "$k: $v\r\n";
        }

        return $r;
    }

}
