<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('backup');

$msg = '';
$report = [];
$defaultTarget = Vars::data_root() . '/.backups/backup_' . date('Ymd_His');

/**
 * Prüft ein frei gewähltes Backup-Ziel: absolut, ohne "..", außerhalb des
 * Webroots und nicht innerhalb des zu sichernden Datenbestands.
 *
 * @param string $target Zielpfad.
 *
 * @return string Fehlermeldung oder leer.
 */
function gbdbui_backup_target_error(string $target) :string {
    if (str_contains($target, "\0") || preg_match('~(^|[\\/])\.\.([\\/]|$)~', $target)) {
        return 'Der Zielpfad darf weder ".." noch Steuerzeichen enthalten.';
    }

    if (str_starts_with($target, 'gbdbdata://')) {
        return str_starts_with($target, Vars::data_root() . '/.backups/') ? '' : 'Interne Ziele nur unterhalb von .backups.';
    }

    if (str_contains($target, '://') || (!str_starts_with($target, '/') && !preg_match('~^[A-Za-z]:[\\/]~', $target))) {
        return 'Bitte einen absoluten Dateisystempfad angeben.';
    }

    $normalized = rtrim(str_replace('\\', '/', $target), '/') . '/';
    $protected = [GBDBConfig::dataPath(), GBDBConfig::root()];
    $documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');

    if ($documentRoot !== '') {
        $protected[] = realpath($documentRoot) ?: $documentRoot;
    }

    foreach ($protected as $directory) {
        $directory = rtrim(str_replace('\\', '/', (string) $directory), '/') . '/';

        if ($directory !== '/' && str_starts_with($normalized, $directory)) {
            return 'Backups dürfen nicht im Webroot, im Framework oder im Datenbestand liegen. Ohne Angabe wird .backups im geschützten Datenordner verwendet.';
        }
    }

    return '';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
    } else {
        $target = rtrim(trim((string) ($_POST['path'] ?? '')), '/');

        if ($target === '') {
            $target = $defaultTarget;
        }

        $error = gbdbui_backup_target_error($target);

        if ($error !== '') {
            $msg = $error;
        } else {
            $report = gbdbui_copy_tree(Vars::DB_PATH(), $target);
            $msg = $report['ok'] ? 'Backup erstellt: ' . $target : 'Backup mit Fehlern.';
        }
    }
}

?>
<?php gbdbui_layout_start('backup', 'Backup', 'Erstellt eine vollständige Dateikopie der GBDB-Daten – sinnvoll vor Migration, Verschlüsselungswechsel oder Re-Install.', ['eyebrow' => 'Daten']); ?>

        <?php if ($msg) : ?>
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
                <?php if ($report) : ?>
                    <pre><?= gbdbui_e($report) ?></pre>
                <?php else : ?>
                    <div class="gbdbui-empty">Noch kein Backup in dieser Sitzung ausgeführt.</div>
                <?php endif; ?>
            </div>
        </section>

<?php gbdbui_layout_end(); ?>
