<?php

trait GBDB_IndexTrait {

    /**
     * Erzeugt einen sicheren Namen.
     * @param string $plain Übergabewert.
     * @param string $ns Übergabewert.
     * @return string Rückgabewert.
     */
    private static function nameToken(string $plain, string $ns = "g"): string {
        $plain = (string)$plain;
        $key = (string)Vars::cryptKey();

        $data = $ns . "|" . $plain;
        $raw = hash_hmac("sha256", $data, $key, true);
        $b64 = base64_encode($raw);
        $safe = rtrim(strtr($b64, "+/", "-_"), "=");

        return "gb_" . $safe;
    }


    /**
     * Gibt die globale Instanz-Index-Datei zurück.
     * @return string Rückgabewert.
     */
    private static function instanceIndexFile(): string {
        return Vars::DB_PATH() . self::nameToken("__instance_index__", "meta") . Vars::data_extension();
    }


    /**
     * Gibt die Datenbank-Index-Datei einer Instanz zurück.
     * @param string $instanceToken Übergabewert.
     * @return string Rückgabewert.
     */
    private static function dbIndexFileByInstanceToken(string $instanceToken): string {
        $dir = Vars::DB_PATH() . $instanceToken . "/";
        return $dir . self::nameToken("__db_index__", "meta") . Vars::data_extension();
    }


    /**
     * Gibt die Tabellen-Index-Datei einer Datenbank zurück.
     * @param string $instanceToken Übergabewert.
     * @param string $dbToken Übergabewert.
     * @return string Rückgabewert.
     */
    private static function tableIndexFileByTokens(string $instanceToken, string $dbToken): string {
        $dir = Vars::DB_PATH() . $instanceToken . "/" . $dbToken . "/";
        return $dir . self::nameToken("__table_index__", "meta") . Vars::data_extension();
    }


    /**
     * Liest eine Index-Datei.
     * @param string $file Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readIndex(string $file): array {
        $rows = self::ini($file);

        if (empty($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return [];
        }

        unset($rows[0]);

        $rows = array_values($rows);
        $map = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!isset($row["plain"], $row["token"])) {
                continue;
            }

            $plain = (string)$row["plain"];
            $token = (string)$row["token"];

            if ($plain !== "" && $token !== "") {
                $map[$plain] = $token;
            }
        }

        return $map;
    }


    /**
     * Schreibt eine Index-Datei.
     * @param string $file Übergabewert.
     * @param array $map Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeIndex(string $file, array $map): bool {
        $db = [];

        $db[] = [
            "id" => -1,
            "plain" => "-header-",
            "token" => "-header-"
        ];

        $id = 0;

        foreach ($map as $plain => $token) {
            $db[] = [
                "id" => $id++,
                "plain" => (string)$plain,
                "token" => (string)$token
            ];
        }

        return self::writeTable($file, $db);
    }


    /**
     * Gibt den Token einer Instanz zurück.
     * @param string $instancePlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getInstanceToken(string $instancePlain, bool $ensure = false): ?string {
        $instancePlain = Format::cleanString($instancePlain);

        if ($instancePlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $instancePlain;
        }

        $idxFile = self::instanceIndexFile();
        $map = self::readIndex($idxFile);

        if (isset($map[$instancePlain])) {
            return $map[$instancePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("inst:" . $instancePlain, "inst");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("inst:" . $instancePlain . "#" . $n, "inst");
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$instancePlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }


    /**
     * Gibt den Token einer Datenbank zurück.
     * @param string $dbPlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getDbToken(string $dbPlain, bool $ensure = false): ?string {
        $instancePlain = self::instanceName();
        $dbPlain = Format::cleanString($dbPlain);

        if ($instancePlain === "" || $dbPlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $dbPlain;
        }

        $instanceToken = self::getInstanceToken($instancePlain, $ensure);

        if ($instanceToken === null) {
            return null;
        }

        $idxFile = self::dbIndexFileByInstanceToken($instanceToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            return $map[$dbPlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("db:" . $instancePlain . "|" . $dbPlain, "db");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("db:" . $instancePlain . "|" . $dbPlain . "#" . $n, "db");
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$dbPlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }


    /**
     * Gibt den Token einer Tabelle zurück.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false): ?string {
        $instancePlain = self::instanceName();
        $dbPlain = Format::cleanString($dbPlain);
        $tablePlain = Format::cleanString($tablePlain);

        if ($instancePlain === "" || $dbPlain === "" || $tablePlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $tablePlain;
        }

        $instanceToken = self::getInstanceToken($instancePlain, $ensure);
        $dbToken = self::getDbToken($dbPlain, $ensure);

        if ($instanceToken === null || $dbToken === null) {
            return null;
        }

        $idxFile = self::tableIndexFileByTokens($instanceToken, $dbToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            return $map[$tablePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("tbl:" . $instancePlain . "|" . $dbPlain . "|" . $tablePlain, "tbl");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("tbl:" . $instancePlain . "|" . $dbPlain . "|" . $tablePlain . "#" . $n, "tbl");
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$tablePlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }


    /**
     * Entfernt eine Tabelle aus dem Tabellen-Index.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropTableFromIndex(string $dbPlain, string $tablePlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);
        $dbToken = self::getDbToken($dbPlain, false);

        if ($instanceToken === null || $dbToken === null) {
            return;
        }

        $idxFile = self::tableIndexFileByTokens($instanceToken, $dbToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            unset($map[$tablePlain]);
            self::writeIndex($idxFile, $map);
        }
    }


    /**
     * Entfernt eine Datenbank aus dem Datenbank-Index.
     * @param string $dbPlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropDatabaseFromIndex(string $dbPlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);

        if ($instanceToken === null) {
            return;
        }

        $idxFile = self::dbIndexFileByInstanceToken($instanceToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            unset($map[$dbPlain]);
            self::writeIndex($idxFile, $map);
        }
    }


    /**
     * Entfernt eine Instanz aus dem Instanz-Index.
     * @param string $instancePlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropInstanceFromIndex(string $instancePlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $idxFile = self::instanceIndexFile();
        $map = self::readIndex($idxFile);

        if (isset($map[$instancePlain])) {
            unset($map[$instancePlain]);
            self::writeIndex($idxFile, $map);
        }
    }
}
