<?php

if (empty($_GET['t']) || empty($_GET['db'])) {
    Ref::to('?null');
    exit();
}

$table = urldecode($_GET['t']);
$db    = urldecode($_GET['db']);
$data = GBDB::getData($db, $table);
$keys = GBDB::getKeys($db, $table);

if (isset($_GET["delete"])) {
    GBDB::deleteData($db, $table, "id", $_GET["id"]);
    Ref::to("?show=table&db=" . urlencode($db) . "&t=" . urlencode($table));
}

if (isset($_GET["del"])) {
    GBDB::deleteTable($db, $table);
    Ref::to("?null");
}

if (isset($_GET["add"])) {
    $obj = [];

    foreach ($keys as $key) {
        if ($key === "id") continue;
        $obj[$key] = $_POST[$key] ?? "";
    }

    GBDB::insertData($db, $table, $obj);
    Ref::to("?show=table&db=" . urlencode($db) . "&t=" . urlencode($table));
}

if (isset($_GET["edit"]) && isset($_GET["id"])) {
    $id = $_GET["id"];
    $newData = [];

    foreach ($keys as $key) {
        if ($key === "id") continue;

        if (isset($_POST[$key])) {
            $newData[$key] = $_POST[$key];
        }

    }

    GBDB::editData($db, $table, "id", $id, $newData);
    Ref::to("?show=table&db=" . urlencode($db) . "&t=" . urlencode($table));
}

?>

<div class="main">
    <div class="topbar">
        <h1>
            Tabelle: <?php echo htmlspecialchars($table); ?>

            <a class="btn"
               style="background-color:#e74c3c; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none;"
               href="?del=true&db=<?php echo urlencode($db); ?>&t=<?php echo urlencode($table); ?>">
                Löschen
            </a>
        </h1>
    </div>

    <div class="content">
        <div class="form-container">
            <h2>Neuen Eintrag erstellen</h2>

            <form id="add-entry-form" method="post"
                  action="?show=table&add=true&db=<?php echo urlencode($db); ?>&t=<?php echo urlencode($table); ?>">

                <?php foreach ($keys as $key): ?>
                    <?php if ($key === "id") continue; ?>
                    <input type="text" name="<?php echo htmlspecialchars($key); ?>"
                           placeholder="<?php echo htmlspecialchars($key); ?>">
                <?php endforeach; ?>

                <button type="submit" class="btn">Hinzufügen</button>
            </form>
        </div>
        <div class="search-bar">
            <input type="text" id="search" placeholder="Einträge durchsuchen...">
        </div>

        <table>
            <thead>
                <tr>
                    <?php foreach ($keys as $key): ?>
                        <th><?php echo htmlspecialchars($key); ?></th>
                    <?php endforeach; ?>
                    <th>Aktionen</th>
                </tr>
            </thead>

            <tbody id="db-entries">
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($keys as $key): ?>
                                <td><?php echo htmlspecialchars($row[$key]); ?></td>
                            <?php endforeach; ?>

                            <td>
                                <button class="btn" style="background:#3498db;color:#fff"
                                    onclick="startEdit(<?php echo $row['id']; ?>)">Bearbeiten</button>

                                <a class="btn"
                                style="background-color:#e74c3c; color:#fff;"
                                href="?show=table&delete=true&db=<?php echo urlencode($db); ?>&t=<?php echo urlencode($table); ?>&id=<?php echo $row['id']; ?>">
                                Löschen
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const tbody = document.getElementById('db-entries');
const searchInput = document.getElementById('search');

searchInput.addEventListener('input', () => {
    const filter = searchInput.value.toLowerCase();
    tbody.querySelectorAll('tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});

function startEdit(id) {
    const row = [...document.querySelectorAll('#db-entries tr')]
        .find(r => r.children[0].textContent == id);

    if (!row) return;

    const cells = row.querySelectorAll('td');
    row.dataset.original = row.innerHTML;

    let colIndex = 0;

    <?php
    echo "const columns = " . json_encode($keys) . ";\n";
    ?>

    for (let i = 0; i < columns.length; i++) {
        const col = columns[i];

        if (col === "id") continue;

        const input = document.createElement("input");
        input.type = "text";
        input.value = cells[i].textContent.trim();
        input.name = col;
        input.style.width = "100%";

        cells[i].innerHTML = "";
        cells[i].appendChild(input);
    }

    const actionCell = cells[cells.length - 1];
    actionCell.innerHTML = `
        <button class="btn" style="background:#2ecc71;color:#fff"
            onclick="saveEdit(${id})">Speichern</button>
        <button class="btn" style="background:#7f8c8d;color:#fff"
            onclick="cancelEdit(${id})">Abbrechen</button>
    `;
}

function cancelEdit(id) {
    const row = [...document.querySelectorAll('#db-entries tr')]
        .find(r => r.children[0].textContent == id);

    row.innerHTML = row.dataset.original;
}

function saveEdit(id) {
    const row = [...document.querySelectorAll('#db-entries tr')]
        .find(r => r.children[0].textContent == id);

    const form = document.createElement("form");
    form.method = "post";
    form.action = "?show=table&edit=true&db=<?php echo urlencode($db); ?>&t=<?php echo urlencode($table); ?>&id=" + id;

    row.querySelectorAll("input").forEach(input => {

        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = input.name;
        hidden.value = input.value;

        form.appendChild(hidden);
    });

    document.body.appendChild(form);
    form.submit();
}

</script>
