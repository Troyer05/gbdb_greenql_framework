<?php

function resp(int $status, mixed $data) {
    http_response_code($status);

    echo json_encode([
        "ok"     => $status >= 200 && $status < 300,
        "status" => $status,
        "data"   => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function test_param(array $params, array $body) {
    foreach ($params as $p) {
        if (!isset($body[$p])) {
            resp(400, "Param '$p' not provided.");
        }
    }
}

function general_auth($body, $method) {
    if ($method != "POST") {
        resp(405, "Request Method blocked.");
    }

    test_param(["sauth"], $body);
    test_param(["token"], $body);

    if ($body["sauth"] != hash('sha256', Vars::srvp_static_key())) {
        resp(401, "Static auth failed.");
    }

    if (!test_token($body["token"])) {
        resp(401, "Token auth failed.");
    }

    delete_token($body["token"]);
}


function GBDB_DB_ARCH(): string {
    if (class_exists('Vars') && method_exists('Vars', 'db_arch')) {
        return Vars::db_arch();
    }

    if (defined('DB_ARCH')) {
        $arch = strtoupper((string)DB_ARCH);
        return in_array($arch, ['GBDB', 'SQL'], true) ? $arch : 'GBDB';
    }

    return 'GBDB';
}

function DB_DRIVER(array $ctx = []): string {
    $cleaner = class_exists("GreenQL") ? ["GreenQL", "cleanName"] : null;
    $instance = $cleaner ? $cleaner((string)($ctx["instance"] ?? "")) : preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($ctx["instance"] ?? ""));

    if ($instance !== "" && class_exists("GBDB")) {
        GBDB::setInstance($instance);
        return "GBDB";
    }

    return "GBDB";
}

function DB_CTX_FROM_BODY(array $body): array {
    $ctx = isset($body["ctx"]) && is_array($body["ctx"]) ? $body["ctx"] : [];

    if (isset($body["instance"]) && (string)$body["instance"] !== "") {
        $ctx["instance"] = class_exists("GreenQL") ? GreenQL::cleanName((string)$body["instance"]) : (preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$body["instance"]) ?? "");
    }

    return $ctx;
}

function DB_GET($db, $table, $filter = false, $where = "", $is = "", array $ctx = []) {
    if (GBDB_DB_ARCH() === "SQL") {
        SQL::connect();

        if ($filter) {
            return SQL::select($table, "*", $where, "'$is'");
        }

        return SQL::select($table);
    }

    $driver = DB_DRIVER($ctx);
    return $driver::getData($db, $table, $filter, $where, $is);
}

function DB_PUT($db, $table, $data, array $ctx = []) {
    if (GBDB_DB_ARCH() === "SQL") {
        SQL::connect();
        return SQL::insert($table, $data);
    }

    $driver = DB_DRIVER($ctx);
    return $driver::insertData($db, $table, $data);
}

function DB_EDIT($db, $table, $where, $is, $data, array $ctx = []) {
    if (GBDB_DB_ARCH() === "SQL") {
        SQL::connect();
        return SQL::update($table, $data, $where, $is);
    }

    $driver = DB_DRIVER($ctx);
    return $driver::editData($db, $table, $where, $is, $data);
}

function DB_DELETE($db, $table, $where, $is, array $ctx = []) {
    if (GBDB_DB_ARCH() === "SQL") {
        SQL::connect();
        return SQL::delete($table, $where, $is);
    }

    $driver = DB_DRIVER($ctx);
    return $driver::deleteData($db, $table, $where, $is);
}

function DB_QUERY($query, array $ctx = [], array $params = []) {
    if (GBDB_DB_ARCH() === "SQL") {
        resp(400, "Query mode is only available for GBDB.");
    }

    if (isset($ctx["instance"]) && (string)$ctx["instance"] !== "" && class_exists("GBDB")) {
        GBDB::setInstance((string)$ctx["instance"]);
        return GBDB::query($query, $ctx, $params);
    }

    return class_exists("GreenQL") ? GreenQL::run($query, $ctx, $params) : GBDB::query($query, $ctx, $params);
}

function _token_file_path(): string {
    $rel = "GBDB_GQL/.DB/.temp/_srvtkns.cry";

    $bases = [];

    foreach (["_ROOT", "ROOT", "BASE_PATH", "APP_ROOT"] as $c) {
        if (defined($c)) $bases[] = constant($c);
    }

    if (!empty($_SERVER["DOCUMENT_ROOT"])) $bases[] = rtrim($_SERVER["DOCUMENT_ROOT"], "/\\") . DIRECTORY_SEPARATOR;
    $bases[] = __DIR__ . DIRECTORY_SEPARATOR;

    foreach ($bases as $b) {
        $b = rtrim($b, "/\\") . DIRECTORY_SEPARATOR;
        $p = $b . str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rel);

        $dir = dirname($p);
        if (is_dir($dir) || @mkdir($dir, 0775, true)) {
            return $p;
        }
    }

    return str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rel);
}

function _tkn_key(): string {
    return hash("sha256", Vars::srvp_static_key(), true);
}

function _tkn_encrypt(string $plain): string {
    if (!function_exists("openssl_encrypt")) {
        return "PLAIN:" . $plain;
    }

    $key = _tkn_key();
    $iv = random_bytes(12);
    $tag = "";

    $cipher = openssl_encrypt($plain, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($cipher === false) {
        return "PLAIN:" . $plain;
    }

    return "GBDBTKN1:" . base64_encode($iv . $tag . $cipher);
}

function _tkn_decrypt(string $raw): string {
    $raw = trim($raw);

    if ($raw === "") return "";

    if (str_starts_with($raw, "PLAIN:")) {
        return substr($raw, 6);
    }

    if (!str_starts_with($raw, "GBDBTKN1:")) {
        return $raw;
    }

    if (!function_exists("openssl_decrypt")) return "";

    $b64 = substr($raw, 9);
    $blob = base64_decode($b64, true);

    if ($blob === false || strlen($blob) < 29) return "";

    $iv = substr($blob, 0, 12);
    $tag = substr($blob, 12, 16);
    $cipher = substr($blob, 28);

    $plain = openssl_decrypt($cipher, "aes-256-gcm", _tkn_key(), OPENSSL_RAW_DATA, $iv, $tag);

    return ($plain === false) ? "" : $plain;
}

function read_tokens(): array {
    $file = _token_file_path();

    if (!file_exists($file)) {
        return [];
    }

    $fp = @fopen($file, "rb");
    if (!$fp) return [];

    try {
        @flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        @flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }

    $json = _tkn_decrypt((string)$raw);
    if ($json === "") return [];

    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];

    $out = [];

    foreach ($arr as $row) {
        if (is_array($row) && isset($row["token"]) && is_string($row["token"]) && $row["token"] !== "") {
            $created = isset($row["created"]) ? (int)$row["created"] : time();

            if ($created + 300 >= time()) {
                $out[] = [
                    "token" => $row["token"],
                    "created" => $created
                ];
            }
        }
    }

    return $out;
}

function add_token(string $token): void {
    $file = _token_file_path();
    $dir = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $fp = @fopen($file, "c+");
    if (!$fp) {
        resp(500, "Token storage not writable.");
    }

    try {
        @flock($fp, LOCK_EX);
        rewind($fp);

        $raw = stream_get_contents($fp);
        $json = _tkn_decrypt((string)$raw);
        $arr = json_decode($json, true);

        if (!is_array($arr)) $arr = [];

        $clean = [];

        foreach ($arr as $row) {
            if (!is_array($row) || empty($row["token"])) continue;
            $created = isset($row["created"]) ? (int)$row["created"] : time();
            if ($created + 300 >= time()) {
                $clean[] = [
                    "token" => (string)$row["token"],
                    "created" => $created
                ];
            }
        }

        $clean[] = [
            "token" => $token,
            "created" => time()
        ];

        $payload = _tkn_encrypt(json_encode($clean, JSON_UNESCAPED_UNICODE));

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $payload);
        fflush($fp);
        @flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function test_token(string $token): bool {
    foreach (read_tokens() as $row) {
        if (($row["token"] ?? "") === $token) {
            return true;
        }
    }

    return false;
}

function delete_token(string $token): void {
    $file = _token_file_path();
    $dir = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $tokens = [];

    foreach (read_tokens() as $row) {
        if (($row["token"] ?? "") !== $token) {
            $tokens[] = $row;
        }
    }

    @file_put_contents($file, _tkn_encrypt(json_encode($tokens, JSON_UNESCAPED_UNICODE)), LOCK_EX);
}
