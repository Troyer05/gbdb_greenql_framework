<?php
declare(strict_types=1);

function gbdbui_e(mixed $value): string {
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function gbdbui_url(string $tool = '', array $params = []): string {
    $base = (string) ($_SERVER['SCRIPT_NAME'] ?? 'gbdb_ui.php');

    if ($base === '')
        $base = 'gbdb_ui.php';

    if ($tool !== '') {
        $params = ['tool' => $tool] + $params;
    }

    $query = http_build_query($params);

    return $base . ($query !== '' ? '?' . $query : '');
}

function gbdbui_nav(string $active = ''): void {
    $items = [
        'dashboard' => 'Dashboard',
        'greenql_v2' => 'GreenQL Studio',
        'php_exec' => 'PHP Exec',
        'users' => 'Benutzer',
        'public_api' => 'Public API',
        'migration' => 'Migration',
        'cryption' => 'Crypto',
        'optimize' => 'Optimize',
        'env' => 'ENV',
        'plugins' => 'Plugins',
        'gql_scripts' => 'GQL Scripts',
        'backup' => 'Backup',
        'import_export' => 'Import/Export',
        'enterprise' => 'Enterprise',
        'monitoring' => 'Monitoring',
        'reinstall' => 'Re-Install',
    ];

    $user = class_exists('GreenQLUIv2Helper') ? GreenQLUIv2Helper::user() : [];

    echo '<div class="gbdbui-topbar">';
    echo '<a class="gbdbui-brand" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">GBDB UI</a>';
    echo '<nav>';

    foreach ($items as $key => $label) {
        if (class_exists('GreenQLUIv2Helper') && GreenQLUIv2Helper::loggedIn() && !gbdbui_can_tool($key)) {
            continue;
        }

        $class = $key === $active ? ' class="active"' : '';
        $params = [];

        if ($key === 'import_export' && isset($_GET['instance'])) {
            $params['instance'] = (string) $_GET['instance'];
        }

        echo '<a' . $class . ' href="' . gbdbui_e(gbdbui_url($key, $params)) . '">' . gbdbui_e($label) . '</a>';
    }

    echo '</nav>';

    if (!empty($user)) {
        echo '<form class="gbdbui-logout" method="post" action="' . gbdbui_e(gbdbui_url($active ?: 'dashboard')) . '">';
        echo '<input type="hidden" name="csrf" value="' . gbdbui_e(GreenQLUIv2Helper::csrf()) . '">';
        echo '<input type="hidden" name="gbdbui_action" value="logout">';
        echo '<span>' . gbdbui_e($user['username'] ?? '') . ' · ' . gbdbui_e($user['role'] ?? '') . '</span>';
        echo '<button type="submit">Logout</button>';
        echo '</form>';
    }

    echo '</div>';
}

function gbdbui_flash(string $type, string $text): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['gbdbui_flash'][] = ['type' => $type, 'text' => $text];
}

function gbdbui_flashes(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $items = $_SESSION['gbdbui_flash'] ?? [];
    unset($_SESSION['gbdbui_flash']);

    return is_array($items) ? $items : [];
}

function gbdbui_render_flashes(): void {
    foreach (gbdbui_flashes() as $f) {
        echo '<div class="gbdbui-flash ' . gbdbui_e($f['type'] ?? '') . '">' . gbdbui_e($f['text'] ?? '') . '</div>';
    }

}

function gbdbui_can_tool(string $tool): bool {
    if (!class_exists('GreenQLUIv2Helper')) {
        return false;
    }

    if (!GreenQLUIv2Helper::loggedIn()) {
        return false;
    }

    return GreenQLUIv2Helper::canUseTool($tool);
}

function gbdbui_cache_get(string $key, int $ttl = 10): mixed {
    static $local = [];

    if (array_key_exists($key, $local))

        return $local[$key];

    if (session_status() !== PHP_SESSION_ACTIVE)
        @session_start();
    $cache = $_SESSION['gbdbui_cache'][$key] ?? null;

    if (is_array($cache) && (time() - (int) ($cache['time'] ?? 0)) <= $ttl) {
        $local[$key] = $cache['value'] ?? null;

        return $local[$key];
    }

    return null;
}

function gbdbui_cache_set(string $key, mixed $value): mixed {
    static $local = [];

    if (session_status() !== PHP_SESSION_ACTIVE)
        @session_start();
    $local[$key] = $value;
    $_SESSION['gbdbui_cache'][$key] = ['time' => time(), 'value' => $value];

    return $value;
}

function gbdbui_list_dbs_cached(?string $instance = null, int $ttl = 10): array {
    if (!class_exists('GBDB'))

        return [];

    $instance = $instance !== null && $instance !== '' ? $instance : GBDB::getInstance();
    $key = 'dbs_' . hash('sha256', (string) $instance);
    $cached = gbdbui_cache_get($key, $ttl);

    if (is_array($cached))

        return $cached;

    $old = GBDB::getInstance();
    try {
        GBDB::setInstance((string) $instance);
        $items = GBDB::listDBs();
    } finally {
        GBDB::setInstance($old);
    }

    return gbdbui_cache_set($key, is_array($items) ? array_values($items) : []);
}

function gbdbui_list_tables_cached(string $db, ?string $instance = null, int $ttl = 10): array {
    if (!class_exists('GBDB'))

        return [];

    $instance = $instance !== null && $instance !== '' ? $instance : GBDB::getInstance();
    $key = 'tables_' . hash('sha256', (string) $instance . '|' . $db);
    $cached = gbdbui_cache_get($key, $ttl);

    if (is_array($cached))

        return $cached;

    $old = GBDB::getInstance();
    try {
        GBDB::setInstance((string) $instance);
        $items = GBDB::listTables($db);
    } finally {
        GBDB::setInstance($old);
    }

    return gbdbui_cache_set($key, is_array($items) ? array_values($items) : []);
}

function gbdbui_cache_clear(): void {
    if (session_status() !== PHP_SESSION_ACTIVE)
        @session_start();
    unset($_SESSION['gbdbui_cache']);
}

function gbdbui_dir_size(string $path): int {
    if (is_file($path))

        return (int) @filesize($path);

    if (!is_dir($path))

        return 0;

    $cacheFile = rtrim(sys_get_temp_dir(), '/') . '/gbdbui_size_' . hash('sha256', $path) . '.json';
    $cache = is_file($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;

    if (is_array($cache) && (time() - (int) ($cache['time'] ?? 0)) < 20) {
        return (int) ($cache['size'] ?? 0);
    }

    $size = 0;
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile())
                $size += (int) $file->getSize();
        }

    } catch (Throwable) {
        return (int) ($cache['size'] ?? 0);
    }

    @file_put_contents($cacheFile, json_encode(['time' => time(), 'size' => $size]), LOCK_EX);

    return $size;
}

function gbdbui_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $v = (float) $bytes;
    $i = 0;

    while ($v >= 1024 && $i < count($units) - 1) {
        $v /= 1024;
        $i++;
    }

    return number_format($v, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
}

function gbdbui_copy_tree(string $from, string $to): array {
    $report = ['ok' => true, 'files' => 0, 'errors' => []];

    if (!is_dir($from))

        return ['ok' => false, 'files' => 0, 'errors' => ['Quelle fehlt: ' . $from]];

    if (!is_dir($to))
        @mkdir($to, 0777, true);

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $target = $to . '/' . ltrim(str_replace($from, '', $item->getPathname()), '/');

        if ($item->isDir()) {
            if (!is_dir($target))
                @mkdir($target, 0777, true);
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

function gbdbui_delete_tree(string $dir): bool {
    if (!is_dir($dir))

        return true;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        if ($item->isDir())
            @rmdir($item->getPathname());
        else
            @unlink($item->getPathname());
    }

    return true;
}

function gbdbui_require_tool(string $tool): void {
    if (!gbdbui_can_tool($tool)) {
        http_response_code(403);
        echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kein Zugriff</title><link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css"></head><body class="gbdbui-dashboard">';
        gbdbui_nav('dashboard');
        echo '<main><section class="gbdbui-hero"><h1>Kein Zugriff</h1><p>Deine aktuelle Rolle darf diesen Bereich nicht öffnen.</p><a class="gbdbui-btn" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">Zurück zum Dashboard</a></section></main></body></html>';

        exit;
    }

}
