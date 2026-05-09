<?php
require_once dirname(__DIR__) . '/autoloader.php';

header("Content-Type: application/json");

$body = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER["REQUEST_METHOD"];

if (!is_array($body)) {
    resp(400, "Invalid JSON body.");
}

if (isset($body["do"]) && $body["do"] === "gtoken") {
    $token = "";

    if (($body["sauth"] ?? "") == hash("sha256", Vars::srvp_static_key())) {
        $tokens = read_tokens();

        do {
            $retry = false;
            $token = hash("sha256", bin2hex(random_bytes(256)));

            foreach ($tokens as $t) {
                if (($t["token"] ?? "") == $token) {
                    $retry = true;
                    break;
                }

            }

        } while ($retry);

        add_token($token);
    }

    resp(200, $token);
}

general_auth($body, $method);
test_param(["do"], $body);

$do = (string)$body["do"];
$ctx = DB_CTX_FROM_BODY($body);
$driver = DB_DRIVER($ctx);

if ($do == "driver") {
    resp(200, [
        "driver" => $driver,
        "instance" => $ctx["instance"] ?? "",
        "gbdb" => class_exists("GBDB"),
        "greenql" => class_exists("GreenQL")
    ]);
}

if ($do == "instances") {
    if (!class_exists("GBDB")) {
        resp(400, "GBDB is not available.");
    }

    resp(200, GBDB::listInstances());
}

if ($do == "create_instance") {
    test_param(["instance"], $body);

    if (!class_exists("GBDB")) {
        resp(400, "GBDB is not available.");
    }

    resp(200, ["created" => GBDB::createInstance((string)$body["instance"])]);
}

if ($do == "delete_instance") {
    test_param(["instance"], $body);

    if (!class_exists("GBDB")) {
        resp(400, "GBDB is not available.");
    }

    resp(200, [
        "deleted" => GBDB::deleteInstance(
            (string)$body["instance"],
            (bool)($body["force"] ?? false)
        )
    ]);
}

if ($do == "bases") {
    resp(200, $driver::listDBs());
}

if ($do == "tables") {
    test_param(["db"], $body);
    resp(200, $driver::listTables((string)$body["db"]));
}

if ($do == "create_base") {
    test_param(["db"], $body);
    resp(200, ["created" => $driver::createDatabase((string)$body["db"])]);
}

if ($do == "delete_base") {
    test_param(["db"], $body);
    resp(200, ["deleted" => $driver::deleteDatabase((string)$body["db"])]);
}

if ($do == "create_table") {
    test_param(["db", "table", "cols"], $body);

    $cols = is_array($body["cols"]) ? $body["cols"] : [];
    resp(200, ["created" => $driver::createTable((string)$body["db"], (string)$body["table"], $cols)]);
}

if ($do == "delete_table") {
    test_param(["db", "table"], $body);
    resp(200, ["deleted" => $driver::deleteTable((string)$body["db"], (string)$body["table"])]);
}

if ($do == "keys") {
    test_param(["db", "table"], $body);
    resp(200, $driver::getKeys((string)$body["db"], (string)$body["table"]));
}

if ($do == "get") {
    test_param(["db", "table"], $body);

    if (isset($body["where"]) && isset($body["is"])) {
        resp(200, DB_GET($body["db"], $body["table"], true, $body["where"], $body["is"], $ctx));
    }

    resp(200, DB_GET($body["db"], $body["table"], false, "", "", $ctx));
}

if ($do == "put") {
    test_param(["db", "table", "data"], $body);

    $id = DB_PUT($body["db"], $body["table"], $body["data"], $ctx);

    if ($id !== false && $id != -1) {
        resp(200, [
            "id" => $id,
            "inserted" => date("d.m.Y H:i:s")
        ]);
    }

    resp(400, "Wrong Data provided.");
}

if ($do == "delete") {
    test_param(["db", "table", "where", "is"], $body);

    $ok = DB_DELETE($body["db"], $body["table"], $body["where"], $body["is"], $ctx);
    resp($ok ? 200 : 400, $ok ? "Data deleted successfully." : "Delete failed.");
}

if ($do == "edit") {
    test_param(["db", "table", "where", "is", "data"], $body);

    $ok = DB_EDIT($body["db"], $body["table"], $body["where"], $body["is"], $body["data"], $ctx);

    if ($ok) {
        resp(200, "Data updated successfully.");
    }

    resp(400, "Edit failed.");
}

if ($do == "instance_exists") {
    test_param(["instance"], $body);
    resp(200, ["exists" => class_exists("GBDB") && in_array((string)$body["instance"], GBDB::listInstances(), true)]);
}

if ($do == "base_exists") {
    test_param(["db"], $body);
    resp(200, ["exists" => in_array((string)$body["db"], $driver::listDBs(), true)]);
}

if ($do == "table_exists") {
    test_param(["db", "table"], $body);
    resp(200, ["exists" => in_array((string)$body["table"], $driver::listTables((string)$body["db"]), true)]);
}

if ($do == "data_exists") {
    test_param(["db", "table", "where", "is"], $body);
    $row = $driver::getData((string)$body["db"], (string)$body["table"], true, (string)$body["where"], $body["is"]);
    resp(200, ["exists" => is_array($row) && !empty($row)]);
}

if ($do == "monitor") {
    if (isset($body["db"]) && isset($body["table"])) {
        resp(200, method_exists($driver, "monitor") ? $driver::monitor((string)$body["db"], (string)$body["table"]) : []);
    }

    $out = [];

    foreach ($driver::listDBs() as $dbName) {
        foreach ($driver::listTables($dbName) as $tableName) {
            $out[] = method_exists($driver, "monitor") ? $driver::monitor($dbName, $tableName) : ["database" => $dbName, "table" => $tableName];
        }

    }

    resp(200, $out);
}

if ($do == "recover") {
    test_param(["db", "table"], $body);
    resp(200, method_exists($driver, "recoverTable") ? $driver::recoverTable((string)$body["db"], (string)$body["table"]) : ["ok" => false, "error" => "recovery_unavailable"]);
}

if ($do == "page") {
    test_param(["db", "table"], $body);
    resp(200, method_exists($driver, "page") ? $driver::page((string)$body["db"], (string)$body["table"], (int)($body["page"] ?? 1), (int)($body["size"] ?? 50)) : ["ok" => false, "rows" => []]);
}

if ($do == "cursor") {
    test_param(["db", "table"], $body);
    $cursor = isset($body["cursor"]) && (string)$body["cursor"] !== "" ? (string)$body["cursor"] : null;
    resp(200, method_exists($driver, "cursor") ? $driver::cursor((string)$body["db"], (string)$body["table"], (int)($body["size"] ?? 100), $cursor) : ["ok" => false, "rows" => []]);
}

if ($do == "fulltext_search" || $do == "fulltext") {
    test_param(["db", "table", "query"], $body);
    $columns = isset($body["columns"]) && is_array($body["columns"]) ? $body["columns"] : [];
    resp(200, method_exists($driver, "fulltext_search") ? $driver::fulltext_search((string)$body["db"], (string)$body["table"], (string)$body["query"], $columns, (int)($body["limit"] ?? 50)) : []);
}

if ($do == "gbdb_get") { test_param(["base", "table"], $body); $options = isset($body["options"]) && is_array($body["options"]) ? $body["options"] : []; resp(200, $driver::get((string)$body["base"], (string)$body["table"], $body["where"] ?? "", $body["is"] ?? "", $options)); }

if ($do == "gbdb_create") { test_param(["base"], $body); $rows = $body["rows"] ?? []; if (!is_array($rows) && !is_object($rows)) $rows = []; resp(200, ["created" => $driver::create((string)$body["base"], (string)($body["table"] ?? ""), (bool)($body["use_data_types"] ?? false), $rows)]); }

if ($do == "gbdb_full_text_search") { test_param(["base", "table", "text"], $body); resp(200, $driver::fullTextSearch((string)$body["base"], (string)$body["table"], (string)$body["text"])); }

if ($do == "gbdb_backup") { resp(200, $driver::createBackup((string)($body["path"] ?? ""))); }

if ($do == "gbdb_run_file") { test_param(["path"], $body); $params = isset($body["params"]) && is_array($body["params"]) ? $body["params"] : []; resp(200, $driver::runFile((string)$body["path"], $params)); }

if ($do == "query") {
    test_param(["query"], $body);
    $params = isset($body["params"]) && is_array($body["params"]) ? $body["params"] : [];
    resp(200, DB_QUERY($body["query"], $ctx, $params));
}

if ($do == "runscript") {
    test_param(["path"], $body);
    $params = isset($body["params"]) && is_array($body["params"]) ? $body["params"] : [];
    resp(200, Srv::runScript($body["path"], $params, $ctx));
}

if ($do == "auth") {
    test_param(["action"], $body);
    resp(200, Srv::auth($body["action"], $body));
}

if ($do == "srv_enqueue") {
    test_param(["service", "action"], $body);

    $payload = $body["payload"] ?? [];
    $id = Srv::enqueue($body["service"], $body["action"], $payload, $ctx);

    resp(200, [
        "job_id" => $id,
        "status" => "queued"
    ]);
}

if ($do == "srv_run_one") {
    test_param(["id"], $body);
    resp(200, Srv::runOne((int)$body["id"], $ctx));
}

if ($do == "srv_status") {
    if (isset($body["id"])) {
        resp(200, Srv::getJob((int)$body["id"], $ctx));
    }

    resp(200, Srv::getJobs($ctx));
}

if ($do == "srv_logs") {
    test_param(["job_id"], $body);
    resp(200, Srv::logs((int)$body["job_id"]));
}

if ($do == "srv_jobs") {
    resp(200, Srv::getJobs($ctx));
}

resp(404, "Unknown backend action: " . $do);
