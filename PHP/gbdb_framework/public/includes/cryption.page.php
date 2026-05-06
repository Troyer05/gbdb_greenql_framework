<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/cryption.logic.php';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GBDB Crypto Migrator</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
    <link rel="stylesheet" href="gbdb_framework/public/css/cryption.css">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
<?php gbdbui_nav('cryption'); ?>
<main class="gbdbui-wide legacy-tool-page">
    <section class="gbdbui-hero legacy-tool-hero">
        <p class="gbdbui-kicker">Crypto</p>
        <h1>GBDB Crypto Migrator</h1>
        <p>
            Convert the complete GBDB storage between plain and encrypted mode.
            The migrator creates a backup and then rewrites the structure based on the selected target format.
        </p>
    </section>

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

        <?php if ($errors): ?>
            <div class="err">
                <?php foreach ($errors as $error): ?>
                    <div>❌ <?= h($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($logs): ?>
            <div class="ok">
                <div>✅ Vorgang abgeschlossen. Details unten.</div>
            </div>
        <?php endif; ?>

        <form method="post">
            <fieldset>
                <legend>Aktion wählen</legend>

                <label>
                    <input type="radio" name="action" value="encrypt" <?= ($action === 'encrypt' ? 'checked' : '') ?>>
                    <div>
                        <div><b>Unverschlüsselt → Verschlüsselt</b></div>
                        <div class="note">
                            Erzeugt Token-Ordner/Dateien + Index-Dateien + verschlüsselt Inhalte in <b><?= h($EXT_ENC) ?></b>.
                        </div>
                    </div>
                </label>

                <label>
                    <input type="radio" name="action" value="decrypt" <?= ($action === 'decrypt' ? 'checked' : '') ?>>
                    <div>
                        <div><b>Verschlüsselt → Unverschlüsselt</b></div>
                        <div class="note">
                            Liest Index-Dateien, entschlüsselt Inhalte und schreibt Klartext-Struktur als <b><?= h($EXT_PLAIN) ?></b>.
                        </div>
                    </div>
                </label>

                <label>
                    <input type="checkbox" name="confirm" value="yes">
                    <div>
                        <div><b>Ich bestätige:</b> Es wird ein Backup des kompletten <code>GBDB</code>-Ordners erstellt und danach umgeschaltet.</div>
                        <div class="note warn">.framework.env.php wird NICHT angefasst – danach musst du <b>crypt_data()</b> manuell passend setzen.</div>
                    </div>
                </label>
            </fieldset>

            <div class="btns">
                <button class="primary" type="submit">Migration starten</button>
            </div>
        </form>

        <?php if ($logs): ?>
            <pre><?php foreach ($logs as $line) { echo h($line) . "\n"; } ?></pre>
        <?php else: ?>
            <p class="note">
                Tipp: Wenn „Erkannt: unknown“ angezeigt wird, kann das heißen, dass der Ordner leer ist oder gemischte Daten enthält.
            </p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
