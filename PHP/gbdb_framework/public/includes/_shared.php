<?php

declare(strict_types=1);

function gbdbui_e(mixed $value) :string {
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function gbdbui_url(string $tool = '', array $params = []) :string {
    $base = (string) ($_SERVER['SCRIPT_NAME'] ?? 'gbdb_ui.php');

    if ($base === '') {
        $base = 'gbdb_ui.php';
    }

    if ($tool !== '') {
        $params = ['tool' => $tool] + $params;
    }

    $query = http_build_query($params);

    return $base . ($query !== '' ? '?' . $query : '');
}

function gbdbui_asset(string $asset) :string {
    $asset = explode('?', $asset, 2)[0];
    $file = dirname(__DIR__) . '/' . $asset;
    // Inhaltsversion statt nur Framework-Version: ein Update liefert sofort neue Dateien aus.
    $version = gbdbui_framework_version() . (is_file($file) ? '-' . base_convert((string) filemtime($file), 10, 36) . '-' . base_convert((string) filesize($file), 10, 36) : '');

    return gbdbui_url('', ['gbdb_asset' => $asset, 'v' => $version]);
}

/** Installierte Framework-Version (aus version.json, nicht aus der Konfiguration). */
function gbdbui_framework_version() :string {
    try {
        return 'v' . GBDBUpdateVersion::current();
    } catch (Throwable) {
        return class_exists('Vars') ? Vars::framework_version() : '';
    }
}

/**
 * Hinweis auf ein verfügbares Framework-Update für Admins, auf jeder Seite der Oberfläche.
 * Liest nur den gespeicherten Status; die Suche selbst läuft im Hintergrund.
 */
function gbdbui_update_banner(string $active) :void {
    if (!class_exists('GreenQLUIv2Helper') || !GreenQLUIv2Helper::isAdmin()) {
        return;
    }

    try {
        $status = GBDBFrameworkUpdater::status();
        $job = GBDBUpdateInstaller::job();
    } catch (Throwable) {
        return;
    }

    $csrf = gbdbui_e(GreenQLUIv2Helper::csrf());

    if ($status['due']) {
        echo '<span hidden data-gbdb-update-tick data-url="' . gbdbui_e(gbdbui_url('updates')) . '" data-csrf="' . $csrf . '"></span>';
    }

    if ($active === 'updates') {
        return;
    }

    if (GBDBUpdateInstaller::isActive($job)) {
        echo '<div class="notice notice-info gbdbui-update-banner" role="status"><div><strong>' . gbdbui_icon('refresh', 16) . ' Framework-Update auf v' . gbdbui_e($job['version'] ?? '') . ' läuft</strong><p>'
            . (($job['status'] ?? '') === 'needs_confirmation' ? 'Eine Entscheidung ist erforderlich.' : 'Der Vorgang ist noch nicht abgeschlossen.') . '</p></div>'
            . '<div class="gbdbui-update-actions"><a class="btn btn-primary btn-sm" href="' . gbdbui_e(gbdbui_url('updates')) . '">Zum Update</a></div></div>';

        return;
    }

    if (!empty($status['notify'])) {
        $version = gbdbui_e($status['available']['version']);
        echo '<div class="notice notice-info gbdbui-update-banner" role="status"><div><strong>' . gbdbui_icon('download', 16) . ' Ein neues GBDB FrameWork Update ist verfügbar!</strong>'
            . '<p>Installierte Version: <code>v' . gbdbui_e($status['installed']) . '</code> · Verfügbare Version: <code>v' . $version . '</code> · signiert und geprüft</p></div>'
            . '<div class="gbdbui-update-actions">'
            . '<form method="post" action="' . gbdbui_e(gbdbui_url('updates')) . '" data-confirm="GBDB auf v' . $version . ' aktualisieren? Vorher wird automatisch gesichert; bei Fehlern wird zurückgerollt.">'
            . '<input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="update_action" value="install"><input type="hidden" name="version" value="' . $version . '">'
            . '<button class="btn btn-primary btn-sm">Update installieren</button></form>'
            . '<form method="post" action="' . gbdbui_e(gbdbui_url('updates')) . '"><input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="update_action" value="skip">'
            . '<input type="hidden" name="version" value="' . $version . '"><input type="hidden" name="return" value="' . gbdbui_e($active) . '"><button class="btn btn-secondary btn-sm">Dieses Update überspringen</button></form>'
            . '</div></div>';

        return;
    }

    $security = ['INVALID_SIGNATURE', 'UNTRUSTED_KEY', 'REVOKED_KEY', 'INVALID_CHECKSUM', 'ROLLBACK_DETECTED', 'INVALID_METADATA', 'CLOCK_INVALID', 'EXPIRED_METADATA'];

    if (in_array($status['code'] ?? '', $security, true) || ($status['warnings'] ?? []) !== []) {
        echo '<div class="notice notice-error gbdbui-update-banner" role="alert"><div><strong>' . gbdbui_icon('shield', 16) . ' Sicherheitshinweis zu Framework-Updates</strong><p>'
            . gbdbui_e((string) (($status['warnings'][0] ?? '') ?: ($status['message'] ?? ''))) . '</p></div>'
            . '<div class="gbdbui-update-actions"><a class="btn btn-secondary btn-sm" href="' . gbdbui_e(gbdbui_url('updates')) . '">Details</a></div></div>';
    }
}

/**
 * Navigationsstruktur der Oberfläche, gruppiert wie im Intranet.
 *
 * @return array<string, list<array{0:string, 1:string, 2:string}>> Gruppe => [Werkzeug, Icon, Beschriftung]
 */
function gbdbui_navigation() :array {
    return [
        'Start' => [
            ['dashboard', 'dashboard', 'Übersicht'],
        ],
        'Daten' => [
            ['greenql_v2', 'database', 'GreenQL Studio'],
            ['gql_scripts', 'book', 'GQL-Skripte'],
            ['import_export', 'download', 'Import / Export'],
            ['backup', 'save', 'Backup'],
        ],
        'Betrieb' => [
            ['storage', 'server', 'Speicher & SQL'],
            ['distributed', 'globe', 'Distributed (GDP)'],
            ['monitoring', 'activity', 'Monitoring'],
            ['optimize', 'sparkles', 'Optimierung'],
            ['migration', 'refresh', 'Migration'],
            ['enterprise', 'chart', 'Enterprise'],
        ],
        'Zugriff' => [
            ['users', 'users', 'Benutzer & Rechte'],
            ['public_api', 'link', 'Public API'],
            ['cryption', 'lock', 'Verschlüsselung'],
        ],
        'System' => [
            ['env', 'settings', 'Framework-Konfiguration'],
            ['updates', 'download', 'Framework-Updates'],
            ['plugins', 'panel', 'Plugins'],
            ['php_exec', 'edit', 'PHP Exec'],
            ['reinstall', 'trash', 'Re-Install'],
        ],
    ];
}

/**
 * Icons aus dem Intranet-Designsystem (gleiche Pfade, gleiche Strichstärke).
 */
function gbdbui_icon(string $name, int $size = 20) :string {
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'server' => '<rect x="3" y="2" width="18" height="8" rx="2"/><rect x="3" y="14" width="18" height="8" rx="2"/><path d="M7 6h.01M7 18h.01M11 6h6M11 18h6"/>',
        'settings' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06A1.7 1.7 0 0 0 16 18.6a1.7 1.7 0 0 0-1 1.57V21h-2.7v-.83A1.7 1.7 0 0 0 10.6 18.6a1.7 1.7 0 0 0-1.8.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.24 15a1.7 1.7 0 0 0-1.57-1H5v-2.7h.67A1.7 1.7 0 0 0 7.24 10.6a1.7 1.7 0 0 0-.34-1.8l-.06-.06 1.9-1.9.06.06A1.7 1.7 0 0 0 10.6 7.24a1.7 1.7 0 0 0 1-1.57V5h2.7v.67A1.7 1.7 0 0 0 16 7.24a1.7 1.7 0 0 0 1.8-.34l.06-.06 1.9 1.9-.06.06a1.7 1.7 0 0 0-.34 1.8 1.7 1.7 0 0 0 1.57 1H21v2.7h-.83A1.7 1.7 0 0 0 19.4 15Z"/>',
        'book' => '<path d="M12 7v14m0-14C9 5 5.5 5 2 6v13c3.5-1 7-1 10 1m0-13c3-2 6.5-2 10-1v13c-3.5-1-7-1-10 1"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="7"/><path d="m16 16 5 5"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'moon' => '<path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>',
        'activity' => '<path d="M2 12h4l3-8 5 16 3-8h5"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'external' => '<path d="M13 5h6v6M19 5l-9 9"/><path d="M19 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/>',
        'refresh' => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.5 9a7 7 0 0 1 12-2L20 12M4 12l2.5 5a7 7 0 0 0 12-2"/>',
        'edit' => '<path d="M12 20h9M15 5l4 4M4 16l-1 5 5-1L20 8a2 2 0 0 0-4-4L4 16Z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2m3 0-1 15H6L5 6M10 10v7m4-7v7"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v6h8"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l3-3a5 5 0 0 0-7.1-7.1l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3a5 5 0 0 0 7.1 7.1l1.7-1.7"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.4 1.6c-1.2 1.2-1.9 1.5-1.9 3.4m0 3h.01"/>',
        'sparkles' => '<path d="m12 3 2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6ZM19 17l.7 1.3L21 19l-1.3.7L19 21l-.7-1.3L17 19l1.3-.7L19 17Z"/>',
        'chart' => '<path d="M3 3v18h18M7 16v-5M12 16V7M17 16v-8"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'panel' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/>',
        'arrow-right' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>',
    ];
    $size = max(12, min(64, $size));

    return '<svg aria-hidden="true" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? $paths['help']) . '</svg>';
}

/**
 * Systemhinweise für die Glocke in der Kopfzeile (Speicher und GDP), ohne Netzwerkzugriff.
 *
 * @return list<array{icon:string, tone:string, title:string, text:string, tool:string}>
 */
function gbdbui_notifications() :array {
    $items = [];

    try {
        $storage = GBDBRuntime::status();

        if ($storage['mode'] === 'sql') {
            $items[] = ['icon' => 'database', 'tone' => 'orange', 'title' => 'SQL-Fallback ist aktiv', 'text' => (string) ($storage['reason'] ?: 'Primärspeicher prüfen und kontrolliert zurückschalten.'), 'tool' => 'storage'];
        } elseif ($storage['last_error'] !== '') {
            $items[] = ['icon' => 'alert', 'tone' => 'orange', 'title' => 'Speicherhinweis', 'text' => (string) $storage['last_error'], 'tool' => 'storage'];
        }

        if (class_exists('GDP') && GDPConfig::enabled()) {
            $health = GDP::getHealth();

            foreach ($health['blocked_instances'] ?? [] as $instance) {
                $items[] = ['icon' => 'globe', 'tone' => 'orange', 'title' => 'GDP: Instanz blockiert', 'text' => (string) $instance . ' braucht eine Admin-Entscheidung.', 'tool' => 'distributed'];
            }

            if (($health['connection_state'] ?? '') === 'offline') {
                $items[] = ['icon' => 'globe', 'tone' => 'blue', 'title' => 'GDP: Hub nicht erreichbar', 'text' => (int) ($health['pending_operations'] ?? 0) . ' Änderungen warten auf Übertragung.', 'tool' => 'distributed'];
            }
        }
    } catch (Throwable $exception) {
        $items[] = ['icon' => 'alert', 'tone' => 'orange', 'title' => 'Status nicht lesbar', 'text' => 'Speicherzustand konnte nicht ermittelt werden.', 'tool' => 'storage'];
    }

    if (class_exists('GreenQLUIv2Helper') && GreenQLUIv2Helper::isAdmin()) {
        try {
            $update = GBDBFrameworkUpdater::status();

            if (!empty($update['notify'])) {
                $items[] = ['icon' => 'download', 'tone' => 'blue', 'title' => 'Framework-Update verfügbar', 'text' => 'v' . $update['installed'] . ' → v' . $update['available']['version'] . ' (signiert und geprüft)', 'tool' => 'updates'];
            } elseif (($update['warnings'] ?? []) !== [] || ($update['state'] ?? '') === 'error') {
                $items[] = ['icon' => 'shield', 'tone' => 'orange', 'title' => 'Framework-Updates prüfen', 'text' => (string) (($update['warnings'][0] ?? '') ?: ($update['message'] ?? '')), 'tool' => 'updates'];
            }
        } catch (Throwable) {
        }

        $migration = GreenQLUIv2Helper::permissionMigrationNotice();

        if ($migration !== null) {
            $items[] = ['icon' => 'users', 'tone' => 'orange', 'title' => 'Rechte-Umstellung prüfen', 'text' => (count($migration['accounts']) === 1 ? '1 Konto hat' : count($migration['accounts']) . ' Konten haben') . ' bei der Umstellung ausdrücklich alle Rechte erhalten.', 'tool' => 'users'];
        }
    }

    return $items;
}

/**
 * Beginn einer Seite im Intranet-Layout: Kopf, Sidebar, Kopfzeile, Brotkrumen und Seitentitel.
 *
 * @param string $active Werkzeugschlüssel der Seite.
 * @param string $title Seitentitel (h1).
 * @param string $subtitle Kurzbeschreibung unter dem Titel.
 * @param array{css?:list<string>, js?:list<string>, actions?:string, eyebrow?:string, body_class?:string} $options
 */
function gbdbui_layout_start(string $active, string $title, string $subtitle = '', array $options = []) :void {
    $user = class_exists('GreenQLUIv2Helper') ? GreenQLUIv2Helper::user() : [];
    $navigation = gbdbui_navigation();
    $activeGroup = 'Start';

    foreach ($navigation as $group => $items) {
        foreach ($items as $item) {
            if ($item[0] === $active) {
                $activeGroup = $group;
            }
        }
    }

    $groupIcons = ['Start' => 'dashboard', 'Daten' => 'database', 'Betrieb' => 'server', 'Zugriff' => 'shield', 'System' => 'settings'];
    $username = (string) ($user['username'] ?? '');
    $initials = strtoupper(mb_substr($username !== '' ? $username : 'GB', 0, 2));
    $notifications = $username !== '' ? gbdbui_notifications() : [];
    $environment = class_exists('Vars') && Vars::__DEV__() ? 'DEV' : 'PROD';
    ?>
<!doctype html>
<html lang="de" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= gbdbui_e($title) ?> · GBDB Control Center</title>
    <script>try { if (localStorage.getItem('gbdbui:theme') === 'dark') { document.documentElement.dataset.theme = 'dark'; } } catch (error) { }</script>
    <link rel="icon" type="image/webp" href="<?= gbdbui_e(gbdbui_asset('img/gbdb_icon.webp')) ?>">
    <link rel="stylesheet" href="<?= gbdbui_e(gbdbui_asset('css/intranet.css')) ?>">
    <link rel="stylesheet" href="<?= gbdbui_e(gbdbui_asset('css/gbdb_ui.css')) ?>">
<?php foreach ($options['css'] ?? [] as $stylesheet) : ?>
    <link rel="stylesheet" href="<?= gbdbui_e(gbdbui_asset('css/' . $stylesheet)) ?>">
<?php endforeach; ?>
    <script defer src="<?= gbdbui_e(gbdbui_asset('js/gbdbui_shell.js')) ?>"></script>
<?php foreach ($options['js'] ?? [] as $script) : ?>
    <script defer src="<?= gbdbui_e(gbdbui_asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</head>
<body data-page="<?= gbdbui_e($active) ?>" class="gbdbui-body <?= gbdbui_e((string) ($options['body_class'] ?? '')) ?>">
<a class="skip-link" href="#main-content">Zum Inhalt springen</a>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Hauptnavigation">
        <div class="brand-area">
            <a href="<?= gbdbui_e(gbdbui_url('dashboard')) ?>" class="brand" aria-label="GBDB – Übersicht">
                <span class="brand-logo gbdbui-brand-logo"><img src="<?= gbdbui_e(gbdbui_asset('img/gbdb_icon.webp')) ?>" alt="" width="40" height="40"></span>
                <span class="brand-type"><strong>GBDB</strong><small>CONTROL <span class="brand-dot"></span> CENTER</small></span>
            </a>
        </div>
        <nav class="side-nav" aria-label="Navigation">
            <div class="nav-browser-head"><span>ARBEITSBEREICH</span><button type="button" data-action="nav-directory" aria-expanded="false" aria-controls="nav-groups"><?= gbdbui_icon('panel', 16) ?> Bereiche wechseln <?= gbdbui_icon('chevron-down', 14) ?></button></div>
            <div class="nav-groups" id="nav-groups">
<?php foreach ($navigation as $group => $items) : ?>
<?php
    $visible = array_values(array_filter($items, static fn(array $item) :bool => $username === '' || gbdbui_can_tool($item[0])));

    if ($visible === []) {
        continue;
    }

    $open = $group === $activeGroup;
    $groupId = 'nav-links-' . strtolower(preg_replace('/[^a-z]/i', '', $group) ?? '');
?>
                <div class="nav-group<?= $open ? ' group-open' : '' ?>" data-nav-group>
                    <button class="nav-group-switch<?= $open ? ' selected' : '' ?>" type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="<?= gbdbui_e($groupId) ?>">
                        <span class="nav-icon"><?= gbdbui_icon($groupIcons[$group] ?? 'book', 18) ?></span>
                        <span class="nav-group-title"><?= gbdbui_e($group) ?></span>
                        <span class="nav-chevron"><?= gbdbui_icon('chevron-down', 14) ?></span>
                    </button>
                    <div class="nav-submenu" id="<?= gbdbui_e($groupId) ?>"<?= $open ? '' : ' hidden' ?>>
                        <div class="nav-main-links">
<?php foreach ($visible as [$tool, $icon, $label]) : ?>
                            <a href="<?= gbdbui_e(gbdbui_url($tool)) ?>" class="nav-link<?= $tool === $active ? ' active' : '' ?>"<?= $tool === $active ? ' aria-current="page"' : '' ?> title="<?= gbdbui_e($label) ?>">
                                <span class="nav-item-icon"><?= gbdbui_icon($icon, 15) ?></span>
                                <span class="nav-label"><?= gbdbui_e($label) ?></span>
                            </a>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </nav>
        <div class="sidebar-bottom">
            <div class="support-box">
                <span class="support-icon"><?= gbdbui_icon('book', 20) ?></span>
                <span><strong>GBDB <?= gbdbui_e(gbdbui_framework_version()) ?></strong><small>Umgebung <?= gbdbui_e($environment) ?></small></span>
            </div>
<?php if ($username !== '') : ?>
            <div class="sidebar-user"><span class="avatar avatar-violet"><?= gbdbui_e($initials) ?></span><span class="sidebar-user-name"><strong><?= gbdbui_e($username) ?></strong><small><?= gbdbui_e(ucfirst((string) ($user['role'] ?? ''))) ?> · GBDB UI</small></span>
                <form method="post" action="<?= gbdbui_e(gbdbui_url($active)) ?>" class="gbdbui-logout-form">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                    <input type="hidden" name="gbdbui_action" value="logout">
                    <button type="submit" class="sidebar-profile-link" aria-label="Abmelden" title="Abmelden"><?= gbdbui_icon('logout', 18) ?></button>
                </form>
            </div>
<?php endif; ?>
        </div>
    </aside>
    <button class="mobile-overlay" id="mobile-overlay" aria-label="Menü schließen" type="button" hidden></button>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-start"><button type="button" class="icon-btn mobile-menu" data-action="mobile-nav" aria-label="Navigation öffnen" aria-expanded="false" aria-controls="sidebar"><?= gbdbui_icon('menu', 22) ?></button><span class="breadcrumbs"><span>GBDB</span><?= gbdbui_icon('chevron-right', 15) ?><strong><?= gbdbui_e($title) ?></strong></span></div>
            <div class="topbar-end">
                <button class="search-trigger" data-action="command" type="button" aria-label="Suche öffnen"><span><?= gbdbui_icon('search', 17) ?> <span class="search-title">Suchen …</span></span><kbd>Ctrl K</kbd></button>
                <span class="topbar-separator"></span>
                <button type="button" class="icon-btn" data-action="theme" title="Darstellung wechseln" aria-label="Darstellung wechseln"><?= gbdbui_icon('moon', 19) ?></button>
                <div class="popover-anchor"><button type="button" class="icon-btn notification-btn" data-action="notifications" aria-label="Systemhinweise anzeigen" aria-expanded="false"><?= gbdbui_icon('bell', 19) ?><?php if ($notifications !== []) : ?><span class="notification-dot"></span><?php endif; ?></button>
                    <div class="notifications-popover" id="notifications-popover" hidden>
                        <div class="popover-heading"><strong>Systemhinweise</strong><small><?= count($notifications) ?> offen</small></div>
<?php if ($notifications === []) : ?>
                        <div class="notification-item"><span class="activity-icon green"><?= gbdbui_icon('check-circle', 17) ?></span><span><strong>Alles in Ordnung</strong><small>Speicher und Replikation melden keine Probleme.</small></span></div>
<?php endif; ?>
<?php foreach (array_slice($notifications, 0, 4) as $notice) : ?>
                        <a class="notification-item" href="<?= gbdbui_e(gbdbui_url($notice['tool'])) ?>"><span class="activity-icon <?= gbdbui_e($notice['tone']) ?>"><?= gbdbui_icon($notice['icon'], 17) ?></span><span><strong><?= gbdbui_e($notice['title']) ?></strong><small><?= gbdbui_e($notice['text']) ?></small></span></a>
<?php endforeach; ?>
                        <a href="<?= gbdbui_e(gbdbui_url('storage')) ?>" class="popover-footer">Zum Speicherstatus <?= gbdbui_icon('arrow-right', 15) ?></a>
                    </div>
                </div>
                <span class="header-avatar avatar avatar-violet" title="<?= gbdbui_e($username) ?>"><?= gbdbui_e($initials) ?></span>
            </div>
        </header>
        <main id="main-content" class="main-content" tabindex="-1">
            <nav class="task-path" aria-label="Brotkrümelnavigation"><a href="<?= gbdbui_e(gbdbui_url('dashboard')) ?>">Startseite</a><?php if ($activeGroup !== 'Start') : ?><span aria-hidden="true">›</span><span><?= gbdbui_e($activeGroup) ?></span><?php endif; ?><span aria-hidden="true">›</span><strong><?= gbdbui_e($title) ?></strong></nav>
            <div class="page-heading"><div class="page-heading-text"><div class="eyebrow"><span class="eyebrow-dot"></span>GBDB WORKSPACE<?= isset($options['eyebrow']) ? ' · ' . gbdbui_e(strtoupper((string) $options['eyebrow'])) : '' ?> <span class="demo-badge"><?= gbdbui_e($environment) ?></span></div><h1><?= gbdbui_e($title) ?></h1><?php if ($subtitle !== '') : ?><p><?= gbdbui_e($subtitle) ?></p><?php endif; ?></div><?php if (($options['actions'] ?? '') !== '') : ?><div class="page-actions"><?= $options['actions'] ?></div><?php endif; ?></div>
<?php
    gbdbui_render_flashes();
    gbdbui_update_banner($active);
}

/**
 * Ende einer Seite im Intranet-Layout.
 */
function gbdbui_layout_end() :void {
    ?>
        </main>
    </div>
</div>
<dialog id="command-dialog" class="command-dialog" aria-label="Globale Navigation und Suche"><div class="command-header"><?= gbdbui_icon('search', 21) ?><input id="command-input" type="search" placeholder="Bereiche durchsuchen …" autocomplete="off" aria-label="Bereiche durchsuchen"><kbd>ESC</kbd></div><div id="command-results" class="command-results"></div><div class="command-footer"><span>↑↓ Navigieren</span><span>↵ Öffnen</span></div></dialog>
<div class="toast-region" id="toast-region" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
<?php
}

/**
 * @deprecated Früherer Navigationsbalken; Seiten nutzen gbdbui_layout_start().
 */
function gbdbui_nav(string $active = '') :void {
    echo '<nav class="gbdbui-legacy-nav" aria-label="GBDB">';

    foreach (gbdbui_navigation() as $items) {
        foreach ($items as [$tool, , $label]) {
            if (gbdbui_can_tool($tool)) {
                echo '<a' . ($tool === $active ? ' class="active"' : '') . ' href="' . gbdbui_e(gbdbui_url($tool)) . '">' . gbdbui_e($label) . '</a>';
            }
        }
    }

    echo '</nav>';
}

function gbdbui_flash(string $type, string $text) :void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['gbdbui_flash'][] = ['type' => $type, 'text' => $text];
}

function gbdbui_flashes() :array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $items = $_SESSION['gbdbui_flash'] ?? [];
    unset($_SESSION['gbdbui_flash']);

    return is_array($items) ? $items : [];
}

function gbdbui_render_flashes() :void {
    foreach (gbdbui_flashes() as $f) {
        $type = (string) ($f['type'] ?? '');
        $class = $type === 'ok' ? 'notice-success' : ($type === 'bad' ? 'notice-error' : 'notice-info');
        echo '<div class="notice ' . $class . ' gbdbui-flash ' . gbdbui_e($type) . '" role="status">' . gbdbui_e($f['text'] ?? '') . '</div>';
    }
}

function gbdbui_can_tool(string $tool) :bool {
    if (!class_exists('GreenQLUIv2Helper')) {
        return false;
    }

    if (!GreenQLUIv2Helper::loggedIn()) {
        return false;
    }

    return GreenQLUIv2Helper::canUseTool($tool);
}

function gbdbui_cache_get(string $key, int $ttl = 10) :mixed {
    static $local = [];

    if (array_key_exists($key, $local)) {
        return $local[$key];
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $cache = $_SESSION['gbdbui_cache'][$key] ?? null;

    if (is_array($cache) && (time() - (int) ($cache['time'] ?? 0)) <= $ttl) {
        $local[$key] = $cache['value'] ?? null;

        return $local[$key];
    }

    return null;
}

function gbdbui_cache_set(string $key, mixed $value) :mixed {
    static $local = [];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $local[$key] = $value;
    $_SESSION['gbdbui_cache'][$key] = ['time' => time(), 'value' => $value];

    return $value;
}

function gbdbui_list_dbs_cached(?string $instance = null, int $ttl = 10) :array {
    if (!class_exists('GBDB')) {
        return [];
    }

    $instance = $instance !== null && $instance !== '' ? $instance : GBDB::getInstance();
    $key = 'dbs_' . hash('sha256', (string) $instance);
    $cached = gbdbui_cache_get($key, $ttl);

    if (is_array($cached)) {
        return $cached;
    }

    $old = GBDB::getInstance();
    try {
        GBDB::setInstance((string) $instance);
        $items = GBDB::listDBs();
    } finally {
        GBDB::setInstance($old);
    }

    return gbdbui_cache_set($key, is_array($items) ? array_values($items) : []);
}

function gbdbui_list_tables_cached(string $db, ?string $instance = null, int $ttl = 10) :array {
    if (!class_exists('GBDB')) {
        return [];
    }

    $instance = $instance !== null && $instance !== '' ? $instance : GBDB::getInstance();
    $key = 'tables_' . hash('sha256', (string) $instance . '|' . $db);
    $cached = gbdbui_cache_get($key, $ttl);

    if (is_array($cached)) {
        return $cached;
    }

    $old = GBDB::getInstance();
    try {
        GBDB::setInstance((string) $instance);
        $items = GBDB::listTables($db);
    } finally {
        GBDB::setInstance($old);
    }

    return gbdbui_cache_set($key, is_array($items) ? array_values($items) : []);
}

function gbdbui_cache_clear() :void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    unset($_SESSION['gbdbui_cache']);
}

function gbdbui_dir_size(string $path) :int {
    if (is_file($path)) {
        return (int) @filesize($path);
    }

    if (!is_dir($path)) {
        return 0;
    }

    $cacheFile = rtrim(sys_get_temp_dir(), '/') . '/gbdbui_size_' . hash('sha256', $path) . '.json';
    $cache = is_file($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;

    if (is_array($cache) && (time() - (int) ($cache['time'] ?? 0)) < 20) {
        return (int) ($cache['size'] ?? 0);
    }

    $size = 0;
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $size += (int) $file->getSize();
            }
        }
    } catch (Throwable) {
        return (int) ($cache['size'] ?? 0);
    }

    @file_put_contents($cacheFile, json_encode(['time' => time(), 'size' => $size]), LOCK_EX);

    return $size;
}

function gbdbui_bytes(int $bytes) :string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $v = (float) $bytes;
    $i = 0;

    while ($v >= 1024 && $i < count($units) - 1) {
        $v /= 1024;
        $i++;
    }

    return number_format($v, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
}

function gbdbui_copy_tree(string $from, string $to) :array {
    $report = ['ok' => true, 'files' => 0, 'errors' => []];

    if (!is_dir($from)) {
        return ['ok' => false, 'files' => 0, 'errors' => ['Quelle fehlt: ' . $from]];
    }

    if (!is_dir($to)) {
        @mkdir($to, 0750, true);
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $target = $to . '/' . ltrim(str_replace($from, '', $item->getPathname()), '/');

        if ($item->isDir()) {
            if (!is_dir($target)) {
                @mkdir($target, 0750, true);
            }
            continue;
        }

        if (!@copy($item->getPathname(), $target)) {
            $report['ok'] = false;
            $report['errors'][] = 'Konnte nicht kopieren: ' . $item->getPathname();
        } else {
            $report['files']++;
        }
    }

    return $report;
}

function gbdbui_delete_tree(string $dir) :bool {
    if (!is_dir($dir)) {
        return true;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }

    return true;
}

function gbdbui_require_tool(string $tool) :void {
    if (!gbdbui_can_tool($tool)) {
        $switch = class_exists('GreenQLUIv2Helper') ? GreenQLUIv2Helper::disabledToolSwitch($tool) : '';
        http_response_code(403);

        if ($switch !== '') {
            gbdbui_layout_start('dashboard', 'Werkzeug deaktiviert', 'Dieses Werkzeug ist in der Framework-Konfiguration ausgeschaltet.');
            echo '<section class="panel padded"><h2>In .framework.env.php deaktiviert</h2><p>Zum Freischalten in <code>gbdb_framework/.config/.framework.env.php</code> in der Konstante <code>UI</code> den Wert <code>' . gbdbui_e($switch) . '</code> auf <code>true</code> setzen. Der Schalter lässt sich bewusst nur direkt in der Datei ändern.</p><a class="btn btn-primary" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">Zurück zur Übersicht</a></section>';
        } else {
            gbdbui_layout_start('dashboard', 'Kein Zugriff', 'Deine aktuelle Rolle darf diesen Bereich nicht öffnen.');
            echo '<section class="panel padded"><h2>Bereich gesperrt</h2><p>Ein Administrator kann dir das Werkzeug oder die nötige Berechtigung in der Benutzerverwaltung freigeben.</p><a class="btn btn-primary" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">Zurück zur Übersicht</a></section>';
        }

        gbdbui_layout_end();

        exit;
    }
}
