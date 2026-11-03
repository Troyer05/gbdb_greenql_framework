<?php

declare(strict_types=1);

/**
 * Unit-Tests des Framework-Updaters: Vertrauenskette, Signaturen, Rollback-Schutz, ZIP-Schutz,
 * Index-Listing, Konfigurationsabgleich und Patch-Day-Planung. Ohne Netzwerk und ohne Server.
 *
 *   php tests/updater_test.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$updater = dirname(__DIR__) . '/gbdb_framework/updater';

foreach (['signature', 'zip', 'zip_writer', 'trust', 'config_merger', 'updater'] as $file) {
    require_once $updater . '/' . $file . '.php';
}

$checks = 0;
$work = sys_get_temp_dir() . '/gbdb-updater-test-' . bin2hex(random_bytes(5));
mkdir($work, 0700, true);

function check(bool $condition, string $message, mixed $detail = null) :void {
    global $checks;
    $checks++;

    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message . ($detail === null ? '' : ' → ' . json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    echo 'PASS: ', $message, "\n";
}

/** Erwartet einen Prüffehler mit bestimmtem Ergebniszustand. */
function expectStatus(string $status, callable $work, string $message) :void {
    try {
        $work();
    } catch (GBDBUpdateTrustException $exception) {
        check($exception->status === $status, $message, $exception->status . ': ' . $exception->getMessage());

        return;
    }

    check(false, $message, 'kein Fehler');
}

function expectInvalid(callable $work, string $message) :void {
    try {
        $work();
    } catch (InvalidArgumentException $exception) {
        check(true, $message);

        return;
    }

    check(false, $message, 'kein Fehler');
}

function entry(array $pair) :array {
    return ['key_id' => $pair['key_id'], 'public_key' => $pair['public_key']];
}

function signer(array $pair) :array {
    return ['key_id' => $pair['key_id'], 'secret_key' => $pair['secret_key']];
}

function trustDoc(int $version, array $root, array $recovery, array $releaseKeys, array $history, int $issued, int $expires) :string {
    return json_encode([
        'type' => 'gbdb-trust', 'spec' => 1, 'version' => $version, 'algorithm' => 'Ed25519',
        'issued_at' => GBDBUpdateTrust::formatTime($issued), 'expires' => GBDBUpdateTrust::formatTime($expires),
        'root' => ['threshold' => 1, 'keys' => array_map('entry', $root)],
        'recovery' => ['threshold' => 1, 'keys' => array_map('entry', $recovery)],
        'root_history' => array_map(static fn(int $v) :array => ['version' => $v], $history),
        'thresholds' => ['release' => 1, 'catalog' => 1],
        'release_keys' => $releaseKeys,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function releaseKey(array $pair, int $from, int $until, bool $revoked = false) :array {
    return ['key_id' => $pair['key_id'], 'public_key' => $pair['public_key'], 'valid_from' => GBDBUpdateTrust::formatTime($from), 'valid_until' => GBDBUpdateTrust::formatTime($until),
        'revoked' => $revoked, 'revoked_at' => null, 'revocation_reason' => '', 'roles' => ['release', 'catalog']];
}

function sign(string $context, string $bytes, array ...$pairs) :string {
    return GBDBUpdateSignature::buildFile($context, $bytes, array_map('signer', $pairs));
}

$now = time();
$day = 86400;

try {
    // ------------------------------------------------------------ Schlüssel und Signaturen
    check(GBDBUpdateSignature::available(), 'sodium verfügbar');
    $root = GBDBUpdateSignature::generate('root');
    $recovery = GBDBUpdateSignature::generate('recovery');
    $release = GBDBUpdateSignature::generate('release');
    $foreign = GBDBUpdateSignature::generate('release');
    check(GBDBUpdateSignature::keyIdMatches($root['key_id'], base64_decode($root['public_key'])), 'Schlüssel-ID ist an den Schlüssel gebunden');
    check(!GBDBUpdateSignature::keyIdMatches($root['key_id'], base64_decode($release['public_key'])), 'Schlüssel-ID passt nicht zu fremdem Schlüssel');
    $sig = GBDBUpdateSignature::sign(GBDBUpdateSignature::CONTEXT_RELEASE, 'abc', $release['secret_key']);
    check(GBDBUpdateSignature::verify(GBDBUpdateSignature::CONTEXT_RELEASE, 'abc', $sig, base64_decode($release['public_key'])), 'Signatur gültig');
    check(!GBDBUpdateSignature::verify(GBDBUpdateSignature::CONTEXT_CATALOG, 'abc', $sig, base64_decode($release['public_key'])), 'Signatur gilt nicht in anderem Kontext');
    check(!GBDBUpdateSignature::verify(GBDBUpdateSignature::CONTEXT_RELEASE, 'abd', $sig, base64_decode($release['public_key'])), 'Signatur gilt nicht für veränderte Bytes');
    expectInvalid(static fn() => GBDBUpdateSignature::parseFile('{"format":"gbdb-sig/1","context":"gbdb-trust/1","signatures":[]}', GBDBUpdateSignature::CONTEXT_TRUST), 'Leere Signaturdatei abgewiesen');
    expectInvalid(static fn() => GBDBUpdateSignature::parseFile(sign(GBDBUpdateSignature::CONTEXT_CATALOG, 'x', $release), GBDBUpdateSignature::CONTEXT_TRUST), 'Signaturdatei mit falschem Kontext abgewiesen');

    // ------------------------------------------------------------ Anker und trust.json
    $anchor = json_encode(['type' => 'gbdb-trust-anchor', 'spec' => 1, 'root_threshold' => 1, 'root_keys' => [entry($root)], 'recovery_threshold' => 1, 'recovery_keys' => [entry($recovery)], 'trust_version' => 0]);
    $trusted = GBDBUpdateTrust::anchorState($anchor);
    expectStatus(GBDBUpdateTrust::NO_TRUST_ANCHOR, static fn() => GBDBUpdateTrust::anchorState('{"type":"gbdb-trust-anchor","spec":1,"root_threshold":1,"root_keys":[],"trust_version":0}'), 'Leerer Anker blockiert Updates');
    $keys = [releaseKey($release, $now - $day, $now + 300 * $day)];
    $trust1 = trustDoc(1, [$root], [$recovery], $keys, [1], $now - 60, $now + 365 * $day);
    $sig1 = sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust1, $root);
    $noHistory = static fn(int $v) :array => throw new RuntimeException('keine Historie erwartet: ' . $v);
    $result = GBDBUpdateTrust::verifyTrust($trust1, $sig1, $trusted, $now, $noHistory);
    check($result['state']['trust_version'] === 1 && !$result['rotated'], 'trust.json v1 mit Root-Signatur akzeptiert');
    expectStatus(GBDBUpdateTrust::INVALID_SIGNATURE, static fn() => GBDBUpdateTrust::verifyTrust(str_replace('"version": 1', '"version": 1 ', $trust1), $sig1, $trusted, $now, $noHistory), 'Veränderte trust.json: INVALID_SIGNATURE');
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyTrust($trust1, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust1, $foreign), $trusted, $now, $noHistory), 'trust.json von fremdem Schlüssel: UNTRUSTED_KEY');
    expectStatus(GBDBUpdateTrust::INVALID_SIGNATURE, static fn() => GBDBUpdateTrust::verifyTrust($trust1, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust1 . ' ', $root), $trusted, $now, $noHistory), 'Root-Signatur über anderen Inhalt: INVALID_SIGNATURE');
    $expired = trustDoc(1, [$root], [$recovery], $keys, [1], $now - 10 * $day, $now - $day);
    expectStatus(GBDBUpdateTrust::EXPIRED_METADATA, static fn() => GBDBUpdateTrust::verifyTrust($expired, sign(GBDBUpdateSignature::CONTEXT_TRUST, $expired, $root), $trusted, $now, $noHistory), 'Abgelaufene trust.json: EXPIRED_METADATA');
    $future = trustDoc(1, [$root], [$recovery], $keys, [1], $now + 5 * $day, $now + 365 * $day);
    expectStatus(GBDBUpdateTrust::CLOCK_INVALID, static fn() => GBDBUpdateTrust::verifyTrust($future, sign(GBDBUpdateSignature::CONTEXT_TRUST, $future, $root), $trusted, $now, $noHistory), 'Systemzeit vor Ausstellungsdatum: CLOCK_INVALID');
    $stateV1 = $result['state'];
    $trust2 = trustDoc(2, [$root], [$recovery], $keys, [1], $now - 30, $now + 365 * $day);
    $sig2 = sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust2, $root);
    $stateV2 = GBDBUpdateTrust::verifyTrust($trust2, $sig2, $stateV1, $now, $noHistory)['state'];
    expectStatus(GBDBUpdateTrust::ROLLBACK_DETECTED, static fn() => GBDBUpdateTrust::verifyTrust($trust1, $sig1, $stateV2, $now, $noHistory), 'Ältere trust.json nach v2: ROLLBACK_DETECTED');
    $trust2b = trustDoc(2, [$root], [$recovery], $keys, [1], $now - 20, $now + 300 * $day);
    expectStatus(GBDBUpdateTrust::ROLLBACK_DETECTED, static fn() => GBDBUpdateTrust::verifyTrust($trust2b, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust2b, $root), $stateV2, $now, $noHistory), 'Gleiche Version mit anderem Inhalt: ROLLBACK_DETECTED');
    $sameRoot = [entry($root)];
    $asRelease = trustDoc(3, [$root], [$recovery], [releaseKey($root, $now - $day, $now + $day)], [1], $now - 10, $now + $day);
    expectStatus(GBDBUpdateTrust::INVALID_METADATA, static fn() => GBDBUpdateTrust::verifyTrust($asRelease, sign(GBDBUpdateSignature::CONTEXT_TRUST, $asRelease, $root), $stateV2, $now, $noHistory), 'Root-Schlüssel als Release-Schlüssel abgewiesen');

    // ------------------------------------------------------------ Root-Rotation und Recovery
    $root2 = GBDBUpdateSignature::generate('root');
    $trust3 = trustDoc(3, [$root2], [$recovery], $keys, [1, 3], $now - 10, $now + 365 * $day);
    $sig3 = sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust3, $root, $root2);
    $rotated = GBDBUpdateTrust::verifyTrust($trust3, $sig3, $stateV2, $now, $noHistory);
    check($rotated['rotated'] && array_keys($rotated['state']['root_keys']) === [$root2['key_id']], 'Root-Rotation mit altem und neuem Root akzeptiert');
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyTrust($trust3, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust3, $root2), $stateV2, $now, $noHistory), 'Rotation nur mit neuem Root: UNTRUSTED_KEY');
    expectStatus(GBDBUpdateTrust::INVALID_SIGNATURE, static fn() => GBDBUpdateTrust::verifyTrust($trust3, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust3, $root), $stateV2, $now, $noHistory), 'Rotation ohne Besitznachweis des neuen Root: INVALID_SIGNATURE');
    $trust4 = trustDoc(4, [$root2], [$recovery], $keys, [1, 3], $now - 5, $now + 365 * $day);
    $sig4 = sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust4, $root2);
    $archive = [3 => [$trust3, $sig3]];
    $chained = GBDBUpdateTrust::verifyTrust($trust4, $sig4, $stateV2, $now, static fn(int $v) :array => $archive[$v]);
    check($chained['rotated'] && $chained['state']['trust_version'] === 4, 'Kette über archivierten Root-Wechsel (v2 → v3 → v4)');
    $freshAnchor = GBDBUpdateTrust::anchorState($anchor);
    $fresh = GBDBUpdateTrust::verifyTrust($trust4, $sig4, $freshAnchor, $now, static fn(int $v) :array => $v === 1 ? [$trust1, $sig1] : $archive[$v]);
    check($fresh['state']['trust_version'] === 4, 'Frische Installation folgt der gesamten Root-Historie');
    $tamperedArchive = [3 => [$trust3, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust3, $root2)]];
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyTrust($trust4, $sig4, $stateV2, $now, static fn(int $v) :array => $tamperedArchive[$v]), 'Gefälschter Root-Wechsel im Archiv abgewiesen');
    $root3 = GBDBUpdateSignature::generate('root');
    $recovery2 = GBDBUpdateSignature::generate('recovery');
    $trust5 = trustDoc(5, [$root3], [$recovery2], $keys, [1, 3, 5], $now - 2, $now + 365 * $day);
    $recovered = GBDBUpdateTrust::verifyTrust($trust5, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust5, $recovery, $root3), $chained['state'], $now, $noHistory);
    check($recovered['rotated'] && array_keys($recovered['state']['recovery_keys']) === [$recovery2['key_id']], 'Notfall-Wiederherstellung mit Recovery-Schlüssel akzeptiert');
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyTrust($trust5, sign(GBDBUpdateSignature::CONTEXT_TRUST, $trust5, $root2, $root3), $chained['state'], $now, $noHistory), 'Austausch der Recovery-Schlüssel nur mit altem Root: UNTRUSTED_KEY');

    // ------------------------------------------------------------ Katalog
    $parsedTrust = GBDBUpdateTrust::parseTrust($trust1);
    $zipFile = $work . '/v1.2.3.zip';
    $writer = new GBDBUpdateZipWriter($zipFile, $now);
    $writer->addContent('gbdb_framework/a.php', "<?php echo 1;\n");
    $writer->addContent('gbdb_framework/b.txt', str_repeat('x', 5000));
    $writer->close();
    $files = ['gbdb_framework/a.php' => ['sha256' => hash('sha256', "<?php echo 1;\n"), 'size' => 14], 'gbdb_framework/b.txt' => ['sha256' => hash('sha256', str_repeat('x', 5000)), 'size' => 5000]];
    $manifestDoc = ['type' => 'gbdb-release', 'manifest_version' => 1, 'version' => '1.2.3', 'release_sequence' => 3, 'filename' => 'v1.2.3.zip', 'sha256' => hash_file('sha256', $zipFile),
        'size' => filesize($zipFile), 'published_at' => GBDBUpdateTrust::formatTime($now - 100), 'minimum_php' => '8.1.0', 'minimum_gbdb' => '0.0.0', 'requires_migration' => false,
        'migrations' => [], 'compatibility' => ['php_extensions' => ['json']], 'framework_dir' => 'gbdb_framework', 'config_files' => [], 'entry_files' => [], 'removed_files' => [],
        'changelog' => '', 'files' => $files, 'signing_key_id' => $release['key_id'], 'trust_version' => 1];
    $manifest = json_encode($manifestDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    $manifestSig = sign(GBDBUpdateSignature::CONTEXT_RELEASE, $manifest, $release);
    $catalogDoc = static function (int $sequence, string $manifestHash, int $releaseSequence = 3, int $expires = 0) use ($now, $day, $zipFile, $release) :string {
        return json_encode(['type' => 'gbdb-catalog', 'spec' => 1, 'sequence' => $sequence, 'created_at' => GBDBUpdateTrust::formatTime($now - 50), 'expires' => GBDBUpdateTrust::formatTime($expires ?: $now + 90 * $day), 'trust_version' => 1,
            'releases' => [['version' => '1.2.3', 'release_sequence' => $releaseSequence, 'filename' => 'v1.2.3.zip', 'manifest' => 'v1.2.3.json', 'manifest_sha256' => $manifestHash, 'sha256' => hash_file('sha256', $zipFile),
                'size' => filesize($zipFile), 'status' => 'published', 'signing_key_id' => $release['key_id'], 'published_at' => GBDBUpdateTrust::formatTime($now - 100)]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    };
    $seen = ['catalog_sequence' => 0, 'catalog_hash' => '', 'highest_release_sequence' => 0];
    $catalog = $catalogDoc(5, hash('sha256', $manifest));
    $catalogSig = sign(GBDBUpdateSignature::CONTEXT_CATALOG, $catalog, $release);
    $verifiedCatalog = GBDBUpdateTrust::verifyCatalog($catalog, $catalogSig, $parsedTrust, $seen, $now);
    check(isset($verifiedCatalog['_releases']['1.2.3']), 'Signierter Katalog akzeptiert');
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyCatalog($catalog, sign(GBDBUpdateSignature::CONTEXT_CATALOG, $catalog, $foreign), $parsedTrust, $seen, $now), 'Katalog von nicht autorisiertem Schlüssel: UNTRUSTED_KEY');
    expectStatus(GBDBUpdateTrust::ROLLBACK_DETECTED, static fn() => GBDBUpdateTrust::verifyCatalog($catalog, $catalogSig, $parsedTrust, ['catalog_sequence' => 6, 'catalog_hash' => '', 'highest_release_sequence' => 0], $now), 'Älterer Katalog: ROLLBACK_DETECTED');
    expectStatus(GBDBUpdateTrust::ROLLBACK_DETECTED, static fn() => GBDBUpdateTrust::verifyCatalog($catalog, $catalogSig, $parsedTrust, ['catalog_sequence' => 1, 'catalog_hash' => '', 'highest_release_sequence' => 9], $now), 'Katalog verschweigt bekannte Releases: ROLLBACK_DETECTED');
    $old = $catalogDoc(5, hash('sha256', $manifest), 3, $now - 10);
    expectStatus(GBDBUpdateTrust::EXPIRED_METADATA, static fn() => GBDBUpdateTrust::verifyCatalog($old, sign(GBDBUpdateSignature::CONTEXT_CATALOG, $old, $release), $parsedTrust, $seen, $now), 'Abgelaufener Katalog: EXPIRED_METADATA');
    $revokedTrust = GBDBUpdateTrust::parseTrust(trustDoc(2, [$root], [$recovery], [releaseKey($release, $now - $day, $now + 300 * $day, true)], [1], $now - 10, $now + 365 * $day));
    expectStatus(GBDBUpdateTrust::REVOKED_KEY, static fn() => GBDBUpdateTrust::verifyCatalog($catalog, $catalogSig, $revokedTrust, $seen, $now), 'Katalog mit widerrufenem Schlüssel: REVOKED_KEY');
    $expiredKeyTrust = GBDBUpdateTrust::parseTrust(trustDoc(2, [$root], [$recovery], [releaseKey($release, $now - 10 * $day, $now - $day)], [1], $now - 10, $now + 365 * $day));
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyCatalog($catalog, $catalogSig, $expiredKeyTrust, $seen, $now), 'Katalog mit abgelaufenem Schlüssel: UNTRUSTED_KEY');

    // ------------------------------------------------------------ Manifest und ZIP
    $verifiedManifest = GBDBUpdateTrust::verifyManifest($manifest, $manifestSig, $parsedTrust, $verifiedCatalog['_releases']['1.2.3'], $now);
    check($verifiedManifest['version'] === '1.2.3', 'Manifest mit autorisiertem Schlüssel akzeptiert');
    expectStatus(GBDBUpdateTrust::INVALID_METADATA, static fn() => GBDBUpdateTrust::verifyManifest(str_replace('"8.1.0"', '"7.0.0"', $manifest), $manifestSig, $parsedTrust, $verifiedCatalog['_releases']['1.2.3'], $now), 'Verändertes Manifest widerspricht dem Katalog');
    $otherSigned = sign(GBDBUpdateSignature::CONTEXT_RELEASE, $manifest, $foreign);
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyManifest($manifest, $otherSigned, $parsedTrust, $verifiedCatalog['_releases']['1.2.3'], $now), 'Manifest-Signatur von fremdem Schlüssel: UNTRUSTED_KEY');
    $badSig = json_encode(['format' => 'gbdb-sig/1', 'context' => 'gbdb-release/1', 'signatures' => [['key_id' => $release['key_id'], 'signature' => base64_encode(str_repeat("\1", 64))]]]);
    expectStatus(GBDBUpdateTrust::INVALID_SIGNATURE, static fn() => GBDBUpdateTrust::verifyManifest($manifest, $badSig, $parsedTrust, $verifiedCatalog['_releases']['1.2.3'], $now), 'Ungültige Manifest-Signatur: INVALID_SIGNATURE');
    expectStatus(GBDBUpdateTrust::REVOKED_KEY, static fn() => GBDBUpdateTrust::verifyManifest($manifest, $manifestSig, $revokedTrust, $verifiedCatalog['_releases']['1.2.3'], $now), 'Manifest mit widerrufenem Schlüssel: REVOKED_KEY');
    GBDBUpdateTrust::verifyZip($zipFile, $verifiedManifest);
    check(true, 'ZIP passt zu SHA-256, Größe und Dateimanifest');
    $copy = $work . '/tampered.zip';
    $bytes = (string) file_get_contents($zipFile);
    file_put_contents($copy, substr_replace($bytes, 'Y', 60, 1));
    expectStatus(GBDBUpdateTrust::INVALID_CHECKSUM, static fn() => GBDBUpdateTrust::verifyZip($copy, $verifiedManifest), 'Veränderte ZIP: INVALID_CHECKSUM');

    $revokedEntry = ['status' => 'revoked'] + $verifiedCatalog['_releases']['1.2.3'];
    expectStatus(GBDBUpdateTrust::RELEASE_REVOKED, static fn() => GBDBUpdateTrust::verifyManifest($manifest, $manifestSig, $parsedTrust, $revokedEntry, $now), 'Zurückgezogenes Release: RELEASE_REVOKED');
    $futureKeyTrust = GBDBUpdateTrust::parseTrust(trustDoc(2, [$root], [$recovery], [releaseKey($release, $now + 3600, $now + 300 * $day)], [1], $now - 10, $now + 365 * $day));
    expectStatus(GBDBUpdateTrust::UNTRUSTED_KEY, static fn() => GBDBUpdateTrust::verifyManifest($manifest, $manifestSig, $futureKeyTrust, $verifiedCatalog['_releases']['1.2.3'], $now), 'Schlüssel vor Gültigkeitsbeginn (1 h): UNTRUSTED_KEY');
    $wrongFileHash = $verifiedManifest;
    $wrongFileHash['files']['gbdb_framework/a.php']['sha256'] = str_repeat('0', 64);
    expectStatus(GBDBUpdateTrust::INVALID_CHECKSUM, static fn() => GBDBUpdateTrust::verifyZip($zipFile, $wrongFileHash), 'Einzel-Hash im Dateimanifest falsch, Gesamt-Hash stimmt: INVALID_CHECKSUM');

    // ------------------------------------------------------------ ZIP-Schutz
    $raw = static function (string $name, string $content, int $method = 0, int $external = 0100644 << 16) :string {
        $data = $method === 8 ? gzdeflate($content) : $content;
        $crc = crc32($content) & 0xFFFFFFFF;
        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $method, 0, 0x5821, $crc, strlen($data), strlen($content), strlen($name), 0) . $name . $data;
        $central = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, (3 << 8) | 20, 20, 0, $method, 0, 0x5821, $crc, strlen($data), strlen($content), strlen($name), 0, 0, 0, 0, $external, 0) . $name;

        return $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, 1, 1, strlen($central), strlen($local), 0);
    };
    file_put_contents($work . '/slip.zip', $raw('../evil.php', 'x'));
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/slip.zip'), 'ZIP-Slip (../) abgewiesen');
    file_put_contents($work . '/abs.zip', $raw('/etc/passwd', 'x'));
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/abs.zip'), 'Absoluter Pfad abgewiesen');
    file_put_contents($work . '/link.zip', $raw('link', '/etc/passwd', 0, 0120777 << 16));
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/link.zip'), 'Symbolischer Link abgewiesen');
    file_put_contents($work . '/bomb.zip', $raw('bomb.bin', str_repeat("\0", 20 * 1048576), 8));
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/bomb.zip'), 'ZIP-Bombe (Kompressionsrate) abgewiesen');
    file_put_contents($work . '/back.zip', $raw('a\\..\\b.php', 'x'));
    $twoEntries = static function (string $first, string $second) :string {
        $local = '';
        $central = '';

        foreach ([$first, $second] as $name) {
            $crc = crc32('x') & 0xFFFFFFFF;
            $offset = strlen($local);
            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0x5821, $crc, 1, 1, strlen($name), 0) . $name . 'x';
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, (3 << 8) | 20, 20, 0, 0, 0, 0x5821, $crc, 1, 1, strlen($name), 0, 0, 0, 0, 0100644 << 16, $offset) . $name;
        }

        return $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, 2, 2, strlen($central), strlen($local), 0);
    };
    file_put_contents($work . '/clash.zip', $twoEntries('gbdb_framework/a', 'gbdb_framework/a/b.php'));
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/clash.zip'), 'Pfad zugleich Datei und Ordner abgewiesen');
    expectInvalid(static function () use ($work) :void {
        $writer = new GBDBUpdateZipWriter($work . '/clash-write.zip');
        $writer->addContent('a/b.php', 'x');
        $writer->addContent('a', 'y');
    }, 'ZipWriter verweigert Datei/Ordner-Konflikt');
    expectInvalid(static fn() => GBDBUpdateZip::entries($work . '/back.zip'), 'Backslash-Pfad abgewiesen');

    // ------------------------------------------------------------ Index-Listing
    $listing = '<html><body><a href="?C=N;O=D">Name</a><a href="/">Parent</a><a href="v1.0.0.zip">v1.0.0.zip</a><a href="v1.10.0.zip">x</a>'
        . "<a href='v1.2.0.zip'>x</a><a href=\"v01.2.3.zip\">x</a><a href=\"v1.2.zip\">x</a><a href=\"https://evil.example/v9.9.9.zip\">x</a><a href=\"../v8.0.0.zip\">x</a>"
        . '<a href="v2.0.0.zip.sig">x</a><a href="./v1.9.0.zip">x</a><a href="v1.0.0.zip">dup</a><a href="v3.0.0.json">x</a></body></html>';
    check(GBDBFrameworkUpdater::parseListing($listing) === ['1.0.0', '1.2.0', '1.9.0', '1.10.0'], 'Index-Listing: nur relative vMAJOR.MINOR.PATCH.zip, semantisch sortiert', GBDBFrameworkUpdater::parseListing($listing));

    // ------------------------------------------------------------ Patch-Day
    date_default_timezone_set('Europe/Berlin');
    $monday = (new DateTimeImmutable('2026-11-02 10:00:00'))->getTimestamp();
    check(date('D H:i', (int) GBDBFrameworkUpdater::nextPatchTime('Fridays, 21:30:00', $monday)) === 'Fri 21:30', 'Patch-Day „Fridays, 21:30:00“');
    check(date('D H:i', (int) GBDBFrameworkUpdater::nextPatchTime('Montags, 09:00', $monday)) === 'Mon 09:00' && GBDBFrameworkUpdater::nextPatchTime('Montags, 09:00', $monday) > $monday + 6 * $day, 'Vergangener Termin heute → nächste Woche');
    check(GBDBFrameworkUpdater::nextPatchTime('daily, 10:30', $monday) === $monday + 1800, 'Täglicher Termin');
    check(GBDBFrameworkUpdater::nextPatchTime('Sometimes', $monday) === null, 'Ungültiges Muster erkannt');

    // ------------------------------------------------------------ Konfigurationsabgleich
    $local = "<?php\nclass Vars {\n    private const APP = [\n        \"name\" => \"Meine App\", // eigener Wert\n    ];\n\n    public static function a() :string {\n        return '{x}';\n    }\n}\n";
    $template = "<?php\nclass Vars {\n    private const APP = [\n        \"name\" => \"GBDB\",\n        \"new\" => ['x' => 1],\n    ];\n\n    private const NEU = [\"a\" => true];\n\n    public static function a() :string {\n        return 'neu';\n    }\n\n    public static function b() :int {\n        return 2;\n    }\n}\n";
    $merged = GBDBUpdateConfigMerger::merge($local, $template);
    check(str_contains($merged['source'], '"Meine App", // eigener Wert') && str_contains($merged['source'], "\"new\" => ['x' => 1]") && str_contains($merged['source'], 'private const NEU')
        && str_contains($merged['source'], 'function b()') && str_contains($merged['source'], "return '{x}'"), 'Konfiguration: nur ergänzt, eigene Werte und Methoden unverändert', $merged['changes']);
    check(GBDBUpdateConfigMerger::merge($merged['source'], $template)['changes'] === [], 'Konfigurationsabgleich ist idempotent');
    $conceptStyle = "<?php\nclass Vars {\n    public const FrameworkUpdater = [\n        \"check_for_updates\" => false,\n    ];\n}\n";
    $houseStyle = "<?php\nclass Vars {\n    private const FRAMEWORK_UPDATER = [\n        \"check_for_updates\" => true,\n        \"keep_backups\" => 3,\n    ];\n}\n";
    $aliased = GBDBUpdateConfigMerger::merge($conceptStyle, $houseStyle);
    check(!str_contains($aliased['source'], 'FRAMEWORK_UPDATER') && str_contains($aliased['source'], '"check_for_updates" => false') && str_contains($aliased['source'], '"keep_backups" => 3'), 'FrameworkUpdater und FRAMEWORK_UPDATER gelten als derselbe Bereich', $aliased['source']);
} catch (Throwable $exception) {
    echo $exception->getMessage(), "\n", $exception->getTraceAsString(), "\n";
    exit(1);
} finally {
    foreach (glob($work . '/*') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($work);
}

echo "\n", $checks, " Prüfungen bestanden.\n";
