<?php

declare(strict_types=1);

/**
 * Transportschnittstelle (Kapitel 23). GDP/1 nutzt HTTPS mit JSON.
 */
interface GDPTransport {
    public function call(string $endpoint, array $body, bool $authenticated = true, array $extraHeaders = [], ?array $credential = null) :array;

    public function checkHealth() :array;
}

/**
 * HTTPS-Client eines Replikats. Zertifikatsprüfung ist immer aktiv; reines HTTP
 * nur mit allow_insecure_http (lokale Tests) und wird in der UI angezeigt.
 */
final class GDPHttpTransport implements GDPTransport {
    public const ENDPOINTS = [
        'pair/start', 'pair/complete', 'handshake', 'sync/push', 'sync/pull', 'sync/ack',
        'snapshot/manifest', 'snapshot/chunk', 'credential/rotate', 'credential/confirm', 'health', 'trigger',
    ];

    private const MAX_RESPONSE_BYTES = 33554432;

    public function __construct(private readonly string $baseUrl) {
        GDPConfig::assertTransportUrl($baseUrl);
    }

    public static function canonicalPath(string $endpoint) :string {
        return '/gdp/v1/' . $endpoint;
    }

    /**
     * Ruft einen Hub-Endpunkt auf und prüft bei authentifizierten Aufrufen die Antwortsignatur.
     */
    public function call(string $endpoint, array $body, bool $authenticated = true, array $extraHeaders = [], ?array $credential = null) :array {
        if (!in_array($endpoint, self::ENDPOINTS, true)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Unbekannter GDP-Endpunkt.');
        }

        $json = GDPCanonical::encode(['protocol' => GDPConfig::PROTOCOL] + $body);
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'] + $extraHeaders;

        if ($authenticated) {
            $credential ??= GDPCredentials::hubKey();

            if ($credential === null) {
                throw new GDPAuthorizationException(GDPErrorCode::NOT_CONFIGURED, 'Dieser Knoten ist mit keinem Hub gekoppelt.');
            }

            $headers += GDPAuthenticator::requestHeaders('POST', self::canonicalPath($endpoint), $json, GDPNodeIdentity::id(), $credential['key_id'], $credential['secret']);
        } else {
            $credential = null;
            $headers['X-GDP-Protocol'] = GDPConfig::PROTOCOL;
        }

        [$status, $responseHeaders, $raw] = $this->send($this->url($endpoint), $json, $headers);

        // Erfolgsantworten müssen signiert sein. Unsignierte Fehler ändern keinen Zustand.
        if ($credential !== null && $status < 400) {
            $signature = (string) ($responseHeaders['x-gdp-response-signature'] ?? '');
            $expected = GDPAuthenticator::responseSignature($credential['secret'], $headers['X-GDP-Nonce'], $raw);

            if ($signature === '' || !hash_equals($expected, $signature)) {
                throw new GDPAuthorizationException(GDPErrorCode::AUTHENTICATION_FAILED, 'Antwort des Hubs ist nicht korrekt signiert.', ['endpoint' => $endpoint, 'status' => $status]);
            }
        }

        try {
            $response = GDPCanonical::decode($raw);
        } catch (JsonException | GDPException $exception) {
            throw new GDPConnectionException(GDPErrorCode::CONNECTION_UNAVAILABLE, 'Ungültige Antwort vom Hub.', ['endpoint' => $endpoint, 'status' => $status], $exception);
        }

        if ($status >= 400 || ($response['success'] ?? false) !== true) {
            throw self::remoteError($response, $status, $endpoint);
        }

        return $response;
    }

    public function checkHealth() :array {
        return $this->call('health', [], false);
    }

    /**
     * Unauthentifizierter Austausch mit Zugriff auf die Antwort-Header (Pairing).
     *
     * @return array{status:int, headers:array<string,string>, raw:string, body:array}
     */
    public function exchange(string $endpoint, array $payload, array $headers) :array {
        if (!in_array($endpoint, self::ENDPOINTS, true)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Unbekannter GDP-Endpunkt.');
        }

        $json = GDPCanonical::encode($payload);
        [$status, $responseHeaders, $raw] = $this->send($this->url($endpoint), $json, ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-GDP-Protocol' => GDPConfig::PROTOCOL] + $headers);

        try {
            $body = GDPCanonical::decode($raw);
        } catch (JsonException | GDPException $exception) {
            throw new GDPConnectionException(GDPErrorCode::CONNECTION_UNAVAILABLE, 'Ungültige Antwort vom Hub.', ['endpoint' => $endpoint, 'status' => $status], $exception);
        }

        if ($status >= 400 || ($body['success'] ?? false) !== true) {
            throw self::remoteError($body, $status, $endpoint);
        }

        return ['status' => $status, 'headers' => $responseHeaders, 'raw' => $raw, 'body' => $body];
    }

    private function url(string $endpoint) :string {
        return $this->baseUrl . (str_contains($this->baseUrl, '?') ? '&' : '?') . 'r=' . rawurlencode('v1/' . $endpoint);
    }

    private static function remoteError(array $response, int $status, string $endpoint) :GDPException {
        $error = is_array($response['error'] ?? null) ? $response['error'] : [];
        $code = GDPErrorCode::tryFrom((string) ($error['code'] ?? '')) ?? ($status === 429 ? GDPErrorCode::RATE_LIMITED : GDPErrorCode::CONNECTION_UNAVAILABLE);
        $message = mb_substr((string) ($error['message'] ?? 'Hub meldet einen Fehler.'), 0, 300);
        $context = ['endpoint' => $endpoint, 'status' => $status, 'remote' => is_array($error['context'] ?? null) ? $error['context'] : []];

        return match ($code) {
            GDPErrorCode::AUTHENTICATION_FAILED, GDPErrorCode::AUTHORIZATION_DENIED, GDPErrorCode::PAIRING_EXPIRED => new GDPAuthorizationException($code, $message, $context),
            GDPErrorCode::SCHEMA_MISMATCH => new GDPSchemaMismatchException($code, $message, $context),
            GDPErrorCode::POLICY_VIOLATION, GDPErrorCode::LEASE_EXPIRED => new GDPPolicyViolationException($code, $message, $context),
            GDPErrorCode::SNAPSHOT_REQUIRED, GDPErrorCode::CHECKPOINT_MISMATCH => new GDPCheckpointMismatchException($code, $message, $context),
            GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED => new GDPSnapshotException($code, $message, $context),
            GDPErrorCode::INTEGRITY_BLOCKED, GDPErrorCode::DUPLICATE_PAYLOAD_MISMATCH, GDPErrorCode::VERSION_CONFLICT, GDPErrorCode::SEQUENCE_GAP => new GDPIntegrityException($code, $message, $context),
            GDPErrorCode::CONNECTION_UNAVAILABLE, GDPErrorCode::RATE_LIMITED => new GDPConnectionException($code, $message, $context),
            default => new GDPException($code, $message, $context),
        };
    }

    /**
     * @return array{0:int, 1:array<string,string>, 2:string}
     */
    private function send(string $url, string $body, array $headers) :array {
        $timeout = GDPConfig::int('sync_timeout', 1, 120);
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        if (function_exists('curl_init')) {
            $responseHeaders = [];
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $lines,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout * 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | (GDPConfig::flag('allow_insecure_http') ? CURLPROTO_HTTP : 0),
                CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders) :int {
                    $parts = explode(':', $line, 2);

                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }

                    return strlen($line);
                },
                CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => static fn($curl, $downloadTotal, $downloaded) :int => $downloaded > self::MAX_RESPONSE_BYTES ? 1 : 0,
            ]);
            $raw = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $error = curl_errno($handle);
            curl_close($handle);

            if ($raw === false || $error !== 0 || $status === 0) {
                throw new GDPConnectionException(GDPErrorCode::CONNECTION_UNAVAILABLE, 'Hub nicht erreichbar.', ['curl_error' => $error]);
            }

            return [$status, $responseHeaders, (string) $raw];
        }

        $context = stream_context_create([
            'http' => ['method' => 'POST', 'header' => implode("\r\n", $lines), 'content' => $body, 'timeout' => $timeout * 3, 'ignore_errors' => true, 'follow_location' => 0],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $context, 0, self::MAX_RESPONSE_BYTES);
        $responseHeaders = [];
        $status = 0;

        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $matches)) {
                $status = (int) $matches[1];
            } elseif (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            }
        }

        if ($raw === false || $status === 0) {
            throw new GDPConnectionException(GDPErrorCode::CONNECTION_UNAVAILABLE, 'Hub nicht erreichbar.');
        }

        return [$status, $responseHeaders, $raw];
    }
}
