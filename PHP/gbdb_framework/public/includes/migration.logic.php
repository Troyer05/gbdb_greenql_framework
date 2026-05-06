<?php
/**
 * NEW since GBDB:
 *
 * You can migrate your old GBDB Databases with this simple function:
 * GBDB::migrateGBDB($fromPath, $toPath);
 *
 * $fromPath is the Path of the old GBDB-Database
 * $toPath is the destination path for the migration output
 */


function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nowStamp(): string { return date('Ymd_His'); }

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;

    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;

        $p = $dir . '/' . $f;

        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }

    @rmdir($dir);
}

function atomic_write(string $file, string $payload): bool {
    $dir = dirname($file);

    ensure_dir($dir);

    $tmp = $file . '.tmp_' . uniqid('', true);

    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }

    return true;
}

function name_token(string $plain, string $ns='g'): string {
    $key  = (string)Vars::cryptKey();
    $data = $ns . '|' . (string)$plain;
    $raw  = hash_hmac('sha256', $data, $key, true);
    $b64  = base64_encode($raw);
    $safe = rtrim(strtr($b64, '+/', '-_'), '=');

    return 'gb_' . $safe;
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

function write_table_target(string $file, array $data, bool $encrypt, int $jsonFlags, bool $backupIfExists = true): bool {
    ensure_dir(dirname($file));

    if ($backupIfExists && is_file($file)) {
        @copy($file, $file . '.bak_' . nowStamp());
    }

    $json = json_encode($data, $jsonFlags);

    if ($json === false) return false;
    $payload = $encrypt ? Crypt::encode($json) : $json;

    return atomic_write($file, $payload);
}

function ensure_header(array $table, array $fallbackCols = []): array {
    if (!empty($table) && isset($table[0]) && is_array($table[0]) && isset($table[0]['id']) && (int)$table[0]['id'] === -1) {
        return $table;
    }

    $header = ['id' => -1];

    if (!empty($table) && is_array($table[0])) {
        foreach (array_keys($table[0]) as $k) {
            if ($k === 'id') continue;
            $header[$k] = '-header-';
        }
    } else {
        foreach ($fallbackCols as $k) {
            $k = (string)$k;
            if ($k === '' || $k === 'id') continue;
            $header[$k] = '-header-';
        }
    }

    array_unshift($table, $header);
    return $table;
}

function compute_meta_from_table(array $table): array {
    $lastId = 0;
    $rows = 0;

    foreach ($table as $i => $r) {
        if (!is_array($r)) continue;
        if ($i === 0 && isset($r['id']) && (int)$r['id'] === -1) continue;

        $rows++;
        if (isset($r['id'])) $lastId = max($lastId, (int)$r['id']);
    }

    return [
        "last_id"     => $lastId,
        "rows"        => $rows,
        "append_ops"  => 0,
        "indexes"     => [],
        "migrated_at" => time(),
        "updated_at"  => time(),
    ];
}

function meta_file_for_table_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__meta__" . Format::cleanString($tablePlain) . $ext;
}

function append_file_for_table_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__append__" . Format::cleanString($tablePlain) . $ext;
}

function meta_file_for_table_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__meta__|' . $tblToken, 'meta') . $ext;
}

function append_file_for_table_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__append__|' . $tblToken, 'meta') . $ext;
}

/**
 * GBDB schema detection (very rough)
 */
function detect_plain_structure(string $gbdbRoot): bool {
    foreach (scandir($gbdbRoot) ?: [] as $d) {
        if ($d === '.' || $d === '..') continue;
        $p = $gbdbRoot . $d;

        if (!is_dir($p)) continue;

        foreach (scandir($p) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with(strtolower($f), '.json')) return true;
        }
    }

    return false;
}

function gbdb_parent_from_root(string $gbdbRoot): string {
    return rtrim(dirname(rtrim($gbdbRoot, "/")), "/") . "/";
}

function db_index_filename(string $extEnc): string {
    return name_token('__db_index__', 'meta') . $extEnc;
}

function table_index_filename(string $extEnc): string {
    return name_token('__table_index__', 'meta') . $extEnc;
}

function dump_plain_any(string $gbdbRoot): array {
    $out = [];

    foreach (scandir($gbdbRoot) ?: [] as $dbDir) {
        if ($dbDir === '.' || $dbDir === '..') continue;
        $dbPath = $gbdbRoot . $dbDir;

        if (!is_dir($dbPath)) continue;

        $tables = [];

        foreach (scandir($dbPath) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with($f, ".lock")) continue;

            $full = $dbPath . '/' . $f;

            if (!is_file($full)) continue;

            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

            if ($ext !== 'json' && $ext !== 'db') continue;

            $base = substr($f, 0, -(strlen($ext) + 1));

            if (str_starts_with($base, '__meta__')) continue;
            if (str_starts_with($base, '__append__')) continue;

            if (str_starts_with($base, 'gb_')) {
                // could be normal table in crypt schema; but if we're dumping "plain", we still include it
                // -> we keep it only if user wants "any". Here: allow it.
            }

            $tables[$base] = read_table_any($full);
        }

        if (!empty($tables)) {
            $out[$dbDir] = $tables;
        }
    }
    return $out;
}

function write_plain_schema_new(string $targetRoot, array $dump, string $extPlain, int $jsonFlags): array {
    $log = [];

    ensure_dir($targetRoot);

    foreach ($dump as $dbPlain => $tables) {
        $dbPlain = Format::cleanString($dbPlain);

        if ($dbPlain === '') continue;

        $dbDir = rtrim($targetRoot, "/") . "/" . $dbPlain . "/";

        ensure_dir($dbDir);

        foreach ($tables as $tblPlain => $content) {
            $tblPlain = Format::cleanString($tblPlain);

            if ($tblPlain === '') continue;

            $table = is_array($content) ? $content : [];
            $table = ensure_header($table);
            $meta  = compute_meta_from_table($table);
            $tblFile = $dbDir . $tblPlain . $extPlain;

            if (!write_table_target($tblFile, $table, false, $jsonFlags, true)) {
                $log[] = "❌ Plain table write failed: {$dbPlain}/{$tblPlain}";
                continue;
            }

            $metaFile   = meta_file_for_table_plain($dbDir, $tblPlain, $extPlain);
            $appendFile = append_file_for_table_plain($dbDir, $tblPlain, $extPlain);

            if (!write_table_target($metaFile, [ $meta ], false, $jsonFlags, true)) {
                $log[] = "❌ Meta write failed: {$dbPlain}/{$tblPlain}";
            }

            ensure_dir(dirname($appendFile));

            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());

            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                $log[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
            }

            $log[] = "✅ Plain migrated: {$dbPlain}/{$tblPlain}";
        }
    }

    return $log;
}

function write_crypt_schema_new(string $targetRoot, array $dump, string $extEnc, int $jsonFlags): array {
    $log = [];

    ensure_dir($targetRoot);

    $dbMap = [];

    foreach ($dump as $dbPlain => $_tables) {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === '') continue;
        $dbMap[$dbPlain] = name_token('db:' . $dbPlain, 'db');
    }

    $dbIdxPath = rtrim($targetRoot, "/") . "/" . db_index_filename($extEnc);
    $dbIdxTable = build_index_table($dbMap);

    if (!write_table_target($dbIdxPath, $dbIdxTable, true, 0, true)) {
        $log[] = "❌ Could not write db_index: " . basename($dbIdxPath);
        return $log;
    }

    foreach ($dump as $dbPlain => $tables) {
        $dbPlain = Format::cleanString($dbPlain);

        if ($dbPlain === '' || !isset($dbMap[$dbPlain])) continue;

        $dbToken = $dbMap[$dbPlain];
        $dbDir = rtrim($targetRoot, "/") . "/" . $dbToken . "/";

        ensure_dir($dbDir);

        $tblMap = [];

        foreach ($tables as $tblPlain => $_content) {
            $tblPlain = Format::cleanString($tblPlain);
            if ($tblPlain === '') continue;
            $tblMap[$tblPlain] = name_token('tbl:' . $dbPlain . '|' . $tblPlain, 'tbl');
        }

        $tblIdxPath = $dbDir . table_index_filename($extEnc);
        $tblIdxTable = build_index_table($tblMap);

        if (!write_table_target($tblIdxPath, $tblIdxTable, true, 0, true)) {
            $log[] = "❌ Could not write table_index: {$dbPlain}";
            continue;
        }

        foreach ($tables as $tblPlain => $content) {
            $tblPlain = Format::cleanString($tblPlain);

            if ($tblPlain === '' || !isset($tblMap[$tblPlain])) continue;

            $tblToken = $tblMap[$tblPlain];
            $table = is_array($content) ? $content : [];
            $table = ensure_header($table);
            $meta  = compute_meta_from_table($table);
            $tblFile = $dbDir . $tblToken . $extEnc;

            if (!write_table_target($tblFile, $table, true, 0, true)) {
                $log[] = "❌ Encrypted table write failed: {$dbPlain}/{$tblPlain}";
                continue;
            }

            $metaFile   = meta_file_for_table_crypt($dbDir, $tblToken, $extEnc);
            $appendFile = append_file_for_table_crypt($dbDir, $tblToken, $extEnc);

            if (!write_table_target($metaFile, [ $meta ], true, 0, true)) {
                $log[] = "❌ Meta write failed: {$dbPlain}/{$tblPlain}";
            }

            ensure_dir(dirname($appendFile));

            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());

            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                $log[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
            }

            $log[] = "✅ Crypt migrated: {$dbPlain}/{$tblPlain}";
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
            return "❌ Backup failed (rename): {$currentPath} -> {$backup}";
        }
    }

    if (!@rename(rtrim($tmpRoot, "/"), $currentPath)) {
        @rename($backup, $currentPath);
        return "❌ Swap failed (rename): {$tmpRoot} -> {$currentPath} (Rollback attempted)";
    }

    return "✅ Backup created: {$backup}";
}

$GBDB_ROOT   = rtrim(Vars::DB_PATH(), "/") . "/";
$GBDB_PARENT = gbdb_parent_from_root($GBDB_ROOT);
$do = $_POST['do'] ?? '';
$convertMode = ($_POST['convert_mode'] ?? 'no');
$forceRewrite = isset($_POST['force']) ? true : false;
$targetCrypt = Vars::crypt_data();
$targetExt   = Vars::data_extension();
$jsonFlags   = Vars::jpretty();
$logs = [];
$errors = [];
$foundPlain = detect_plain_structure($GBDB_ROOT);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $do === 'migrate') {
    if (!$foundPlain) {
        $errors[] = "Keine plain .json Struktur erkannt (oder Ordner leer). Dieses Script ist primär für alte plain Projekte.";
    } else {
        $dump = dump_plain_any($GBDB_ROOT);

        if (empty($dump)) {
            $errors[] = "Konnte keine Tabellen dumpen (leer/korrupt?).";
        } else {
            if ($convertMode === 'to_target_schema') {
                $tmpRoot = $GBDB_PARENT . "GBDB__tmp_migrate__/";

                if (is_dir($tmpRoot)) rrmdir($tmpRoot);

                ensure_dir($tmpRoot);

                if ($targetCrypt) {
                    $logs[] = "Ziel: crypt=true ({$targetExt}) → baue tokenisierte Struktur + Indices + per-table Meta/Append.";
                    $logs = array_merge($logs, write_crypt_schema_new($tmpRoot, $dump, $targetExt, $jsonFlags));
                    $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
                    $logs[] = "⚠️ ENV bleibt unverändert. Achte darauf, dass crypt_data() in ENV zu diesem Schema passt.";
                } else {
                    $logs[] = "Ziel: crypt=false ({$targetExt}) → baue plain Struktur + per-table Meta/Append.";
                    $logs = array_merge($logs, write_plain_schema_new($tmpRoot, $dump, $targetExt, $jsonFlags));
                    $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
                    $logs[] = "⚠️ ENV bleibt unverändert. Achte darauf, dass crypt_data() in ENV zu diesem Schema passt.";
                }

                if (is_dir($tmpRoot)) rrmdir($tmpRoot);

            } else {
                $logs[] = "Modus: Nur Struktur-Upgrade IN-PLACE (Base bleibt liegen).";

                foreach ($dump as $dbPlain => $tables) {
                    $dbDir = $GBDB_ROOT . $dbPlain . "/";

                    if (!is_dir($dbDir)) continue;

                    foreach ($tables as $tblPlain => $content) {
                        $tblPlain = Format::cleanString($tblPlain);

                        if ($tblPlain === '') continue;

                        $table = is_array($content) ? $content : [];

                        if (empty($table)) {
                            $logs[] = "❌ Skip empty/unreadable: {$dbPlain}/{$tblPlain}";
                            continue;
                        }

                        $table = ensure_header($table);
                        $meta  = compute_meta_from_table($table);
                        $ext = $targetExt;

                        if ($targetCrypt) {
                            $logs[] = "⚠️ Skip {$dbPlain}/{$tblPlain}: In-place upgrade in crypt=true ist nicht supported. Nutze 'In Zielschema umwandeln'.";
                            continue;
                        }

                        $metaFile   = meta_file_for_table_plain($dbDir, $tblPlain, $ext);
                        $appendFile = append_file_for_table_plain($dbDir, $tblPlain, $ext);

                        if ($forceRewrite || !is_file($metaFile)) {
                            if (!write_table_target($metaFile, [ $meta ], false, $jsonFlags, true)) {
                                $logs[] = "❌ Meta failed: {$dbPlain}/{$tblPlain}";
                                continue;
                            }
                        }

                        if ($forceRewrite || !is_file($appendFile)) {
                            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());

                            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                                $logs[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
                                continue;
                            }
                        }

                        $logs[] = "✅ Upgraded: {$dbPlain}/{$tblPlain}";
                    }
                }

                $logs[] = "✅ Fertig. (Hinweis: Indices/Token-Struktur werden in diesem Modus NICHT gebaut.)";
            }
        }
    }
}
?>
