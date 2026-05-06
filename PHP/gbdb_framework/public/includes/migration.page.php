<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/migration.logic.php';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Migration</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
    <link rel="stylesheet" href="gbdb_framework/public/css/migration.css">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
<?php gbdbui_nav('migration'); ?>
<main class="gbdbui-wide legacy-tool-page">
    <section class="gbdbui-hero legacy-tool-hero">
        <p class="gbdbui-kicker">Migration</p>
        <h1>GBDB Migration</h1>
        <p>
            Migrate older GBDB structures to the current schema, optionally convert to the target storage format
            and rewrite meta / append files in a controlled way. This area is intended for development and maintenance work.
        </p>
    </section>

    <section class="gbdbui-panel legacy-tool-panel">
        <div class="gbdbui-panel-head compact">
            <div>
                <h3>Migration job</h3>
                <p>
                    Root: <code><?= h($GBDB_ROOT) ?></code><br>
                    Target from ENV: <code><?= $targetCrypt ? 'crypt=true (.db)' : 'crypt=false (.json)' ?></code>
                    · Extension: <code><?= h($targetExt) ?></code>
                </p>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="msgs">
                <?php foreach ($errors as $error): ?>
                    <div class="msg err">❌ <?= h($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="row legacy-tool-form" style="margin-top:12px;">
            <input type="hidden" name="do" value="migrate">

            <label>
                <span class="muted">Modus</span>
                <select name="convert_mode">
                    <option value="no">Nur Struktur-Upgrade (Meta/Append pro Tabelle, Base bleibt)</option>
                    <option value="to_target_schema">In Zielschema umwandeln (Backup + Swap)</option>
                </select>
            </label>

            <label class="legacy-check">
                <input type="checkbox" name="force" value="1">
                <span>Meta / Append neu schreiben (Backup)</span>
            </label>

            <div class="legacy-tool-actions">
                <button class="primary" type="submit">Migration starten</button>
            </div>
        </form>

        <?php if ($logs): ?>
            <pre><?php foreach ($logs as $line) { echo h($line) . "\n"; } ?></pre>
        <?php endif; ?>
    </section>

    <section class="gbdbui-panel legacy-note-panel">
        <div class="gbdbui-panel-head compact">
            <div>
                <h3>Hinweis</h3>
                <p>
                    Wenn du auf <code>crypt=true</code> wechseln möchtest, nutze <b>„In Zielschema umwandeln“</b>.
                    Ein reines In-place-Upgrade würde sonst einen Mischzustand erzeugen.
                </p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
