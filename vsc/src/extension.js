const vscode = require("vscode");

const COMMAND_SPECS = [
    ["META", "Script-Metadaten als Objekt setzen: META = {...};", "Meta"],
    ["DECLARE", "Variable oder Konstante deklarieren. Optional mit Datentyp: declare :int _id = 0; oder declare :string $APP_NAME = \"demo\";", "Declaration"],
    ["DECALRE", "Tippfehler-kompatibler Alias für DECLARE.", "Declaration"],
    ["DELACE", "Legacy-/Alias-Schreibweise für DECLARE.", "Declaration"],
    ["OUTPUT", "Wert in den Output-Stream schreiben. Unterstützt OUTPUT wert; und OUTPUT(wert);", "Output"],
    ["MSG", "Nachricht ausgeben.", "Output"],
    ["ERROR MSG", "Fehler mit Nachricht ausgeben.", "Output"],
    ["SET_LOGFILE", "Aktive GreenQL-Logdatei setzen.", "Logging"],
    ["LOG", "Wert(e) in die aktive Logdatei schreiben.", "Logging"],
    ["CLEAR_LOG", "Aktive Logdatei leeren.", "Logging"],
    ["DELETE_LOG_FILE", "Aktive Logdatei löschen und aus dem Context entfernen.", "Logging"],
    ["FILE.INCLUDE", "Script im aktuellen Context ausführen.", "Files"],
    ["FILE.RUN", "Script separat ausführen, optional mit Parameterobjekt.", "Files"],
    ["FILE.BACK", "Wert aus FILE.RUN zurückgeben.", "Files"],
    ["EXEC PATTERN", "Enterprise-Pattern ausführen: EXEC PATTERN loadPattern(\"museumqr\"); oder EXEC PATTERN $pattern IN $instance;", "Patterns"],
    ["EXECUTE PATTERN", "Alias/ausgeschriebene Schreibweise für EXEC PATTERN.", "Patterns"],
    ["IF", "Bedingten Block starten. Verschachtelte Blöcke werden unterstützt.", "Control"],
    ["ELSE", "Alternativblock für IF.", "Control"],
    ["FOR", "Schleife über Array oder Zähl-Loop.", "Control"],
    ["MAP_OBJECT", "Objekt/Array iterieren.", "Control"],
    ["BACK", "Wert aus Funktion oder Script zurückgeben.", "Control"],
    ["END_PROC", "Script erfolgreich an dieser Stelle stoppen.", "Control"],
    ["this_f.restart", "Aktuelle Funktion neu starten.", "Control"],
    ["CLASS", "Klasse definieren oder Methodenaufruf im Format CLASS Name/method(...).", "OOP"],
    ["C", "Kurzform für CLASS.", "OOP"],
    ["F", "Funktion/Methode definieren.", "OOP"],
    ["FUNCTION", "Alias/Highlighting für Funktionsdefinition.", "OOP"],
    ["CALL", "Funktion oder Klassenmethode aufrufen.", "OOP"],
    ["PUB", "Öffentlicher Klassen-/Member-Marker.", "OOP"],
    ["PRIV", "Privater Klassen-/Member-Marker.", "OOP"],
    ["BEGIN TRANSACTION", "Transaktion starten.", "Transactions"],
    ["COMMIT TRANSACTION", "Transaktion speichern.", "Transactions"],
    ["ROLLBACK TRANSACTION", "Transaktion verwerfen.", "Transactions"],
    ["SHOW TRANSACTION", "Transaktionsstatus anzeigen.", "Transactions"],
    ["SAVEPOINT", "Transaktions-Savepoint setzen.", "Transactions"],
    ["ROLLBACK TO", "Bis zu einem Savepoint zurückrollen.", "Transactions"],
    ["TX TIMEOUT", "Transaktions-Timeout setzen.", "Transactions"],
    ["QUERY OPTIONS", "Query-Optionen als JSON setzen.", "Queries"],
    ["PREPARE", "Prepared Query speichern.", "Queries"],
    ["EXECUTE", "Prepared Query mit optionalem WITH-JSON ausführen.", "Queries"],
    ["INNER JOIN", "Zwei Tabellen per Join verbinden.", "Queries"],
    ["LEFT JOIN", "Left Join zwischen zwei Tabellen.", "Queries"],
    ["USE INSTANCE", "GBDB-Instanz aktivieren.", "Instances"],
    ["ROOT INSTANCE", "GBDB-Instanz fokussieren.", "Instances"],
    ["SHOW INSTANCES", "Alle Instanzen mit Stats anzeigen.", "Instances"],
    ["GROW INSTANCE", "Instanz erstellen und aktivieren.", "Instances"],
    ["DROP INSTANCE", "Instanz löschen, optional FORCE.", "Instances"],
    ["ROOT", "Aktive Base setzen.", "Structure"],
    ["BRANCH", "Aktive Tabelle setzen.", "Structure"],
    ["SHOW BASES", "Bases anzeigen.", "Structure"],
    ["SHOW TABLES", "Tabellen anzeigen, optional IN base.", "Structure"],
    ["GROW BASE", "Base erstellen.", "Structure"],
    ["DROP BASE", "Base löschen.", "Structure"],
    ["GROW TABLE", "Tabelle erstellen: untyped oder typed mit uid:string, age:int, REQUIRED, UNIQUE, DEFAULT und optional WITHOUT TYPES.", "Structure"],
    ["EDIT TABLE ADD", "Spalte ergänzen. Alias/Legacy zu ALTER TABLE ADD.", "Structure"],
    ["ALTER TABLE ADD", "Spalte ergänzen, optional DEFAULT und IN base.", "Structure"],
    ["DROP TABLE", "Tabelle löschen.", "Structure"],
    ["RENAME", "Objekt umbenennen: RENAME INSTANCE|BASE|TABLE old INTO new.", "Structure"],
    ["DESCRIBE", "Tabellenstruktur anzeigen.", "Structure"],
    ["SHOW META", "Meta-Daten einer Tabelle anzeigen.", "Structure"],
    ["PICK", "Daten lesen: PICK * FROM users WHERE ... SORT ... LIMIT ... OFFSET ...", "CRUD"],
    ["EXPLAIN PICK", "PICK-Query erklären statt nur ausführen.", "CRUD"],
    ["SEED", "Datensatz einfügen. Runtime-Funktionen werden in Werten ausgewertet.", "CRUD"],
    ["RESHAPE", "Datensatz ändern: RESHAPE table WITH a=b WHERE id=...", "CRUD"],
    ["DELETE FROM", "Datensatz löschen.", "CRUD"],
    ["ERASE FROM", "Alias/Sprachvariante für DELETE FROM.", "CRUD"],
    ["DISTINCT", "Eindeutige Werte einer Spalte lesen.", "Aggregate"],
    ["COUNT", "Aggregation: COUNT(*) FROM table ...", "Aggregate"],
    ["SUM", "Aggregation: SUM(column) FROM table ...", "Aggregate"],
    ["AVG", "Aggregation: AVG(column) FROM table ...", "Aggregate"],
    ["MIN", "Aggregation: MIN(column) FROM table ...", "Aggregate"],
    ["MAX", "Aggregation: MAX(column) FROM table ... oder MAX(n) als Limit-Alternative.", "Aggregate"],
    ["GROUP BY", "Aggregation gruppieren.", "Aggregate"],
    ["HAVING", "Aggregation nach Gruppen filtern.", "Aggregate"],
    ["EXISTS INSTANCE", "Existenz einer Instanz prüfen.", "Exists"],
    ["EXISTS BASE", "Existenz einer Base prüfen.", "Exists"],
    ["EXISTS TABLE", "Existenz einer Tabelle prüfen.", "Exists"],
    ["EXISTS DATA", "Existenz eines Datensatzes prüfen.", "Exists"],
    ["PACK", "Tabelle kompaktieren.", "Maintenance"],
    ["PEEK", "Tabelle schnell inspizieren.", "Maintenance"],
    ["CHECK", "Tabellen-Health prüfen.", "Maintenance"],
    ["HEALTH", "Alias für CHECK.", "Maintenance"],
    ["REPAIR", "Tabelle reparieren.", "Maintenance"],
    ["SNAPSHOT", "Tabellen-Snapshot erstellen.", "Maintenance"],
    ["MIGRATE TO V2", "Migration nach GBDBv2 starten.", "Maintenance"],
    ["MIGRATE_TO_V2", "Alias für MIGRATE TO V2.", "Maintenance"],
    ["MONITOR", "Monitoringdaten lesen, optional für table/base.table.", "Advanced Engine"],
    ["RECOVER", "WAL-/Table-Recovery ausführen.", "Advanced Engine"],
    ["PAGE", "Paged Resultset laden.", "Advanced Engine"],
    ["CURSOR", "Cursor-Slice laden.", "Advanced Engine"],
    ["FULLTEXT", "Volltextsuche ausführen.", "Advanced Engine"],
    ["STATS", "Analyse/Stats für Tabelle anzeigen.", "Advanced Engine"],
    ["ANALYZE", "Analyse/Stats für Tabelle anzeigen.", "Advanced Engine"],
    ["SUGGEST INDEXES", "Index-Vorschläge erzeugen.", "Indexes"],
    ["INDEX SUGGESTIONS", "Alias für SUGGEST INDEXES.", "Indexes"],
    ["AUTO INDEX", "Indexe automatisch anhand Analyse anlegen.", "Indexes"],
    ["INDEX", "Index anlegen, optional PRIMARY/UNIQUE/COMPOSITE/SORTED/RANGE/PREFIX/FULLTEXT.", "Indexes"],
    ["CREATE INDEX ON", "Index anlegen.", "Indexes"],
    ["DROP INDEX ON", "Index löschen.", "Indexes"],
    ["UNINDEX", "Index löschen.", "Indexes"],
    ["REINDEX", "Indexe neu aufbauen.", "Indexes"],
    ["REPAIR INDEXES", "Indexe reparieren.", "Indexes"],
    ["SHOW INDEXES", "Indexe einer Tabelle anzeigen.", "Indexes"],
    ["ALTER TABLE ADD CONSTRAINT", "UNIQUE/REQUIRED Constraint hinzufügen.", "Constraints"],
    ["ALTER TABLE DROP CONSTRAINT", "UNIQUE/REQUIRED Constraint entfernen.", "Constraints"],
    ["ALTER TABLE ADD FOREIGN KEY", "Foreign-Key Relation hinzufügen.", "Relations"],
    ["SHOW CONSTRAINTS", "Constraints einer Tabelle anzeigen.", "Constraints"],
    ["SHOW RELATIONS", "Foreign-Key Relationen anzeigen.", "Relations"],
    ["CHECK RELATIONS", "Relationen prüfen.", "Relations"],
    ["REPAIR RELATIONS", "Relationen reparieren: MODE REPORT|SET_NULL|DELETE.", "Relations"],
    ["GRANT", "Recht/Rolle auf Tabelle vergeben.", "ACL"],
    ["REVOKE", "Recht/Rolle auf Tabelle entziehen.", "ACL"],
    ["PARTITION TABLE", "Tabelle partitionieren: BY DATE|USER|TENANT|HASH.", "Partitioning"],
    ["SHOW PARTITIONS", "Partitionen einer Tabelle anzeigen.", "Partitioning"],
    ["REPAIR PARTITIONS", "Partitionen reparieren.", "Partitioning"],
    ["BACKUP PARTITION", "Partition sichern.", "Partitioning"],
    ["SET PARTITION", "Partition auf READONLY/WRITABLE setzen.", "Partitioning"],
    ["REGISTER SHARD", "Shard registrieren, optional ROLE PRIMARY|REPLICA|WORKER.", "Sharding"],
    ["SHOW SHARDS", "Shards anzeigen.", "Sharding"],
    ["SHARD TABLE", "Tabelle sharden: BY HASH|USER|TENANT.", "Sharding"],
    ["SHARD HEALTH", "Shard-Health prüfen.", "Sharding"],
    ["CLUSTER HEALTH", "Cluster-Health prüfen.", "Sharding"],
    ["HEARTBEAT NODE", "Node-Heartbeat schreiben.", "Sharding"],
    ["BACKUP FULL", "Full Backup starten.", "Backup"],
    ["BACKUP SNAPSHOT", "Snapshot Backup starten.", "Backup"],
    ["BACKUP COLD", "Cold Backup starten.", "Backup"],
    ["BACKUP ENCRYPTED", "Encrypted Backup starten.", "Backup"],
    ["BACKUP TABLE", "Tabelle sichern.", "Backup"],
    ["BACKUP INSTANCE", "Instanz sichern.", "Backup"],
    ["BACKUP TENANT", "Tenant/Instanz sichern.", "Backup"],
    ["SRV/test_connection", "Lokalen SRV-Dienst testen.", "Bridge"],
    ["SecondServer/test_connection", "Remote SecondServer/SrvP-Verbindung testen.", "Bridge"]
];

const RUNTIME_FUNCTIONS = [
    [
        "param",
        "Runtime-Parameter aus dem Parameterobjekt lesen, z.B. param(\"museum_id\")."
    ],
    [
        "ENV",
        "Wert aus .config/.greenql.env.php lesen, z.B. ENV(\"api_auth\")."
    ],
    [
        "now",
        "Aktuellen Zeitwert als Y-m-d H:i:s erzeugen. NOW als Literal bleibt ebenfalls erlaubt."
    ],
    [
        "hash",
        "SHA-256 Hash oder Hash mit explizitem Algorithmus: hash(value) oder hash(\"sha512\", value)."
    ],
    [
        "fusion",
        "Beliebig viele Werte sauber als String zusammenfügen, inklusive bool/null/json."
    ],
    [
        "hash_sha512",
        "SHA-512 Hash."
    ],
    [
        "hash_md5",
        "MD5 Hash."
    ],
    [
        "hash_adler32",
        "Adler32 Hash."
    ],
    [
        "hash_crc32",
        "CRC32 Hash."
    ],
    [
        "hash_pass",
        "Framework-Passwort-Hash kompatibel zu Auth::hashPass()."
    ],
    [
        "len",
        "Länge von String/Array."
    ],
    [
        "uni_random",
        "Einmalige Random-ID."
    ],
    [
        "random_int",
        "Zufallszahl oder numerische Random-Zeichenfolge: random_int(length) oder random_int(min, max)."
    ],
    [
        "spark_id",
        "Alias für uni_random()."
    ],
    [
        "fresh_id",
        "Alias für uni_random()."
    ],
    [
        "uuid",
        "UUID erzeugen."
    ],
    [
        "loadPattern",
        "Enterprise-Pattern nach Name laden: loadPattern(\"museumqr\")."
    ],
    [
        "load_pattern",
        "Alias für loadPattern()."
    ],
    [
        "exec_pattern",
        "Enterprise-Pattern ausführen: exec_pattern(pattern) oder exec_pattern(pattern, instance)."
    ],
    [
        "execPattern",
        "Alias für exec_pattern()."
    ],
    [
        "fetch_api",
        "JSON API abrufen: fetch_api(url, bodyObj, headerObj)."
    ],
    [
        "api_fetch",
        "Alias für fetch_api()."
    ],
    [
        "call_api",
        "Alias für fetch_api()."
    ],
    [
        "get_instances",
        "Instanzen listen."
    ],
    [
        "instances",
        "Alias für get_instances()."
    ],
    [
        "get_bases",
        "Bases listen, optional get_bases(instance)."
    ],
    [
        "bases",
        "Alias für get_bases()."
    ],
    [
        "get_tables",
        "Tabellen listen, z.B. get_tables(base) oder get_tables(instance, base)."
    ],
    [
        "tables",
        "Alias für get_tables()."
    ],
    [
        "get_data",
        "Daten holen: get_data(base, table, filter) oder get_data(instance, base, table, filter)."
    ],
    [
        "fetch_data",
        "Alias für get_data()."
    ],
    [
        "fetch",
        "Alias für get_data()."
    ],
    [
        "add_data",
        "Datensatz einfügen."
    ],
    [
        "plant_data",
        "Alias für add_data()."
    ],
    [
        "seed_data",
        "Alias für add_data()."
    ],
    [
        "editdata",
        "Legacy-/Alias-Schreibweise für edit_data()."
    ],
    [
        "edit_data",
        "Datensatz ändern."
    ],
    [
        "reshape_data",
        "Alias für edit_data()."
    ],
    [
        "delete_data",
        "Datensatz löschen."
    ],
    [
        "erase_data",
        "Alias für delete_data()."
    ],
    [
        "delete_data_recursive",
        "Rekursiv/erweitert löschen."
    ],
    [
        "count_data",
        "Datensätze zählen."
    ],
    [
        "tally_data",
        "Alias für count_data()."
    ],
    [
        "last_added",
        "Letzten Datensatz lesen."
    ],
    [
        "last_data",
        "Alias für last_added()."
    ],
    [
        "new_column",
        "Spalte hinzufügen."
    ],
    [
        "sprout_column",
        "Alias für new_column()."
    ],
    [
        "delete_column",
        "Spalte löschen."
    ],
    [
        "prune_column",
        "Alias für delete_column()."
    ],
    [
        "delete_instance",
        "Instanz löschen."
    ],
    [
        "drop_instance",
        "Alias für delete_instance()."
    ],
    [
        "delete_base",
        "Base löschen."
    ],
    [
        "drop_base",
        "Alias für delete_base()."
    ],
    [
        "delete_table",
        "Tabelle löschen."
    ],
    [
        "drop_table",
        "Alias für delete_table()."
    ],
    [
        "rename_instance",
        "Instanz umbenennen/kopieren-löschen."
    ],
    [
        "rename_base",
        "Base umbenennen."
    ],
    [
        "rename_table",
        "Tabelle umbenennen."
    ],
    [
        "transfer_data",
        "Daten/Tabelle kopieren."
    ],
    [
        "copy_data",
        "Alias für transfer_data()."
    ],
    [
        "transfer_data_delete",
        "Kopieren und Quelle löschen."
    ],
    [
        "move_data",
        "Alias für transfer_data_delete()."
    ],
    [
        "set_data_readonly",
        "Readonly für Datensatz setzen."
    ],
    [
        "lock_data",
        "Alias für set_data_readonly()."
    ],
    [
        "instance_exists",
        "Instanzexistenz prüfen."
    ],
    [
        "base_exists",
        "Baseexistenz prüfen."
    ],
    [
        "table_exists",
        "Tabellenexistenz prüfen."
    ],
    [
        "data_exists",
        "Datenexistenz prüfen."
    ],
    [
        "monitor",
        "Monitoring als Runtime-Funktion."
    ],
    [
        "recover",
        "Recovery als Runtime-Funktion."
    ],
    [
        "page",
        "Page als Runtime-Funktion."
    ],
    [
        "cursor",
        "Cursor als Runtime-Funktion."
    ],
    [
        "fulltext_search",
        "Volltextsuche als Runtime-Funktion."
    ],
    [
        "enqueue_job",
        "GBDB Job enqueue."
    ],
    [
        "delayed_job",
        "Verzögerten Job anlegen."
    ],
    [
        "retry_failed_jobs",
        "Fehlgeschlagene Jobs erneut versuchen."
    ],
    [
        "queue_stats",
        "Queue-Statistiken."
    ],
    [
        "define_trigger",
        "Trigger definieren."
    ],
    [
        "define_view",
        "View definieren."
    ],
    [
        "refresh_view",
        "View aktualisieren."
    ],
    [
        "get_view",
        "View lesen."
    ],
    [
        "define_procedure",
        "Stored GreenQL Procedure definieren."
    ],
    [
        "call_procedure",
        "Procedure ausführen."
    ],
    [
        "define_db_user",
        "DB-User definieren."
    ],
    [
        "define_db_role",
        "DB-Rolle definieren."
    ],
    [
        "define_policy",
        "Policy definieren."
    ],
    [
        "evaluate_policy",
        "Policy auswerten."
    ],
    [
        "audit_export",
        "Audit exportieren."
    ],
    [
        "audit_search",
        "Audit durchsuchen."
    ],
    [
        "user_data_export",
        "DSGVO-Export."
    ],
    [
        "user_data_delete",
        "DSGVO-Löschung."
    ],
    [
        "user_data_redact",
        "DSGVO-Redaction."
    ],
    [
        "mark_pii_field",
        "PII-Feld markieren."
    ],
    [
        "encryption_config",
        "Encryption-Konfiguration."
    ],
    [
        "rotate_key",
        "Key Rotation."
    ],
    [
        "put_blob",
        "Blob speichern."
    ],
    [
        "signed_url",
        "Signierte URL erzeugen."
    ],
    [
        "delete_media",
        "Media löschen."
    ],
    [
        "install_social_patterns",
        "Social Patterns installieren."
    ],
    [
        "social_schema_patterns",
        "Social Schema Patterns."
    ],
    [
        "soft_delete",
        "Soft Delete per deleted_at/visibility."
    ]
];

const CLAUSE_WORDS = new Map(Object.entries({"FROM":"Quell-Tabelle", "WHERE":"Filterbedingung", "SORT":"Sortierung", "ASC":"Aufsteigend", "DESC":"Absteigend", "LIMIT":"Maximale Anzahl", "OFFSET":"Offset", "IN":"greenQL Clause-/Options-Keyword", "WITH":"Assignments/Parameter", "WITHOUT":"Option ohne Typprüfung", "TYPES":"Datentypen aktivieren/deaktivieren", "TYPE":"greenQL Clause-/Options-Keyword", "DEFAULT":"Default-Wert", "REQUIRED":"Required Constraint", "UNIQUE":"Unique Constraint/Index", "NULLABLE":"Nullable Constraint", "NOT":"greenQL Clause-/Options-Keyword", "NULL":"greenQL Clause-/Options-Keyword", "NOT_NULL":"greenQL Clause-/Options-Keyword", "AUTO_INCREMENT":"Auto-Increment", "AUTOINCREMENT":"Auto-Increment Alias", "GROUP":"GROUP BY Aggregation", "BY":"Strategie/Gruppierung", "HAVING":"Gruppenfilter", "ON":"Join/Relation/ACL Ziel", "REFERENCES":"Foreign-Key Referenz", "DELETE":"greenQL Clause-/Options-Keyword", "UPDATE":"greenQL Clause-/Options-Keyword", "CASCADE":"Cascade Aktion", "RESTRICT":"Restrict Aktion", "SET":"greenQL Clause-/Options-Keyword", "MODE":"greenQL Clause-/Options-Keyword", "REPORT":"greenQL Clause-/Options-Keyword", "SET_NULL":"Set Null Aktion", "BUCKETS":"Partition Buckets", "ROLE":"Rolle", "PRIMARY":"Primary Rolle/Index", "REPLICA":"Replica Rolle", "WORKER":"Worker Rolle", "READONLY":"Readonly", "READ_ONLY":"Readonly Alias", "WRITABLE":"Writable", "COLUMNS":"Spaltenliste", "SEARCH":"Suchtext", "AFTER":"Cursor nach", "SIZE":"Größe", "AS":"greenQL Clause-/Options-Keyword", "TO":"greenQL Clause-/Options-Keyword", "FORCE":"greenQL Clause-/Options-Keyword", "PATTERN":"Pattern-Kontext"}));

const PURPOSE = new Map();
PURPOSE.set("_...", "Normale Variable. Muss bei Deklaration und Aufruf mit _ beginnen.");
PURPOSE.set("$...", "Konstante. Wird wie Variable genutzt, darf aber nicht erneut überschrieben werden.");
for (const [word, detail, group] of COMMAND_SPECS) PURPOSE.set(word, `${detail}\n\nKategorie: ${group}`);
for (const [word, detail] of RUNTIME_FUNCTIONS) PURPOSE.set(word, detail);
for (const [word, detail] of CLAUSE_WORDS) PURPOSE.set(word, detail);

const COMMAND_WORDS = new Set(COMMAND_SPECS.flatMap(([cmd]) => cmd.split(/\s+/)).map(w => w.toUpperCase()).concat(["FILE", "INCLUDE", "RUN", "BACK", "PUBLIC", "PRIVATE", "PROTECTED", "STATIC", "RENAME", "INTO"]));
const TYPE_WORDS = new Set(["ANY", "ARR", "ARRAY", "BLOB", "BLOB_REFERENCE", "BOOL", "BOOLEAN", "COLD", "COLUMN", "CONSTRAINT", "CONSTRAINTS", "DATE", "DATETIME", "DATETYPE", "DECIMAL", "DOUBLE", "EMAIL", "ENCRYPTED", "ENUM", "FLOAT", "FOREIGN", "FULL", "INDEXES", "INSTANCE", "INSTANCES", "INT", "INTEGER", "JSON", "KEY", "MAP", "MIXED", "NODE", "NUMBER", "OBJ", "OBJECT", "PARTITIONS", "RELATIONS", "SHARDS", "SNAPSHOT", "STR", "STRING", "TABLE", "TABLES", "TENANT", "TEXT", "TIME", "TIMESTAMP", "TIMETYPE", "TRANSACTION", "ULID", "URL", "UUID", "VAR"]);
const VARIABLE_TYPE_WORDS = new Set(["ANY", "ARR", "ARRAY", "BLOB", "BLOB_REFERENCE", "BOOL", "BOOLEAN", "DATE", "DATETIME", "DATETYPE", "DECIMAL", "DOUBLE", "EMAIL", "ENUM", "FLOAT", "INT", "INTEGER", "JSON", "MAP", "MIXED", "NUMBER", "OBJ", "OBJECT", "STR", "STRING", "TEXT", "TIME", "TIMESTAMP", "TIMETYPE", "ULID", "URL", "UUID", "VAR"]);
for (const type of VARIABLE_TYPE_WORDS) PURPOSE.set(type, `Optionaler Variablen-Datentyp für DECLARE, z.B. declare :${type.toLowerCase()} _value = ...;`);
const DANGER_WORDS = new Set(["ERASE", "DELETE", "DROP", "UNINDEX", "REVOKE", "ROLLBACK", "REPAIR", "RECOVER"]);
const CONTROL_WORDS = new Set(["IF", "ELSE", "FOR", "MAP_OBJECT", "BACK", "END_PROC"]);
const DECLARATION_WORDS = new Set(["DECLARE", "DECALRE", "DELACE", "CLASS", "C", "F", "FUNCTION", "PUB", "PRIV", "PUBLIC", "PRIVATE", "PROTECTED", "STATIC", "RENAME", "INTO"]);
const LITERAL_WORDS = new Set(["TRUE", "FALSE", "NULL", "NOW"]);
const RUNTIME_FUNCTION_SET = new Set(RUNTIME_FUNCTIONS.map(([name]) => name.toLowerCase()));
const VALUE_WORDS = new Set([...CLAUSE_WORDS.keys()].map(w => w.toLowerCase()).concat(["this", "file"]));


const RUNTIME_CATEGORY_SETS = {
    identity: new Set(["now", "param", "env", "len", "fusion", "uuid", "uni_random", "spark_id", "fresh_id", "random_int"]),
    text: new Set(["fusion"]),
    hash: new Set(["hash", "hash_sha256", "hash_sha512", "hash_md5", "hash_adler32", "hash_crc32", "hash_pass"]),
    pattern: new Set(["loadpattern", "loadpattern", "load_pattern", "execpattern", "execpattern", "exec_pattern"]),
    data: new Set(["get_instances", "instances", "get_bases", "bases", "get_tables", "tables", "instance_exists", "base_exists", "table_exists", "data_exists", "get_data", "fetch_data", "fetch", "count_data", "tally_data", "last_added", "last_data", "add_data", "plant_data", "seed_data", "edit_data", "editdata", "reshape_data", "delete_data", "erase_data", "delete_data_recursive", "transfer_data", "copy_data", "transfer_data_delete", "move_data", "set_data_readonly", "lock_data"]),
    schema: new Set(["new_column", "sprout_column", "delete_column", "prune_column", "delete_instance", "drop_instance", "delete_base", "drop_base", "delete_table", "drop_table", "rename_instance", "rename_base", "rename_table"]),
    advanced: new Set(["fetch_api", "api_fetch", "call_api", "monitor", "recover", "page", "cursor", "fulltext_search", "enqueue_job", "delayed_job", "retry_failed_jobs", "queue_stats", "define_trigger", "define_view", "refresh_view", "get_view", "define_procedure", "call_procedure", "define_db_user", "define_db_role", "define_policy", "evaluate_policy", "audit_export", "audit_search", "user_data_export", "user_data_delete", "user_data_redact", "mark_pii_field", "encryption_config", "rotate_key", "put_blob", "signed_url", "delete_media", "install_social_patterns", "social_schema_patterns", "soft_delete"])
};

function runtimeModifierFor(name) {
    const lower = String(name || "").toLowerCase();
    for (const [modifier, set] of Object.entries(RUNTIME_CATEGORY_SETS)) {
        if (set.has(lower)) return TOKEN_MODIFIER.runtime | TOKEN_MODIFIER[modifier];
    }
    return TOKEN_MODIFIER.runtime;
}

const SEMANTIC_TOKEN_TYPES = ["keyword", "variable", "parameter", "function", "method", "property", "string", "number", "comment", "class", "type", "namespace", "operator", "macro", "enumMember"];
const SEMANTIC_TOKEN_MODIFIERS = ["declaration", "readonly", "control", "danger", "runtime", "identity", "hash", "pattern", "data", "schema", "advanced", "user"];
const SEMANTIC_LEGEND = new vscode.SemanticTokensLegend(SEMANTIC_TOKEN_TYPES, SEMANTIC_TOKEN_MODIFIERS);
const TOKEN_TYPE = Object.fromEntries(SEMANTIC_TOKEN_TYPES.map((name, index) => [name, index]));
const TOKEN_MODIFIER = Object.fromEntries(SEMANTIC_TOKEN_MODIFIERS.map((name, index) => [name, 1 << index]));

function activate(context) {
    const diagnostics = vscode.languages.createDiagnosticCollection("greenql");
    context.subscriptions.push(diagnostics);

    context.subscriptions.push(vscode.languages.registerDocumentSemanticTokensProvider("greenql", {
        provideDocumentSemanticTokens: provideSemanticTokens
    }, SEMANTIC_LEGEND));

    context.subscriptions.push(vscode.languages.registerCompletionItemProvider("greenql", {
        provideCompletionItems(document, position) {
            const before = document.lineAt(position).text.substring(0, position.character);
            const items = [];
            addContextSnippets(items, before);
            addCompletions(items);
            return items;
        }
    }, " ", ";", "(", "[", ".", "/", "$", "_"));

    context.subscriptions.push(vscode.languages.registerHoverProvider("greenql", { provideHover }));
    context.subscriptions.push(vscode.languages.registerDocumentSymbolProvider("greenql", { provideDocumentSymbols }));
    context.subscriptions.push(vscode.languages.registerFoldingRangeProvider("greenql", { provideFoldingRanges }));
    context.subscriptions.push(vscode.languages.registerSignatureHelpProvider("greenql", { provideSignatureHelp }, "(", ","));
    context.subscriptions.push(vscode.languages.registerDocumentFormattingEditProvider("greenql", { provideDocumentFormattingEdits }));
    context.subscriptions.push(vscode.languages.registerOnTypeFormattingEditProvider("greenql", { provideOnTypeFormattingEdits }, ";", "}"));
    context.subscriptions.push(vscode.languages.registerCodeActionsProvider("greenql", { provideCodeActions }, { providedCodeActionKinds: [vscode.CodeActionKind.QuickFix, vscode.CodeActionKind.RefactorRewrite] }));

    context.subscriptions.push(vscode.commands.registerCommand("greenql.insertMegaDemo", () => insertMegaDemo()));
    context.subscriptions.push(vscode.commands.registerCommand("greenql.showBuiltins", () => showBuiltinsCheatSheet()));
    context.subscriptions.push(vscode.commands.registerCommand("greenql.wrapSelectionOutput", () => wrapSelection("OUTPUT")));
    context.subscriptions.push(vscode.commands.registerCommand("greenql.wrapSelectionLog", () => wrapSelection("LOG")));

    const runValidation = document => validateDocument(document, diagnostics);
    if (vscode.window.activeTextEditor) runValidation(vscode.window.activeTextEditor.document);
    context.subscriptions.push(vscode.workspace.onDidOpenTextDocument(runValidation));
    context.subscriptions.push(vscode.workspace.onDidSaveTextDocument(runValidation));
    context.subscriptions.push(vscode.workspace.onDidChangeTextDocument(event => runValidation(event.document)));
    context.subscriptions.push(vscode.window.onDidChangeActiveTextEditor(editor => { if (editor) runValidation(editor.document); }));
}

function addCompletions(items) {
    const seen = new Set();
    for (const [word, detail, group] of COMMAND_SPECS) {
        if (seen.has(word)) continue; seen.add(word);
        const item = new vscode.CompletionItem(word, vscode.CompletionItemKind.Keyword);
        item.detail = group;
        item.documentation = new vscode.MarkdownString(`**${word}**\n\n${detail}`);
        items.push(item);
    }
    for (const [word, detail] of RUNTIME_FUNCTIONS) {
        const item = new vscode.CompletionItem(word, vscode.CompletionItemKind.Function);
        item.detail = "Runtime function";
        item.documentation = new vscode.MarkdownString(`**${word}()**\n\n${detail}`);
        item.insertText = new vscode.SnippetString(`${word}(${runtimePlaceholder(word)})`);
        items.push(item);
    }
    for (const [word, detail] of CLAUSE_WORDS) {
        const item = new vscode.CompletionItem(word, vscode.CompletionItemKind.Keyword);
        item.detail = "Clause / literal";
        item.documentation = detail;
        items.push(item);
    }
}

function runtimePlaceholder(word) {
    const lower = word.toLowerCase();
    if (["uni_random", "spark_id", "fresh_id", "uuid", "now"].includes(lower)) return "";
    if (lower === "param" || lower === "env") return "\"${1:key}\"";
    if (["loadpattern", "load_pattern"].includes(lower)) return "\"${1:museumqr}\"";
    if (["exec_pattern", "execpattern"].includes(lower)) return "${1:pattern}, ${2:instance}";
    if (lower === "hash") return "${1:value}";
    if (lower.startsWith("hash_")) return "${1:value}";
    if (["fetch_api", "api_fetch", "call_api"].includes(lower)) return "${1:url}, ${2:body}, ${3:headers}";
    if (["get_bases", "bases"].includes(lower)) return "${1:instance}";
    if (["get_tables", "tables"].includes(lower)) return "${1:base}";
    if (["get_data", "fetch_data", "fetch"].includes(lower)) return "${1:base}, ${2:table}, ${3:filter}";
    if (["add_data", "plant_data", "seed_data"].includes(lower)) return "${1:base}, ${2:table}, ${3:data}";
    return "${1:args}";
}

function addContextSnippets(items, before) {
    const sn = (label, body, detail) => {
        const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Snippet);
        item.insertText = new vscode.SnippetString(body);
        item.detail = detail;
        item.documentation = detail;
        items.push(item);
    };
    if (/\b(?:DECLARE|DECALRE|DELACE)\s+$/i.test(before)) {
        sn("_variable", "_${1:name} = ${2:value};", "Variable deklarieren");
        sn("$CONSTANT", "\\$${1:NAME} = ${2:value};", "Konstante deklarieren");
    }
    if (/\bFILE\.$/i.test(before)) {
        sn("INCLUDE", "INCLUDE \"${1:scripts/include.gql}\";", "Script einbinden");
        sn("RUN", "RUN \"${1:scripts/job.gql}\" {\"${2:key}\": ${3:_value}};", "Script separat ausführen");
        sn("BACK", "BACK ${1:_value};", "FILE.RUN Rückgabe");
    }
    if (/\bEXEC(?:UTE)?\s+PATTERN\s+$/i.test(before)) {
        sn("loadPattern", "loadPattern(\"${1:museumqr}\");", "Pattern direkt laden und ausführen");
        sn("$pattern", "\\$${1:pattern};", "Geladenes Pattern aus Variable ausführen");
    }
    if (/^\s*$/i.test(before)) {
        sn("MuseumQR Pattern Bootstrap", "declare \\$mid = param(\"museum_id\");\ndeclare \\$instance = param(\"main_instance\");\ndeclare \\$pattern = loadPattern(\"museumqr\");\n\nif (!instance_exists(\\$instance)) {\n    GROW INSTANCE \\$instance;\n}\n\nUSE INSTANCE \\$instance;\nEXEC PATTERN \\$pattern;", "MuseumQR Pattern-Bootstrap");
    }
    if (/\b(?:GROW|DROP|SHOW|USE|ROOT|EXISTS)\s+$/i.test(before)) {
        sn("INSTANCE", "INSTANCE ${1:main};", "Instance-Kontext");
        sn("BASE", "BASE ${1:main};", "Base-Kontext");
        sn("TABLE", "TABLE ${1:users};", "Table-Kontext");
        sn("DATA", "DATA {\"${1:id}\": ${2:_id}} IN ${3:users};", "Data Exists-Kontext");
    }
    if (/\b(?:MONITOR|RECOVER|PAGE|CURSOR|FULLTEXT|STATS|ANALYZE|AUTO\s+INDEX|SUGGEST\s+INDEXES)\s*$/i.test(before)) {
        sn("main.users", "${1:main}.${2:users};", "Qualifizierter Tabellenbezug");
    }
}

function provideHover(document, position) {
    const range = document.getWordRangeAtPosition(position, /[A-Za-z_.$][A-Za-z0-9_.$]*(?:\s+[A-Za-z_][A-Za-z0-9_]*)?/);
    if (!range) return;
    const raw = document.getText(range).trim();
    const exact = PURPOSE.get(raw) || PURPOSE.get(raw.toUpperCase()) || PURPOSE.get(raw.toLowerCase());
    if (exact) return new vscode.Hover(new vscode.MarkdownString(`**greenQL: ${raw}**\n\n${exact}`), range);
    const wordRange = document.getWordRangeAtPosition(position, /[A-Za-z_.$][A-Za-z0-9_.$]*/);
    if (!wordRange) return;
    const word = document.getText(wordRange);
    const detail = PURPOSE.get(word) || PURPOSE.get(word.toUpperCase()) || PURPOSE.get(word.toLowerCase());
    if (!detail) return;
    return new vscode.Hover(new vscode.MarkdownString(`**greenQL: ${word}**\n\n${detail}`), wordRange);
}

function provideSemanticTokens(document) {
    const builder = new vscode.SemanticTokensBuilder(SEMANTIC_LEGEND);
    for (let line = 0; line < document.lineCount; line++) tokenizeSemanticLine(document.lineAt(line).text, line, builder);
    return builder.build();
}

function tokenizeSemanticLine(text, line, builder) {
    let i = 0;
    const push = (start, len, type, modifier = 0) => { if (len > 0 && TOKEN_TYPE[type] !== undefined) builder.push(line, start, len, TOKEN_TYPE[type], modifier); };
    while (i < text.length) {
        const ch = text[i], next = text[i + 1];
        if (ch === "#" || (ch === "/" && next === "/") || (ch === "-" && next === "-")) { push(i, text.length - i, "comment"); return; }
        if (ch === '"' || ch === "'" || ch === "`") {
            const quote = ch, start = i; i++; let escaped = false;
            while (i < text.length) { const c = text[i]; if (escaped) escaped = false; else if (c === "\\") escaped = true; else if (c === quote) { i++; break; } i++; }
            const after = text.slice(i).trimStart();
            if (after.startsWith(":")) push(start + 1, Math.max(0, i - start - 2), "property"); else push(start, i - start, "string");
            continue;
        }
        if (/[0-9]/.test(ch)) { const start = i; i++; while (i < text.length && /[0-9A-Fa-f.x_]/.test(text[i])) i++; push(start, i - start, "number"); continue; }
        if (ch === "$") { const start = i; i++; while (i < text.length && /[A-Za-z0-9_]/.test(text[i])) i++; push(start, i - start, "variable", TOKEN_MODIFIER.readonly); continue; }
        if (/[A-Za-z_]/.test(ch)) {
            const start = i; i++; while (i < text.length && /[A-Za-z0-9_]/.test(text[i])) i++;
            const word = text.slice(start, i), upper = word.toUpperCase(), lower = word.toLowerCase();
            const rest = text.slice(i), nextNonSpace = (rest.match(/^\s*(.)/) || [])[1] || "";
            const prev = text.slice(0, start).trimEnd(), nextTrim = rest.trimStart();
            const slashCall = /^(srv|SRV|Srv|secondserver|Secondserver|SecondServer)$/i.test(word) && /^\s*\/\s*[A-Za-z_][A-Za-z0-9_]*\s*\(/.test(rest);
            if (slashCall) push(start, i - start, "namespace");
            else if (word.startsWith("_")) push(start, i - start, "variable");
            else if (/\b(F|FUNCTION)\s+$/i.test(prev)) push(start, i - start, "function", TOKEN_MODIFIER.declaration);
            else if (/\b(C|CLASS)\s+$/i.test(prev)) push(start, i - start, "class");
            else if (/\b(?:srv|SRV|Srv|secondserver|Secondserver|SecondServer)\s*\/\s*$/i.test(prev)) push(start, i - start, "method", TOKEN_MODIFIER.runtime | TOKEN_MODIFIER.advanced);
            else if (RUNTIME_FUNCTION_SET.has(lower) && nextNonSpace === "(") push(start, i - start, "function", runtimeModifierFor(word));
            else if (nextNonSpace === "(" && !COMMAND_WORDS.has(upper) && !CLAUSE_WORDS.has(upper)) push(start, i - start, "function", TOKEN_MODIFIER.user);
            else if (DECLARATION_WORDS.has(upper)) push(start, i - start, "keyword", TOKEN_MODIFIER.declaration);
            else if (CONTROL_WORDS.has(upper)) push(start, i - start, "keyword", TOKEN_MODIFIER.control);
            else if (DANGER_WORDS.has(upper)) push(start, i - start, "keyword", TOKEN_MODIFIER.danger);
            else if (LITERAL_WORDS.has(upper)) push(start, i - start, "macro");
            else if (VARIABLE_TYPE_WORDS.has(upper)) push(start, i - start, "type");
            else if (COMMAND_WORDS.has(upper) || CLAUSE_WORDS.has(upper)) push(start, i - start, "keyword");
            else if (TYPE_WORDS.has(upper)) push(start, i - start, "namespace");
            else if (nextTrim.startsWith(":")) push(start, i - start, "property");
            else if (/\.$/.test(prev) || /\bthis\.$/i.test(prev)) push(start, i - start, "method");
            else if (/\b(?:INSTANCE|BASE|TABLE|FROM|IN|ROOT|BRANCH|DESCRIBE|PACK|PEEK|SEED|RESHAPE|JOIN|PARTITION|SHARD|BACKUP|PATTERN)\s+$/i.test(prev)) push(start, i - start, "namespace");
            else push(start, i - start, "property");
            continue;
        }
        if (/[=<>!~+\-*/%|&.]/.test(ch)) push(i, 1, "operator");
        i++;
    }
}

function provideDocumentSymbols(document) {
    const symbols = [];
    for (let line = 0; line < document.lineCount; line++) {
        const text = stripStringsAndComments(document.lineAt(line).text).trim();
        let m;
        if ((m = text.match(/^(?:C|CLASS)\s+([A-Za-z_][A-Za-z0-9_]*)/i))) symbols.push(symbol(line, m[1], "greenQL class", vscode.SymbolKind.Class));
        if ((m = text.match(/^F\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/i))) symbols.push(symbol(line, m[1], "greenQL function", vscode.SymbolKind.Function));
        if ((m = text.match(/^(?:DECLARE|DECALRE|DELACE)\s+(?::([A-Za-z_][A-Za-z0-9_]*)\s+)?([$]?[A-Za-z_][A-Za-z0-9_]*)/i))) symbols.push(symbol(line, m[2], m[1] ? `greenQL variable :${m[1]}` : "greenQL variable", vscode.SymbolKind.Variable));
        if ((m = text.match(/^META\s*=/i))) symbols.push(symbol(line, "META", "greenQL meta", vscode.SymbolKind.Object));
        if ((m = text.match(/^ROOT\s+([A-Za-z0-9_\-]+)/i))) symbols.push(symbol(line, `ROOT ${m[1]}`, "Base focus", vscode.SymbolKind.Namespace));
        if ((m = text.match(/^BRANCH\s+([A-Za-z0-9_\-]+)/i))) symbols.push(symbol(line, `BRANCH ${m[1]}`, "Table focus", vscode.SymbolKind.Namespace));
    }
    return symbols;
}

function symbol(line, name, detail, kind) {
    const range = new vscode.Range(line, 0, line, 200);
    return new vscode.DocumentSymbol(name, detail, kind, range, range);
}

function provideFoldingRanges(document) {
    const ranges = [];
    const stack = [];
    for (let line = 0; line < document.lineCount; line++) {
        const text = stripStringsAndComments(document.lineAt(line).text);
        for (const ch of text) {
            if (ch === "{") stack.push(line);
            if (ch === "}" && stack.length) {
                const start = stack.pop();
                if (line > start) ranges.push(new vscode.FoldingRange(start, line));
            }
        }
    }
    return ranges;
}

function validateDocument(document, diagnostics) {
    if (document.languageId !== "greenql") return;
    const items = [], constants = new Map();
    const lines = document.getText().split(/\r?\n/);
    const braces = [];
    for (let lineIndex = 0; lineIndex < lines.length; lineIndex++) {
        const raw = lines[lineIndex];
        const line = stripStringsAndComments(raw);
        findDeclarationIssues(line, lineIndex, items, constants);
        findFunctionParamIssues(line, lineIndex, items);
        // 2.1.2: Keine aggressiven "nackter Wert"-Warnings mehr.
        // greenQL nutzt bewusst Tabellen-/Spaltennamen, SRV-Slash-Calls und unquoted Identifier.
        // Die alte Prüfung hat dadurch gültige Syntax wie srv/listInstances(), INDEX users username,
        // SEED key=value und WITH TYPES fälschlich unterkringelt.
        findSuspiciousCallIssues(line, lineIndex, items);
        trackBraces(line, lineIndex, braces, items);
        findLikelyCommandIssues(line, lineIndex, items);
    }
    for (const b of braces) pushIssue(items, b.line, b.ch, 1, "Block wurde geöffnet, aber nicht geschlossen.", vscode.DiagnosticSeverity.Warning);
    diagnostics.set(document.uri, items);
}

function stripStringsAndComments(line) {
    let out = "", quote = null, escaped = false;
    for (let i = 0; i < line.length; i++) {
        const ch = line[i], next = line[i + 1];
        if (!quote && ch === "#") return out + " ".repeat(line.length - out.length);
        if (!quote && ch === "/" && next === "/") return out + " ".repeat(line.length - out.length);
        // SQL-Style -- Kommentare nur am Zeilenanfang oder nach Whitespace erkennen.
        // Wichtig: greenQL nutzt _i-- in FOR-Loops; das darf nicht als Kommentar abgeschnitten werden.
        if (!quote && ch === "-" && next === "-" && (i === 0 || /\s/.test(line[i - 1]))) return out + " ".repeat(line.length - out.length);
        if (quote) { out += " "; if (escaped) escaped = false; else if (ch === "\\") escaped = true; else if (ch === quote) quote = null; continue; }
        if (ch === '"' || ch === "'" || ch === "`") { quote = ch; out += " "; continue; }
        out += ch;
    }
    return out;
}

function findDeclarationIssues(line, lineIndex, items, constants) {
    const regex = /\b(?:DECLARE|DECALRE|DELACE)\s+(?::([A-Za-z_][A-Za-z0-9_]*)\s+)?([^\s=;]+)/gi;
    let match;
    while ((match = regex.exec(line))) {
        const type = match[1] || "";
        const name = match[2], start = match.index + match[0].lastIndexOf(name);
        if (type && !VARIABLE_TYPE_WORDS.has(type.toUpperCase())) {
            const typeStart = match.index + match[0].indexOf(type);
            pushIssue(items, lineIndex, typeStart, type.length, `Unbekannter Variablen-Datentyp :${type}. Erlaubt sind int, string, float, bool, array, json und mixed.`, vscode.DiagnosticSeverity.Warning);
        }
        if (name.startsWith("$")) {
            const key = name.toLowerCase();
            if (constants.has(key)) pushIssue(items, lineIndex, start, name.length, `Konstante ${name} wurde bereits deklariert und sollte nicht überschrieben werden.`, vscode.DiagnosticSeverity.Warning);
            constants.set(key, true);
        } else if (!/^_[A-Za-z][A-Za-z0-9_]*$/.test(name)) {
            pushIssue(items, lineIndex, start, name.length, "greenQL Variablen müssen bei Deklaration mit _ beginnen. Konstanten beginnen mit $.");
        }
    }
}

function findFunctionParamIssues(line, lineIndex, items) {
    const regex = /\b(?:F|FUNCTION)\s+[A-Za-z_][A-Za-z0-9_]*\s*\(([^)]*)\)/gi;
    let match;
    while ((match = regex.exec(line))) {
        const inner = match[1], offset = match.index + match[0].indexOf(inner);
        let searchFrom = 0;
        for (const rawParam of splitArgsLight(inner)) {
            const raw = rawParam.trim();
            if (!raw) continue;
            const rel = inner.indexOf(rawParam, searchFrom);
            searchFrom = rel + rawParam.length;

            // Erlaubt:
            // _name
            // ?_name:string
            // ?_id:int = 0
            // ?$id:int = 0
            // $CONST:string
            const beforeDefault = raw.replace(/=.*/, "").trim();
            const paramMatch = beforeDefault.match(/^\??\s*([$]?[A-Za-z_][A-Za-z0-9_]*)(?:\s*:\s*([A-Za-z_][A-Za-z0-9_]*))?$/);
            if (!paramMatch) {
                pushIssue(items, lineIndex, offset + Math.max(0, rel), raw.length, "Parameter-Syntax prüfen. Erlaubt: _name, ?_name:string, ?_id:int = 0 oder ?$id:int = 0.", vscode.DiagnosticSeverity.Warning);
                continue;
            }

            const name = paramMatch[1];
            const type = paramMatch[2] || "";
            if (type && !VARIABLE_TYPE_WORDS.has(type.toUpperCase())) {
                const typeStart = raw.indexOf(type);
                pushIssue(items, lineIndex, offset + Math.max(0, rel) + typeStart, type.length, `Unbekannter Parameter-Datentyp :${type}.`, vscode.DiagnosticSeverity.Warning);
            }
            if (!name.startsWith("_") && !name.startsWith("$")) {
                const nameStart = raw.indexOf(name);
                pushIssue(items, lineIndex, offset + Math.max(0, rel) + nameStart, name.length, "Funktionsparameter müssen mit _ beginnen oder als $Konstante gelesen werden. Optional: ?_name:string = default.", vscode.DiagnosticSeverity.Warning);
            }
        }
    }
}

function findBareFunctionVariableIssues(line, lineIndex, items) {
    // Absichtlich deaktiviert.
    // greenQL hat viele legitime nackte Identifier:
    // - srv/listInstances()
    // - INDEX users username
    // - GROW TABLE users WITH uid:string ... WITH TYPES
    // - SEED users WITH username="admin"
    // Eine pauschale RHS-Identifier-Warnung erzeugt hier mehr Fake-Fehler als Nutzen.
}

function findSuspiciousCallIssues(line, lineIndex, items) {
    const runtimeNoArg = ["uni_random", "spark_id", "fresh_id", "uuid"];
    for (const fn of runtimeNoArg) {
        const re = new RegExp(`\\b${fn}\\b(?!\\s*\\()`, "i");
        const m = line.match(re);
        if (m) pushIssue(items, lineIndex, m.index, m[0].length, `${fn} ist eine Runtime-Funktion. Nutze ${fn}().`, vscode.DiagnosticSeverity.Warning);
    }
    if (/\bFILE\.RUN\b/i.test(line) && /\bWITH\b/i.test(line)) {
        const idx = line.toUpperCase().indexOf("WITH");
        pushIssue(items, lineIndex, idx, 4, "Hinweis: Der aktuelle Parser erwartet bei FILE.RUN direkt ein Objekt/Array nach dem Pfad; WITH wird von manchen UI-Snippets nur als Schreibstil genutzt.", vscode.DiagnosticSeverity.Information);
    }
}

function trackBraces(line, lineIndex, stack, items) {
    for (let i = 0; i < line.length; i++) {
        if (line[i] === "{") stack.push({ line: lineIndex, ch: i });
        if (line[i] === "}") {
            if (!stack.length) pushIssue(items, lineIndex, i, 1, "Schließende Klammer ohne passende öffnende Klammer.", vscode.DiagnosticSeverity.Warning);
            else stack.pop();
        }
    }
}

function findLikelyCommandIssues(line, lineIndex, items) {
    const t = line.trim();
    if (!t || /^[#}\]]/.test(t)) return;
    if (!/^[A-Z_]+\b/.test(t)) return;
    const allowed = /^(META|DECLARE|DECALRE|DELACE|OUTPUT|MSG|ERROR|SET_LOGFILE|LOG|CLEAR_LOG|DELETE_LOG_FILE|FILE\.|IF|ELSE|FOR|MAP_OBJECT|BACK|END_PROC|CLASS|C\b|F\b|FUNCTION|CALL|PUB|PRIV|PUBLIC|PRIVATE|PROTECTED|STATIC|BEGIN|COMMIT|ROLLBACK|SAVEPOINT|TX|QUERY|PREPARE|EXECUTE|INNER|LEFT|MIGRATE|MIGRATE_TO_V2|EXEC|SHOW|EXISTS|USE|ROOT|BRANCH|GROW|DROP|EDIT|ALTER|RENAME|PARTITION|REGISTER|SHARD|CLUSTER|HEARTBEAT|BACKUP|INDEX|CREATE|UNINDEX|REINDEX|REPAIR|CHECK|HEALTH|SNAPSHOT|STATS|ANALYZE|SUGGEST|AUTO|MONITOR|RECOVER|PAGE|CURSOR|FULLTEXT|GRANT|REVOKE|DESCRIBE|PACK|PEEK|DISTINCT|COUNT|SUM|AVG|MIN|MAX|PICK|EXPLAIN|SEED|RESHAPE|DELETE|ERASE|WITH|WHERE|SORT|ORDER|GROUP|HAVING|LIMIT|OFFSET|DEFAULT|REQUIRED|UNIQUE|COMPOSITE|SORTED|RANGE|PREFIX|PRIMARY|SET)\b/i;
    if (!allowed.test(t)) {
        pushIssue(items, lineIndex, 0, Math.min(t.length, 30), "Unbekannter oder nicht abgedeckter greenQL-Befehl. Syntax/Keyword prüfen.", vscode.DiagnosticSeverity.Information);
    }
}

function pushIssue(items, line, start, len, message, severity = vscode.DiagnosticSeverity.Error) {
    items.push(new vscode.Diagnostic(new vscode.Range(line, Math.max(0, start), line, Math.max(0, start + len)), message, severity));
}


function splitArgsLight(raw) {
    const out = [];
    let current = "", quote = "", escaped = false, round = 0, square = 0, curly = 0;
    for (let i = 0; i < raw.length; i++) {
        const ch = raw[i];
        if (quote) {
            current += ch;
            if (escaped) escaped = false;
            else if (ch === "\\") escaped = true;
            else if (ch === quote) quote = "";
            continue;
        }
        if (ch === '"' || ch === "'" || ch === "`") { quote = ch; current += ch; continue; }
        if (ch === "(") round++;
        if (ch === ")") round = Math.max(0, round - 1);
        if (ch === "[") square++;
        if (ch === "]") square = Math.max(0, square - 1);
        if (ch === "{") curly++;
        if (ch === "}") curly = Math.max(0, curly - 1);
        if (ch === "," && round === 0 && square === 0 && curly === 0) { out.push(current); current = ""; continue; }
        current += ch;
    }
    if (current.trim() !== "") out.push(current);
    return out;
}

function provideSignatureHelp(document, position) {
    const line = document.lineAt(position).text.substring(0, position.character);
    const match = line.match(/([A-Za-z_][A-Za-z0-9_]*)\s*\(([^()]*)$/);
    if (!match) return null;
    const name = match[1];
    const sig = signatureForRuntime(name);
    if (!sig) return null;
    const help = new vscode.SignatureHelp();
    const info = new vscode.SignatureInformation(sig.label, sig.documentation);
    for (const p of sig.params) info.parameters.push(new vscode.ParameterInformation(p));
    help.signatures = [info];
    help.activeSignature = 0;
    help.activeParameter = Math.max(0, splitArgsLight(match[2]).length - 1);
    return help;
}

function signatureForRuntime(name) {
    const lower = String(name || "").toLowerCase();
    const docs = {
        param: ["param(key:string)", "Runtime-Parameter aus dem Script-Parameterobjekt lesen.", ["key:string"]],
        env: ["ENV(key:string)", "Wert aus .config/.greenql.env.php lesen.", ["key:string"]],
        now: ["now()", "Aktuellen Zeitwert als Y-m-d H:i:s erzeugen.", []],
        fusion: ["fusion(value:mixed, ...values:mixed)", "Beliebig viele Werte sauber als String zusammenfügen, inklusive bool/null/json.", ["value:mixed", "...values:mixed"]],
        random_int: ["random_int(length:int = 1) | random_int(min:int, max:int)", "Zufallszahl oder Zufallsziffernfolge erzeugen.", ["length:int", "min:int", "max:int"]],
        loadpattern: ["loadPattern(name:string)", "Enterprise-Pattern anhand Dateiname ohne .json laden.", ["name:string"]],
        load_pattern: ["load_pattern(name:string)", "Alias für loadPattern().", ["name:string"]],
        exec_pattern: ["exec_pattern(pattern:mixed, instance:string = active)", "Geladenes Pattern ausführen.", ["pattern:mixed", "instance:string"]],
        get_data: ["get_data([instance], base, table, filter:json = {})", "Daten lesen. Nutzt je nach Argumentanzahl aktiven Context.", ["instance", "base", "table", "filter"]],
        add_data: ["add_data([instance], base, table, data:json)", "Datensatz einfügen.", ["instance", "base", "table", "data"]],
        edit_data: ["edit_data([instance], base, table, filter:json, data:json)", "Datensatz ändern.", ["instance", "base", "table", "filter", "data"]],
        delete_data: ["delete_data([instance], base, table, filter:json)", "Datensatz löschen.", ["instance", "base", "table", "filter"]],
        fulltext_search: ["fulltext_search([instance], base, table, query:string, columns:array = [], limit:int = 50)", "Volltextsuche ausführen.", ["instance", "base", "table", "query", "columns", "limit"]]
    };
    if (/^hash_(sha256|sha512|md5|adler32|crc32)$/.test(lower)) return { label: `${name}(value:mixed)`, documentation: "Hash-Funktion.", params: ["value:mixed"] };
    const item = docs[lower];
    if (!item) return null;
    return { label: item[0], documentation: item[1], params: item[2] };
}

function provideDocumentFormattingEdits(document) {
    const edits = [];
    const original = document.getText();
    const formatted = formatGreenQL(original);
    if (formatted !== original) edits.push(vscode.TextEdit.replace(fullDocumentRange(document), formatted));
    return edits;
}

function provideOnTypeFormattingEdits(document) {
    return provideDocumentFormattingEdits(document);
}

function fullDocumentRange(document) {
    const last = document.lineAt(Math.max(0, document.lineCount - 1));
    return new vscode.Range(0, 0, document.lineCount - 1, last.text.length);
}

function formatGreenQL(text) {
    const lines = text.split(/\r?\n/);
    let level = 0;
    const out = [];
    for (const raw of lines) {
        const trimmed = raw.trim();
        if (trimmed === "") { out.push(""); continue; }
        const visible = stripStringsAndComments(trimmed);
        if (/^[}\])]/.test(trimmed)) level = Math.max(0, level - 1);
        out.push("    ".repeat(level) + trimmed);
        const opens = (visible.match(/[({[]/g) || []).length;
        const closes = (visible.match(/[)}\]]/g) || []).length;
        level = Math.max(0, level + opens - closes);
    }
    return out.join("\n");
}

function provideCodeActions(document, range, context) {
    const actions = [];
    const lineText = document.lineAt(range.start.line).text;
    for (const d of context.diagnostics || []) {
        const msg = d.message || "";
        if (/DECALRE|DELACE/i.test(lineText)) {
            const a = new vscode.CodeAction("DECLARE-Schreibweise normalisieren", vscode.CodeActionKind.QuickFix);
            a.edit = new vscode.WorkspaceEdit();
            a.edit.replace(document.uri, new vscode.Range(range.start.line, 0, range.start.line, lineText.length), lineText.replace(/\b(?:DECALRE|DELACE)\b/gi, "DECLARE"));
            actions.push(a);
        }
        if (/Variablen müssen/.test(msg)) {
            const m = lineText.match(/\b(DECLARE|DECALRE|DELACE)\s+(?::[A-Za-z_][A-Za-z0-9_]*\s+)?([A-Za-z][A-Za-z0-9_]*)/i);
            if (m) {
                const a = new vscode.CodeAction(`_${m[2]} als Variable verwenden`, vscode.CodeActionKind.QuickFix);
                a.edit = new vscode.WorkspaceEdit();
                const idx = lineText.indexOf(m[2], lineText.indexOf(m[1]));
                a.edit.insert(document.uri, new vscode.Position(range.start.line, idx), "_");
                actions.push(a);
            }
        }
    }
    const trimmed = lineText.trim();
    if (trimmed && !/^\s*(OUTPUT|LOG)\s*\(/i.test(lineText)) {
        const a = new vscode.CodeAction("Zeile als OUTPUT(...) ausgeben", vscode.CodeActionKind.RefactorRewrite);
        a.edit = new vscode.WorkspaceEdit();
        a.edit.replace(document.uri, new vscode.Range(range.start.line, 0, range.start.line, lineText.length), lineText.replace(trimmed, `OUTPUT(${trimmed.replace(/;$/, "")});`));
        actions.push(a);
    }
    return actions;
}

async function insertMegaDemo() {
    const editor = vscode.window.activeTextEditor;
    if (!editor || editor.document.languageId !== "greenql") return;
    await editor.edit(edit => edit.insert(editor.selection.active, MEGA_DEMO_SCRIPT));
}

async function showBuiltinsCheatSheet() {
    const doc = await vscode.workspace.openTextDocument({ language: "greenql", content: buildCheatSheet() });
    await vscode.window.showTextDocument(doc, { preview: false });
}

function buildCheatSheet() {
    const lines = [];
    lines.push("# greenQL Built-ins / Keywords Cheat Sheet");
    lines.push("# Generated by greenQL Language Support Pro");
    lines.push("");
    lines.push("# ---------------- COMMANDS / KEYWORDS ----------------");
    for (const [word, detail, group] of COMMAND_SPECS) lines.push(`# ${group.padEnd(16)} ${word} - ${detail}`);
    lines.push("");
    lines.push("# ---------------- RUNTIME FUNCTIONS ----------------");
    for (const [word, detail] of RUNTIME_FUNCTIONS) lines.push(`# ${word}() - ${detail}`);
    lines.push("");
    lines.push("# ---------------- TYPES ----------------");
    lines.push(Array.from(VARIABLE_TYPE_WORDS).map(v => `:${v.toLowerCase()}`).join("  "));
    return lines.join("\n");
}

async function wrapSelection(kind) {
    const editor = vscode.window.activeTextEditor;
    if (!editor || editor.document.languageId !== "greenql") return;
    const sel = editor.selection;
    const text = editor.document.getText(sel) || "_value";
    await editor.edit(edit => edit.replace(sel, `${kind}(${text.replace(/;$/, "")});`));
}

const MEGA_DEMO_SCRIPT = `# ============================================================
# greenQL Mega Demo / VSCode Extension Preview
# ============================================================
META = {
    "GreenQL_version": 2,
    "description": "Demo für Highlighting, IntelliSense, Runtime-Funktionen und Commands",
    "params": [
        {"parameter": "museum_id", "required": false},
        {"parameter": "main_instance", "required": false}
    ]
};

DECLARE :string $mid = param("museum_id");
DECLARE :string $instance = param("main_instance");
DECLARE :string _name = fusion("MuseumQR-", $mid);
DECLARE :int _start = random_int(1, 9999);
DECLARE :json _settings = {"theme":"dark", "created_at": now()};

OUTPUT fusion("BOOT ", _name, " / ", _start);
LOG(_settings);

IF (!instance_exists($instance)) {
    GROW INSTANCE $instance;
}

USE INSTANCE $instance;
ROOT system;

IF (!table_exists("users")) {
    GROW TABLE users (uid:string UNIQUE, username:string REQUIRED, role:string DEFAULT "admin") WITHOUT TYPES;
}

SEED users WITH
    uid=uni_random(),
    username="admin",
    role="admin";

PICK * FROM users WHERE username == "admin" SORT id DESC LIMIT 10;

F makeName(?_prefix:string = "demo", ?_id:int = 0) {
    BACK fusion(_prefix, "-", _id);
}

CLASS DemoService {
    PRIV DECLARE :string _prefix = "srv";

    PUB F boot(?$id:int = 0) {
        BACK fusion(this._prefix, "-", $id);
    }
}

DECLARE _pattern = loadPattern("museumqr");
# EXEC PATTERN _pattern;
# SecondServer/test_connection();
`;

function deactivate() {}
module.exports = { activate, deactivate };
