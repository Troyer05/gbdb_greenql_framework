<?php

declare(strict_types=1);

/**
 * GDP Manager (Konzept Kapitel 27): Übersicht, Knoten, Replikate, Policies,
 * Sync, Journal, Snapshots, Sicherheit und Diagnose. Jede Aktion verlangt
 * CSRF-Token und Admin-Rechte und nennt vorab ihre Auswirkung.
 */
gbdbui_require_tool('distributed');

$views = [
    'overview' => ['Übersicht', 'dashboard'],
    'nodes' => ['Knoten & Pairing', 'users'],
    'replicas' => ['Replikate', 'server'],
    'policies' => ['Policies', 'shield'],
    'sync' => ['Synchronisation', 'refresh'],
    'journal' => ['Journal', 'book'],
    'snapshots' => ['Snapshots', 'save'],
    'security' => ['Sicherheit', 'lock'],
    'diagnostics' => ['Diagnose', 'activity'],
];
$view = (string) ($_GET['view'] ?? 'overview');
$view = isset($views[$view]) ? $view : 'overview';
$enabled = GDPConfig::enabled();
$oneTimeCode = null;
$syncReport = null;
$connectionTest = null;

/**
 * Öffentliche gdp.php-Adresse dieses Hubs: GDP → hub_url, sonst aus der aktuellen Anfrage abgeleitet.
 */
function gbdbui_gdp_endpoint() :string {
    if (GDPConfig::hubUrl() !== '') {
        return GDPConfig::hubUrl();
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/D', $host)) {
        return '';
    }

    $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
    $dir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/gbdb_ui.php'))), '/');

    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/gdp.php';
}

/**
 * Formatiert einen Unix-Zeitpunkt für die Anzeige.
 */
function gbdbui_gdp_time(int $timestamp) :string {
    return $timestamp > 0 ? date('d.m.Y H:i:s', $timestamp) : '—';
}

function gbdbui_gdp_state_badge(string $state) :string {
    $tone = match ($state) {
        'idle', 'online', 'active', 'ok' => 'success',
        'pending', 'initializing', 'resync', 'pending_approval', 'degraded' => 'warning',
        'blocked', 'offline', 'revoked' => 'warning gbdb-badge-danger',
        default => 'neutral',
    };
    $labels = [
        'idle' => 'IDLE', 'pending' => 'PENDING', 'initializing' => 'INITIALIZING', 'resync' => 'RESYNC', 'blocked' => 'BLOCKED',
        'online' => 'ONLINE', 'offline' => 'OFFLINE', 'unpaired' => 'NICHT GEKOPPELT', 'active' => 'Aktiv', 'pending_approval' => 'Wartet auf Freigabe', 'revoked' => 'Widerrufen',
    ];

    return '<span class="status-badge ' . $tone . '">' . gbdbui_e($labels[$state] ?? $state) . '</span>';
}

function gbdbui_gdp_redirect(string $view, array $params = []) :never {
    header('Location: ' . gbdbui_url('distributed', ['view' => $view] + $params));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token. Bitte die Seite neu laden.');
        gbdbui_gdp_redirect($view);
    }

    $action = (string) ($_POST['gdp_action'] ?? '');
    $instance = (string) ($_POST['instance'] ?? '');

    try {
        switch ($action) {
            case 'init':
                GDP::init();
                gbdbui_flash('ok', 'Knotenidentität angelegt.');
                break;
            case 'enable_instance':
                GDP::enableInstanceReplication($instance, ['default_mode' => (string) ($_POST['default_mode'] ?? 'central_owner')]);
                gbdbui_flash('ok', 'Instanz „' . $instance . '“ ist zur Replikation freigegeben. Bestehende Daten wurden nicht verändert.');
                break;
            case 'disable_instance':
                GDP::disableInstanceReplication($instance);
                gbdbui_flash('ok', 'Replikation für „' . $instance . '“ beendet; Replikate erhalten keinen Zugriff mehr.');
                break;
            case 'create_pairing':
                $permissions = array_values(array_intersect(GDPGrants::PERMISSIONS, (array) ($_POST['permissions'] ?? [])));
                $oneTimeCode = GDP::createPairing([$instance], (string) ($_POST['role'] ?? 'replica'), $permissions, ($_POST['approval'] ?? '') === '1');
                break;
            case 'revoke_pairing':
                GDP::revokePairing((string) ($_POST['pairing_id'] ?? ''));
                gbdbui_flash('ok', 'Pairing-Freigabe widerrufen.');
                break;
            case 'pair':
                $result = GDP::pairWithHub((string) ($_POST['hub_url'] ?? ''), (string) ($_POST['code'] ?? ''), (string) ($_POST['display_name'] ?? ''));
                gbdbui_flash('ok', 'Mit Hub ' . $result['hub_node_id'] . ' gekoppelt (' . $result['status'] . '). Instanzen: ' . implode(', ', $result['instances']) . '. Der erste Sync lädt den Snapshot.');
                break;
            case 'approve_node':
                GDP::approveNode((string) ($_POST['node_id'] ?? ''));
                gbdbui_flash('ok', 'Knoten freigegeben.');
                break;
            case 'revoke_node':
                GDP::unregisterNode((string) ($_POST['node_id'] ?? ''));
                gbdbui_flash('ok', 'Knoten gesperrt; Schlüssel und Grants sind widerrufen.');
                break;
            case 'rotate':
                $result = GDP::rotateCredential();
                gbdbui_flash('ok', 'Neuer Knotenschlüssel ' . $result['key_id'] . ' aktiv; der alte ist widerrufen.');
                break;
            case 'set_policy':
                $options = ['mode' => (string) ($_POST['mode'] ?? '')];

                foreach (['owner_node_id', 'owner_column'] as $field) {
                    if (trim((string) ($_POST[$field] ?? '')) !== '') {
                        $options[$field] = trim((string) $_POST[$field]);
                    }
                }

                $options['offline_permission'] = ($_POST['offline_permission'] ?? '') === '1';
                $options['lease_seconds'] = max(0, (int) ($_POST['lease_seconds'] ?? 0));
                $writers = trim((string) ($_POST['writers'] ?? ''));
                $options['writers'] = $writers === '' ? [] : array_map('trim', explode(',', $writers));
                GDP::setPolicy($instance, trim((string) ($_POST['database'] ?? '')), trim((string) ($_POST['table'] ?? '')), $options);
                gbdbui_flash('ok', 'Policy gespeichert; Replikate übernehmen sie beim nächsten Handshake.');
                break;
            case 'remove_policy':
                GDP::removePolicy($instance, (string) ($_POST['database'] ?? ''), (string) ($_POST['table'] ?? ''));
                gbdbui_flash('ok', 'Policy entfernt; es gilt wieder der Standardmodus.');
                break;
            case 'default_mode':
                GDP::setDefaultPolicyMode($instance, GDPWriteMode::from((string) ($_POST['mode'] ?? '')));
                gbdbui_flash('ok', 'Standardmodus geändert.');
                break;
            case 'transfer':
                GDP::transferOwnership($instance, (string) ($_POST['database'] ?? ''), (string) ($_POST['table'] ?? ''), trim((string) ($_POST['new_owner'] ?? '')));
                gbdbui_flash('ok', 'Eigentümerwechsel gestartet. Er endet, sobald der bisherige Eigentümer alles übertragen hat.');
                break;
            case 'force_transfer':
                $count = GDP::forceCompleteTransfers($instance);
                gbdbui_flash($count > 0 ? 'ok' : 'bad', $count > 0 ? $count . ' Eigentümerwechsel abgeschlossen.' : 'Kein Wechsel abschließbar: Die Lease des alten Eigentümers läuft noch.');
                break;
            case 'sync':
                $syncReport = GDP::sync($instance !== '' ? $instance : null);
                break;
            case 'test_connection':
                $connectionTest = GDP::testConnection(gbdbui_gdp_endpoint());
                break;
            case 'regenerate_identity':
                $identity = GDP::regenerateIdentity();
                gbdbui_flash('ok', 'Neue Knotenidentität ' . $identity['node_id'] . ' (' . $identity['display_name'] . ') angelegt.');
                break;
            case 'preference':
                match ((string) ($_POST['preference'] ?? 'auto')) {
                    'force_local' => GDP::forceLocalMode(),
                    'prefer_central' => GDP::preferCentralMode(),
                    default => GDP::returnToAutoMode(),
                };
                gbdbui_flash('ok', 'Betriebspräferenz gespeichert. Sie erteilt keine zusätzlichen Schreibrechte.');
                break;
            case 'resolve':
                GDP::resolveBlock($instance, (string) ($_POST['how'] ?? 'retry'));
                gbdbui_flash('ok', 'Blockade aufgehoben; der nächste Sync prüft erneut.');
                break;
            case 'bootstrap':
                $syncReport = GDP::bootstrapReplica($instance, ($_POST['replace_local'] ?? '') === '1');
                break;
            case 'unblock_peer':
                GDP::unblockPeerStream($instance, (string) ($_POST['node_id'] ?? ''));
                gbdbui_flash('ok', 'Replikationsstrom freigegeben. Das Replikat sollte vorher neu initialisiert werden.');
                break;
            case 'snapshot':
                $manifest = GDP::createSnapshot($instance);
                gbdbui_flash('ok', 'Snapshot ' . $manifest['snapshot_id'] . ' bei Hubsequenz ' . $manifest['hub_sequence_at_snapshot'] . ' erstellt.');
                break;
            default:
                throw new InvalidArgumentException('Unbekannte Aktion.');
        }
    } catch (GDPException $exception) {
        gbdbui_flash('bad', $exception->errorCode()->value . ': ' . $exception->getMessage());
    } catch (Throwable $exception) {
        gbdbui_flash('bad', $exception->getMessage());
    }

    if ($oneTimeCode === null && $syncReport === null && $connectionTest === null) {
        gbdbui_gdp_redirect($view, $instance !== '' ? ['instance' => $instance] : []);
    }
}

$status = $enabled || is_dir(GDPStore::path()) ? GDP::getStatus() : ['enabled' => false, 'instances' => []];
$instances = $status['instances'] ?? [];
$selected = (string) ($_GET['instance'] ?? ($_POST['instance'] ?? (array_key_first($instances) ?? '')));
$selected = isset($instances[$selected]) ? $selected : (string) (array_key_first($instances) ?? '');
$isHub = ($status['role'] ?? '') === 'hub';
$csrf = GreenQLUIv2Helper::csrf();
$csrfField = '<input type="hidden" name="csrf" value="' . gbdbui_e($csrf) . '">';
$tabs = '';

foreach ($views as $key => [$label, $icon]) {
    $tabs .= '<a class="btn ' . ($key === $view ? 'btn-primary' : 'btn-secondary') . ' btn-sm" href="' . gbdbui_e(gbdbui_url('distributed', ['view' => $key] + ($selected !== '' ? ['instance' => $selected] : []))) . '">' . gbdbui_icon($icon, 14) . ' ' . gbdbui_e($label) . '</a>';
}

gbdbui_layout_start('distributed', 'Distributed (GDP)', 'GBDB Distributed Protocol: Knoten, Replikate, Policies, Journal und Synchronisation. Optional – ohne Aktivierung bleibt jede Instanz rein lokal.', ['eyebrow' => 'Betrieb']);
?>
        <nav class="gdp-tabs" aria-label="GDP-Bereiche"><?= $tabs ?></nav>

<?php if (!$enabled) : ?>
        <section class="panel padded">
            <h2>GDP ist auf diesem Knoten nicht aktiviert</h2>
            <p class="muted">GBDB arbeitet vollständig lokal. Für einen Verbund in <code>.config/.framework.env.php</code> den Bereich <code>GDP</code> setzen – auf dem zentralen Knoten mit <code>role = hub</code>, auf lokalen Installationen mit <code>role = replica</code>. Anschließend gibt der Hub Instanzen frei und erzeugt Pairing-Codes.</p>
            <pre class="raw">"enabled" => true,
"role" => "replica",            // hub, replica oder observer
"hub_url" => "https://backend.example.com/gdp.php",
"sync_mode" => "embedded",      // embedded, worker oder manual</pre>
        </section>
<?php gbdbui_layout_end(); exit; endif; ?>

<?php if (($status['role_mismatch'] ?? false) === true) : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Rollenwechsel erkannt</strong><p>Die konfigurierte Rolle weicht von der gespeicherten Knotenidentität ab, obwohl Instanzen registriert sind. Bitte Konfiguration prüfen – ein stiller Rollenwechsel kann Daten gefährden.</p></div>
<?php endif; ?>
<?php if (($status['insecure_http'] ?? false) === true) : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('lock', 16) ?> Unverschlüsselter Transport erlaubt</strong><p><code>allow_insecure_http</code> ist aktiv. Nur für lokale Tests verwenden – im Betrieb ist HTTPS mit Zertifikatsprüfung Pflicht.</p></div>
<?php endif; ?>

<?php if ($oneTimeCode !== null) : ?>
        <section class="panel padded gdp-code-panel">
            <h2>Pairing-Code (wird nur jetzt angezeigt)</h2>
            <p class="muted">Auf dem Replikat unter „Knoten &amp; Pairing“ zusammen mit der Hub-URL eintragen. Gültig bis <?= gbdbui_e(gbdbui_gdp_time((int) $oneTimeCode['expires_at'])) ?>, nur einmal verwendbar.</p>
            <pre class="raw gdp-code"><?= gbdbui_e($oneTimeCode['code']) ?></pre>
        </section>
<?php endif; ?>

<?php if ($syncReport !== null) : ?>
        <section class="panel padded">
            <h2>Ergebnis der Synchronisation</h2>
<?php foreach ($syncReport['errors'] ?? [] as $error) : ?>
            <div class="notice notice-error"><strong><?= gbdbui_e($error['code'] ?? '') ?></strong><p><?= gbdbui_e($error['message'] ?? '') ?> (<?= gbdbui_e($error['retry'] ?? '') ?>)</p></div>
<?php endforeach; ?>
<?php if (isset($syncReport['skipped'])) : ?>
            <div class="notice notice-info"><?= gbdbui_e($syncReport['skipped']) ?></div>
<?php endif; ?>
            <details class="raw-details"><summary>Bericht anzeigen</summary><pre class="raw"><?= gbdbui_e(json_encode($syncReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre></details>
        </section>
<?php endif; ?>

<?php if ($view === 'overview') : ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-blue"><?= gbdbui_icon('server', 20) ?></span><?= gbdbui_gdp_state_badge((string) $status['connection_state']) ?></div>
                <div class="stat-label">Knoten · <?= gbdbui_e(GDPNodeRole::tryFrom((string) $status['role'])?->label() ?? '') ?></div>
                <div class="stat-number gdp-node"><?= gbdbui_e($status['display_name']) ?></div>
                <div class="stat-foot"><code><?= gbdbui_e($status['node_id']) ?></code></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon <?= $status['storage_state'] === 'sql' ? 'stat-orange' : 'stat-green' ?>"><?= gbdbui_icon('database', 20) ?></span></div>
                <div class="stat-label">Lokaler Speicher</div>
                <div class="stat-number"><?= gbdbui_e(strtoupper((string) $status['storage_state'])) ?></div>
                <div class="stat-foot">Journal wechselt mit dem Speicher</div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-purple"><?= gbdbui_icon('refresh', 20) ?></span></div>
                <div class="stat-label">Letzter erfolgreicher Sync</div>
                <div class="stat-number"><small><?= gbdbui_e(gbdbui_gdp_time((int) $status['last_successful_sync_at'])) ?></small></div>
                <div class="stat-foot">Modus <?= gbdbui_e($status['sync_mode']) ?> · Präferenz <?= gbdbui_e($status['preference']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon <?= array_sum(array_column($instances, 'pending_operations')) > 0 ? 'stat-orange' : 'stat-green' ?>"><?= gbdbui_icon('clock', 20) ?></span></div>
                <div class="stat-label">Ausstehende Operationen</div>
                <div class="stat-number"><?= (int) array_sum(array_column($instances, 'pending_operations')) ?></div>
                <div class="stat-foot"><?= (int) $status['nodes'] ?> bekannte Knoten · <?= count($instances) ?> Instanzen</div>
            </div>
        </div>

<?php if (($status['identity_drift'] ?? []) !== []) : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Knotenidentität passt nicht zur Konfiguration</strong><p><?= gbdbui_e(implode(', ', $status['identity_drift'])) ?>. Meist wurde die Installation samt <code>.gdp</code>-Daten kopiert; dann tragen beide Knoten dieselbe Knoten-ID <code><?= gbdbui_e($status['node_id']) ?></code>.</p>
<?php if ($status['identity_regenerable'] ?? false) : ?>
            <form method="post" data-confirm="Neue Knotenidentität nach der aktuellen Konfiguration anlegen? Der Knoten ist mit niemandem verbunden, es geht nichts verloren."><?= $csrfField ?><input type="hidden" name="gdp_action" value="regenerate_identity"><button class="btn btn-primary btn-sm"><?= gbdbui_icon('refresh', 14) ?> Identität neu erzeugen</button></form>
<?php else : ?>
            <p>Der Knoten ist bereits gekoppelt oder hat Replikate; die Identität bleibt deshalb unverändert. Bei Bedarf die Kopplung lösen und neu koppeln.</p>
<?php endif; ?>
        </div>
<?php endif; ?>

<?php $endpoint = $isHub ? gbdbui_gdp_endpoint() : (string) $status['hub_url']; ?>
        <section class="panel padded gdp-connection">
            <div class="panel-header">
                <div>
                    <h2><?= $isHub ? 'Endpunkt für Replikate' : 'Verbindung zum Hub' ?></h2>
                    <p class="muted"><?= $isHub ? 'Diese Adresse auf den Replikaten als Hub-URL eintragen. Der Test ruft sie vom Server aus auf.' : 'Prüft Erreichbarkeit, Protokoll, Identität, Uhrzeit und – nach dem Pairing – eine signierte Anfrage.' ?></p>
                </div>
                <form method="post"><?= $csrfField ?><input type="hidden" name="gdp_action" value="test_connection"><button class="btn btn-primary"><?= gbdbui_icon('activity', 16) ?> Verbindung testen</button></form>
            </div>
            <p class="gdp-endpoint"><?= $endpoint !== '' ? '<code>' . gbdbui_e($endpoint) . '</code>' : '<span class="muted">Keine Hub-URL gesetzt.</span>' ?><?= !$isHub && $status['backoff_until'] > time() ? ' <small class="muted">· nächster automatischer Versuch ab ' . gbdbui_e(gbdbui_gdp_time((int) $status['backoff_until'])) . '</small>' : '' ?></p>
<?php if ($connectionTest !== null) : ?>
            <div class="notice <?= $connectionTest['ok'] ? 'notice-success' : 'notice-error' ?>" role="status"><strong><?= $connectionTest['ok'] ? 'Verbindung in Ordnung' : 'Verbindung fehlerhaft' ?></strong><?php if ($connectionTest['latency_ms'] > 0) : ?><p>Antwortzeit <?= (int) $connectionTest['latency_ms'] ?> ms</p><?php endif; ?></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Prüfung</th><th>Ergebnis</th><th>Details</th></tr></thead><tbody>
<?php foreach ($connectionTest['checks'] as $check) : ?>
                <tr><td><strong><?= gbdbui_e($check['check']) ?></strong></td><td><?= ['ok' => '<span class="status-badge success">OK</span>', 'warn' => '<span class="status-badge warning">Hinweis</span>'][$check['state']] ?? '<span class="status-badge warning gbdb-badge-danger">Fehler</span>' ?></td><td><?= gbdbui_e($check['detail']) ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php endif; ?>
        </section>
<?php if ($status['sync_mode'] === 'embedded') : ?>
        <div class="notice notice-info"><strong><?= gbdbui_icon('clock', 16) ?> Embedded-Modus</strong><p>Synchronisiert wird nur während eingehender Anfragen (höchstens alle <?= (int) GDPConfig::get('embedded_interval') ?> s). Ohne Besucher findet keine Hintergrund-Synchronisation statt – für garantierte Abstände den Worker oder den Trigger nutzen.</p></div>
<?php endif; ?>

        <section class="panel">
            <div class="panel-header"><div><h2>Instanzen</h2><p class="muted">Schema, Policy, Checkpoints und Journal-Verzug je logischer Instanz. „Synchron“ erscheint nur nach Cursor-Abgleich.</p></div></div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Instanz</th><th>Zustand</th><th>Schema / Policy</th><th>Quellsequenz</th><th>Hubsequenz</th><th>Ausstehend / Verzug</th><th>Letzter Sync</th></tr></thead>
                    <tbody>
<?php if ($instances === []) : ?>
                        <tr><td colspan="7"><strong>Keine Instanz registriert</strong><small><?= $isHub ? 'Unter „Replikate“ eine GBDB-Instanz zur Replikation freigeben.' : 'Unter „Knoten & Pairing“ mit einem Hub koppeln.' ?></small></td></tr>
<?php endif; ?>
<?php foreach ($instances as $instanceId => $item) : ?>
                        <tr>
                            <td><strong><a href="<?= gbdbui_e(gbdbui_url('distributed', ['view' => 'sync', 'instance' => $instanceId])) ?>"><?= gbdbui_e($instanceId) ?></a></strong><small><?= $item['is_hub'] ? 'Hub-Instanz' : 'Hub ' . gbdbui_e($item['hub_node_id']) ?></small></td>
                            <td><?= gbdbui_gdp_state_badge((string) $item['sync_state']) ?><?php if (is_array($item['blocked'])) : ?><small><?= gbdbui_e($item['blocked']['code'] . ': ' . $item['blocked']['message']) ?></small><?php endif; ?></td>
                            <td>v<?= (int) $item['schema_version'] ?> / v<?= (int) $item['policy_version'] ?></td>
                            <td><?= (int) $item['source_sequence'] ?><small>bestätigt <?= (int) $item['accepted_source_sequence'] ?></small></td>
                            <td><?= (int) $item['applied_hub_sequence'] ?><small>Hub-Kopf <?= (int) $item['hub_sequence'] ?></small></td>
                            <td><?= (int) $item['pending_operations'] ?> / <?= (int) $item['journal_lag'] ?></td>
                            <td><?= gbdbui_e(gbdbui_gdp_time((int) $item['last_sync_at'])) ?></td>
                        </tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

<?php if (($status['failures'] ?? []) !== []) : ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Letzte Fehler</h2><p class="muted">Maschinenlesbare Codes mit Wiederholungsklasse: retry = automatisch erneut, check = Konfiguration prüfen, manual = Admin-Eingriff.</p></div></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Zeitpunkt</th><th>Code</th><th>Instanz</th><th>Meldung</th><th>Klasse</th></tr></thead><tbody>
<?php foreach ($status['failures'] as $failure) : ?>
                <tr><td><?= gbdbui_e($failure['at'] ?? '') ?></td><td><strong><?= gbdbui_e($failure['code'] ?? '') ?></strong></td><td><?= gbdbui_e($failure['instance'] ?? '') ?></td><td><?= gbdbui_e($failure['message'] ?? '') ?></td><td><?= gbdbui_e($failure['retry'] ?? '') ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php endif; ?>
<?php endif; ?>

<?php if ($view === 'nodes') : ?>
        <div class="gbdbui-page-grid">
            <section class="panel span-7">
                <div class="panel-header"><div><h2><?= $isHub ? 'Gekoppelte Knoten' : 'Hub' ?></h2><p class="muted"><?= $isHub ? 'Replikate und Observer mit Status, Schlüssel und Instanzen.' : 'Der zentrale Knoten, mit dem diese Installation synchronisiert.' ?></p></div></div>
                <div class="table-scroll"><table class="data-table"><thead><tr><th>Knoten</th><th>Rolle</th><th>Status</th><th>Letzter Kontakt</th><th>Aktionen</th></tr></thead><tbody>
<?php $nodes = GDP::getNodes(); ?>
<?php if ($nodes === []) : ?>
                    <tr><td colspan="5"><strong>Noch keine Knoten</strong><small><?= $isHub ? 'Pairing-Code erzeugen und auf dem Replikat eintragen.' : 'Mit dem Formular rechts koppeln.' ?></small></td></tr>
<?php endif; ?>
<?php foreach ($nodes as $nodeId => $node) : ?>
                    <tr>
                        <td><strong><?= gbdbui_e($node['display_name'] ?? $nodeId) ?></strong><small><code><?= gbdbui_e($nodeId) ?></code></small></td>
                        <td><?= gbdbui_e($node['role'] ?? '') ?><small><?= gbdbui_e(implode(', ', $node['instances'] ?? [])) ?></small></td>
                        <td><?= gbdbui_gdp_state_badge((string) ($node['status'] ?? '')) ?></td>
                        <td><?= gbdbui_e(gbdbui_gdp_time((int) ($node['last_contact_at'] ?? 0))) ?></td>
                        <td class="row-actions">
<?php if ($isHub && ($node['status'] ?? '') === 'pending_approval') : ?>
                            <form method="post"><?= $csrfField ?><input type="hidden" name="gdp_action" value="approve_node"><input type="hidden" name="node_id" value="<?= gbdbui_e($nodeId) ?>"><button class="btn btn-primary btn-sm">Freigeben</button></form>
<?php endif; ?>
<?php if ($isHub && ($node['status'] ?? '') !== 'revoked') : ?>
                            <form method="post" data-confirm="Knoten sperren? Schlüssel und Grants werden sofort widerrufen; das Replikat kann nicht mehr synchronisieren."><?= $csrfField ?><input type="hidden" name="gdp_action" value="revoke_node"><input type="hidden" name="node_id" value="<?= gbdbui_e($nodeId) ?>"><button class="btn btn-danger-outline btn-sm">Sperren</button></form>
<?php endif; ?>
                        </td>
                    </tr>
<?php endforeach; ?>
                </tbody></table></div>
            </section>
            <section class="panel padded span-5">
<?php if ($isHub) : ?>
                <h2>Pairing-Code erzeugen</h2>
                <p class="muted">Einmalig, zeitlich begrenzt (<?= (int) GDPConfig::get('pairing_ttl') ?> s). Der Knotenschlüssel wird verschlüsselt übergeben; der Code selbst verlässt das Replikat nie.</p>
<?php if ($instances === []) : ?>
                <div class="notice notice-info">Zuerst unter „Replikate“ eine Instanz freigeben.</div>
<?php else : ?>
                <form method="post">
                    <?= $csrfField ?><input type="hidden" name="gdp_action" value="create_pairing">
                    <div class="form-field"><label for="pair-instance">Instanz</label><select id="pair-instance" name="instance"><?php foreach (array_keys($instances) as $instanceId) : ?><option<?= $instanceId === $selected ? ' selected' : '' ?>><?= gbdbui_e($instanceId) ?></option><?php endforeach; ?></select></div>
                    <div class="form-field"><label for="pair-role">Rolle</label><select id="pair-role" name="role"><option value="replica">Replica (lesen und nach Policy schreiben)</option><option value="observer">Observer (nur lesen)</option></select></div>
                    <div class="form-field"><label>Rechte</label><div><?php foreach (GDPGrants::PERMISSIONS as $permission) : ?><label class="checkline"><input type="checkbox" name="permissions[]" value="<?= gbdbui_e($permission) ?>" checked> <?= gbdbui_e($permission) ?></label><?php endforeach; ?></div></div>
                    <label class="checkline"><input type="checkbox" name="approval" value="1"> Nach dem Pairing zusätzlich manuell freigeben</label>
                    <button class="btn btn-primary"><?= gbdbui_icon('link', 16) ?> Code erzeugen</button>
                </form>
<?php endif; ?>
<?php else : ?>
                <h2>Mit Hub koppeln</h2>
                <p class="muted">Hub-URL (bis einschließlich <code>gdp.php</code>) und den Einmalcode aus der Hub-Oberfläche eintragen. Diese Installation erhält eine dauerhafte <code>node_id</code> und einen eigenen Schlüssel.</p>
                <form method="post">
                    <?= $csrfField ?><input type="hidden" name="gdp_action" value="pair">
                    <div class="form-field"><label for="hub-url">Hub-URL</label><input id="hub-url" name="hub_url" value="<?= gbdbui_e($status['hub_url']) ?>" placeholder="https://backend.example.com/gdp.php" required></div>
                    <div class="form-field"><label for="pair-code">Pairing-Code</label><input id="pair-code" name="code" placeholder="GDP1-p…-…" autocomplete="off" required></div>
                    <div class="form-field"><label for="pair-name">Anzeigename dieses Knotens</label><input id="pair-name" name="display_name" value="<?= gbdbui_e($status['display_name']) ?>"></div>
                    <button class="btn btn-primary"><?= gbdbui_icon('link', 16) ?> Koppeln</button>
                </form>
<?php if (GDPCredentials::hubKey() !== null) : ?>
                <hr>
                <h3>Schlüssel rotieren</h3>
                <p class="muted">Erzeugt beim Hub einen neuen Knotenschlüssel; der alte wird erst nach erfolgreicher Bestätigung widerrufen.</p>
                <form method="post" data-confirm="Knotenschlüssel jetzt rotieren?"><?= $csrfField ?><input type="hidden" name="gdp_action" value="rotate"><button class="btn btn-secondary"><?= gbdbui_icon('refresh', 16) ?> Schlüssel rotieren</button></form>
<?php endif; ?>
<?php endif; ?>
            </section>
        </div>
<?php if ($isHub) : ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Pairing-Freigaben</h2><p class="muted">Codes werden nicht gespeichert, nur ein daraus abgeleiteter Schlüssel im geschützten Tresor.</p></div></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Freigabe</th><th>Instanzen</th><th>Rolle</th><th>Gültig bis</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach (GDP::pairingSessions() as $pairingId => $session) : ?>
                <tr><td><code><?= gbdbui_e($pairingId) ?></code></td><td><?= gbdbui_e(implode(', ', $session['instances'])) ?></td><td><?= gbdbui_e($session['role']) ?></td><td><?= gbdbui_e(gbdbui_gdp_time((int) $session['expires_at'])) ?></td>
                    <td><?= (int) $session['used_at'] > 0 ? '<span class="status-badge success">verwendet von ' . gbdbui_e($session['node_id']) . '</span>' : ((int) $session['expires_at'] < time() ? '<span class="status-badge neutral">abgelaufen</span>' : '<span class="status-badge info">offen</span>') ?></td>
                    <td><?php if ((int) $session['used_at'] === 0 && (int) $session['expires_at'] > time()) : ?><form method="post"><?= $csrfField ?><input type="hidden" name="gdp_action" value="revoke_pairing"><input type="hidden" name="pairing_id" value="<?= gbdbui_e($pairingId) ?>"><button class="btn btn-danger-outline btn-sm">Widerrufen</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php endif; ?>
<?php endif; ?>

<?php if ($view === 'replicas') : ?>
<?php if ($isHub) : ?>
        <section class="panel padded">
            <h2>Instanz zur Replikation freigeben</h2>
            <p class="muted">Vorhandene Daten bleiben unverändert. Ohne Policy gilt der Standardmodus; „central_owner“ ist der sichere Ausgangspunkt (nur der Hub schreibt).</p>
<?php
    $candidates = array_values(array_filter(GBDB::listInstances(), static fn(string $name) :bool => !isset($instances[$name]) && !GBDB::isReservedInstance($name) && $name !== 'default'));
?>
            <form method="post" class="gbdbui-inline-form">
                <?= $csrfField ?><input type="hidden" name="gdp_action" value="enable_instance">
                <label>GBDB-Instanz<select name="instance"><?php foreach ($candidates as $candidate) : ?><option><?= gbdbui_e($candidate) ?></option><?php endforeach; ?></select></label>
                <label>Standardmodus<select name="default_mode"><?php foreach (GDPWriteMode::cases() as $mode) : ?><option value="<?= gbdbui_e($mode->value) ?>"><?= gbdbui_e($mode->label()) ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-primary"<?= $candidates === [] ? ' disabled' : '' ?>>Freigeben</button>
            </form>
        </section>
<?php endif; ?>
<?php foreach ($instances as $instanceId => $item) : ?>
        <section class="panel">
            <div class="panel-header"><div><h2><?= gbdbui_e($instanceId) ?></h2><p class="muted"><?= $item['is_hub'] ? 'Replikate dieser Hub-Instanz mit Grants und Cursorn.' : 'Diese Installation ist ein Replikat dieser Instanz.' ?></p></div>
<?php if ($isHub) : ?>
                <form method="post" data-confirm="Replikation beenden? Alle Grants dieser Instanz werden widerrufen; Replikate müssen später neu initialisiert werden."><?= $csrfField ?><input type="hidden" name="gdp_action" value="disable_instance"><input type="hidden" name="instance" value="<?= gbdbui_e($instanceId) ?>"><button class="btn btn-danger-outline btn-sm">Replikation beenden</button></form>
<?php endif; ?>
            </div>
<?php if ($item['is_hub']) : ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Replikat</th><th>Rechte</th><th>Quell-Cursor</th><th>Hub-Cursor / Verzug</th><th>Letzte Bestätigung</th><th>Strom</th></tr></thead><tbody>
<?php $replicas = GDP::getReplicas($instanceId); ?>
<?php if ($replicas === []) : ?>
                <tr><td colspan="6"><strong>Noch kein Replikat</strong><small>Unter „Knoten &amp; Pairing“ einen Code erzeugen.</small></td></tr>
<?php endif; ?>
<?php foreach ($replicas as $replica) : ?>
                <tr>
                    <td><strong><?= gbdbui_e($replica['display_name']) ?></strong><small><code><?= gbdbui_e($replica['replica_id']) ?></code></small></td>
                    <td><?= gbdbui_e(implode(', ', $replica['permissions'])) ?><small><?= $replica['allowed_scope'] === null ? 'alle replizierten Tabellen' : gbdbui_e(implode(', ', $replica['allowed_scope']['tables'])) ?></small></td>
                    <td><?= (int) $replica['cursor']['accepted_source_sequence'] ?></td>
                    <td><?= (int) $replica['cursor']['applied_hub_sequence'] ?><small>Verzug <?= max(0, (int) $item['hub_sequence'] - (int) $replica['cursor']['applied_hub_sequence']) ?></small></td>
                    <td><?= gbdbui_e(gbdbui_gdp_time((int) $replica['cursor']['last_confirmed_at'])) ?></td>
                    <td><?php if (is_array($replica['cursor']['blocked'] ?? null)) : ?><span class="status-badge warning gbdb-badge-danger">BLOCKED</span><small><?= gbdbui_e($replica['cursor']['blocked']['code'] . ': ' . $replica['cursor']['blocked']['message']) ?></small>
                        <form method="post" data-confirm="Strom freigeben? Das Replikat sollte vorher per Re-Snapshot neu initialisiert werden, sonst blockiert dieselbe Operation erneut."><?= $csrfField ?><input type="hidden" name="gdp_action" value="unblock_peer"><input type="hidden" name="instance" value="<?= gbdbui_e($instanceId) ?>"><input type="hidden" name="node_id" value="<?= gbdbui_e($replica['node_id']) ?>"><button class="btn btn-secondary btn-sm">Freigeben</button></form>
                        <?php else : ?><span class="status-badge success">offen</span><?php endif; ?></td>
                </tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php else : ?>
            <div class="padded"><dl class="gbdb-kv"><dt>Hub</dt><dd><code><?= gbdbui_e($item['hub_node_id']) ?></code></dd><dt>Bootstrap-Snapshot</dt><dd><?= gbdbui_e($item['current_snapshot_id'] ?: '—') ?></dd><dt>Angewendete Hubsequenz</dt><dd><?= (int) $item['applied_hub_sequence'] ?></dd><dt>Bestätigte Quellsequenz</dt><dd><?= (int) $item['accepted_source_sequence'] ?> von <?= (int) $item['source_sequence'] ?></dd></dl></div>
<?php endif; ?>
        </section>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($view === 'policies' && $selected !== '') : ?>
<?php $policies = GDP::getPolicies($selected); ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Policies für <?= gbdbui_e($selected) ?></h2><p class="muted">Policy-Version <?= (int) $policies['policy_version'] ?> · Standardmodus <strong><?= gbdbui_e(GDPWriteMode::from($policies['default_mode'])->label()) ?></strong>. Spezifischste Regel gewinnt: Tabelle vor Datenbank (*) vor Instanz. Interne Bases (<code>_gbdb_*</code>, <code>system</code>) sind immer LOCAL_ONLY.</p></div>
                <form method="get" class="gbdbui-inline-form"><input type="hidden" name="tool" value="distributed"><input type="hidden" name="view" value="policies"><label>Instanz<select name="instance" onchange="this.form.submit()"><?php foreach (array_keys($instances) as $instanceId) : ?><option<?= $instanceId === $selected ? ' selected' : '' ?>><?= gbdbui_e($instanceId) ?></option><?php endforeach; ?></select></label></form>
            </div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Bereich</th><th>Modus</th><th>Eigentümer</th><th>Offline</th><th>Lease</th><th>Epoche</th><th></th></tr></thead><tbody>
<?php if ($policies['rules'] === []) : ?>
                <tr><td colspan="7"><strong>Keine Regeln</strong><small>Für alle Tabellen gilt der Standardmodus.</small></td></tr>
<?php endif; ?>
<?php foreach ($policies['rules'] as $rule) : ?>
                <tr>
                    <td><strong><?= gbdbui_e($rule['database'] . '.' . $rule['table']) ?></strong><small><code><?= gbdbui_e($rule['policy_id']) ?></code></small></td>
                    <td><?= gbdbui_e(GDPWriteMode::from($rule['mode'])->label()) ?></td>
                    <td><?= gbdbui_e($rule['owner_column'] !== '' ? 'je Datensatz: Spalte ' . $rule['owner_column'] : ($rule['owner_node_id'] ?: '—')) ?><?php if (is_array($rule['transfer'] ?? null)) : ?><small>Wechsel zu <?= gbdbui_e($rule['transfer']['to']) ?> läuft</small><?php endif; ?></td>
                    <td><?= $rule['offline_permission'] ? 'erlaubt' : 'gesperrt' ?></td>
                    <td><?= (int) $rule['lease_seconds'] > 0 ? (int) $rule['lease_seconds'] . ' s' : '—' ?></td>
                    <td><?= (int) $rule['authority_epoch'] ?></td>
                    <td class="row-actions">
<?php if ($isHub) : ?>
                        <form method="post" data-confirm="Regel entfernen? Danach gilt der Standardmodus für diesen Bereich."><?= $csrfField ?><input type="hidden" name="gdp_action" value="remove_policy"><input type="hidden" name="instance" value="<?= gbdbui_e($selected) ?>"><input type="hidden" name="database" value="<?= gbdbui_e($rule['database']) ?>"><input type="hidden" name="table" value="<?= gbdbui_e($rule['table']) ?>"><button class="btn btn-danger-outline btn-sm">Entfernen</button></form>
<?php endif; ?>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php if ($isHub) : ?>
        <div class="gbdbui-page-grid">
            <section class="panel padded span-7">
                <h2>Regel setzen</h2>
                <p class="muted">CENTRAL_OWNER: nur der Hub schreibt. NODE_OWNER: genau ein Knoten (oder je Datensatz über eine Spalte). APPEND_ONLY: nur neue, unveränderliche Einträge. LOCAL_ONLY: nie repliziert. Ein Moduswechsel erhöht die Autoritätsepoche.</p>
                <form method="post" class="gbdbui-form-grid">
                    <?= $csrfField ?><input type="hidden" name="gdp_action" value="set_policy"><input type="hidden" name="instance" value="<?= gbdbui_e($selected) ?>">
                    <label class="field-4">Datenbank (oder *)<input name="database" required></label>
                    <label class="field-4">Tabelle (oder *)<input name="table" required></label>
                    <label class="field-4">Modus<select name="mode"><?php foreach (GDPWriteMode::cases() as $mode) : ?><option value="<?= gbdbui_e($mode->value) ?>"><?= gbdbui_e($mode->label()) ?></option><?php endforeach; ?></select></label>
                    <label class="field-6">Eigentümer-Knoten (NODE_OWNER)<input name="owner_node_id" placeholder="node-…"></label>
                    <label class="field-6">oder Eigentümer-Spalte je Datensatz<input name="owner_column" placeholder="site"></label>
                    <label class="field-6">Schreib-Lease in Sekunden (0 = ohne)<input name="lease_seconds" type="number" min="0" value="0"></label>
                    <label class="field-6">Erlaubte Schreiber (APPEND_ONLY, leer = alle)<input name="writers" placeholder="node-a, node-b"></label>
                    <label class="checkline field-12"><input type="checkbox" name="offline_permission" value="1"> Offline schreiben erlauben (nur bei exklusiver Schreibhoheit)</label>
                    <div class="field-12"><button class="btn btn-primary">Policy speichern</button></div>
                </form>
            </section>
            <section class="panel padded span-5">
                <h2>Standardmodus</h2>
                <form method="post" class="gbdbui-inline-form"><?= $csrfField ?><input type="hidden" name="gdp_action" value="default_mode"><input type="hidden" name="instance" value="<?= gbdbui_e($selected) ?>"><label>Modus<select name="mode"><?php foreach (GDPWriteMode::cases() as $mode) : ?><option value="<?= gbdbui_e($mode->value) ?>"<?= $mode->value === $policies['default_mode'] ? ' selected' : '' ?>><?= gbdbui_e($mode->label()) ?></option><?php endforeach; ?></select></label><button class="btn btn-secondary">Übernehmen</button></form>
                <hr>
                <h2>Eigentümerwechsel</h2>
                <p class="muted">Mit Fencing: Der alte Eigentümer verliert sofort die Schreibhoheit, überträgt seine offenen Operationen, dann übernimmt der neue Knoten mit höherer Epoche.</p>
                <form method="post" data-confirm="Eigentümerwechsel starten? Bis zum Abschluss kann niemand in diesem Bereich schreiben.">
                    <?= $csrfField ?><input type="hidden" name="gdp_action" value="transfer"><input type="hidden" name="instance" value="<?= gbdbui_e($selected) ?>">
                    <div class="form-field"><label>Bereich</label><select name="database" class="gdp-transfer-db"><?php foreach ($policies['rules'] as $rule) : ?><?php if ($rule['mode'] === 'node_owner' && $rule['owner_column'] === '') : ?><option value="<?= gbdbui_e($rule['database']) ?>" data-table="<?= gbdbui_e($rule['table']) ?>"><?= gbdbui_e($rule['database'] . '.' . $rule['table']) ?></option><?php endif; ?><?php endforeach; ?></select></div>
                    <input type="hidden" name="table" class="gdp-transfer-table" value="">
                    <div class="form-field"><label>Neuer Eigentümer</label><input name="new_owner" placeholder="node-…" required></div>
                    <button class="btn btn-secondary">Wechsel starten</button>
                </form>
                <form method="post" data-confirm="Wechsel nach Ablauf der alten Lease erzwingen? Spät eintreffende Operationen des alten Eigentümers werden dann abgewiesen."><?= $csrfField ?><input type="hidden" name="gdp_action" value="force_transfer"><input type="hidden" name="instance" value="<?= gbdbui_e($selected) ?>"><button class="btn btn-plain">Abgelaufene Wechsel abschließen</button></form>
            </section>
        </div>
        <script>
            document.querySelectorAll('.gdp-transfer-db').forEach(select => {
                const sync = () => { select.form.querySelector('.gdp-transfer-table').value = select.selectedOptions[0]?.dataset.table || ''; };
                select.addEventListener('change', sync);
                sync();
            });
        </script>
<?php endif; ?>
<?php elseif ($view === 'policies') : ?>
        <section class="panel padded"><h2>Keine Instanz</h2><p class="muted">Zuerst eine Instanz registrieren.</p></section>
<?php endif; ?>

<?php if ($view === 'sync') : ?>
        <div class="gbdbui-page-grid">
            <section class="panel padded span-7">
                <h2>Manuell synchronisieren</h2>
<?php if ($isHub) : ?>
                <p class="muted">Der Hub synchronisiert nicht selbst; Replikate verbinden sich zu ihm. Hier läuft nur die Journal-Wartung (Retention).</p>
<?php else : ?>
                <p class="muted">Ein vollständiger Durchlauf: Handshake, ausstehende Operationen übertragen, Hub-Ereignisse anwenden und bestätigen. Netzwerkzugriffe laufen nie innerhalb einer lokalen Speichertransaktion.</p>
<?php endif; ?>
                <form method="post" class="gbdbui-inline-form"><?= $csrfField ?><input type="hidden" name="gdp_action" value="sync">
                    <label>Instanz<select name="instance"><option value="">Alle</option><?php foreach (array_keys($instances) as $instanceId) : ?><option<?= $instanceId === $selected ? ' selected' : '' ?>><?= gbdbui_e($instanceId) ?></option><?php endforeach; ?></select></label>
                    <button class="btn btn-primary"><?= gbdbui_icon('refresh', 16) ?> Jetzt synchronisieren</button>
                </form>
            </section>
            <section class="panel padded span-5">
                <h2>Betriebspräferenz</h2>
                <p class="muted">AUTO: automatisch verbinden. FORCE_LOCAL: keine zentrale Kommunikation (Wartung/Test), nur offline erlaubte Schreibvorgänge. PREFER_CENTRAL: zentrale Synchronisation bevorzugen. Keine Präferenz erteilt zusätzliche Schreibrechte.</p>
                <form method="post" class="gbdbui-inline-form"><?= $csrfField ?><input type="hidden" name="gdp_action" value="preference">
                    <label>Präferenz<select name="preference"><?php foreach (GDPOperationPreference::cases() as $preference) : ?><option value="<?= gbdbui_e($preference->value) ?>"<?= $preference->value === $status['preference'] ? ' selected' : '' ?>><?= gbdbui_e(strtoupper($preference->value)) ?></option><?php endforeach; ?></select></label>
                    <button class="btn btn-secondary">Speichern</button>
                </form>
            </section>
        </div>
<?php foreach ($instances as $instanceId => $item) : ?>
<?php if (!$item['is_hub'] && in_array($item['sync_state'], ['blocked', 'resync', 'initializing'], true)) : ?>
        <section class="panel padded gbdbui-danger-zone">
            <h2><?= gbdbui_e($instanceId) ?>: <?= gbdbui_gdp_state_badge((string) $item['sync_state']) ?></h2>
<?php if (is_array($item['blocked'])) : ?>
            <p><strong><?= gbdbui_e($item['blocked']['code']) ?></strong> – <?= gbdbui_e($item['blocked']['message']) ?></p>
            <details class="raw-details"><summary>Diagnosekontext</summary><pre class="raw"><?= gbdbui_e(json_encode($item['blocked']['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre></details>
<?php endif; ?>
<?php if (is_array($item['bootstrap_preflight'])) : ?>
            <div class="notice notice-error"><strong>Bestätigung nötig</strong><p>Lokal vorhanden: <?= gbdbui_e(implode(', ', $item['bootstrap_preflight']['populated']) ?: 'keine Tabellen') ?> · nicht übertragene Operationen: <?= (int) $item['bootstrap_preflight']['unsent'] ?>. Beim Ersetzen entsteht vorher automatisch eine Vollsicherung.</p></div>
<?php endif; ?>
            <div class="gbdbui-action-row">
                <form method="post" data-confirm="Erneut versuchen? Nur sinnvoll, wenn die Ursache behoben ist (z. B. Schema angeglichen)."><?= $csrfField ?><input type="hidden" name="gdp_action" value="resolve"><input type="hidden" name="instance" value="<?= gbdbui_e($instanceId) ?>"><input type="hidden" name="how" value="retry"><button class="btn btn-secondary">Erneut versuchen</button></form>
                <form method="post" data-confirm="Neu initialisieren (Snapshot laden)? Vorhandene Bestandsdaten werden nur mit ausdrücklicher Bestätigung ersetzt."><?= $csrfField ?><input type="hidden" name="gdp_action" value="bootstrap"><input type="hidden" name="instance" value="<?= gbdbui_e($instanceId) ?>"><button class="btn btn-primary">Neu initialisieren</button></form>
                <form method="post" data-confirm="Lokale Daten der replizierten Tabellen durch den Hub-Stand ERSETZEN? Vorher wird automatisch eine Vollsicherung erstellt; nicht übertragene lokale Operationen gehen in den replizierten Tabellen verloren."><?= $csrfField ?><input type="hidden" name="gdp_action" value="bootstrap"><input type="hidden" name="instance" value="<?= gbdbui_e($instanceId) ?>"><input type="hidden" name="replace_local" value="1"><button class="btn btn-danger-outline">Lokalen Stand ersetzen</button></form>
            </div>
        </section>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!$isHub && $selected !== '') : ?>
<?php $pending = GDP::getPendingOperations($selected, 50); ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Ausstehende Operationen · <?= gbdbui_e($selected) ?></h2><p class="muted">Dauerhaft im lokalen Journal, noch nicht vom Hub bestätigt.</p></div></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Seq.</th><th>Operation</th><th>Bereich</th><th>Datensatz</th><th>Version</th><th>Erstellt</th></tr></thead><tbody>
<?php if ($pending === []) : ?>
                <tr><td colspan="6"><strong>Nichts ausstehend</strong></td></tr>
<?php endif; ?>
<?php foreach ($pending as $entry) : ?>
                <tr><td><?= (int) $entry['source_sequence'] ?></td><td><strong><?= gbdbui_e($entry['operation']) ?></strong></td><td><?= gbdbui_e($entry['database'] . '.' . $entry['table']) ?></td><td><?= gbdbui_e($entry['record_id']) ?></td><td><?= (int) $entry['previous_version'] ?> → <?= (int) $entry['new_version'] ?></td><td><?= gbdbui_e($entry['created_at']) ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php endif; ?>
<?php endif; ?>

<?php if ($view === 'journal' && $selected !== '') : ?>
<?php
    $log = ($_GET['log'] ?? ($isHub ? 'hub' : 'outbox')) === 'hub' ? 'hub' : 'outbox';
    $from = max(1, (int) ($_GET['from'] ?? 1));
    $filterTable = preg_match('/^[a-zA-Z0-9_\-äöüÄÖÜß]+\.[a-zA-Z0-9_\-äöüÄÖÜß]+$/uD', (string) ($_GET['table'] ?? '')) ? (string) $_GET['table'] : '';
    $journalError = '';

    try {
        $journal = GDP::journal($selected, $log, $from, 50, $filterTable);
    } catch (GDPException $exception) {
        $journal = ['entries' => [], 'through' => $from - 1, 'more' => false];
        $journalError = $exception->errorCode()->value . ': ' . $exception->getMessage();
    }

    $checkpoints = GDP::getCheckpoints($selected);
?>
        <section class="panel">
            <div class="panel-header"><div><h2>Journal · <?= gbdbui_e($selected) ?></h2><p class="muted">Append-only. Nutzdaten werden hier nicht angezeigt. Retention-Grenze: Ausgang ab <?= (int) $checkpoints['sequences']['floor_outbox'] ?>, Hub ab <?= (int) $checkpoints['sequences']['floor_hub'] ?>.</p></div>
                <form method="get" class="gbdbui-inline-form"><input type="hidden" name="tool" value="distributed"><input type="hidden" name="view" value="journal">
                    <label>Instanz<select name="instance"><?php foreach (array_keys($instances) as $instanceId) : ?><option<?= $instanceId === $selected ? ' selected' : '' ?>><?= gbdbui_e($instanceId) ?></option><?php endforeach; ?></select></label>
                    <label>Journal<select name="log"><option value="outbox"<?= $log === 'outbox' ? ' selected' : '' ?>>Ausgang (Quellsequenz)</option><option value="hub"<?= $log === 'hub' ? ' selected' : '' ?>>Hub (Hubsequenz)</option></select></label>
                    <label>Ab Sequenz<input name="from" type="number" min="1" value="<?= $from ?>"></label>
                    <label>Tabelle (db.tabelle)<input name="table" value="<?= gbdbui_e($filterTable) ?>"></label>
                    <button class="btn btn-secondary">Anzeigen</button>
                </form>
            </div>
<?php if ($journalError !== '') : ?>
            <div class="padded"><div class="notice notice-error"><?= gbdbui_e($journalError) ?></div></div>
<?php endif; ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Quelle</th><th>Hub</th><th>Operation</th><th>Bereich / Datensatz</th><th>Version</th><th>Ursprung</th><th>Gruppe</th><th>Hash</th></tr></thead><tbody>
<?php if ($journal['entries'] === []) : ?>
                <tr><td colspan="8"><strong>Keine Einträge in diesem Bereich</strong></td></tr>
<?php endif; ?>
<?php foreach ($journal['entries'] as $entry) : ?>
                <tr><td><?= (int) $entry['source_sequence'] ?></td><td><?= $entry['hub_sequence'] === null ? '—' : (int) $entry['hub_sequence'] ?></td><td><strong><?= gbdbui_e($entry['operation']) ?></strong><small><?= gbdbui_e($entry['created_at']) ?></small></td><td><?= gbdbui_e($entry['database'] . '.' . $entry['table']) ?><small>#<?= gbdbui_e($entry['record_id']) ?></small></td><td><?= (int) $entry['previous_version'] ?> → <?= (int) $entry['new_version'] ?><small>Epoche <?= (int) $entry['authority_epoch'] ?></small></td><td><code><?= gbdbui_e($entry['origin_node_id']) ?></code></td><td><?= $entry['transaction_group_id'] === null ? '—' : '<code>' . gbdbui_e(substr($entry['transaction_group_id'], 0, 13)) . '…</code>' ?></td><td><code><?= gbdbui_e(substr($entry['payload_hash'], 7, 12)) ?>…</code></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php if ($journal['more']) : ?>
            <div class="padded"><a class="btn btn-secondary btn-sm" href="<?= gbdbui_e(gbdbui_url('distributed', ['view' => 'journal', 'instance' => $selected, 'log' => $log, 'from' => $journal['through'] + 1, 'table' => $filterTable])) ?>">Weitere Einträge</a></div>
<?php endif; ?>
        </section>
<?php endif; ?>

<?php if ($view === 'snapshots') : ?>
        <section class="panel padded">
            <h2>Snapshots</h2>
<?php if ($isHub) : ?>
            <p class="muted">Ein Snapshot ist ein konsistentes Abbild aller replizierten Tabellen bei einer Hubsequenz S. Replikate laden ihn in prüfbaren Chunks, aktivieren ihn atomar und holen danach alle Ereignisse ab S + 1 nach. Jeder neue Bootstrap erhält einen frischen Snapshot; abgebrochene Downloads werden fortgesetzt.</p>
            <form method="post" class="gbdbui-inline-form"><?= $csrfField ?><input type="hidden" name="gdp_action" value="snapshot">
                <label>Instanz<select name="instance"><?php foreach (array_keys($instances) as $instanceId) : ?><option<?= $instanceId === $selected ? ' selected' : '' ?>><?= gbdbui_e($instanceId) ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-primary"<?= $instances === [] ? ' disabled' : '' ?>><?= gbdbui_icon('save', 16) ?> Snapshot erstellen</button>
            </form>
<?php else : ?>
            <p class="muted">Replikate erhalten Snapshots vom Hub. Neu initialisieren unter „Synchronisation“; vorhandene Bestandsdaten werden nur nach ausdrücklicher Bestätigung und automatischer Sicherung ersetzt.</p>
<?php endif; ?>
        </section>
<?php foreach ($instances as $instanceId => $item) : ?>
        <section class="panel padded"><h2><?= gbdbui_e($instanceId) ?></h2><dl class="gbdb-kv"><dt>Aktueller Snapshot</dt><dd><?= gbdbui_e($item['current_snapshot_id'] ?: '—') ?></dd><dt>Schema-Version</dt><dd><?= (int) $item['schema_version'] ?></dd><dt>Hubsequenz</dt><dd><?= (int) $item['hub_sequence'] ?></dd></dl></section>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($view === 'security') : ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Audit-Log</h2><p class="muted">Getrennt vom Debug-Log. Pairings, Rechteänderungen, Widerrufe, Blockaden und abgewiesene Zugriffe – ohne Codes, Schlüssel oder Tokens.</p></div></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Zeitpunkt</th><th>Ereignis</th><th>Akteur</th><th>Details</th></tr></thead><tbody>
<?php foreach (GDP::auditLog(100) as $event) : ?>
                <tr><td><?= gbdbui_e($event['created_at'] ?? '') ?></td><td><strong><?= gbdbui_e($event['event_type'] ?? '') ?></strong></td><td><?= gbdbui_e($event['actor'] ?? '') ?></td><td><code><?= gbdbui_e(json_encode($event['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php if ($isHub) : ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Grants</h2><p class="muted">Knotengebundene Rechte je Instanz. Ein bekannter Knoten hat nicht automatisch Zugriff auf alle Daten.</p></div></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Knoten</th><th>Instanz</th><th>Rechte</th><th>Bereich</th><th>Status</th></tr></thead><tbody>
<?php foreach (GDPStore::read(static fn() :array => GDPGrants::all()) as $grant) : ?>
                <tr><td><code><?= gbdbui_e($grant['node_id']) ?></code></td><td><?= gbdbui_e($grant['instance_id']) ?></td><td><?= gbdbui_e(implode(', ', $grant['permissions'])) ?></td><td><?= $grant['allowed_scope'] === null ? 'alle' : gbdbui_e(implode(', ', $grant['allowed_scope']['tables'])) ?></td><td><?= (int) $grant['revoked_at'] > 0 ? '<span class="status-badge neutral">widerrufen</span>' : '<span class="status-badge success">aktiv</span>' ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php endif; ?>
<?php endif; ?>

<?php if ($view === 'diagnostics') : ?>
<?php $diagnostics = GDP::getDiagnostics(isset($_GET['network'])); ?>
        <section class="panel">
            <div class="panel-header"><div><h2>Diagnose</h2><p class="muted">Konfiguration, Speicher, Schlüssel und Journal-Integrität (lückenlose Reihenfolge, Payload-Hashes). Der Verbindungstest kontaktiert den Hub.</p></div><a class="btn btn-secondary btn-sm" href="<?= gbdbui_e(gbdbui_url('distributed', ['view' => 'diagnostics', 'network' => 1])) ?>"><?= gbdbui_icon('globe', 14) ?> Mit Verbindungstest</a></div>
            <div class="table-scroll"><table class="data-table"><thead><tr><th>Prüfung</th><th>Ergebnis</th><th>Details</th></tr></thead><tbody>
<?php foreach ($diagnostics as $check) : ?>
                <tr><td><strong><?= gbdbui_e($check['check']) ?></strong></td><td><?= $check['ok'] ? '<span class="status-badge success">OK</span>' : '<span class="status-badge warning gbdb-badge-danger">Prüfen</span>' ?></td><td><?= gbdbui_e($check['detail']) ?></td></tr>
<?php endforeach; ?>
            </tbody></table></div>
        </section>
<?php endif; ?>
<?php gbdbui_layout_end(); ?>
