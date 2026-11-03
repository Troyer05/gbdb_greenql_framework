<?php

declare(strict_types=1);

/**
 * GDP/1 Integrationstests (Konzept Kapitel 31) mit zwei getrennten
 * Installationen (Hub und Replica), je eigenem PHP-Webserver und echten,
 * signierten HTTP-Anfragen. Alle Daten liegen in einem temporären Ordner.
 *
 *   php tests/gdp_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$package = dirname(__DIR__);
$root = sys_get_temp_dir() . '/gdp-test-' . bin2hex(random_bytes(6));
$php = getenv('GBDB_TEST_PHP') ?: PHP_BINARY;
$checks = 0;
$servers = [];
$started = microtime(true);

function check(bool $condition, string $message, mixed $detail = null) :void {
    global $checks;
    $checks++;

    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message . ($detail === null ? '' : ' → ' . json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    echo 'PASS: ', $message, "\n";
}

function copyPackage(string $source, string $target) :void {
    mkdir($target, 0700, true);

    foreach (new DirectoryIterator($source) as $entry) {
        $name = $entry->getFilename();

        if ($entry->isDot() || $entry->isLink() || in_array($name, ['.runtime', 'tests', '.storage', '.system', '.gdp', '.backups', '.temp', '.media'], true)) {
            continue;
        }

        $entry->isDir() ? copyPackage($entry->getPathname(), $target . '/' . $name) : copy($entry->getPathname(), $target . '/' . $name);
    }
}

function removeTree(string $directory) :void {
    if (!is_dir($directory)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
        $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($directory);
}

function environment(string $role, string $name, string $hubUrl, array $extra = []) :array {
    return array_replace(getenv(), [
        'GBDB_GDP_ENABLED' => '1',
        'GBDB_GDP_ROLE' => $role,
        'GBDB_GDP_NODE_NAME' => $name,
        'GBDB_GDP_HUB_URL' => $hubUrl,
        'GBDB_GDP_ALLOW_INSECURE_HTTP' => '1',
        'GBDB_GDP_SYNC_MODE' => 'manual',
        'GBDB_GDP_SYNC_TIMEOUT' => '3',
        'GBDB_ENV' => 'prod',
    ], $extra);
}

function startServer(string $directory, int $port, array $environment) :mixed {
    global $php;
    $log = $directory . '/server.log';
    $process = proc_open([$php, '-S', '127.0.0.1:' . $port, '-t', $directory], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $directory, $environment);

    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);

        if ($socket !== false) {
            fclose($socket);

            return $process;
        }

        usleep(100000);
    }

    throw new RuntimeException('Testserver startet nicht auf Port ' . $port);
}

function stopServer(mixed $process) :void {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

function node(string $installation, array $environment, string $action, array $input = []) :array {
    global $php;
    $process = proc_open([$php, __DIR__ . '/gdp_node.php', $installation, $action, json_encode($input, JSON_UNESCAPED_UNICODE)], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $installation, $environment);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    proc_close($process);
    $lines = array_values(array_filter(explode("\n", trim((string) $output))));
    $result = json_decode((string) end($lines), true);

    if (!is_array($result)) {
        throw new RuntimeException('Testaktion ' . $action . ' lieferte kein JSON: ' . $output . $errors);
    }

    if (trim((string) $errors) !== '' && !str_contains((string) $errors, 'GDP')) {
        throw new RuntimeException('PHP-Ausgabe auf stderr bei ' . $action . ': ' . $errors);
    }

    return $result;
}

$hubPort = random_int(20000, 29999);
$replicaPort = $hubPort + 1;
$hubDir = $root . '/hub';
$replicaDir = $root . '/replica';
$hubUrl = 'http://127.0.0.1:' . $hubPort . '/gdp.php';
$hubEnv = environment('hub', 'Testhub', '');
$replicaEnv = environment('replica', 'Testreplica', $hubUrl);

try {
    copyPackage($package, $hubDir);
    copyPackage($package, $replicaDir);
    $hub = static fn(string $action, array $input = []) :array => node($hubDir, $hubEnv, $action, $input);
    $replica = static fn(string $action, array $input = []) :array => node($replicaDir, $replicaEnv, $action, $input);
    $servers['hub'] = startServer($hubDir, $hubPort, $hubEnv);

    // 1: Ohne Replikation bleibt GBDB unverändert.
    $plain = $hub('insert', ['instance' => 'plain', 'table' => 'missing', 'data' => ['x' => 1]]);
    check($plain['ok'] && $plain['result'] === -1, 'Nicht replizierte Instanz verhält sich wie bisher (kein GDP-Eingriff)', $plain);

    $setup = $hub('setup_hub');
    check($setup['ok'] && $setup['result']['role'] === 'hub', 'Hub initialisiert und Instanz zur Replikation freigegeben', $setup);
    $replicaNode = $replica('node_id')['result'];
    $hubNode = $setup['result']['node_id'];
    check(is_string($replicaNode) && str_starts_with($replicaNode, 'node-'), 'Replica erzeugt dauerhafte node_id');

    foreach ([
        ['private_notes', ['mode' => 'node_owner', 'owner_node_id' => $replicaNode, 'offline_permission' => false]],
        ['history', ['mode' => 'append_only', 'offline_create' => true]],
        ['prefs', ['mode' => 'local_only']],
        ['site_notes', ['mode' => 'node_owner', 'owner_column' => 'site', 'lease_seconds' => 3]],
    ] as [$table, $options]) {
        $policy = $hub('policy', ['table' => $table, 'options' => $options]);
        check($policy['ok'], 'Policy gesetzt: ' . $table . ' = ' . $options['mode'], $policy);
    }

    // 14: Ungültiger Pairing-Code.
    $bad = $replica('pair', ['url' => $hubUrl, 'code' => 'GDP1-p000000000000-' . str_repeat('A', 32)]);
    check(!$bad['ok'] && $bad['code'] === 'PAIRING_EXPIRED', 'Ungültiger Pairing-Code wird abgelehnt', $bad);

    $pairing = $hub('pairing');
    $code = $pairing['result']['code'];
    $paired = $replica('pair', ['url' => $hubUrl, 'code' => $code]);
    check($paired['ok'] && $paired['result']['status'] === 'active' && $paired['result']['instances'] === ['tickets'], 'Pairing mit Einmalcode und verschlüsselter Schlüsselübergabe', $paired);
    $again = node($root . '/hub', $replicaEnv, 'pair', ['url' => $hubUrl, 'code' => $code]);
    check(!$again['ok'], 'Ein verbrauchter Pairing-Code ist nicht erneut nutzbar', $again);

    // Bootstrap über Snapshot.
    $sync = $replica('sync');
    check($sync['ok'] && ($sync['result']['instances']['tickets']['bootstrap']['snapshot_id'] ?? '') !== '', 'Erstverbindung: Snapshot geladen, geprüft und aktiviert', $sync);
    $rows = $replica('rows');
    check($rows['ok'] && count($rows['result']) === 1 && $rows['result'][0]['title'] === 'Bestand vor GDP', 'Bestandsdaten des Hubs sind auf dem Replikat');

    // 8: Zentral verwaltete Daten sind auf dem Replikat schreibgeschützt.
    $denied = $replica('insert', ['data' => ['title' => 'lokal', 'status' => 'x']]);
    check(!$denied['ok'] && $denied['code'] === 'POLICY_VIOLATION', 'CENTRAL_OWNER: Schreiben auf dem Replikat verweigert', $denied);

    // 4/9: NODE_OWNER schreibt lokal und journalisiert automatisch.
    $note = $replica('insert', ['table' => 'private_notes', 'data' => ['text' => 'Notiz 1', 'owner' => 'a']]);
    check($note['ok'] && $note['result'] > 0, 'NODE_OWNER: Eigentümer schreibt über den normalen GBDB-Aufruf', $note);
    $journal = $replica('journal');
    check(count($journal['result']['entries']) === 1 && $journal['result']['entries'][0]['operation'] === 'INSERT' && $journal['result']['entries'][0]['source_sequence'] === 1, 'Journalereignis mit Quellsequenz 1 ohne zusätzlichen Anwendungsaufruf');
    $hubDenied = $hub('insert', ['table' => 'private_notes', 'data' => ['text' => 'vom Hub']]);
    check(!$hubDenied['ok'] && $hubDenied['code'] === 'POLICY_VIOLATION', 'Hub darf fremde NODE_OWNER-Daten nicht überschreiben', $hubDenied);

    $local = $replica('insert', ['table' => 'prefs', 'data' => ['key' => 'theme', 'value' => 'dark']]);
    check($local['ok'] && count($replica('journal')['result']['entries']) === 1, 'LOCAL_ONLY wird nicht journalisiert');
    $append = $replica('insert', ['table' => 'history', 'data' => ['event' => 'erstellt']]);
    check($append['ok'], 'APPEND_ONLY: neuer Eintrag erlaubt', $append);
    $appendEdit = $replica('edit', ['table' => 'history', 'where' => 'id', 'is' => $append['result'], 'data' => ['event' => 'x']]);
    check(!$appendEdit['ok'] && $appendEdit['code'] === 'POLICY_VIOLATION', 'APPEND_ONLY: Änderungen sind verboten', $appendEdit);
    $structure = $replica('create_table', ['table' => 'neu', 'columns' => ['a']]);
    check(!$structure['ok'] && $structure['code'] === 'POLICY_VIOLATION', 'Strukturänderung an replizierter Datenbank ohne Wartungsmodus blockiert', $structure);

    $hubTicket = $hub('insert', ['data' => ['title' => 'Vom Hub', 'status' => 'open']]);
    check($hubTicket['ok'] && $hubTicket['result'] > 0, 'Hub schreibt zentral verwaltete Daten');
    $sync = $replica('sync');
    check($sync['ok'] && ($sync['result']['instances']['tickets']['pushed'] ?? 0) === 2, 'Push: zwei Operationen dauerhaft angenommen', $sync);
    check(count($hub('rows', ['table' => 'private_notes'])['result']) === 1 && count($hub('rows', ['table' => 'history'])['result']) === 1, 'Hub hat Notiz und Verlaufseintrag übernommen');
    check(count($hub('rows', ['table' => 'prefs'])['result']) === 0, 'LOCAL_ONLY-Daten bleiben lokal');
    $replicaTickets = $replica('rows')['result'];
    check(count($replicaTickets) === 2 && $replicaTickets[1]['title'] === 'Vom Hub', 'Pull: Hub-Änderung auf dem Replikat angewendet');
    check(count($replica('rows', ['table' => 'private_notes'])['result']) === 1, 'Eigene Operation kehrt vom Hub zurück, ohne doppelt angewendet zu werden');
    $status = $replica('status')['result']['instances']['tickets'];
    check($status['sync_state'] === 'idle' && $status['pending_operations'] === 0, 'Zustand IDLE nach vollständigem Abgleich', $status);

    // UPDATE/DELETE mit Versionen und Tombstone.
    $edit = $replica('edit', ['table' => 'private_notes', 'where' => 'id', 'is' => $note['result'], 'data' => ['text' => 'Notiz 1b']]);
    $delete = $replica('insert', ['table' => 'private_notes', 'data' => ['text' => 'wird gelöscht']]);
    $removed = $replica('delete', ['table' => 'private_notes', 'where' => 'id', 'is' => $delete['result']]);
    check($edit['ok'] && $removed['ok'], 'Replikat ändert und löscht eigene Notizen');
    $replica('sync');
    $hubNotes = $hub('rows', ['table' => 'private_notes'])['result'];
    check(count($hubNotes) === 1 && $hubNotes[0]['text'] === 'Notiz 1b' && $hubNotes[0]['v'] === 2, 'UPDATE mit Vorversion und DELETE-Tombstone auf dem Hub angewendet', $hubNotes);

    // Transaktionsgruppe.
    $tx = $replica('transaction', ['table' => 'private_notes', 'rows' => [['text' => 'G1'], ['text' => 'G2']]]);
    $groupEntries = array_slice($replica('journal')['result']['entries'], -2);
    check($tx['ok'] && $tx['result'] === true && $groupEntries[0]['transaction_group_id'] !== null && $groupEntries[0]['transaction_group_id'] === $groupEntries[1]['transaction_group_id'], 'GBDB-Transaktion erzeugt eine gemeinsame transaction_group_id', $groupEntries);
    $replica('sync');
    check(count($hub('rows', ['table' => 'private_notes'])['result']) === 3, 'Transaktionsgruppe vollständig übertragen');

    // 03/04/05/14: Duplikate, Hash-Manipulation, Lücken und Replay über signierte Rohanfragen.
    $fullOutbox = node($replicaDir, $replicaEnv, 'journal')['result']['entries'];
    $journalRaw = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'handshake', 'body' => ['instances' => [['instance_id' => 'tickets']]], 'replay' => true]);
    check($journalRaw['ok'] && $journalRaw['result']['first']['status'] === 200, 'Signierte Anfrage wird akzeptiert', $journalRaw);
    check($journalRaw['result']['replay']['status'] === 401, 'Replay derselben signierten Anfrage wird abgewiesen', $journalRaw['result']['replay']);
    check(count($fullOutbox) >= 5, 'Ausgangsjournal enthält alle lokalen Ereignisse');

    $pull = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'sync/pull', 'body' => ['instance_id' => 'tickets', 'from_hub_sequence' => 1]]);
    $hubEvents = $pull['result']['first']['body']['events'];
    $own = array_values(array_filter($hubEvents, static fn(array $event) :bool => $event['origin_node_id'] === $replicaNode));
    $duplicate = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'sync/push', 'body' => ['instance_id' => 'tickets', 'operations' => [array_diff_key($own[0], ['hub_sequence' => 1, 'accepted_at' => 1])]]]);
    check($duplicate['result']['first']['status'] === 200 && count($duplicate['result']['first']['body']['duplicate_operations']) === 1, 'Erneut gesendete Operation: ACK ohne zweite Ausführung', $duplicate['result']['first']);
    $tampered = $own[0];
    $tampered['payload']['text'] = 'manipuliert';
    $mismatch = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'sync/push', 'body' => ['instance_id' => 'tickets', 'operations' => [$tampered]]]);
    check($mismatch['result']['first']['status'] === 409 && $mismatch['result']['first']['body']['error']['code'] === 'DUPLICATE_PAYLOAD_MISMATCH', 'Gleiche operation_id mit anderem Payload: Integritätsfehler', $mismatch['result']['first']);
    $gapEntry = $own[count($own) - 1];
    $gapEntry['source_sequence'] += 5;
    $gap = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'sync/push', 'body' => ['instance_id' => 'tickets', 'operations' => [$gapEntry]]]);
    check($gap['result']['first']['status'] === 409 || ($gap['result']['first']['body']['gap']['code'] ?? '') === 'SEQUENCE_GAP', 'Fehlende Quellsequenz wird erkannt, der Cursor springt nicht', $gap['result']['first']);
    $handshake = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'handshake', 'body' => ['instances' => [['instance_id' => 'tickets']]]]);
    check($handshake['result']['first']['body']['instances'][0]['accepted_source_sequence'] === count($fullOutbox), 'Hub-Checkpoint unverändert lückenlos', $handshake['result']['first']['body']['instances'][0]);

    // 22: Manipulierte, unsignierte und zu große Nachrichten.
    $unsigned = $hub('raw_request', ['url' => $hubUrl . '?r=v1/sync/pull', 'body' => '{"protocol":"GDP/1"}', 'headers' => ['Content-Type: application/json', 'X-GDP-Protocol: GDP/1']]);
    check($unsigned['result']['status'] === 401, 'Unsignierte Anfrage wird abgewiesen', $unsigned);
    $malformed = $hub('raw_request', ['url' => $hubUrl . '?r=v1/handshake', 'body' => '{kaputt', 'headers' => ['Content-Type: application/json']]);
    check($malformed['result']['status'] === 400, 'Defektes JSON führt zu einem frühen Validierungsfehler', $malformed);
    $large = $hub('raw_request', ['url' => $hubUrl . '?r=v1/handshake', 'body_size' => 9 * 1024 * 1024, 'headers' => ['Content-Type: application/json']]);
    check($large['result']['status'] === 413, 'Zu große Nachricht wird ohne Verarbeitung abgewiesen', $large['result']['status']);
    $unknown = $hub('raw_request', ['url' => $hubUrl . '?r=v1/../../etc/passwd', 'body' => '{}', 'headers' => ['Content-Type: application/json']]);
    check($unknown['result']['status'] === 400, 'Unbekannte Endpunkte werden abgewiesen', $unknown['result']['status']);

    // 06: Absturz zwischen Fachänderung und Journal.
    $crash = $replica('simulate_crash_before_journal', ['table' => 'private_notes', 'data' => ['text' => 'nach Absturz']]);
    check($crash['ok'] && $crash['result']['intent'] === true, 'Simulierter PHP-Abbruch: Fachänderung ohne Journal, Intent bleibt zurück', $crash);
    $replica('sync');
    $afterCrash = $hub('rows', ['table' => 'private_notes'])['result'];
    check(in_array('nach Absturz', array_column($afterCrash, 'text'), true), 'Recovery journalisiert die Änderung aus dem Intent und überträgt sie', $afterCrash);
    check($replica('status')['result']['instances']['tickets']['intent_pending'] === false, 'Kein offener Intent nach der Recovery');

    // 01/07/08: Hub-Ausfall.
    stopServer($servers['hub']);
    $offlineSync = $replica('sync');
    check($offlineSync['ok'] && ($offlineSync['result']['errors'][0]['code'] ?? '') === 'CONNECTION_UNAVAILABLE', 'Hub nicht erreichbar: Sync meldet CONNECTION_UNAVAILABLE', $offlineSync);
    check($replica('status')['result']['connection_state'] === 'offline', 'Verbindungszustand OFFLINE');
    $offlineWrite = $replica('insert', ['table' => 'private_notes', 'data' => ['text' => 'offline']]);
    check(!$offlineWrite['ok'] && $offlineWrite['code'] === 'POLICY_VIOLATION', 'Ohne Offline-Freigabe ist NODE_OWNER offline gesperrt', $offlineWrite);
    $offlineAppend = $replica('insert', ['table' => 'history', 'data' => ['event' => 'offline erstellt']]);
    check($offlineAppend['ok'], 'APPEND_ONLY mit offline_create bleibt offline nutzbar', $offlineAppend);
    $stillLocal = $replica('insert', ['table' => 'prefs', 'data' => ['key' => 'k', 'value' => 'v']]);
    check($stillLocal['ok'], 'LOCAL_ONLY offline möglich');
    check($replica('status')['result']['instances']['tickets']['sync_state'] === 'pending', 'Sync-Zustand PENDING mit lokaler Queue');
    $servers['hub'] = startServer($hubDir, $hubPort, $hubEnv);
    $back = $replica('sync');
    check($back['ok'] && ($back['result']['instances']['tickets']['pushed'] ?? 0) === 1 && $replica('status')['result']['connection_state'] === 'online', 'Hub-Rückkehr: Offline-Operation nachgereicht, wieder ONLINE', $back);
    check(in_array('offline erstellt', array_column($hub('rows', ['table' => 'history'])['result'], 'event'), true), 'Offline erzeugter Eintrag ist auf dem Hub');

    // 15: Lease mit Datensatz-Eigentümer.
    $replica('sync');
    $site = $replica('insert', ['table' => 'site_notes', 'data' => ['text' => 'mit Lease']]);
    check($site['ok'], 'Gültige Lease erlaubt das Schreiben eigener Datensätze', $site);
    $siteRows = $replica('rows', ['table' => 'site_notes'])['result'];
    check($siteRows[0]['site'] === $replicaNode, 'owner_column wird automatisch mit der node_id belegt', $siteRows);
    sleep(4);
    $expired = $replica('insert', ['table' => 'site_notes', 'data' => ['text' => 'zu spät']]);
    check(!$expired['ok'] && $expired['code'] === 'LEASE_EXPIRED', 'Abgelaufene Lease verweigert das Schreiben', $expired);
    $replica('sync');

    // 17/13: Direkte Veränderung außerhalb von GDP → Versionskonflikt → Blockade statt Überschreiben.
    $notes = $hub('rows', ['table' => 'private_notes'])['result'];
    $target = $notes[0];
    $external = $hub('external_edit', ['table' => 'private_notes', 'where' => 'id', 'is' => $target['id'], 'data' => ['text' => 'extern verändert']]);
    check($external['ok'] && $external['result'] === true, 'Simulierte direkte Datenänderung auf dem Hub (an GDP vorbei)', $external);
    $replica('edit', ['table' => 'private_notes', 'where' => 'id', 'is' => $target['id'], 'data' => ['text' => 'lokal geändert']]);
    $conflict = $replica('sync');
    check(($conflict['result']['errors'][0]['code'] ?? '') === 'VERSION_CONFLICT', 'Hub erkennt den Versionskonflikt (kein Lost Update)', $conflict);
    check($replica('status')['result']['instances']['tickets']['sync_state'] === 'blocked', 'Replikat-Instanz ist BLOCKED mit dokumentierter Ursache');
    check(in_array('extern verändert', array_column($hub('rows', ['table' => 'private_notes'])['result'], 'text'), true), 'Hub-Daten wurden nicht stillschweigend überschrieben');

    // Administrative Recovery: Strom freigeben und Replikat neu initialisieren.
    $hub('unblock_peer', ['node' => $replicaNode]);
    $confirm = $replica('bootstrap');
    check(($confirm['result']['instances']['tickets']['bootstrap']['status'] ?? '') === 'confirmation_required', 'Re-Snapshot verlangt Bestätigung, weil lokale Daten vorhanden sind', $confirm);
    $replaced = $replica('bootstrap', ['replace' => true]);
    $bootstrap = $replaced['result']['instances']['tickets']['bootstrap'] ?? [];
    check(($bootstrap['snapshot_id'] ?? '') !== '' && ($bootstrap['backup'] ?? '') !== '', 'Re-Snapshot mit automatischer Vollsicherung vor dem Ersetzen', $replaced);
    check($replica('rows', ['table' => 'private_notes'])['result'] == $hub('rows', ['table' => 'private_notes'])['result'], 'Nach der Recovery stimmen Replikat und Hub überein');
    check($replica('status')['result']['instances']['tickets']['sync_state'] === 'idle', 'Instanz nach Recovery wieder IDLE');
    $resumed = $replica('insert', ['table' => 'private_notes', 'data' => ['text' => 'nach Recovery']]);
    $replica('sync');
    check($resumed['ok'] && in_array('nach Recovery', array_column($hub('rows', ['table' => 'private_notes'])['result'], 'text'), true), 'Replikation läuft nach der Recovery weiter');

    // 12: Schema-Änderung über den Wartungsmodus → SCHEMA_MISMATCH → Re-Snapshot.
    $maintenance = $hub('create_table', ['table' => 'tickets_extra', 'columns' => ['a']]);
    check(!$maintenance['ok'], 'Hub: Strukturänderung ohne Wartungsmodus blockiert', $maintenance);
    $schema = $hub('schema_change', ['column' => 'prio']);
    check($schema['ok'], 'Hub: Spalte über den GDP-Wartungsmodus ergänzt, Schema-Version erhöht', $schema);
    $mismatch = $replica('sync');
    check(($mismatch['result']['errors'][0]['code'] ?? '') === 'SCHEMA_MISMATCH' && $replica('status')['result']['instances']['tickets']['sync_state'] === 'blocked', 'Replikat blockiert bei abweichendem Schema statt Daten zu beschädigen', $mismatch);
    $replica('bootstrap', ['replace' => true]);
    check(in_array('prio', $replica('keys')['result'], true) && $replica('status')['result']['instances']['tickets']['schema_version'] === 2, 'Re-Snapshot übernimmt das neue Schema', $replica('keys'));

    // 21: Mandantentrennung.
    $foreign = node($replicaDir, $replicaEnv, 'signed_request', ['url' => $hubUrl, 'endpoint' => 'sync/pull', 'body' => ['instance_id' => 'plain', 'from_hub_sequence' => 1]]);
    check($foreign['result']['first']['status'] === 403, 'Replikat kann keine nicht freigegebene Instanz lesen', $foreign['result']['first']);

    // 24: Journal-Manipulation wird erkannt.
    $tamper = $replica('tamper_journal');
    $verify = $replica('verify');
    check($tamper['ok'] && !$verify['ok'] && $verify['code'] === 'DUPLICATE_PAYLOAD_MISMATCH', 'Integritätsprüfung erkennt manipulierte Journalzeilen', $verify);

    // 23: Schlüsselrotation.
    $rotate = $replica('rotate');
    check($rotate['ok'] && str_starts_with($rotate['result']['key_id'], 'k'), 'Schlüsselrotation abgeschlossen', $rotate);
    $afterRotate = $replica('sync');
    check($afterRotate['ok'] && ($afterRotate['result']['errors'] ?? []) === [], 'Sync mit neuem Schlüssel', $afterRotate);

    // Eigentümerwechsel mit Fencing.
    $transfer = $hub('transfer', ['table' => 'private_notes', 'to' => $hubNode]);
    check($transfer['ok'] && $transfer['result']['transfer']['from'] === $replicaNode, 'Eigentümerwechsel gestartet (neue authority_epoch)', $transfer);
    $replica('sync');
    $oldOwner = $replica('insert', ['table' => 'private_notes', 'data' => ['text' => 'alter Eigentümer']]);
    check(!$oldOwner['ok'], 'Alter Eigentümer schreibt nach Übergabe nicht mehr', $oldOwner);
    $replica('sync');
    $newOwner = $hub('insert', ['table' => 'private_notes', 'data' => ['text' => 'neuer Eigentümer']]);
    check($newOwner['ok'], 'Neuer Eigentümer schreibt nach abgeschlossener Übergabe', $newOwner);
    $replica('sync');
    check(in_array('neuer Eigentümer', array_column($replica('rows', ['table' => 'private_notes'])['result'], 'text'), true), 'Replikat erhält Änderungen des neuen Eigentümers');

    // 13/15: Widerruf.
    $revoke = $hub('revoke', ['node' => $replicaNode]);
    $revoked = $replica('sync');
    check($revoke['ok'] && $revoked['ok'] && ($revoked['result']['errors'][0]['code'] ?? '') !== '', 'Widerrufener Knoten erhält keinen Zugriff mehr', $revoked);

    // 25: Embedded-Grenze dokumentiert.
    $diagnostics = $replica('diagnostics');
    $modeText = implode(' ', array_column($diagnostics['result'], 'detail'));
    check($diagnostics['ok'], 'Diagnose läuft', $diagnostics);
    check(str_contains($modeText, 'Manuell') || str_contains($modeText, 'Embedded'), 'Diagnose beschreibt die Grenzen des Sync-Modus ohne falsche Echtzeit-Zusage');

    echo 'OK: ', $checks, ' Prüfungen in ', round(microtime(true) - $started, 1), " s\n";
    $exit = 0;
} catch (Throwable $exception) {
    echo $exception->getMessage(), "\n";

    foreach (['hub' => $hubDir, 'replica' => $replicaDir] as $name => $directory) {
        if (is_file($directory . '/server.log')) {
            echo "--- {$name} server.log ---\n", implode("\n", array_slice(file($directory . '/server.log', FILE_IGNORE_NEW_LINES) ?: [], -15)), "\n";
        }
    }

    $exit = 1;
} finally {
    foreach ($servers as $server) {
        stopServer($server);
    }

    if (getenv('GDP_TEST_KEEP') !== '1') {
        removeTree($root);
    } else {
        echo 'Testdaten: ', $root, "\n";
    }
}

exit($exit);
