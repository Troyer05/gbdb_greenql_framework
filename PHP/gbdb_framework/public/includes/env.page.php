<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('env');

$file = dirname(__DIR__, 2) . '/.config/.framework.env.php';
$msg = '';
$msgType = 'ok';
$content = is_file($file) ? (string)file_get_contents($file) : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['action'] ?? '') === 'save') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
        $msgType = 'bad';
    } else {
        $new = (string)($_POST['content'] ?? '');
        $tmp = tempnam(sys_get_temp_dir(), 'envphp_');
        file_put_contents($tmp, $new);
        $lint = trim((string)shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1'));
        @unlink($tmp);

        if (str_contains($lint, 'No syntax errors')) {
            file_put_contents($file, $new);
            $content = $new;
            $msg = '.framework.env.php gespeichert und Syntax geprüft.';
        } else {
            $msg = 'Syntaxfehler, nicht gespeichert: ' . $lint;
            $msgType = 'bad';
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB ENV</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('env'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Config</p>
            <h1>ENV</h1>
            <p>.framework.env.php ansehen und vorsichtig bearbeiten. Vor dem Speichern läuft ein PHP-Lint.</p>
        </section>

        <?php if ($msg): ?><div class="gbdbui-flash <?= gbdbui_e($msgType) ?>"><?= gbdbui_e($msg) ?></div><?php endif; ?>

        <section class="gbdbui-panel" style="margin-top:18px">
            <div class="gbdbui-panel-head">
                <div>
                    <h2>Framework ENV Editor</h2>
                    <p>Pfad: <code><?= gbdbui_e($file) ?></code></p>
                </div>
                <span class="gbdbui-pill">php -l</span>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="action" value="save">
                <div class="gbdbui-highlight-editor" data-lang="php">
                    <pre aria-hidden="true"></pre>
                    <textarea name="content" spellcheck="false"><?= gbdbui_e($content) ?></textarea>
                </div>
                <div class="gbdbui-action-row">
                    <button class="primary" type="submit">Speichern & prüfen</button>
                    <a class="secondary" href="<?= gbdbui_e(gbdbui_url('dashboard')) ?>">Abbrechen</a>
                </div>
            </form>
        </section>
    </main>
    <script src="gbdb_framework/public/js/gbdbui_highlight.js?v=2026.4"></script>
</body>
</html>
