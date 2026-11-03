<?php

declare(strict_types=1);

/**
 * Struktur der replizierten Tabellen einer Instanz.
 */
final class GDPSchema {
    /**
     * Replizierte Tabellen (alles außer LOCAL_ONLY) samt Spalten.
     *
     * @return list<array{database:string, table:string, columns:list<string>}>
     */
    public static function tables(string $instanceId) :array {
        return GBDBEngine::withInstance($instanceId, static function () use ($instanceId) :array {
            $tables = [];

            foreach (GBDBEngine::listDBs() as $database) {
                $database = (string) $database;

                foreach (GBDBEngine::listTables($database) as $table) {
                    $table = (string) $table;

                    if (!GDPPolicyEngine::mode(GDPPolicyEngine::resolve($instanceId, $database, $table))->replicated()) {
                        continue;
                    }

                    $columns = array_values(array_map('strval', array_filter(GBDBEngine::getKeys($database, $table), static fn($key) :bool => (string) $key !== 'id')));
                    $tables[] = ['database' => $database, 'table' => $table, 'columns' => $columns];
                }
            }

            usort($tables, static fn(array $a, array $b) :int => strcmp($a['database'] . "\0" . $a['table'], $b['database'] . "\0" . $b['table']));

            return $tables;
        });
    }

    public static function fingerprint(string $instanceId) :string {
        $tables = self::tables($instanceId);

        foreach ($tables as $index => $table) {
            sort($tables[$index]['columns'], SORT_STRING);
        }

        return GDPCanonical::hash($tables);
    }

    /**
     * Enthält eine replizierte Tabelle dieser Instanz bereits Datensätze?
     *
     * @return list<string>
     */
    public static function populatedTables(string $instanceId, array $tables) :array {
        return GBDBEngine::withInstance($instanceId, static function () use ($tables) :array {
            $populated = [];

            foreach ($tables as $table) {
                $rows = GBDBEngine::getData((string) $table['database'], (string) $table['table']);

                if (is_array($rows) && $rows !== []) {
                    $populated[] = $table['database'] . '.' . $table['table'];
                }
            }

            return $populated;
        });
    }
}

/**
 * Snapshot-Bootstrap (Konzept Kapitel 17).
 *
 * Der Hub schreibt unter dem exklusiven Lock ein konsistentes Abbild aller
 * replizierten Tabellen bei Hubsequenz S. Das Replikat lädt die Chunks einzeln
 * und wiederholbar in einen Staging-Bereich, prüft jede Prüfsumme und das
 * Manifest und aktiviert den Stand erst danach. Eine abgebrochene Aktivierung
 * wird beim nächsten Durchlauf aus dem Staging wiederholt.
 */
final class GDPSnapshotManager {
    private const KEEP = 2;
    private const LIFETIME = 86400;

    private static function directory(string $instanceId, string $snapshotId = '') :string {
        return $snapshotId === ''
            ? GDPStore::path('instances', $instanceId, 'snapshots')
            : GDPStore::path('instances', $instanceId, 'snapshots', $snapshotId);
    }

    private static function stagingDirectory(string $instanceId, string $snapshotId) :string {
        return GDPStore::path('staging', $instanceId, $snapshotId);
    }

    /**
     * Hub: Manifest liefern. Ein Replikat, das einen abgebrochenen Download
     * fortsetzt, erhält denselben Snapshot; sonst entsteht ein frischer. So
     * spiegelt jeder neue Bootstrap auch Änderungen, die an GDP vorbei
     * geschrieben wurden (Integritätsreparatur), statt einen alten Stand zu liefern.
     */
    public static function currentManifest(string $instanceId, string $resumeSnapshotId = '') :array {
        $existing = $resumeSnapshotId === '' || !GDPCanonical::isIdentifier($resumeSnapshotId) ? null : GDPStore::read(static function () use ($instanceId, $resumeSnapshotId) :?array {
            $manifest = GDPStore::readJson(self::directory($instanceId, $resumeSnapshotId) . '/manifest.json', []);

            return ($manifest['status'] ?? '') === 'complete' && (int) ($manifest['expires_at'] ?? 0) > time()
                && ($manifest['schema_fingerprint'] ?? '') === GDPSchema::fingerprint($instanceId) ? $manifest : null;
        });

        return $existing ?? self::create($instanceId);
    }

    /**
     * Replikat: Snapshot-ID eines begonnenen Downloads, falls vorhanden.
     */
    public static function stagedSnapshotId(string $instanceId) :string {
        return GDPStore::read(static function () use ($instanceId) :string {
            foreach (array_reverse(GDPStore::listDirectory(GDPStore::path('staging', $instanceId))) as $snapshotId) {
                if (GDPStore::exists(self::stagingDirectory($instanceId, $snapshotId) . '/manifest.json')) {
                    return $snapshotId;
                }
            }

            return '';
        });
    }

    /**
     * Hub: konsistenten Snapshot erzeugen. Schreibzugriffe warten solange auf den Lock.
     */
    public static function create(string $instanceId) :array {
        if (GDPNodeIdentity::role() !== GDPNodeRole::HUB) {
            throw new GDPSnapshotException(GDPErrorCode::NOT_CONFIGURED, 'Snapshots erstellt nur der Hub.');
        }

        return GDPStore::transaction(static function () use ($instanceId) :array {
            $instance = GDPInstanceRegistry::get($instanceId) ?? [];
            $snapshotId = 'snap-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));
            $directory = self::directory($instanceId, $snapshotId);
            $chunkBytes = GDPConfig::int('snapshot_chunk_bytes', 16384, 8388608);
            $tables = GDPSchema::tables($instanceId);
            $chunks = [];
            $buffer = '';
            $summary = [];
            $flush = static function () use (&$buffer, &$chunks, $directory) :void {
                if ($buffer === '') {
                    return;
                }

                $index = count($chunks);
                GDPStore::ensureDirectory($directory);
                $file = $directory . sprintf('/chunk-%06d.dat', $index);

                if (@file_put_contents($file, GDPStore::seal($buffer)) === false) {
                    throw new GDPSnapshotException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Snapshot-Chunk konnte nicht geschrieben werden.');
                }

                $chunks[] = ['index' => $index, 'hash' => 'sha256:' . hash('sha256', $buffer), 'bytes' => strlen($buffer)];
                $buffer = '';
            };

            GBDBEngine::withInstance($instanceId, static function () use ($tables, &$buffer, &$summary, $chunkBytes, $flush) :void {
                foreach ($tables as $table) {
                    $buffer .= GDPCanonical::encode(['type' => 'table'] + $table) . "\n";
                    $rows = GBDBEngine::getData($table['database'], $table['table']);
                    $count = 0;

                    foreach (is_array($rows) ? $rows : [] as $row) {
                        $buffer .= GDPCanonical::encode(['type' => 'row', 'database' => $table['database'], 'table' => $table['table'], 'row' => $row]) . "\n";
                        $count++;

                        if (strlen($buffer) >= $chunkBytes) {
                            $flush();
                        }
                    }

                    $summary[] = ['database' => $table['database'], 'table' => $table['table'], 'rows' => $count];
                }
            });
            $flush();

            $manifest = [
                'snapshot_id' => $snapshotId,
                'instance_id' => $instanceId,
                'schema_version' => (int) ($instance['schema_version'] ?? 1),
                'schema_fingerprint' => GDPSchema::fingerprint($instanceId),
                'hub_sequence_at_snapshot' => (int) GDPJournal::sequences($instanceId)['hub_sequence'],
                'policy_version' => (int) GDPPolicyEngine::document($instanceId)['policy_version'],
                'tables' => $summary,
                'chunks' => $chunks,
                'created_at' => time(),
                'expires_at' => time() + self::LIFETIME,
                'status' => 'complete',
            ];
            $manifest['manifest_hash'] = GDPCanonical::hash($manifest);
            GDPStore::writeJson($directory . '/manifest.json', $manifest);
            GDPInstanceRegistry::save($instanceId, ['current_snapshot_id' => $snapshotId]);
            GDPAuditLogger::log('snapshot.created', ['instance' => $instanceId, 'snapshot_id' => $snapshotId, 'hub_sequence' => $manifest['hub_sequence_at_snapshot'], 'chunks' => count($chunks)]);
            self::cleanup($instanceId);

            return $manifest;
        });
    }

    private static function cleanup(string $instanceId) :void {
        $snapshots = GDPStore::listDirectory(self::directory($instanceId));

        foreach (array_slice($snapshots, 0, max(0, count($snapshots) - self::KEEP)) as $snapshotId) {
            GDPStore::removeTree(self::directory($instanceId, $snapshotId));
        }
    }

    /**
     * Hub: einzelnen Chunk ausliefern.
     */
    public static function chunk(string $instanceId, string $snapshotId, int $index) :array {
        return GDPStore::read(static function () use ($instanceId, $snapshotId, $index) :array {
            $manifest = GDPStore::readJson(self::directory($instanceId, $snapshotId) . '/manifest.json', []);
            $meta = $manifest['chunks'][$index] ?? null;

            if (!is_array($meta)) {
                throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_REQUIRED, 'Snapshot oder Chunk ist nicht mehr verfügbar.', ['snapshot_id' => $snapshotId, 'index' => $index]);
            }

            $raw = @file_get_contents(self::directory($instanceId, $snapshotId) . sprintf('/chunk-%06d.dat', $index));
            $plain = $raw === false ? '' : GDPStore::unseal($raw);

            if (!hash_equals((string) $meta['hash'], 'sha256:' . hash('sha256', $plain))) {
                throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED, 'Snapshot-Chunk auf dem Hub ist beschädigt.', ['snapshot_id' => $snapshotId, 'index' => $index]);
            }

            return ['index' => $index, 'hash' => $meta['hash'], 'data' => base64_encode($plain)];
        });
    }

    public static function verifyManifest(array $manifest, string $instanceId) :void {
        $hash = (string) ($manifest['manifest_hash'] ?? '');
        $basis = $manifest;
        unset($basis['manifest_hash']);

        if (($manifest['instance_id'] ?? '') !== $instanceId || !is_array($manifest['chunks'] ?? null) || !is_int($manifest['hub_sequence_at_snapshot'] ?? null)
            || !GDPCanonical::isIdentifier((string) ($manifest['snapshot_id'] ?? '')) || !hash_equals(GDPCanonical::hash($basis), $hash)) {
            throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED, 'Snapshot-Manifest ist ungültig.', ['instance' => $instanceId]);
        }
    }

    /**
     * Replikat: fehlende Chunks laden. Bereits geprüfte Chunks werden übersprungen,
     * sodass ein abgebrochener Transfer fortgesetzt wird.
     */
    public static function download(GDPTransport $transport, string $instanceId, array $manifest) :void {
        self::verifyManifest($manifest, $instanceId);
        $snapshotId = (string) $manifest['snapshot_id'];
        $directory = self::stagingDirectory($instanceId, $snapshotId);
        GDPStore::transaction(static function () use ($instanceId, $snapshotId, $directory, $manifest) :void {
            // Ältere, nicht mehr fortsetzbare Downloads verwerfen.
            foreach (GDPStore::listDirectory(GDPStore::path('staging', $instanceId)) as $other) {
                if ($other !== $snapshotId) {
                    GDPStore::removeTree(GDPStore::path('staging', $instanceId, $other));
                }
            }

            GDPStore::writeJson($directory . '/manifest.json', $manifest);
        });

        foreach ($manifest['chunks'] as $meta) {
            $file = $directory . sprintf('/chunk-%06d.dat', (int) $meta['index']);
            $present = GDPStore::read(static function () use ($file, $meta) :bool {
                $raw = @file_get_contents($file);

                return $raw !== false && hash_equals((string) $meta['hash'], 'sha256:' . hash('sha256', GDPStore::unseal($raw)));
            });

            if ($present) {
                continue;
            }

            $response = $transport->call('snapshot/chunk', ['instance_id' => $instanceId, 'snapshot_id' => $snapshotId, 'index' => (int) $meta['index']]);
            $plain = base64_decode((string) ($response['data'] ?? ''), true);

            if ($plain === false || !hash_equals((string) $meta['hash'], 'sha256:' . hash('sha256', $plain))) {
                throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED, 'Prüfsumme eines Snapshot-Chunks stimmt nicht.', ['snapshot_id' => $snapshotId, 'index' => (int) $meta['index']]);
            }

            GDPStore::transaction(static function () use ($directory, $file, $plain) :void {
                GDPStore::ensureDirectory($directory);

                if (@file_put_contents($file, GDPStore::seal($plain)) === false) {
                    throw new GDPSnapshotException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Snapshot-Chunk konnte nicht zwischengespeichert werden.');
                }
            });
        }

    }

    /**
     * Replikat: prüft vor dem Ersetzen auf vorhandene, abweichende Bestandsdaten.
     *
     * @return array{populated:list<string>, unsent:int}
     */
    public static function preflight(string $instanceId, array $manifest) :array {
        return GDPStore::read(static function () use ($instanceId, $manifest) :array {
            $tables = array_map(static fn(array $table) :array => ['database' => $table['database'], 'table' => $table['table']], (array) ($manifest['tables'] ?? []));
            $sequences = GDPJournal::sequences($instanceId);
            $pushed = (int) GDPCursorManager::get($instanceId, 'hub')['accepted_source_sequence'];

            return [
                'populated' => GDPSchema::populatedTables($instanceId, $tables),
                'unsent' => max(0, (int) $sequences['source_sequence'] - $pushed),
            ];
        });
    }

    /**
     * Replikat: geprüften Stand atomar aktivieren (Redo-fähig über den Aktivierungsmarker).
     */
    public static function activate(string $instanceId, string $snapshotId) :array {
        return GDPStore::transaction(static function () use ($instanceId, $snapshotId) :array {
            $directory = self::stagingDirectory($instanceId, $snapshotId);
            $manifest = GDPStore::readJson($directory . '/manifest.json', []);
            self::verifyManifest($manifest, $instanceId);
            GDPInstanceRegistry::save($instanceId, ['activation' => $snapshotId, 'sync_state' => GDPSyncState::INITIALIZING->value]);
            $tables = [];

            foreach ($manifest['chunks'] as $meta) {
                $raw = @file_get_contents($directory . sprintf('/chunk-%06d.dat', (int) $meta['index']));
                $plain = $raw === false ? '' : GDPStore::unseal($raw);

                if (!hash_equals((string) $meta['hash'], 'sha256:' . hash('sha256', $plain))) {
                    throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED, 'Staging ist unvollständig; Snapshot erneut laden.', ['snapshot_id' => $snapshotId, 'index' => (int) $meta['index']]);
                }

                foreach (explode("\n", rtrim($plain, "\n")) as $line) {
                    $item = GDPCanonical::decode($line);
                    $key = (string) ($item['database'] ?? '') . "\0" . (string) ($item['table'] ?? '');

                    if (($item['type'] ?? '') === 'table') {
                        $tables[$key] = ['database' => $item['database'], 'table' => $item['table'], 'columns' => $item['columns'], 'rows' => []];
                    } elseif (($item['type'] ?? '') === 'row' && isset($tables[$key])) {
                        $tables[$key]['rows'][] = $item['row'];
                    } else {
                        throw new GDPSnapshotException(GDPErrorCode::SNAPSHOT_CHECKSUM_FAILED, 'Snapshot-Inhalt ist nicht wohlgeformt.');
                    }
                }
            }

            GDPWriteInterceptor::applying(static function () use ($instanceId, $tables) :void {
                GBDBEngine::withInstance($instanceId, static function () use ($tables) :void {
                    if (!GBDBEngine::existsInstance(GBDBEngine::getInstance())) {
                        GBDBEngine::createInstance(GBDBEngine::getInstance());
                    }

                    foreach ($tables as $table) {
                        if (!GBDBEngine::replaceTableContents($table['database'], $table['table'], $table['columns'], $table['rows'])) {
                            throw new GDPSnapshotException(GDPErrorCode::STORAGE_TRANSACTION_FAILED, 'Tabelle konnte nicht aktiviert werden.', ['table' => $table['database'] . '.' . $table['table']]);
                        }
                    }
                });
            });

            // Nicht übertragene lokale Operationen wurden durch den Snapshot ersetzt.
            // Der Hub überspringt sie nach der nächsten Anmeldung (source_reset).
            $sequences = GDPJournal::sequences($instanceId);
            GDPCursorManager::update($instanceId, 'hub', [
                'applied_hub_sequence' => (int) $manifest['hub_sequence_at_snapshot'],
                'accepted_source_sequence' => (int) $sequences['source_sequence'],
            ]);
            GDPJournal::compact($instanceId, GDPJournal::OUTBOX, (int) $sequences['source_sequence']);
            $instance = GDPInstanceRegistry::get($instanceId) ?? [];
            GDPInstanceRegistry::save($instanceId, [
                'activation' => null,
                'current_snapshot_id' => $snapshotId,
                'schema_version' => (int) $manifest['schema_version'],
                'bootstrap_generation' => (int) ($instance['bootstrap_generation'] ?? 0) + 1,
                'sync_state' => GDPSyncState::IDLE->value,
                'blocked' => null,
                'source_reset' => (int) $sequences['source_sequence'],
                'bootstrap_preflight' => null,
            ]);
            GDPStore::removeTree(GDPStore::path('staging', $instanceId));
            GDPAuditLogger::log('snapshot.activated', ['instance' => $instanceId, 'snapshot_id' => $snapshotId, 'hub_sequence' => (int) $manifest['hub_sequence_at_snapshot'], 'tables' => count($tables)]);

            return ['snapshot_id' => $snapshotId, 'hub_sequence' => (int) $manifest['hub_sequence_at_snapshot'], 'tables' => count($tables)];
        });
    }
}
