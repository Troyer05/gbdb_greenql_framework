<?php

declare(strict_types=1);

/** Bounded buckets, shared by all PHP workers without a daemon or cron job. */
final class GBDBRateLimiter {
    public static function allow(string $purpose, string $identity, int $limit, int $seconds) :bool {
        $directory = GBDBRuntime::lockDirectory() . '/limits';

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('GBDB-Anmeldeschutz ist nicht beschreibbar.');
        }

        $identityHash = hash('sha256', $identity);
        // At most 256 files per purpose, and 1024 live identities per bucket.
        $file = $directory . '/' . substr(hash('sha256', $purpose), 0, 16) . '-' . substr($identityHash, 0, 2) . '.json';
        $handle = fopen($file, 'c+');

        if ($handle === false || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('GBDB-Anmeldeschutz konnte nicht gesperrt werden.');
        }

        try {
            $entries = json_decode((string) stream_get_contents($handle), true);
            $entries = is_array($entries) ? $entries : [];

            foreach ($entries as $key => $entry) {
                if ((int) ($entry['until'] ?? 0) <= time()) {
                    unset($entries[$key]);
                }
            }

            if (!isset($entries[$identityHash]) && count($entries) >= 1024) {
                return false;
            }

            $entry = $entries[$identityHash] ?? ['count' => 0, 'until' => time() + max(1, $seconds)];
            $entry['count']++;
            $entries[$identityHash] = $entry;
            rewind($handle);
            ftruncate($handle, 0);

            if (!GBDBRuntime::writeAll($handle, json_encode($entries, JSON_THROW_ON_ERROR)) || !fflush($handle)) {
                throw new RuntimeException('GBDB-Anmeldeschutz konnte nicht gespeichert werden.');
            }

            return $entry['count'] <= max(1, $limit);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
