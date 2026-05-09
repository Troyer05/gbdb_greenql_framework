<?php

/**
 * @author Markus Müller
 *
 * helper class for srv modules
 * log path: gbdb_framework/.logs/srv/
 */
class SRVJob {
    public static string $logFile = "srv_job.log";
    public static array $params = [];

    private static function logDir(): string {
        return dirname(__DIR__, 2) . "/.logs/srv";
    }

    private static function logFile(): string {
        $filename = basename(self::$logFile);

        if ($filename == "") {
            $filename = "srv_job.log";
        }

        return self::logDir() . "/" . $filename;
    }

    private static function ensureLogDir(): void {
        $dir = self::logDir();

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private static function cleanLogMessage(string $log): string {
        return trim(str_replace(["\r", "\n"], " ", $log));
    }

    /**
     * sets active log file
     *
     * @param string $filename
     * @return void
     */
    public static function setLogfile(string $filename): void {
        $filename = trim($filename);

        if ($filename == "") {
            $filename = "srv_job.log";
        }

        if (!str_ends_with($filename, ".log")) {
            $filename .= ".log";
        }

        self::$logFile = basename($filename);
    }

    /**
     * writes a log entry
     *
     * @param string $log
     * @return void
     */
    public static function log(string $log): void {
        self::ensureLogDir();

        $txt = "[" . date("Y-m-d H:i:s") . "] " . self::cleanLogMessage($log) . "\n";

        file_put_contents(self::logFile(), $txt, FILE_APPEND | LOCK_EX);
    }

    /**
     * sets job parameters
     *
     * @param array $params
     * @return void
     */
    public static function setParams(array $params): void {
        self::$params = $params;
    }

    /**
     * returns all job parameters
     *
     * @return array
     */
    public static function getParams(): array {
        return self::$params;
    }

    /**
     * checks if parameter exists
     *
     * @param string $param
     * @return bool
     */
    public static function hasParam(string $param): bool {
        return array_key_exists($param, self::$params);
    }

    /**
     * returns parameter value
     *
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public static function getParam(string $param, mixed $default = null): mixed {
        if (!array_key_exists($param, self::$params)) {
            return $default;
        }

        return self::$params[$param];
    }

    /**
     * returns string parameter value
     *
     * @param string $param
     * @param string $default
     * @return string
     */
    public static function getString(string $param, string $default = ""): string {
        return trim((string)self::getParam($param, $default));
    }

    /**
     * returns int parameter value
     *
     * @param string $param
     * @param int $default
     * @return int
     */
    public static function getInt(string $param, int $default = 0): int {
        return (int)self::getParam($param, $default);
    }

    /**
     * returns bool parameter value
     *
     * @param string $param
     * @param bool $default
     * @return bool
     */
    public static function getBool(string $param, bool $default = false): bool {
        if (!array_key_exists($param, self::$params)) {
            return $default;
        }

        if (is_bool(self::$params[$param])) {
            return self::$params[$param];
        }

        $value = strtolower(trim((string)self::$params[$param]));

        if (in_array($value, ["1", "true", "yes", "on"], true)) {
            return true;
        }

        if (in_array($value, ["0", "false", "no", "off"], true)) {
            return false;
        }

        return $default;
    }

    /**
     * returns ok job response
     *
     * @param string $message
     * @param array $data
     * @return array
     */
    public static function returnOk(string $message, array $data = []): array {
        self::log("OK: " . $message);

        return [
            "ok" => true,
            "message" => $message,
            "data" => $data
        ];
    }

    /**
     * returns error job response
     *
     * @param string $message
     * @param array $data
     * @return array
     */
    public static function returnError(string $message, array $data = []): array {
        self::log("ERROR: " . $message);

        return [
            "ok" => false,
            "message" => $message,
            "data" => $data
        ];
    }
}

?>