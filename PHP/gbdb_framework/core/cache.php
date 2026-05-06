<?php

class Cache {
    /**
     * Stellt sicher, dass die Session-Cache-Struktur existiert.
     */
    protected static function ensureSession(): void {
        if (!isset($_SESSION['cache']) || !is_array($_SESSION['cache'])) {
            $_SESSION['cache'] = [
                "updates" => [],
                "data"    => []
            ];
        }

        if (!isset($_SESSION['cache']['updates'])) {
            $_SESSION['cache']['updates'] = [];
        }

        if (!isset($_SESSION['cache']['data'])) {
            $_SESSION['cache']['data'] = [];
        }
    }

    /**
     * Lädt Daten aus dem Cache, bei Änderung automatisch neu.
     */
    public static function load(string $db, string $table, mixed $cache): array {
        self::ensureSession();

        // "cache" muss ein Array von Meta-Infos sein
        if (!is_array($cache)) {
            return [];
        }

        // Suche den Update-Wert
        $currentUpdate = null;

        foreach ($cache as $entry) {
            if (
                isset($entry["table"], $entry["update"]) &&
                $entry["table"] === $table
            ) {
                $currentUpdate = $entry["update"];
                break;
            }
        }

        // Kein Update-Wert → kein Caching möglich
        if ($currentUpdate === null) {
            return [];
        }

        // Schlüssel für DB + Tabelle (verhindert Konflikte)
        $key = $db . "::" . $table;

        // Prüfe, ob Neu-Laden nötig ist
        $needsUpdate =
            !isset($_SESSION["cache"]["updates"][$key]) ||
            $_SESSION["cache"]["updates"][$key] !== $currentUpdate;

        if ($needsUpdate) {
            // Daten aus DB holen
            $data = GBDB::getData($db, $table);

            if (!is_array($data)) {
                // Fehler bei GBDB – wir speichern NICHT den Fehler
                return [];
            }

            $_SESSION["cache"]["data"][$key]    = $data;
            $_SESSION["cache"]["updates"][$key] = $currentUpdate;
        }

        return $_SESSION["cache"]["data"][$key];
    }

    /**
     * Erzwingt Cache-Aktualisierung für eine Tabelle
     * (z. B. nach Änderungen in GBDB)
     */
    public static function update(string $db, string $table): void {
        // Verbesserung: random_bytes statt uniqid (kryptographisch sicher)
        $newUpdate = bin2hex(random_bytes(8));

        if (!in_array($db, GBDB::listDBs(), true)) {
            GBDB::createDatabase($db);
        }

        if (!in_array("cache", GBDB::listTables($db), true)) {
            GBDB::createTable($db, "cache", ["table", "update"]);
        }

        $row = GBDB::getData($db, "cache", true, "table", $table);

        if (is_array($row) && !empty($row)) {
            GBDB::editData($db, "cache", "table", $table, [
                "update" => $newUpdate
            ]);
            return;
        }

        GBDB::insertData($db, "cache", [
            "table" => $table,
            "update" => $newUpdate
        ]);
    }

    /**
     * Löscht einen Cache-Eintrag für eine Tabelle
     */
    public static function clear(string $table, ?string $db = null): void {
        self::ensureSession();

        // Wenn DB nicht angegeben → alle DBs durchsuchen
        foreach ($_SESSION["cache"]["data"] as $key => $value) {
            if (str_ends_with($key, "::" . $table)) {
                unset($_SESSION["cache"]["data"][$key]);
                unset($_SESSION["cache"]["updates"][$key]);
            }
        }
    }

    /**
     * Kompletten Cache löschen (Session-Ebene)
     */
    public static function flush(): void {
        unset($_SESSION["cache"]);
    }

    /**
     * Prüft, ob Tabelle im Cache existiert
     */
    public static function exists(string $table, ?string $db = null): bool {
        self::ensureSession();

        foreach ($_SESSION["cache"]["data"] as $key => $value) {
            if (str_ends_with($key, "::" . $table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt alle Caches zurück
     */
    public static function all(): array {
        self::ensureSession();
        return $_SESSION["cache"]["data"];
    }
}
