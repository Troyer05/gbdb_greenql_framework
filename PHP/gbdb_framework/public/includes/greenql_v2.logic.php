<?php
function e(mixed $value): string {
    return GreenQLUIv2Helper::e($value);
}

function csrf(): string {
    return GreenQLUIv2Helper::csrf();
}

function selfUrl(array $params = []): string {
    $script = (string)($_SERVER["SCRIPT_NAME"] ?? "");

    if ($script === "") {
        $script = "/" . basename(__FILE__);
    }

    if (!str_starts_with($script, "/")) {
        $script = "/" . ltrim($script, "/");
    }

    if (!isset($params["tool"])) {
        $params = ["tool" => "greenql_v2"] + $params;
    }

    $query = http_build_query($params);
    return $script . ($query !== "" ? "?" . $query : "");
}

function redirectSelf(array $params = []): void {
    header("Location: " . selfUrl($params));
    exit;
}

function flash(string $type, string $text): void {
    $_SESSION["gqlui_v2_flash"][] = ["type" => $type, "text" => $text];
}

function flashes(): array {
    $items = $_SESSION["gqlui_v2_flash"] ?? [];
    unset($_SESSION["gqlui_v2_flash"]);
    return is_array($items) ? $items : [];
}

function selectedMode(): string {
    $mode = (string)($_GET["mode"] ?? $_POST["mode"] ?? "ui");
    return in_array($mode, ["ui", "query"], true) ? $mode : "ui";
}

function selectedInstance(): string {
    if (!isset($_GET["instance"]) && !isset($_POST["instance"])) {
        return "";
    }

    $instance = GreenQLUIv2Helper::clean((string)($_GET["instance"] ?? $_POST["instance"] ?? ""));

    if ($instance !== "" && GreenQLUIv2Helper::canAccessInstance($instance)) {
        return $instance;
    }

    return "";
}

function selectedDb(string $instance): string {
    $db = GreenQLUIv2Helper::clean((string)($_GET["db"] ?? $_POST["db"] ?? ""));

    if ($db !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
        return $db;
    }

    return "";
}

function selectedTable(string $instance, string $db): string {
    $table = GreenQLUIv2Helper::clean((string)($_GET["table"] ?? $_POST["table"] ?? ""));
    $tables = ($instance !== "" && $db !== "") ? GreenQLUIv2Helper::tables($instance, $db) : [];

    if ($table !== "" && in_array($table, $tables, true)) {
        return $table;
    }

    return "";
}

function queryResultBox(array $result): void {
    if (empty($result)) {
        return;
    }

    echo '<section class="result-card">';
    echo '<div class="result-head"><div><span class="status-dot ' . (!empty($result["ok"]) ? 'ok' : 'bad') . '"></span>' . (!empty($result["ok"]) ? 'Ausführung erfolgreich' : 'Ausführung fehlgeschlagen') . '</div></div>';

    if (!empty($result["messages"]) && is_array($result["messages"])) {
        echo '<div class="message-stack">';

        foreach ($result["messages"] as $msg) {
            $ok = !empty($msg["ok"]);
            echo '<div class="mini-message ' . ($ok ? 'ok' : 'bad') . '">' . e($msg["text"] ?? "") . '</div>';
        }

        echo '</div>';
    }

    if (!empty($result["outputs"]) && is_array($result["outputs"])) {
        echo '<div class="output-stream">';

        foreach ($result["outputs"] as $entry) {
            $value = is_array($entry) ? ($entry["value"] ?? "") : $entry;

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            echo '<div class="output-entry"><div class="output-label">OUTPUT <span>' . e((string)($entry["command"] ?? "")) . '</span></div><pre>' . e((string)$value) . '</pre></div>';
        }

        echo '</div>';
    }

    $keys = $result["keys"] ?? [];
    $rows = $result["rows"] ?? [];

    if (is_array($keys) && is_array($rows) && !empty($keys)) {
        echo '<div class="table-wrap result-table"><table><thead><tr>';

        foreach ($keys as $key) {
            echo '<th>' . e($key) . '</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            echo '<tr>';

            foreach ($keys as $key) {
                $value = $row[$key] ?? "";

                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }

                echo '<td><code>' . e($value) . '</code></td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    if (!empty($result["results"]) && is_array($result["results"])) {
        echo '<details class="raw-details"><summary>Alle Query Ergebnisse</summary><pre>' . e(json_encode($result["results"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre></details>';
    }

    echo '</section>';
}

function paramTextFromPost(): string {
    return (string)($_POST["params"] ?? "");
}

function uiValue(mixed $value): mixed {
    if (!is_string($value)) {
        return $value;
    }

    $value = trim($value);
    $low = strtolower($value);

    if ($low === "true") return 1;
    if ($low === "false") return 0;
    if ($low === "null") return null;
    if (is_numeric($value)) return $value + 0;

    if (($value !== "") && (($value[0] === "[" && substr($value, -1) === "]") || ($value[0] === "{" && substr($value, -1) === "}"))) {
        $json = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
    }

    return $value;
}

function tableDefaults(string $instance, string $db, string $table): array {
    $file = dirname(__DIR__, 2) . "/GBDB_GQL/.DB/.system/schema.json";

    if (!is_file($file)) {
        return [];
    }

    $json = json_decode((string)@file_get_contents($file), true);

    if (!is_array($json)) {
        return [];
    }

    $schema = $json[$instance][$db][$table] ?? [];
    return is_array($schema) ? $schema : [];
}

$action = (string)($_POST["action"] ?? "");
$queryResult = [];
$loadedScript = (string)($_POST["script"] ?? ($_SESSION["gqlui_v2_last_script"] ?? "SHOW INSTANCES;"));
$loadedPath = (string)($_POST["script_path"] ?? ($_SESSION["gqlui_v2_last_path"] ?? ""));
$paramsText = paramTextFromPost();

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST["csrf"] ?? ""))) {
        flash("bad", "Ungültiger Sicherheits-Token.");
        redirectSelf();
    }

    if ($action === "setup") {
        $username = (string)($_POST["username"] ?? "");
        $password = (string)($_POST["password"] ?? "");
        $password2 = (string)($_POST["password2"] ?? "");
        $language = GreenQLUIv2Helper::normalizeLanguage((string)($_POST["language"] ?? "en"));
        GreenQLUIv2Helper::setLanguage($language);

        if (GreenQLUIv2Helper::hasUsers()) {
            flash("bad", "Setup ist bereits abgeschlossen.");
        } elseif ($password !== $password2) {
            flash("bad", "Passwörter stimmen nicht überein.");
        } elseif (GreenQLUIv2Helper::createUser($username, $password, "admin", "*", "*", $language, "*", "*")) {
            GreenQLUIv2Helper::login($username, $password);
            flash("ok", "Admin angelegt und eingeloggt.");
        } else {
            flash("bad", "Admin konnte nicht angelegt werden. Benutzername prüfen, Passwort mindestens 8 Zeichen.");
        }

        redirectSelf();
    }

    if ($action === "login") {
        if (GreenQLUIv2Helper::login((string)($_POST["username"] ?? ""), (string)($_POST["password"] ?? ""))) {
            flash("ok", "Willkommen zurück.");
        } else {
            flash("bad", "Login fehlgeschlagen.");
        }

        redirectSelf();
    }

    if ($action === "logout") {
        GreenQLUIv2Helper::logout();
        flash("ok", "Du wurdest ausgeloggt.");
        redirectSelf();
    }
}

$needsSetup = !GreenQLUIv2Helper::hasUsers();
$loggedIn = GreenQLUIv2Helper::loggedIn();
$user = GreenQLUIv2Helper::user();

if ($loggedIn && ($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $instance = selectedInstance();
    $db = selectedDb($instance);
    $table = selectedTable($instance, $db);
    $mode = selectedMode();

    if ($action === "create_instance") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Instanzen erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($name === "" || GreenQLUIv2Helper::reservedInstance($name)) {
            flash("bad", "Ungültiger Instanzname.");
        } elseif (GBDB::createInstance($name)) {
            flash("ok", "Instanz erstellt: " . $name);
            redirectSelf(["mode" => "ui", "instance" => $name]);
        } else {
            flash("bad", "Instanz konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "create_db") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Bases erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($instance === "" || $name === "" || GreenQLUIv2Helper::reservedName($name)) {
            flash("bad", "Ungültiger Base-Name.");
        } else {
            GBDB::setInstance($instance);

            if (GBDB::createDatabase($name)) {
                flash("ok", "Base erstellt: " . $name);
                redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $name]);
            }

            flash("bad", "Base konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance]);
    }

    if ($action === "create_table") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Tabellen erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));
        $typesEnabled = !empty($_POST["types_enabled"]);
        $schema = [];
        $cols = [];

        foreach (explode(",", (string)($_POST["cols"] ?? "")) as $rawCol) {
            $rawCol = trim($rawCol);
            if ($rawCol === "") continue;
            $parts = array_map('trim', explode(':', $rawCol, 2));
            $col = GreenQLUIv2Helper::clean($parts[0] ?? "");
            $type = strtolower(trim((string)($parts[1] ?? "mixed")));
            if ($col === "" || $col === "id") continue;
            $cols[] = $col;
            $schema[$col] = ["type" => $type, "default" => "", "nullable" => true, "required" => false, "unique" => false];
        }

        if ($instance === "" || $db === "" || $name === "" || empty($cols) || GreenQLUIv2Helper::reservedName($name)) {
            flash("bad", "Tabelle oder Felder ungültig.");
        } else {
            GBDB::setInstance($instance);

            if (GBDB::createTable($db, $name, $cols)) {
                if (method_exists('GBDB', 'enableSchemaTypes')) GBDB::enableSchemaTypes($db, $name, $typesEnabled);
                if ($typesEnabled && method_exists('GBDB', 'setColumnType')) {
                    foreach ($schema as $col => $def) GBDB::setColumnType($db, $name, $col, (string)$def["type"], $def);
                }
                flash("ok", "Tabelle erstellt: " . $name . ($typesEnabled ? " (Types aktiv)" : " (ohne Types)"));
                redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $name]);
            }

            flash("bad", "Tabelle konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db]);
    }

    if ($action === "delete_instance") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Instanzen löschen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($name === "" || GreenQLUIv2Helper::reservedInstance($name)) {
            flash("bad", "Diese Instanz darf nicht gelöscht werden.");
        } elseif (GBDB::deleteInstance($name, true)) {
            flash("ok", "Instanz gelöscht: " . $name);
            redirectSelf(["mode" => "ui"]);
        } else {
            flash("bad", "Instanz konnte nicht gelöscht werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "delete_db") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Bases löschen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $targetInstance = GreenQLUIv2Helper::clean((string)($_POST["instance"] ?? $instance));
        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($targetInstance === "" || $name === "" || GreenQLUIv2Helper::reservedName($name)) {
            flash("bad", "Diese Base darf nicht gelöscht werden.");
        } else {
            GBDB::setInstance($targetInstance);
            $ok = GBDB::deleteDatabase($name);

            flash($ok ? "ok" : "bad", $ok ? "Base gelöscht: " . $name : "Base konnte nicht gelöscht werden. Bitte Tabellen zuerst löschen.");

            if ($ok) {
                redirectSelf(["mode" => "ui", "instance" => $targetInstance]);
            }
        }

        redirectSelf(["mode" => "ui", "instance" => $targetInstance, "db" => $db, "table" => $table]);
    }

    if ($action === "delete_table") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Tabellen löschen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $targetInstance = GreenQLUIv2Helper::clean((string)($_POST["instance"] ?? $instance));
        $targetDb = GreenQLUIv2Helper::clean((string)($_POST["db"] ?? $db));
        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($targetInstance === "" || $targetDb === "" || $name === "") {
            flash("bad", "Tabelle ungültig.");
        } else {
            GBDB::setInstance($targetInstance);
            $ok = GBDB::deleteTable($targetDb, $name);

            flash($ok ? "ok" : "bad", $ok ? "Tabelle gelöscht: " . $name : "Tabelle konnte nicht gelöscht werden.");

            if ($ok) {
                redirectSelf(["mode" => "ui", "instance" => $targetInstance, "db" => $targetDb]);
            }
        }

        redirectSelf(["mode" => "ui", "instance" => $targetInstance, "db" => $targetDb, "table" => $table]);
    }

    if ($action === "add_column") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Spalten hinzufügen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $column = GreenQLUIv2Helper::clean((string)($_POST["column"] ?? ""));
        $default = uiValue($_POST["default"] ?? "");

        if ($instance === "" || $db === "" || $table === "" || $column === "") {
            flash("bad", "Spalte ungültig.");
        } else {
            GBDB::setInstance($instance);
            $ok = GBDB::addColumn($db, $table, $column, $default);
            if ($ok) {
                $rowsForDefault = GBDB::getData($db, $table);
                if (is_array($rowsForDefault)) foreach ($rowsForDefault as $rowForDefault) {
                    if (!is_array($rowForDefault) || (int)($rowForDefault["id"] ?? 0) < 0) continue;
                    if (!array_key_exists($column, $rowForDefault) || (string)($rowForDefault[$column] ?? "") === "-header-") GBDB::editData($db, $table, "id", (int)$rowForDefault["id"], [$column => $default]);
                }
            }
            flash($ok ? "ok" : "bad", $ok ? "Spalte verarbeitet: " . $column . " (Default wurde in Schema und bestehende Rows übernommen)" : "Spalte konnte nicht verarbeitet werden: " . $column);
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "insert_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        if ($instance !== "" && $db !== "" && $table !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDB::setInstance($instance);

            $keys = GBDB::getKeys($db, $table);
            $data = [];

            foreach ($keys as $key) {
                if ($key === "id") {
                    continue;
                }

                $data[$key] = uiValue($_POST["row"][$key] ?? "");
            }

            $id = GBDB::insertData($db, $table, $data);
            flash($id > 0 ? "ok" : "bad", $id > 0 ? "Datensatz angelegt: #" . $id : "Datensatz konnte nicht angelegt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "update_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $id = (int)($_POST["id"] ?? 0);

        if ($instance !== "" && $db !== "" && $table !== "" && $id > 0 && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDB::setInstance($instance);

            $keys = GBDB::getKeys($db, $table);
            $data = [];

            foreach ($keys as $key) {
                if ($key === "id") {
                    continue;
                }

                $data[$key] = uiValue($_POST["row"][$key] ?? "");
            }

            $ok = GBDB::editData($db, $table, "id", $id, $data);
            flash($ok ? "ok" : "bad", $ok ? "Datensatz aktualisiert: #" . $id : "Datensatz konnte nicht aktualisiert werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "delete_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $id = (int)($_POST["id"] ?? 0);

        if ($instance !== "" && $db !== "" && $table !== "" && $id > 0 && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDB::setInstance($instance);
            $ok = GBDB::deleteData($db, $table, "id", $id);
            flash($ok ? "ok" : "bad", $ok ? "Datensatz gelöscht: #" . $id : "Datensatz konnte nicht gelöscht werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "run_query" || $action === "run_uploaded" || $action === "run_path") {
        $script = (string)($_POST["script"] ?? "");
        $params = GreenQLUIv2Helper::parseParams($paramsText);

        if ($action === "run_uploaded") {
            if (!isset($_FILES["gql_file"]) || !is_uploaded_file((string)($_FILES["gql_file"]["tmp_name"] ?? ""))) {
                $queryResult = GreenQLUIv2Helper::errorResult("Keine .gql Datei hochgeladen.");
            } elseif (strtolower(pathinfo((string)$_FILES["gql_file"]["name"], PATHINFO_EXTENSION)) !== "gql") {
                $queryResult = GreenQLUIv2Helper::errorResult("Nur .gql Dateien sind erlaubt.");
            } else {
                $script = (string)file_get_contents((string)$_FILES["gql_file"]["tmp_name"]);
                $loadedScript = $script;
            }
        }

        if ($action === "run_path") {
            $loadedPath = (string)($_POST["script_path"] ?? ($_SESSION["gqlui_v2_last_path"] ?? ""));
            $read = GreenQLUIv2Helper::readScriptPath($loadedPath);

            if (empty($read["ok"])) {
                $queryResult = GreenQLUIv2Helper::errorResult((string)$read["message"]);
            } else {
                $script = (string)$read["script"];
                $loadedScript = $script;

                flash("ok", (string)$read["message"]);
            }
        }

        if (empty($queryResult)) {
            $loadedScript = $script;
            $_SESSION["gqlui_v2_last_script"] = $loadedScript;
            $_SESSION["gqlui_v2_last_path"] = $loadedPath;
            $queryResult = GreenQLUIv2Helper::runScript($script, $instance, $params);
        }
    }
}

$mode = selectedMode();

$instance = $loggedIn ? selectedInstance() : "";
$db = $loggedIn ? selectedDb($instance) : "";
$table = $loggedIn ? selectedTable($instance, $db) : "";
$instances = $loggedIn ? GreenQLUIv2Helper::instances() : [];
if ($loggedIn && $instance === "" && !empty($instances)) {
    $instance = (string)$instances[0];
}
$dbs = ($loggedIn && $instance !== "") ? GreenQLUIv2Helper::databases($instance) : [];
$tables = ($loggedIn && $instance !== "" && $db !== "") ? GreenQLUIv2Helper::tables($instance, $db) : [];
$search = (string)($_GET["search"] ?? "");
$keys = [];
$rows = [];
$columnDefaults = [];

if ($loggedIn && $instance !== "" && $db !== "" && $table !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
    GBDB::setInstance($instance);

    $keys = GBDB::getKeys($db, $table);
    $columnDefaults = tableDefaults($instance, $db, $table);
    $rows = GBDB::getData($db, $table);

    if (!is_array($rows)) {
        $rows = [];
    }

    if ($search !== "") {
        $needle = mb_strtolower($search);

        $rows = array_values(array_filter($rows, function ($row) use ($needle) {
            if (!is_array($row)) {
                return false;
            }

            return mb_stripos(mb_strtolower(json_encode($row, JSON_UNESCAPED_UNICODE) ?: ""), $needle) !== false;
        }));
    }
}
