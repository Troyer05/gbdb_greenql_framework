<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/cryption.logic.php';
?>
<?php gbdbui_layout_start('cryption', 'Verschlüsselung', 'Legacy-Konverter für historische, flache Datenbestände. GBDB-Full-Instanzen werden zum Schutz vor Datenverlust nicht konvertiert; vor jeder Umstellung entsteht ein Backup.', ['eyebrow' => 'Zugriff']); ?>

        <section class="gbdbui-panel legacy-tool-panel">
            <div class="gbdbui-panel-head compact">
                <div>
                    <h3>Migration target</h3>
                    <p>
                        <span class="legacy-pill">GBDB Path: <b><?= h($GBDB_ROOT) ?></b></span>
                        <span class="legacy-pill">Erkannt: <b><?= h($state) ?></b></span>
                        <span class="legacy-pill">Plain Ext: <b><?= h($EXT_PLAIN) ?></b></span>
                        <span class="legacy-pill">Enc Ext: <b><?= h($EXT_ENC) ?></b></span>
                    </p>
                </div>
            </div>

            <?php if ($errors) : ?>
                <div class="err">
                    <?php foreach ($errors as $error) : ?>
                        <div>❌ <?= h($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($logs) : ?>
                <div class="ok">
                    <div>✅ Vorgang abgeschlossen. Details unten.</div>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <fieldset>
                    <legend>Aktion wählen</legend>

                    <label>
                        <input type="radio" name="action" value="encrypt" <?= ($action === 'encrypt' ? 'checked' : '') ?>>
                        <div>
                            <div><b>Unverschlüsselt → Verschlüsselt</b></div>
                            <div class="note">
                                Erzeugt Token-Ordner/Dateien + Index-Dateien + verschlüsselt Inhalte in
                                <b><?= h($EXT_ENC) ?></b>.
                            </div>
                        </div>
                    </label>

                    <label>
                        <input type="radio" name="action" value="decrypt" <?= ($action === 'decrypt' ? 'checked' : '') ?>>
                        <div>
                            <div><b>Verschlüsselt → Unverschlüsselt</b></div>
                            <div class="note">
                                Liest Index-Dateien, entschlüsselt Inhalte und schreibt Klartext-Struktur als
                                <b><?= h($EXT_PLAIN) ?></b>.
                            </div>
                        </div>
                    </label>

                    <label>
                        <input type="checkbox" name="confirm" value="yes">
                        <div>
                            <div><b>Ich bestätige:</b> Es wird ein Backup des kompletten <code>GBDB</code>-Ordners
                                erstellt und danach umgeschaltet.</div>
                            <div class="note warn">.framework.env.php wird NICHT angefasst – danach musst du
                                <b>SECURITY → crypt_data</b> manuell passend setzen.</div>
                        </div>
                    </label>
                </fieldset>

                <div class="btns">
                    <button class="primary" type="submit">Migration starten</button>
                </div>
            </form>

            <?php if ($logs) : ?>
                <pre><?php foreach ($logs as $line) {
                    echo h($line) . "\n";
                     } ?></pre>
            <?php else : ?>
                <p class="note">
                    Tipp: Wenn „Erkannt: unknown“ angezeigt wird, kann das heißen, dass der Ordner leer ist oder gemischte
                    Daten enthält.
                </p>
            <?php endif; ?>
        </section>

<?php gbdbui_layout_end(); ?>
