<?php

declare(strict_types=1);

/**
 * HTTP für den Updater: ohne Weiterleitungen, mit Größen- und Zeitgrenzen und
 * TLS-Zertifikatsprüfung. Fehler werden als Ergebnis gemeldet, nie als Absturz.
 */
final class GBDBUpdateHttp {
    private function __construct() {
    }

    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    public static function get(string $url, int $maxBytes, ?int $timeout = null) :array {
        return self::request('GET', $url, null, $maxBytes, $timeout, null);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    public static function postJson(string $url, array $payload, int $maxBytes = 65536, ?int $timeout = null) :array {
        return self::request('POST', $url, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $maxBytes, $timeout, null);
    }

    /**
     * Download direkt in eine Datei.
     *
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    public static function download(string $url, string $target, int $maxBytes, ?int $timeout = null) :array {
        return self::request('GET', $url, null, $maxBytes, $timeout, $target);
    }

    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    private static function request(string $method, string $url, ?string $body, int $maxBytes, ?int $timeout, ?string $target) :array {
        $settings = GBDBUpdateSettings::all();
        $timeout ??= (int) $settings['http_timeout'];
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ($scheme !== 'http' || $settings['force_ssl'])) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Nur HTTPS-Adressen sind erlaubt.'];
        }

        $agent = 'GBDB-Framework-Updater/' . GBDBUpdateVersion::current() . ' PHP/' . PHP_VERSION;

        return function_exists('curl_init')
            ? self::curl($method, $url, $body, $maxBytes, $timeout, $target, $agent, $scheme === 'https')
            : self::stream($method, $url, $body, $maxBytes, $timeout, $target, $agent);
    }

    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    private static function curl(string $method, string $url, ?string $body, int $maxBytes, int $timeout, ?string $target, string $agent, bool $https) :array {
        $handle = curl_init($url);
        $file = $target !== null ? @fopen($target, 'wb') : null;

        if ($handle === false || $file === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'HTTP-Anfrage konnte nicht vorbereitet werden.'];
        }

        $received = 0;
        $buffer = '';
        $tooLarge = false;
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => $agent,
            CURLOPT_HTTPHEADER => $body !== null ? ['Content-Type: application/json', 'Accept: application/json'] : ['Accept: */*'],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$received, &$buffer, &$tooLarge, $maxBytes, $file) :int {
                $received += strlen($chunk);

                if ($received > $maxBytes) {
                    $tooLarge = true;

                    return 0;
                }

                if ($file !== null) {
                    return (int) fwrite($file, $chunk);
                }

                $buffer .= $chunk;

                return strlen($chunk);
            },
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $options[CURLOPT_PROTOCOLS] = $https ? CURLPROTO_HTTPS : (CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        if ($https) {
            $options[CURLOPT_SSL_VERIFYPEER] = true;
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = (string) $body;
        }

        curl_setopt_array($handle, $options);
        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = $result === false ? curl_error($handle) : '';
        curl_close($handle);

        if ($file !== null) {
            fclose($file);
        }

        if ($tooLarge) {
            return ['ok' => false, 'status' => $status, 'body' => '', 'error' => 'Antwort ist größer als erlaubt.'];
        }

        if ($error !== '') {
            return ['ok' => false, 'status' => $status, 'body' => '', 'error' => 'Verbindung fehlgeschlagen: ' . $error];
        }

        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => $buffer, 'error' => $status >= 200 && $status < 300 ? '' : 'HTTP-Status ' . $status];
    }

    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    private static function stream(string $method, string $url, ?string $body, int $maxBytes, int $timeout, ?string $target, string $agent) :array {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $body !== null ? "Content-Type: application/json\r\nAccept: application/json\r\n" : "Accept: */*\r\n",
                'content' => (string) $body,
                'timeout' => $timeout,
                'follow_location' => 0,
                'max_redirects' => 0,
                'ignore_errors' => true,
                'user_agent' => $agent,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false],
        ]);
        $source = @fopen($url, 'rb', false, $context);

        if ($source === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Verbindung fehlgeschlagen.'];
        }

        $meta = stream_get_meta_data($source);
        $status = 0;

        foreach ((array) ($meta['wrapper_data'] ?? []) as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', (string) $line, $match)) {
                $status = (int) $match[1];
            }
        }

        $file = $target !== null ? @fopen($target, 'wb') : null;
        $buffer = '';
        $received = 0;

        while (!feof($source)) {
            $chunk = (string) fread($source, 65536);
            $received += strlen($chunk);

            if ($received > $maxBytes) {
                fclose($source);

                if ($file) {
                    fclose($file);
                }

                return ['ok' => false, 'status' => $status, 'body' => '', 'error' => 'Antwort ist größer als erlaubt.'];
            }

            if ($file) {
                fwrite($file, $chunk);
            } else {
                $buffer .= $chunk;
            }

            if (($meta = stream_get_meta_data($source)) && !empty($meta['timed_out'])) {
                fclose($source);

                return ['ok' => false, 'status' => $status, 'body' => '', 'error' => 'Zeitüberschreitung.'];
            }
        }

        fclose($source);

        if ($file) {
            fclose($file);
        }

        $ok = $status >= 200 && $status < 300;

        return ['ok' => $ok, 'status' => $status, 'body' => $buffer, 'error' => $ok ? '' : 'HTTP-Status ' . $status];
    }
}
