<?php

require_once __DIR__ . "/module_helper.php";

class PublicAPI {
    private const MODULES_DIR = __DIR__ . "/public_api_modules";
    private const INSTANCE = "__greenql_ui_v2_system";
    private const KEY_BASE = "papi";
    private const KEY_TABLE = "keys";
    private const LOG_BASE = "plog";
    private const LOG_FETCH_TABLE = "fetches";
    private const LOG_KEY_TABLE = "key_log";
    private const LOG_DEFAULT_TABLE = "api_log";

    private static array $body = [];
    private static array $keyData = [];
    private static array $modules = [];
    private static string $module = "";
    private static string $action = "";
    private static string $logTable = self::LOG_DEFAULT_TABLE;

    /**
     * initializes public api request handling
     *
     * @return void
     */
    public static function init(): void {
        self::readBody();
        self::parseAction();
        self::loadModules();
        self::auth();

        self::log(self::$module . "." . self::$action, [
            "log" => "fetch",
            "key" => (string) (self::$keyData["key"] ?? ""),
            "ip" => (string) ($_SERVER["REMOTE_ADDR"] ?? "")
        ]);

        self::dispatch();
    }

    /**
     * sets custom public api log table
     *
     * @param string $table
     * @return void
     */
    public static function setLogTable(string $table): void {
        $table = Format::cleanString($table);

        if ($table == "") {
            return;
        }

        self::$logTable = $table;

        self::withSystemInstance(function () use ($table): void {
            if (!GBDB::exists(self::LOG_BASE, $table)) {
                GBDB::createTable(self::LOG_BASE, $table, [
                    "datetime",
                    "log"
                ]);
            }
        });
    }

    /**
     * writes public api log entry
     *
     * @param string $log
     * @param array $sys
     * @return void
     */
    public static function log(string $log, array $sys = []): void {
        self::withSystemInstance(function () use ($log, $sys): void {
            if (empty($sys)) {
                if (self::$logTable == "") {
                    self::$logTable = self::LOG_DEFAULT_TABLE;
                }

                if (!GBDB::exists(self::LOG_BASE, self::$logTable)) {
                    GBDB::createTable(self::LOG_BASE, self::$logTable, [
                        "datetime",
                        "log"
                    ]);
                }

                GBDB::insertData(self::LOG_BASE, self::$logTable, [
                    "datetime" => date("Y-m-d H:i:s"),
                    "log" => $log
                ]);

                return;
            }

            if (($sys["log"] ?? "") == "fetch") {
                GBDB::insertData(self::LOG_BASE, self::LOG_FETCH_TABLE, [
                    "key" => (string) ($sys["key"] ?? ""),
                    "ip" => (string) ($sys["ip"] ?? ""),
                    "datetime" => date("Y-m-d H:i:s"),
                    "action" => $log
                ]);

                return;
            }

            GBDB::insertData(self::LOG_BASE, self::LOG_KEY_TABLE, [
                "key" => (string) ($sys["key"] ?? ""),
                "user" => (string) ($sys["uid"] ?? ""),
                "datetime" => date("Y-m-d H:i:s"),
                "action" => $log
            ]);
        });
    }

    /**
     * sends public api json response
     *
     * @param int $status
     * @param mixed $data
     * @return never
     */
    public static function respond(int $status, mixed $data): never {
        http_response_code($status);

        header("Content-Type: application/json; charset=utf-8");

        echo json_encode([
            "ok" => $status >= 200 && $status < 300,
            "status" => $status,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    private static function readBody(): void {
        $raw = file_get_contents("php://input");
        $body = json_decode($raw ?: "{}", true);

        if (!is_array($body)) {
            self::respond(400, "invalid json body");
        }

        self::$body = $body;
    }

    private static function parseAction(): void {
        $do = Format::cleanString((string) (self::$body["do"] ?? ""));

        if ($do == "") {
            self::$module = Format::cleanString((string) (self::$body["module"] ?? ""));
            self::$action = Format::cleanString((string) (self::$body["action"] ?? ""));
        } else {
            $parts = explode(".", $do, 2);

            self::$module = Format::cleanString((string) ($parts[0] ?? ""));
            self::$action = Format::cleanString((string) ($parts[1] ?? ""));
        }

        if (self::$module == "") {
            self::respond(400, "missing module");
        }

        if (self::$action == "") {
            self::respond(400, "missing action");
        }
    }

    private static function loadModules(): void {
        if (!is_dir(self::MODULES_DIR)) {
            self::respond(500, "public api modules directory not found");
        }

        $coreModule = __DIR__ . "/gbdb.php";

        if (is_file($coreModule)) {
            self::loadModuleFile($coreModule);
        }

        $files = scandir(self::MODULES_DIR);

        if (!is_array($files)) {
            self::respond(500, "could not read public api modules directory");
        }

        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            if (!str_ends_with($file, ".php")) {
                continue;
            }

            self::loadModuleFile(self::MODULES_DIR . "/" . $file);
        }
    }

    private static function loadModuleFile(string $file): void {
        $before = get_declared_classes();

        require_once $file;

        $after = get_declared_classes();
        $classes = array_diff($after, $before);

        foreach ($classes as $class) {
            if (!str_starts_with($class, "PublicAPI_")) {
                continue;
            }

            if (!method_exists($class, "name")) {
                continue;
            }

            if (!method_exists($class, "requiresRights")) {
                continue;
            }

            if (!method_exists($class, "handle")) {
                continue;
            }

            $name = Format::cleanString((string) $class::name());

            if ($name == "") {
                continue;
            }

            self::$modules[$name] = $class;
        }
    }

    private static function auth(): void {
        $key = (string) (self::$body["key"] ?? "");

        if ($key == "") {
            self::respond(401, "missing api key");
        }

        $rows = self::withSystemInstance(function () use ($key): array {
            return GBDB::getData(self::KEY_BASE, self::KEY_TABLE, true, "key", $key);
        });

        if (empty($rows) || !isset($rows[0])) {
            self::respond(401, "invalid api key");
        }

        $data = $rows[0];

        if ((string) ($data["active"] ?? "0") != "1") {
            self::respond(403, "api key inactive");
        }

        if (!empty($data["exp"]) && strtotime((string) $data["exp"]) < time()) {
            self::respond(403, "api key expired");
        }

        self::$keyData = $data;
    }

    private static function dispatch(): void {
        if (!isset(self::$modules[self::$module])) {
            self::respond(404, "module not found");
        }

        $class = self::$modules[self::$module];

        self::checkRights($class);

        $result = $class::handle(self::$action, self::$body, self::$keyData);

        if (is_array($result) && isset($result["status"])) {
            self::respond((int) $result["status"], $result["data"] ?? null);
        }

        self::respond(200, $result);
    }

    private static function checkRights(string $class): void {
        $required = self::moduleRights($class);

        if (empty($required)) {
            return;
        }

        $rights = self::keyRights();

        if (self::hasRight($rights, "*")) {
            return;
        }

        foreach ($required as $right) {
            $right = trim((string) $right);

            if ($right == "") {
                continue;
            }

            if (self::hasRight($rights, $right)) {
                continue;
            }

            self::log("missing right: " . $right, [
                "log" => "key",
                "key" => (string) (self::$keyData["key"] ?? ""),
                "uid" => (string) (self::$keyData["created_by"] ?? "")
            ]);

            self::respond(403, "missing api right: " . $right);
        }
    }

    private static function moduleRights(string $class): array {
        $method = new ReflectionMethod($class, "requiresRights");
        $count = $method->getNumberOfParameters();

        if ($count <= 0) {
            $rights = $class::requiresRights();
        } else {
            $rights = $class::requiresRights(self::$action);
        }

        if (!is_array($rights)) {
            self::respond(500, "module requiresRights must return array");
        }

        return $rights;
    }

    private static function keyRights(): array {
        $raw = self::$keyData["rights"] ?? [];

        if (is_array($raw)) {
            return $raw;
        }

        $raw = trim((string) $raw);

        if ($raw == "") {
            return [];
        }

        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        $parts = explode(",", $raw);
        $rights = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part != "") {
                $rights[] = $part;
            }
        }

        return $rights;
    }

    private static function hasRight(array $rights, string $right): bool {
        if (isset($rights["admin"]) && $rights["admin"] === true) {
            return true;
        }

        if (isset($rights["rights"]) && is_array($rights["rights"])) {
            return self::hasRight($rights["rights"], $right);
        }

        foreach ($rights as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item == "*") {
                return true;
            }

            if ($item == $right) {
                return true;
            }

            if (str_ends_with($item, ".*")) {
                $prefix = substr($item, 0, -2);

                if (str_starts_with($right, $prefix . ".")) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function withSystemInstance(callable $callback): mixed {
        $oldInstance = GBDB::getInstance();

        GBDB::setInstance(self::INSTANCE);

        try {
            return $callback();
        } finally {
            if ($oldInstance != "") {
                GBDB::setInstance($oldInstance);
            }
        }
    }
}
