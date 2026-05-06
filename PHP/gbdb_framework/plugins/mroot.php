<?php

/**
 * @author Markus Müller
 *
 * mRoot Anbindung
 * API Doku:
 */

class mRootLicense {
    private const STORE = "json/mRoot/license.json";

    /**
     * Verarbeitet die Funktion fetch.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    private static function fetch(array $data): array {
        $resp = Http::post(Vars::mRoot_url(), $data);

        if ($resp === false || $resp === "") {
            return [
                "ok" => false,
                "status" => 0,
                "msg" => "Keine Antwort vom Lizenzserver"
            ];
        }

        if (is_array($resp)) {
            return $resp;
        }

        $json = json_decode($resp, true);

        if (!is_array($json)) {
            return [
                "ok" => false,
                "status" => 0,
                "msg" => "Ungültige Antwort vom Lizenzserver",
                "raw" => $resp
            ];
        }

        return $json;
    }

    /**
     * Verarbeitet die Funktion store path.
     * @return string Rückgabewert.
     */
    private static function storePath(): string {
        return dirname(__DIR__) . "/" . self::STORE;
    }

    /**
     * Verarbeitet die Funktion ensure store.
     * @return void Rückgabewert.
     */
    private static function ensureStore(): void {
        $file = self::storePath();
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (!file_exists($file)) {
            @file_put_contents($file, json_encode([
                "lizenz" => "",
                "kid" => ""
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
    }

    /**
     * Verarbeitet die Funktion license data.
     * @return array Rückgabewert.
     */
    private static function licenseData(): array {
        self::ensureStore();

        $raw = file_get_contents(self::storePath());

        if ($raw === false || trim($raw) === "") {
            return [];
        }

        $json = json_decode($raw, true);

        if (!is_array($json)) {
            return [];
        }

        return $json;
    }

    /**
     * Verarbeitet die Funktion save license.
     * @param array $data Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function saveLicense(array $data): bool {
        self::ensureStore();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        return file_put_contents(self::storePath(), $json, LOCK_EX) !== false;
    }

    /**
     * Verarbeitet die Funktion current url.
     * @return string Rückgabewert.
     */
    private static function currentUrl(): string {
        return $_SERVER["HTTP_HOST"] ?? "localhost";
    }

    /**
     * Verarbeitet die Funktion valid api response.
     * @param array $resp Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function validApiResponse(array $resp): bool {
        if (empty($resp["ok"])) {
            return false;
        }

        if (($resp["status"] ?? 0) != 200) {
            return false;
        }

        if (!isset($resp["data"]) || !is_array($resp["data"])) {
            return false;
        }

        if (empty($resp["data"]["ok"])) {
            return false;
        }

        return true;
    }

    /**
     * Verarbeitet die Funktion test license.
     * @return void Rückgabewert.
     */
    public static function testLicense(): void {
        if (Vars::this_file() == Vars::mRoot_license_form()) {
            return;
        }

        $license = self::licenseData();

        if (
            empty($license) ||
            empty($license["lizenz"]) ||
            empty($license["kid"])
        ) {
            Ref::to(Vars::mRoot_license_form() . "?err=true");
            return;
        }

        $resp = self::fetch([
            "auth" => Vars::mRoot_auth(),
            "do" => "check_license",
            "pid" => Vars::mRoot_pid(),
            "key" => $license["lizenz"],
            "kid" => $license["kid"],
            "url" => self::currentUrl()
        ]);

        if (self::validApiResponse($resp)) {
            return;
        }

        Ref::to(Vars::mRoot_license_form() . "?err=true");
    }

    /**
     * Verarbeitet die Funktion set license.
     * @param string $key Übergabewert.
     * @param string $kid Übergabewert.
     * @return void Rückgabewert.
     */
    public static function setLicense(string $key, string $kid): void {
        self::saveLicense([
            "lizenz" => trim($key),
            "kid" => trim($kid)
        ]);
    }

    /**
     * Verarbeitet die Funktion check license.
     * @param string $key Übergabewert.
     * @param string $kid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function checkLicense(string $key, string $kid): array {
        $key = trim($key);
        $kid = trim($kid);

        if ($key === "" || $kid === "") {
            return [
                "ok" => false,
                "msg" => "Lizenzschlüssel oder Kunden-ID fehlt"
            ];
        }

        $resp = self::fetch([
            "auth" => Vars::mRoot_auth(),
            "do" => "check_license",
            "pid" => Vars::mRoot_pid(),
            "key" => $key,
            "kid" => $kid,
            "url" => self::currentUrl()
        ]);

        return [
            "ok" => self::validApiResponse($resp),
            "status" => $resp["status"] ?? 0,
            "data" => $resp["data"] ?? [],
            "msg" => $resp["msg"] ?? "",
            "raw" => $resp
        ];
    }
}

class mRootUpdate {
    private const UPDATE_DIR = "update";
    private const TMP_DIR = "update/tmp";
    private const BACKUP_DIR = "update/backups";
    private const CACHE_FILE = "update/check_cache.json";
    private const CACHE_TTL = 1800;

    private const PRESERVE_PATHS = [
        "gbdb_framework/GBDB_GQL/.DB",
        "gbdb_framework/.config/.framework.env.php",
        "gbdb_framework/autoloader.php",
        "update",
        "uploads"
    ];

    /**
     * Verarbeitet die Funktion root.
     * @return string Rückgabewert.
     */
    private static function root(): string {
        return dirname(__DIR__, 2);
    }

    /**
     * Verarbeitet die Funktion server.
     * @return string Rückgabewert.
     */
    private static function server(): string {
        return Vars::mRoot_url();
    }

    /**
     * Verarbeitet Auth-Aktionen.
     * @return string Rückgabewert.
     */
    private static function auth(): string {
        return Vars::mRoot_auth();
    }

    /**
     * Verarbeitet die Funktion normalize version.
     * @param string $version Übergabewert.
     * @return string Rückgabewert.
     */
    private static function normalizeVersion(string $version): string {
        $version = strtolower(trim($version));
        $version = preg_replace('/^[^0-9]*/', '', $version) ?? $version;
        $version = preg_replace('/[^0-9.].*$/', '', $version) ?? $version;
        $version = trim($version, ". ");

        return $version === "" ? "0" : $version;
    }

    /**
     * Verarbeitet die Funktion newer.
     * @param string $remote Übergabewert.
     * @param string $local Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function newer(string $remote, string $local): bool {
        return version_compare(self::normalizeVersion($remote), self::normalizeVersion($local), ">");
    }

    /**
     * Verarbeitet die Funktion value.
     * @param array $data Übergabewert.
     * @param array $paths Übergabewert.
     * @param mixed $default Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function value(array $data, array $paths, mixed $default = ""): mixed {
        foreach ($paths as $path) {
            $tmp = $data;
            $ok = true;

            foreach ($path as $key) {
                if (!is_array($tmp) || !array_key_exists($key, $tmp)) {
                    $ok = false;
                    break;
                }

                $tmp = $tmp[$key];
            }

            if ($ok && $tmp !== null && $tmp !== "") {
                return $tmp;
            }
        }

        return $default;
    }

    /**
     * Verarbeitet die Funktion clean response.
     * @param array $resp Übergabewert.
     * @return array Rückgabewert.
     */
    private static function cleanResponse(array $resp): array {
        if (!isset($resp["data"]) || !is_array($resp["data"])) {
            $resp["data"] = [];
        }

        if (!isset($resp["data"]["check"]) || !is_array($resp["data"]["check"])) {
            $resp["data"]["check"] = [];
        }

        if (!isset($resp["data"]["info"]) || !is_array($resp["data"]["info"])) {
            $resp["data"]["info"] = [];
        }

        $latest = (string)self::value($resp, [
            ["data", "check", "latest_version"],
            ["data", "latest_version"],
            ["data", "version"],
            ["latest_version"],
            ["version"]
        ]);

        $url = (string)self::value($resp, [
            ["data", "check", "url"],
            ["data", "url"],
            ["url"],
            ["download"],
            ["zip"]
        ]);

        $changelog = (string)self::value($resp, [
            ["data", "info", "changelog"],
            ["data", "changelog"],
            ["changelog"]
        ]);

        $current = Vars::app_version();
        $update = self::value($resp, [["data", "check", "update"], ["data", "update"], ["update"]], null);

        if ($update === null) {
            $update = $latest !== "" && self::newer($latest, $current);
        }

        $resp["data"]["check"]["current_version"] = $current;
        $resp["data"]["check"]["latest_version"] = $latest;
        $resp["data"]["check"]["url"] = $url;
        $resp["data"]["check"]["update"] = (bool)$update;
        $resp["data"]["info"]["changelog"] = $changelog;

        return $resp;
    }

    /**
     * Verarbeitet die Funktion fetch remote.
     * @return array Rückgabewert.
     */
    private static function fetchRemote(): array {
        $resp = Http::post(self::server(), [
            "auth" => self::auth(),
            "do" => "check_update",
            "pid" => Vars::mRoot_pid(),
            "version" => Vars::app_version()
        ]);

        if ($resp === false || $resp === "") {
            return ["ok" => false, "status" => 0, "msg" => "Update-Server nicht erreichbar", "data" => []];
        }

        if (is_string($resp)) {
            $json = json_decode($resp, true);

            if (!is_array($json)) {
                return ["ok" => false, "status" => 0, "msg" => "Ungültige Antwort vom Update-Server", "raw" => $resp, "data" => []];
            }

            return self::cleanResponse($json);
        }

        if (is_array($resp)) {
            return self::cleanResponse($resp);
        }

        return ["ok" => false, "status" => 0, "msg" => "Unbekannte Antwort vom Update-Server", "data" => []];
    }

    /**
     * Verarbeitet die Funktion cache path.
     * @return string Rückgabewert.
     */
    private static function cachePath(): string {
        return self::root() . "/" . self::CACHE_FILE;
    }

    /**
     * Verarbeitet die Funktion read cache.
     * @param int $ttl Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readCache(int $ttl = self::CACHE_TTL): array {
        $file = self::cachePath();

        if (!is_file($file) || $ttl <= 0) return [];

        $json = json_decode((string)@file_get_contents($file), true);

        if (!is_array($json)) return [];

        $time = (int)($json["time"] ?? 0);
        $resp = $json["resp"] ?? [];

        if ($time <= 0 || (time() - $time) > $ttl || !is_array($resp)) return [];

        $resp["_cache"] = true;
        $resp["_checked_at"] = $time;

        return self::cleanResponse($resp);
    }

    /**
     * Verarbeitet die Funktion write cache.
     * @param array $resp Übergabewert.
     * @return void Rückgabewert.
     */
    private static function writeCache(array $resp): void {
        $path = self::cachePath();
        self::ensureDir(dirname($path));

        unset($resp["_cache"], $resp["_checked_at"]);

        @file_put_contents($path, json_encode([
            "time" => time(),
            "resp" => $resp
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Verarbeitet die Funktion fetch.
     * @param bool $force Übergabewert.
     * @return array Rückgabewert.
     */
    private static function fetch(bool $force = false): array {
        if (!$force) {
            $cached = self::readCache();

            if (!empty($cached)) return $cached;
        }

        $resp = self::fetchRemote();
        $resp["_cache"] = false;
        $resp["_checked_at"] = time();
        self::writeCache($resp);

        return $resp;
    }

    /**
     * Verarbeitet die Funktion check.
     * @param bool $force Übergabewert.
     * @return array Rückgabewert.
     */
    public static function check(bool $force = false): array {
        return self::fetch($force);
    }

    /**
     * Verarbeitet die Funktion need update.
     * @param bool $force Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function needUpdate(bool $force = false): bool {
        $resp = self::fetch($force);
        return !empty($resp["ok"]) && !empty($resp["data"]["check"]["update"]);
    }

    /**
     * Verarbeitet die Funktion latest version.
     * @param bool $force Übergabewert.
     * @return string Rückgabewert.
     */
    public static function latestVersion(bool $force = false): string {
        $resp = self::fetch($force);
        return (string)($resp["data"]["check"]["latest_version"] ?? "");
    }

    /**
     * Verarbeitet die Funktion changelog.
     * @param bool $force Übergabewert.
     * @return string Rückgabewert.
     */
    public static function changelog(bool $force = false): string {
        $resp = self::fetch($force);
        return (string)($resp["data"]["info"]["changelog"] ?? "");
    }

    /**
     * Verarbeitet die Funktion update url.
     * @param bool $force Übergabewert.
     * @return string Rückgabewert.
     */
    public static function updateUrl(bool $force = false): string {
        $resp = self::fetch($force);
        return (string)($resp["data"]["check"]["url"] ?? "");
    }

    /**
     * Verarbeitet die Funktion ensure dir.
     * @param string $dir Übergabewert.
     * @return void Rückgabewert.
     */
    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
    }

    /**
     * Verarbeitet die Funktion rrmdir.
     * @param string $dir Übergabewert.
     * @return void Rückgabewert.
     */
    private static function rrmdir(string $dir): void {
        if (!is_dir($dir)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($dir);
    }

    /**
     * Verarbeitet die Funktion normalize path.
     * @param string $path Übergabewert.
     * @return string Rückgabewert.
     */
    private static function normalizePath(string $path): string {
        return str_replace("\\", "/", trim($path, "/"));
    }

    /**
     * Verarbeitet die Funktion is preserved.
     * @param string $relative Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function isPreserved(string $relative): bool {
        $relative = self::normalizePath($relative);

        foreach (self::PRESERVE_PATHS as $path) {
            $path = self::normalizePath($path);
            if ($relative === $path || str_starts_with($relative, $path . "/")) return true;
        }

        return false;
    }

    /**
     * Verarbeitet die Funktion is ignored for backup.
     * @param string $relative Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function isIgnoredForBackup(string $relative): bool {
        $relative = self::normalizePath($relative);

        foreach ([self::TMP_DIR, self::BACKUP_DIR] as $path) {
            $path = self::normalizePath($path);
            if ($relative === $path || str_starts_with($relative, $path . "/")) return true;
        }

        return false;
    }

    /**
     * Verarbeitet die Funktion download.
     * @param string $url Übergabewert.
     * @param string $target Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function download(string $url, string $target): bool {
        if ($url === "") return false;

        $data = @file_get_contents($url);

        if ($data === false || $data === "") return false;

        return @file_put_contents($target, $data, LOCK_EX) !== false;
    }

    /**
     * Verarbeitet die Funktion extract zip.
     * @param string $zipFile Übergabewert.
     * @param string $targetDir Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function extractZip(string $zipFile, string $targetDir): bool {
        $zip = new ZipArchive();

        if ($zip->open($zipFile) !== true) return false;

        $ok = $zip->extractTo($targetDir);
        $zip->close();

        return $ok;
    }

    /**
     * Verarbeitet die Funktion backup.
     * @return string|false Rückgabewert.
     */
    private static function backup(): string|false {
        $root = self::root();
        $backupDir = $root . "/" . self::BACKUP_DIR;
        $backupFile = $backupDir . "/backup_" . date("Ymd_His") . ".zip";
        $zip = new ZipArchive();

        self::ensureDir($backupDir);

        if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $relative = self::normalizePath(substr($path, strlen($root) + 1));

            if ($relative === "" || self::isIgnoredForBackup($relative)) continue;

            $file->isDir() ? $zip->addEmptyDir($relative) : $zip->addFile($path, $relative);
        }

        $zip->close();

        return is_file($backupFile) ? $backupFile : false;
    }

    /**
     * Verarbeitet die Funktion detect release root.
     * @param string $extractDir Übergabewert.
     * @return string Rückgabewert.
     */
    private static function detectReleaseRoot(string $extractDir): string {
        $items = array_values(array_filter(scandir($extractDir), fn($item) => $item !== "." && $item !== ".." && is_dir($extractDir . "/" . $item)));
        $files = array_values(array_filter(scandir($extractDir), fn($item) => $item !== "." && $item !== ".." && is_file($extractDir . "/" . $item)));

        if (count($items) === 1 && count($files) === 0) return $extractDir . "/" . $items[0];

        return $extractDir;
    }

    /**
     * Verarbeitet die Funktion copy release.
     * @param string $src Übergabewert.
     * @param string $dst Übergabewert.
     * @param array & $stats Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function copyRelease(string $src, string $dst, array &$stats = []): bool {
        $stats = ["copied" => 0, "skipped" => 0, "dirs" => 0, "errors" => []];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $srcPath = $file->getPathname();
            $relative = self::normalizePath(substr($srcPath, strlen($src) + 1));

            if ($relative === "") continue;
            if (self::isPreserved($relative)) {
                $stats["skipped"]++;
                continue;
            }

            $target = $dst . "/" . $relative;

            if ($file->isDir()) {
                self::ensureDir($target);
                $stats["dirs"]++;
                continue;
            }

            self::ensureDir(dirname($target));

            if (!@copy($srcPath, $target)) {
                $stats["errors"][] = $relative;
                return false;
            }

            @chmod($target, 0664);
            $stats["copied"]++;
        }

        return true;
    }

    /**
     * Verarbeitet die Funktion update local version.
     * @param string $version Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function updateLocalVersion(string $version): bool {
        $version = trim($version);

        if ($version === "") return true;

        $file = dirname(__DIR__) . "/.config/.framework.env.php";

        if (!is_file($file) || !is_writable($file)) return false;

        $php = (string)file_get_contents($file);
        $safe = addslashes($version);

        $new = preg_replace(
            '/public\s+static\s+function\s+app_version\s*\(\s*\)\s*\{\s*return\s+["\'][^"\']*["\']\s*;\s*\}/is',
            "public static function app_version() \n    {\n        return \"" . $safe . "\";\n    }",
            $php,
            1,
            $count
        );

        if ($new === null || $count < 1) return false;

        return file_put_contents($file, $new, LOCK_EX) !== false;
    }

    /**
     * Verarbeitet die Funktion refresh update cache.
     * @param array $resp Übergabewert.
     * @param string $latest Übergabewert.
     * @return void Rückgabewert.
     */
    private static function refreshUpdateCache(array $resp, string $latest): void {
        if ($latest === "") return;

        $resp["data"]["check"]["update"] = false;
        $resp["data"]["check"]["current_version"] = $latest;
        self::writeCache($resp);
    }

    /**
     * Verarbeitet die Funktion find schema file.
     * @param string $releaseRoot Übergabewert.
     * @return string Rückgabewert.
     */
    private static function findSchemaFile(string $releaseRoot): string {
        foreach ([
            $releaseRoot . "/PHP/gbdb_framework/json/schema.json",
            $releaseRoot . "/gbdb_framework/json/schema.json",
            $releaseRoot . "/update/schema.json",
            $releaseRoot . "/assets/DB/schema.json",
            $releaseRoot . "/assets/DB/GBDB/schema.json"
        ] as $file) {
            if (is_file($file)) return $file;
        }

        return "";
    }

    /**
     * Verarbeitet die Funktion migrate g b d b schema.
     * @param string $releaseRoot Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function migrateGBDBSchema(string $releaseRoot): bool {
        $schemaFile = self::findSchemaFile($releaseRoot);

        if ($schemaFile === "") return true;

        $schema = json_decode((string)file_get_contents($schemaFile), true);

        if (!is_array($schema)) return false;

        foreach ($schema as $db => $tables) {
            if (!is_array($tables)) continue;
            if (!in_array($db, GBDB::listDBs(), true)) GBDB::createDatabase($db);

            foreach ($tables as $table => $cols) {
                if (!is_array($cols)) continue;

                if (!in_array($table, GBDB::listTables($db), true)) {
                    GBDB::createTable($db, $table, array_keys($cols));
                }

                foreach ($cols as $col => $default) {
                    GBDB::addColumn($db, $table, (string)$col, $default);
                }

                GBDB::compactTable($db, $table);
            }
        }

        return true;
    }

    /**
     * Verarbeitet die Funktion update.
     * @param bool $force Übergabewert.
     * @return array Rückgabewert.
     */
    public static function update(bool $force = true): array {
        $resp = self::fetch($force);

        if (empty($resp["ok"])) {
            return ["ok" => false, "msg" => $resp["msg"] ?? "Update-Check fehlgeschlagen"];
        }

        $latest = (string)($resp["data"]["check"]["latest_version"] ?? "");
        $url = (string)($resp["data"]["check"]["url"] ?? "");
        $hasUpdate = !empty($resp["data"]["check"]["update"]);

        if (!$hasUpdate && $latest !== "") {
            $hasUpdate = self::newer($latest, Vars::app_version());
        }

        if (!$hasUpdate) {
            return ["ok" => true, "msg" => "Kein Update verfügbar", "version" => Vars::app_version(), "latest" => $latest];
        }

        if ($url === "") {
            return ["ok" => false, "msg" => "Keine Update-URL vorhanden"];
        }

        $root = self::root();
        $tmp = $root . "/" . self::TMP_DIR;
        $zipFile = $tmp . "/update.zip";
        $extractDir = $tmp . "/extract";

        self::ensureDir($tmp);
        if (is_dir($extractDir)) self::rrmdir($extractDir);
        self::ensureDir($extractDir);

        $old = Vars::app_version();
        $backup = self::backup();

        if ($backup === false) return ["ok" => false, "msg" => "Backup konnte nicht erstellt werden"];
        if (!self::download($url, $zipFile)) return ["ok" => false, "msg" => "Update konnte nicht heruntergeladen werden", "backup" => $backup];
        if (!self::extractZip($zipFile, $extractDir)) return ["ok" => false, "msg" => "Update-ZIP konnte nicht entpackt werden", "backup" => $backup];

        $releaseRoot = self::detectReleaseRoot($extractDir);

        if (!self::migrateGBDBSchema($releaseRoot)) {
            return ["ok" => false, "msg" => "GBDB-Schema konnte nicht migriert werden", "backup" => $backup];
        }

        $copyStats = [];

        if (!self::copyRelease($releaseRoot, $root, $copyStats)) {
            return [
                "ok" => false,
                "msg" => "Update-Dateien konnten nicht vollständig kopiert werden",
                "backup" => $backup,
                "copied" => (string)($copyStats["copied"] ?? 0),
                "error_file" => (string)($copyStats["errors"][0] ?? "")
            ];
        }

        $versionUpdated = self::updateLocalVersion($latest);
        self::refreshUpdateCache($resp, $latest);

        clearstatcache(true);
        if (function_exists("opcache_reset")) @opcache_reset();

        return [
            "ok" => true,
            "msg" => $versionUpdated ? "Update erfolgreich installiert" : "Update installiert, aber app_version konnte nicht aktualisiert werden",
            "version_old" => $old,
            "version_new" => $latest,
            "backup" => $backup,
            "copied" => (string)($copyStats["copied"] ?? 0),
            "skipped_protected" => (string)($copyStats["skipped"] ?? 0),
            "version_config_updated" => $versionUpdated ? "yes" : "no"
        ];
    }
}
