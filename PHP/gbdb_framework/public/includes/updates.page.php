<?php

declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('updates');

function gbdbui_upd_time(mixed $value) :string {
    if (is_string($value) && $value !== '' && !ctype_digit($value)) {
        $time = strtotime($value);

        return $time === false ? $value : date('d.m.Y H:i', $time);
    }

    return (int) $value > 0 ? date('d.m.Y H:i', (int) $value) : '—';
}

function gbdbui_upd_badge(string $status) :string {
    $map = [
        'VERIFIED' => ['success', 'Verifiziert'],
        'VERIFIED_API_UNAVAILABLE' => ['warning', 'Verifiziert, API nicht erreichbar'],
        'INVALID_SIGNATURE' => ['warning gbdb-badge-danger', 'Ungültige Signatur'],
        'UNTRUSTED_KEY' => ['warning gbdb-badge-danger', 'Nicht autorisierter Schlüssel'],
        'REVOKED_KEY' => ['warning gbdb-badge-danger', 'Widerrufener Schlüssel'],
        'INVALID_CHECKSUM' => ['warning gbdb-badge-danger', 'Checksum ungültig'],
        'EXPIRED_METADATA' => ['warning gbdb-badge-danger', 'Metadaten abgelaufen'],
        'ROLLBACK_DETECTED' => ['warning gbdb-badge-danger', 'Rollback erkannt'],
        'INCOMPATIBLE' => ['warning', 'Nicht kompatibel'],
        'INVALID_METADATA' => ['warning gbdb-badge-danger', 'Ungültige Metadaten'],
        'NO_TRUST_ANCHOR' => ['warning', 'Kein Vertrauensanker'],
        'CRYPTO_UNAVAILABLE' => ['warning gbdb-badge-danger', 'sodium fehlt'],
        'CLOCK_INVALID' => ['warning gbdb-badge-danger', 'Systemzeit unplausibel'],
        'API_REJECTED' => ['warning gbdb-badge-danger', 'Von der API abgelehnt'],
        'RELEASE_REVOKED' => ['warning gbdb-badge-danger', 'Release zurückgezogen'],
        'NETWORK' => ['neutral', 'Server nicht erreichbar'],
        'completed' => ['success', 'Erfolgreich'],
        'failed' => ['warning gbdb-badge-danger', 'Fehlgeschlagen'],
        'cancelled' => ['neutral', 'Abgebrochen'],
        'rolled_back' => ['warning', 'Zurückgenommen'],
        'rollback_failed' => ['warning gbdb-badge-danger', 'Rollback unvollständig'],
        'running' => ['info', 'Läuft'],
        'needs_confirmation' => ['warning', 'Entscheidung nötig'],
        'applying' => ['info', 'Installiert'],
        'verify_pending' => ['info', 'Selbstprüfung folgt'],
        'verifying' => ['info', 'Selbstprüfung'],
    ];
    [$tone, $label] = $map[$status] ?? ['neutral', $status !== '' ? $status : '—'];

    return '<span class="status-badge ' . gbdbui_e($tone) . '">' . gbdbui_e($label) . '</span>';
}

$ajax = ($_POST['ajax'] ?? '') === '1';
$phaseLabels = ['download' => 'Download und Prüfung der Vertrauenskette', 'preflight' => 'Vorabprüfung', 'backup' => 'Backup', 'staging' => 'Staging und Prüfung', 'apply' => 'Installation'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_action'])) {
    $action = (string) $_POST['update_action'];

    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            exit;
        }

        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_redirect('updates');
    }

    try {
        switch ($action) {
            case 'tick':
                GBDBFrameworkUpdater::tick(true);
                header('Content-Type: application/json');
                echo json_encode(['ok' => true]);
                exit;
            case 'step':
                $job = GBDBUpdateInstaller::step();

                if ($ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'status' => $job['status'] ?? '', 'phase' => $job['phase'] ?? '', 'label' => $phaseLabels[$job['phase'] ?? ''] ?? '']);
                    exit;
                }

                gbdbui_redirect('updates', ($job['status'] ?? '') === 'verify_pending' ? ['finish' => '1'] : []);
            case 'check':
                $status = GBDBFrameworkUpdater::check(true);
                gbdbui_flash(($status['state'] ?? '') === 'error' ? 'bad' : 'ok', (string) ($status['message'] ?? 'Update-Suche abgeschlossen.'));
                break;
            case 'install':
                GBDBUpdateInstaller::start((string) ($_POST['version'] ?? ''), 'manual');
                gbdbui_flash('ok', 'Update gestartet. Alle Prüfungen werden vor der Installation wiederholt.');
                break;
            case 'confirm':
                GBDBUpdateInstaller::confirm((string) ($_POST['what'] ?? ''));
                break;
            case 'cancel':
                GBDBUpdateInstaller::cancel();
                gbdbui_flash('ok', 'Update abgebrochen. Die Installation ist unverändert.');
                break;
            case 'skip':
                GBDBFrameworkUpdater::skip((string) ($_POST['version'] ?? ''));
                gbdbui_flash('ok', 'Version ' . (string) $_POST['version'] . ' wird nicht mehr angeboten. Neuere Versionen erscheinen wieder.');
                break;
            case 'unskip':
                GBDBFrameworkUpdater::unskip((string) ($_POST['version'] ?? ''));
                gbdbui_flash('ok', 'Version ' . (string) $_POST['version'] . ' wird wieder angeboten.');
                break;
            case 'restore':
                $result = GBDBUpdateInstaller::restoreLatest((string) ($_POST['job'] ?? ''));
                gbdbui_flash($result['ok'] ? 'ok' : 'bad', $result['message']);
                break;
        }
    } catch (Throwable $exception) {
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $exception->getMessage()]);
            exit;
        }

        gbdbui_flash('bad', $exception->getMessage());
    }

    gbdbui_redirect('updates', ($_POST['return'] ?? '') !== '' ? ['tool_return' => (string) $_POST['return']] : []);
}

if (isset($_GET['tool_return']) && preg_match('/^[a-z_0-9]{1,40}$/', (string) $_GET['tool_return']) && (string) $_GET['tool_return'] !== 'updates' && empty(GBDBUpdateInstaller::job()['question'])) {
    $job = GBDBUpdateInstaller::job();

    if (!GBDBUpdateInstaller::isActive($job)) {
        gbdbui_redirect((string) $_GET['tool_return']);
    }
}

$settings = GBDBUpdateSettings::all();
$status = GBDBFrameworkUpdater::status();
$job = GBDBUpdateInstaller::job();
$jobActive = GBDBUpdateInstaller::isActive($job);
$history = GBDBUpdateState::history(30);
$backups = GBDBUpdateInstaller::backups();
$installed = GBDBUpdateState::read('installed');
$csrf = GreenQLUIv2Helper::csrf();
$csrfField = '<input type="hidden" name="csrf" value="' . gbdbui_e($csrf) . '">';
$available = $status['available'] ?? null;
$trust = $status['trust'] ?? null;
$sodium = GBDBUpdateSignature::available();
$anchorError = '';

try {
    GBDBUpdateState::trustState();
} catch (Throwable $exception) {
    $anchorError = $exception->getMessage();
}

$actions = '<form method="post">' . $csrfField . '<input type="hidden" name="update_action" value="check"><button class="btn btn-primary"' . ($jobActive ? ' disabled' : '') . '>' . gbdbui_icon('refresh', 16) . ' Jetzt nach Updates suchen</button></form>';
gbdbui_layout_start('updates', 'Framework-Updates', 'Signierte GBDB-Releases finden, prüfen und kontrolliert installieren – mit Backup, Selbstprüfung und Rollback.', ['eyebrow' => 'System', 'actions' => $actions, 'js' => ['gbdbui_updates.js']]);
?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-blue"><?= gbdbui_icon('book', 20) ?></span></div>
                <div class="stat-label">Installierte Version</div>
                <div class="stat-number">v<?= gbdbui_e($status['installed']) ?></div>
                <div class="stat-foot"><?= $status['installed_official'] ? 'Offizielles Release' . (isset($installed['installed_at']) ? ' · seit ' . gbdbui_e(gbdbui_upd_time($installed['installed_at'])) : '') : 'Kein signiertes Release (Entwicklungsstand)' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon <?= $available ? 'stat-orange' : 'stat-green' ?>"><?= gbdbui_icon('download', 20) ?></span></div>
                <div class="stat-label">Verfügbare Version</div>
                <div class="stat-number"><?= $available ? 'v' . gbdbui_e($available['version']) : 'Aktuell' ?></div>
                <div class="stat-foot"><?= $available ? gbdbui_e(GBDBUpdateInstaller::bytes((int) $available['size'])) . ' · veröffentlicht ' . gbdbui_e(gbdbui_upd_time($available['published_at'])) : 'Keine neuere vertrauenswürdige Version' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-purple"><?= gbdbui_icon('shield', 20) ?></span></div>
                <div class="stat-label">Verifizierung</div>
                <div class="stat-number"><small><?= gbdbui_upd_badge((string) ($status['code'] ?? '')) ?></small></div>
                <div class="stat-foot"><?= $trust ? 'Vertrauensmetadaten v' . (int) $trust['version'] . ' · gültig bis ' . gbdbui_e(gbdbui_upd_time($trust['expires'])) : 'Noch nicht geprüft' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-green"><?= gbdbui_icon('clock', 20) ?></span></div>
                <div class="stat-label">Letzte Prüfung</div>
                <div class="stat-number"><small><?= gbdbui_e(gbdbui_upd_time($status['last_check'] ?? 0)) ?></small></div>
                <div class="stat-foot"><?= $settings['check_for_updates'] ? 'Automatisch alle ' . round($settings['check_interval'] / 3600, 1) . ' h' : 'Automatische Suche ausgeschaltet' ?></div>
            </div>
        </div>

<?php if (!$sodium) : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Updates blockiert</strong><p>Die PHP-Erweiterung <code>sodium</code> fehlt. Ohne sie lassen sich Ed25519-Signaturen nicht prüfen; eine unsichere Ersatzprüfung gibt es nicht. Der normale Betrieb läuft weiter.</p></div>
<?php elseif ($anchorError !== '') : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Kein Vertrauensanker</strong><p><?= gbdbui_e($anchorError) ?> Der Root-Schlüssel ist Teil jedes offiziellen Release-Pakets von <code>mamueller.de/gbdb/updates/</code>. Dieser Stand lässt sich erst automatisch aktualisieren, nachdem einmal ein offizielles Release installiert wurde.</p></div>
<?php endif; ?>
<?php foreach ($settings['_warnings'] as $warning) : ?>
        <div class="notice notice-info"><strong><?= gbdbui_icon('shield', 16) ?> Konfiguration</strong><p><?= gbdbui_e($warning) ?></p></div>
<?php endforeach; ?>
<?php if (in_array($status['state'] ?? '', ['error', 'offline'], true)) : ?>
        <div class="notice <?= ($status['state'] ?? '') === 'error' ? 'notice-error' : 'notice-info' ?>" role="status"><strong><?= gbdbui_icon('alert', 16) ?> <?= ($status['state'] ?? '') === 'error' ? 'Update-Prüfung abgewiesen' : 'Update-Server nicht erreichbar' ?></strong>
            <p><?= gbdbui_upd_badge((string) ($status['code'] ?? '')) ?> <?= gbdbui_e((string) ($status['message'] ?? '')) ?><?= ($status['key_id'] ?? '') !== '' ? ' · Schlüssel-ID <code>' . gbdbui_e($status['key_id']) . '</code>' : '' ?></p>
            <?php if (($status['state'] ?? '') === 'offline') : ?><p class="muted">GBDB arbeitet normal weiter. Die nächste Prüfung erfolgt automatisch.</p><?php endif; ?></div>
<?php endif; ?>
<?php foreach ((array) ($status['warnings'] ?? []) as $warning) : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('shield', 16) ?> Sicherheitshinweis</strong><p><?= gbdbui_e($warning) ?></p></div>
<?php endforeach; ?>

<?php if ($job !== []) : ?>
<?php
    $jobStatus = (string) ($job['status'] ?? '');
    $question = $job['question'] ?? null;
?>
        <section class="panel padded gbdbui-update-job" data-update-job="<?= gbdbui_e($jobStatus) ?>" data-update-url="<?= gbdbui_e(gbdbui_url('updates')) ?>" data-csrf="<?= gbdbui_e($csrf) ?>" data-finish-url="<?= gbdbui_e(gbdbui_url('updates', ['finish' => '1'])) ?>">
            <div class="panel-header">
                <div>
                    <h2><?= $jobActive ? 'Update auf v' . gbdbui_e($job['version'] ?? '') : 'Letztes Update: v' . gbdbui_e($job['from_version'] ?? '') . ' → v' . gbdbui_e($job['version'] ?? '') ?> <?= gbdbui_upd_badge($jobStatus) ?></h2>
                    <p class="muted">Auftrag <code><?= gbdbui_e($job['id'] ?? '') ?></code> · <?= ($job['trigger'] ?? '') === 'auto' ? 'automatisch' : 'manuell' ?> · gestartet <?= gbdbui_e(gbdbui_upd_time($job['created_at'] ?? 0)) ?></p>
                </div>
<?php if (in_array($jobStatus, ['running', 'needs_confirmation'], true)) : ?>
                <form method="post" data-confirm="Update abbrechen? Bis zur Installation bleibt alles unverändert."><?= $csrfField ?><input type="hidden" name="update_action" value="cancel"><button class="btn btn-secondary"><?= gbdbui_icon('x', 16) ?> Abbrechen</button></form>
<?php endif; ?>
            </div>
<?php if ($jobActive) : ?>
            <ol class="gbdbui-update-phases">
<?php
    $installing = in_array($jobStatus, ['applying', 'verify_pending', 'verifying'], true);
    $currentIndex = $installing ? count($phaseLabels) : (int) array_search($job['phase'] ?? 'download', array_keys($phaseLabels), true);

    foreach (array_keys($phaseLabels) as $index => $phase) :
        $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : '');
?>
                <li class="<?= $state ?>"><?= gbdbui_icon($state === 'done' ? 'check-circle' : ($state === 'current' ? 'clock' : 'chevron-right'), 15) ?> <?= gbdbui_e($phaseLabels[$phase]) ?></li>
<?php endforeach; ?>
                <li class="<?= in_array($jobStatus, ['verify_pending', 'verifying'], true) ? 'current' : '' ?>"><?= gbdbui_icon('shield', 15) ?> Selbstprüfung und Abschluss</li>
            </ol>
<?php if ($jobStatus === 'running') : ?>
            <p class="gbdbui-update-progress" aria-live="polite"><?= gbdbui_icon('refresh', 16) ?> <span data-update-progress>Wird ausgeführt: <?= gbdbui_e($phaseLabels[$job['phase'] ?? ''] ?? '') ?> …</span></p>
            <noscript><form method="post"><?= $csrfField ?><input type="hidden" name="update_action" value="step"><button class="btn btn-primary">Nächsten Schritt ausführen</button></form></noscript>
<?php elseif ($jobStatus === 'verify_pending') : ?>
            <p><a class="btn btn-primary" href="<?= gbdbui_e(gbdbui_url('updates', ['finish' => '1'])) ?>"><?= gbdbui_icon('shield', 16) ?> Selbstprüfung abschließen</a></p>
<?php endif; ?>
<?php endif; ?>
<?php if (is_array($question) && $jobStatus === 'needs_confirmation') : ?>
            <div class="notice notice-info" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Entscheidung erforderlich</strong><p><?= gbdbui_e($question['text']) ?></p>
<?php if (($question['details']['api'] ?? '') !== '') : ?>
                <p class="muted">Verify-API: <?= gbdbui_e($question['details']['api']) ?>. Signatur, Schlüsselautorisierung und SHA-256 sind lokal bestätigt.</p>
<?php endif; ?>
<?php if (($question['details']['files'] ?? []) !== []) : ?>
                <details><summary><?= count($question['details']['files']) ?> Dateien anzeigen</summary><ul class="gbdbui-update-files"><?php foreach ($question['details']['files'] as $file) : ?><li><code><?= gbdbui_e($file) ?></code></li><?php endforeach; ?></ul></details>
<?php endif; ?>
                <div class="gbdbui-update-actions">
                    <form method="post"><?= $csrfField ?><input type="hidden" name="update_action" value="confirm"><input type="hidden" name="what" value="<?= gbdbui_e($question['type']) ?>"><button class="btn btn-primary"><?= $question['type'] === 'api_unavailable' ? 'Update dennoch installieren' : 'Dateien ersetzen und fortfahren' ?></button></form>
                    <form method="post"><?= $csrfField ?><input type="hidden" name="update_action" value="cancel"><button class="btn btn-secondary">Abbrechen</button></form>
                </div>
            </div>
<?php endif; ?>
<?php if (!$jobActive && ($job['error'] ?? '') !== '') : ?>
            <div class="notice <?= in_array($jobStatus, ['cancelled'], true) ? 'notice-info' : 'notice-error' ?>" role="status"><strong><?= gbdbui_upd_badge($jobStatus) ?> <?= ($job['error_code'] ?? '') !== '' ? gbdbui_upd_badge((string) $job['error_code']) : '' ?></strong><p><?= gbdbui_e((string) $job['error']) ?><?= ($job['key_id'] ?? '') !== '' ? ' · Schlüssel-ID <code>' . gbdbui_e($job['key_id']) . '</code>' : '' ?></p>
<?php if (in_array($job['error_code'] ?? '', ['INVALID_SIGNATURE', 'INVALID_CHECKSUM', 'UNTRUSTED_KEY', 'REVOKED_KEY', 'API_REJECTED', 'INVALID_METADATA', 'ROLLBACK_DETECTED'], true)) : ?>
                <p><strong>Sicherheitswarnung:</strong> Die Datei wurde nicht installiert. Eine Umgehung ist nicht vorgesehen; das Ereignis ist protokolliert.</p>
<?php endif; ?>
            </div>
<?php elseif ($jobStatus === 'completed') : ?>
            <div class="notice notice-success" role="status"><strong><?= gbdbui_icon('check-circle', 16) ?> Update abgeschlossen</strong><p>Selbstprüfung: <?= gbdbui_e(implode(' · ', (array) ($job['selftest'] ?? []))) ?></p>
<?php if (($job['config_changes'] ?? []) !== []) : ?><p class="muted">Konfiguration ergänzt: <?= gbdbui_e(implode(', ', $job['config_changes'])) ?></p><?php endif; ?>
            </div>
<?php endif; ?>
<?php if (($job['verification'] ?? []) !== []) : ?>
            <dl class="gbdb-kv">
                <dt>Ergebnis der Verifizierung</dt><dd><?= gbdbui_upd_badge((string) ($job['verification']['status'] ?? '')) ?></dd>
                <dt>Signaturschlüssel</dt><dd><code><?= gbdbui_e($job['verification']['signing_key_id'] ?? '') ?></code></dd>
                <dt>SHA-256</dt><dd><code><?= gbdbui_e($job['verification']['sha256'] ?? '') ?></code></dd>
                <dt>Vertrauensmetadaten / Katalog</dt><dd>v<?= (int) ($job['verification']['trust_version'] ?? 0) ?> / Sequenz <?= (int) ($job['verification']['catalog_sequence'] ?? 0) ?></dd>
                <dt>Verify-API</dt><dd><?= gbdbui_e($job['verification']['api'] ?? '') ?></dd>
            </dl>
<?php endif; ?>
            <details class="raw-details"><summary>Protokoll</summary><pre class="raw"><?= gbdbui_e(GBDBUpdateState::logText((string) ($job['id'] ?? ''))) ?></pre></details>
        </section>
<?php endif; ?>

        <section class="panel">
            <div class="panel-header"><div><h2>Verfügbare Versionen</h2><p class="muted">Gefunden über das Verzeichnis des Update-Servers, angeboten nur nach bestandener Vertrauenskette. Vor jeder Installation werden alle Prüfungen wiederholt.</p></div></div>
<?php if (($status['versions'] ?? []) === []) : ?>
            <p class="padded muted">Keine neuere vertrauenswürdige Version gefunden.</p>
<?php else : ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Version</th><th>Veröffentlicht</th><th>Größe</th><th>Prüfung</th><th>Hinweise</th><th></th></tr></thead><tbody>
<?php foreach ($status['versions'] as $version) : ?>
<?php $skipped = in_array($version['version'], $status['skipped'], true); ?>
                <tr>
                    <td><strong>v<?= gbdbui_e($version['version']) ?></strong><?= $skipped ? ' <span class="status-badge neutral">übersprungen</span>' : '' ?></td>
                    <td><?= gbdbui_e(gbdbui_upd_time($version['published_at'])) ?></td>
                    <td><?= gbdbui_e(GBDBUpdateInstaller::bytes((int) $version['size'])) ?></td>
                    <td><?= gbdbui_upd_badge($version['incompatible'] === [] ? 'VERIFIED' : 'INCOMPATIBLE') ?><br><small class="muted">Schlüssel <?= gbdbui_e($version['signing_key_id']) ?></small></td>
                    <td><?= gbdbui_e(implode(' ', $version['incompatible'])) ?><?= $version['requires_migration'] ? ' Enthält Datenmigrationen (vorher wird der Datenbestand gesichert).' : '' ?><?php if ($version['changelog'] !== '') : ?><details><summary>Änderungen</summary><pre class="raw"><?= gbdbui_e($version['changelog']) ?></pre></details><?php endif; ?></td>
                    <td class="row-actions">
<?php if ($version['incompatible'] === [] && !$jobActive) : ?>
                        <form method="post" data-confirm="GBDB auf v<?= gbdbui_e($version['version']) ?> aktualisieren? Vorher wird automatisch gesichert; bei Fehlern wird zurückgerollt."><?= $csrfField ?><input type="hidden" name="update_action" value="install"><input type="hidden" name="version" value="<?= gbdbui_e($version['version']) ?>"><button class="btn btn-primary btn-sm">Installieren</button></form>
<?php endif; ?>
                        <form method="post"><?= $csrfField ?><input type="hidden" name="update_action" value="<?= $skipped ? 'unskip' : 'skip' ?>"><input type="hidden" name="version" value="<?= gbdbui_e($version['version']) ?>"><button class="btn btn-secondary btn-sm"><?= $skipped ? 'Wieder anbieten' : 'Überspringen' ?></button></form>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php endif; ?>
<?php if (($status['notes'] ?? []) !== []) : ?>
            <div class="padded"><?php foreach ($status['notes'] as $note) : ?><p class="muted"><?= gbdbui_icon('help', 14) ?> <?= gbdbui_e($note) ?></p><?php endforeach; ?></div>
<?php endif; ?>
        </section>

        <div class="gbdbui-page-grid">
            <section class="panel padded span-7">
                <h2>Automatische Updates</h2>
                <dl class="gbdb-kv">
                    <dt>Update-Suche</dt><dd><?= $settings['check_for_updates'] ? 'aktiv, alle ' . round($settings['check_interval'] / 3600, 1) . ' Stunden' : 'aus' ?></dd>
                    <dt>Automatisch installieren</dt><dd><?= $settings['auto_updating']['enabled'] ? 'ja, ' . gbdbui_e($settings['auto_updating']['patch_day']) : 'nein (Standard)' ?></dd>
                    <dt>Geplant</dt><dd><?= is_array($status['scheduled'] ?? null) ? 'v' . gbdbui_e($status['scheduled']['version']) . ' ab ' . gbdbui_e(gbdbui_upd_time($status['scheduled']['due_at'])) : '—' ?></dd>
                    <dt>Update-Server</dt><dd><code><?= gbdbui_e($settings['update_server']) ?></code></dd>
                    <dt>Verify-API</dt><dd><code><?= gbdbui_e($settings['verify_api']) ?></code><?= $settings['verification']['verify_via_api'] ? '' : ' (deaktiviert)' ?></dd>
                    <dt>Arbeitsordner</dt><dd><code><?= gbdbui_e(GBDBUpdateSettings::workDir()) ?></code></dd>
                </dl>
<?php if (($status['schedule_error'] ?? '') !== '') : ?>
                <div class="notice notice-error"><p><?= gbdbui_e($status['schedule_error']) ?></p></div>
<?php endif; ?>
                <p class="muted">Einstellungen in <code>.framework.env.php</code>, Konstante <code>FRAMEWORK_UPDATER</code>. Ohne Cronjob: Fällige Prüfungen und Updates starten bei der nächsten passenden Anfrage nach dem geplanten Zeitpunkt. Automatisch installiert wird nur bei vollständiger Verifizierung (auch über die API), ohne Dateikonflikte und mit gelungenem Backup.</p>
            </section>
            <section class="panel padded span-5">
                <h2>Übersprungene Updates</h2>
<?php if ($status['skipped'] === []) : ?>
                <p class="muted">Keine Version übersprungen.</p>
<?php else : ?>
<?php foreach ($status['skipped'] as $version) : ?>
                <form method="post" class="gbdbui-inline-form"><?= $csrfField ?><input type="hidden" name="update_action" value="unskip"><input type="hidden" name="version" value="<?= gbdbui_e($version) ?>"><span>v<?= gbdbui_e($version) ?></span> <button class="btn btn-secondary btn-sm">Wieder anbieten</button></form>
<?php endforeach; ?>
<?php endif; ?>
                <p class="muted">Übersprungene Versionen lassen sich jederzeit manuell installieren; neuere Versionen werden wieder angeboten.</p>
            </section>
        </div>

        <section class="panel">
            <div class="panel-header"><div><h2>Vertrauensstatus</h2><p class="muted">Kette: fest integrierter Root-Schlüssel → trust.json → Release-Schlüssel → releases.json → Manifest → ZIP.</p></div></div>
<?php if (!$trust) : ?>
            <p class="padded muted">Noch keine Vertrauensmetadaten bestätigt.</p>
<?php else : ?>
            <div class="padded"><dl class="gbdb-kv">
                <dt>Vertrauensmetadaten</dt><dd>Version <?= (int) $trust['version'] ?> · ausgestellt <?= gbdbui_e(gbdbui_upd_time($trust['issued_at'])) ?> · gültig bis <?= gbdbui_e(gbdbui_upd_time($trust['expires'])) ?></dd>
                <dt>Root-Schlüssel</dt><dd><?= gbdbui_e(implode(', ', $trust['root_keys'])) ?></dd>
                <dt>Recovery-Schlüssel</dt><dd><?= $trust['recovery_keys'] !== [] ? gbdbui_e(implode(', ', $trust['recovery_keys'])) : '—' ?></dd>
                <dt>Release-Katalog</dt><dd>Sequenz <?= (int) ($status['catalog']['sequence'] ?? 0) ?> · gültig bis <?= gbdbui_e(gbdbui_upd_time($status['catalog']['expires'] ?? '')) ?></dd>
            </dl></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Release-Schlüssel</th><th>Rollen</th><th>Gültig</th><th>Status</th></tr></thead><tbody>
<?php foreach ($trust['release_keys'] as $key) : ?>
                <tr><td><code><?= gbdbui_e($key['key_id']) ?></code></td><td><?= gbdbui_e(implode(', ', $key['roles'])) ?></td><td><?= gbdbui_e(gbdbui_upd_time($key['valid_from'])) ?> – <?= gbdbui_e(gbdbui_upd_time($key['valid_until'])) ?></td>
                    <td><?= $key['revoked'] ? gbdbui_upd_badge('REVOKED_KEY') . ($key['reason'] !== '' ? ' <small class="muted">' . gbdbui_e($key['reason']) . '</small>' : '') : gbdbui_upd_badge($key['status'] === 'VERIFIED' ? 'VERIFIED' : 'UNTRUSTED_KEY') ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header"><div><h2>Update-Historie</h2><p class="muted">Jeder Installationsversuch mit Verifizierung, Backup und Rollback-Status.</p></div></div>
<?php if ($history === []) : ?>
            <p class="padded muted">Noch keine Updates.</p>
<?php else : ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Zeitpunkt</th><th>Von → Nach</th><th>Auslöser</th><th>Ergebnis</th><th>Prüfungen</th><th>Backup / Rollback</th><th>Fehler</th></tr></thead><tbody>
<?php foreach ($history as $entry) : ?>
                <tr>
                    <td><?= gbdbui_e(gbdbui_upd_time($entry['time'] ?? '')) ?></td>
                    <td><?= ($entry['type'] ?? '') === 'trust' ? 'Vertrauensmetadaten v' . (int) ($entry['trust_version'] ?? 0) : 'v' . gbdbui_e($entry['from'] ?? '') . ' → v' . gbdbui_e($entry['to'] ?? '') ?></td>
                    <td><?= gbdbui_e(($entry['trigger'] ?? '') === 'auto' ? 'automatisch' : (($entry['trigger'] ?? '') === 'manual' ? 'manuell' : '—')) ?></td>
                    <td><?= gbdbui_upd_badge((string) ($entry['result'] ?? '')) ?></td>
                    <td><small>SHA-256 <?= gbdbui_e($entry['checksum'] ?? '—') ?> · Signatur <?= gbdbui_e($entry['signature'] ?? '—') ?><?= ($entry['signing_key_id'] ?? '') !== '' ? ' (' . gbdbui_e($entry['signing_key_id']) . ')' : '' ?> · Trust v<?= (int) ($entry['trust_version'] ?? 0) ?> · Katalog <?= (int) ($entry['catalog_sequence'] ?? 0) ?> · API <?= gbdbui_e($entry['api'] ?? '—') ?></small></td>
                    <td><small><?= ($entry['backup'] ?? '') !== '' ? '<code>' . gbdbui_e($entry['backup']) . '</code>' : '—' ?> · <?= gbdbui_e($entry['rollback'] ?? '—') ?></small></td>
                    <td><small><?= gbdbui_e($entry['error'] ?? '') ?></small></td>
                </tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header"><div><h2>Backups und Wiederherstellungspunkte</h2><p class="muted">Liegen im Arbeitsordner außerhalb des Framework-Ordners. Die neuesten <?= (int) $settings['keep_backups'] ?> bleiben erhalten.</p></div></div>
<?php if ($backups === []) : ?>
            <p class="padded muted">Noch keine Update-Backups.</p>
<?php else : ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Erstellt</th><th>Update</th><th>Gesicherte Dateien</th><th>Daten</th><th></th></tr></thead><tbody>
<?php foreach ($backups as $index => $backup) : ?>
                <tr><td><?= gbdbui_e(gbdbui_upd_time($backup['created_at'])) ?></td><td>v<?= gbdbui_e($backup['from']) ?> → v<?= gbdbui_e($backup['to']) ?></td><td><?= (int) $backup['files'] ?></td><td><?= $backup['data'] ? 'Datenbestand gesichert' : '—' ?></td>
                    <td class="row-actions"><?php if ($index === 0 && $backup['to'] === $status['installed'] && !$backup['data'] && !$jobActive) : ?><form method="post" data-confirm="Stand vor dem Update (v<?= gbdbui_e($backup['from']) ?>) wiederherstellen? Daten bleiben unverändert; die neuere Version wird danach wieder angeboten."><?= $csrfField ?><input type="hidden" name="update_action" value="restore"><input type="hidden" name="job" value="<?= gbdbui_e($backup['job']) ?>"><button class="btn btn-danger-outline btn-sm">v<?= gbdbui_e($backup['from']) ?> wiederherstellen</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php endif; ?>
        </section>
<?php gbdbui_layout_end(); ?>
