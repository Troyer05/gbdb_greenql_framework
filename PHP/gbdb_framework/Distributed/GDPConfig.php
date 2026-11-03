<?php

declare(strict_types=1);

/**
 * Lesezugriff auf den Bereich GDP der zentralen .framework.env.php.
 *
 * Jeder Wert kann wie bei GBDBConfig per Umgebungsvariable überschrieben
 * werden, etwa GBDB_GDP_ENABLED=1 oder GBDB_GDP_HUB_URL=https://…
 */
final class GDPConfig {
    public const PROTOCOL = 'GDP/1';
    public const PROTOCOL_MAJOR = 1;
    public const PROTOCOL_MINOR = 0;

    private const DEFAULTS = [
        'enabled' => false,
        'role' => 'replica',
        'node_name' => '',
        'hub_url' => '',
        'allow_insecure_http' => false,
        'sync_mode' => 'embedded',
        'sync_batch_size' => 100,
        'sync_max_bytes' => 1048576,
        'sync_timeout' => 5,
        'embedded_interval' => 30,
        'embedded_budget_seconds' => 2,
        'offline_enabled' => true,
        'clock_skew_seconds' => 300,
        'max_request_bytes' => 8388608,
        'retention_days' => 30,
        'snapshot_chunk_bytes' => 262144,
        'trigger_token' => '',
        'pairing_ttl' => 900,
    ];

    private static ?bool $enabled = null;

    public static function get(string $name) :mixed {
        return GBDBConfig::get('gdp', $name, self::DEFAULTS[$name] ?? null);
    }

    public static function enabled() :bool {
        return self::$enabled ??= self::flag('enabled');
    }

    /**
     * Nur für Tests und den Worker: Konfiguration erneut lesen.
     */
    public static function reset() :void {
        self::$enabled = null;
    }

    public static function flag(string $name) :bool {
        return GBDBConfig::flag('gdp', $name, (bool) (self::DEFAULTS[$name] ?? false));
    }

    public static function int(string $name, int $min, int $max) :int {
        return max($min, min($max, (int) self::get($name)));
    }

    public static function role() :GDPNodeRole {
        return GDPNodeRole::tryFrom(strtolower(trim((string) self::get('role')))) ?? GDPNodeRole::REPLICA;
    }

    public static function syncMode() :string {
        $mode = strtolower(trim((string) self::get('sync_mode')));

        return in_array($mode, ['embedded', 'worker', 'manual'], true) ? $mode : 'embedded';
    }

    /**
     * Basis-URL des Hub-Endpunkts, z. B. https://backend.example.com/gdp.php
     */
    public static function hubUrl() :string {
        $url = rtrim(trim((string) self::get('hub_url')), '/');

        if ($url !== '' && !str_ends_with($url, '.php')) {
            $url .= '/gdp.php';
        }

        return $url;
    }

    /**
     * Prüft eine Hub-URL: HTTPS ist Pflicht, HTTP nur mit ausdrücklichem Testschalter.
     */
    public static function assertTransportUrl(string $url) :void {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (!is_array($parts) || ($parts['host'] ?? '') === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'Die Hub-URL ist ungültig.');
        }

        if ($scheme === 'https') {
            return;
        }

        if ($scheme === 'http' && self::flag('allow_insecure_http')) {
            return;
        }

        throw new GDPException(GDPErrorCode::NOT_CONFIGURED, 'GDP verlangt HTTPS. HTTP ist nur mit allow_insecure_http für lokale Tests erlaubt.');
    }
}
