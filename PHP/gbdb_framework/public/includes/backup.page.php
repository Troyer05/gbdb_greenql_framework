<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('backup');

$msg = '';
$report = [];
$defaultTarget = dirname(Vars::DB_PATH()) . '/backup_' . date('Ymd_His');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
    } else {
        $target = rtrim((string) ($_POST['path'] ?? ''), '/');

        if ($target === '')
            $target = $defaultTarget;

        $report = gbdbui_copy_tree(Vars::DB_PATH(), $target);
        $msg = $report['ok'] ? 'Backup erstellt: ' . $target : 'Backup mit Fehlern.';
    }

}

?>
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Backup</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>

<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('backup'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Safe</p>
            <h1>Backup</h1>
            <p>Erstellt eine saubere Dateisystem-Kopie der kompletten GBDB-Daten. Ideal vor Migration,
                Crypto-Konvertierung oder Re-Install.</p>
        </section>

        <?php if ($msg): ?>
            <div class="gbdbui-flash <?= $report && !$report['ok'] ? 'bad' : 'ok' ?>"><?= gbdbui_e($msg) ?></div>
        <?php endif; ?>

        <section class="gbdbui-page-grid">
            <div class="gbdbui-panel gbdbui-safe-zone span-8">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Backup erstellen</h2>
                        <p>Quelle und Ziel prüfen, dann Kopie starten. Der Zielordner wird bei Bedarf angelegt.</p>
                    </div>
                    <span class="gbdbui-pill ok">Filesystem Copy</span>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <div class="gbdbui-form-grid">
                        <label class="field-12">Zielpfad
                            <input name="path" value="<?= gbdbui_e($defaultTarget) ?>" placeholder="/path/to/backup">
                        </label>
                    </div>
                    <p class="gbdbui-help">Quelle: <code><?= gbdbui_e(Vars::DB_PATH()) ?></code></p>
                    <div class="gbdbui-action-row">
                        <button class="primary" type="submit">Backup erstellen</button>
                        <a class="secondary" href="<?= gbdbui_e(gbdbui_url('dashboard')) ?>">Zurück</a>
                    </div>
                </form>
            </div>

            <div class="gbdbui-panel span-4">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Status</h2>
                        <p>Letzter Lauf und Report.</p>
                    </div>
                </div>
                <?php if ($report): ?>
                    <pre><?= gbdbui_e($report) ?></pre>
                <?php else: ?>
                    <div class="gbdbui-empty">Noch kein Backup in dieser Sitzung ausgeführt.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>

</html>
