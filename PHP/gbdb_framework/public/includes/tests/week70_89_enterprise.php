<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/autoloader.php';

/**
 * Regression-Test für Woche 70-89.
 * Ausführen: php gbdb_framework/public/includes/tests/week70_89_enterprise.php
 */

$old = GBDB::getInstance();
$instance = 'week70_89_test_' . date('YmdHis');
GBDB::createInstance($instance);
GBDB::setInstance($instance);

$report = ['ok' => true, 'tests' => []];
$check = function (string $name, bool $ok, mixed $data = null) use (&$report): void {
    $report['tests'][$name] = ['ok' => $ok, 'data' => $data];
    if (!$ok) $report['ok'] = false;
};

try {
    $intra = GBDB::installIntranetPattern('intranet_test');
    $check('intranet_pattern', ($intra['ok'] ?? false) && in_array('users', $intra['tables'] ?? [], true), $intra);

    $svc = GBDB::createServiceAccount('test-service', ['read', 'write']);
    $check('service_account', $svc['ok'] ?? false, $svc);

    $tok = GBDB::createApiToken($svc['account']['id'] ?? 'svc', ['read'], 3600);
    $valid = GBDB::validateApiToken((string)($tok['token'] ?? ''), 'read');
    $check('api_token', ($tok['ok'] ?? false) && ($valid['ok'] ?? false), ['token_id' => $tok['id'] ?? null, 'valid' => $valid]);

    $sso = GBDB::prepareOidcAdapter(['issuer' => 'https://example.test']);
    $check('oidc_adapter', ($sso['ok'] ?? false) && (($sso['config']['issuer'] ?? '') === 'https://example.test'), $sso);

    $quota = GBDB::quotaCheck('intranet_test', 'users');
    $check('quota_check', $quota['ok'] ?? false, $quota);

    $rate = GBDB::rateLimit('test', '127.0.0.1', 2, 60);
    $check('rate_limit', $rate['ok'] ?? false, $rate);

    $export = GBDB::exportRows('intranet_test', 'users', 'json');
    $check('json_export', ($export['ok'] ?? false) && is_file((string)($export['path'] ?? '')), $export);

    $adapter = GBDB::prepareSQLiteImportAdapter();
    $check('sqlite_adapter', $adapter['ok'] ?? false, $adapter);

    $docs = GBDB::documentationIndex();
    $check('documentation_index', ($docs['ok'] ?? false) && count($docs['docs'] ?? []) >= 8, $docs);

    $self = GBDB::enterpriseSelfTest();
    $check('enterprise_selftest', $self['ok'] ?? false, $self);
} catch (Throwable $e) {
    $report['ok'] = false;
    $report['error'] = $e->getMessage();
} finally {
    GBDB::setInstance($old);
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($report['ok'] ? 0 : 1);
