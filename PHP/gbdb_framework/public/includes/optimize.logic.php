<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function name_token(string $plain, string $ns='g'): string {
    $key  = (string)Vars::cryptKey();
    $data = $ns . '|' . (string)$plain;
    $raw  = hash_hmac('sha256', $data, $key, true);
    $b64  = base64_encode($raw);
    $safe = rtrim(strtr($b64, '+/', '-_'), '=');

    return 'gb_' . $safe;
}

function read_table_any(string $file): array {
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);

    if ($raw === false) return [];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'db') {
        $decoded = Crypt::decode($raw);

        if ($decoded === null) return [];
        $arr = json_decode($decoded, true);

        return is_array($arr) ? $arr : [];
    }

    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
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

function count_lines(string $file): int {
    if (!is_file($file)) return 0;
    $fh = @fopen($file, 'r');

    if (!$fh) return 0;
    $n = 0;

    try {
        while (!feof($fh)) {
            $line = fgets($fh);

            if ($line === false) break;
            if (trim($line) !== '') $n++;
        }
    } finally {
        @fclose($fh);
    }

    return $n;
}

function meta_file_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__meta__" . Format::cleanString($tablePlain) . $ext;
}

function append_file_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__append__" . Format::cleanString($tablePlain) . $ext;
}

function meta_file_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__meta__|' . $tblToken, 'meta') . $ext;
}

function append_file_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__append__|' . $tblToken, 'meta') . $ext;
}

function db_index_file(string $root, string $ext): string {
    return rtrim($root, "/") . "/" . name_token('__db_index__', 'meta') . $ext;
}

function table_index_file(string $dbDir, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__table_index__', 'meta') . $ext;
}

function resolve_paths_for_table(string $dbPlain, string $tablePlain): array {
    $root = rtrim(Vars::DB_PATH(), "/") . "/";
    $ext  = Vars::data_extension();

    if (!Vars::crypt_data()) {
        $dbDir = $root . $dbPlain . "/";
        $base  = $dbDir . $tablePlain . $ext;
        $meta  = meta_file_plain($dbDir, $tablePlain, $ext);
        $app   = append_file_plain($dbDir, $tablePlain, $ext);

        return [
            "mode" => "plain",
            "dbDir" => $dbDir,
            "base" => $base,
            "meta" => $meta,
            "append" => $app,
        ];
    }

    $dbIdx = db_index_file($root, $ext);
    $dbIdxTable = read_table_any($dbIdx);
    $dbMap = parse_index_table($dbIdxTable);

    if (!isset($dbMap[$dbPlain])) {
        return ["mode" => "crypt", "error" => "DB nicht im db_index gefunden."];
    }

    $dbToken = $dbMap[$dbPlain];
    $dbDir   = $root . $dbToken . "/";
    $tblIdx  = table_index_file($dbDir, $ext);
    $tblIdxTable = read_table_any($tblIdx);
    $tblMap  = parse_index_table($tblIdxTable);

    if (!isset($tblMap[$tablePlain])) {
        return ["mode" => "crypt", "error" => "Tabelle nicht im table_index gefunden."];
    }

    $tblToken = $tblMap[$tablePlain];
    $base  = $dbDir . $tblToken . $ext;
    $meta  = meta_file_crypt($dbDir, $tblToken, $ext);
    $app   = append_file_crypt($dbDir, $tblToken, $ext);

    return [
        "mode" => "crypt",
        "dbDir" => $dbDir,
        "base" => $base,
        "meta" => $meta,
        "append" => $app,
    ];
}

function tableStats(string $db, string $table): array {
    $p = resolve_paths_for_table($db, $table);

    if (isset($p["error"])) return $p;

    $metaArr = read_table_any($p["meta"]);
    $meta = (isset($metaArr[0]) && is_array($metaArr[0])) ? $metaArr[0] : [];

    return [
        "mode" => $p["mode"],
        "base_exists" => is_file($p["base"]),
        "meta_exists" => is_file($p["meta"]),
        "append_exists" => is_file($p["append"]),
        "base_size" => is_file($p["base"]) ? filesize($p["base"]) : 0,
        "meta_size" => is_file($p["meta"]) ? filesize($p["meta"]) : 0,
        "append_size" => is_file($p["append"]) ? filesize($p["append"]) : 0,
        "append_lines" => count_lines($p["append"]),
        "rows" => (int)($meta["rows"] ?? 0),
        "last_id" => (int)($meta["last_id"] ?? 0),
        "append_ops" => (int)($meta["append_ops"] ?? 0),
        "paths" => $p,
    ];
}

$dbs = GBDB::listDBs();
$selDb    = isset($_GET['db']) ? Format::cleanString($_GET['db']) : '';
$selTable = isset($_GET['table']) ? Format::cleanString($_GET['table']) : '';
$tables = $selDb !== '' ? GBDB::listTables($selDb) : [];
$action = $_POST['action'] ?? '';
$msgs = [];

if ($action === 'compact_one') {
    $db = Format::cleanString($_POST['db'] ?? '');
    $tb = Format::cleanString($_POST['table'] ?? '');

    if ($db !== '' && $tb !== '') {
        $ok = GBDB::compactTable($db, $tb);
        $msgs[] = $ok ? "✅ Compact OK: {$db} / {$tb}" : "❌ Compact FEHLER: {$db} / {$tb}";
        $selDb = $db;
        $selTable = $tb;
        $tables = GBDB::listTables($selDb);
    }
}

if ($action === 'compact_all') {
    $db = Format::cleanString($_POST['db'] ?? '');

    if ($db !== '') {
        $tbs = GBDB::listTables($db);
        $okAll = true;

        foreach ($tbs as $tb) {
            if (!GBDB::compactTable($db, $tb)) {
                $okAll = false;
                $msgs[] = "❌ Compact FEHLER: {$db} / {$tb}";
            } else {
                $msgs[] = "✅ Compact OK: {$db} / {$tb}";
            }
        }

        if ($okAll) $msgs[] = "✅ Alle Tabellen in {$db} wurden kompaktiert.";

        $selDb = $db;
        $tables = GBDB::listTables($selDb);
    }
}

$stats = [];

if ($selDb !== '' && $selTable !== '') {
    $stats = tableStats($selDb, $selTable);
}
?>
