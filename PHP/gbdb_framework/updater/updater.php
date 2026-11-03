<?php

declare(strict_types=1);

/**
 * GBDB-Framework-Updater: Update-Suche, Status, Überspringen, Planung automatischer Updates.
 *
 * Versionssuche ausschließlich über das Apache-Index-Listing des Update-Servers. Das Listing
 * ist nur Entdeckung, kein Vertrauensnachweis: Angeboten wird eine Version erst, wenn
 * trust.json, releases.json und ihr Manifest die Vertrauenskette bestanden haben.
 * Fehler des Update-Servers beeinflussen den normalen Betrieb nie.
 */
final class GBDBFrameworkUpdater {
    public const MAX_LISTING_BYTES = 1048576;

    private const SECURITY_STATES = [
        GBDBUpdateTrust::INVALID_SIGNATURE, GBDBUpdateTrust::UNTRUSTED_KEY, GBDBUpdateTrust::REVOKED_KEY, GBDBUpdateTrust::INVALID_CHECKSUM,
        GBDBUpdateTrust::ROLLBACK_DETECTED, GBDBUpdateTrust::INVALID_METADATA, GBDBUpdateTrust::API_REJECTED,
    ];

    private const WEEKDAYS = [
        'monday' => 1, 'mondays' => 1, 'montag' => 1, 'montags' => 1,
        'tuesday' => 2, 'tuesdays' => 2, 'dienstag' => 2, 'dienstags' => 2,
        'wednesday' => 3, 'wednesdays' => 3, 'mittwoch' => 3, 'mittwochs' => 3,
        'thursday' => 4, 'thursdays' => 4, 'donnerstag' => 4, 'donnerstags' => 4,
        'friday' => 5, 'fridays' => 5, 'freitag' => 5, 'freitags' => 5,
        'saturday' => 6, 'saturdays' => 6, 'samstag' => 6, 'samstags' => 6,
        'sunday' => 7, 'sundays' => 7, 'sonntag' => 7, 'sonntags' => 7,
        'daily' => 0, 'täglich' => 0,
    ];

    private function __construct() {
    }

    // ------------------------------------------------------------------ Status

    /**
     * Gespeicherter Status samt abgeleiteter Angaben (ohne Netzwerkzugriff).
     *
     * @return array<string, mixed>
     */
    public static function status() :array {
        try {
            $status = GBDBUpdateState::read('status');
        } catch (Throwable) {
            $status = [];
        }

        $installed = GBDBUpdateVersion::info();
        $status['installed'] = $installed['version'];
        $status['installed_official'] = $installed['official'];
        $status['skipped'] = array_values(array_filter((array) ($status['skipped'] ?? []), [GBDBUpdateTrust::class, 'isVersion']));
        $available = $status['available'] ?? null;

        if (is_array($available) && GBDBUpdateTrust::compareVersions((string) $available['version'], $installed['version']) <= 0) {
            $available = null;
        }

        $status['available'] = $available;
        $status['notify'] = is_array($available) && !in_array($available['version'], $status['skipped'], true);
        $status['due'] = self::checkDue($status);

        return $status;
    }

    /** @param array<string, mixed> $status */
    private static function checkDue(array $status) :bool {
        try {
            $settings = GBDBUpdateSettings::all();
        } catch (Throwable) {
            return false;
        }

        return $settings['check_for_updates'] && time() - (int) ($status['last_check'] ?? 0) >= $settings['check_interval'];
    }

    // ------------------------------------------------------------------ Update-Suche

    /**
     * Update-Suche. Ohne $force nur, wenn aktiviert und das Prüfintervall abgelaufen ist.
     *
     * @return array<string, mixed>
     */
    public static function check(bool $force = false) :array {
        $status = self::status();

        if (!$force && !$status['due']) {
            return $status;
        }

        $lock = GBDBUpdateState::lock();

        if ($lock === null) {
            return $status + ['busy' => true];
        }

        try {
            $stored = GBDBUpdateState::read('status');
            $stored['last_check'] = time();

            try {
                $result = self::discover();
                $stored['state'] = $result['available'] !== null ? 'available' : 'up_to_date';
                $stored['code'] = GBDBUpdateTrust::VERIFIED;
                $stored['message'] = $result['available'] !== null ? 'Version ' . $result['available']['version'] . ' ist verfügbar und verifiziert.' : 'Das Framework ist aktuell.';
                $stored['last_success'] = time();
                unset($result['_trust'], $result['_catalog']);
                $stored = array_replace($stored, $result);
            } catch (GBDBUpdateTrustException $exception) {
                $stored['state'] = 'error';
                $stored['code'] = $exception->status;
                $stored['message'] = $exception->getMessage();
                $stored['key_id'] = $exception->keyId;
                $stored['available'] = null;

                if (in_array($exception->status, self::SECURITY_STATES, true)) {
                    GBDBUpdateState::securityEvent($exception->status, $exception->getMessage(), $exception->keyId);
                }
            } catch (Throwable $exception) {
                // Server nicht erreichbar oder Antwort unbrauchbar: GBDB arbeitet normal weiter.
                $stored['state'] = 'offline';
                $stored['code'] = 'NETWORK';
                $stored['message'] = $exception->getMessage();
            }

            self::schedule($stored);
            GBDBUpdateState::write('status', $stored);
        } finally {
            GBDBUpdateState::unlock($lock);
        }

        return self::status();
    }

    /**
     * Versionen aus dem Apache-Index-Listing: nur relative Links der Form vMAJOR.MINOR.PATCH.zip.
     *
     * @return list<string>
     */
    public static function parseListing(string $html) :array {
        preg_match_all('/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $matches, PREG_SET_ORDER);
        $versions = [];

        foreach ($matches as $match) {
            $href = html_entity_decode($match[1] !== '' ? $match[1] : ($match[2] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $href = preg_replace('~^\./~', '', $href) ?? '';

            if (preg_match('/^v((?:0|[1-9]\d{0,5})\.(?:0|[1-9]\d{0,5})\.(?:0|[1-9]\d{0,5}))\.zip$/D', $href, $version)) {
                $versions[$version[1]] = true;
            }
        }

        $list = array_keys($versions);
        usort($list, 'version_compare');

        return $list;
    }

    private static function base() :string {
        return rtrim(GBDBUpdateSettings::url('update_server'), '/') . '/';
    }

    private static function fetch(string $name, int $max) :string {
        $response = GBDBUpdateHttp::get(self::base() . $name, $max);

        if (!$response['ok']) {
            throw new RuntimeException($name . ' konnte nicht geladen werden: ' . $response['error']);
        }

        return $response['body'];
    }

    /**
     * Vertrauensmetadaten und Release-Katalog laden, prüfen und den Vertrauensstand fortschreiben.
     *
     * @return array{trust: array<string, mixed>, catalog: array<string, mixed>, state: array<string, mixed>}
     */
    public static function verifiedMetadata() :array {
        if (!GBDBUpdateSignature::available()) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::CRYPTO_UNAVAILABLE, 'Die PHP-Erweiterung sodium fehlt. Updates sind blockiert; der normale Betrieb läuft weiter.');
        }

        $trusted = GBDBUpdateState::trustState();

        // Datei und Signatur werden getrennt geladen; trifft die Abfrage genau eine laufende
        // Veröffentlichung, passen sie kurz nicht zusammen. Deshalb einmal neu laden, dann gilt das Ergebnis.
        for ($attempt = 1; ; $attempt++) {
            try {
                $trustBytes = self::fetch('trust.json', GBDBUpdateTrust::MAX_TRUST_BYTES);
                $trustSignature = self::fetch('trust.json.sig', GBDBUpdateSignature::MAX_SIGNATURE_FILE);
                $checked = GBDBUpdateTrust::verifyTrust($trustBytes, $trustSignature, $trusted, time(), static function (int $version) :array {
                    return [self::fetch('trust.' . $version . '.json', GBDBUpdateTrust::MAX_TRUST_BYTES), self::fetch('trust.' . $version . '.json.sig', GBDBUpdateSignature::MAX_SIGNATURE_FILE)];
                });
                $catalogBytes = self::fetch('releases.json', GBDBUpdateTrust::MAX_CATALOG_BYTES);
                $catalogSignature = self::fetch('releases.json.sig', GBDBUpdateSignature::MAX_SIGNATURE_FILE);
                $catalog = GBDBUpdateTrust::verifyCatalog($catalogBytes, $catalogSignature, $checked['trust'], $trusted, time());

                break;
            } catch (GBDBUpdateTrustException $exception) {
                if ($attempt >= 2 || !in_array($exception->status, [GBDBUpdateTrust::INVALID_SIGNATURE, GBDBUpdateTrust::INVALID_METADATA, GBDBUpdateTrust::EXPIRED_METADATA], true)) {
                    throw $exception;
                }

                // Abgelaufene Metadaten: Der Release-Manager erneuert sie bei jedem Aufruf der
                // Verify-API. Ein Lebenszeichen (GET, ohne Daten) stößt das an; danach neu prüfen.
                if ($exception->status === GBDBUpdateTrust::EXPIRED_METADATA) {
                    try {
                        GBDBUpdateHttp::get(GBDBUpdateSettings::url('verify_api'), 4096, 15);
                    } catch (Throwable) {
                    }
                }

                sleep(2);
            }
        }

        $state = $checked['state'] + [
            'catalog_sequence' => $catalog['_sequence'],
            'catalog_hash' => $catalog['_hash'],
            'highest_release_sequence' => max($trusted['highest_release_sequence'], $catalog['_highest']),
        ];
        GBDBUpdateState::saveTrustState($state, $trustBytes, $trustSignature);

        if ($checked['rotated']) {
            GBDBUpdateState::addHistory(['type' => 'trust', 'result' => 'root_rotated', 'trust_version' => $state['trust_version']]);
        }

        return ['trust' => $checked['trust'], 'catalog' => $catalog, 'state' => $state, 'catalog_bytes' => $catalogBytes, 'catalog_signature' => $catalogSignature];
    }

    /**
     * Manifest einer Version laden und prüfen.
     *
     * @param array<string, mixed> $metadata aus verifiedMetadata()
     * @return array{manifest: array<string, mixed>, bytes: string, signature: string, entry: array<string, mixed>}
     */
    public static function verifiedManifest(array $metadata, string $version, bool $retry = true) :array {
        try {
            return self::loadManifest($metadata, $version);
        } catch (GBDBUpdateTrustException $exception) {
            // Während der Herausgeber neu signiert, passen Manifest und Katalog kurz nicht zusammen.
            if (!$retry || $exception->status !== GBDBUpdateTrust::INVALID_METADATA) {
                throw $exception;
            }

            sleep(2);

            return self::loadManifest(self::verifiedMetadata(), $version);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{manifest: array<string, mixed>, bytes: string, signature: string, entry: array<string, mixed>}
     */
    private static function loadManifest(array $metadata, string $version) :array {
        $entry = $metadata['catalog']['_releases'][$version] ?? null;

        if ($entry === null) {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::UNTRUSTED_KEY, 'Version ' . $version . ' ist im signierten Release-Katalog nicht freigegeben.');
        }

        if ($entry['status'] !== 'published') {
            throw new GBDBUpdateTrustException(GBDBUpdateTrust::RELEASE_REVOKED, 'Version ' . $version . ' wurde vom Herausgeber zurückgezogen.');
        }

        $bytes = self::fetch($entry['manifest'], GBDBUpdateTrust::MAX_MANIFEST_BYTES);
        $signature = self::fetch($entry['manifest'] . '.sig', GBDBUpdateSignature::MAX_SIGNATURE_FILE);

        return ['manifest' => GBDBUpdateTrust::verifyManifest($bytes, $signature, $metadata['trust'], $entry, time()), 'bytes' => $bytes, 'signature' => $signature, 'entry' => $entry];
    }

    /**
     * Technische Kompatibilität eines Releases mit dieser Installation.
     *
     * @param array<string, mixed> $manifest
     * @return list<string> Fehler; leer = kompatibel
     */
    public static function compatibility(array $manifest) :array {
        $errors = [];
        $installed = GBDBUpdateVersion::current();

        if (version_compare(PHP_VERSION, (string) $manifest['minimum_php'], '<')) {
            $errors[] = 'PHP ' . $manifest['minimum_php'] . ' oder neuer erforderlich, installiert ist ' . PHP_VERSION . '.';
        }

        if (GBDBUpdateTrust::compareVersions($installed, (string) $manifest['minimum_gbdb']) < 0) {
            $errors[] = 'Direktes Update erst ab GBDB ' . $manifest['minimum_gbdb'] . ' möglich, installiert ist ' . $installed . '. Zuerst eine Zwischenversion installieren.';
        }

        foreach ((array) ($manifest['compatibility']['php_extensions'] ?? []) as $extension) {
            if (is_string($extension) && !extension_loaded($extension)) {
                $errors[] = 'Die PHP-Erweiterung „' . $extension . '“ fehlt.';
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private static function discover() :array {
        $listing = GBDBUpdateHttp::get(self::base(), self::MAX_LISTING_BYTES);

        if (!$listing['ok']) {
            throw new RuntimeException('Update-Server nicht erreichbar: ' . $listing['error']);
        }

        $listed = self::parseListing($listing['body']);
        $metadata = self::verifiedMetadata();
        $catalog = $metadata['catalog'];
        $trust = $metadata['trust'];
        $installed = GBDBUpdateVersion::info();
        $notes = [];
        $warnings = [];
        $candidates = [];

        foreach ($listed as $version) {
            $entry = $catalog['_releases'][$version] ?? null;

            if ($entry === null) {
                $notes[] = 'v' . $version . '.zip liegt auf dem Server, ist aber nicht im signierten Katalog freigegeben und wird ignoriert.';
            } elseif ($entry['status'] === 'published' && GBDBUpdateTrust::compareVersions($version, $installed['version']) > 0) {
                $candidates[] = $version;
            }
        }

        foreach ($catalog['_releases'] as $version => $entry) {
            if ($entry['status'] === 'published' && !in_array((string) $version, $listed, true) && GBDBUpdateTrust::compareVersions((string) $version, $installed['version']) > 0) {
                $notes[] = 'Version ' . $version . ' ist freigegeben, liegt aber nicht im Verzeichnis des Update-Servers.';
            }
        }

        // Installierte Version neu bewerten: zurückgezogen oder mit widerrufenem Schlüssel signiert?
        $own = $catalog['_releases'][$installed['version']] ?? null;

        if ($installed['official'] && $own !== null) {
            if ($own['status'] === 'revoked') {
                $warnings[] = 'Die installierte Version ' . $installed['version'] . ' wurde vom Herausgeber zurückgezogen. Bitte zeitnah aktualisieren.';
                GBDBUpdateState::securityEvent(GBDBUpdateTrust::REVOKED_KEY, 'Installierte Version ' . $installed['version'] . ' zurückgezogen.');
            } else {
                $keyStatus = GBDBUpdateTrust::keyStatus($trust, (string) $own['signing_key_id'], 'release', time());

                if ($keyStatus === GBDBUpdateTrust::REVOKED_KEY) {
                    $warnings[] = 'Die installierte Version beruht auf dem widerrufenen Schlüssel ' . $own['signing_key_id'] . '. Sicherheitsereignis: Installation prüfen und aktualisieren.';
                    GBDBUpdateState::securityEvent(GBDBUpdateTrust::REVOKED_KEY, 'Installierte Version mit widerrufenem Schlüssel signiert.', (string) $own['signing_key_id']);
                }
            }
        }

        usort($candidates, 'version_compare');
        $available = null;
        $versions = [];

        // Neueste vertrauenswürdige und kompatible Version; ungültige Kandidaten werden übersprungen.
        foreach (array_reverse($candidates) as $version) {
            try {
                $checked = self::verifiedManifest($metadata, $version);
            } catch (GBDBUpdateTrustException $exception) {
                $warnings[] = 'Version ' . $version . ': ' . $exception->getMessage();
                GBDBUpdateState::securityEvent($exception->status, 'Version ' . $version . ': ' . $exception->getMessage(), $exception->keyId);

                continue;
            }

            $manifest = $checked['manifest'];
            $incompatible = self::compatibility($manifest);
            $info = [
                'version' => $version,
                'release_sequence' => $manifest['release_sequence'],
                'published_at' => $manifest['published_at'],
                'size' => $manifest['size'],
                'sha256' => $manifest['sha256'],
                'signing_key_id' => $manifest['signing_key_id'],
                'minimum_php' => $manifest['minimum_php'],
                'minimum_gbdb' => $manifest['minimum_gbdb'],
                'requires_migration' => $manifest['requires_migration'],
                'changelog' => is_string($manifest['changelog'] ?? null) && preg_match('/^.{0,4000}/us', $manifest['changelog'], $cut) === 1 ? $cut[0] : '',
                'verification' => GBDBUpdateTrust::VERIFIED,
                'incompatible' => $incompatible,
            ];
            $versions[] = $info;

            if ($incompatible === [] && $available === null) {
                $available = $info;
            }
        }

        return [
            'available' => $available,
            'versions' => $versions,
            'listed' => $listed,
            'notes' => $notes,
            'warnings' => $warnings,
            'trust' => self::trustSummary($trust),
            'catalog' => ['sequence' => $catalog['_sequence'], 'created_at' => GBDBUpdateTrust::formatTime($catalog['_created']), 'expires' => GBDBUpdateTrust::formatTime($catalog['_expires'])],
        ];
    }

    /**
     * @param array<string, mixed> $trust
     * @return array<string, mixed>
     */
    public static function trustSummary(array $trust) :array {
        $keys = [];

        foreach ($trust['_release_keys'] as $id => $key) {
            $keys[] = [
                'key_id' => $id,
                'roles' => $key['roles'],
                'valid_from' => GBDBUpdateTrust::formatTime($key['from']),
                'valid_until' => GBDBUpdateTrust::formatTime($key['until']),
                'revoked' => $key['revoked'],
                'reason' => $key['reason'],
                'status' => GBDBUpdateTrust::keyStatus($trust, (string) $id, $key['roles'][0], time()),
            ];
        }

        return [
            'version' => $trust['_version'],
            'issued_at' => GBDBUpdateTrust::formatTime($trust['_issued']),
            'expires' => GBDBUpdateTrust::formatTime($trust['_expires']),
            'root_keys' => array_keys($trust['_root']),
            'recovery_keys' => array_keys($trust['_recovery']),
            'release_keys' => $keys,
        ];
    }

    // ------------------------------------------------------------------ Überspringen

    public static function skip(string $version) :void {
        self::changeSkipped($version, true);
    }

    public static function unskip(string $version) :void {
        self::changeSkipped($version, false);
    }

    private static function changeSkipped(string $version, bool $skip) :void {
        if (!GBDBUpdateTrust::isVersion($version)) {
            throw new InvalidArgumentException('Ungültige Versionsnummer.');
        }

        $lock = GBDBUpdateState::lock();

        try {
            $status = GBDBUpdateState::read('status');
            $skipped = array_values(array_diff((array) ($status['skipped'] ?? []), [$version]));

            if ($skip) {
                $skipped[] = $version;
            }

            $status['skipped'] = $skipped;
            self::schedule($status);
            GBDBUpdateState::write('status', $status);
        } finally {
            GBDBUpdateState::unlock($lock);
        }
    }

    // ------------------------------------------------------------------ Automatische Updates

    /**
     * Nächster Zeitpunkt nach dem Muster „Fridays, 21:30:00“ (auch „Freitags, 21:30“ oder „daily, 03:00“).
     */
    public static function nextPatchTime(string $pattern, int $now) :?int {
        if (!preg_match('/^\s*([A-Za-zäÄ]+)\s*,?\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*$/u', $pattern, $match)) {
            return null;
        }

        $day = self::WEEKDAYS[function_exists('mb_strtolower') ? mb_strtolower($match[1]) : strtolower($match[1])] ?? null;
        $hour = (int) $match[2];
        $minute = (int) $match[3];
        $second = (int) ($match[4] ?? 0);

        if ($day === null || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        $base = (new DateTimeImmutable('@' . $now))->setTimezone(new DateTimeZone(date_default_timezone_get()));

        for ($offset = 0; $offset <= 7; $offset++) {
            $candidate = $base->modify('+' . $offset . ' day')->setTime($hour, $minute, $second);

            if (($day === 0 || (int) $candidate->format('N') === $day) && $candidate->getTimestamp() >= $now) {
                return $candidate->getTimestamp();
            }
        }

        return null;
    }

    /**
     * Automatisches Update einplanen oder Planung aufheben (nur VERIFIED, kompatibel, nicht übersprungen).
     *
     * @param array<string, mixed> $status
     */
    private static function schedule(array &$status) :void {
        $settings = GBDBUpdateSettings::all();
        $available = $status['available'] ?? null;
        $installed = GBDBUpdateVersion::current();

        if (!$settings['auto_updating']['enabled'] || !is_array($available) || in_array($available['version'], (array) ($status['skipped'] ?? []), true)
            || GBDBUpdateTrust::compareVersions((string) $available['version'], $installed) <= 0 || ($status['state'] ?? '') === 'error') {
            unset($status['scheduled']);

            return;
        }

        if (($status['scheduled']['version'] ?? null) === $available['version']) {
            return;
        }

        $due = self::nextPatchTime((string) $settings['auto_updating']['patch_day'], time());

        if ($due === null) {
            $status['schedule_error'] = 'auto_updating.patch_day ist ungültig („' . $settings['auto_updating']['patch_day'] . '“). Beispiel: Fridays, 21:30:00';
            unset($status['scheduled']);

            return;
        }

        unset($status['schedule_error']);
        $status['scheduled'] = ['version' => $available['version'], 'due_at' => $due];
    }

    /**
     * Hintergrundarbeit bei normalen Anfragen (ohne Cronjob): fällige Update-Suche und fällige
     * automatische Installation. $longRunning nur, wenn die Antwort bereits ausgeliefert ist.
     */
    public static function tick(bool $longRunning) :void {
        try {
            $status = self::status();

            if ($status['due']) {
                $status = self::check();
            }

            $scheduled = $status['scheduled'] ?? null;

            if ($longRunning && is_array($scheduled) && (int) $scheduled['due_at'] <= time() && GBDBUpdateSettings::all()['auto_updating']['enabled']) {
                GBDBUpdateInstaller::runAutomatic((string) $scheduled['version']);
            }
        } catch (Throwable $exception) {
            error_log('[GBDB Updater] ' . $exception->getMessage());
        }
    }

    /** Läuft eine Update-Suche oder ein automatisches Update bei dieser Anfrage an? Ohne Dateizugriff außer Status. */
    public static function tickDue() :bool {
        try {
            $settings = GBDBUpdateSettings::all();

            if (!$settings['check_for_updates'] && !$settings['auto_updating']['enabled']) {
                return false;
            }

            $file = GBDBUpdateState::dir('state/status.json');

            if (!is_file($file)) {
                return $settings['check_for_updates'];
            }

            if ($settings['check_for_updates'] && time() - (int) filemtime($file) >= $settings['check_interval']) {
                return true;
            }

            $status = GBDBUpdateState::read('status');

            return is_array($status['scheduled'] ?? null) && (int) $status['scheduled']['due_at'] <= time();
        } catch (Throwable) {
            return false;
        }
    }
}
