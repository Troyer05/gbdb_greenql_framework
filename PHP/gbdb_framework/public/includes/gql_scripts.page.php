<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('gql_scripts');

$root = dirname(rtrim(Vars::DB_PATH(), '/')) . '/.scripts';

if (!is_dir($root)) {
    @mkdir($root, 0750, true);
}

function gbdbui_script_rel(string $path) :string {
    $path = str_replace('\\', '/', trim($path));
    $path = preg_replace('#/+#', '/', $path) ?: '';
    $path = trim($path, '/');
    $parts = [];

    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            continue;
        }
        $parts[] = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $part);
    }

    return implode('/', $parts);
}

function gbdbui_script_abs(string $root, string $rel) :string {
    return rtrim($root, '/') . '/' . gbdbui_script_rel($rel);
}

function gbdbui_script_tree(string $root) :array {
    $items = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $f) {
        $rel = ltrim(str_replace($root, '', $f->getPathname()), '/');

        if ($rel === '' || $rel === '.ENV' || str_starts_with($rel, '.ENV/')) {
            continue;
        }

        $items[] = [
            'rel' => $rel,
            'name' => basename($rel),
            'dir' => dirname($rel) === '.' ? '' : dirname($rel),
            'is_dir' => $f->isDir(),
            'size' => $f->isFile() ? (int)$f->getSize() : 0,
        ];
    }

    usort($items, function (array $a, array $b) :int {
        if ($a['is_dir'] !== $b['is_dir']) {
            return $a['is_dir'] ? -1 : 1;
        }

        return strnatcasecmp((string)$a['rel'], (string)$b['rel']);
    });

    return $items;
}

function gbdbui_gql_output_text(mixed $value) :string {
    if (is_array($value) || is_object($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if ($value === null) {
        return 'null';
    }

    return (string)$value;
}

function gbdbui_gql_outputs(array $runResult) :array {
    $out = [];

    foreach (($runResult['outputs'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $out[] = [
            'command' => (string)($item['command'] ?? 'OUTPUT'),
            'value' => $item['value'] ?? ''
        ];
    }

    return $out;
}

$rel = gbdbui_script_rel((string)($_GET['file'] ?? $_POST['file'] ?? ''));
$dir = gbdbui_script_rel((string)($_GET['dir'] ?? $_POST['dir'] ?? ''));
$msg = '';
$msgType = 'ok';
$runResult = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $msg = 'Ungültiger CSRF Token.';
        $msgType = 'bad';
    } else {
        $act = (string)($_POST['action'] ?? '');
        $targetRel = gbdbui_script_rel((string)($_POST['file'] ?? ''));
        $target = gbdbui_script_abs($root, $targetRel);

        if ($act === 'save') {
            if ($targetRel === '') {
                $targetRel = ($dir !== '' ? $dir . '/' : '') . 'new_script.gql';
            }

            if (!str_ends_with(strtolower($targetRel), '.gql')) {
                $targetRel .= '.gql';
            }

            $target = gbdbui_script_abs($root, $targetRel);

            if (!is_dir(dirname($target))) {
                @mkdir(dirname($target), 0750, true);
            }
            // Browser senden Textarea-Zeilenumbrüche als CRLF; Skripte bleiben bei LF wie die mitgelieferten Dateien.
            // file_put_contents(..., LOCK_EX) lehnt den Datenspeicher gbdbdata:// ab; deshalb wurde bisher nichts gespeichert.
            $payload = str_replace(["\r\n", "\r"], "\n", (string)($_POST['content'] ?? ''));
            $saved = GBDBRuntime::run(static fn() :bool => GBDBStorage::atomicWrite($target, $payload));
            $rel = $targetRel;
            $dir = dirname($rel) === '.' ? '' : dirname($rel);
            $msg = $saved ? 'Script gespeichert.' : 'Script konnte nicht gespeichert werden. Schreibrechte prüfen.';
            $msgType = $saved ? 'ok' : 'bad';
        } elseif ($act === 'mkdir') {
            $name = gbdbui_script_rel((string)($_POST['name'] ?? ''));

            if ($name !== '') {
                $folder = gbdbui_script_abs($root, $name);

                if (!is_dir($folder) && @mkdir($folder, 0750, true)) {
                    $msg = 'Ordner erstellt.';
                    $dir = $name;
                } elseif (is_dir($folder)) {
                    $msg = 'Ordner existiert bereits.';
                    $dir = $name;
                } else {
                    $msg = 'Ordner konnte nicht erstellt werden.';
                    $msgType = 'bad';
                }
            } else {
                $msg = 'Ungültiger Ordnername.';
                $msgType = 'bad';
            }
        } elseif ($act === 'run' && $targetRel !== '' && is_file($target)) {
            $runResult = GBDB::runFile($target, []);
            $msg = !empty($runResult['ok']) ? 'Script ausgeführt.' : 'Script-Ausführung fehlgeschlagen.';
            $msgType = !empty($runResult['ok']) ? 'ok' : 'bad';
            $rel = $targetRel;
        } elseif ($act === 'delete' && $targetRel !== '' && is_file($target)) {
            if (@unlink($target)) {
                $msg = 'Datei gelöscht.';
                $rel = '';
            } else {
                $msg = 'Datei konnte nicht gelöscht werden.';
                $msgType = 'bad';
            }
        } elseif ($act === 'rename' && $targetRel !== '' && is_file($target)) {
            $newRel = gbdbui_script_rel((string)($_POST['new_name'] ?? ''));

            if ($newRel === '') {
                $msg = 'Ungültiger neuer Dateiname.';
                $msgType = 'bad';
            } else {
                if (!str_contains($newRel, '/')) {
                    $currentDir = dirname($targetRel) === '.' ? '' : dirname($targetRel);
                    $newRel = ($currentDir !== '' ? $currentDir . '/' : '') . $newRel;
                }

                if (!str_ends_with(strtolower($newRel), '.gql')) {
                    $newRel .= '.gql';
                }
                $newTarget = gbdbui_script_abs($root, $newRel);

                if (is_file($newTarget)) {
                    $msg = 'Zieldatei existiert bereits.';
                    $msgType = 'bad';
                } else {
                    if (!is_dir(dirname($newTarget))) {
                        @mkdir(dirname($newTarget), 0750, true);
                    }

                    if (@rename($target, $newTarget)) {
                        $rel = $newRel;
                        $dir = dirname($rel) === '.' ? '' : dirname($rel);
                        $msg = 'Datei umbenannt.';
                    } else {
                        $msg = 'Datei konnte nicht umbenannt werden.';
                        $msgType = 'bad';
                    }
                }
            }
        } elseif ($act === 'move' && $targetRel !== '' && is_file($target)) {
            $moveDir = gbdbui_script_rel((string)($_POST['move_dir'] ?? ''));
            $newRel = ($moveDir !== '' ? $moveDir . '/' : '') . basename($targetRel);
            $newTarget = gbdbui_script_abs($root, $newRel);

            if ($newRel === $targetRel) {
                $msg = 'Datei liegt bereits in diesem Ordner.';
            } elseif (is_file($newTarget)) {
                $msg = 'Im Zielordner existiert bereits eine Datei mit diesem Namen.';
                $msgType = 'bad';
            } else {
                if ($moveDir !== '' && !is_dir(gbdbui_script_abs($root, $moveDir))) {
                    @mkdir(gbdbui_script_abs($root, $moveDir), 0750, true);
                }

                if (@rename($target, $newTarget)) {
                    $rel = $newRel;
                    $dir = $moveDir;
                    $msg = 'Datei verschoben.';
                } else {
                    $msg = 'Datei konnte nicht verschoben werden.';
                    $msgType = 'bad';
                }
            }
        } elseif ($act === 'rmdir') {
            $name = gbdbui_script_rel((string)($_POST['dir'] ?? ''));
            $folder = gbdbui_script_abs($root, $name);

            if ($name !== '' && is_dir($folder) && @rmdir($folder)) {
                $msg = 'Ordner gelöscht.';
                $dir = dirname($name) === '.' ? '' : dirname($name);
            } else {
                $msg = 'Ordner konnte nur gelöscht werden, wenn er leer ist.';
                $msgType = 'bad';
            }
        }
    }
}

$items = gbdbui_script_tree($root);
$files = array_values(array_filter($items, fn($i) => !$i['is_dir']));
$dirs = array_values(array_filter($items, fn($i) => $i['is_dir']));

$content = '';
$editorFile = $rel;

if ($rel !== '' && is_file(gbdbui_script_abs($root, $rel))) {
    $content = (string)file_get_contents(gbdbui_script_abs($root, $rel));
}

?>
<?php gbdbui_layout_start('gql_scripts', 'GQL-Skripte', '.gql-Dateien mit Ordnernavigation, Editor, Verschieben, Umbenennen und Löschen verwalten.', ['eyebrow' => 'Daten']); ?>

        <?php if ($msg) :
            ?><div class="gbdbui-flash <?= gbdbui_e($msgType) ?>"><?= gbdbui_e($msg) ?></div><?php
        endif; ?>

        <section class="gbdbui-editor-layout gbdbui-script-layout">
            <aside class="gbdbui-panel">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Dateien</h2>
                        <p><?= count($files) ?> Script-Datei(en), <?= count($dirs) ?> Ordner</p>
                    </div>
                    <div class="gbdbui-action-row compact">
                        <a class="gbdbui-pill" href="<?= gbdbui_e(gbdbui_url('gql_scripts', ['file' => ($dir !== '' ? $dir . '/' : '') . 'new_script.gql'])) ?>">+ Neues Script</a>
                    </div>
                </div>

                <div class="gbdbui-pathbar">
                    <a href="<?= gbdbui_e(gbdbui_url('gql_scripts')) ?>">/</a>
                    <?php if ($dir !== '') : ?>
                        <span><?= gbdbui_e($dir) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($items) : ?>
                    <div class="gbdbui-file-list gbdbui-tree-list">
                        <?php foreach ($dirs as $f) : ?>
                            <div class="gbdbui-file-row">
                                <a class="gbdbui-file-link is-dir <?= $f['rel'] === $dir ? 'active' : '' ?>" href="<?= gbdbui_e(gbdbui_url('gql_scripts', ['dir' => $f['rel']])) ?>">
                                    <span><?= gbdbui_icon('folder', 15) ?><?= gbdbui_e($f['rel']) ?></span>
                                    <small>Ordner</small>
                                </a>
                                <form method="post" onsubmit="return confirm('Leeren Ordner löschen?')">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                                    <input type="hidden" name="action" value="rmdir">
                                    <input type="hidden" name="dir" value="<?= gbdbui_e($f['rel']) ?>">
                                    <button class="danger xs" type="submit">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($files as $f) : ?>
                            <a class="gbdbui-file-link <?= $f['rel'] === $rel ? 'active' : '' ?>" href="<?= gbdbui_e(gbdbui_url('gql_scripts', ['file' => $f['rel']])) ?>">
                                <span><?= gbdbui_icon('file', 15) ?><?= gbdbui_e($f['rel']) ?></span>
                                <small><?= gbdbui_e(gbdbui_bytes((int)$f['size'])) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="gbdbui-empty">Noch keine Scripts vorhanden.</div>
                <?php endif; ?>

                <form method="post" style="margin-top:16px">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <input type="hidden" name="action" value="mkdir">
                    <label>Neuer Ordner
                        <input name="name" placeholder="ordner/name" value="<?= gbdbui_e($dir) ?>">
                    </label>
                    <div class="gbdbui-action-row"><button class="secondary" type="submit">Ordner erstellen</button></div>
                </form>
            </aside>

            <section class="gbdbui-panel">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Editor</h2>
                        <p>Script bearbeiten oder neue Datei anlegen. Werte für <code>ENV("name")</code> stehen in der Konstante <code>GREENQL</code> der Framework-Konfiguration.</p>
                    </div>
                    <span class="gbdbui-pill"><?= $editorFile !== '' ? gbdbui_e($editorFile) : 'No file selected' ?></span>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">

                        <label>Datei
                            <input name="file" value="<?= gbdbui_e($editorFile) ?>" placeholder="demo/script.gql">
                        </label>
                        <div class="gbdbui-highlight-editor" data-lang="gql">
                            <pre aria-hidden="true"></pre>
                            <textarea name="content" spellcheck="false" autocomplete="off" placeholder="# GreenQL Script&#10;OUTPUT &quot;Hello GBDB&quot;;">
<?= gbdbui_e($content) ?></textarea>
                        </div>
                        <div class="gbdbui-action-row">
                            <button class="primary" name="action" value="save" type="submit">Speichern</button>
                            <?php if ($rel) :
                                ?><button class="secondary" name="action" value="run" type="submit">Ausführen</button><button class="danger" name="action" value="delete" type="submit" onclick="return confirm('Datei wirklich löschen?')">Löschen</button><?php
                            endif; ?>
                        </div>

                        <?php if ($rel) : ?>
                            <div class="gbdbui-subpanel">
                                <div class="gbdbui-panel-head compact">
                                    <div>
                                        <h3>Datei verwalten</h3>
                                        <p>Ausgewählt: <?= gbdbui_e($rel) ?></p>
                                    </div>
                                </div>

                                <div class="gbdbui-two-cols">
                                    <div>
                                        <label>Umbenennen
                                            <input name="new_name" value="<?= gbdbui_e(basename($rel)) ?>" placeholder="neuer_name.gql oder ordner/name.gql">
                                        </label>
                                        <div class="gbdbui-action-row">
                                            <button class="secondary" name="action" value="rename" type="submit">Datei umbenennen</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label>Verschieben nach
                                            <select name="move_dir">
                                                <option value="">/ Root</option>
                                                <?php foreach ($dirs as $moveTarget) : ?>
                                                    <option value="<?= gbdbui_e($moveTarget['rel']) ?>" <?= (dirname($rel) !== '.' && dirname($rel) === $moveTarget['rel']) ? 'selected' : '' ?>><?= gbdbui_e($moveTarget['rel']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <div class="gbdbui-action-row">
                                            <button class="secondary" name="action" value="move" type="submit">Datei verschieben</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                </form>
            </section>
        </section>
        <?php if (is_array($runResult)) : ?>
            <?php $normalOutputs = gbdbui_gql_outputs($runResult); ?>
            <section class="gbdbui-panel gbdbui-gql-run-output" style="margin-top:18px">
                <div class="gbdbui-panel-head">
                    <div>
                        <h2>Script Output</h2>
                        <p>Normale OUTPUT-Ausgaben direkt lesbar. Die komplette JSON-Antwort ist darunter optional aufklappbar.</p>
                    </div>
                    <span class="gbdbui-pill <?= !empty($runResult['ok']) ? 'ok' : 'danger' ?>"><?= !empty($runResult['ok']) ? 'OK' : 'Fehler' ?></span>
                </div>

                <?php if ($normalOutputs) : ?>
                    <div class="gbdbui-output-stream">
                        <?php foreach ($normalOutputs as $i => $out) : ?>
                            <article class="gbdbui-output-item">
                                <small>#<?= (int)$i + 1 ?> · <?= gbdbui_e($out['command']) ?></small>
                                <pre><?= gbdbui_e(gbdbui_gql_output_text($out['value'])) ?></pre>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="gbdbui-empty">Dieses Script hat keine OUTPUT-Ausgabe erzeugt.</div>
                <?php endif; ?>

                <?php if (!empty($runResult['messages'])) : ?>
                    <div class="gbdbui-subpanel">
                        <h3>Meldungen</h3>
                        <div class="gbdbui-output-messages">
                            <?php foreach ($runResult['messages'] as $m) : ?>
                                <div class="gbdbui-message-line <?= !empty($m['ok']) ? 'ok' : 'bad' ?>"><?= gbdbui_e((string)($m['text'] ?? '')) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <details class="gbdbui-json-details">
                    <summary>Komplette Output-JSON anzeigen</summary>
                    <pre><?= gbdbui_e(json_encode($runResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '') ?></pre>
                </details>
            </section>
        <?php endif; ?>

    <script src="<?= gbdbui_e(gbdbui_asset('js/gbdbui_highlight.js')) ?>"></script>
<?php gbdbui_layout_end(); ?>
