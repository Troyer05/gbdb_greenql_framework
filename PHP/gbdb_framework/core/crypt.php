<?php

class Crypt {
    private const METHOD = 'aes-256-cbc';
    private const LEGACY_IV = '1234567891011121'; // Für alte Daten
    private const PREFIX = 'enc1.'; // Neue Daten werden markiert

    /**
     * Liefert den binären Schlüssel
     */
    private static function getKey(): string {
        return hash('sha256', Vars::cryptKey(), true);
    }

    /**
     * Sicherer, zufälliger IV
     */
    private static function randomIV(): string {
        return random_bytes(openssl_cipher_iv_length(self::METHOD));
    }

    /**
     * Encode: kompatibel + sicher für neue Daten
     */
    public static function encode(string $data): string {
        $key = self::getKey();
        $iv  = self::randomIV();

        $cipher = openssl_encrypt(
            $data,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // HMAC schützt gegen Manipulation
        $hmac = hash_hmac('sha256', $iv . $cipher, $key, true);

        // Format:
        // enc1.<IV>.<HMAC>.<cipher>
        $combined = self::PREFIX .
            base64_encode($iv) . "." .
            base64_encode($hmac) . "." .
            base64_encode($cipher);

        // URL-safe
        return strtr($combined, ['+' => '-', '/' => '_', '=' => '']);
    }

    /**
     * Decode: unterstützt alte und neue Daten
     */
    public static function decode(string $data): ?string {
        // Rücktransformieren
        $data = strtr($data, ['-' => '+', '_' => '/']);

        // =============================
        // 1) NEW FORMAT? (enc1.)
        // =============================
        if (str_starts_with($data, self::PREFIX)) {
            $data = substr($data, strlen(self::PREFIX));
            $parts = explode('.', $data);

            if (count($parts) !== 3) {
                return null;
            }

            $iv     = base64_decode($parts[0]);
            $hmac   = base64_decode($parts[1]);
            $cipher = base64_decode($parts[2]);

            $key = self::getKey();

            // HMAC prüfen (Timing-safe)
            $calcHmac = hash_hmac('sha256', $iv . $cipher, $key, true);

            if (!hash_equals($hmac, $calcHmac)) {
                return null; // Manipuliert
            }

            return openssl_decrypt(
                $cipher,
                self::METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
        }

        // =============================
        // 2) LEGACY FORMAT – alte GBDB Daten
        // =============================
        $base = base64_decode($data);

        if ($base === false) {
            return null;
        }

        $key = self::getKey();

        $iv = substr(
            hash('sha256', self::LEGACY_IV, true),
            0,
            16
        );

        return openssl_decrypt(
            $base,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
