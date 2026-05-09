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

    const gqlBuiltinFunctions = new Set(["add_data", "api_fetch", "audit_export", "audit_search", "base_exists", "bases", "call_api", "call_procedure", "copy_data", "count_data", "cursor", "data_exists", "define_db_role", "define_db_user", "define_policy", "define_procedure", "define_trigger", "define_view", "delayed_job", "delete_base", "delete_column", "delete_data", "delete_data_recursive", "delete_instance", "delete_media", "delete_table", "drop_base", "drop_instance", "drop_table", "edit_data", "editdata", "encryption_config", "enqueue_job", "env", "erase_data", "evaluate_policy", "exec_pattern", "execpattern", "execpattern", "fetch", "fetch_api", "fetch_data", "fresh_id", "fulltext_search", "fusion", "get_bases", "get_data", "get_instances", "get_tables", "get_view", "hash", "hash_adler32", "hash_crc32", "hash_md5", "hash_pass", "hash_sha256", "hash_sha512", "install_social_patterns", "instance_exists", "instances", "last_added", "last_data", "len", "load_pattern", "loadpattern", "loadpattern", "lock_data", "mark_pii_field", "monitor", "move_data", "new_column", "now", "page", "param", "plant_data", "prune_column", "put_blob", "queue_stats", "random_int", "recover", "refresh_view", "rename_base", "rename_instance", "rename_table", "reshape_data", "retry_failed_jobs", "rotate_key", "seed_data", "set_data_readonly", "signed_url", "social_schema_patterns", "soft_delete", "spark_id", "sprout_column", "table_exists", "tables", "tally_data", "transfer_data", "transfer_data_delete", "uni_random", "user_data_delete", "user_data_export", "user_data_redact", "uuid"]);
    const gqlTypes = new Set(["any", "arr", "array", "blob", "blob_reference", "bool", "boolean", "date", "datetime", "datetype", "decimal", "double", "email", "enum", "float", "int", "integer", "json", "map", "mixed", "number", "obj", "object", "str", "string", "text", "time", "timestamp", "timetype", "ulid", "url", "uuid", "var"]);
    const gqlImplementedFunctions = new Set(["now", "param", "env", "len", "fusion", "uuid", "uni_random", "spark_id", "fresh_id", "random_int", "hash", "hash_sha256", "hash_sha512", "hash_md5", "hash_adler32", "hash_crc32", "hash_pass", "loadpattern", "loadpattern", "load_pattern", "execpattern", "execpattern", "exec_pattern", "fetch_api", "api_fetch", "call_api", "get_instances", "instances", "get_bases", "bases", "get_tables", "tables", "instance_exists", "base_exists", "table_exists", "data_exists", "get_data", "fetch_data", "fetch", "count_data", "tally_data", "last_added", "last_data", "add_data", "plant_data", "seed_data", "edit_data", "editdata", "reshape_data", "delete_data", "erase_data", "delete_data_recursive", "transfer_data", "copy_data", "transfer_data_delete", "move_data", "set_data_readonly", "lock_data", "new_column", "sprout_column", "delete_column", "prune_column", "delete_instance", "drop_instance", "delete_base", "drop_base", "delete_table", "drop_table", "rename_instance", "rename_base", "rename_table", "monitor", "recover", "page", "cursor", "fulltext_search"]);
    const gqlIdentityFunctions = new Set(["now", "param", "env", "len", "fusion", "uuid", "uni_random", "spark_id", "fresh_id", "random_int"]);
    const gqlPatternFunctions = new Set(["loadpattern", "loadpattern", "load_pattern", "execpattern", "execpattern", "exec_pattern"]);
    const gqlHashFunctions = new Set(["hash", "hash_sha256", "hash_sha512", "hash_md5", "hash_adler32", "hash_crc32", "hash_pass"]);
    const gqlApiFunctions = new Set(["fetch_api", "api_fetch", "call_api"]);
    const gqlInstanceFunctions = new Set(["get_instances", "instances", "get_bases", "bases", "get_tables", "tables", "instance_exists", "base_exists", "table_exists", "data_exists"]);
    const gqlDataFunctions = new Set(["get_data", "fetch_data", "fetch", "count_data", "tally_data", "last_added", "last_data", "add_data", "plant_data", "seed_data", "edit_data", "editdata", "reshape_data", "delete_data", "erase_data", "delete_data_recursive", "transfer_data", "copy_data", "transfer_data_delete", "move_data", "set_data_readonly", "lock_data"]);
    const gqlSchemaFunctions = new Set(["new_column", "sprout_column", "delete_column", "prune_column", "delete_instance", "drop_instance", "delete_base", "drop_base", "delete_table", "drop_table", "rename_instance", "rename_base", "rename_table"]);
    const gqlAdvancedFunctions = new Set(["monitor", "recover", "page", "cursor", "fulltext_search"]);
    const gqlDeclaredOnlyFunctions = new Set(["enqueue_job", "delayed_job", "retry_failed_jobs", "queue_stats", "define_trigger", "define_view", "refresh_view", "get_view", "define_procedure", "call_procedure", "define_db_user", "define_db_role", "define_policy", "evaluate_policy", "audit_export", "audit_search", "user_data_export", "user_data_delete", "user_data_redact", "mark_pii_field", "encryption_config", "rotate_key", "put_blob", "signed_url", "delete_media", "install_social_patterns", "social_schema_patterns", "soft_delete"]);

    const gqlBuiltinClass = value => {
        const lower = String(value || '').toLowerCase();
        if (gqlIdentityFunctions.has(lower)) return 'tok-identity-fn';
        if (gqlPatternFunctions.has(lower)) return 'tok-pattern-fn';
        if (gqlHashFunctions.has(lower)) return 'tok-hash-fn';
        if (gqlApiFunctions.has(lower)) return 'tok-api-fn';
        if (gqlInstanceFunctions.has(lower)) return 'tok-instance-fn';
        if (gqlDataFunctions.has(lower)) return 'tok-data-fn';
        if (gqlSchemaFunctions.has(lower)) return 'tok-schema-fn';
        if (gqlAdvancedFunctions.has(lower)) return 'tok-advanced-fn';
        if (gqlDeclaredOnlyFunctions.has(lower)) return 'tok-declared-only-fn';
        return gqlImplementedFunctions.has(lower) ? 'tok-builtin-fn' : 'tok-declared-only-fn';
    };

    const isGqlBuiltinFunction = value => gqlBuiltinFunctions.has(String(value || '').toLowerCase());
    const isGqlType = value => gqlTypes.has(String(value || '').toLowerCase().replace(/^:/, ''));

    const gqlKeywordGroups = {
        "tx": new Set(["BEGIN", "COMMIT", "ROLLBACK", "TRANSACTION", "SAVEPOINT", "TIMEOUT", "TX", "TO"]),
        "instance": new Set(["USE", "INSTANCE", "INSTANCES", "ROOT", "FORCE"]),
        "structure": new Set(["BRANCH", "GROW", "DROP", "ALTER", "EDIT", "TABLE", "TABLES", "BASE", "BASES", "COLUMN", "COLUMNS", "CREATE", "RENAME", "INTO", "DEFAULT", "DESCRIBE", "TYPE", "TYPES", "WITH", "WITHOUT", "SHOW"]),
        "index": new Set(["INDEX", "UNINDEX", "REINDEX", "INDEXES", "SUGGEST", "SUGGESTIONS", "AUTO", "PRIMARY", "COMPOSITE", "SORTED", "RANGE", "PREFIX", "FULLTEXT"]),
        "constraint": new Set(["CONSTRAINT", "CONSTRAINTS", "UNIQUE", "REQUIRED", "NULLABLE", "NOT", "NOT_NULL", "FOREIGN", "KEY", "REFERENCES", "CASCADE", "RESTRICT", "SET", "SET_NULL", "RELATIONS"]),
        "control": new Set(["IF", "ELSE", "FOR", "MAP_OBJECT", "ERROR", "MSG", "TRUE", "FALSE", "NULL", "BACK", "OUTPUT", "LOG", "CLEAR_LOG", "DELETE_LOG_FILE", "END_PROC", "EXISTS", "AS"]),
        "query": new Set(["PICK", "FROM", "WHERE", "SORT", "ASC", "DESC", "LIMIT", "OFFSET", "SIZE", "SEARCH", "AFTER", "MAX", "MIN", "AVG", "SUM", "COUNT", "DISTINCT", "GROUP", "BY", "HAVING", "SEED", "RESHAPE", "ERASE", "DELETE", "IN", "CALL", "ON", "AND", "OR", "QUERY", "OPTIONS", "PREPARE", "EXECUTE", "INNER", "LEFT", "JOIN"]),
        "health": new Set(["PACK", "PEEK", "CHECK", "HEALTH", "REPAIR", "SNAPSHOT", "META", "EXPLAIN", "MONITOR", "RECOVER", "PAGE", "CURSOR", "STATS", "ANALYZE", "BACKUP", "MIGRATE", "MIGRATE_TO_V2"]),
        "cluster": new Set(["PARTITION", "PARTITIONS", "SHARD", "SHARDS", "REGISTER", "CLUSTER", "HEARTBEAT", "NODE", "BUCKETS", "ROLE", "REPLICA", "WORKER", "READONLY", "READ_ONLY", "WRITABLE", "TENANT", "USER", "HASH", "DATE"]),
        "security": new Set(["GRANT", "REVOKE", "PUBLIC", "PRIVATE", "PROTECTED", "STATIC", "PUB", "PRIV"]),
        "oop": new Set(["F", "FUNCTION", "C", "CLASS"]),
        "file": new Set(["FILE", "INCLUDE", "RUN", "EXEC", "PATTERN", "SET_LOGFILE"]),
        "decl": new Set(["DECLARE", "DECALRE", "DELACE"])
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

            if (ch === '/' && next === '*') {
                const j = readBlockComment(src, i);
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
                const token = src.slice(i, j);
                const after = src.slice(j).match(/^\s*:/);
                out += '<span class="' + (after ? 'tok-field' : 'tok-string') + '">' + escapeHtml(token) + '</span>';
                i = j;
                continue;
            }

            const fileCommand = rest.match(/^(FILE)(\s*\.\s*)(INCLUDE|RUN|BACK)\b/i);
            if (fileCommand) {
                out += '<span class="tok-file">' + escapeHtml(fileCommand[1]) + '</span>';
                out += '<span class="tok-op">' + escapeHtml(fileCommand[2]) + '</span>';
                out += '<span class="tok-file">' + escapeHtml(fileCommand[3]) + '</span>';
                i += fileCommand[0].length;
                continue;
            }

            const typedDeclare = rest.match(/^(PUB|PRIV|PUBLIC|PRIVATE|PROTECTED)?(\s+)?(DECLARE|DECALRE|DELACE)(\s+)(:)([A-Za-z_][A-Za-z0-9_]*)(\s+)([$]?[A-Za-z_][A-Za-z0-9_]*)/i);
            if (typedDeclare) {
                if (typedDeclare[1]) out += '<span class="tok-security">' + escapeHtml(typedDeclare[1]) + '</span>' + escapeHtml(typedDeclare[2] || '');
                out += '<span class="tok-decl">' + escapeHtml(typedDeclare[3]) + '</span>' + escapeHtml(typedDeclare[4]);
                out += '<span class="tok-op">:</span><span class="tok-type">' + escapeHtml(typedDeclare[6]) + '</span>' + escapeHtml(typedDeclare[7]);
                out += '<span class="tok-var-decl">' + escapeHtml(typedDeclare[8]) + '</span>';
                i += typedDeclare[0].length;
                continue;
            }

            const plainDeclare = rest.match(/^(PUB|PRIV|PUBLIC|PRIVATE|PROTECTED)?(\s+)?(DECLARE|DECALRE|DELACE)(\s+)([$]?[A-Za-z_][A-Za-z0-9_]*)/i);
            if (plainDeclare) {
                if (plainDeclare[1]) out += '<span class="tok-security">' + escapeHtml(plainDeclare[1]) + '</span>' + escapeHtml(plainDeclare[2] || '');
                out += '<span class="tok-decl">' + escapeHtml(plainDeclare[3]) + '</span>' + escapeHtml(plainDeclare[4]);
                out += '<span class="tok-var-decl">' + escapeHtml(plainDeclare[5]) + '</span>';
                i += plainDeclare[0].length;
                continue;
            }

            const fnDecl = rest.match(/^(PUB|PRIV|PUBLIC|PRIVATE|PROTECTED)?(\s+)?(F|FUNCTION)(\s+)([A-Za-z_][A-Za-z0-9_]*)(?=\s*\()/i);
            if (fnDecl) {
                if (fnDecl[1]) out += '<span class="tok-security">' + escapeHtml(fnDecl[1]) + '</span>' + escapeHtml(fnDecl[2] || '');
                out += '<span class="tok-fn-key">' + escapeHtml(fnDecl[3]) + '</span>' + escapeHtml(fnDecl[4]);
                out += '<span class="tok-fndef">' + escapeHtml(fnDecl[5]) + '</span>';
                i += fnDecl[0].length;
                continue;
            }

            const classDecl = rest.match(/^(C|CLASS)(\s+)([A-Za-z_][A-Za-z0-9_]*)(?!\s*[/(])/i);
            if (classDecl) {
                out += '<span class="tok-class-key">' + escapeHtml(classDecl[1]) + '</span>' + escapeHtml(classDecl[2]);
                out += '<span class="tok-class-name">' + escapeHtml(classDecl[3]) + '</span>';
                i += classDecl[0].length;
                continue;
            }

            const classCall = rest.match(/^(CALL|CLASS)(\s+)([A-Za-z_][A-Za-z0-9_]*)(\s*\/\s*)([A-Za-z_][A-Za-z0-9_]*)(?=\s*\()/i);
            if (classCall) {
                out += '<span class="tok-query">' + escapeHtml(classCall[1]) + '</span>' + escapeHtml(classCall[2]);
                out += '<span class="tok-class-name">' + escapeHtml(classCall[3]) + '</span>';
                out += '<span class="tok-op">' + escapeHtml(classCall[4]) + '</span>';
                out += '<span class="tok-user-fn">' + escapeHtml(classCall[5]) + '</span>';
                i += classCall[0].length;
                continue;
            }

            const srvBridge = rest.match(/^(srv|SRV|Srv|secondserver|Secondserver|SecondServer)(\s*\/\s*)([A-Za-z_][A-Za-z0-9_]*)(?=\s*\()/);
            if (srvBridge) {
                out += '<span class="tok-srv-namespace">' + escapeHtml(srvBridge[1]) + '</span>';
                out += '<span class="tok-op">' + escapeHtml(srvBridge[2]) + '</span>';
                out += '<span class="tok-srv-fn">' + escapeHtml(srvBridge[3]) + '</span>';
                i += srvBridge[0].length;
                continue;
            }

            const thisMember = rest.match(/^this\s*\.\s*_[A-Za-z][A-Za-z0-9_]*/i);
            if (thisMember) {
                out += '<span class="tok-this">' + escapeHtml(thisMember[0]) + '</span>';
                i += thisMember[0].length;
                continue;
            }

            const optionalVar = rest.match(/^\?([$]?[A-Za-z_][A-Za-z0-9_]*)/);
            if (optionalVar) {
                out += '<span class="tok-op">?</span><span class="tok-var">' + escapeHtml(optionalVar[1]) + '</span>';
                i += optionalVar[0].length;
                continue;
            }

            const constVar = rest.match(/^\$[A-Za-z_][A-Za-z0-9_]*/);
            if (constVar) {
                out += '<span class="tok-const">' + escapeHtml(constVar[0]) + '</span>';
                i += constVar[0].length;
                continue;
            }

            const normalVar = rest.match(/^_[A-Za-z_][A-Za-z0-9_]*/);
            if (normalVar) {
                out += '<span class="tok-var">' + escapeHtml(normalVar[0]) + '</span>';
                i += normalVar[0].length;
                continue;
            }

            const typedColon = rest.match(/^:([A-Za-z_][A-Za-z0-9_]*)/);
            if (typedColon && isGqlType(typedColon[1])) {
                out += '<span class="tok-op">:</span><span class="tok-type">' + escapeHtml(typedColon[1]) + '</span>';
                i += typedColon[0].length;
                continue;
            }

            const bareObjectKey = rest.match(/^[A-Za-z_][A-Za-z0-9_\-]*(?=\s*:)/);
            if (bareObjectKey) {
                out += '<span class="tok-field">' + escapeHtml(bareObjectKey[0]) + '</span>';
                i += bareObjectKey[0].length;
                continue;
            }

            const qualifiedName = rest.match(/^([A-Za-z0-9_\-]+)(\.)([A-Za-z0-9_\-]+)\b/);
            if (qualifiedName) {
                out += '<span class="tok-field">' + escapeHtml(qualifiedName[1]) + '</span>';
                out += '<span class="tok-op">.</span>';
                out += '<span class="tok-field">' + escapeHtml(qualifiedName[3]) + '</span>';
                i += qualifiedName[0].length;
                continue;
            }

            const word = rest.match(/^[A-Za-z_][A-Za-z0-9_]*/);
            if (word) {
                const value = word[0];
                const cls = gqlClassForWord(value);
                const isCall = src.slice(i + value.length).match(/^\s*\(/);

                if (/^(true|false|null|NOW)$/i.test(value) && !isCall) {
                    out += '<span class="tok-lit">' + escapeHtml(value) + '</span>';
                } else if (isCall && isGqlBuiltinFunction(value)) {
                    out += '<span class="' + gqlBuiltinClass(value) + '">' + escapeHtml(value) + '</span>';
                } else if (isCall) {
                    out += '<span class="tok-user-fn">' + escapeHtml(value) + '</span>';
                } else if (isGqlType(value)) {
                    out += '<span class="tok-type">' + escapeHtml(value) + '</span>';
                } else {
                    out += cls ? '<span class="' + cls + '">' + escapeHtml(value) + '</span>' : escapeHtml(value);
                }

                i += value.length;
                continue;
            }

            const number = rest.match(/^-?\d+(?:\.\d+)?(?:e[+-]?\d+)?/i);
            if (number) {
                out += '<span class="tok-num">' + escapeHtml(number[0]) + '</span>';
                i += number[0].length;
                continue;
            }

            const multiOp = rest.match(/^(?:==|!=|>=|<=|~=|&&|\|\||\+\+|--)/);
            if (multiOp) {
                out += '<span class="tok-op">' + escapeHtml(multiOp[0]) + '</span>';
                i += multiOp[0].length;
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

            if ('=!<>:+-/%.,|&~'.includes(ch)) {
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

    window.GBDBUIHighlight = { php: highlightPhp, gql: highlightGql, json: highlightJson };

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
