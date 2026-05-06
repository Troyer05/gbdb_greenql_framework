<?php

class Tools {
    /**
     * Generiert ein (kryptografisch) sicheres Passwort
     *
     * @param int $length Länge des Passworts
     */
    public static function generatePassword(int $length): string {
        if ($length <= 0) {
            return '';
        }

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}|:<>?-=[];,./';
        $maxIndex = strlen($chars) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $idx = random_int(0, $maxIndex);
            $password .= $chars[$idx];
        }

        return $password;
    }

    /**
     * Testet eine Passwortstärke
     *
     * @return string Beschreibung, was ggf. noch fehlt
     */
    public static function testPasswordStrength(string $password): string {
        if (strlen($password) < 8) {
            return 'It would be good if the password had 8 characters or more.';
        }

        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            return 'It would be good to add both lowercase and uppercase characters.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'It would be good if the password had one or more numbers.';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'It would be good if the password contained a non-alphanumeric character.';
        }

        return 'This password is strong.';
    }

    /**
     * Sicheres WHOIS für eine Domain (shell_exec mit Sanitizing)
     *
     * @return string JSON mit success|error
     */
    public static function getDomainInfo(string $domain): mixed {
        $domain = trim(strtolower($domain));

        if (
            !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ||
            !preg_match('/^[a-z0-9.-]+$/', $domain)
        ) {
            return json_encode(["error" => "That domain does not exist."]);
        }

        if (!function_exists('shell_exec')) {
            return json_encode(["error" => "whois is not available on this system."]);
        }

        $cmd = 'whois ' . escapeshellarg($domain) . ' 2>/dev/null';
        $whois = shell_exec($cmd);

        if ($whois === null || $whois === false || $whois === '') {
            return json_encode(["error" => "Could not get whois data."]);
        }

        return json_encode(["success" => $whois]);
    }

    /**
     * Generiert eine inkrementelle ID (filebasiert, mit Locking)
     */
    public static function generateId(): int {
        $tmpFile = self::getFrameworkTempFile('_id.txt');

        self::ensureDir(dirname($tmpFile));

        $fp = @fopen($tmpFile, 'c+');

        if (!$fp) {
            // Fallback: zufällige ID
            return random_int(1, PHP_INT_MAX);
        }

        // Exklusiver Lock
        flock($fp, LOCK_EX);

        $contents = trim(stream_get_contents($fp));
        $lastId = ($contents !== '') ? (int)$contents : 0;
        $newId = $lastId + 1;

        // Datei zurücksetzen und neue ID schreiben
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)$newId);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $newId;
    }

    /**
     * Generiert einen Token (Dateibasierter Duplicate-Schutz)
     *
     * @param string $delimiter Trennzeichen zwischen Fragmenten
     * @param int $many Anzahl Tokens
     * @param int $fragments Anzahl an Fragmenten pro Token
     * @return array Generierte Tokens
     */
    public static function generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4): array {
        return self::generateTokenInternal($delimiter, $many, $fragments);
    }

    /**
     * Erweiterte Token-Variante (gleiche Logik, anderer historischer Pfad)
     */
    public static function generateTokenExt(string $delimiter = "-", int $many = 1, int $fragments = 4): array {
        // Für Kompatibilität gleiche Logik, gleicher Speicherort
        return self::generateTokenInternal($delimiter, $many, $fragments);
    }

    /**
     * IP → Land (nutzt Http::get statt rohem cURL)
     */
    public static function getIpCountry(string $ip): string {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return "Invalid IP.";
        }

        $url = 'https://api.country.is/' . urlencode($ip);
        $response = Http::get($url);

        if ($response === false) {
            return "Invalid IP.";
        }

        $json = json_decode($response, true);
        $country = $json['country'] ?? null;

        if (!$country || $country === "") {
            return "Invalid IP.";
        }

        return $country;
    }

    /**
     * IPv4-Ping (sicher, OS-aware)
     */
    public static function ping4(string $ip): string {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip . " nicht erreichbar!";
        }

        $escapedIp = escapeshellarg($ip);

        $cmd = (PHP_OS_FAMILY === 'Windows')
            ? "ping -n 1 $escapedIp"
            : "ping -c 1 $escapedIp 2>/dev/null";

        @exec($cmd, $output, $status);

        if ($status === 0) {
            return $ip . " erreichbar.";
        }

        return $ip . " nicht erreichbar!";
    }

    /**
     * IPv6-Ping (sicher, OS-aware)
     */
    public static function ping6(string $ip): string {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip . " nicht erreichbar!";
        }

        $escapedIp = escapeshellarg($ip);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "ping -n 1 -6 $escapedIp";
        } else {
            // Viele Systeme: ping -6, manche ping6 – wir versuchen ping -6
            $cmd = "ping -c 1 -6 $escapedIp 2>/dev/null";
        }

        @exec($cmd, $output, $status);

        if ($status === 0) {
            return $ip . " erreichbar.";
        }

        return $ip . " nicht erreichbar!";
    }

    /**
     * Erstellt einen QR Code (iframe Wrapper)
     */
    public static function qr(string $value, int $width, int $height): string {
        $width  = max(1, $width);
        $height = max(1, $height);

        $params = "?width=" . $width . "&height=" . $height . "&correctlevel=H";
        $params .= "&zielurl=" . urlencode($value);
        $style = "border: none; width: " . $width . "px; height: " . $height . "px;";

        return '<iframe style="' . htmlspecialchars($style, ENT_QUOTES) . '" src="assets/tool_apis/qrcode.api.php' . $params . '"></iframe>';
    }

    /**
     * Erstellt einen BAR Code (iframe Wrapper)
     */
    public static function bar(string $value, int $width, int $height = 175): string  {
        $width  = max(1, $width);
        $height = max(1, $height);

        $params = "?value=" . urlencode($value);
        $style = "border: none; width: " . $width . "px; height: " . $height . "px;";

        return '<iframe style="' . htmlspecialchars($style, ENT_QUOTES) . '" src="assets/tool_apis/barcode.api.php' . $params . '"></iframe>';
    }

    // =====================================================
    //  INTERNAL HELPER
    // =====================================================

    /**
     * Gemeinsame Token-Generierung (cryptographically secure)
     */
    private static function generateTokenInternal(string $delimiter, int $many, int $fragments): array {
        $tmpFile = self::getFrameworkTempFile('_tokens.txt');

        self::ensureDir(dirname($tmpFile));

        $tokens = [];
        $existing = [];

        // Bestehende Tokens laden (für Duplicate-Schutz)
        if (file_exists($tmpFile)) {
            $existing = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }

        $fp = @fopen($tmpFile, 'a+');

        if (!$fp) {
            // Fallback: trotzdem Tokens liefern, aber nicht persistieren
            for ($i = 0; $i < $many; $i++) {
                $tokens[] = self::buildToken($delimiter, $fragments);
            }

            return $tokens;
        }

        flock($fp, LOCK_EX);

        for ($i = 0; $i < $many; $i++) {
            do {
                $token = self::buildToken($delimiter, $fragments);
            } while (in_array($token, $existing, true));

            $existing[] = $token;
            $tokens[]   = $token;

            fwrite($fp, $token . PHP_EOL);
        }

        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $tokens;
    }

    /**
     * Baut ein einzelnes Token aus n Fragmenten
     */
    private static function buildToken(string $delimiter, int $fragments): string {
        $parts = [];

        for ($j = 0; $j < $fragments; $j++) {
            // 8 Hex-Zeichen pro Fragment (32-bit)
            $parts[] = bin2hex(random_bytes(4));
        }

        return implode($delimiter, $parts);
    }

    /**
     * Liefert den Pfad zum framework_temp-Verzeichnis (mit Legacy-Unterstützung)
     */
    private static function getFrameworkTempFile(string $filename): string {
        // Neuer, sauberer Pfad
        $base = rtrim(Vars::json_path(), '/\\') . '/framework_temp/';

        // Legacy Pfad (wie früher in deiner Klasse)
        $legacyBase = "../../" . rtrim(Vars::json_path(), '/\\') . '/framework_temp/';

        // Wenn Legacy-Verzeichnis existiert und neuer noch nicht → Legacy weiter verwenden
        if (!is_dir($base) && is_dir($legacyBase)) {
            return $legacyBase . $filename;
        }

        return $base . $filename;
    }

    /**
     * Stellt sicher, dass ein Verzeichnis existiert
     */
    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }
}
