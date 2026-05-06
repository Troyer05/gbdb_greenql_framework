<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('import_export');

$msg = '';
$report = [];
$formats = ['json' => 'JSON', 'ndjson' => 'NDJSON', 'csv' => 'CSV'];
$instances = GreenQLUIv2Helper::instances();
$selectedInstance = GreenQLUIv2Helper::clean((string)($_GET['instance'] ?? $_POST['instance'] ?? ($_SESSION['gbdbui_import_export_instance'] ?? '')));
if ($selectedInstance === '' || !in_array($selectedInstance, $instances, true)) {
    $selectedInstance = (string)($instances[0] ?? '');
}
if ($selectedInstance !== '') {
    $_SESSION['gbdbui_import_export_instance'] = $selectedInstance;
}

$pairs = [];
$dbs = $selectedInstance !== '' ? GreenQLUIv2Helper::databases($selectedInstance) : [];

foreach ($dbs as $db) {
    $db = (string)$db;
    foreach (GreenQLUIv2Helper::tables($selectedInstance, $db) as $table) {
        $table = (string)$table;
        $pairs[] = [
            'instance' => $selectedInstance,
            'db' => $db,
            'table' => $table,
            'value' => $selectedInstance . '|' . $db . '|' . $table,
        ];
    }
}

/**
 * Zerlegt einen Tabellenwert aus der UI.
 * @param string $value Übergabewert.
 * @return array Rückgabewert.
 */
function gbdbui_import_export_target(string $value): array {
    $parts = explode('|', $value, 3);
    return [
        GreenQLUIv2Helper::clean((string)($parts[0] ?? '')),
        GreenQLUIv2Helper::clean((string)($parts[1] ?? '')),
        GreenQLUIv2Helper::clean((string)($parts[2] ?? '')),
    ];
}

/**
 * Prüft ob eine Ziel-Tabelle in der sichtbaren aktiven Instanz existiert.
 * @param string $instance Instanzname.
 * @param string $db Base-Name.
 * @param string $table Tabellenname.
 * @return bool Rückgabewert.
 */
function gbdbui_import_export_valid_target(string $instance, string $db, string $table): bool {
    if ($instance === '' || $db === '' || $table === '') return false;
    if (!GreenQLUIv2Helper::canAccessInstance($instance) || !GreenQLUIv2Helper::canAccessDb($instance, $db)) return false;
    return in_array($table, GreenQLUIv2Helper::tables($instance, $db), true);
}

/**
 * Führt eine Operation in einer temporär gesetzten Instanz aus.
 * @param string $instance Instanzname.
 * @param callable $callback Callback.
 * @return mixed Rückgabewert.
 */
function gbdbui_import_export_with_instance(string $instance, callable $callback): mixed {
    $old = GBDB::getInstance();
    try {
        GBDB::setInstance($instance);
        return $callback();
    } finally {
        GBDB::setInstance($old);
    }
}

/**
 * Liefert einen sicheren Dateinamen für Downloads.
 * @param string $instance Instanzname.
 * @param string $db Übergabewert.
 * @param string $table Übergabewert.
 * @param string $format Übergabewert.
 * @return string Rückgabewert.
 */
function gbdbui_export_filename(string $instance, string $db, string $table, string $format): string {
    return date('Ymd_His') . '_' . GreenQLUIv2Helper::clean($instance) . '_' . GreenQLUIv2Helper::clean($db) . '_' . GreenQLUIv2Helper::clean($table) . '.' . strtolower($format);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger Sicherheits-Token.';
        $report = ['ok' => false, 'error' => 'csrf'];
    } else {
        $action = (string)($_POST['gbdbui_import_export_action'] ?? '');
        $format = strtolower((string)($_POST['format'] ?? 'json'));
        if (!array_key_exists($format, $formats)) $format = 'json';

        if ($action === 'export') {
            [$targetInstance, $db, $table] = gbdbui_import_export_target((string)($_POST['target'] ?? ''));

            if (!gbdbui_import_export_valid_target($targetInstance, $db, $table)) {
                $msg = 'Export-Ziel ist ungültig.';
                $report = ['ok' => false, 'error' => 'invalid_target'];
            } elseif (!method_exists('GBDB', 'exportRows')) {
                $msg = 'GBDB::exportRows ist in dieser Framework-Version nicht verfügbar.';
                $report = ['ok' => false, 'error' => 'method_missing'];
            } else {
                $tmp = rtrim(sys_get_temp_dir(), '/') . '/' . gbdbui_export_filename($targetInstance, $db, $table, $format);
                $report = gbdbui_import_export_with_instance($targetInstance, fn() => GBDB::exportRows($db, $table, $format, $tmp));

                if (!empty($report['ok']) && is_file($tmp)) {
                    $downloadName = gbdbui_export_filename($targetInstance, $db, $table, $format);
                    header('Content-Type: application/octet-stream');
                    header('Content-Length: ' . (string)filesize($tmp));
                    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
                    readfile($tmp);
                    @unlink($tmp);
                    exit;
                }

                $msg = 'Export konnte nicht erstellt werden.';
            }
        }

        if ($action === 'import') {
            [$targetInstance, $db, $table] = gbdbui_import_export_target((string)($_POST['target'] ?? ''));
            $upload = $_FILES['import_file'] ?? null;

            if (!gbdbui_import_export_valid_target($targetInstance, $db, $table)) {
                $msg = 'Import-Ziel ist ungültig.';
                $report = ['ok' => false, 'error' => 'invalid_target'];
            } elseif (!method_exists('GBDB', 'importRows')) {
                $msg = 'GBDB::importRows ist in dieser Framework-Version nicht verfügbar.';
                $report = ['ok' => false, 'error' => 'method_missing'];
            } elseif (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) {
                $msg = 'Keine gültige Import-Datei hochgeladen.';
                $report = ['ok' => false, 'error' => 'upload_failed'];
            } else {
                $report = gbdbui_import_export_with_instance($targetInstance, fn() => GBDB::importRows($db, $table, (string)$upload['tmp_name'], $format, true));
                gbdbui_cache_clear();
                $msg = !empty($report['ok']) ? 'Import abgeschlossen.' : 'Import mit Fehlern oder ungültigen Spalten.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Import / Export</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('import_export'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Data</p>
            <h1>Import / Export</h1>
            <p>Tabellen direkt aus der aktiven GBDB-Instanz exportieren oder geprüfte Datensätze importieren. Unterstützt JSON, NDJSON und CSV; Imports laufen über die vorhandene GBDB-Validierung und erstellen bei aktivierter Enterprise-Logik ein Rollback-Backup.</p>
        </section>

        <?php if ($msg): ?><div class="gbdbui-flash <?= !empty($report['ok']) ? 'ok' : 'bad' ?>"><?= gbdbui_e($msg) ?></div><?php endif; ?>

        <section class="gbdbui-panel gbdbui-import-export-filter">
            <form method="get" class="gbdbui-inline-form">
                <input type="hidden" name="tool" value="import_export">
                <label>Aktive Instanz
                    <select name="instance" onchange="this.form.submit()">
                        <?php foreach ($instances as $inst): ?>
                        <option value="<?= gbdbui_e($inst) ?>" <?= $inst === $selectedInstance ? 'selected' : '' ?>><?= gbdbui_e($inst) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="secondary" type="submit">Instanz laden</button>
            </form>
            <?php if ($selectedInstance !== ''): ?>
                <p class="gbdbui-help">Angezeigt werden nur Bases und Tabellen aus <strong><?= gbdbui_e($selectedInstance) ?></strong>, auf die dein UI-User Zugriff hat.</p>
            <?php endif; ?>
        </section>

        <section class="gbdbui-page-grid">
            <div class="gbdbui-panel gbdbui-safe-zone span-6">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Export</h2>
                        <p>Erzeugt einen Download aus der gewählten Tabelle. Der Export wird nur temporär geschrieben und anschließend direkt ausgeliefert.</p>
                    </div>
                    <span class="gbdbui-pill ok">Download</span>
                </div>
                <?php if (!$pairs): ?>
                    <div class="gbdbui-empty">Keine Tabellen in der aktiven Instanz gefunden.</div>
                <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <input type="hidden" name="gbdbui_import_export_action" value="export">
                    <div class="gbdbui-form-grid">
                        <label class="field-8">Tabelle
                            <select name="target" required>
                                <?php foreach ($pairs as $pair): ?>
                                <option value="<?= gbdbui_e($pair['value']) ?>"><?= gbdbui_e($pair['instance'] . ' / ' . $pair['db'] . ' / ' . $pair['table']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field-4">Format
                            <select name="format">
                                <?php foreach ($formats as $value => $label): ?>
                                <option value="<?= gbdbui_e($value) ?>"><?= gbdbui_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="gbdbui-action-row">
                        <button class="primary" type="submit">Export herunterladen</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <div class="gbdbui-panel span-6">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Import</h2>
                        <p>Fügt Datensätze an eine bestehende Tabelle an. Unbekannte Spalten werden vor dem Schreiben abgefangen.</p>
                    </div>
                    <span class="gbdbui-pill">Append</span>
                </div>
                <?php if (!$pairs): ?>
                    <div class="gbdbui-empty">Lege zuerst mindestens eine Base und Tabelle an.</div>
                <?php else: ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <input type="hidden" name="gbdbui_import_export_action" value="import">
                    <div class="gbdbui-form-grid">
                        <label class="field-8">Zieltabelle
                            <select name="target" required>
                                <?php foreach ($pairs as $pair): ?>
                                <option value="<?= gbdbui_e($pair['value']) ?>"><?= gbdbui_e($pair['instance'] . ' / ' . $pair['db'] . ' / ' . $pair['table']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field-4">Format
                            <select name="format">
                                <?php foreach ($formats as $value => $label): ?>
                                <option value="<?= gbdbui_e($value) ?>"><?= gbdbui_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field-12">Datei
                            <input type="file" name="import_file" accept=".json,.ndjson,.csv,application/json,text/csv,text/plain" required>
                        </label>
                    </div>
                    <p class="gbdbui-help">Hinweis: Der Import ersetzt keine Tabelle, sondern hängt valide Zeilen an. Für Lösch-/Restore-Aktionen weiter Backup oder Re-Install nutzen.</p>
                    <div class="gbdbui-action-row">
                        <button class="primary" type="submit">Import starten</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <div class="gbdbui-panel span-12">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Letzter Report</h2>
                        <p>Zeigt Validierung, eingefügte Zeilen, Backup-Pfad oder Fehler des letzten Import-/Export-Laufs.</p>
                    </div>
                </div>
                <?php if ($report): ?>
                    <pre><?= gbdbui_e($report) ?></pre>
                <?php else: ?>
                    <div class="gbdbui-empty">Noch keine Aktion in dieser Sitzung ausgeführt.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
