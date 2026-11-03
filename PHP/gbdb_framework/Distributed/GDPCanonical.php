<?php

declare(strict_types=1);

/**
 * Kanonische JSON-Form für Hashes und Signaturen.
 *
 * Regeln (GDP/1): Objektschlüssel binär aufsteigend sortiert, Listen behalten
 * ihre Reihenfolge, UTF-8 ohne Escaping von Slashes und Unicode, Fließkommazahlen
 * mit Nachkommateil (JSON_PRESERVE_ZERO_FRACTION) in kürzester exakter Darstellung
 * (serialize_precision = -1). Dieselben Daten ergeben so auf jeder PHP-8.1+-
 * Installation dieselben Bytes.
 */
final class GDPCanonical {
    private const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    public static function encode(mixed $value) :string {
        $precision = ini_get('serialize_precision');
        ini_set('serialize_precision', '-1');

        try {
            return json_encode(self::normalize($value), self::FLAGS);
        } finally {
            ini_set('serialize_precision', (string) $precision);
        }
    }

    public static function hash(mixed $value) :string {
        return 'sha256:' . hash('sha256', self::encode($value));
    }

    public static function decode(string $json, int $depth = 64) :array {
        $value = json_decode($json, true, $depth, JSON_THROW_ON_ERROR);

        if (!is_array($value)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'JSON-Objekt erwartet.');
        }

        return $value;
    }

    private static function normalize(mixed $value) :mixed {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                ksort($value, SORT_STRING);
            }

            foreach ($value as $key => $entry) {
                $value[$key] = self::normalize($entry);
            }

            return $value;
        }

        if (is_object($value)) {
            return self::normalize(get_object_vars($value));
        }

        if (is_float($value) && !is_finite($value)) {
            throw new GDPProtocolException(GDPErrorCode::PROTOCOL_ERROR, 'Nicht endliche Zahlen sind in GDP nicht erlaubt.');
        }

        return $value;
    }

    /**
     * Neue, zeitlich sortierbare Operations-ID (UUIDv7).
     */
    public static function uuid7() :string {
        $milliseconds = (int) floor(microtime(true) * 1000);
        $bytes = random_bytes(16);
        $time = str_pad(dechex($milliseconds), 12, '0', STR_PAD_LEFT);
        $bytes = hex2bin($time) . substr($bytes, 6);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }

    public static function isUuid(string $value) :bool {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value);
    }

    /**
     * Technische Bezeichner: Knoten-, Instanz- und Replikat-IDs.
     */
    public static function isIdentifier(string $value) :bool {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,95}$/D', $value);
    }
}
