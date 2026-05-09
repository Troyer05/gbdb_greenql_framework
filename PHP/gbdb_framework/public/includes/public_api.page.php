<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('public_api');

function gbdbui_public_api_redirect(): void {
    header('Location: ' . gbdbui_url('public_api'));

    exit;
}

function gbdbui_public_api_rights_from_post(): string {
    $rights = $_POST['rights'] ?? [];
    $items = [];

    if (is_array($rights)) {
        foreach ($rights as $right) {
            $right = trim((string) $right);

            if ($right !== '') {
                $items[] = $right;
            }
        }
    }

    $custom = trim((string) ($_POST['custom_rights'] ?? ''));

    if ($custom !== '') {
        foreach ((preg_split('/[\s,]+/', $custom) ?: []) as $right) {
            $right = trim((string) $right);

            if ($right !== '') {
                $items[] = $right;
            }
        }
    }

    return implode(',', array_unique($items));
}

function gbdbui_public_api_rights_badges(string $rights): string {
    $decoded = json_decode($rights, true);
    $items = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $rights);
    $items = is_array($items) ? $items : [];
    $html = '';

    foreach ($items as $item) {
        if (!is_scalar($item)) {
            continue;
        }

        $item = trim((string) $item);

        if ($item === '') {
            continue;
        }

        $html .= '<span class="gb-chip">' . gbdbui_e($item) . '</span>';
    }

    return $html !== '' ? $html : '<span class="gb-chip gb-chip-muted">no rights</span>';
}

function gbdbui_public_api_datetime_input(string $value): string {
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $ts = strtotime($value);

    if ($ts === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $ts);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['public_api_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Invalid security token.');
        gbdbui_public_api_redirect();
    }

    $action = (string) $_POST['public_api_action'];

    if ($action === 'create_key') {
        $key = GreenQLUIv2Helper::createPublicApiKey(
            (string) ($_POST['name'] ?? ''),
            gbdbui_public_api_rights_from_post(),
            (string) ($_POST['exp'] ?? ''),
            (string) ($_POST['active'] ?? '1')
        );

        gbdbui_flash($key !== '' ? 'ok' : 'bad', $key !== '' ? 'API key created. Copy now: ' . $key : 'Could not create API key.');
        gbdbui_public_api_redirect();
    }

    if ($action === 'update_key') {
        $ok = GreenQLUIv2Helper::updatePublicApiKey((int) ($_POST['id'] ?? 0), [
            'name' => (string) ($_POST['name'] ?? ''),
            'rights' => gbdbui_public_api_rights_from_post(),
            'exp' => (string) ($_POST['exp'] ?? ''),
            'active' => (string) ($_POST['active'] ?? '0')
        ]);

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'API key updated.' : 'Could not update API key.');
        gbdbui_public_api_redirect();
    }

    if ($action === 'delete_key') {
        $ok = GreenQLUIv2Helper::deletePublicApiKey((int) ($_POST['id'] ?? 0));
        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'API key deleted.' : 'Could not delete API key.');
        gbdbui_public_api_redirect();
    }
}

$csrf = GreenQLUIv2Helper::csrf();
$endpoint = GreenQLUIv2Helper::publicApiEndpoint();
$keys = GreenQLUIv2Helper::publicApiKeys();
$modules = GreenQLUIv2Helper::publicApiModules();
$fetchLogs = GreenQLUIv2Helper::publicApiLogs('fetches', 25);
$keyLogs = GreenQLUIv2Helper::publicApiLogs('key_log', 25);
$activeKeys = 0;
$expiredKeys = 0;
$knownRights = ['*', 'gbdb-read', 'gbdb-write', 'gbdb-gql', 'gbdb-admin', 'read'];

foreach ($modules as $module) {
    foreach (($module['rights'] ?? []) as $right) {
        if (is_scalar($right) && !in_array((string) $right, $knownRights, true)) {
            $knownRights[] = (string) $right;
        }
    }
}

foreach ($keys as $keyData) {
    if ((string) ($keyData['active'] ?? '0') === '1') {
        $activeKeys++;
    }

    if (!empty($keyData['exp']) && strtotime((string) $keyData['exp']) < time()) {
        $expiredKeys++;
    }
}

$exampleJson = json_encode([
    'key' => 'YOUR_API_KEY',
    'do' => 'gbdb.info'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI Public API</title>
    <link rel="stylesheet" href="gbdb_framework/public/css/gbdb_ui.css?v=2026.11">
</head>

<body class="gbdbui-dashboard gbdbui-pro public-api-body">
    <?php gbdbui_nav('public_api'); ?>
    <main class="gbdbui-wide">
        <?php gbdbui_render_flashes(); ?>

        <section class="gbdbui-hero public-api-hero">
            <div>
                <p class="gbdbui-kicker">Public API Center</p>
                <h1>Keys, rights, modules & logs</h1>
                <p>
                    Manage API keys for external clients, assign granular rights like <code>gbdb-read</code>,
                    <code>gbdb-write</code>, <code>gbdb-gql</code> and <code>gbdb-admin</code>, inspect loaded modules
                    and keep an eye on request activity.
                </p>
            </div>
            <div class="public-api-endpoint-card">
                <span>Endpoint</span>
                <code><?= gbdbui_e($endpoint) ?></code>
                <button type="button" data-copy="<?= gbdbui_e($endpoint) ?>">Copy endpoint</button>
            </div>
        </section>

        <section class="users-overview-grid public-api-stat-grid">
            <article class="users-stat-card">
                <span>API keys</span>
                <strong><?= count($keys) ?></strong>
                <small><?= $activeKeys ?> active · <?= $expiredKeys ?> expired</small>
            </article>
            <article class="users-stat-card is-accent">
                <span>Modules</span>
                <strong><?= count($modules) ?></strong>
                <small><?= count($knownRights) ?> known rights</small>
            </article>
            <article class="users-stat-card">
                <span>Requests</span>
                <strong><?= count($fetchLogs) ?></strong>
                <small>latest public fetch log entries</small>
            </article>
        </section>

        <section class="gbdbui-page-grid public-api-grid">
            <section class="gbdbui-panel span-5">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>Create API key</h3>
                        <p>Generate a key, choose rights and optionally set an expiration time.</p>
                    </div>
                </div>

                <form method="post" class="gbdbui-form-grid public-api-form">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                    <input type="hidden" name="public_api_action" value="create_key">

                    <label class="field-8">
                        <span>Name</span>
                        <input name="name" placeholder="museumqr-website" required>
                    </label>

                    <label class="field-4">
                        <span>Status</span>
                        <select name="active">
                            <option value="1">active</option>
                            <option value="0">inactive</option>
                        </select>
                    </label>

                    <label class="field-12">
                        <span>Expires</span>
                        <input name="exp" type="datetime-local">
                    </label>

                    <div class="field-12 public-api-right-picker">
                        <span>Rights</span>
                        <div>
                            <?php foreach ($knownRights as $right): ?>
                                <label><input type="checkbox" name="rights[]" value="<?= gbdbui_e($right) ?>"> <?= gbdbui_e($right) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <label class="field-12">
                        <span>Custom rights</span>
                        <input name="custom_rights" placeholder="custom-read custom-write">
                    </label>

                    <div class="field-12">
                        <button class="primary" type="submit">Create key</button>
                    </div>
                </form>
            </section>

            <section class="gbdbui-panel span-7">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>Quick test</h3>
                        <p>Use this request to test if the API entry and module loading work.</p>
                    </div>
                </div>

                <div class="public-api-code-card">
                    <pre>curl -X POST <?= gbdbui_e($endpoint) ?> \
  -H 'Content-Type: application/json' \
  -d '<?= gbdbui_e((string) $exampleJson) ?>'</pre>
                    <button type="button" data-copy="curl -X POST <?= gbdbui_e($endpoint) ?> -H 'Content-Type: application/json' -d '<?= gbdbui_e((string) $exampleJson) ?>'">Copy curl</button>
                </div>

                <div class="public-api-note-grid">
                    <div>
                        <strong>Action format</strong>
                        <span><code>do</code> is <code>module.action</code>, for example <code>gbdb.get</code>.</span>
                    </div>
                    <div>
                        <strong>Rights model</strong>
                        <span><code>*</code> grants everything. <code>gbdb.*</code> grants matching dotted rights.</span>
                    </div>
                </div>
            </section>

            <section class="gbdbui-panel span-12 public-api-log-panel">
                <details class="public-api-log-details">
                    <summary>
                        <span>
                            <strong>Recent requests</strong>
                            <small>Latest 25 fetch log rows written by PublicAPI::init().</small>
                        </span>
                        <em><?= count($fetchLogs) ?> entries</em>
                    </summary>

                    <div class="gbdbui-table-wrap public-api-log-table">
                        <table class="gbdbui-table">
                            <thead><tr><th>Time</th><th>Action</th><th>IP</th><th>Key</th></tr></thead>
                            <tbody>
                                <?php foreach ($fetchLogs as $log): ?>
                                    <tr>
                                        <td><?= gbdbui_e($log['datetime'] ?? '') ?></td>
                                        <td><code><?= gbdbui_e($log['action'] ?? '') ?></code></td>
                                        <td><?= gbdbui_e($log['ip'] ?? '') ?></td>
                                        <td><code><?= gbdbui_e(GreenQLUIv2Helper::maskPublicApiKey((string) ($log['key'] ?? ''))) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($fetchLogs)): ?><tr><td colspan="4">No request logs yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <section class="gbdbui-panel span-12 public-api-log-panel">
                <details class="public-api-log-details">
                    <summary>
                        <span>
                            <strong>Security log</strong>
                            <small>Latest 25 missing-right and key-related security events.</small>
                        </span>
                        <em><?= count($keyLogs) ?> entries</em>
                    </summary>

                    <div class="gbdbui-table-wrap public-api-log-table">
                        <table class="gbdbui-table">
                            <thead><tr><th>Time</th><th>Action</th><th>User</th><th>Key</th></tr></thead>
                            <tbody>
                                <?php foreach ($keyLogs as $log): ?>
                                    <tr>
                                        <td><?= gbdbui_e($log['datetime'] ?? '') ?></td>
                                        <td><?= gbdbui_e($log['action'] ?? '') ?></td>
                                        <td><?= gbdbui_e($log['user'] ?? '') ?></td>
                                        <td><code><?= gbdbui_e(GreenQLUIv2Helper::maskPublicApiKey((string) ($log['key'] ?? ''))) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($keyLogs)): ?><tr><td colspan="4">No security events yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <section class="gbdbui-panel span-12">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>API keys</h3>
                        <p>Keys are shown masked by default. Open details to reveal and copy a key.</p>
                    </div>
                </div>

                <?php if (empty($keys)): ?>
                    <div class="empty-state"><strong>No API keys yet.</strong><span>Create the first key on the left.</span></div>
                <?php else: ?>
                    <div class="public-api-key-list">
                        <?php foreach ($keys as $row): ?>
                            <?php
                            $rawKey = (string) ($row['key'] ?? '');
                            $rights = (string) ($row['rights'] ?? '');
                            $expired = !empty($row['exp']) && strtotime((string) $row['exp']) < time();
                            ?>
                            <article class="public-api-key-card <?= $expired ? 'is-expired' : '' ?>">
                                <div class="public-api-key-main">
                                    <div>
                                        <h4><?= gbdbui_e($row['name'] ?? 'API key') ?></h4>
                                        <p><code><?= gbdbui_e(GreenQLUIv2Helper::maskPublicApiKey($rawKey)) ?></code></p>
                                        <div class="public-api-chip-row">
                                            <span class="gb-chip <?= (string) ($row['active'] ?? '0') === '1' ? '' : 'gb-chip-muted' ?>"><?= (string) ($row['active'] ?? '0') === '1' ? 'active' : 'inactive' ?></span>
                                            <?php if (!empty($row['exp'])): ?><span class="gb-chip <?= $expired ? 'gb-chip-muted' : '' ?>">exp <?= gbdbui_e($row['exp']) ?></span><?php endif; ?>
                                            <?= gbdbui_public_api_rights_badges($rights) ?>
                                        </div>
                                    </div>
                                    <details>
                                        <summary>Reveal</summary>
                                        <input readonly value="<?= gbdbui_e($rawKey) ?>">
                                        <button type="button" data-copy="<?= gbdbui_e($rawKey) ?>">Copy key</button>
                                    </details>
                                </div>

                                <form method="post" class="gbdbui-form-grid public-api-key-edit">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                    <input type="hidden" name="public_api_action" value="update_key">
                                    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">

                                    <label class="field-3"><span>Name</span><input name="name" value="<?= gbdbui_e($row['name'] ?? '') ?>"></label>
                                    <label class="field-2"><span>Status</span><select name="active"><option value="1" <?= (string) ($row['active'] ?? '0') === '1' ? 'selected' : '' ?>>active</option><option value="0" <?= (string) ($row['active'] ?? '0') !== '1' ? 'selected' : '' ?>>inactive</option></select></label>
                                    <label class="field-3"><span>Expires</span><input name="exp" type="datetime-local" value="<?= gbdbui_e(gbdbui_public_api_datetime_input((string) ($row['exp'] ?? ''))) ?>"></label>
                                    <label class="field-4"><span>Rights</span><input name="custom_rights" value="<?= gbdbui_e(implode(',', json_decode($rights, true) ?: [])) ?>"></label>
                                    <div class="field-12 public-api-key-actions">
                                        <button type="submit">Save key</button>
                                    </div>
                                </form>

                                <form method="post" onsubmit="return confirm('Delete this API key?');" class="public-api-delete-form">
                                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                    <input type="hidden" name="public_api_action" value="delete_key">
                                    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                    <button class="danger" type="submit">Delete key</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="gbdbui-panel span-12">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>Loaded modules</h3>
                        <p>Core module plus all module files from public_api_modules.</p>
                    </div>
                </div>

                <?php if (empty($modules)): ?>
                    <div class="empty-state"><strong>No modules loaded.</strong><span>Check includes/public_api and module files.</span></div>
                <?php else: ?>
                    <div class="public-api-module-list">
                        <?php foreach ($modules as $module): ?>
                            <details class="public-api-module" open>
                                <summary><strong><?= gbdbui_e($module['name'] ?? '') ?></strong><span><?= gbdbui_e($module['class'] ?? '') ?> · <?= gbdbui_e($module['file'] ?? '') ?></span></summary>
                                <div class="public-api-chip-row"><?= gbdbui_public_api_rights_badges(json_encode($module['rights'] ?? [])) ?></div>
                                <?php if (!empty($module['actions'])): ?>
                                    <div class="gbdbui-table-wrap public-api-actions-table">
                                        <table class="gbdbui-table">
                                            <thead><tr><th>Action</th><th>Required rights</th></tr></thead>
                                            <tbody>
                                                <?php foreach (($module['actions'] ?? []) as $action => $rights): ?>
                                                    <tr><td><code><?= gbdbui_e($module['name'] . '.' . $action) ?></code></td><td><?= gbdbui_public_api_rights_badges(json_encode($rights)) ?></td></tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            </section>
    </main>

    <script>
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.getAttribute('data-copy') || '').then(function () {
                var old = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = old; }, 900);
            });
        });
    });
    </script>
</body>

</html>
