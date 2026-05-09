<?php
declare(strict_types=1);

require_once __DIR__ . "/gbdb_locking.trait.php";
require_once __DIR__ . "/gbdb_recovery.trait.php";
require_once __DIR__ . "/gbdb_mvcc.trait.php";
require_once __DIR__ . "/gbdb_transaction.trait.php";
require_once __DIR__ . "/gbdb_instance_schema.trait.php";
require_once __DIR__ . "/gbdb_index.trait.php";
require_once __DIR__ . "/gbdb_storage.trait.php";
require_once __DIR__ . "/gbdb_crud.trait.php";
require_once __DIR__ . "/gbdb_maintenance.trait.php";
require_once __DIR__ . "/gbdb_advanced.trait.php";
require_once __DIR__ . "/gbdb_query.trait.php";
require_once __DIR__ . "/gbdb_relations_ids_streaming_partition.trait.php";
require_once __DIR__ . "/gbdb_cluster_backup.trait.php";
require_once __DIR__ . "/gbdb_admin_ops.trait.php";
require_once __DIR__ . "/gbdb_jobs_security_media_social.trait.php";
require_once __DIR__ . "/gbdb_enterprise_ops.trait.php";
require_once __DIR__ . "/gbdb_public_v103.trait.php";

/**
 * Zentrale GBDB-Fassade und aktuelle instanzfähige GBDB-Engine.
 *
 * Ab dieser Struktur gibt es für Entwickler nur noch GBDB::.
 * Frühere Versionsklassen werden bewusst nicht mehr registriert.
 */
class GBDB {
    private const SCHEMA_FILE = "GBDB_GQL/.DB/.system/schema.json";

    private static string $instance = "default";
    private static bool $txActive = false;
    private static bool $txCommitting = false;
    private static string $txId = "";
    private static array $txOps = [];
    private static int $txStartedAt = 0;
    private static int $txTimeout = 30;
    private static array $txSavepoints = [];
    private static array $txSnapshots = [];

    private static int $queryTimeout = 10;
    private static int $queryMemoryLimit = 33554432;
    private static int $queryMaxJoinSize = 250000;
    private static bool $queryBlockFullScan = false;
    private static array $preparedQueries = [];
    private static array $queryCache = [];

    use GBDB_LockingTrait;
    use GBDB_RecoveryTrait;
    use GBDB_MVCCTrait;
    use GBDB_TransactionTrait;
    use GBDB_InstanceSchemaTrait;
    use GBDB_IndexTrait;
    use GBDB_StorageTrait;
    use GBDB_CrudTrait;
    use GBDB_MaintenanceTrait;
    use GBDB_AdvancedTrait;
    use GBDB_QueryTrait;
    use GBDB_RelationsIdsStreamingPartitionTrait;
    use GBDB_ClusterBackupTrait;
    use GBDB_AdminOpsTrait;
    use GBDB_JobsSecurityMediaSocialTrait;
    use GBDB_EnterpriseOpsTrait;

    use GBDB_PublicV103Trait {
        GBDB_PublicV103Trait::exists insteadof GBDB_AdvancedTrait;
        GBDB_PublicV103Trait::edit insteadof GBDB_AdvancedTrait;
        GBDB_PublicV103Trait::delete insteadof GBDB_AdvancedTrait;
        GBDB_PublicV103Trait::fullTextSearch insteadof GBDB_AdvancedTrait;
        GBDB_PublicV103Trait::get insteadof GBDB_AdvancedTrait;
    }

    /**
     * handles update.
     *
     * @param null|string $pathOfOldDB value.
     *
     * @return array result.
     */
    public static function update(?string $pathOfOldDB = null): array {
        $root = self::rootPath();
        $base = $root . "/GBDB_GQL/.DB";
        $oldDb = self::normalizeMigrationSource($pathOfOldDB, $root);
        $report = ["ok" => true, "source" => $oldDb, "target" => $base, "created" => [], "copied" => [], "skipped" => [], "errors" => []];

        foreach ([".system", ".storage", ".storage/default", ".scripts", ".temp", ".temp/locks", ".backups", ".media"] as $dir) {
            $path = $base . "/" . $dir;

            if (!is_dir($path)) {
                if (@mkdir($path, 0777, true) || is_dir($path)) $report["created"][] = $path; else {
                    $report["ok"] = false;
                    $report["errors"][] = "Folder could not be created: " . $path;
                }

            }

        }

        $htaccess = $base . "/.htaccess"; if (!is_file($htaccess)) { @file_put_contents($htaccess, "Require all denied
"); $report["created"][] = $htaccess; }
        self::copyMissingTree($oldDb, $base . "/.storage/default", $report);
        self::copyMissingTree($root . "/scripts/greenql", $base . "/.scripts", $report);
        self::mergeOldSchemas($root, $base . "/.system/schema.json", $report);

        $envFile = $root . "/.config/.greenql.env.php";

        if (!is_file($envFile)) { @file_put_contents($envFile, "<?php

return [
    'api_auth' => '',
];
"); $report["created"][] = $envFile; }

        return $report;
    }

    /**
     * handles migrate.
     *
     * @param null|string $pathOfOldDB value.
     *
     * @return mixed result.
     */
    public static function migrate(?string $pathOfOldDB = null): array { return self::update($pathOfOldDB); }

    private static function normalizeMigrationSource(?string $pathOfOldDB, string $root): string { $path=trim((string)$pathOfOldDB); if ($path==='') return $root . "/assets/DB/GBDB"; $path=str_replace('\\','/',$path); if (!str_starts_with($path,'/')) $path=$root.'/'.ltrim($path,'/'); return rtrim($path,'/'); }

    private static function copyMissingTree(string $from, string $to, array &$report): void {
        if (!is_dir($from)) {
            $report["skipped"][] = "Quelle fehlt: " . $from;

            return;
        }

        if (!is_dir($to)) {
            @mkdir($to, 0777, true);
        }

        $items = scandir($from);

        if (!is_array($items)) return;

        foreach ($items as $item) {
            if ($item === "." || $item === "..") continue;

            $src = $from . "/" . $item;
            $dst = $to . "/" . $item;

            if (is_dir($src)) {
                self::copyMissingTree($src, $dst, $report);
                continue;
            }

            if (is_file($dst)) {
                $report["skipped"][] = "Existiert bereits: " . $dst;
                continue;
            }

            if (@copy($src, $dst)) {
                $report["copied"][] = $dst;
            } else {
                $report["ok"] = false;
                $report["errors"][] = "Datei konnte nicht kopiert werden: " . $src;
            }

        }

    }

    private static function mergeOldSchemas(string $root, string $target, array &$report): void {
        $schema = [];

        if (is_file($target)) {
            $current = json_decode((string)@file_get_contents($target), true);

            if (is_array($current)) $schema = $current;
        }

        foreach ([
            $root . "/json/schema.json",
            $root . "/json/schema_v2.json"
        ] as $file) {
            if (!is_file($file)) continue;

            $data = json_decode((string)@file_get_contents($file), true);

            if (!is_array($data)) continue;

            $schema = array_replace_recursive($schema, $data);
        }

        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0777, true);
        }

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false || @file_put_contents($target, $json) === false) {
            $report["ok"] = false;
            $report["errors"][] = "Schema konnte nicht geschrieben werden: " . $target;

            return;
        }

        $report["copied"][] = $target;
    }

}
