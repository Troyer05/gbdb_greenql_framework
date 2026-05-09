<?php
/**
 * This is a collection of tools, i couldn't really put into own categorys/classes
 */

class Tools {
    /**
     * generates a password
     * @param int $length
     * @return string
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
     * tests the strength of a password
     * @param string $password
     * @return string
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
     * get domain info by whois-request
     * @param string $domain
     * @return bool|string
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
     * generates an id
     * @return int
     */
    public static function generateId(): int {
        $tmpFile = self::getFrameworkTempFile('_id.txt');

        self::ensureDir(dirname($tmpFile));

        $fp = @fopen($tmpFile, 'c+');

        if (!$fp) {
            return random_int(1, PHP_INT_MAX);
        }

        flock($fp, LOCK_EX);

        $contents = trim(stream_get_contents($fp));
        $lastId = ($contents !== '') ? (int)$contents : 0;
        $newId = $lastId + 1;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)$newId);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $newId;
    }

    /**
     * generates a token
     * @param string $delimiter
     * @param int $many
     * @param int $fragments
     * @return array
     */
    public static function generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4): array {
        return self::generateTokenInternal($delimiter, $many, $fragments);
    }

    /**
     * gets country to an ip
     * @param string $ip
     * @return string
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
     * ipv4 ping
     * @param string $ip
     * @return string
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
     * ipv6 ping
     * @param string $ip
     * @return string
     */
    public static function ping6(string $ip): string {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip . " nicht erreichbar!";
        }

        $escapedIp = escapeshellarg($ip);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "ping -n 1 -6 $escapedIp";
        } else {
            $cmd = "ping -c 1 -6 $escapedIp 2>/dev/null";
        }

        @exec($cmd, $output, $status);

        if ($status === 0) {
            return $ip . " erreichbar.";
        }

        return $ip . " nicht erreichbar!";
    }

    /**
     * creates a qr-code in an iframe
     * @param string $value
     * @param int $width
     * @param int $height
     * @return string
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
     * creates bar-code in an iframe
     * @param string $value
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function bar(string $value, int $width, int $height = 175): string  {
        $width  = max(1, $width);
        $height = max(1, $height);

        $params = "?value=" . urlencode($value);
        $style = "border: none; width: " . $width . "px; height: " . $height . "px;";

        return '<iframe style="' . htmlspecialchars($style, ENT_QUOTES) . '" src="assets/tool_apis/barcode.api.php' . $params . '"></iframe>';
    }

    private static function generateTokenInternal(string $delimiter, int $many, int $fragments): array {
        $tmpFile = self::getFrameworkTempFile('_tokens.txt');

        self::ensureDir(dirname($tmpFile));

        $tokens = [];
        $existing = [];

        if (file_exists($tmpFile)) {
            $existing = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }

        $fp = @fopen($tmpFile, 'a+');

        if (!$fp) {
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

    private static function buildToken(string $delimiter, int $fragments): string {
        $parts = [];

        for ($j = 0; $j < $fragments; $j++) {
            $parts[] = bin2hex(random_bytes(4));
        }

        return implode($delimiter, $parts);
    }

    private static function getFrameworkTempFile(string $filename): string {
        $base = rtrim(Vars::json_path(), '/\\') . '/framework_temp/';

        return $base . $filename;
    }

    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

    }

}
