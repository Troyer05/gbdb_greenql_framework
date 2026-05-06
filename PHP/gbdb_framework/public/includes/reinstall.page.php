<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('reinstall');

$msg = '';
$ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
    } elseif ((string)($_POST['confirm1'] ?? '') === 'DATEN LÖSCHEN' && (string)($_POST['confirm2'] ?? '') === 'ICH VERSTEHE DAS RISIKO') {
        gbdbui_delete_tree(Vars::DB_PATH());
        @mkdir(Vars::DB_PATH(), 0777, true);
        $msg = 'GBDB Daten wurden gelöscht und der DB-Ordner wurde neu angelegt.';
        $ok = true;
    } else {
        $msg = 'Bestätigungen stimmen nicht. Nichts gelöscht.';
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Re-Install</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('reinstall'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Danger</p>
            <h1>Re-Install</h1>
            <p>Gefahrenbereich: löscht alle GBDB-Daten erst nach zwei expliziten Warnbestätigungen.</p>
        </section>

        <?php if ($msg): ?><div class="gbdbui-flash <?= $ok ? 'ok' : 'bad' ?>"><?= gbdbui_e($msg) ?></div><?php endif; ?>

        <section class="gbdbui-page-grid">
            <div class="gbdbui-panel gbdbui-danger-zone span-7">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Endgültig löschen</h2>
                        <p>Das entfernt Datenbanken, Tabellen, Append-Logs, WALs, Journals und Script-Daten im GBDB-Datenpfad.</p>
                    </div>
                    <span class="gbdbui-pill bad">irreversibel</span>
                </div>
                <form method="post" onsubmit="return confirm('Wirklich alle GBDB-Daten löschen?')">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <div class="gbdbui-form-grid">
                        <label class="field-6">Tippe exakt: DATEN LÖSCHEN
                            <input name="confirm1" autocomplete="off">
                        </label>
                        <label class="field-6">Tippe exakt: ICH VERSTEHE DAS RISIKO
                            <input name="confirm2" autocomplete="off">
                        </label>
                    </div>
                    <div class="gbdbui-action-row">
                        <button class="danger" type="submit">Re-Install ausführen</button>
                        <a class="secondary" href="<?= gbdbui_e(gbdbui_url('backup')) ?>">Erst Backup öffnen</a>
                    </div>
                </form>
            </div>

            <div class="gbdbui-panel span-5">
                <div class="gbdbui-panel-head"><div><h2>Checkliste</h2><p>Vor dem Löschen kurz prüfen.</p></div></div>
                <p class="gbdbui-help">1. Backup wurde erstellt und liegt außerhalb des DB-Pfads.</p>
                <p class="gbdbui-help">2. Kein Live-System nutzt diese Daten gerade produktiv.</p>
                <p class="gbdbui-help">3. Du willst wirklich einen frischen DB-Ordner.</p>
                <p class="gbdbui-help">Datenpfad: <code><?= gbdbui_e(Vars::DB_PATH()) ?></code></p>
            </div>
        </section>
    </main>
</body>
</html>
