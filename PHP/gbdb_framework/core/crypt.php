<?php

class Crypt {
    private const METHOD = 'aes-256-cbc';
    private const LEGACY_IV = '1234567891011121';
    private const PREFIX = 'enc1.';

    private static function getKey(): string {
        return hash('sha256', Vars::cryptKey(), true);
    }

    private static function randomIV(): string {
        return random_bytes(openssl_cipher_iv_length(self::METHOD));
    }

    /**
     * encodes provided data
     * @param string $data
     * @return string
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

        $hmac = hash_hmac('sha256', $iv . $cipher, $key, true);

        // enc1.<IV>.<HMAC>.<cipher>
        $combined = self::PREFIX .
            base64_encode($iv) . "." .
            base64_encode($hmac) . "." .
            base64_encode($cipher);

        return strtr($combined, ['+' => '-', '/' => '_', '=' => '']);
    }

    /**
     * decodes encrypted data
     * @param string $data
     * @return bool|string|null
     */
    public static function decode(string $data): ?string {
        $data = strtr($data, ['-' => '+', '_' => '/']);

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

            $calcHmac = hash_hmac('sha256', $iv . $cipher, $key, true);

            if (!hash_equals($hmac, $calcHmac)) {
                return null;
            }

            return openssl_decrypt(
                $cipher,
                self::METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
        }

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
