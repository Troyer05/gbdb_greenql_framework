<?php
declare(strict_types=1);

require_once __DIR__ . '/_shared.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('enterprise');

$action = (string)($_POST['enterprise_action'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $action !== '') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_redirect('enterprise');
    }

    if ($action === 'install_pattern') {
        $pattern = trim((string)($_POST['pattern'] ?? ''));
        $instance = trim((string)($_POST['instance'] ?? ''));
        $res = GBDB::installPattern($pattern, $instance);
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? ('Pattern "'.$pattern.'" in Instanz "'.$instance.'" installiert.') : ('Pattern konnte nicht installiert werden: '.(string)($res['error'] ?? 'unknown')));
        gbdbui_redirect('enterprise');
    }

    if ($action === 'save_pattern') {
        $raw = (string)($_POST['pattern_json'] ?? '');
        $data = json_decode($raw, true);
        $res = is_array($data) ? GBDB::savePattern($data) : ['ok' => false, 'error' => 'invalid_json'];
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Pattern gespeichert.' : ('Pattern konnte nicht gespeichert werden: '.(string)($res['error'] ?? 'unknown')));
        gbdbui_redirect('enterprise');
    }

    if ($action === 'delete_pattern') {
        $res = GBDB::deletePattern((string)($_POST['pattern'] ?? ''));
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Pattern gelöscht.' : 'Pattern konnte nicht gelöscht werden.');
        gbdbui_redirect('enterprise');
    }

    if ($action === 'rename_pattern') {
        $res = GBDB::renamePattern((string)($_POST['pattern'] ?? ''), (string)($_POST['new_pattern_name'] ?? ''));
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Pattern umbenannt.' : ('Pattern konnte nicht umbenannt werden: '.(string)($res['error'] ?? 'unknown')));
        gbdbui_redirect('enterprise');
    }

    if ($action === 'toggle_pattern') {
        $res = GBDB::setPatternActive((string)($_POST['pattern'] ?? ''), (string)($_POST['active'] ?? '1') === '1');
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Pattern-Status gespeichert.' : 'Pattern-Status konnte nicht gespeichert werden.');
        gbdbui_redirect('enterprise');
    }

    if ($action === 'template_pattern') {
        $res = GBDB::savePattern(GBDB::patternTemplate((string)($_POST['template_name'] ?? 'pattern_template')));
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Pattern-Template erzeugt.' : 'Pattern-Template konnte nicht erzeugt werden.');
        gbdbui_redirect('enterprise');
    }

    if ($action === 'selftest') {
        $res = GBDB::enterpriseSelfTest();
        gbdbui_flash(($res['ok'] ?? false) ? 'ok' : 'bad', ($res['ok'] ?? false) ? 'Enterprise Self-Test erfolgreich.' : 'Enterprise Self-Test fehlgeschlagen.');
        gbdbui_redirect('enterprise');
    }

    if ($action === 'quota') {
        $limits = [
            'max_rows_per_table' => (int)($_POST['max_rows_per_table'] ?? 100000),
            'max_storage_per_instance' => (int)($_POST['max_storage_per_instance'] ?? 1073741824),
            'max_api_calls' => (int)($_POST['max_api_calls'] ?? 100000),
            'enforce' => isset($_POST['enforce'])
        ];
        GBDB::quotaLimits($limits);
        gbdbui_flash('ok', 'Quota-Limits gespeichert.');
        gbdbui_redirect('enterprise');
    }
}

$data = GBDB::adminUiData();
$quota = GBDB::quotaDashboard();
$docs = GBDB::documentationIndex();
$patterns = GBDB::listPatterns(true);
$patternTemplateJson = json_encode(GBDB::patternTemplate('new_pattern'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$patternJsonMap = [];
foreach ($patterns as $pattern) {
    $patternJsonMap[(string)$pattern['name']] = json_encode($pattern, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
}
$patternJsonMapRaw = json_encode($patternJsonMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$currentInstance = GBDB::getInstance();
$isSystemInstance = GreenQLUIv2Helper::reservedInstance($currentInstance) && !in_array($currentInstance, ['default'], true);
$visibleInstance = $isSystemInstance ? 'keine Benutzer-Instanz' : $currentInstance;
if ($isSystemInstance) {
    $tableRows = [];
} else {
    $tableRows = $data['tables'] ?? [];
}
$stats = $data['dashboard']['metrics'] ?? [];
$dbCount = count($tableRows);
$tableCount = 0;
foreach ($tableRows as $row) {
    $tableCount += count($row['tables'] ?? []);
}
$docList = $docs['docs'] ?? [];
$quotaWarnings = count($quota['current']['warnings'] ?? []);
$quotaOk = (bool)($quota['current']['ok'] ?? true);

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Enterprise Ops</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>
<body class="gbdbui-dashboard gbdbui-pro gbdbui-enterprise-body">
    <?php gbdbui_nav('enterprise'); ?>
    <main class="gbdbui-wide">

<section class="enterprise-hero-shell">
    <div class="enterprise-hero-copy">
        <p class="gbdbui-kicker">GBDB Enterprise Ops</p>
        <h1>Enterprise Control Center</h1>
        <p>Intranet-Pattern, Tenant-Grenzen, Quotas, Monitoring, Import/Export und Doku an einer Stelle – sauber gebündelt für größere Setups.</p>
        <div class="enterprise-hero-actions">
            <a class="primary gbdbui-btn" href="<?= gbdbui_e(gbdbui_url('monitoring')) ?>">Monitoring öffnen</a>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="enterprise_action" value="selftest">
                <button class="secondary">Self-Test starten</button>
            </form>
        </div>
    </div>
    <div class="enterprise-hero-status">
        <span class="enterprise-orb"></span>
        <small>Aktive Instanz</small>
        <strong><?= gbdbui_e($visibleInstance) ?></strong>
        <p><?= $quotaOk ? 'Quota-System meldet keine harten Limits.' : 'Quota-System meldet aktive Limits/Warnungen.' ?></p>
    </div>
</section>

<?php gbdbui_render_flashes(); ?>

<section class="enterprise-kpi-grid">
    <article class="enterprise-kpi-card">
        <span>Datenbanken</span>
        <strong><?= $dbCount ?></strong>
        <small>Bases in aktueller Instanz</small>
    </article>
    <article class="enterprise-kpi-card">
        <span>Tabellen</span>
        <strong><?= $tableCount ?></strong>
        <small>Enterprise-relevante Übersicht</small>
    </article>
    <article class="enterprise-kpi-card">
        <span>Queries</span>
        <strong><?= (int)($stats['queries'] ?? 0) ?></strong>
        <small>Ø <?= round((float)($stats['query_time_avg'] ?? 0), 2) ?> ms</small>
    </article>
    <article class="enterprise-kpi-card <?= $quotaOk ? 'is-ok' : 'is-warn' ?>">
        <span>Quota</span>
        <strong><?= $quotaOk ? 'OK' : 'Limit' ?></strong>
        <small><?= $quotaWarnings ?> Warnungen</small>
    </article>
</section>

<section class="enterprise-action-grid">
    <article class="enterprise-action-card enterprise-card-glow">
        <div class="enterprise-card-head">
            <span class="enterprise-icon">PT</span>
            <div>
                <h2>Pattern installieren</h2>
                <p>Wählt ein JSON-Pattern aus <code>gbdb_framework/json/patterns</code>, erstellt eine neue Instanz und legt darin Bases, Tabellen und optionale Datentypen an.</p>
            </div>
        </div>
        <form method="post" class="stack-form compact-form enterprise-form-row">
            <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
            <input type="hidden" name="enterprise_action" value="install_pattern">
            <label>Pattern
                <select name="pattern">
                    <?php foreach ($patterns as $pattern): if (empty($pattern['active'])) continue; ?>
                        <option value="<?= gbdbui_e((string)$pattern['name']) ?>"><?= gbdbui_e((string)$pattern['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Neue Instanz<input name="instance" placeholder="z. B. museum_intranet"></label>
            <button class="primary">Pattern in Instanz installieren</button>
        </form>
    </article>

    <article class="enterprise-action-card">
        <div class="enterprise-card-head">
            <span class="enterprise-icon">QL</span>
            <div>
                <h2>Quota Enforcement</h2>
                <p>Grenzen für Rows, Storage und API-Calls zentral setzen. Optional hart erzwingen.</p>
            </div>
        </div>
        <form method="post" class="enterprise-quota-grid">
            <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
            <input type="hidden" name="enterprise_action" value="quota">
            <label>Max Rows/Table<input name="max_rows_per_table" type="number" value="<?= (int)($quota['limits']['max_rows_per_table'] ?? 100000) ?>"></label>
            <label>Max Storage/Instance Bytes<input name="max_storage_per_instance" type="number" value="<?= (int)($quota['limits']['max_storage_per_instance'] ?? 1073741824) ?>"></label>
            <label>Max API Calls<input name="max_api_calls" type="number" value="<?= (int)($quota['limits']['max_api_calls'] ?? 100000) ?>"></label>
            <label class="checkline enterprise-check"><input name="enforce" type="checkbox" <?= !empty($quota['limits']['enforce']) ? 'checked' : '' ?>> Limits aktiv erzwingen</label>
            <button class="primary">Limits speichern</button>
        </form>
    </article>
</section>

<section class="enterprise-action-grid enterprise-pattern-manager">
    <article class="enterprise-action-card">
        <div class="enterprise-card-head slim">
            <span class="enterprise-icon">PM</span>
            <div>
                <h2>Pattern Manager</h2>
                <p>Pattern erstellen, bearbeiten, aktivieren/deaktivieren oder löschen. Die Struktur folgt <code>name</code>, <code>structure</code>, <code>base</code>, <code>tables</code>, <code>useDataTypes</code> und <code>rows</code>.</p>
            </div>
        </div>
        <div class="table-scroll enterprise-table-scroll">
            <table class="enterprise-pattern-table">
                <thead><tr><th>Filename</th><th>Aktiv?</th><th>Aktionen</th></tr></thead>
                <tbody>
                <?php foreach ($patterns as $pattern): ?>
                    <?php $patternName = (string)$pattern['name']; $fileName = (string)($pattern['_file'] ?? ($patternName . '.json')); ?>
                    <tr>
                        <td>
                            <strong><?= gbdbui_e($fileName) ?></strong><br>
                            <small><?= gbdbui_e($patternName) ?><?= trim((string)($pattern['description'] ?? '')) !== '' ? ' · '.gbdbui_e((string)$pattern['description']) : '' ?></small>
                        </td>
                        <td><span class="status-pill <?= !empty($pattern['active']) ? 'done' : 'queued' ?>"><?= !empty($pattern['active']) ? 'aktiv' : 'inaktiv' ?></span></td>
                        <td>
                            <div class="enterprise-pattern-actions">
                                <button type="button" class="secondary enterprise-edit-pattern" data-pattern="<?= gbdbui_e($patternName) ?>">Bearbeiten</button>
                                <form method="post" class="inline-form enterprise-rename-form">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                                    <input type="hidden" name="enterprise_action" value="rename_pattern">
                                    <input type="hidden" name="pattern" value="<?= gbdbui_e($patternName) ?>">
                                    <input name="new_pattern_name" value="<?= gbdbui_e($patternName) ?>" aria-label="Neuer Pattern-Name">
                                    <button class="secondary">Umbenennen</button>
                                </form>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                                    <input type="hidden" name="pattern" value="<?= gbdbui_e($patternName) ?>">
                                    <input type="hidden" name="active" value="<?= !empty($pattern['active']) ? '0' : '1' ?>">
                                    <button class="secondary" name="enterprise_action" value="toggle_pattern"><?= !empty($pattern['active']) ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                </form>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                                    <input type="hidden" name="pattern" value="<?= gbdbui_e($patternName) ?>">
                                    <button class="danger" name="enterprise_action" value="delete_pattern" onclick="return confirm('Pattern wirklich löschen?')">Löschen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($patterns)): ?><tr><td colspan="3" class="muted">Keine Patterns gefunden.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <form method="post" class="stack-form compact-form enterprise-form-row">
            <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
            <input type="hidden" name="enterprise_action" value="template_pattern">
            <label>Template-Name<input name="template_name" value="new_pattern"></label>
            <button class="secondary">Pattern-Template Generator</button>
        </form>
    </article>
    <article class="enterprise-action-card">
        <div class="enterprise-card-head slim">
            <span class="enterprise-icon">JS</span>
            <div>
                <h2>Pattern erstellen / bearbeiten</h2>
                <p>JSON einfügen und speichern. Bestehende Patterns werden anhand von <code>name</code> überschrieben.</p>
            </div>
        </div>
        <form method="post" class="stack-form compact-form enterprise-form-row" id="enterprise-pattern-editor-form">
            <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
            <input type="hidden" name="enterprise_action" value="save_pattern">
            <div class="gbdbui-editor-head enterprise-editor-head">
                <strong id="enterprise-editor-title">Template: new_pattern</strong>
                <div>
                    <button type="button" class="gbdbui-mini-btn" id="enterprise-format-json">Auto-Einrücken</button>
                    <button type="button" class="gbdbui-mini-btn" id="enterprise-reset-template">Template laden</button>
                </div>
            </div>
            <div class="gbdbui-highlight-editor enterprise-json-editor" data-lang="json">
                <pre aria-hidden="true"></pre>
                <textarea id="enterprise-pattern-json" name="pattern_json" rows="18" spellcheck="false" autocomplete="off"><?= gbdbui_e($patternTemplateJson) ?></textarea>
            </div>
            <button class="primary">Pattern speichern</button>
        </form>
    </article>
</section>

<section class="enterprise-feature-strip">
    <a href="<?= gbdbui_e(gbdbui_url('monitoring')) ?>" class="enterprise-feature-card">
        <span>Jobs</span>
        <strong>Jobs & Queue Monitoring</strong>
        <small>Queued, running, failed, scheduled und Dead-Letter Einträge prüfen.</small>
    </a>
    <form method="post" class="enterprise-feature-card as-form">
        <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
        <input type="hidden" name="enterprise_action" value="selftest">
        <span>Health</span>
        <strong>Enterprise Self-Test</strong>
        <small>Quota, Rate-Limit Runtime, SSO-Konfig, Adapter und Doku-Registry testen.</small>
        <button class="secondary">Jetzt prüfen</button>
    </form>
    <div class="enterprise-feature-card">
        <span>Docs</span>
        <strong><?= count($docList) ?> Doku-Dateien</strong>
        <small>API-, Migrations-, Operations- und Security-Dokumentation im Framework.</small>
    </div>
</section>

<section class="enterprise-data-grid">
    <article class="enterprise-action-card enterprise-table-card">
        <div class="enterprise-card-head slim">
            <span class="enterprise-icon">DB</span>
            <div>
                <h2>Tabellenübersicht</h2>
                <p>Alle Bases mit den aktuell erkannten Tabellen der aktiven GBDB-Instanz.</p>
            </div>
        </div>
        <div class="table-scroll enterprise-table-scroll">
            <table>
                <thead><tr><th>Base</th><th>Tabellen</th></tr></thead>
                <tbody>
                <?php foreach ($tableRows as $row): ?>
                    <tr>
                        <td><strong><?= gbdbui_e((string)$row['database']) ?></strong></td>
                        <td>
                            <div class="enterprise-chip-wrap">
                                <?php foreach (($row['tables'] ?? []) as $table): ?>
                                    <span class="enterprise-chip"><?= gbdbui_e((string)$table) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($tableRows)): ?>
                    <tr><td colspan="2" class="muted">Keine Tabellen gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="enterprise-action-card enterprise-doc-card">
        <div class="enterprise-card-head slim">
            <span class="enterprise-icon">DO</span>
            <div>
                <h2>Doku / API</h2>
                <p>Neue Enterprise- und Operations-Dokumente im Framework.</p>
            </div>
        </div>
        <div class="enterprise-doc-list">
            <?php foreach ($docList as $doc): ?>
                <code><?= gbdbui_e((string)$doc) ?></code>
            <?php endforeach; ?>
            <?php if (empty($docList)): ?>
                <span class="muted">Keine Doku-Dateien gefunden.</span>
            <?php endif; ?>
        </div>
    </article>
</section>
    </main>
    <script>
    window.GBDB_ENTERPRISE_PATTERN_TEMPLATE = <?= json_encode($patternTemplateJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.GBDB_ENTERPRISE_PATTERNS = <?= $patternJsonMapRaw ?>;
    </script>
    <script src="gbdb_framework/public/js/gbdbui_highlight.js?v=2026.8"></script>
    <script src="gbdb_framework/public/js/gbdbui_enterprise.js?v=2026.8"></script>
</body>
</html>
