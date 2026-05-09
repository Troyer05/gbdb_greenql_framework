<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/optimize.logic.php';
?>
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Optimize</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
    <link rel="stylesheet" href="gbdb_framework/public/css/optimize.css">
</head>

<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('optimize'); ?>
    <main class="gbdbui-wide legacy-tool-page">
        <section class="gbdbui-hero legacy-tool-hero">
            <p class="gbdbui-kicker">Optimization</p>
            <h1>GBDB Optimize</h1>
            <p>
                Compact tables, inspect append/meta state and reduce write overhead after many inserts, edits and
                deletes.
                Use this section as a maintenance tool, not as something that runs on every request.
            </p>
        </section>

        <section class="gbdbui-panel legacy-tool-panel">
            <div class="gbdbui-panel-head compact">
                <div>
                    <h3>Compaction workspace</h3>
                    <p>Select a base and optionally a table to inspect detailed statistics or start compaction.</p>
                </div>
            </div>

            <?php if (!empty($msgs)): ?>
                <div class="msgs">
                    <?php foreach ($msgs as $msg): ?>
                        <div class="msg"><?= h($msg) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="legacy-tool-stack">
                <form method="get" class="row legacy-tool-form">
                    <label>
                        <span class="muted">DB</span>
                        <select name="db" onchange="this.form.submit()">
                            <option value="">– auswählen –</option>
                            <?php foreach ($dbs as $db): ?>
                                <option value="<?= h($db) ?>" <?= ($db === $selDb ? 'selected' : '') ?>><?= h($db) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span class="muted">Table</span>
                        <select name="table">
                            <option value="">– auswählen –</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= h($table) ?>" <?= ($table === $selTable ? 'selected' : '') ?>>
                                    <?= h($table) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="legacy-tool-actions">
                        <button type="submit">Anzeigen</button>
                    </div>
                </form>

                <?php if ($selDb !== ''): ?>
                    <div class="row legacy-tool-actions-row">
                        <form method="post">
                            <input type="hidden" name="action" value="compact_all">
                            <input type="hidden" name="db" value="<?= h($selDb) ?>">
                            <button class="primary" type="submit">Compact ALL in <?= h($selDb) ?></button>
                        </form>

                        <?php if ($selTable !== ''): ?>
                            <form method="post">
                                <input type="hidden" name="action" value="compact_one">
                                <input type="hidden" name="db" value="<?= h($selDb) ?>">
                                <input type="hidden" name="table" value="<?= h($selTable) ?>">
                                <button type="submit">Compact: <?= h($selTable) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($selTable !== ''): ?>
                        <?php if (isset($stats['error'])): ?>
                            <p class="muted" style="margin-top:12px;">❌ <?= h($stats['error']) ?></p>
                        <?php else: ?>
                            <div class="kv">
                                <div class="muted">Mode</div>
                                <div><code><?= h((string) $stats['mode']) ?></code></div>

                                <div class="muted">Rows / last_id</div>
                                <div><code><?= h((string) $stats['rows']) ?> / <?= h((string) $stats['last_id']) ?></code></div>

                                <div class="muted">append_ops (meta)</div>
                                <div><code><?= h((string) $stats['append_ops']) ?></code></div>

                                <div class="muted">Append lines</div>
                                <div><code><?= h((string) $stats['append_lines']) ?></code></div>

                                <div class="muted">Base / Meta / Append size</div>
                                <div>
                                    <code><?= h((string) $stats['base_size']) ?> / <?= h((string) $stats['meta_size']) ?> / <?= h((string) $stats['append_size']) ?></code>
                                </div>

                                <div class="muted">Paths</div>
                                <div>
                                    <div>Base: <code><?= h((string) ($stats['paths']['base'] ?? '')) ?></code></div>
                                    <div>Meta: <code><?= h((string) ($stats['paths']['meta'] ?? '')) ?></code></div>
                                    <div>Append: <code><?= h((string) ($stats['paths']['append'] ?? '')) ?></code></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted">Wähle zuerst eine Datenbank.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="gbdbui-panel legacy-note-panel">
            <div class="gbdbui-panel-head compact">
                <div>
                    <h3>Tipp</h3>
                    <p>Nach vielen Inserts, Edits oder Deletes lohnt sich ein Compact-Lauf, um Base-Dateien sauber neu
                        zu schreiben.</p>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
