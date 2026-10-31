<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('php_exec');

$defaultCode = <<<'PHPDEF'
<?php
// Schneller Framework-Test im DEV-Browser.
// Ausgabe landet rechts im Output-Block.

echo hash('sha256', 'hallo') . "\n";

print_r([
    "version" => Vars::app_version(),
    "dev" => Vars::__DEV__(),
    "db_arch" => class_exists("Vars") && method_exists("Vars", "db_arch") ? Vars::db_arch() : (defined("DB_ARCH") ? DB_ARCH : "GBDB")
]);
PHPDEF;

$code = (string)($_POST['code'] ?? $defaultCode);
$result = null;

/**
 * Entfernt öffnende PHP-Tags für eval.
 * @param string $code PHP-Code.
 * @return string Rückgabewert.
 */

function gbdbui_php_exec_prepare(string $code) :string {
    $code = preg_replace('/^\s*<\?(php)?/i', '', $code) ?? $code;
    $code = preg_replace('/\?>\s*$/', '', $code) ?? $code;

    return $code;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['php_action'] ?? '') === 'run') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $result = ['ok' => false, 'output' => '', 'error' => 'Ungültiger Sicherheits-Token.'];
    } else {
        $prepared = gbdbui_php_exec_prepare($code);
        $output = '';
        $error = '';
        $ok = true;

        ob_start();
        try {
            eval($prepared);
        } catch (Throwable $e) {
            $ok = false;
            $error = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        } finally {
            $output = (string)ob_get_clean();
        }

        $result = ['ok' => $ok, 'output' => $output, 'error' => $error];
    }
}

?>
<?php gbdbui_layout_start('php_exec', 'PHP Exec', 'Framework-Code direkt im Browser testen. Nur für Admins und nur mit UI → allow_php_exec.', ['eyebrow' => 'System']); ?>

        <section class="gbdbui-exec-layout">
            <form method="post" class="gbdbui-exec-editor" id="php-exec-form">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="php_action" value="run">
                <div class="gbdbui-editor-head">
                    <strong>PHP Editor</strong>
                    <div>
                        <button type="button" class="gbdbui-mini-btn" id="indent-code" data-editor-format="php-code">Auto-Einrücken</button>
                        <button class="gbdbui-mini-btn primary">Ausführen</button>
                    </div>
                </div>
                <div class="gbdbui-code-wrap gbdbui-php-editor-shell" data-lang="php">
                    <pre id="php-highlight" aria-hidden="true"></pre>
                    <textarea id="php-code" name="code" spellcheck="false" autocomplete="off">
<?= gbdbui_e($code) ?></textarea>
                </div>
            </form>

            <aside class="gbdbui-output-card">
                <div class="gbdbui-editor-head"><strong>Output</strong><span><?= $result === null ? 'bereit' : (!empty($result['ok']) ? 'ok' : 'error') ?></span></div>
                <?php if ($result === null) : ?>
                    <pre>Noch keine Ausführung.</pre>
                <?php else : ?>
                    <?php if ((string)$result['error'] !== '') :
                        ?><div class="gbdbui-flash bad"><?= gbdbui_e($result['error']) ?></div><?php
                    endif; ?>
                    <pre><?= gbdbui_e((string)$result['output']) ?></pre>
                <?php endif; ?>
            </aside>
        </section>


    <script src="<?= gbdbui_e(gbdbui_asset('js/gbdbui_highlight.js')) ?>"></script>
<?php gbdbui_layout_end(); ?>
