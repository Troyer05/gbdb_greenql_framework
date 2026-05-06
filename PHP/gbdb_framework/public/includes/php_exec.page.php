<?php
declare(strict_types=1);
require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('php_exec');

$defaultCode = <<<'PHPDEF'
<?php
// Schneller Framework-Test im DEV-Browser.
// Ausgabe landet rechts im Output-Block.

echo hash('sha256', 'hallo') . "\n";

print_r([
    "version" => Vars::app_version(),
    "dev" => Vars::__DEV__(),
    "db_arch" => class_exists("Vars") && method_exists("Vars", "db_arch") ? Vars::db_arch() : (defined("DB_ARCH") ? DB_ARCH : "GBDB")
]);
PHPDEF;

$code = (string)($_POST['code'] ?? $defaultCode);
$result = null;

/**
 * Entfernt öffnende PHP-Tags für eval.
 * @param string $code PHP-Code.
 * @return string Rückgabewert.
 */
function gbdbui_php_exec_prepare(string $code): string {
    $code = preg_replace('/^\s*<\?(php)?/i', '', $code) ?? $code;
    $code = preg_replace('/\?>\s*$/', '', $code) ?? $code;
    return $code;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['php_action'] ?? '') === 'run') {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        $result = ['ok' => false, 'output' => '', 'error' => 'Ungültiger Sicherheits-Token.'];
    } else {
        $prepared = gbdbui_php_exec_prepare($code);
        $output = '';
        $error = '';
        $ok = true;

        ob_start();
        try {
            eval($prepared);
        } catch (Throwable $e) {
            $ok = false;
            $error = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        } finally {
            $output = (string)ob_get_clean();
        }

        $result = ['ok' => $ok, 'output' => $output, 'error' => $error];
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI PHP Exec</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css">
</head>
<body class="gbdbui-dashboard">
    <?php gbdbui_nav('php_exec'); ?>
    <main class="gbdbui-wide">
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Admin-only · DEV-Modus</p>
            <h1>PHP Web Execution</h1>
            <p>Direktes Testen von Framework-Code im Browser. Der Editor nutzt jetzt dieselbe Overlay-Technologie wie GreenQL v2: echtes Textarea-Caret, darüber ein sauber geparstes Highlighting ohne HTML-Verschiebung.</p>
        </section>

        <section class="gbdbui-exec-layout">
            <form method="post" class="gbdbui-exec-editor" id="php-exec-form">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="php_action" value="run">
                <div class="gbdbui-editor-head">
                    <strong>PHP Editor</strong>
                    <div>
                        <button type="button" class="gbdbui-mini-btn" id="indent-code">Auto-Einrücken</button>
                        <button class="gbdbui-mini-btn primary">Ausführen</button>
                    </div>
                </div>
                <div class="gbdbui-code-wrap gbdbui-php-editor-shell">
                    <pre id="php-highlight" aria-hidden="true"></pre>
                    <textarea id="php-code" name="code" spellcheck="false" autocomplete="off"><?= gbdbui_e($code) ?></textarea>
                </div>
            </form>

            <aside class="gbdbui-output-card">
                <div class="gbdbui-editor-head"><strong>Output</strong><span><?= $result === null ? 'bereit' : (!empty($result['ok']) ? 'ok' : 'error') ?></span></div>
                <?php if ($result === null): ?>
                    <pre>Noch keine Ausführung.</pre>
                <?php else: ?>
                    <?php if ((string)$result['error'] !== ''): ?><div class="gbdbui-flash bad"><?= gbdbui_e($result['error']) ?></div><?php endif; ?>
                    <pre><?= gbdbui_e((string)$result['output']) ?></pre>
                <?php endif; ?>
            </aside>
        </section>
    </main>

    <script>
    (function() {
        const textarea = document.getElementById('php-code');
        const high = document.getElementById('php-highlight');
        const indentBtn = document.getElementById('indent-code');

        const escapeHtml = value => value.replace(/[&<>]/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        }[char]));

        const keywordGroups = {
            decl: new Set(['declare', 'namespace', 'use', 'class', 'interface', 'trait', 'enum', 'extends', 'implements', 'function', 'fn', 'const']),
            visibility: new Set(['public', 'private', 'protected', 'static', 'final', 'abstract', 'readonly', 'var', 'global']),
            control: new Set(['if', 'else', 'elseif', 'switch', 'case', 'default', 'match', 'for', 'foreach', 'while', 'do', 'break', 'continue', 'return', 'yield', 'try', 'catch', 'finally', 'throw']),
            include: new Set(['require', 'require_once', 'include', 'include_once']),
            output: new Set(['echo', 'print', 'print_r', 'var_dump', 'var_export', 'die', 'exit']),
            types: new Set(['array', 'callable', 'bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'object', 'mixed', 'void', 'never', 'iterable', 'self', 'parent', 'static']),
            lit: new Set(['true', 'false', 'null'])
        };

        const classForWord = word => {
            const lower = word.toLowerCase();
            for (const [name, set] of Object.entries(keywordGroups)) {
                if (set.has(lower)) return 'tok-php-' + name;
            }
            return '';
        };

        const readQuoted = (src, start, quote) => {
            let i = start + 1;
            while (i < src.length) {
                if (src[i] === '\\') {
                    i += 2;
                    continue;
                }
                if (src[i] === quote) {
                    i++;
                    break;
                }
                i++;
            }
            return i;
        };

        const readBlockComment = (src, start) => {
            const end = src.indexOf('*/', start + 2);
            return end === -1 ? src.length : end + 2;
        };

        const readLine = (src, start) => {
            const end = src.indexOf('\n', start);
            return end === -1 ? src.length : end;
        };

        const highlight = src => {
            let out = '';
            let i = 0;

            while (i < src.length) {
                const ch = src[i];
                const next = src[i + 1] || '';
                const rest = src.slice(i);

                if (ch === '<' && rest.toLowerCase().startsWith('<?php')) {
                    out += '<span class="tok-php-tag">' + escapeHtml(src.slice(i, i + 5)) + '</span>';
                    i += 5;
                    continue;
                }

                if (ch === '<' && next === '?') {
                    out += '<span class="tok-php-tag">' + escapeHtml(src.slice(i, i + 2)) + '</span>';
                    i += 2;
                    continue;
                }

                if (ch === '?' && next === '>') {
                    out += '<span class="tok-php-tag">?&gt;</span>';
                    i += 2;
                    continue;
                }

                if (ch === '/' && next === '*') {
                    const j = readBlockComment(src, i);
                    out += '<span class="tok-comment">' + escapeHtml(src.slice(i, j)) + '</span>';
                    i = j;
                    continue;
                }

                if ((ch === '/' && next === '/') || ch === '#') {
                    const j = readLine(src, i);
                    out += '<span class="tok-comment">' + escapeHtml(src.slice(i, j)) + '</span>';
                    i = j;
                    continue;
                }

                if (ch === '"' || ch === "'") {
                    const j = readQuoted(src, i, ch);
                    out += '<span class="tok-string">' + escapeHtml(src.slice(i, j)) + '</span>';
                    i = j;
                    continue;
                }

                const variable = rest.match(/^\$[A-Za-z_][A-Za-z0-9_]*/);
                if (variable) {
                    out += '<span class="tok-var">' + escapeHtml(variable[0]) + '</span>';
                    i += variable[0].length;
                    continue;
                }

                const staticMember = rest.match(/^::[A-Za-z_][A-Za-z0-9_]*/);
                if (staticMember) {
                    out += '<span class="tok-field">' + escapeHtml(staticMember[0]) + '</span>';
                    i += staticMember[0].length;
                    continue;
                }

                const objectMember = rest.match(/^-&gt;[A-Za-z_][A-Za-z0-9_]*/);
                if (objectMember) {
                    out += '<span class="tok-field">' + escapeHtml(objectMember[0]) + '</span>';
                    i += objectMember[0].length;
                    continue;
                }

                const word = rest.match(/^[A-Za-z_][A-Za-z0-9_]*/);
                if (word) {
                    const value = word[0];
                    const cls = classForWord(value);

                    if (cls !== '') {
                        out += '<span class="' + cls + '">' + escapeHtml(value) + '</span>';
                    } else if (src.slice(i + value.length).match(/^\s*\(/)) {
                        out += '<span class="tok-fn">' + escapeHtml(value) + '</span>';
                    } else if (value[0] === value[0].toUpperCase()) {
                        out += '<span class="tok-field">' + escapeHtml(value) + '</span>';
                    } else {
                        out += escapeHtml(value);
                    }

                    i += value.length;
                    continue;
                }

                const number = rest.match(/^\d+(?:\.\d+)?/);
                if (number) {
                    out += '<span class="tok-num">' + escapeHtml(number[0]) + '</span>';
                    i += number[0].length;
                    continue;
                }

                if ('{}[]()'.includes(ch)) {
                    out += '<span class="tok-brace">' + escapeHtml(ch) + '</span>';
                    i++;
                    continue;
                }

                if ('=!<>:+-*/%.,|&^~@'.includes(ch)) {
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

        const paint = () => {
            if (!textarea || !high) return;
            high.innerHTML = highlight(textarea.value) + '\n';
            high.scrollTop = textarea.scrollTop;
            high.scrollLeft = textarea.scrollLeft;
        };

        const keyboardIndent = event => {
            if (!textarea) return;

            if (event.key === 'Tab') {
                event.preventDefault();
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;

                if (start !== end && textarea.value.slice(start, end).includes('\n')) {
                    const before = textarea.value.slice(0, start);
                    const selected = textarea.value.slice(start, end);
                    const after = textarea.value.slice(end);
                    const indented = selected.split('\n').map(line => line === '' ? line : '    ' + line).join('\n');
                    textarea.value = before + indented + after;
                    textarea.selectionStart = start;
                    textarea.selectionEnd = start + indented.length;
                } else {
                    textarea.setRangeText('    ', start, end, 'end');
                }

                paint();
                return;
            }

            if (event.key === '}') {
                const pos = textarea.selectionStart;
                const before = textarea.value.slice(0, pos);
                const lineStart = before.lastIndexOf('\n') + 1;
                const line = before.slice(lineStart);

                if (/^\s*$/.test(line)) {
                    event.preventDefault();
                    const base = currentIndent(line).replace(/ {1,4}$/, '');
                    textarea.setRangeText(base + '}', lineStart, pos, 'end');
                    paint();
                }

                return;
            }

            if (event.key !== 'Enter') return;

            event.preventDefault();
            const pos = textarea.selectionStart;
            const before = textarea.value.slice(0, pos);
            const after = textarea.value.slice(textarea.selectionEnd);
            const line = before.split('\n').pop() || '';
            let indent = currentIndent(line);
            const opens = /(?:\{|\[|\()\s*$/.test(line);
            const closesNext = /^\s*(?:\}|\]|\))/.test(after);

            if (opens) indent += '    ';
            if (closesNext) indent = indent.replace(/ {1,4}$/, '');

            textarea.setRangeText('\n' + indent, pos, textarea.selectionEnd, 'end');
            paint();
        };

        const formatPhp = () => {
            if (!textarea) return;

            const lines = textarea.value.replace(/\r\n/g, '\n').split('\n');
            let level = 0;
            let inBlockComment = false;
            const out = [];

            for (const raw of lines) {
                const trimmed = raw.trim();

                if (trimmed === '') {
                    out.push('');
                    continue;
                }

                if (inBlockComment) {
                    out.push('    '.repeat(level) + trimmed);
                    if (trimmed.includes('*/')) inBlockComment = false;
                    continue;
                }

                if (trimmed.startsWith('/*') && !trimmed.includes('*/')) {
                    out.push('    '.repeat(level) + trimmed);
                    inBlockComment = true;
                    continue;
                }

                if (/^(\}|\]|\)|case\b|default\b)/i.test(trimmed)) level = Math.max(0, level - 1);

                out.push('    '.repeat(level) + trimmed);

                const clean = trimmed
                    .replace(/\/\/.*$/g, '')
                    .replace(/#.*$/g, '')
                    .replace(/'(?:\\.|[^'\\])*'/g, "''")
                    .replace(/"(?:\\.|[^"\\])*"/g, '""');

                const opens = (clean.match(/[({[]/g) || []).length;
                const closes = (clean.match(/[)}\]]/g) || []).length;
                level = Math.max(0, level + opens - closes);

                if (/^(case\b|default\b)/i.test(trimmed)) level++;
            }

            textarea.value = out.join('\n');
            paint();
        };

        if (textarea && high) {
            textarea.addEventListener('input', paint);
            textarea.addEventListener('keydown', keyboardIndent);
            textarea.addEventListener('scroll', paint);
            if (indentBtn) indentBtn.addEventListener('click', formatPhp);
            paint();
        }
    })();
    </script>
</body>
</html>
