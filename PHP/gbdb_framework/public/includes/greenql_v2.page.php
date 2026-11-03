<?php

declare(strict_types=1);

require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/greenql_v2_helper.php";

GreenQLUIv2Helper::boot();

require_once __DIR__ . "/greenql_v2.logic.php";
?>
<?php
$studioActions = '';

if ($loggedIn && GreenQLUIv2Helper::canStructure()) {
    $studioActions .= '<button class="btn btn-secondary" type="button" data-toggle="create-instance">' . gbdbui_icon('database', 16) . ' Neue Instanz</button>';
}

if ($loggedIn) {
    $otherMode = $mode === 'query' ? 'ui' : 'query';
    $studioActions .= '<a class="btn btn-primary" href="' . e(selfUrl(['mode' => $otherMode, 'instance' => $instance, 'db' => $db, 'table' => $table])) . '">' . gbdbui_icon($otherMode === 'query' ? 'edit' : 'panel', 16) . ($otherMode === 'query' ? ' Query öffnen' : ' Zur Tabellenansicht') . '</a>';
}

$studioPath = trim(($instance ?: 'keine Instanz') . ($db ? ' / ' . $db : '') . ($table ? ' / ' . $table : ''));
gbdbui_layout_start('greenql_v2', 'GreenQL Studio', ($mode === 'query' ? 'Query-Modus' : 'Tabellenansicht') . ' · ' . $studioPath, ['eyebrow' => 'Daten', 'actions' => $studioActions, 'body_class' => 'greenql-studio-body', 'css' => ['greenql_ui.v2.css']]);
?>
    <?php if (!$loggedIn) : ?>
        <section class="panel padded"><h2>Anmeldung erforderlich</h2><p>Bitte über die GBDB-Oberfläche anmelden.</p></section>
<?php else : ?>
        <div class="gql-shell">
            <aside class="gql-sidebar panel">

                <nav class="mode-switch">
                    <a class="<?= $mode === 'ui' ? 'active' : '' ?>"
                        href="<?= e(selfUrl(['mode' => 'ui', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">UI</a>
                    <a class="<?= $mode === 'query' ? 'active' : '' ?>"
                        href="<?= e(selfUrl(['mode' => 'query', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">Query</a>
                </nav>

                <div class="tree-head"><span>Instanzen</span><span><?= count($instances) ?></span></div>
                <div class="tree">
                    <?php if (empty($instances)) : ?>
                        <div class="empty-state"><strong>Keine sichtbare Instanz</strong><span>Die reservierte default-Instanz
                                wird bewusst ausgeblendet. Lege eine eigene Instanz an, um im Studio zu arbeiten.</span></div>
                    <?php endif; ?>
                    <?php foreach ($instances as $inst) : ?>
                        <?php $instDbs = GreenQLUIv2Helper::databases($inst); ?>
                        <details <?= $inst === $instance ? 'open' : '' ?>>
                            <summary class="tree-summary"><a
                                    href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst])) ?>"><?= e($inst) ?></a><?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                    <form method="post" class="tree-action"
                                        onsubmit="return confirm('Instanz wirklich vollständig löschen?')"><input type="hidden"
                                            name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action"
                                            value="delete_instance"><input type="hidden" name="name" value="<?= e($inst) ?>"><button
                                            class="danger mini" title="Instanz löschen">×</button></form>
                                          <?php endif; ?>
                            </summary>
                            <?php foreach ($instDbs as $dbName) : ?>
                                <?php $dbTables = GreenQLUIv2Helper::tables($inst, $dbName); ?>
                                <details class="level-db" <?= $inst === $instance && $dbName === $db ? 'open' : '' ?>>
                                    <summary class="tree-summary"><a
                                            href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName])) ?>"><?= e($dbName) ?></a><?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                            <form method="post" class="tree-action"
                                                onsubmit="return confirm('Base wirklich löschen? Tabellen müssen leer/gelöscht sein.')">
                                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden"
                                                    name="action" value="delete_db"><input type="hidden" name="instance"
                                                    value="<?= e($inst) ?>"><input type="hidden" name="name"
                                                    value="<?= e($dbName) ?>"><button class="danger mini"
                                                    title="Base löschen">×</button>
                                            </form>
                                                  <?php endif; ?>
                                    </summary>
                                    <?php foreach ($dbTables as $tableName) : ?>
                                        <div class="leaf-row"><a
                                                class="leaf <?= $inst === $instance && $dbName === $db && $tableName === $table ? 'active' : '' ?>"
                                                href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName, 'table' => $tableName])) ?>"><?= e($tableName) ?></a><?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                                <form method="post" class="tree-action leaf-delete"
                                                    onsubmit="return confirm('Tabelle wirklich löschen?')"><input type="hidden" name="csrf"
                                                        value="<?= e(csrf()) ?>"><input type="hidden" name="action"
                                                        value="delete_table"><input type="hidden" name="instance"
                                                        value="<?= e($inst) ?>"><input type="hidden" name="db"
                                                        value="<?= e($dbName) ?>"><input type="hidden" name="name"
                                                        value="<?= e($tableName) ?>"><button class="danger mini"
                                                        title="Tabelle löschen">×</button></form>
                                                      <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                        <button class="tiny-link" type="button" data-toggle="create-table"
                                            data-instance="<?= e($inst) ?>" data-db="<?= e($dbName) ?>">+ Tabelle</button>
                                    <?php endif; ?>
                                </details>
                            <?php endforeach; ?>
                            <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                <button class="tiny-link tree-db" type="button" data-toggle="create-db"
                                    data-instance="<?= e($inst) ?>">+ Base</button>
                            <?php endif; ?>
                        </details>
                    <?php endforeach; ?>
                </div>

                <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                    <button class="secondary full" type="button" data-toggle="create-instance">Instanz erstellen</button>
                <?php endif; ?>
            </aside>

            <div class="gql-main">

                <?php foreach (flashes() as $f) : ?>
                    <div class="flash <?= e($f["type"] ?? "") ?>"><?= e($f["text"] ?? "") ?></div>
                <?php endforeach; ?>

                <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                    <section id="create-instance" class="inline-card hidden">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="action" value="create_instance">
                            <input name="name" placeholder="instanz_name" required>
                            <button class="primary">Instanz erstellen</button>
                        </form>
                    </section>
                    <section id="create-db" class="inline-card hidden">
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="action" value="create_db">
                            <input id="db-instance" type="hidden" name="instance" value="<?= e($instance) ?>">
                            <input name="name" placeholder="base_name" required>
                            <button class="primary">Base erstellen</button>
                        </form>
                    </section>
                    <section id="create-table" class="inline-card hidden">
                        <form method="post" class="inline-form wide">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="action" value="create_table">
                            <input id="table-instance" type="hidden" name="instance" value="<?= e($instance) ?>">
                            <input id="table-db" type="hidden" name="db" value="<?= e($db) ?>">
                            <input name="name" placeholder="table_name" required>
                            <input name="cols" placeholder="uid, username, email oder uid:string, age:int" required>
                            <label class="checkline"
                                title="Wenn aktiv, wird das GBDB Schema 2.0 für diese Tabelle mit Datentypen verwendet.">
                                <input type="checkbox" name="types_enabled" value="1">
                                <span>Types aktivieren</span>
                            </label>
                            <button class="primary">Tabelle erstellen</button>
                        </form>
                    </section>
                <?php endif; ?>

                <?php if ($mode === 'query') : ?>
                    <section class="panel query-panel">
                        <div class="panel-head">
                            <div>
                                <h2>Query Editor</h2>
                                <p class="muted">Syntax Highlighting ohne Cursor-Versatz · Upload · Pfad-Ausführung · Parameter
                                </p>
                            </div>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="query-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="instance" value="<?= e($instance) ?>">
                            <input type="hidden" name="db" value="<?= e($db) ?>">
                            <input type="hidden" name="table" value="<?= e($table) ?>">
                            <input type="hidden" name="mode" value="query">
                            <div class="editor-shell" data-lang="gql">
                                <pre id="gql-highlight" aria-hidden="true"></pre>
                                <textarea id="gql-editor" name="script" spellcheck="false" autocomplete="off">
<?= e($loadedScript) ?></textarea>
                            </div>
                            <div class="query-grid">
                                <label>Parameter JSON oder key=value<textarea name="params" class="small-area"
                                        placeholder='{"uid":"abc123"}'>
<?= e($paramsText) ?></textarea></label>
                                <label>.gql Script-Pfad<input name="script_path" value="<?= e($loadedPath) ?>"
                                        placeholder="scripts/greenql/makeUser.gql"></label>
                                <label>.gql hochladen<input type="file" name="gql_file" accept=".gql"></label>
                            </div>
                            <div class="button-row">
                                <button class="primary" name="action" value="run_query">Editor ausführen</button>
                                <button class="secondary" name="action" value="run_uploaded">Upload ausführen</button>
                                <button class="secondary" name="action" value="run_path">Pfad ausführen</button>
                            </div>
                        </form>
                    </section>
                    <?php queryResultBox($queryResult); ?>
                <?php else : ?>
                    <?php if ($instance === "") : ?>
                        <section class="empty-state">
                            <h2>Keine sichtbare Instanz</h2>
                            <p>Admins können eine neue Instanz erstellen. Eingeschränkte Nutzer brauchen erst eine Freigabe.</p>
                        </section>
                    <?php elseif ($db === "") : ?>
                        <section class="empty-state">
                            <?php if ($dbs !== []) : ?>
                                <h2>Base auswählen</h2>
                                <p>Wähle links in <strong><?= e($instance) ?></strong> eine Base, um ihre Tabellen zu sehen.</p>
                            <?php else : ?>
                                <h2>Keine Base sichtbar</h2>
                                <p>In dieser Instanz gibt es keine für dich freigegebene Base.</p>
                            <?php endif; ?>
                            <?php if (GreenQLUIv2Helper::canStructure()) :
                                ?><button type="button" class="primary"
                                    data-toggle="create-db">Base erstellen</button>
                            <?php endif; ?>
                        </section>
                    <?php elseif ($table === "") : ?>
                        <section class="empty-state">
                            <?php if ($tables !== []) : ?>
                                <h2>Tabelle auswählen</h2>
                                <p>Wähle links in <strong><?= e($db) ?></strong> eine Tabelle, um ihre Datensätze zu bearbeiten.</p>
                            <?php else : ?>
                                <h2>Keine Tabelle sichtbar</h2>
                                <p>In dieser Base gibt es keine sichtbare Tabelle.</p>
                            <?php endif; ?>
                            <?php if (GreenQLUIv2Helper::canStructure()) :
                                ?><button type="button" class="primary"
                                    data-toggle="create-table">Tabelle erstellen</button>
                            <?php endif; ?>
                        </section>
                    <?php else : ?>
                        <section class="panel table-panel">
                            <div class="panel-head">
                                <div>
                                    <h2><?= e($table) ?></h2>
                                    <p class="muted"><?= count($rows) ?> Datensätze · <?= count($keys) ?> Felder</p>
                                </div>
                                <div class="gql-table-actions">
                                <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                    <form method="post" onsubmit="return confirm('Tabelle wirklich löschen?')"><input type="hidden"
                                            name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action"
                                            value="delete_table"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input
                                            type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="name"
                                            value="<?= e($table) ?>"><button class="danger xs">Tabelle löschen</button></form>
                                <?php endif; ?>
                                <form method="get" class="search-form">
                                    <input type="hidden" name="tool" value="greenql_v2"><input type="hidden" name="mode"
                                        value="ui"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input
                                        type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="table"
                                        value="<?= e($table) ?>">
                                    <input name="search" value="<?= e($search) ?>" placeholder="Suchen …" aria-label="Datensätze durchsuchen"><button>Suchen</button>
                                </form>
                                </div>
                            </div>
                            <?php if (GreenQLUIv2Helper::canWrite()) : ?>
                                <details class="insert-box">
                                    <summary>Datensatz hinzufügen</summary>
                                    <form method="post" class="data-form"><input type="hidden" name="csrf"
                                            value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="insert_row"><input
                                            type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db"
                                            value="<?= e($db) ?>"><input type="hidden" name="table"
                                            value="<?= e($table) ?>"><?php foreach ($keys as $key) :
                                                ?><?php if ($key === "id") {
                                                       continue;
                                                } ?><label><?= e($key) ?><input
                                                    name="row[<?= e($key) ?>]"></label><?php
                                                   endforeach; ?><button
                                            class="primary">Speichern</button></form>
                                </details>
                            <?php endif; ?>
                            <?php if (GreenQLUIv2Helper::canStructure()) : ?>
                                <form method="post" class="add-column-form"><input type="hidden" name="csrf"
                                        value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="add_column"><input
                                        type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db"
                                        value="<?= e($db) ?>"><input type="hidden" name="table"
                                        value="<?= e($table) ?>"><label>Spalte<input name="column" placeholder="neue_spalte"
                                            required></label><label>Default-Wert<input name="default"
                                            placeholder="leer, true, false, 0, JSON..."></label><button>Spalte hinzufügen</button>
                                </form>
                            <?php endif; ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr><?php foreach ($keys as $key) : ?>
                                                <th><?= e($key) ?></th>
                                            <?php endforeach; ?>            <?php if (GreenQLUIv2Helper::canWrite()) : ?>
                                                <th>Aktionen</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row) :
                                            ?>                <?php if (!is_array($row)) {
                                                                 continue;
                                            } ?>
                                            <tr>
                                                <?php foreach ($keys as $key) :
                                                    ?>                    <?php $value = $row[$key] ?? ""; ?>
                                                    <td>
                                                        <code><?= e(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) ?></code>
                                                    </td>
                                                <?php endforeach; ?><?php if (GreenQLUIv2Helper::canWrite()) : ?>
                                                    <td class="row-actions"><button type="button" class="ghost xs edit-row"
                                                            data-row="<?= e(base64_encode(json_encode($row, JSON_UNESCAPED_UNICODE) ?: '{}')) ?>">Edit</button>
                                                        <form method="post" onsubmit="return confirm('Datensatz wirklich löschen?')"><input
                                                                type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden"
                                                                name="action" value="delete_row"><input type="hidden" name="instance"
                                                                value="<?= e($instance) ?>"><input type="hidden" name="db"
                                                                value="<?= e($db) ?>"><input type="hidden" name="table"
                                                                value="<?= e($table) ?>"><input type="hidden" name="id"
                                                                value="<?= e($row["id"] ?? "") ?>"><button class="danger xs">Delete</button>
                                                        </form>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if ($rows === []) : ?>
                                            <tr><td class="gbdbui-table-empty" colspan="<?= count($keys) + (GreenQLUIv2Helper::canWrite() ? 1 : 0) ?>"><?= $search !== '' ? 'Keine Treffer für „' . e($search) . '“.' : 'Diese Tabelle enthält noch keine Datensätze.' ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <dialog id="edit-dialog" class="edit-dialog">
            <form method="post" class="data-form" id="edit-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="update_row">
                <input type="hidden" name="instance" value="<?= e($instance) ?>">
                <input type="hidden" name="db" value="<?= e($db) ?>">
                <input type="hidden" name="table" value="<?= e($table) ?>">
                <input type="hidden" name="id" id="edit-id">
                <div class="dialog-head">
                    <h3>Datensatz bearbeiten</h3><button type="button" class="ghost xs" id="edit-close">Schließen</button>
                </div>
                <div id="edit-fields"></div>
                <button class="primary">Änderungen speichern</button>
            </form>
        </dialog>

        <script src="<?= gbdbui_e(gbdbui_asset('js/gbdbui_highlight.js')) ?>"></script>
        <script>
            (function () {
                document.querySelectorAll('[data-toggle]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-toggle');
                        const el = document.getElementById(id);

                        if (!el) return;

                        if (btn.dataset.instance && document.getElementById('db-instance')) document
                            .getElementById('db-instance').value = btn.dataset.instance;

                        if (btn.dataset.instance && document.getElementById('table-instance')) document
                            .getElementById('table-instance').value = btn.dataset.instance;

                        if (btn.dataset.db && document.getElementById('table-db')) document.getElementById(
                            'table-db').value = btn.dataset.db;
                        el.classList.toggle('hidden');
                        el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    });
                });

                const editKeys =
                    <?= json_encode(array_values(array_filter($keys, fn($k) => $k !== 'id')), JSON_UNESCAPED_UNICODE) ?>;
                const columnDefaults = <?= json_encode($columnDefaults, JSON_UNESCAPED_UNICODE) ?>;
                const dialog = document.getElementById('edit-dialog');
                const fields = document.getElementById('edit-fields');
                const editId = document.getElementById('edit-id');
                document.querySelectorAll('.edit-row').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (!dialog || !fields || !editId) return;
                        const row = JSON.parse(atob(btn.dataset.row || 'e30='));
                        editId.value = row.id || '';
                        fields.innerHTML = '';
                        (editKeys.length ? editKeys : Object.keys(row)).forEach(key => {
                            if (key === 'id') return;
                            const label = document.createElement('label');
                            label.textContent = key;
                            const input = document.createElement('input');
                            input.name = 'row[' + key + ']';
                            input.value = row[key] ?? columnDefaults[key] ?? '';
                            label.appendChild(input);
                            fields.appendChild(label);
                        });
                        dialog.showModal();
                    });
                });
                const close = document.getElementById('edit-close');

                if (close && dialog) close.addEventListener('click', () => dialog.close());
            })();
        </script>
    <?php endif; ?>
<?php gbdbui_layout_end(); ?>
