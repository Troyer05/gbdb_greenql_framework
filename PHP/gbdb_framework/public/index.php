<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoloader.php';

if (!Vars::__DEV__()) {
    http_response_code(403);
    echo 'GBDB UI ist nur im DEV-Modus verfuegbar.';
    exit;
}

$uiDir = __DIR__ . '/includes';
require_once $uiDir . '/_shared.php';
require_once $uiDir . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();

$tool = (string)($_GET['tool'] ?? $_POST['tool'] ?? 'dashboard');
$allowed = ['dashboard', 'greenql_v2', 'php_exec', 'users', 'migration', 'cryption', 'optimize', 'env', 'plugins', 'gql_scripts', 'backup', 'import_export', 'reinstall', 'enterprise', 'monitoring'];

if (!in_array($tool, $allowed, true)) {
    $tool = 'dashboard';
}

/**
 * Leitet innerhalb der GBDB UI weiter.
 * @param string $tool Ziel-Tool.
 * @param array $params Query-Parameter.
 * @return void
 */
function gbdbui_redirect(string $tool = 'dashboard', array $params = []): void {
    header('Location: ' . gbdbui_url($tool, $params));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['gbdbui_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_redirect($tool);
    }

    $action = (string)$_POST['gbdbui_action'];

    if ($action === 'setup') {
        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');
        $language = GreenQLUIv2Helper::normalizeLanguage((string)($_POST['language'] ?? 'en'));
        GreenQLUIv2Helper::setLanguage($language);

        if (GreenQLUIv2Helper::hasUsers()) {
            gbdbui_flash('bad', 'Setup ist bereits abgeschlossen.');
        } elseif ($password !== $password2) {
            gbdbui_flash('bad', 'Passwörter stimmen nicht überein.');
        } elseif (GreenQLUIv2Helper::createUser($username, $password, 'admin', '*', '*', $language, '*', '*')) {
            GreenQLUIv2Helper::login($username, $password);
            gbdbui_flash('ok', 'Admin angelegt und eingeloggt.');
        } else {
            gbdbui_flash('bad', 'Admin konnte nicht angelegt werden. Benutzername prüfen, Passwort mindestens 8 Zeichen.');
        }

        gbdbui_redirect('dashboard');
    }

    if ($action === 'login') {
        if (GreenQLUIv2Helper::login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''))) {
            gbdbui_flash('ok', 'Willkommen zurück.');
        } else {
            gbdbui_flash('bad', 'Login fehlgeschlagen.');
        }

        gbdbui_redirect('dashboard');
    }

    if ($action === 'logout') {
        GreenQLUIv2Helper::logout();
        gbdbui_flash('ok', 'Du wurdest ausgeloggt.');
        gbdbui_redirect('dashboard');
    }
}

$needsSetup = !GreenQLUIv2Helper::hasUsers();
$loggedIn = GreenQLUIv2Helper::loggedIn();

if (!$loggedIn) {
    ?>
<!doctype html>
<html lang="<?= gbdbui_e(GreenQLUIv2Helper::language()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI Login</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
    <link rel="stylesheet" href="gbdb_framework/public/css/greenql_ui.v2.css?v=2026.2">
</head>
<body class="gbdbui-dashboard gbdbui-auth-body">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand-mark">GB</div>
            <p class="eyebrow">greenbucket® Framework</p>
            <h1>GBDB Control Center</h1>
            <p class="muted"><?= $needsSetup ? gbdbui_e(GreenQLUIv2Helper::t('setup_text')) : gbdbui_e(GreenQLUIv2Helper::t('login_text')) ?></p>
            <?php gbdbui_render_flashes(); ?>
            <form method="post" class="stack-form">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="gbdbui_action" value="<?= $needsSetup ? 'setup' : 'login' ?>">
                <?php if ($needsSetup): ?>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('language')) ?><select name="language"><?php foreach (['en'=>'English','de'=>'Deutsch','fr'=>'Français','es'=>'Español','it'=>'Italiano','nl'=>'Nederlands'] as $code=>$label): ?><option value="<?= gbdbui_e($code) ?>"><?= gbdbui_e($label) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('username')) ?><input name="username" autocomplete="username" required></label>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('password')) ?><input name="password" type="password" autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" required></label>
                <?php if ($needsSetup): ?>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('password_repeat')) ?><input name="password2" type="password" autocomplete="new-password" required></label>
                <?php endif; ?>
                <button class="primary"><?= $needsSetup ? gbdbui_e(GreenQLUIv2Helper::t('create_admin')) : gbdbui_e(GreenQLUIv2Helper::t('login')) ?></button>
            </form>
        </section>
    </main>
</body>
</html>
    <?php
    exit;
}

gbdbui_require_tool($tool);

if ($tool !== 'dashboard') {
    require $uiDir . '/' . $tool . '.page.php';
    exit;
}

$user = GreenQLUIv2Helper::user();
$cards = [
    ['tool' => 'greenql_v2', 'tag' => 'Studio', 'title' => 'GreenQL', 'text' => 'Zentrale GreenQL-Oberfläche für GBDB, Instanzen, Bases, Tabellen, Query-Pläne und Rechte.'],
    ['tool' => 'php_exec', 'tag' => 'Dev', 'title' => 'PHP Exec', 'text' => 'PHP direkt im Browser testen – mit Highlighting, Auto-Einrückung und Output.'],
    ['tool' => 'users', 'tag' => 'Admin', 'title' => 'Benutzerverwaltung', 'text' => 'Zentrale UI-User, Rollen, Instanz- und Base-Rechte verwalten.'],
    ['tool' => 'migration', 'tag' => 'Maintenance', 'title' => 'Migration', 'text' => 'Struktur-Upgrades, Meta-/Append-Aufbau und optionale Schema-Konvertierung.'],
    ['tool' => 'cryption', 'tag' => 'Security', 'title' => 'Crypto', 'text' => 'GBDB zwischen Plain-JSON und verschlüsselter/tokenisierter Struktur konvertieren.'],
    ['tool' => 'optimize', 'tag' => 'Performance', 'title' => 'Optimize', 'text' => 'Tabellen compacten, Append-Logs auswerten und GBDB-Dateien warten.'],
    ['tool' => 'env', 'tag' => 'Config', 'title' => 'ENV', 'text' => '.framework.env.php ansehen und ausgewählte Config-Datei direkt bearbeiten.'],
    ['tool' => 'plugins', 'tag' => 'Extend', 'title' => 'Plugins', 'text' => 'Plugins anzeigen, hochladen, löschen und aktiv/deaktiviert markieren.'],
    ['tool' => 'gql_scripts', 'tag' => 'Files', 'title' => 'GQL Scripts', 'text' => '.gql Scripts mit File-Browser, Editor und ENV-Verwaltung pflegen.'],
    ['tool' => 'backup', 'tag' => 'Safe', 'title' => 'Backup', 'text' => 'GBDB-Datenbank nach frei wählbarem Pfad sichern.'],
    ['tool' => 'import_export', 'tag' => 'Data', 'title' => 'Import / Export', 'text' => 'Tabellen als JSON, NDJSON oder CSV exportieren und geprüfte Datensätze importieren.'],
    ['tool' => 'reinstall', 'tag' => 'Danger', 'title' => 'Re-Install', 'text' => 'Alle GBDB-Daten nach doppelter Warnung sauber löschen.'],
    ['tool' => 'enterprise', 'tag' => 'Enterprise', 'title' => 'Enterprise Ops', 'text' => 'Intranet-Pattern, SSO, Tenant-Isolation, Quotas, Rate-Limits, Import/Export, CLI/API und Tests.'],
    ['tool' => 'monitoring', 'tag' => 'Ops', 'title' => 'Monitoring', 'text' => 'Jobs, Queues, Dead-Letter, Worker-Locks und Framework-Health im Blick behalten.'],
];

$dbRoot = Vars::DB_PATH();
$stats = [
    'instance' => GBDB::getInstance(),
    'dbs' => count(gbdbui_list_dbs_cached()),
    'tables' => 0,
    'size' => gbdbui_dir_size($dbRoot),
    'path' => basename(rtrim(dirname($dbRoot), '/')) . '/' . basename(rtrim($dbRoot, '/')),
    'full_path' => $dbRoot,
];
foreach (gbdbui_list_dbs_cached() as $dbName) $stats['tables'] += count(gbdbui_list_tables_cached((string)$dbName));
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.10">
</head>
<body class="gbdbui-dashboard gbdbui-pro">
    <?php gbdbui_nav('dashboard'); ?>
    <main>
        <?php gbdbui_render_flashes(); ?>
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Angemeldet als <?= gbdbui_e($user['username'] ?? '') ?> · Rolle <?= gbdbui_e($user['role'] ?? '') ?></p>
            <h1>GBDB Control Center</h1>
            <p>Zentrale Dev-Oberfläche für GBDB, GreenQL Studio, Migration, Crypto, Optimierung, PHP-Testausführung und Benutzerrechte. GreenQL Legacy wurde aus der Oberfläche entfernt; alle Query-Workflows laufen über das Studio.</p>
        </section>

        <section class="gbdbui-quickbar">
            <?php if (gbdbui_can_tool('greenql_v2')): ?><a class="gbdbui-quick" href="<?= gbdbui_e(gbdbui_url('greenql_v2')) ?>"><strong>GreenQL Studio öffnen</strong><span>Instanzen, Bases, Tabellen und Queries verwalten</span></a><?php endif; ?>
            <?php if (gbdbui_can_tool('users')): ?><a class="gbdbui-quick" href="<?= gbdbui_e(gbdbui_url('users')) ?>"><strong>Rechte prüfen</strong><span>User, Rollen und Zugriff sauber pflegen</span></a><?php endif; ?>
            <?php if (gbdbui_can_tool('backup')): ?><a class="gbdbui-quick" href="<?= gbdbui_e(gbdbui_url('backup')) ?>"><strong>Backup ziehen</strong><span>Datenbestand vor Änderungen sichern</span></a><?php endif; ?>
            <?php if (gbdbui_can_tool('import_export')): ?><a class="gbdbui-quick" href="<?= gbdbui_e(gbdbui_url('import_export')) ?>"><strong>Import / Export</strong><span>Tabellen sicher übertragen oder prüfen</span></a><?php endif; ?>
        </section>

        <section class="gbdbui-grid gbdbui-stat-grid">
            <div class="gbdbui-card"><span>Instanz</span><h2><?= gbdbui_e($stats['instance']) ?></h2><p>Aktive GBDB-Instanz.</p></div>
            <div class="gbdbui-card"><span>Bases</span><h2><?= (int)$stats['dbs'] ?></h2><p>Logische Datenbanken in der aktiven Instanz.</p></div>
            <div class="gbdbui-card"><span>Tabellen</span><h2><?= (int)$stats['tables'] ?></h2><p>Tabellen über alle sichtbaren Bases.</p></div>
            <div class="gbdbui-card gbdbui-size-card"><span>Größe</span><h2><?= gbdbui_e(gbdbui_bytes((int)$stats['size'])) ?></h2><p title="<?= gbdbui_e($stats['full_path']) ?>">Speicherort: <?= gbdbui_e($stats['path']) ?></p></div>
        </section>

        <section class="gbdbui-grid">
            <?php foreach ($cards as $card): ?>
            <?php if (!gbdbui_can_tool($card['tool'])) continue; ?>
            <a class="gbdbui-card" href="<?= gbdbui_e(gbdbui_url($card['tool'])) ?>"><span><?= gbdbui_e($card['tag']) ?></span><h2><?= gbdbui_e($card['title']) ?></h2><p><?= gbdbui_e($card['text']) ?></p></a>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
