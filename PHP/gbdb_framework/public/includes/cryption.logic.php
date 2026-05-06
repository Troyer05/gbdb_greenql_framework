<?php
$EXT_PLAIN = '.json';
$EXT_ENC   = '.db';
$GBDB_ROOT   = rtrim(Vars::DB_PATH(), "/") . "/";
$GBDB_PARENT = rtrim(dirname(rtrim($GBDB_ROOT, "/")), "/") . "/";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function atomic_write(string $file, string $payload): bool {
    $dir = dirname($file);

    ensure_dir($dir);

    $tmp = $file . '.' . uniqid('tmp_', true);

    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }

    return true;
}

function name_token(string $plain, string $ns = 'g'): string {
    $key  = (string)Vars::cryptKey();
    $data = $ns . '|' . $plain;
    $raw  = hash_hmac('sha256', $data, $key, true);
    $b64  = base64_encode($raw);
    $safe = rtrim(strtr($b64, '+/', '-_'), '=');

    return 'gb_' . $safe;
}

function json_pretty_flags(): int {
    return Vars::jpretty();
}

function read_plain_json(string $file): array {
    $raw = @file_get_contents($file);

    if ($raw === false) return [];
    $arr = json_decode($raw, true);

    return is_array($arr) ? $arr : [];
}

function read_enc_db(string $file): array {
    $raw = @file_get_contents($file);

    if ($raw === false) return [];
    $decoded = Crypt::decode($raw);

    if ($decoded === null) return [];
    $arr = json_decode($decoded, true);

    return is_array($arr) ? $arr : [];
}

function write_plain_json(string $file, array $data): bool {
    $json = json_encode($data, json_pretty_flags());

    if ($json === false) return false;
    return atomic_write($file, $json);
}

function write_enc_db(string $file, array $data): bool {
    $json = json_encode($data, json_pretty_flags());

    if ($json === false) return false;
    $payload = Crypt::encode($json);

    return atomic_write($file, $payload);
}

function db_index_filename(string $extEnc): string {
    return name_token('__db_index__', 'meta') . $extEnc;
}
function table_index_filename(string $extEnc): string {
    return name_token('__table_index__', 'meta') . $extEnc;
}

function build_index_table(array $mapPlainToToken): array {
    $db = [];
    $db[] = ["id" => -1, "plain" => "-header-", "token" => "-header-"];
    $id = 0;

    foreach ($mapPlainToToken as $plain => $token) {
        $db[] = ["id" => $id++, "plain" => (string)$plain, "token" => (string)$token];
    }

    return $db;
}

function parse_index_table(array $table): array {
    if (empty($table) || !isset($table[0]) || !is_array($table[0])) return [];

    unset($table[0]);

    $table = array_values($table);
    $map = [];

    foreach ($table as $r) {
        if (!is_array($r)) continue;
        if (!isset($r['plain'], $r['token'])) continue;

        $p = (string)$r['plain'];
        $t = (string)$r['token'];

        if ($p !== '' && $t !== '') $map[$p] = $t;
    }

    return $map;
}

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;

    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;

        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }

    @rmdir($dir);
}

function meta_filename_plain(string $tblPlain, string $extPlain): string {
    return "__meta__" . $tblPlain . $extPlain;
}
function append_filename_plain(string $tblPlain, string $extPlain): string {
    return "__append__" . $tblPlain . $extPlain;
}

function meta_filename_enc(string $tblToken, string $extEnc): string {
    return name_token('__meta__|' . $tblToken, 'meta') . $extEnc;
}
function append_filename_enc(string $tblToken, string $extEnc): string {
    return name_token('__append__|' . $tblToken, 'meta') . $extEnc;
}

function looks_like_meta_or_append_or_idx(string $file, string $extPlain, string $extEnc): bool {
    if (str_starts_with($file, "__meta__") && str_ends_with($file, $extPlain)) return true;
    if (str_starts_with($file, "__append__") && str_ends_with($file, $extPlain)) return true;
    if (str_starts_with($file, "__idx__")) return true;
    if (str_starts_with($file, "__idxa__")) return true;
    if (str_ends_with($file, ".lock")) return true;

    return false;
}

function detect_state(string $gbdbRoot, string $extEnc, string $extPlain): string {
    $encIdx = $gbdbRoot . db_index_filename($extEnc);

    if (is_file($encIdx)) return 'encrypted';

    foreach (@scandir($gbdbRoot) ?: [] as $d) {
        if ($d === '.' || $d === '..') continue;

        $p = $gbdbRoot . $d;

        if (!is_dir($p)) continue;

        foreach (@scandir($p) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (looks_like_meta_or_append_or_idx($f, $extPlain, $extEnc)) continue;
            if (str_ends_with($f, $extPlain)) return 'plain';
        }
    }

    return 'unknown';
}

function dump_plain(string $gbdbRoot, string $extPlain, string $extEnc): array {
    $out = [];

    foreach (scandir($gbdbRoot) as $dbDir) {
        if ($dbDir === '.' || $dbDir === '..') continue;
        $dbPath = $gbdbRoot . $dbDir;

        if (!is_dir($dbPath)) continue;
        $tables = [];

        foreach (scandir($dbPath) as $f) {
            if ($f === '.' || $f === '..') continue;
            if (looks_like_meta_or_append_or_idx($f, $extPlain, $extEnc)) continue;
            if (!str_ends_with($f, $extPlain)) continue;

            $tablePlain = substr($f, 0, -strlen($extPlain));
            $tables[$tablePlain] = read_plain_json($dbPath . '/' . $f);
        }

        $out[$dbDir] = $tables;
    }

    return $out;
}

function dump_encrypted(string $gbdbRoot, string $extEnc): array {
    $out = [];
    $dbIdxFile = $gbdbRoot . db_index_filename($extEnc);

    if (!is_file($dbIdxFile)) return [];

    $dbIdxTable = read_enc_db($dbIdxFile);
    $dbMap = parse_index_table($dbIdxTable);

    foreach ($dbMap as $dbPlain => $dbToken) {
        $dbPath = $gbdbRoot . $dbToken . '/';

        if (!is_dir($dbPath)) continue;

        $tblIdxFile = $dbPath . table_index_filename($extEnc);

        if (!is_file($tblIdxFile)) {
            $out[$dbPlain] = [];
            continue;
        }

        $tblIdxTable = read_enc_db($tblIdxFile);
        $tblMap = parse_index_table($tblIdxTable);
        $tables = [];

        foreach ($tblMap as $tblPlain => $tblToken) {
            $tblFile = $dbPath . $tblToken . $extEnc;
            if (!is_file($tblFile)) continue;
            $tables[$tblPlain] = read_enc_db($tblFile);
        }

        $out[$dbPlain] = $tables;
    }

    return $out;
}

function compute_meta_from_table(array $table): array {
    $maxId = 0;
    $rows  = 0;

    foreach ($table as $i => $r) {
        if (!is_array($r)) continue;
        if ($i === 0 && isset($r["id"]) && (int)$r["id"] === -1) continue;

        $rows++;

        if (isset($r["id"])) $maxId = max($maxId, (int)$r["id"]);
    }

    return [
        "last_id"    => $maxId,
        "rows"       => $rows,
        "append_ops" => 0,
        "indexes"    => [],
        "created_at" => time(),
        "updated_at" => time(),
    ];
}

function write_encrypted_schema(string $targetRoot, array $dump, string $extEnc): array {
    $log = [];

    ensure_dir($targetRoot);

    $dbMap = [];

    foreach ($dump as $dbPlain => $_tables) {
        $dbToken = name_token('db:' . $dbPlain, 'db');
        $dbMap[$dbPlain] = $dbToken;
    }

    $dbIdxPath  = $targetRoot . db_index_filename($extEnc);
    $dbIdxTable = build_index_table($dbMap);

    if (!write_enc_db($dbIdxPath, $dbIdxTable)) {
        $log[] = "❌ Konnte DB-Index nicht schreiben: {$dbIdxPath}";
        return $log;
    }

    foreach ($dump as $dbPlain => $tables) {
        $dbToken = $dbMap[$dbPlain];
        $dbDir   = $targetRoot . $dbToken . '/';

        ensure_dir($dbDir);

        $tblMap = [];

        foreach ($tables as $tblPlain => $_content) {
            $tblToken = name_token('tbl:' . $dbPlain . '|' . $tblPlain, 'tbl');
            $tblMap[$tblPlain] = $tblToken;
        }

        $tblIdxPath  = $dbDir . table_index_filename($extEnc);
        $tblIdxTable = build_index_table($tblMap);

        if (!write_enc_db($tblIdxPath, $tblIdxTable)) {
            $log[] = "❌ Konnte Table-Index nicht schreiben: {$tblIdxPath}";
            continue;
        }

        foreach ($tables as $tblPlain => $content) {
            $tblToken = $tblMap[$tblPlain];
            $tblFile = $dbDir . $tblToken . $extEnc;
            $tableArr = is_array($content) ? $content : [];

            if (!write_enc_db($tblFile, $tableArr)) {
                $log[] = "❌ Konnte Tabelle nicht schreiben: {$dbPlain}/{$tblPlain}";
                continue;
            }

            $meta = compute_meta_from_table($tableArr);
            $metaFile = $dbDir . meta_filename_enc($tblToken, $extEnc);

            if (!write_enc_db($metaFile, [ $meta ])) {
                $log[] = "⚠️ Meta konnte nicht geschrieben werden: {$dbPlain}/{$tblPlain}";
            }

            $appendFile = $dbDir . append_filename_enc($tblToken, $extEnc);
            $empty = "";
            $payload = Crypt::encode($empty);

            if (!atomic_write($appendFile, $payload)) {
                $log[] = "⚠️ Append konnte nicht geschrieben werden: {$dbPlain}/{$tblPlain}";
            }

            $log[] = "✅ Encoded: {$dbPlain}/{$tblPlain}";
        }
    }

    return $log;
}

function write_plain_schema(string $targetRoot, array $dump, string $extPlain): array {
    $log = [];

    ensure_dir($targetRoot);

    foreach ($dump as $dbPlain => $tables) {
        $dbDir = $targetRoot . $dbPlain . '/';

        ensure_dir($dbDir);

        foreach ($tables as $tblPlain => $content) {
            $tblFile = $dbDir . $tblPlain . $extPlain;

            if (!write_plain_json($tblFile, is_array($content) ? $content : [])) {
                $log[] = "❌ Konnte Tabelle nicht schreiben: {$dbPlain}/{$tblPlain}";
            } else {
                $log[] = "✅ Decoded: {$dbPlain}/{$tblPlain}";
            }
        }
    }

    return $log;
}

function swap_with_backup(string $gbdbRoot, string $parent, string $tmpRoot): string {
    $ts = date('Ymd_His');
    $backup = rtrim($parent, "/") . "/GBDB_backup_" . $ts;
    $currentPath = rtrim($gbdbRoot, "/");

    if (is_dir($currentPath)) {
        if (!@rename($currentPath, $backup)) {
            return "❌ Backup fehlgeschlagen (rename): {$currentPath} -> {$backup}";
        }
    }

    if (!@rename(rtrim($tmpRoot, "/"), $currentPath)) {
        @rename($backup, $currentPath);
        return "❌ Swap fehlgeschlagen (rename): {$tmpRoot} -> {$currentPath} (Rollback versucht)";
    }

    return "✅ Backup erstellt: {$backup}";
}

$logs = [];
$errors = [];
$state = detect_state($GBDB_ROOT, $EXT_ENC, $EXT_PLAIN);
$action = $_POST['action'] ?? '';
$confirm = ($_POST['confirm'] ?? '') === 'yes';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$confirm) {
        $errors[] = "Bitte bestätige die Checkbox (Backup + Umstellung).";
    } elseif ($action !== 'encrypt' && $action !== 'decrypt') {
        $errors[] = "Ungültige Aktion.";
    } else {

        $tmpRoot = $GBDB_PARENT . "GBDB__tmp_migrate__/";

        if (is_dir($tmpRoot)) rrmdir($tmpRoot);

        ensure_dir($tmpRoot);

        if ($action === 'encrypt') {
            $dump = dump_plain($GBDB_ROOT, $EXT_PLAIN, $EXT_ENC);
            $logs[] = "Quelle gelesen (plain): " . count($dump) . " DB(s)";
            $logs = array_merge($logs, write_encrypted_schema($tmpRoot, $dump, $EXT_ENC));
            $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
            $logs[] = "⚠️ Danach .framework.env.php: crypt_data() auf TRUE setzen (manuell).";
        }

        if ($action === 'decrypt') {
            $dump = dump_encrypted($GBDB_ROOT, $EXT_ENC);
            $logs[] = "Quelle gelesen (encrypted): " . count($dump) . " DB(s)";
            $logs = array_merge($logs, write_plain_schema($tmpRoot, $dump, $EXT_PLAIN));
            $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
            $logs[] = "⚠️ Danach .framework.env.php: crypt_data() auf FALSE setzen (manuell).";
        }

        if (is_dir($tmpRoot)) rrmdir($tmpRoot);
    }
}
?>
