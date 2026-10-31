<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('env');

$file = GBDBConfig::file();
$msg = '';
$msgType = 'ok';
$raw = (string) file_get_contents($file);
$constants = GBDBConfig::editableConstants();
$content = json_encode($constants, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$revision = hash('sha256', $raw);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['action'] ?? '') === 'save') {
    $submitted = str_replace(["\r\n", "\r"], "\n", (string) ($_POST['content'] ?? ''));

    try {
        if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Ungültiger CSRF Token.');
        }

        if (!hash_equals($revision, (string) ($_POST['config_revision'] ?? ''))) {
            throw new RuntimeException('Die Konfiguration wurde zwischenzeitlich geändert. Seite neu laden.');
        }

        $new = json_decode($submitted, true, 64, JSON_THROW_ON_ERROR);

        if (!is_array($new)) {
            throw new InvalidArgumentException('Ein JSON-Objekt mit den Konfigurationsbereichen ist erforderlich.');
        }

        $parsed = GBDBConfig::constantsToSections($new);
        $current = Vars::configuration();

        foreach ([['storage', 'path'], ['security', 'crypt_data'], ['security', 'crypt_key'], ['security', 'installation_key']] as [$section, $key]) {
            if (($parsed[$section][$key] ?? null) !== ($current[$section][$key] ?? null)) {
                throw new RuntimeException('Datenpfad und Verschlüsselungsschlüssel eines laufenden Bestands benötigen eine gesonderte Datenmigration.');
            }
        }

        // Diese Schalter geben Code-Ausführung bzw. die Konfiguration selbst frei und bleiben deshalb der Datei vorbehalten.
        foreach (['allow_php_exec', 'allow_plugin_upload', 'allow_reinstall', 'allow_config_edit'] as $key) {
            if (($parsed['ui'][$key] ?? null) !== ($current['ui'][$key] ?? null)) {
                throw new RuntimeException('UI.' . $key . ' lässt sich nur direkt in .framework.env.php ändern, nicht über die Oberfläche.');
            }
        }

        if (GBDB::backendStatus()['mode'] === 'sql') {
            if (!$parsed['failover']['enabled']) {
                throw new RuntimeException('Zuerst kontrolliert nach GBDB zurückschalten.');
            }

            foreach (['failover', 'failover.dev'] as $section) {
                foreach (['dsn', 'prefix'] as $key) {
                    if (($parsed[$section][$key] ?? null) !== ($current[$section][$key] ?? null)) {
                        throw new RuntimeException('Zuerst kontrolliert nach GBDB zurückschalten, bevor das SQL-Ziel geändert wird.');
                    }
                }
            }
        }

        if (!GreenQLUIv2Helper::hasUsers() && strlen($parsed['ui']['setup_token']) < 24) {
            throw new RuntimeException('UI.setup_token muss mindestens 24 Zeichen lang sein.');
        }

        $payload = GBDBConfig::sourceWithConstants($new);
        $saved = GBDBRuntime::run(static function () use ($file, $raw, $payload) :bool {
            if (file_get_contents($file) !== $raw) {
                throw new RuntimeException('Die Konfiguration wurde zwischenzeitlich geändert. Seite neu laden.');
            }

            return GBDBStorage::atomicWrite($file, $payload);
        });

        if (!$saved) {
            throw new RuntimeException('Konfiguration konnte nicht gespeichert werden. Schreibrechte prüfen.');
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }

        $content = json_encode($new, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $revision = hash('sha256', $payload);
        $msg = '.framework.env.php gespeichert. PHP-Konstanten geprüft; Änderungen gelten ab dem nächsten Aufruf.';
    } catch (JsonException $exception) {
        $msg = 'Ungültiges JSON: ' . $exception->getMessage();
        $msgType = 'bad';
    } catch (Throwable $exception) {
        $msg = $exception->getMessage();
        $msgType = 'bad';
    }

    // Nach einem Fehler bleibt die Eingabe zum Korrigieren im Editor; bei einem Versionskonflikt gilt der aktuelle Dateistand.
    if ($msgType === 'bad' && hash_equals($revision, (string) ($_POST['config_revision'] ?? ''))) {
        $content = $submitted;
    }
}

?>
<?php gbdbui_layout_start('env', 'Framework-Konfiguration', 'Die einzige Konfiguration ist .config/.framework.env.php mit der Vars-Klasse. Hier werden ihre Konstanten als JSON bearbeitet und wieder als PHP gespeichert.', ['eyebrow' => 'System']); ?>

        <?php if ($msg) : ?>
            <div class="gbdbui-flash <?= gbdbui_e($msgType) ?>"><?= gbdbui_e($msg) ?></div><?php
        endif; ?>

        <section class="gbdbui-panel" style="margin-top:18px">
            <div class="gbdbui-panel-head">
                <div>
                    <h2>Framework ENV Editor</h2>
                    <p>Pfad: <code><?= gbdbui_e($file) ?></code></p>
                </div>
                <span class="gbdbui-pill">PHP · Vars</span>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="config_revision" value="<?= gbdbui_e($revision) ?>">
                <p>Bereiche und Datentypen beibehalten. Methoden werden unverändert übernommen; eingegebener PHP-Code wird nicht ausgeführt.</p>
                <div class="gbdbui-highlight-editor" data-lang="json">
                    <pre aria-hidden="true"></pre>
                    <textarea name="content" spellcheck="false" autocomplete="off">
<?= gbdbui_e($content) ?></textarea>
                </div>
                <div class="gbdbui-action-row">
                    <button class="primary" type="submit">Speichern & prüfen</button>
                    <a class="secondary" href="<?= gbdbui_e(gbdbui_url('dashboard')) ?>">Abbrechen</a>
                </div>
            </form>
        </section>

    <script src="<?= gbdbui_e(gbdbui_asset('js/gbdbui_highlight.js')) ?>"></script>
<?php gbdbui_layout_end(); ?>
