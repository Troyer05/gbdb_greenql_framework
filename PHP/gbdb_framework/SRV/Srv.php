<?php

class Srv {
    /**
     * Liefert den aktiven DB-Treiber für optionale GBDB-Instanz-Kontexte.
     * @param array $ctx Übergabewert.
     * @return string Rückgabewert.
     */
    private static function driver(array $ctx = []): string {
        if (function_exists("DB_DRIVER")) {
            return DB_DRIVER($ctx);
        }

        if (!empty($ctx["instance"]) && class_exists("GBDB")) {
            GBDB::setInstance((string)$ctx["instance"]);
            return "GBDB";
        }

        return "GBDB";
    }

    /**
     * Stellt die SRV Tabellen sicher.
     * @param array $ctx Übergabewert.
     * @return void Rückgabewert.
     */
    private static function ensureTables(array $ctx = []): void {
        $driver = self::driver($ctx);

        if (!in_array("main", $driver::listDBs(), true)) {
            $driver::createDatabase("main");
        }

        if (!in_array("srv_jobs", $driver::listTables("main"), true)) {
            $driver::createTable("main", "srv_jobs", [
                "service",
                "action",
                "payload",
                "status",
                "created",
                "started_at",
                "finished_at",
                "error_msg"
            ]);
        }
    }

    /**
     * Normalisiert einen Script-Pfad relativ zum Projekt-Root des Backends.
     * @param string $path Übergabewert.
     * @return string Rückgabewert.
     */
    private static function scriptPath(string $path): string {
        $path = trim($path);
        $path = str_replace("\\", "/", $path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if ($path === "") {
            return "";
        }

        if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return "";
        }

        if (is_file($path)) {
            return $path;
        }

        $root = dirname(__DIR__, 3);
        $candidate = $root . "/" . ltrim($path, "/");

        if (is_file($candidate)) {
            return $candidate;
        }

        return $candidate;
    }

    /**
     * Verarbeitet die Funktion enqueue.
     * @param string $service Übergabewert.
     * @param string $action Übergabewert.
     * @param array $payload Übergabewert.
     * @param array $ctx Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function enqueue(string $service, string $action, array $payload = [], array $ctx = []) {
        self::ensureTables($ctx);
        $driver = self::driver($ctx);

        $job = [
            "service" => $service,
            "action" => $action,
            "payload" => json_encode($payload, JSON_UNESCAPED_UNICODE),
            "status" => "pending",
            "created" => date("Y-m-d H:i:s"),
            "started_at" => "",
            "finished_at" => "",
            "error_msg" => ""
        ];

        return $driver::insertData("main", "srv_jobs", $job);
    }

    /**
     * Verarbeitet die Funktion get jobs.
     * @param array $ctx Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function getJobs(array $ctx = []) {
        self::ensureTables($ctx);
        $driver = self::driver($ctx);
        return $driver::getData("main", "srv_jobs");
    }

    /**
     * Verarbeitet die Funktion get job.
     * @param int $id Übergabewert.
     * @param array $ctx Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function getJob(int $id, array $ctx = []) {
        self::ensureTables($ctx);

        $driver = self::driver($ctx);
        $job = $driver::getData("main", "srv_jobs", true, "id", (string)$id);

        return $job[0] ?? $job;
    }

    /**
     * Führt ein Script aus und gibt das Ergebnis zurück.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        $file = self::scriptPath($path);

        if ($file === "" || !is_file($file)) {
            return [
                "ok" => false,
                "msg" => "Script not found on backend: " . $path
            ];
        }

        if (!is_readable($file)) {
            return [
                "ok" => false,
                "msg" => "Script not readable on backend: " . $path
            ];
        }

        $script = file_get_contents($file);

        if ($script === false) {
            return [
                "ok" => false,
                "msg" => "Script could not be read on backend: " . $path
            ];
        }

        return [
            "ok" => true,
            "path" => $file,
            "result" => DB_QUERY($script, $ctx, $params)
        ];
    }

    /**
     * Verarbeitet Auth-Aktionen.
     * @param string $action Übergabewert.
     * @param array $body Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth(string $action, array $body): array {
        try {
            if ($action == "init") {
                return Auth::initRemote();
            }

            if ($action == "login") {
                return Auth::loginRemote($body["username_or_email"] ?? "", $body["plain_text_password"] ?? "");
            }

            if ($action == "login_2fa") {
                return Auth::login2FaRemote($body["uid"] ?? "", $body["code"] ?? "");
            }

            if ($action == "token") {
                return Auth::authByToken($body["jwt"] ?? "");
            }

            if ($action == "me") {
                $jwt = $body["jwt"] ?? "";
                $auth = $jwt !== "" ? Auth::authByToken($jwt) : [];

                if (empty($auth["ok"])) {
                    return ["ok" => false, "msg" => "Not authenticated"];
                }

                return ["ok" => true, "user" => Auth::user((string)($auth["uid"] ?? ""))];
            }

            if ($action == "get") {
                $table = $body["table"] ?? "";

                if ($table == "") {
                    return ["ok" => false, "msg" => "Table not provided"];
                }

                if (($body["where"] ?? "") != "") {
                    return [
                        "ok" => true,
                        "data" => Auth::get($table, $body["where"], $body["is"] ?? "")
                    ];
                }

                return [
                    "ok" => true,
                    "data" => Auth::get($table)
                ];
            }

            if ($action == "user") {
                return [
                    "ok" => true,
                    "user" => Auth::user($body["uid"] ?? "")
                ];
            }

            if ($action == "new_user") {
                $err = Auth::newUser(
                    is_array($body["user_data"] ?? null) ? $body["user_data"] : [],
                    is_array($body["user_meta"] ?? null) ? $body["user_meta"] : [],
                    (bool)($body["is_this_register"] ?? false)
                );

                return [
                    "ok" => $err == "",
                    "msg" => $err
                ];
            }

            if ($action == "edit_user") {
                $err = Auth::editUser(
                    $body["uid"] ?? "",
                    is_array($body["user_data"] ?? null) ? $body["user_data"] : [],
                    is_array($body["user_meta"] ?? null) ? $body["user_meta"] : []
                );

                return [
                    "ok" => $err == "",
                    "msg" => $err
                ];
            }

            if ($action == "delete") {
                Auth::delete($body["table"] ?? "", $body["where"] ?? "", $body["is"] ?? "");

                return [
                    "ok" => true,
                    "msg" => "Deleted"
                ];
            }

            if ($action == "logout") {
                $jwt = $body["jwt"] ?? "";

                if ($jwt !== "") {
                    Auth::delete("jwt", "token", $jwt);
                }

                return [
                    "ok" => true,
                    "msg" => "Logged out"
                ];
            }

            if ($action == "verify_email") {
                return [
                    "ok" => Auth::verifyEmail($body["token"] ?? "")
                ];
            }

            if ($action == "verify_2fa") {
                return [
                    "ok" => Auth::verify2FaCode($body["code"] ?? "")
                ];
            }

            return [
                "ok" => false,
                "msg" => "Unknown auth action: " . $action
            ];
        } catch (Throwable $e) {
            return [
                "ok" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    /**
     * Verarbeitet die Funktion run one.
     * @param int $id Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runOne(int $id, array $ctx = []): array {
        self::ensureTables($ctx);

        $driver = self::driver($ctx);
        $job = self::getJob($id, $ctx);

        if (!$job || !isset($job["service"])) {
            return ["error" => "Job not found"];
        }

        $service = $job["service"];
        $action = $job["action"];
        $payload = json_decode($job["payload"] ?? "[]", true) ?: [];

        self::log($id, "info", "Run job #$id: service=$service action=$action");

        $driver::editData("main", "srv_jobs", "id", (string)$id, [
            "status" => "running",
            "started_at" => date("Y-m-d H:i:s")
        ]);

        $module = self::loadModule($service);

        if (!$module) {
            self::log($id, "error", "Module '$service' konnte nicht geladen werden");
            return ["error" => "Module '$service' not found"];
        }

        if (!method_exists($module, $action)) {
            self::log($id, "error", "Action '$action' in Modul '" . get_class($module) . "' nicht gefunden");
            return ["error" => "Action '$action' not found in module"];
        }

        try {
            self::log($id, "debug", "Starte Action '$action'", $payload);
            $result = $module->$action($payload, $job);
            self::log($id, "success", "Job #$id erfolgreich beendet", $result);

            $driver::editData("main", "srv_jobs", "id", (string)$id, [
                "status" => "done",
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["ok" => true, "result" => $result];
        } catch (Throwable $e) {
            self::log($id, "error", "Exception: " . $e->getMessage(), $e->getTraceAsString());

            $driver::editData("main", "srv_jobs", "id", (string)$id, [
                "status" => "failed",
                "error_msg" => $e->getMessage(),
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Verarbeitet die Funktion load module.
     * @param string $service Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function loadModule(string $service) {
        $service = preg_replace('/[^a-zA-Z0-9_\-]/', '', $service) ?? '';
        $file = __DIR__ . "/srv_modules/" . ucfirst($service) . ".php";
        $class = "Srv_" . ucfirst($service);

        if (!file_exists($file)) {
            return null;
        }

        require_once $file;

        if (!class_exists($class)) {
            return null;
        }

        return new $class();
    }

    /**
     * Verarbeitet die Funktion log.
     * @param int $jobId Übergabewert.
     * @param string $level Übergabewert.
     * @param string $message Übergabewert.
     * @param mixed $extra Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function log(int $jobId, string $level, string $message, $extra = null) {
        $dir = __DIR__ . "/../.logs/srv/";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . $jobId . ".log";

        $entry = [
            "time" => date("Y-m-d H:i:s"),
            "level" => $level,
            "msg" => $message
        ];

        if ($extra !== null) {
            $entry["extra"] = $extra;
        }

        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Verarbeitet die Funktion logs.
     * @param int $jobId Übergabewert.
     * @return array Rückgabewert.
     */
    public static function logs(int $jobId): array {
        $file = __DIR__ . "/../.logs/srv/" . $jobId . ".log";

        if (!is_file($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Verarbeitet die Funktion module log.
     * @param int $jobId Übergabewert.
     * @param string $level Übergabewert.
     * @param string $message Übergabewert.
     * @param mixed $extra Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function moduleLog(int $jobId, string $level, string $message, $extra = null) {
        self::log($jobId, $level, $message, $extra);
    }
}
