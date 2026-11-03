<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('users');

function gbdbui_users_redirect() :void {
    header('Location: ' . gbdbui_url('users'));

    exit;
}

function gbdbui_csv_items(string $value) :array {
    $value = trim($value);

    if ($value === '') {
        return [];
    }

    $parts = preg_split('/\s*,\s*/', $value) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn($item) :bool => $item !== ''));

    return $parts;
}

function gbdbui_render_chip_group(string $value, string $fallback = '—') :string {
    $items = gbdbui_csv_items($value);

    if ($items === []) {
        return '<span class="gb-chip gb-chip-muted">' . gbdbui_e($fallback) . '</span>';
    }

    $html = '';

    foreach ($items as $item) {
        $html .= '<span class="gb-chip">' . gbdbui_e($item) . '</span>';
    }

    return $html;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['user_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Invalid security token.');
        gbdbui_users_redirect();
    }

    $action = (string) $_POST['user_action'];

    if ($action === 'create_user') {
        $ok = GreenQLUIv2Helper::createUser(
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['role'] ?? 'viewer'),
            (string) ($_POST['instances'] ?? '*'),
            (string) ($_POST['bases'] ?? '*'),
            (string) ($_POST['language'] ?? 'en'),
            (string) ($_POST['permissions'] ?? ''),
            (string) ($_POST['tools'] ?? '')
        );

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'User created.' : 'Could not create user.');
        gbdbui_users_redirect();
    }

    if ($action === 'update_user') {
        $ok = GreenQLUIv2Helper::updateUser(
            (int) ($_POST['id'] ?? 0),
            [
                'username' => (string) ($_POST['username'] ?? ''),
                'role' => (string) ($_POST['role'] ?? 'viewer'),
                'active' => (string) ($_POST['active'] ?? '0'),
                'instances' => (string) ($_POST['instances'] ?? '*'),
                'bases' => (string) ($_POST['bases'] ?? '*'),
                'language' => (string) ($_POST['language'] ?? 'en'),
                'permissions' => (string) ($_POST['permissions'] ?? ''),
                'tools' => (string) ($_POST['tools'] ?? ''),
            ]
        );

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'User updated.' : 'Could not update user.');
        gbdbui_users_redirect();
    }

    if ($action === 'reset_password') {
        $password = (string) ($_POST['password'] ?? '');
        $ok = $password === (string) ($_POST['password2'] ?? '')
            && GreenQLUIv2Helper::resetPassword((int) ($_POST['id'] ?? 0), $password);

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Password reset.' : 'Password reset failed.');
        gbdbui_users_redirect();
    }

    if ($action === 'delete_user') {
        $ok = GreenQLUIv2Helper::deleteUser((int) ($_POST['id'] ?? 0));
        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'User deleted.' : 'Could not delete user.');
        gbdbui_users_redirect();
    }

    if ($action === 'ack_permission_migration') {
        $ok = GreenQLUIv2Helper::acknowledgePermissionMigration();
        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Hinweis zur Rechte-Umstellung ausgeblendet.' : 'Nur Admins können den Hinweis ausblenden.');
        gbdbui_users_redirect();
    }
}

$users = GreenQLUIv2Helper::users();
$csrf = GreenQLUIv2Helper::csrf();
$isAdmin = GreenQLUIv2Helper::isAdmin();
// Admin-Konten und die Rolle "admin" verwalten nur Admins (serverseitig in GreenQLUIv2Helper geprüft).
$roles = $isAdmin ? ['viewer', 'editor', 'maintainer', 'admin'] : ['viewer', 'editor', 'maintainer'];
$migration = GreenQLUIv2Helper::permissionMigrationNotice();
$langs = [
    'en' => 'English',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español',
    'it' => 'Italiano',
    'nl' => 'Nederlands',
];

$totalUsers = count($users);
$activeUsers = 0;
$disabledUsers = 0;
$roleCounter = [];
$langCounter = [];

foreach ($users as $user) {
    $isActive = (string) ($user['active'] ?? '1') === '1';
    $role = (string) ($user['role'] ?? 'viewer');
    $lang = (string) ($user['language'] ?? 'en');

    if ($isActive) {
        $activeUsers++;
    } else {
        $disabledUsers++;
    }

    $roleCounter[$role] = ($roleCounter[$role] ?? 0) + 1;
    $langCounter[$lang] = ($langCounter[$lang] ?? 0) + 1;
}

arsort($roleCounter);
arsort($langCounter);
$topRole = (string) array_key_first($roleCounter);
$topLang = (string) array_key_first($langCounter);
?>
<?php gbdbui_layout_start('users', 'Benutzer & Rechte', 'UI-Benutzer, Rollen, Berechtigungen (read, write, structure, maintenance, jobs, backup, config, dev, users, danger) und Werkzeugfreigaben verwalten.', ['eyebrow' => 'Zugriff']); ?>

        <?php if ($migration !== null) : ?>
            <section class="gbdbui-panel gbdbui-migration-notice">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h2>Rechte-Umstellung: <?= count($migration['accounts']) === 1 ? '1 Konto' : count($migration['accounts']) . ' Konten' ?> prüfen</h2>
                        <p>Ein leeres Rechtefeld bedeutete bisher <strong>alle Rechte</strong>, jetzt <strong>nur die Rechte der Rolle</strong>. Damit sich nichts unbemerkt ändert, haben diese Nicht-Admin-Konten am <?= gbdbui_e($migration['migrated_at']) ?> ausdrücklich <code>*</code> erhalten. Prüfe, ob sie wirklich alle Rechte brauchen, und trage sonst gezielt Rechte ein oder leere das Feld.</p>
                    </div>
                    <span class="gbdbui-pill warn">Zugriff unverändert</span>
                </div>
                <div class="chip-stack">
                    <?php foreach ($migration['accounts'] as $account) : ?>
                        <span class="gb-chip"><?= gbdbui_e((string) ($account['username'] ?? '')) ?> · <?= gbdbui_e((string) ($account['role'] ?? '')) ?><?= (string) ($account['active'] ?? '1') === '1' ? '' : ' · deaktiviert' ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if ($isAdmin) : ?>
                    <form method="post" class="gbdbui-action-row">
                        <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                        <input type="hidden" name="user_action" value="ack_permission_migration">
                        <button class="secondary" type="submit">Geprüft – Hinweis ausblenden</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="gbdbui-hero-stats">
            <div class="users-overview-grid">
                <article class="users-stat-card">
                    <span>Total users</span>
                    <strong><?= $totalUsers ?></strong>
                    <small><?= $activeUsers ?> active · <?= $disabledUsers ?> disabled</small>
                </article>
                <article class="users-stat-card is-accent">
                    <span>Primary role</span>
                    <strong><?= gbdbui_e($topRole !== '' ? $topRole : '—') ?></strong>
                    <small><?= $topRole !== '' ? (int) ($roleCounter[$topRole] ?? 0) . ' accounts' : 'No users yet' ?></small>
                </article>
                <article class="users-stat-card">
                    <span>Languages</span>
                    <strong><?= count($langCounter) ?></strong>
                    <small><?= gbdbui_e($topLang !== '' ? strtoupper($topLang) : '—') ?> most used</small>
                </article>
            </div>
        </section>

        <section class="users-admin-shell">
            <section class="gbdbui-panel user-create-card">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>Create user</h3>
                        <p>Create a new GBDB UI account with scoped instance/base access and tool restrictions.</p>
                    </div>
                </div>

                <form method="post" class="user-create-grid user-form-shell">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                    <input type="hidden" name="user_action" value="create_user">

                    <label>
                        <span>Username</span>
                        <input name="username" required>
                    </label>

                    <label>
                        <span>Password</span>
                        <input name="password" type="password" minlength="8" required>
                    </label>

                    <label>
                        <span>Role</span>
                        <select name="role">
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?= gbdbui_e($role) ?>"><?= gbdbui_e($role) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Language</span>
                        <select name="language">
                            <?php foreach ($langs as $code => $label) : ?>
                                <option value="<?= gbdbui_e($code) ?>"><?= gbdbui_e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Instances</span>
                        <input name="instances" value="*" placeholder="* or demo,shop,tenant_a">
                    </label>

                    <label>
                        <span>Bases</span>
                        <input name="bases" value="*" placeholder="* or crm,content,logs">
                    </label>

                    <label class="field-span-2">
                        <span>Permissions (leer = nur Rolle, * = alle)</span>
                        <input name="permissions" placeholder="z. B. backup,jobs – leer lassen für nur die Rechte der Rolle">
                    </label>

                    <label class="field-span-2">
                        <span>Tools</span>
                        <input name="tools" placeholder="monitoring,gql_scripts,backup,import_export">
                    </label>

                    <div class="field-span-2 user-form-actions">
                        <button class="primary" type="submit">Create user</button>
                    </div>
                </form>
            </section>

            <aside class="gbdbui-panel user-help-card">
                <div class="gbdbui-panel-head compact">
                    <div>
                        <h3>Role model</h3>
                        <p>Use a simple role baseline and keep special access in permissions/tools. Ein leeres Rechtefeld gibt nur die Rechte der Rolle, <code>*</code> gibt alle.</p>
                    </div>
                </div>

                <div class="role-guide-grid">
                    <div class="role-guide-item">
                        <span class="role-pill role-admin">admin</span>
                        <p>Full access including maintenance, configuration, user and dangerous operations.</p>
                    </div>
                    <div class="role-guide-item">
                        <span class="role-pill role-maintainer">maintainer</span>
                        <p>Operational maintenance, jobs, backups and structure work without full admin rights.</p>
                    </div>
                    <div class="role-guide-item">
                        <span class="role-pill role-editor">editor</span>
                        <p>Daily work on content and bases with limited system reach.</p>
                    </div>
                    <div class="role-guide-item">
                        <span class="role-pill role-viewer">viewer</span>
                        <p>Read-only or observation-focused access.</p>
                    </div>
                </div>

                <div class="user-help-block">
                    <small class="user-help-label">Suggested permission tags</small>
                    <div class="user-help-chips">
                        <span class="gb-chip">read</span>
                        <span class="gb-chip">write</span>
                        <span class="gb-chip">structure</span>
                        <span class="gb-chip">maintenance</span>
                        <span class="gb-chip">jobs</span>
                        <span class="gb-chip">backup</span>
                        <span class="gb-chip">config</span>
                        <span class="gb-chip">dev</span>
                        <span class="gb-chip">users</span>
                        <span class="gb-chip">danger</span>
                    </div>
                </div>

                <div class="user-help-block">
                    <small class="user-help-label">Tool restrictions</small>
                    <div class="user-help-chips">
                        <span class="gb-chip">dashboard</span>
                        <span class="gb-chip">migration</span>
                        <span class="gb-chip">cryption</span>
                        <span class="gb-chip">optimize</span>
                        <span class="gb-chip">env</span>
                        <span class="gb-chip">plugins</span>
                        <span class="gb-chip">gql_scripts</span>
                        <span class="gb-chip">backup</span>
                        <span class="gb-chip">import_export</span>
                        <span class="gb-chip">monitoring</span>
                    </div>
                </div>
            </aside>
        </section>

        <section class="gbdbui-panel users-table-panel">
            <div class="gbdbui-panel-head">
                <div>
                    <h2>Users</h2>
                    <p>Overview of all GBDB UI accounts with scoped access, permissions and quick actions.</p>
                </div>
                <div class="users-table-summary">
                    <span class="gb-chip"><?= $totalUsers ?> total</span>
                    <span class="gb-chip"><?= $activeUsers ?> active</span>
                    <span class="gb-chip"><?= count($roleCounter) ?> roles in use</span>
                </div>
            </div>

            <div class="user-table-wrap modern-table-wrap">
                <table class="user-table user-admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Language</th>
                            <th>Instances</th>
                            <th>Bases</th>
                            <th>Permissions</th>
                            <th>Tools</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) :
                            $id = (int) ($user['id'] ?? 0);
                            $role = (string) ($user['role'] ?? 'viewer');
                            $active = (string) ($user['active'] ?? '1') === '1';
                            $lang = (string) ($user['language'] ?? 'en');
                            $instances = (string) ($user['instances'] ?? '*');
                            $bases = (string) ($user['bases'] ?? '*');
                            $permissions = (string) ($user['permissions'] ?? '');
                            $tools = (string) ($user['tools'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <div class="user-main-cell">
                                        <strong><?= gbdbui_e((string) ($user['username'] ?? '')) ?></strong>
                                        <small>ID <?= $id ?></small>
                                    </div>
                                </td>
                                <td><span class="role-pill role-<?= gbdbui_e($role) ?>"><?= gbdbui_e($role) ?></span></td>
                                <td>
                                    <span class="status-pill <?= $active ? 'done' : 'failed' ?>">
                                        <?= $active ? 'active' : 'disabled' ?>
                                    </span>
                                </td>
                                <td><span class="gb-chip gb-chip-muted"><?= gbdbui_e(strtoupper($lang)) ?></span></td>
                                <td>
                                    <div class="chip-stack"><?= gbdbui_render_chip_group($instances, 'none') ?></div>
                                </td>
                                <td>
                                    <div class="chip-stack"><?= gbdbui_render_chip_group($bases, 'none') ?></div>
                                </td>
                                <td>
                                    <div class="chip-stack"><?= gbdbui_render_chip_group($permissions, $role === 'admin' ? 'alle (Admin)' : 'nur Rolle') ?></div>
                                </td>
                                <td>
                                    <div class="chip-stack"><?= gbdbui_render_chip_group($tools, 'none') ?></div>
                                </td>
                                <td>
                                    <?php if ($role === 'admin' && !$isAdmin) : ?>
                                    <span class="gb-chip gb-chip-muted">nur Admins</span>
                                    <?php else : ?>
                                    <div class="user-action-group">
                                        <button type="button" class="secondary xs"
                                            data-toggle="edit-<?= $id ?>">Edit</button>
                                        <button type="button" class="secondary xs"
                                            data-toggle="pwd-<?= $id ?>">Password</button>
                                        <form method="post" onsubmit="return confirm('Delete user?')">
                                            <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                            <input type="hidden" name="user_action" value="delete_user">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button class="danger xs" type="submit">Delete</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr id="edit-<?= $id ?>" class="user-expand-row" hidden>
                                <td colspan="9">
                                    <form method="post" class="user-inline-grid user-form-shell">
                                        <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                        <input type="hidden" name="user_action" value="update_user">
                                        <input type="hidden" name="id" value="<?= $id ?>">

                                        <label>
                                            <span>Username</span>
                                            <input name="username"
                                                value="<?= gbdbui_e((string) ($user['username'] ?? '')) ?>">
                                        </label>

                                        <label>
                                            <span>Role</span>
                                            <select name="role">
                                                <?php foreach ($roles as $roleOption) : ?>
                                                    <option value="<?= gbdbui_e($roleOption) ?>" <?= $role === $roleOption ? 'selected' : '' ?>>
                                                        <?= gbdbui_e($roleOption) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label>
                                            <span>Status</span>
                                            <select name="active">
                                                <option value="1" <?= $active ? 'selected' : '' ?>>active</option>
                                                <option value="0" <?= !$active ? 'selected' : '' ?>>disabled</option>
                                            </select>
                                        </label>

                                        <label>
                                            <span>Language</span>
                                            <select name="language">
                                                <?php foreach ($langs as $code => $label) : ?>
                                                    <option value="<?= gbdbui_e($code) ?>" <?= $lang === $code ? 'selected' : '' ?>>
                                                        <?= gbdbui_e($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label>
                                            <span>Instances</span>
                                            <input name="instances" value="<?= gbdbui_e($instances) ?>">
                                        </label>

                                        <label>
                                            <span>Bases</span>
                                            <input name="bases" value="<?= gbdbui_e($bases) ?>">
                                        </label>

                                        <label class="field-span-2">
                                            <span>Permissions (leer = nur Rolle, * = alle)</span>
                                            <input name="permissions" value="<?= gbdbui_e($permissions) ?>" placeholder="leer = nur die Rechte der Rolle">
                                        </label>

                                        <label class="field-span-2">
                                            <span>Tools</span>
                                            <input name="tools" value="<?= gbdbui_e($tools) ?>">
                                        </label>

                                        <div class="field-span-2 user-form-actions">
                                            <button class="primary" type="submit">Save changes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr id="pwd-<?= $id ?>" class="user-expand-row" hidden>
                                <td colspan="9">
                                    <form method="post" class="user-inline-grid user-form-shell compact-password-grid">
                                        <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                        <input type="hidden" name="user_action" value="reset_password">
                                        <input type="hidden" name="id" value="<?= $id ?>">

                                        <label>
                                            <span>New password</span>
                                            <input name="password" type="password" minlength="8">
                                        </label>

                                        <label>
                                            <span>Repeat password</span>
                                            <input name="password2" type="password" minlength="8">
                                        </label>

                                        <div class="user-form-actions password-action-row">
                                            <button class="primary" type="submit">Reset password</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <script>
        document.querySelectorAll('[data-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.getAttribute('data-toggle'));

                if (!target) {
                    return;
                }

                target.hidden = !target.hidden;
            });
        });
    </script>
<?php gbdbui_layout_end(); ?>
