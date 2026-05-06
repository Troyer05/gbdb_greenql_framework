(function () {
    'use strict';

    const escapeHtml = value => String(value || '').replace(/[&<>]/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;'
    }[char]));

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

    const phpKeywordGroups = {
        decl: new Set(['declare', 'namespace', 'use', 'class', 'interface', 'trait', 'enum', 'extends', 'implements', 'function', 'fn', 'const']),
        visibility: new Set(['public', 'private', 'protected', 'static', 'final', 'abstract', 'readonly', 'var', 'global']),
        control: new Set(['if', 'else', 'elseif', 'switch', 'case', 'default', 'match', 'for', 'foreach', 'while', 'do', 'break', 'continue', 'return', 'yield', 'try', 'catch', 'finally', 'throw']),
        include: new Set(['require', 'require_once', 'include', 'include_once']),
        output: new Set(['echo', 'print', 'print_r', 'var_dump', 'var_export', 'die', 'exit']),
        types: new Set(['array', 'callable', 'bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'object', 'mixed', 'void', 'never', 'iterable', 'self', 'parent', 'static']),
        lit: new Set(['true', 'false', 'null'])
    };

    const phpClassForWord = word => {
        const lower = word.toLowerCase();
        for (const [name, set] of Object.entries(phpKeywordGroups)) {
            if (set.has(lower)) return 'tok-php-' + name;
        }
        return '';
    };

    const highlightPhp = src => {
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
                const cls = phpClassForWord(value);

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

    const gqlKeywordGroups = {
        tx: new Set(['BEGIN', 'COMMIT', 'ROLLBACK', 'TRANSACTION', 'PITR']),
        instance: new Set(['USE', 'INSTANCE', 'INSTANCES', 'FORCE']),
        structure: new Set(['ROOT', 'BRANCH', 'GROW', 'DROP', 'ALTER', 'EDIT', 'TABLE', 'BASE', 'COLUMN', 'DEFAULT', 'DESCRIBE', 'PARTITION', 'PARTITIONS', 'TENANT', 'TENANTS']),
        index: new Set(['INDEX', 'UNINDEX', 'REINDEX', 'INDEXES', 'SUGGEST', 'SUGGESTIONS', 'AUTO', 'SHARD', 'SHARDS', 'REPLICA', 'REPLICAS', 'REPLICATION', 'CLUSTER', 'NODE', 'NODES', 'QUORUM']),
        constraint: new Set(['CONSTRAINT', 'CONSTRAINTS', 'UNIQUE', 'REQUIRED']),
        control: new Set(['IF', 'ELSE', 'FOR', 'MAP_OBJECT', 'ERROR', 'MSG', 'TRUE', 'FALSE', 'NULL', 'BACK', 'OUTPUT', 'LOG', 'CLEAR_LOG', 'DELETE_LOG_FILE', 'END_PROC', 'EXISTS', 'JOB', 'QUEUE', 'TRIGGER', 'EVENT', 'VIEW', 'MATERIALIZED', 'PROCEDURE', 'PERMISSION', 'POLICY', 'AUDIT', 'GDPR', 'BLOB', 'MEDIA', 'SOCIAL', 'SOFT_DELETE', 'FANOUT', 'MODERATION']),
        query: new Set(['PICK', 'FROM', 'WHERE', 'SORT', 'ASC', 'DESC', 'LIMIT', 'SIZE', 'SEARCH', 'AFTER', 'COLUMNS', 'MAX', 'SEED', 'WITH', 'RESHAPE', 'ERASE', 'DELETE', 'IN', 'CALL', 'F', 'SHOW', 'CLASS', 'C', 'PUB', 'PRIV', 'AS', 'READONLY', 'READ_ONLY', 'WRITABLE', 'ROLE', 'PRIMARY', 'WORKER', 'FULL', 'COLD', 'ENCRYPTED', 'REMOTE', 'OFFSITE']),
        health: new Set(['PACK', 'PEEK', 'CHECK', 'HEALTH', 'REPAIR', 'SNAPSHOT', 'META', 'EXPLAIN', 'MONITOR', 'RECOVER', 'PAGE', 'CURSOR', 'FULLTEXT', 'STATS', 'ANALYZE', 'BACKUP', 'RESTORE', 'ROTATE', 'RETENTION', 'VERIFY', 'HEARTBEAT', 'PROMOTE', 'FAILOVER']),
        decl: new Set(['DECLARE', 'DECALRE', 'DELACE', 'PARAM', 'HASH', 'HASH_SHA256', 'HASH_SHA512', 'HASH_MD5', 'HASH_ADLER32', 'HASH_CRC32', 'LEN', 'ENV', 'FILE', 'INCLUDE', 'RUN', 'NOW', 'SET_LOGFILE', 'FETCH_API', 'API_FETCH', 'CALL_API', 'UNI_RANDOM', 'SPARK_ID', 'FRESH_ID', 'GET_INSTANCES', 'GET_BASES', 'GET_TABLES', 'FETCH_DATA', 'GET_DATA', 'COUNT_DATA', 'LAST_ADDED', 'ADD_DATA', 'EDIT_DATA', 'DELETE_DATA', 'NEW_COLUMN', 'DELETE_COLUMN', 'ENQUEUE_JOB', 'DELAYED_JOB', 'RETRY_FAILED_JOBS', 'QUEUE_STATS', 'DEFINE_TRIGGER', 'DEFINE_VIEW', 'REFRESH_VIEW', 'GET_VIEW', 'DEFINE_PROCEDURE', 'CALL_PROCEDURE', 'DEFINE_DB_USER', 'DEFINE_DB_ROLE', 'DEFINE_POLICY', 'EVALUATE_POLICY', 'AUDIT_EXPORT', 'AUDIT_SEARCH', 'USER_DATA_EXPORT', 'USER_DATA_DELETE', 'USER_DATA_REDACT', 'MARK_PII_FIELD', 'ENCRYPTION_CONFIG', 'ROTATE_KEY', 'PUT_BLOB', 'SIGNED_URL', 'DELETE_MEDIA', 'INSTALL_SOCIAL_PATTERNS', 'SOCIAL_SCHEMA_PATTERNS'])
    };

    const gqlClassForWord = word => {
        const upper = word.toUpperCase();
        for (const [cls, set] of Object.entries(gqlKeywordGroups)) {
            if (set.has(upper)) return 'tok-' + cls;
        }
        return '';
    };

    const highlightGql = src => {
        let out = '';
        let i = 0;

        while (i < src.length) {
            const ch = src[i];
            const next = src[i + 1] || '';
            const rest = src.slice(i);

            if (ch === '#') {
                const j = readLine(src, i);
                out += '<span class="tok-comment">' + escapeHtml(src.slice(i, j)) + '</span>';
                i = j;
                continue;
            }

            if ((ch === '-' && next === '-') || (ch === '/' && next === '/')) {
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

            const assign = rest.match(/^([$]?[A-Za-z_][A-Za-z0-9_]*)\s*(?==)/);
            if (assign) {
                const cls = assign[1].startsWith('_') || assign[1].startsWith('$') ? 'tok-var' : 'tok-field';
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
                const value = word[0];
                const cls = gqlClassForWord(value);

                if (value.startsWith('_')) {
                    out += '<span class="tok-var">' + escapeHtml(value) + '</span>';
                } else if (/^(true|false|null|now)$/i.test(value)) {
                    out += '<span class="tok-lit">' + escapeHtml(value) + '</span>';
                } else if (src.slice(i + value.length).match(/^\s*\(/)) {
                    out += '<span class="tok-fn">' + escapeHtml(value) + '</span>';
                } else {
                    out += cls ? '<span class="' + cls + '">' + escapeHtml(value) + '</span>' : escapeHtml(value);
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


    const highlightJson = src => {
        let out = '';
        let i = 0;

        while (i < src.length) {
            const ch = src[i];
            const rest = src.slice(i);

            if (ch === '"') {
                const j = readQuoted(src, i, ch);
                const token = src.slice(i, j);
                const after = src.slice(j).match(/^\s*:/);
                out += '<span class="' + (after ? 'tok-field' : 'tok-string') + '">' + escapeHtml(token) + '</span>';
                i = j;
                continue;
            }

            const lit = rest.match(/^(true|false|null)\b/i);
            if (lit) {
                out += '<span class="tok-lit">' + escapeHtml(lit[0]) + '</span>';
                i += lit[0].length;
                continue;
            }

            const number = rest.match(/^-?\d+(?:\.\d+)?(?:e[+-]?\d+)?/i);
            if (number) {
                out += '<span class="tok-num">' + escapeHtml(number[0]) + '</span>';
                i += number[0].length;
                continue;
            }

            if ('{}[]'.includes(ch)) {
                out += '<span class="tok-brace">' + escapeHtml(ch) + '</span>';
                i++;
                continue;
            }

            if (':,'.includes(ch)) {
                out += '<span class="tok-op">' + escapeHtml(ch) + '</span>';
                i++;
                continue;
            }

            out += escapeHtml(ch);
            i++;
        }

        return out;
    };

    const currentIndent = line => (line.match(/^\s*/) || [''])[0];

    function bindEditor(box) {
        const pre = box.querySelector('pre');
        const textarea = box.querySelector('textarea');
        const lang = String(box.dataset.lang || 'gql').toLowerCase();
        if (!pre || !textarea) return;

        const highlight = lang === 'php' ? highlightPhp : (lang === 'json' ? highlightJson : highlightGql);

        const paint = () => {
            pre.innerHTML = highlight(textarea.value) + '\n';
            pre.scrollTop = textarea.scrollTop;
            pre.scrollLeft = textarea.scrollLeft;
        };

        const autoIndent = event => {
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
            const opens = lang === 'php' ? /(?:\{|\[|\()\s*$/.test(line) : /(?:\{|\[)\s*$/.test(line);
            const closesNext = lang === 'php' ? /^\s*(?:\}|\]|\))/.test(after) : /^\s*(?:\}|\])/.test(after);

            if (opens) indent += '    ';
            if (closesNext) indent = indent.replace(/ {1,4}$/, '');

            textarea.setRangeText('\n' + indent, pos, textarea.selectionEnd, 'end');
            paint();
        };

        textarea.addEventListener('input', paint);
        textarea.addEventListener('keydown', autoIndent);
        textarea.addEventListener('scroll', paint);
        paint();
    }

    document.querySelectorAll('.gbdbui-highlight-editor').forEach(bindEditor);
}());
