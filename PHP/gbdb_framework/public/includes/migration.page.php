<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/migration.logic.php';
?>
<?php gbdbui_layout_start('migration', 'Migration', 'Ältere GBDB-Strukturen auf das aktuelle Schema heben, optional ins Zielformat konvertieren und Meta-/Append-Dateien kontrolliert neu schreiben.', ['eyebrow' => 'Betrieb']); ?>

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

            <?php if ($errors) : ?>
                <div class="msgs">
                    <?php foreach ($errors as $error) : ?>
                        <div class="msg err">❌ <?= h($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="row legacy-tool-form" style="margin-top:12px;">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
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

            <?php if ($logs) : ?>
                <pre><?php foreach ($logs as $line) {
                    echo h($line) . "\n";
                     } ?></pre>
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

<?php gbdbui_layout_end(); ?>
