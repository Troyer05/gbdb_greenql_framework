<?php

declare(strict_types=1);

/**
 * Testhelfer: führt eine benannte Aktion im Kontext genau einer
 * Testinstallation aus und gibt das Ergebnis als JSON aus.
 *
 *   php gdp_node.php <installation> <aktion> '<json-parameter>'
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

[, $installation, $action] = $argv + [null, '', ''];
$input = json_decode($argv[3] ?? '{}', true) ?: [];
require $installation . '/gbdb_framework/autoloader.php';

/**
 * @return array{ok:bool, result?:mixed, code?:string, message?:string, class?:string}
 */
function gdpTestRun(callable $callback) :array {
    try {
        return ['ok' => true, 'result' => $callback()];
    } catch (GDPException $exception) {
        return ['ok' => false, 'code' => $exception->errorCode()->value, 'message' => $exception->getMessage(), 'class' => get_class($exception), 'context' => $exception->context()];
    } catch (Throwable $exception) {
        return ['ok' => false, 'code' => 'PHP', 'message' => get_class($exception) . ': ' . $exception->getMessage() . ' @ ' . basename($exception->getFile()) . ':' . $exception->getLine(), 'class' => get_class($exception)];
    }
}

$instance = (string) ($input['instance'] ?? 'tickets');
$database = (string) ($input['database'] ?? 'tickets');
$table = (string) ($input['table'] ?? 'tickets');

$result = gdpTestRun(static function () use ($action, $input, $instance, $database, $table) :mixed {
    return match ($action) {
        'setup_hub' => (static function () use ($instance) :array {
            GBDB::createInstance($instance);
            GBDB::withInstance($instance, static function () :void {
                GBDB::createTable('tickets', 'tickets', ['title', 'status']);
                GBDB::createTable('tickets', 'private_notes', ['text', 'owner']);
                GBDB::createTable('tickets', 'site_notes', ['text', 'site']);
                GBDB::createTable('tickets', 'history', ['event']);
                GBDB::createTable('tickets', 'prefs', ['key', 'value']);
                GBDB::insertData('tickets', 'tickets', ['title' => 'Bestand vor GDP', 'status' => 'open']);
            });
            GDP::init();
            GDP::enableInstanceReplication($instance);

            return GDP::getStatus();
        })(),
        'policy' => GDP::setPolicy($instance, $database, $table, $input['options']),
        'pairing' => GDP::createPairing([$instance], (string) ($input['role'] ?? 'replica'), $input['permissions'] ?? GDPGrants::PERMISSIONS, (bool) ($input['approval'] ?? false)),
        'pair' => GDP::pairWithHub((string) $input['url'], (string) $input['code'], 'Testreplikat'),
        'sync' => GDP::sync($input['only'] ?? null, 30),
        'bootstrap' => GDP::bootstrapReplica($instance, (bool) ($input['replace'] ?? false)),
        'node_id' => GDP::init()['node_id'],
        'status' => GDP::getStatus(),
        'insert' => GBDB::withInstance($instance, static fn() :int => GBDB::insertData($database, $table, $input['data'])),
        'edit' => GBDB::withInstance($instance, static fn() :bool => GBDB::editData($database, $table, $input['where'], $input['is'], $input['data'])),
        'delete' => GBDB::withInstance($instance, static fn() :bool => GBDB::deleteData($database, $table, $input['where'], $input['is'])),
        'rows' => GBDB::withInstance($instance, static fn() :array => array_map(static fn(array $row) :array => GDPWriteInterceptor::payload($row) + ['id' => $row['id'], 'v' => $row['_gbdb_version'] ?? null], (array) GBDB::getData($database, $table))),
        'transaction' => GBDB::withInstance($instance, static function () use ($database, $table, $input) :bool {
            GBDB::begin();

            foreach ($input['rows'] as $row) {
                GBDB::insertData($database, $table, $row);
            }

            return GBDB::commit();
        }),
        'create_table' => GBDB::withInstance($instance, static fn() :bool => GBDB::createTable($database, $table, $input['columns'])),
        'journal' => GDP::journal($instance, (string) ($input['log'] ?? 'outbox'), (int) ($input['from'] ?? 1), 200),
        'verify' => GDP::verifyJournal($instance),
        'force_local' => GDP::forceLocalMode(),
        'auto' => GDP::returnToAutoMode(),
        'set_offline' => GDPStore::transaction(static fn() => GDPStateManager::update(['connection_state' => 'offline'])),
        'can_write' => GDP::explainWrite($instance, $database, $table),
        'revoke' => GDP::unregisterNode((string) $input['node']),
        'rotate' => GDP::rotateCredential(),
        'resolve' => GDP::resolveBlock($instance, (string) $input['how']),
        'unblock_peer' => GDP::unblockPeerStream($instance, (string) $input['node']),
        'transfer' => GDP::transferOwnership($instance, $database, $table, (string) $input['to']),
        'housekeeping' => GDPSyncEngine::housekeeping(),
        'schema_change' => GDP::schemaMaintenance($instance, static fn() :bool => GBDB::addColumn($database, $table, (string) $input['column'], '')),
        'external_edit' => GDPWriteInterceptor::applying(static fn() :bool => GBDB::withInstance($instance, static fn() :bool => GBDB::editData($database, $table, $input['where'], $input['is'], $input['data']))),
        'keys' => GBDB::withInstance($instance, static fn() :array => GBDB::getKeys($database, $table)),
        'diagnostics' => GDP::getDiagnostics(true),
        'simulate_crash_before_journal' => GBDB::withInstance($instance, static function () use ($database, $table, $input) :array {
            // Intent wie im echten Ablauf, dann Fachänderung, dann „Absturz“ vor dem Journal.
            return GDPStore::transaction(static function () use ($database, $table, $input) :array {
                $id = GBDBEngine::nextID($database, $table);
                $instanceId = GBDB::getInstance();
                $row = GBDBEngine::getData($database, $table, true, 'id', $id);
                GDPStore::writeJson(GDPStore::path('instances', $instanceId, 'intent.json'), [
                    'records' => [['record_key' => 0, 'database' => $database, 'table' => $table, 'before' => [(string) $id => is_array($row) && $row !== [] ? 1 : null], 'settled' => false]],
                    'entries' => [],
                    'group' => GDPCanonical::uuid7(),
                ], true);
                GDPWriteInterceptor::applying(static fn() :int => GBDBEngine::insertData($database, $table, $input['data'] + ['id' => $id]));

                return ['id' => $id, 'intent' => GDPWriteInterceptor::hasPendingIntent($instanceId)];
            });
        }),
        'tamper_journal' => GDPStore::transaction(static function () use ($instance) :bool {
            $file = GDPStore::path('instances', $instance, 'outbox', sprintf('%010d.log', 1));
            $lines = GDPStore::readLines($file);
            $entry = GDPCanonical::decode(GDPStore::unseal($lines[0]));
            $entry['payload']['text'] = 'manipuliert';
            $lines[0] = GDPStore::seal(GDPCanonical::encode($entry));
            file_put_contents($file, implode("\n", $lines) . "\n");

            return true;
        }),
        'raw_request' => (static function () use ($input) :array {
            $content = isset($input['body_size']) ? str_repeat('x', (int) $input['body_size']) : (string) $input['body'];
            $context = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $input['headers'] ?? []), 'content' => $content, 'ignore_errors' => true]]);
            $body = file_get_contents((string) $input['url'], false, $context);
            preg_match('~\s(\d{3})\s~', (string) ($http_response_header[0] ?? ''), $matches);

            return ['status' => (int) ($matches[1] ?? 0), 'body' => json_decode((string) $body, true)];
        })(),
        'signed_request' => (static function () use ($input) :array {
            $credential = GDPCredentials::hubKey();
            $body = GDPCanonical::encode(['protocol' => GDPConfig::PROTOCOL] + $input['body']);
            $headers = GDPAuthenticator::requestHeaders('POST', GDPHttpTransport::canonicalPath((string) $input['endpoint']), $body, GDPNodeIdentity::id(), $credential['key_id'], $credential['secret']);
            $lines = ['Content-Type: application/json'];

            foreach ($headers as $name => $value) {
                $lines[] = $name . ': ' . $value;
            }

            $send = static function () use ($input, $lines, $body) :array {
                $context = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $lines), 'content' => $body, 'ignore_errors' => true]]);
                $raw = file_get_contents((string) $input['url'] . '?r=v1/' . $input['endpoint'], false, $context);
                preg_match('~\s(\d{3})\s~', (string) ($http_response_header[0] ?? ''), $matches);

                return ['status' => (int) ($matches[1] ?? 0), 'body' => json_decode((string) $raw, true)];
            };
            $first = $send();

            return ['first' => $first, 'replay' => ($input['replay'] ?? false) ? $send() : null];
        })(),
        default => throw new InvalidArgumentException('Unbekannte Testaktion: ' . $action),
    };
});

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
