<?php

declare(strict_types=1);

gbdbui_require_tool('storage');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!GreenQLUIv2Helper::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Ungültiger Sicherheits-Token.');
    }

    try {
        if (($_POST['storage_action'] ?? '') === 'sync') {
            GBDB::synchronizeSQL();
            gbdbui_flash('ok', 'Datenbestand synchronisiert und geprüft.');
        } elseif (($_POST['storage_action'] ?? '') === 'switch') {
            $result = GBDB::switchBackend((string) ($_POST['backend'] ?? ''));
            gbdbui_flash('ok', 'Aktiver Speicher: ' . strtoupper($result['mode']));
        }
    } catch (Throwable $exception) {
        gbdbui_flash('bad', $exception->getMessage());
    }

    gbdbui_redirect('storage');
}

$storage = GBDB::backendStatus();
$isSql = $storage['mode'] === 'sql';
$csrf = GreenQLUIv2Helper::csrf();
gbdbui_layout_start('storage', 'Speicher & SQL-Fallback', 'Aktiven Speicher prüfen, den SQL-Spiegel abgleichen und kontrolliert zwischen GBDB und SQL umschalten.', ['eyebrow' => 'Betrieb']);
?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon <?= $isSql ? 'stat-orange' : 'stat-blue' ?>"><?= gbdbui_icon('database', 20) ?></span></div>
                <div class="stat-label">Aktiver Speicher</div>
                <div class="stat-number"><?= gbdbui_e(strtoupper((string) $storage['mode'])) ?></div>
                <div class="stat-foot"><span class="tiny-dot <?= $isSql ? 'orange-dot' : 'green-dot' ?>"></span><?= $isSql ? 'Fallback aktiv – Rückschaltung prüfen' : 'Primärspeicher aktiv' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-purple"><?= gbdbui_icon('server', 20) ?></span></div>
                <div class="stat-label">SQL-Fallback</div>
                <div class="stat-number"><?= $storage['enabled'] ? ($storage['sql_available'] ? 'Erreichbar' : 'Getrennt') : 'Aus' ?></div>
                <div class="stat-foot"><span class="tiny-dot <?= $storage['sql_ready'] ? 'green-dot' : 'orange-dot' ?>"></span><?= $storage['sql_ready'] ? 'Vollständig synchronisiert' : 'Nicht einsatzbereit' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-green"><?= gbdbui_icon('refresh', 20) ?></span></div>
                <div class="stat-label">Letzter Abgleich</div>
                <div class="stat-number"><small><?= $storage['last_sync'] ? gbdbui_e(date('d.m.Y H:i', (int) $storage['last_sync'])) : 'Noch keiner' ?></small></div>
                <div class="stat-foot"><span class="tiny-dot <?= $storage['sync_pending'] ? 'orange-dot' : 'green-dot' ?>"></span><?= $storage['sync_pending'] ? 'Abgleich ausstehend' : 'Kein Abgleich ausstehend' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-head"><span class="stat-icon stat-blue"><?= gbdbui_icon('shield', 20) ?></span></div>
                <div class="stat-label">Umschaltgeneration</div>
                <div class="stat-number"><?= (int) $storage['generation'] ?></div>
                <div class="stat-foot"><?= gbdbui_e($storage['reason'] !== '' ? $storage['reason'] : 'Keine Umschaltung protokolliert') ?></div>
            </div>
        </div>

<?php if ($storage['last_error'] !== '') : ?>
        <div class="notice notice-error" role="alert"><strong><?= gbdbui_icon('alert', 16) ?> Speicherhinweis</strong><p><?= gbdbui_e($storage['last_error']) ?></p></div>
<?php endif; ?>

        <div class="gbdbui-page-grid">
            <section class="panel padded span-7">
                <h2>Speicher wechseln</h2>
                <p class="muted">Änderungen werden während normaler PHP-Anfragen gespiegelt. Nach einem Ausfall bleibt SQL aktiv, bis der aktuelle SQL-Datenbestand geprüft nach GBDB zurückkopiert wurde. Ein veralteter SQL-Stand wird nie stillschweigend aktiviert.</p>
                <form method="post" data-confirm="Aktiven Speicher wirklich wechseln? Laufende Anfragen warten kurz auf den Speicher-Lock.">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                    <input type="hidden" name="storage_action" value="switch">
                    <div class="form-field">
                        <label for="storage-backend">Aktiven Speicher wählen</label>
                        <select id="storage-backend" name="backend">
                            <option value="gbdb"<?= $storage['mode'] === 'gbdb' ? ' selected' : '' ?>>GBDB (nativer Dateispeicher)</option>
                            <option value="sql"<?= $isSql ? ' selected' : '' ?>>SQL (MySQL/MariaDB oder SQLite)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= gbdbui_icon('refresh', 16) ?> Speicher wechseln</button>
                </form>
            </section>
            <section class="panel padded span-5">
                <h2>Vollständig abgleichen</h2>
                <p class="muted">Kopiert den aktiven Bestand geprüft in den jeweils anderen Speicher. Im SQL-Betrieb wird der GBDB-Stand neu aufgebaut, der bisherige Ordner bleibt erhalten.</p>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                    <input type="hidden" name="storage_action" value="sync">
                    <button type="submit" class="btn btn-secondary"><?= gbdbui_icon('database', 16) ?> Jetzt vollständig synchronisieren</button>
                </form>
                <hr>
                <p class="muted">Die Verbindung steht in <code>.config/.framework.env.php</code> in der Konstante <code>FAILOVER</code>. Das Tabellenpräfix muss je Installation eindeutig sein.</p>
<?php if (gbdbui_can_tool('distributed')) : ?>
                <p class="muted">GDP-Journal und -Cursor liegen im selben Datenbestand und wechseln mit dem Speicher – es entsteht keine zweite Journalhistorie. <a href="<?= gbdbui_e(gbdbui_url('distributed')) ?>">Zum GDP-Status</a></p>
<?php endif; ?>
            </section>
        </div>
<?php gbdbui_layout_end(); ?>
