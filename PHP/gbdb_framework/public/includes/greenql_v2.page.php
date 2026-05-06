<?php
declare(strict_types=1);
require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/greenql_v2_helper.php";

GreenQLUIv2Helper::boot();

require_once __DIR__ . "/greenql_v2.logic.php";
?>
<!doctype html>
<html lang="de">

<head>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GreenQL Studio · GBDB UI</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/greenql_ui.v2.css?v=2026.3">
</head>

<body class="greenql-studio-body">
    <?php gbdbui_nav('greenql_v2'); ?>
    <?php if (!$loggedIn): ?>
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand-mark">G2</div>
            <p class="eyebrow">greenbucket®</p>
            <h1>GreenQL v2</h1>
            <p class="muted">
                <?= $needsSetup ? "Ersteinrichtung der lokalen Admin-Oberfläche." : "Einloggen, um Instanzen, Bases und Tabellen zu verwalten." ?>
            </p>

            <?php foreach (flashes() as $f): ?>
            <div class="flash <?= e($f["type"] ?? "") ?>"><?= e($f["text"] ?? "") ?></div>
            <?php endforeach; ?>

            <form method="post" class="stack-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="<?= $needsSetup ? 'setup' : 'login' ?>">
                <label>Benutzername<input name="username" autocomplete="username" required></label>
                <label>Passwort<input name="password" type="password"
                        autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" required></label>
                <?php if ($needsSetup): ?>
                <label>Passwort wiederholen<input name="password2" type="password" autocomplete="new-password"
                        required></label>
                <?php endif; ?>
                <button class="primary"><?= $needsSetup ? "Admin anlegen" : "Einloggen" ?></button>
            </form>
        </section>
    </main>
    <?php else: ?>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="side-top">
                <div class="brand-mark small">G2</div>
                <div>
                    <strong>GreenQL Studio</strong>
                    <span><?= e($user["username"] ?? "") ?> · <?= e($user["role"] ?? "") ?></span>
                </div>
            </div>

            <nav class="mode-switch">
                <a class="<?= $mode === 'ui' ? 'active' : '' ?>"
                    href="<?= e(selfUrl(['mode' => 'ui', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">UI</a>
                <a class="<?= $mode === 'query' ? 'active' : '' ?>"
                    href="<?= e(selfUrl(['mode' => 'query', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">Query</a>
            </nav>

            <div class="tree-head"><span>Instanzen</span><span><?= count($instances) ?></span></div>
            <div class="tree">
                <?php if (empty($instances)): ?>
                <div class="empty-state"><strong>Keine sichtbare Instanz</strong><span>Die reservierte default-Instanz wird bewusst ausgeblendet. Lege eine eigene Instanz an, um im Studio zu arbeiten.</span></div>
                <?php endif; ?>
                <?php foreach ($instances as $inst): ?>
                                <?php $instDbs = GreenQLUIv2Helper::databases($inst); ?>
                <details <?= $inst === $instance ? 'open' : '' ?>>
                    <summary class="tree-summary"><a
                            href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst])) ?>"><?= e($inst) ?></a><?php if (GreenQLUIv2Helper::canStructure()): ?>
                        <form method="post" class="tree-action"
                            onsubmit="return confirm('Instanz wirklich vollständig löschen?')"><input type="hidden"
                                name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action"
                                value="delete_instance"><input type="hidden" name="name" value="<?= e($inst) ?>"><button
                                class="danger mini" title="Instanz löschen">×</button></form><?php endif; ?></summary>
                    <?php foreach ($instDbs as $dbName): ?>
                    <?php $dbTables = GreenQLUIv2Helper::tables($inst, $dbName); ?>
                    <details class="level-db" <?= $inst === $instance && $dbName === $db ? 'open' : '' ?>>
                        <summary class="tree-summary"><a
                                href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName])) ?>"><?= e($dbName) ?></a><?php if (GreenQLUIv2Helper::canStructure()): ?>
                            <form method="post" class="tree-action"
                                onsubmit="return confirm('Base wirklich löschen? Tabellen müssen leer/gelöscht sein.')">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden"
                                    name="action" value="delete_db"><input type="hidden" name="instance"
                                    value="<?= e($inst) ?>"><input type="hidden" name="name"
                                    value="<?= e($dbName) ?>"><button class="danger mini"
                                    title="Base löschen">×</button></form><?php endif; ?></summary>
                        <?php foreach ($dbTables as $tableName): ?>
                        <div class="leaf-row"><a
                                class="leaf <?= $inst === $instance && $dbName === $db && $tableName === $table ? 'active' : '' ?>"
                                href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName, 'table' => $tableName])) ?>"><?= e($tableName) ?></a><?php if (GreenQLUIv2Helper::canStructure()): ?>
                            <form method="post" class="tree-action leaf-delete"
                                onsubmit="return confirm('Tabelle wirklich löschen?')"><input type="hidden" name="csrf"
                                    value="<?= e(csrf()) ?>"><input type="hidden" name="action"
                                    value="delete_table"><input type="hidden" name="instance"
                                    value="<?= e($inst) ?>"><input type="hidden" name="db"
                                    value="<?= e($dbName) ?>"><input type="hidden" name="name"
                                    value="<?= e($tableName) ?>"><button class="danger mini"
                                    title="Tabelle löschen">×</button></form><?php endif; ?></div>
                        <?php endforeach; ?>
                        <?php if (GreenQLUIv2Helper::canStructure()): ?>
                        <button class="tiny-link" type="button" data-toggle="create-table"
                            data-instance="<?= e($inst) ?>" data-db="<?= e($dbName) ?>">+ Tabelle</button>
                        <?php endif; ?>
                    </details>
                    <?php endforeach; ?>
                    <?php if (GreenQLUIv2Helper::canStructure()): ?>
                    <button class="tiny-link tree-db" type="button" data-toggle="create-db"
                        data-instance="<?= e($inst) ?>">+ Base</button>
                    <?php endif; ?>
                </details>
                <?php endforeach; ?>
            </div>

            <?php if (GreenQLUIv2Helper::canStructure()): ?>
            <button class="secondary full" type="button" data-toggle="create-instance">Instanz erstellen</button>
            <?php endif; ?>

            <form method="post" class="logout-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="gbdbui_action" value="logout">
                <button class="ghost full">Logout</button>
            </form>
        </aside>

        <main class="main">
            <header class="topbar">
                <div>
                    <p class="eyebrow">GreenQL Studio</p>
                    <h1><?= $mode === 'query' ? 'Query Mode' : 'UI Mode' ?>
                    </h1>
                    <p class="muted">
                        <?= e($instance ?: 'keine instanz') ?><?= $db ? ' / ' . e($db) : '' ?><?= $table ? ' / ' . e($table) : '' ?>
                    </p>
                </div>
                <div class="top-actions">
                    <?php if (GreenQLUIv2Helper::canStructure()): ?>
                    <button class="secondary" type="button" data-toggle="create-instance">Neue Instanz</button>
                    <?php endif; ?>
                    <a class="secondary"
                        href="<?= e(selfUrl(['mode' => 'query', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">Query
                        öffnen</a>
                </div>
            </header>

            <?php foreach (flashes() as $f): ?>
            <div class="flash <?= e($f["type"] ?? "") ?>"><?= e($f["text"] ?? "") ?></div>
            <?php endforeach; ?>

            <?php if (GreenQLUIv2Helper::canStructure()): ?>
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
                    <label class="checkline" title="Wenn aktiv, wird das GBDB Schema 2.0 für diese Tabelle mit Datentypen verwendet.">
                        <input type="checkbox" name="types_enabled" value="1">
                        <span>Types aktivieren</span>
                    </label>
                    <button class="primary">Tabelle erstellen</button>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($mode === 'query'): ?>
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
                    <div class="editor-shell">
                        <pre id="gql-highlight" aria-hidden="true"></pre>
                        <textarea id="gql-editor" name="script" spellcheck="false"><?= e($loadedScript) ?></textarea>
                    </div>
                    <div class="query-grid">
                        <label>Parameter JSON oder key=value<textarea name="params" class="small-area"
                                placeholder='{"uid":"abc123"}'><?= e($paramsText) ?></textarea></label>
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
            <?php else: ?>
            <?php if ($instance === ""): ?>
            <section class="empty-state">
                <h2>Keine sichtbare Instanz</h2>
                <p>Admins können eine neue Instanz erstellen. Eingeschränkte Nutzer brauchen erst eine Freigabe.</p>
            </section>
            <?php elseif ($db === ""): ?>
            <section class="empty-state">
                <h2>Keine Base sichtbar</h2>
                <p>In dieser Instanz gibt es keine für dich freigegebene Base.</p>
                <?php if (GreenQLUIv2Helper::canStructure()): ?><button type="button" class="primary"
                    data-toggle="create-db">Base erstellen</button><?php endif; ?>
            </section>
            <?php elseif ($table === ""): ?>
            <section class="empty-state">
                <h2>Keine Tabelle sichtbar</h2>
                <p>In dieser Base gibt es keine sichtbare Tabelle.</p>
                <?php if (GreenQLUIv2Helper::canStructure()): ?><button type="button" class="primary"
                    data-toggle="create-table">Tabelle erstellen</button><?php endif; ?>
            </section>
            <?php else: ?>
            <section class="panel table-panel">
                <div class="panel-head">
                    <div>
                        <h2><?= e($table) ?></h2>
                        <p class="muted"><?= count($rows) ?> Datensätze · <?= count($keys) ?> Felder</p>
                    </div>
                    <?php if (GreenQLUIv2Helper::canStructure()): ?><form method="post"
                        onsubmit="return confirm('Tabelle wirklich löschen?')"><input type="hidden" name="csrf"
                            value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete_table"><input
                            type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db"
                            value="<?= e($db) ?>"><input type="hidden" name="name" value="<?= e($table) ?>"><button
                            class="danger xs">Tabelle löschen</button></form><?php endif; ?>
                    <form method="get" class="search-form">
                        <input type="hidden" name="tool" value="greenql_v2"><input type="hidden" name="mode" value="ui"><input type="hidden" name="instance"
                            value="<?= e($instance) ?>"><input type="hidden" name="db" value="<?= e($db) ?>"><input
                            type="hidden" name="table" value="<?= e($table) ?>">
                        <input name="search" value="<?= e($search) ?>" placeholder="Suchen..."><button>Suchen</button>
                    </form>
                </div>
                <?php if (GreenQLUIv2Helper::canWrite()): ?>
                <details class="insert-box">
                    <summary>Datensatz hinzufügen</summary>
                    <form method="post" class="data-form"><input type="hidden" name="csrf"
                            value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="insert_row"><input
                            type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db"
                            value="<?= e($db) ?>"><input type="hidden" name="table"
                            value="<?= e($table) ?>"><?php foreach ($keys as $key): ?><?php if ($key === "id") continue; ?><label><?= e($key) ?><input
                                name="row[<?= e($key) ?>]"></label><?php endforeach; ?><button
                            class="primary">Speichern</button></form>
                </details>
                <?php endif; ?>
                <?php if (GreenQLUIv2Helper::canStructure()): ?>
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
                            <tr><?php foreach ($keys as $key): ?><th><?= e($key) ?></th>
                                <?php endforeach; ?><?php if (GreenQLUIv2Helper::canWrite()): ?><th>Aktionen</th>
                                <?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?><?php if (!is_array($row)) continue; ?><tr>
                                <?php foreach ($keys as $key): ?><?php $value = $row[$key] ?? ""; ?><td>
                                    <code><?= e(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) ?></code>
                                </td><?php endforeach; ?><?php if (GreenQLUIv2Helper::canWrite()): ?><td
                                    class="row-actions"><button type="button" class="ghost xs edit-row"
                                        data-row="<?= e(base64_encode(json_encode($row, JSON_UNESCAPED_UNICODE) ?: '{}')) ?>">Edit</button>
                                    <form method="post" onsubmit="return confirm('Datensatz wirklich löschen?')"><input
                                            type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden"
                                            name="action" value="delete_row"><input type="hidden" name="instance"
                                            value="<?= e($instance) ?>"><input type="hidden" name="db"
                                            value="<?= e($db) ?>"><input type="hidden" name="table"
                                            value="<?= e($table) ?>"><input type="hidden" name="id"
                                            value="<?= e($row["id"] ?? "") ?>"><button class="danger xs">Delete</button>
                                    </form>
                                </td><?php endif; ?></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>
            <?php endif; ?>
        </main>
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

    <script>
    (function() {
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

        const textarea = document.getElementById('gql-editor');
        const high = document.getElementById('gql-highlight');
        const escapeHtml = s => s.replace(/[&<>]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        } [c]));
        const keywordGroups = {
            tx: new Set(['BEGIN', 'COMMIT', 'ROLLBACK', 'TRANSACTION']),
            instance: new Set(['USE', 'INSTANCE', 'INSTANCES', 'FORCE']),
            structure: new Set(['ROOT', 'BRANCH', 'GROW', 'DROP', 'ALTER', 'EDIT', 'TABLE', 'BASE', 'COLUMN',
                'DEFAULT', 'DESCRIBE'
            ]),
            index: new Set(['INDEX', 'UNINDEX', 'REINDEX', 'INDEXES', 'SUGGEST', 'SUGGESTIONS', 'AUTO']),
            constraint: new Set(['CONSTRAINT', 'CONSTRAINTS', 'UNIQUE', 'REQUIRED']),
            control: new Set(['IF', 'ELSE', 'FOR', 'MAP_OBJECT', 'ERROR', 'MSG', 'TRUE', 'FALSE', 'NULL',
                'BACK', 'OUTPUT', 'LOG', 'CLEAR_LOG', 'DELETE_LOG_FILE', 'END_PROC', 'EXISTS', 'MODERATION', 'FANOUT', 'SOFT_DELETE', 'SOCIAL', 'MEDIA', 'BLOB', 'GDPR', 'AUDIT', 'POLICY', 'PERMISSION', 'PROCEDURE', 'MATERIALIZED', 'VIEW', 'EVENT', 'TRIGGER', 'QUEUE', 'JOB'
            ]),
            query: new Set(['PICK', 'FROM', 'WHERE', 'SORT', 'ASC', 'DESC', 'LIMIT', 'SIZE', 'SEARCH', 'AFTER',
                'COLUMNS', 'MAX', 'SEED', 'WITH', 'RESHAPE', 'ERASE', 'DELETE', 'IN', 'CALL', 'F',
                'SHOW', 'CLASS', 'C', 'PUB', 'PRIV', 'AS'
            ]),
            health: new Set(['PACK', 'PEEK', 'CHECK', 'HEALTH', 'REPAIR', 'SNAPSHOT', 'META', 'EXPLAIN',
                'MONITOR', 'RECOVER', 'PAGE', 'CURSOR', 'FULLTEXT', 'STATS', 'ANALYZE'
            ]),
            decl: new Set(['DECLARE', 'DECALRE', 'DELACE', 'PARAM', 'HASH', 'HASH_SHA256', 'HASH_SHA512',
                'HASH_MD5', 'HASH_ADLER32', 'HASH_CRC32', 'LEN', 'ENV', 'FILE', 'INCLUDE', 'RUN', 'NOW',
                'SET_LOGFILE', 'FETCH_API', 'API_FETCH', 'CALL_API', 'UNI_RANDOM', 'SPARK_ID',
                'FRESH_ID', 'GET_INSTANCES', 'GET_BASES', 'GET_TABLES', 'FETCH_DATA', 'GET_DATA',
                'COUNT_DATA', 'LAST_ADDED', 'ADD_DATA', 'EDIT_DATA', 'DELETE_DATA', 'NEW_COLUMN',
                'DELETE_COLUMN'
            ])
        };
        const classForWord = w => {
            const u = w.toUpperCase();
            for (const [cls, set] of Object.entries(keywordGroups)) {
                if (set.has(u)) return 'tok-' + cls;
            }
            return '';
        };
        const highlight = src => {
            let out = '',
                i = 0;
            while (i < src.length) {
                const ch = src[i];
                if (ch === '#') {
                    const j = src.indexOf('\n', i);
                    const end = j === -1 ? src.length : j;
                    out += '<span class="tok-comment">' + escapeHtml(src.slice(i, end)) + '</span>';
                    i = end;
                    continue;
                }
                if (ch === '-' && src[i + 1] === '-') {
                    const j = src.indexOf('\n', i);
                    const end = j === -1 ? src.length : j;
                    out += '<span class="tok-comment">' + escapeHtml(src.slice(i, end)) + '</span>';
                    i = end;
                    continue;
                }
                if (ch === '/' && src[i + 1] === '/') {
                    const j = src.indexOf('\n', i);
                    const end = j === -1 ? src.length : j;
                    out += '<span class="tok-comment">' + escapeHtml(src.slice(i, end)) + '</span>';
                    i = end;
                    continue;
                }
                if (ch === '"' || ch === "'") {
                    const q = ch;
                    let j = i + 1;
                    while (j < src.length) {
                        if (src[j] === '\\') {
                            j += 2;
                            continue;
                        }
                        if (src[j] === q) {
                            j++;
                            break;
                        }
                        j++;
                    }
                    out += '<span class="tok-string">' + escapeHtml(src.slice(i, j)) + '</span>';
                    i = j;
                    continue;
                }
                const rest = src.slice(i);
                const assign = rest.match(/^([$]?[A-Za-z_][A-Za-z0-9_]*)\s*(?==)/);
                if (assign) {
                    const cls = assign[1].startsWith('_') || assign[1].startsWith('$') ? 'tok-var' :
                    'tok-field';
                    out += '<span class="' + cls + '">' + escapeHtml(assign[1]) + '</span>';
                    i += assign[1].length;
                    continue;
                }
                const constVar = rest.match(/^\$[A-Za-z_][A-Za-z0-9_]*/);
                if (constVar) {
                    out += '<span class="tok-var">' + escapeHtml(constVar[0]) + '</span>';
                    i += constVar[0].length;
                    continue;
                }
                const word = rest.match(/^[A-Za-z_][A-Za-z0-9_]*/);
                if (word) {
                    const w = word[0];
                    const cls = classForWord(w);
                    if (w.startsWith('_')) {
                        out += '<span class="tok-var">' + escapeHtml(w) + '</span>';
                    } else if (/^(true|false|null|now)$/i.test(w)) {
                        out += '<span class="tok-lit">' + escapeHtml(w) + '</span>';
                    } else if (src.slice(i + w.length).match(/^\s*\(/)) {
                        out += '<span class="tok-fn">' + escapeHtml(w) + '</span>';
                    } else {
                        out += cls ? '<span class="' + cls + '">' + escapeHtml(w) + '</span>' : escapeHtml(w);
                    }
                    i += w.length;
                    continue;
                }
                const num = rest.match(/^\d+(?:\.\d+)?/);
                if (num) {
                    out += '<span class="tok-num">' + escapeHtml(num[0]) + '</span>';
                    i += num[0].length;
                    continue;
                }
                if (ch === '*') {
                    out += '<span class="tok-wild">*</span>';
                    i++;
                    continue;
                }
                if ('{}[]()'.includes(ch)) {
                    out += '<span class="tok-brace">' + escapeHtml(ch) + '</span>';
                    i++;
                    continue;
                }
                if ('=!<>:+-.,'.includes(ch)) {
                    out += '<span class="tok-op">' + escapeHtml(ch) + '</span>';
                    i++;
                    continue;
                }
                if (ch === ';') {
                    out += '<span class="tok-semi">;</span>';
                    i++;
                    continue;
                }
                out += escapeHtml(ch);
                i++;
            }
            return out;
        };
        const currentIndent = line => (line.match(/^\s*/) || [''])[0];
        const autoIndent = e => {
            if (!textarea) return;

            if (e.key === 'Tab') {
                e.preventDefault();
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.setRangeText('    ', start, end, 'end');
                paint();
                return;
            }

            if (e.key === '}') {
                const pos = textarea.selectionStart;
                const before = textarea.value.slice(0, pos);
                const lineStart = before.lastIndexOf('\n') + 1;
                const line = before.slice(lineStart);

                if (/^\s*$/.test(line)) {
                    e.preventDefault();
                    const base = currentIndent(line).replace(/ {1,4}$/, '');
                    textarea.setRangeText(base + '}', lineStart, pos, 'end');
                    paint();
                }

                return;
            }

            if (e.key !== 'Enter') return;

            e.preventDefault();
            const pos = textarea.selectionStart;
            const before = textarea.value.slice(0, pos);
            const after = textarea.value.slice(textarea.selectionEnd);
            const line = before.split('\n').pop() || '';
            let indent = currentIndent(line);
            const opens = /(?:\{|\[)\s*$/.test(line);
            const closesNext = /^\s*(?:\}|\])/.test(after);

            if (opens) indent += '    ';
            if (closesNext) indent = indent.replace(/ {1,4}$/, '');

            textarea.setRangeText('\n' + indent, pos, textarea.selectionEnd, 'end');
            paint();
        };
        const paint = () => {
            if (!textarea || !high) return;
            high.innerHTML = highlight(textarea.value) + '\n';
        };
        if (textarea && high) {
            textarea.addEventListener('input', paint);
            textarea.addEventListener('keydown', autoIndent);
            textarea.addEventListener('scroll', () => {
                high.scrollTop = textarea.scrollTop;
                high.scrollLeft = textarea.scrollLeft;
            });
            paint();
        }

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
</body>

</html>
