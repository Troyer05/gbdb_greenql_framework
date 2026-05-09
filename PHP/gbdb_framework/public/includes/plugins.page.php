<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('plugins');

$dir = dirname(__DIR__, 2) . '/plugins';
$disabled = $dir . '/.disabled';

if (!is_dir($disabled))
    @mkdir($disabled, 0777, true);
$msg = '';
$msgType = 'ok';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
        $msgType = 'bad';
    } else {
        $act = (string) ($_POST['action'] ?? '');
        $file = basename((string) ($_POST['file'] ?? ''));

        if ($act === 'upload' && isset($_FILES['plugin']) && is_uploaded_file($_FILES['plugin']['tmp_name'])) {
            $name = basename((string) $_FILES['plugin']['name']);

            if (str_ends_with($name, '.php')) {
                move_uploaded_file($_FILES['plugin']['tmp_name'], $dir . '/' . $name);
                $msg = 'Plugin hochgeladen.';
            } else {
                $msg = 'Nur .php Plugins erlaubt.';
                $msgType = 'bad';
            }

        }

        if ($act === 'delete' && $file !== '' && str_ends_with($file, '.php')) {
            @unlink($dir . '/' . $file);
            @unlink($disabled . '/' . $file);
            $msg = 'Plugin gelöscht.';
        }

        if ($act === 'disable' && is_file($dir . '/' . $file)) {
            @rename($dir . '/' . $file, $disabled . '/' . $file);
            $msg = 'Plugin deaktiviert.';
        }

        if ($act === 'enable' && is_file($disabled . '/' . $file)) {
            @rename($disabled . '/' . $file, $dir . '/' . $file);
            $msg = 'Plugin aktiviert.';
        }

    }

}

$plugins = [];

foreach (glob($dir . '/*.php') ?: [] as $f)
    $plugins[] = ['file' => basename($f), 'active' => true, 'size' => filesize($f)];

foreach (glob($disabled . '/*.php') ?: [] as $f)
    $plugins[] = ['file' => basename($f), 'active' => false, 'size' => filesize($f)];
usort($plugins, fn($a, $b) => strcmp((string) $a['file'], (string) $b['file']));
?>
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Plugins</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>

<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('plugins'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Extend</p>
            <h1>Plugins</h1>
            <p>Plugins verwalten: Upload, löschen, aktivieren und deaktivieren.</p>
        </section>

        <?php if ($msg): ?>
            <div class="gbdbui-flash <?= gbdbui_e($msgType) ?>"><?= gbdbui_e($msg) ?></div><?php endif; ?>

        <section class="gbdbui-page-grid">
            <div class="gbdbui-panel span-4">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Upload</h2>
                        <p>Nur PHP-Plugin-Dateien werden angenommen.</p>
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <input type="hidden" name="action" value="upload">
                    <label>Plugin-Datei
                        <input type="file" name="plugin" accept=".php">
                    </label>
                    <div class="gbdbui-action-row"><button class="primary" type="submit">Plugin hochladen</button></div>
                </form>
            </div>

            <div class="gbdbui-panel span-8">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Installierte Plugins</h2>
                        <p><?= count($plugins) ?> Plugin-Datei(en) gefunden.</p>
                    </div>
                    <span class="gbdbui-pill">/plugins</span>
                </div>
                <?php if ($plugins): ?>
                    <div class="gbdbui-table-wrap">
                        <table class="gbdbui-table">
                            <thead>
                                <tr>
                                    <th>Datei</th>
                                    <th>Status</th>
                                    <th>Größe</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plugins as $p): ?>
                                    <tr>
                                        <td><code><?= gbdbui_e($p['file']) ?></code></td>
                                        <td><span
                                                class="gbdbui-pill <?= $p['active'] ? 'ok' : 'bad' ?>"><?= $p['active'] ? 'aktiv' : 'deaktiviert' ?></span>
                                        </td>
                                        <td><?= gbdbui_e(gbdbui_bytes((int) $p['size'])) ?></td>
                                        <td>
                                            <form method="post" class="gbdbui-action-row" style="margin:0">
                                                <input type="hidden" name="csrf"
                                                    value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                                                <input type="hidden" name="file" value="<?= gbdbui_e($p['file']) ?>">
                                                <button class="secondary" name="action"
                                                    value="<?= $p['active'] ? 'disable' : 'enable' ?>"
                                                    type="submit"><?= $p['active'] ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                                <button class="danger" name="action" value="delete" type="submit"
                                                    onclick="return confirm('Plugin löschen?')">Löschen</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="gbdbui-empty">Keine Plugins installiert.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>

</html>
