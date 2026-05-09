<?php

if (isset($_GET["add"])) {
    if ($_GET["add"] == "db") {
        GBDB::createDatabase($_POST["name"]);
        Ref::this_file();
    }

    if ($_GET["add"] == "table") {
        $sdb = GetForm::getDropdown($_POST["db"]);
        $rawCols = trim((string) ($_POST["array"] ?? ""));
        $typesEnabled = !empty($_POST["types_enabled"]);
        $arr = json_decode($rawCols, true);

        if (!is_array($arr)) {
            $arr = array_values(array_filter(array_map('trim', explode(',', $rawCols))));
        }

        $cols = [];
        $schema = [];

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $name = trim((string) ($value["name"] ?? $key));
                $type = strtolower(trim((string) ($value["type"] ?? "mixed")));
                $default = $value["default"] ?? "";
            } else if (is_string($key) && !is_numeric($key)) {
                $name = trim($key);
                $type = is_string($value) ? strtolower(trim($value)) : "mixed";
                $default = "";
            } else {
                $parts = array_map('trim', explode(':', (string) $value, 2));
                $name = $parts[0] ?? "";
                $type = $parts[1] ?? "mixed";
                $default = "";
            }

            if ($name === "" || $name === "id")
                continue;
            $cols[] = $name;
            $schema[$name] = ["type" => $type, "default" => $default, "nullable" => true, "required" => false, "unique" => false];
        }

        if (!empty($cols) && GBDB::createTable($sdb, $_POST["name"], $cols)) {
            if (method_exists('GBDB', 'enableSchemaTypes'))
                GBDB::enableSchemaTypes($sdb, $_POST["name"], $typesEnabled);

            if ($typesEnabled && method_exists('GBDB', 'setColumnType')) {
                foreach ($schema as $col => $def)
                    GBDB::setColumnType($sdb, $_POST["name"], $col, (string) $def["type"], $def);
            }

        }

        Ref::this_file();
    }

}

$dbs = GBDB::listDBs();
?>

<div class="main">
    <div class="topbar">
        <h1>Datenbank-Verwaltung</h1>
    </div>

    <div class="content">
        <!-- Formular -->
        <div class="form-container">
            <h2>Neue Datenbank</h2>
            <form id="add-entry-form" method="post" action="?add=db">
                <input type="text" name="name" placeholder="Name" required>
                <button type="submit" class="btn">Datenbank erstellen</button>
            </form>
        </div>

        <br><br>
        <h2>Neue Tabelle</h2>

        <form id="add-entry-form" method="post" action="?add=table">
            <input type="text" name="name" placeholder="Name" required>

            <select name="db[]">
                <?php for ($i = 0; $i < count($dbs); $i++) { ?>
                    <?php if ($dbs[$i] != "." && $dbs[$i] != "..") { ?>
                        <option value="<?php echo $dbs[$i]; ?>"><?php echo $dbs[$i]; ?></option>
                    <?php } ?>
                <?php } ?>
            </select>

            <input type="text" name="array" placeholder="uid, name, email oder uid:string, age:int" required>
            <label style="display:flex;gap:8px;align-items:center;margin:8px 0;color:#fff;">
                <input type="checkbox" name="types_enabled" value="1">
                Datentypen aktivieren
            </label>
            <button type="submit" class="btn">Tabelle erstellen</button>
        </form>
    </div>
</div>
