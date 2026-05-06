<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . "/gbdb.php";

header("Content-Type: application/json; charset=utf-8");

$oldInstance = GBDB::getInstance();
$instance = "week21_25_test_" . bin2hex(random_bytes(4));
$out = ["ok" => true, "tests" => []];

try {
    GBDB::setInstance($instance);
    GBDB::createTable("demo", "users", ["email", "age", "active", "role", "serial", "token", "created_at"]);

    $out["tests"]["schema_types_enabled"] = GBDB::enableSchemaTypes("demo", "users", true);
    $out["tests"]["email_type"] = GBDB::setColumnType("demo", "users", "email", "email");
    $out["tests"]["age_type"] = GBDB::setColumnType("demo", "users", "age", "int", ["default" => 18, "check" => ["min" => 0, "max" => 130]]);
    $out["tests"]["bool_type"] = GBDB::setColumnType("demo", "users", "active", "bool", ["default" => true]);
    $out["tests"]["enum_type"] = GBDB::setColumnType("demo", "users", "role", "enum", ["enum" => ["admin", "user"], "default" => "user"]);
    $out["tests"]["uuid_generated"] = GBDB::setColumnType("demo", "users", "token", "uuid", ["generated" => "uuid"]);
    $out["tests"]["date_default"] = GBDB::setColumnType("demo", "users", "created_at", "datetime", ["default" => "NOW"]);
    $out["tests"]["unique_email"] = GBDB::setSchemaConstraint("demo", "users", "email", "unique", true);
    $out["tests"]["required_email"] = GBDB::setSchemaConstraint("demo", "users", "email", "required", true);
    $out["tests"]["serial_ai"] = GBDB::setSchemaConstraint("demo", "users", "serial", "auto_increment", true);

    $id = GBDB::insertData("demo", "users", ["email" => "markus@example.com", "age" => "42", "role" => "admin"]);
    $row = GBDB::getData("demo", "users", true, "id", $id);

    $out["tests"]["insert_id"] = $id;
    $out["tests"]["insert_row"] = $row;
    $out["tests"]["duplicate_blocked"] = GBDB::insertData("demo", "users", ["email" => "markus@example.com"]) === -1;
    $out["tests"]["invalid_email_blocked"] = GBDB::insertData("demo", "users", ["email" => "not-valid"]) === -1;

    $snap = GBDB::beginSnapshot();
    GBDB::editData("demo", "users", "id", $id, ["age" => "43"]);
    $snapshotRow = GBDB::getDataSnapshot("demo", "users", $snap, true, "id", $id);
    $committedRow = GBDB::getData("demo", "users", true, "id", $id);
    GBDB::endSnapshot($snap);

    $out["tests"]["snapshot_row"] = $snapshotRow;
    $out["tests"]["committed_row"] = $committedRow;
    $out["tests"]["version_chain"] = GBDB::rowVersions("demo", "users", $id);
    $out["tests"]["mvcc_stats"] = GBDB::mvccStats("demo", "users");
    $out["tests"]["schema_check"] = GBDB::checkSchema("demo", "users");
    $out["tests"]["mvcc_cleanup"] = GBDB::garbageCollectVersions("demo", "users", 0);

    $out["ok"] = $id > 0
        && is_array($row)
        && ($row["age"] ?? null) === 42
        && ($committedRow["age"] ?? null) === 43
        && ($out["tests"]["duplicate_blocked"] ?? false)
        && ($out["tests"]["invalid_email_blocked"] ?? false)
        && (bool)($out["tests"]["schema_check"]["ok"] ?? false);
} catch (Throwable $e) {
    $out["ok"] = false;
    $out["error"] = $e->getMessage();
} finally {
    GBDB::deleteInstance($instance, true);
    GBDB::setInstance($oldInstance);
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
