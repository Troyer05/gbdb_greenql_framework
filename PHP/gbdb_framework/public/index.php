<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/autoloader.php';

if (!GBDBConfig::flag('ui', 'enabled', true)) {
    http_response_code(403);
    exit('GBDB UI ist deaktiviert.');
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

if (isset($_GET['gbdb_asset'])) {
    $asset = (string) $_GET['gbdb_asset'];

    if (!preg_match('~^(css/[a-zA-Z0-9_.-]+\.css|js/[a-zA-Z0-9_.-]+\.js|img/[a-zA-Z0-9_.-]+\.webp)$~D', $asset)) {
        http_response_code(404);
        exit;
    }

    $file = __DIR__ . '/' . $asset;

    if (!is_file($file)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . match (true) {
        str_ends_with($asset, '.css') => 'text/css; charset=utf-8',
        str_ends_with($asset, '.webp') => 'image/webp',
        default => 'application/javascript; charset=utf-8',
    });
    header('Cache-Control: private, max-age=3600');
    readfile($file);
    exit;
}

$uiDir = __DIR__ . '/includes';
require_once $uiDir . '/_shared.php';
require_once $uiDir . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();

$tool = (string) ($_GET['tool'] ?? $_POST['tool'] ?? 'dashboard');
$allowed = ['dashboard', 'greenql_v2', 'php_exec', 'users', 'migration', 'cryption', 'optimize', 'env', 'plugins', 'gql_scripts', 'backup', 'import_export', 'reinstall', 'enterprise', 'monitoring', 'public_api', 'storage', 'distributed', 'updates'];

if (!in_array($tool, $allowed, true)) {
    $tool = 'dashboard';
}

function gbdbui_redirect(string $tool = 'dashboard', array $params = []) :void {
    header('Location: ' . gbdbui_url($tool, $params));

    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['gbdbui_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_redirect($tool);
    }

    $action = (string) $_POST['gbdbui_action'];

    if ($action === 'setup') {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');
        $language = GreenQLUIv2Helper::normalizeLanguage((string) ($_POST['language'] ?? 'en'));
        GreenQLUIv2Helper::setLanguage($language);

        if (GreenQLUIv2Helper::hasUsers()) {
            gbdbui_flash('bad', 'Setup ist bereits abgeschlossen.');
        } elseif ($password !== $password2) {
            gbdbui_flash('bad', 'Passwörter stimmen nicht überein.');
        } elseif (GreenQLUIv2Helper::createInitialAdmin($username, $password, $language, (string) ($_POST['setup_token'] ?? ''))) {
            $loginSuccessful = GreenQLUIv2Helper::login($username, $password);
            gbdbui_flash($loginSuccessful ? 'ok' : 'bad', $loginSuccessful
                ? 'Admin angelegt und eingeloggt.' : 'Admin angelegt. Bitte mit deinen Zugangsdaten einloggen.');
        } else {
            gbdbui_flash('bad', GreenQLUIv2Helper::setupError());
        }

        gbdbui_redirect('dashboard');
    }

    if ($action === 'login') {
        if (GreenQLUIv2Helper::login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
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
    $languages = ['en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español', 'it' => 'Italiano', 'nl' => 'Nederlands'];
    ?>
<!doctype html>
<html lang="<?= gbdbui_e(GreenQLUIv2Helper::language()) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= $needsSetup ? 'Einrichtung' : 'Anmelden' ?> · GBDB Control Center</title>
    <script>try { if (localStorage.getItem('gbdbui:theme') === 'dark') { document.documentElement.dataset.theme = 'dark'; } } catch (error) { }</script>
    <link rel="icon" type="image/webp" href="<?= gbdbui_e(gbdbui_asset('img/gbdb_icon.webp')) ?>">
    <link rel="stylesheet" href="<?= gbdbui_e(gbdbui_asset('css/intranet.css')) ?>">
    <link rel="stylesheet" href="<?= gbdbui_e(gbdbui_asset('css/gbdb_ui.css')) ?>">
</head>
<body data-page="login" class="login-body gbdbui-auth">
<div class="login-grid">
    <div class="login-illustration"><div class="login-illustration-inner">
        <div class="login-mark"><img class="gbdbui-logo-icon" src="<?= gbdbui_e(gbdbui_asset('img/gbdb_icon.webp')) ?>" alt="" width="34" height="34"><span>GBDB<span class="muted"> Control Center</span></span></div>
        <div class="login-hero"><span class="login-kicker"><span class="online-dot"></span> GREENBUCKET® FRAMEWORK</span><img class="gbdbui-login-logo" src="<?= gbdbui_e(gbdbui_asset('img/gbdb_logo.webp')) ?>" alt="GBDB" width="220" height="220"><h1>Eine Datenbank.<br><em>Alle Werkzeuge.</em></h1><p>Instanzen, Tabellen, GreenQL, Speicher-Failover und verteilte Replikation übersichtlich an einem Ort verwalten.</p>
            <div class="login-stats"><div><strong>GBDB</strong><small>Engine</small></div><div><strong>SQL</strong><small>Fallback</small></div><div><strong>GDP/1</strong><small>Replikation</small></div></div>
        </div>
        <div class="login-grid-glow"></div>
        <div class="login-illustration-foot">GBDB · CONTROL CENTER · <?= gbdbui_e(gbdbui_framework_version()) ?></div>
    </div></div>
    <div class="login-panel">
        <div class="login-mobile-brand"><img class="gbdbui-logo-icon" src="<?= gbdbui_e(gbdbui_asset('img/gbdb_icon.webp')) ?>" alt="" width="34" height="34"><strong>GBDB</strong></div>
        <div class="login-card">
            <div class="welcome-icon"><?= gbdbui_icon('shield', 27) ?></div>
            <div class="eyebrow"><?= $needsSetup ? 'ERSTEINRICHTUNG' : 'WILLKOMMEN ZURÜCK' ?></div>
            <h2><?= $needsSetup ? 'Admin-Konto<br>einrichten.' : 'Hallo, schön dich<br>zu sehen.' ?></h2>
            <p class="login-lead"><?= gbdbui_e($needsSetup ? GreenQLUIv2Helper::t('setup_text') : GreenQLUIv2Helper::t('login_text')) ?></p>
            <?php gbdbui_render_flashes(); ?>
            <?php if ($needsSetup && GreenQLUIv2Helper::setupConfigurationError() !== '') : ?>
                <div class="notice notice-error" role="alert"><?= gbdbui_e(GreenQLUIv2Helper::setupConfigurationError()) ?></div>
            <?php endif; ?>
            <?php if ($needsSetup) : ?>
                <div class="notice notice-info"><strong><?= gbdbui_icon('alert', 16) ?> Setup-Token</strong><p>Den Wert <code>UI → setup_token</code> aus <code>.config/.framework.env.php</code> einfügen. Das Admin-Passwort braucht mindestens 12 Zeichen.</p></div>
            <?php endif; ?>
            <form method="post" class="login-form">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="gbdbui_action" value="<?= $needsSetup ? 'setup' : 'login' ?>">
                <?php if ($needsSetup) : ?>
                    <label>Setup-Token<input name="setup_token" type="password" required autocomplete="off"></label>
                    <label><?= gbdbui_e(GreenQLUIv2Helper::t('language')) ?><select name="language"><?php foreach ($languages as $code => $label) : ?><option value="<?= gbdbui_e($code) ?>"><?= gbdbui_e($label) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('username')) ?><input name="username" autocomplete="username" required></label>
                <label><?= gbdbui_e(GreenQLUIv2Helper::t('password')) ?><input name="password" type="password" autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" <?= $needsSetup ? 'minlength="12"' : '' ?> required></label>
                <?php if ($needsSetup) : ?>
                    <label><?= gbdbui_e(GreenQLUIv2Helper::t('password_repeat')) ?><input name="password2" type="password" autocomplete="new-password" required></label>
                <?php endif; ?>
                <button class="btn btn-primary btn-lg" type="submit"><?= $needsSetup ? gbdbui_e(GreenQLUIv2Helper::t('create_admin')) : gbdbui_e(GreenQLUIv2Helper::t('login')) ?> <?= gbdbui_icon('arrow-right', 18) ?></button>
            </form>
            <p class="login-undertext"><?= gbdbui_icon('lock', 14) ?> Anmeldeversuche sind begrenzt; die Sitzung ist an diesen Browser gebunden.</p>
        </div>
        <div class="login-panel-bottom">© <?= date('Y') ?> greenbucket <span>·</span> GBDB Framework</div>
    </div>
</div>
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
    ['tool' => 'greenql_v2', 'title' => 'Daten im GreenQL Studio bearbeiten', 'text' => 'Instanz, Base und Tabelle wählen, Datensätze pflegen oder Abfragen ausführen.'],
    ['tool' => 'storage', 'title' => 'Speicher und SQL-Fallback prüfen', 'text' => 'Synchronisation kontrollieren und kontrolliert zwischen GBDB und SQL umschalten.'],
    ['tool' => 'distributed', 'title' => 'Replikation (GDP) überwachen', 'text' => 'Knoten, Replikate, Policies, Journal und Synchronisationszustand einsehen.'],
    ['tool' => 'users', 'title' => 'Benutzer und Rechte verwalten', 'text' => 'Konten, Rollen, Instanz- und Base-Rechte sowie Werkzeugfreigaben pflegen.'],
    ['tool' => 'backup', 'title' => 'Backup ziehen', 'text' => 'Den Datenbestand vor Änderungen vollständig sichern.'],
    ['tool' => 'import_export', 'title' => 'Tabellen importieren oder exportieren', 'text' => 'JSON, NDJSON oder CSV mit Validierung übertragen.'],
    ['tool' => 'public_api', 'title' => 'API-Schlüssel ausgeben', 'text' => 'Schlüssel, Rechte, Module und Anfrageprotokolle verwalten.'],
    ['tool' => 'monitoring', 'title' => 'Jobs und Warteschlangen ansehen', 'text' => 'Laufende, fehlgeschlagene und geplante Jobs im Blick behalten.'],
    ['tool' => 'gql_scripts', 'title' => 'GQL-Skripte pflegen', 'text' => '.gql-Dateien bearbeiten, verschieben und testen.'],
    ['tool' => 'env', 'title' => 'Framework-Konfiguration bearbeiten', 'text' => 'Die Vars-Konstanten aus .framework.env.php geprüft ändern.'],
];

$dbRoot = Vars::DB_PATH();
$dbs = gbdbui_list_dbs_cached();
$tables = 0;

foreach ($dbs as $dbName) {
    $tables += count(gbdbui_list_tables_cached((string) $dbName));
}

$storage = GBDB::backendStatus();
$gdp = class_exists('GDP') && GDPConfig::enabled() ? GDP::getHealth() : ['status' => 'disabled'];
gbdbui_layout_start('dashboard', 'Was möchtest du erledigen?', 'Wähle eine Aufgabe. Du gelangst direkt in den passenden Bereich.', ['eyebrow' => 'Start']);
?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-blue"><?= gbdbui_icon('database', 20) ?></span></div>
                <div class="stat-label">Aktive Instanz</div>
                <div class="stat-number"><?= gbdbui_e(GBDB::getInstance()) ?></div>
                <div class="stat-foot">Angemeldet als <?= gbdbui_e($user['username'] ?? '') ?> · <?= gbdbui_e($user['role'] ?? '') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-purple"><?= gbdbui_icon('panel', 20) ?></span></div>
                <div class="stat-label">Bases / Tabellen</div>
                <div class="stat-number"><?= count($dbs) ?> <small>/ <?= (int) $tables ?></small></div>
                <div class="stat-foot">In der aktiven Instanz sichtbar</div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon <?= $storage['mode'] === 'sql' ? 'stat-orange' : 'stat-green' ?>"><?= gbdbui_icon('server', 20) ?></span></div>
                <div class="stat-label">Speicher</div>
                <div class="stat-number"><?= gbdbui_e(strtoupper((string) $storage['mode'])) ?></div>
                <div class="stat-foot"><span class="tiny-dot <?= $storage['sync_pending'] ? 'orange-dot' : 'green-dot' ?>"></span><?= gbdbui_e(gbdbui_bytes(gbdbui_dir_size($dbRoot))) ?> · <?= $storage['enabled'] ? ($storage['sql_ready'] ? 'SQL-Spiegel aktuell' : 'SQL-Spiegel ausstehend') : 'ohne SQL-Fallback' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-blue"><?= gbdbui_icon('globe', 20) ?></span></div>
                <div class="stat-label">Replikation (GDP)</div>
                <div class="stat-number"><?= gbdbui_e(['ok' => 'Online', 'degraded' => 'Offline', 'unpaired' => 'Nicht gekoppelt', 'blocked' => 'Blockiert', 'disabled' => 'Aus'][$gdp['status']] ?? $gdp['status']) ?></div>
                <div class="stat-foot"><?= $gdp['status'] === 'disabled' ? 'Optional: GDP in .framework.env.php aktivieren' : (int) ($gdp['pending_operations'] ?? 0) . ' Änderungen ausstehend' ?></div>
            </div>
        </div>

        <div class="task-cards">
<?php foreach ($cards as $card) : ?>
<?php if (gbdbui_can_tool($card['tool'])) : ?>
            <a class="task-card" href="<?= gbdbui_e(gbdbui_url($card['tool'])) ?>"><span><strong><?= gbdbui_e($card['title']) ?></strong><small><?= gbdbui_e($card['text']) ?></small></span><span aria-hidden="true">→</span></a>
<?php endif; ?>
<?php endforeach; ?>
        </div>
        <section class="panel padded"><h2>Du suchst ein anderes Werkzeug?</h2><p>Öffne links den passenden Bereich oder nutze die Suche oben (Strg + K). Wartung, Migration und Systemeinstellungen findest du unter „Betrieb“ und „System“.</p></section>
<?php gbdbui_layout_end(); ?>
