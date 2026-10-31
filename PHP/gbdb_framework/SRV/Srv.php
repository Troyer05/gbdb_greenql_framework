<?php

class Srv {
    public static array $body = [];
    public static string $tmpInstance = "";

    private const FUNCTIONS = [
        "ping" => "ping",
        "get_data" => "getData",
        "register_user" => "registerUser",
        "login_user" => "login",
        "logout_user" => "logout",
        "init" => "init_auth",
        "verify_email" => "verifyEmail",
        "verify_2fa_code" => "verify2fa",
        "get_engine_driver" => "driver",
        'delete_user' => 'deleteUser',
        'edit_user' => 'editUser',
        'get_user' => 'getUser',
        'get_jwt_user' => 'getJwtUser',
        'get_users' => 'getUsers'
    ];

    private const JOB_FUNCTIONS = [
        "start_job",
        "list_jobs"
    ];

    /**
     * ensures srv-system instance
     *
     * @return void
     */
    public static function ensureInstance() :void {
        GBDBRuntime::run(static function () :void {
            if (!GBDB::existsInstance(self::instance())) {
                $result = GBDB::runScript('init_srv.gql', ['instance' => self::instance()]);

                if (($result['ok'] ?? false) !== true) {
                    throw new RuntimeException('SRV-Speicher konnte nicht initialisiert werden.');
                }
            }
        });
    }

    /**
     * initializes backend api for secondserver module
     *
     * @return void
     */
    public static function init() :void {
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        if (strlen(Vars::srvp_static_key()) < 32) {
            self::respond(503, 'SRV endpoint is disabled until a strong static key is configured');
        }

        if (($_SERVER["REQUEST_METHOD"] ?? "") != "POST") {
            self::respond(403, "request method blocked");
        }

        if (!GBDBRateLimiter::allow('srv-api', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 600, 60)) {
            self::respond(429, 'rate limit exceeded');
        }

        $json = file_get_contents('php://input', false, null, 0, 4194305);

        if (strlen((string) $json) > 4194304) {
            self::respond(413, 'request body too large');
        }

        if ($json === false || trim($json) == "") {
            self::respond(400, "empty request body");
        }

        $body = json_decode($json, true);

        if (!is_array($body)) {
            self::respond(400, "invalid json body");
        }

        self::$body = $body;

        self::testParams(["do", "static_auth"]);

        foreach (["do", "static_auth", "tmp_token", "instance", "job"] as $name) {
            if (isset(self::$body[$name]) && !is_string(self::$body[$name])) {
                self::respond(400, $name . " must be a string");
            }
        }

        self::authenticate(self::$body["static_auth"], self::$body["do"]);
        GBDB::sandboxInstances(static function () :void {
            self::dispatch();
        });
    }

    private static function dispatch() :void {
        self::applyRequestInstance();

        foreach (self::FUNCTIONS as $key => $function) {
            if (self::$body["do"] === $key) {
                if (!class_exists("SrvFunctions") || !method_exists("SrvFunctions", $function)) {
                    self::respond(500, "srv function not found: " . $function);
                }

                SrvFunctions:: {$function}();

                exit;
            }
        }

        if (in_array(self::$body["do"], self::JOB_FUNCTIONS, true)) {
            if (self::$body["do"] == "start_job") {
                self::runJob();
            }

            if (self::$body["do"] == "list_jobs") {
                self::listJobs();
            }

            exit;
        }

        self::respond(404, "unknown action: " . self::$body["do"]);
    }

    /**
     * sets temporary target instance
     *
     * @param string $instance
     * @return void
     */
    public static function setInstance(string $instance) :void {
        $instance = trim($instance);

        if ($instance == "") {
            self::respond(403, "instance is empty");
        }

        if (GBDB::isReservedInstance($instance) || !GBDB::existsInstance($instance)) {
            self::respond(403, "instance " . $instance . " not found");
        }

        self::$tmpInstance = $instance;

        GBDB::setInstance($instance);
    }

    /**
     * returns json response
     *
     * @param int $status
     * @param mixed $data
     * @return void
     */
    public static function respond(int $status, mixed $data) :void {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code($status);

        $response = [
            "ok" => ($status >= 200 && $status < 300),
            "status" => $status,
            "data" => $data
        ];

        die(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * validates request parameters
     *
     * @param array $params
     * @return void
     */
    public static function testParams(array $params) :void {
        foreach ($params as $param) {
            if (!isset(self::$body[$param])) {
                self::respond(403, "parameter " . $param . " not found");
            }
        }
    }

    private static function instance() :string {
        return Vars::srvp_sys_instance();
    }

    private static function modulePath() :string {
        return __DIR__ . "/srv_modules";
    }

    private static function applyRequestInstance() :void {
        if (!isset(self::$body["instance"])) {
            return;
        }

        self::setInstance((string)self::$body["instance"]);
    }

    private static function toggleInstance(bool $switchToTmpInstance = false) :void {
        if ($switchToTmpInstance) {
            if (self::$tmpInstance != "") {
                GBDB::setInstance(self::$tmpInstance);
            }

            return;
        }

        if (GBDB::isReservedInstance(self::instance())) {
            self::respond(500, "srv system instance must not use a reserved name");
        }

        GBDB::setInstance(self::instance());
    }

    private static function expires() :string {
        return (string)(time() + 300);
    }

    private static function expired(string $time) :bool {
        if ($time == "") {
            return true;
        }

        return ((int)$time) < time();
    }

    private static function authenticate(string $staticAuth, string $do) :void {
        if (!hash_equals(Vars::srvp_static_key(), $staticAuth)) {
            self::respond(403, "static authentification failed");
        }

        self::ensureInstance();

        if ($do === "start_session(srvp.init)") {
            self::newToken();
        }

        self::testParams(["tmp_token"]);
        self::checkToken((string)self::$body["tmp_token"]);
    }

    private static function newToken() :void {
        self::toggleInstance();

        do {
            $retry = false;
            $token = bin2hex(random_bytes(32));
            $exists = GBDB::get("main", "tokens", true, "token", $token);

            if (!empty($exists)) {
                $retry = true;
            }
        } while ($retry);

        $exp = self::expires();

        $obj = [
            "token" => $token,
            "exp" => $exp,
            "created_at" => (string)time()
        ];

        $tokens = GBDB::get("main", "tokens");

        if (is_array($tokens)) {
            foreach ($tokens as $t) {
                if (!isset($t["exp"], $t["token"])) {
                    continue;
                }

                if (self::expired((string)$t["exp"])) {
                    GBDB::delete("main", "tokens", "token", $t["token"]);
                }
            }
        }

        GBDB::insert("main", "tokens", $obj);
        self::toggleInstance(true);

        self::respond(200, [
            "token" => $token,
            "exp" => $exp
        ]);
    }

    private static function checkToken(string $token) :void {
        if ($token == "") {
            self::respond(403, "srv-authentification failed");
        }

        self::toggleInstance();

        $row = GBDB::get("main", "tokens", true, "token", $token);

        if (empty($row) || !is_array($row) || !hash_equals((string)($row["token"] ?? ""), $token)) {
            self::respond(403, "srv-authentification failed");
        }

        if (!isset($row["exp"], $row["token"]) || self::expired((string)$row["exp"])) {
            if (isset($row["token"])) {
                GBDB::delete("main", "tokens", "token", $row["token"]);
            }

            self::respond(403, "srv-token expired");
        }

        GBDB::delete("main", "tokens", "token", $row["token"]);
        self::toggleInstance(true);
    }

    private static function loadJobModules() :void {
        $path = self::modulePath();

        if (!is_dir($path)) {
            self::respond(500, "srv module path not found");
        }

        $helper = $path . "/SRVJob.php";

        if (is_file($helper)) {
            require_once $helper;
        }

        $files = GBDBFilesystem::glob($path . "/*.php");

        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            if (basename($file) == "SRVJob.php") {
                continue;
            }

            require_once $file;
        }
    }

    private static function normalizeJobClass(string $job) :string {
        $job = trim($job);

        if ($job == "") {
            self::respond(400, "job is empty");
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $job)) {
            self::respond(400, "invalid job name");
        }

        if (!str_starts_with($job, "SRVJob_")) {
            $job = "SRVJob_" . $job;
        }

        return $job;
    }

    private static function resolveJobClass(string $job) :string {
        self::loadJobModules();

        $class = self::normalizeJobClass($job);

        if (class_exists($class, false)) {
            return $class;
        }

        foreach (get_declared_classes() as $declaredClass) {
            if (strtolower($declaredClass) == strtolower($class)) {
                return $declaredClass;
            }
        }

        self::respond(404, "srv job not found: " . $job);

        return "";
    }

    private static function getJobClasses() :array {
        self::loadJobModules();

        $jobs = [];

        foreach (get_declared_classes() as $class) {
            if (!str_starts_with($class, "SRVJob_")) {
                continue;
            }

            if (!method_exists($class, "__start_job")) {
                continue;
            }

            $jobs[] = [
                "class" => $class,
                "job" => substr($class, strlen("SRVJob_")),
                "starter" => "__start_job"
            ];
        }

        return $jobs;
    }

    private static function getJobParameters() :array {
        if (isset(self::$body["parameters"]) && is_array(self::$body["parameters"])) {
            return self::$body["parameters"];
        }

        if (isset(self::$body["params"]) && is_array(self::$body["params"])) {
            return self::$body["params"];
        }

        return [];
    }

    private static function listJobs() :void {
        self::respond(200, [
            "path" => self::modulePath(),
            "jobs" => self::getJobClasses()
        ]);
    }

    private static function runJob() :void {
        self::testParams(["job"]);

        $class = self::resolveJobClass((string)self::$body["job"]);

        if (!method_exists($class, "__start_job")) {
            self::respond(500, "srv job starter not found: " . $class . "::__start_job");
        }

        $parameters = self::getJobParameters();

        if (class_exists("SRVJob") && method_exists("SRVJob", "setParams")) {
            SRVJob::setParams($parameters);
        }

        try {
            $method = new ReflectionMethod($class, "__start_job");

            if (!$method->isPublic()) {
                self::respond(500, "srv job starter must be public: " . $class . "::__start_job");
            }

            ob_start();

            if ($method->getNumberOfParameters() > 0) {
                if ($method->isStatic()) {
                    $result = $method->invoke(null, $parameters);
                } else {
                    $object = new $class();
                    $result = $method->invoke($object, $parameters);
                }
            } else {
                if ($method->isStatic()) {
                    $result = $method->invoke(null);
                } else {
                    $object = new $class();
                    $result = $method->invoke($object);
                }
            }

            $output = ob_get_clean();

            self::respond(200, [
                "job" => (string)self::$body["job"],
                "class" => $class,
                "started" => date("Y-m-d H:i:s"),
                "parameters" => $parameters,
                "result" => $result,
                "output" => $output
            ]);
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            self::respond(500, [
                "error" => "srv job failed",
                "job" => (string)self::$body["job"],
                "class" => $class,
                "message" => $e->getMessage()
            ]);
        }
    }
}
