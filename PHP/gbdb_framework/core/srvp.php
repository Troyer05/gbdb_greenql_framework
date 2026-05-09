<?php

class SecondServer {
    private const DO_START_SESSION = "start_session(srvp.init)";

    private static function endpoint(): string {
        return rtrim(Vars::srvp_ip(), "/") . "/backend.php";
    }

    private static function error(string $message = "", array $data = []): array {
        return [
            "ok" => false,
            "message" => $message,
            "data" => $data
        ];
    }

    private static function send(array $body): array {
        $body["static_auth"] = Vars::srvp_static_key();

        $tmp = Http::post(self::endpoint(), $body);

        if (!is_string($tmp) || trim($tmp) == "") {
            return self::error("Empty response from server.");
        }

        $response = json_decode($tmp, true);

        if (!is_array($response)) {
            return self::error("Invalid JSON response from server.", [
                "raw" => $tmp
            ]);
        }

        return $response;
    }

    private static function getToken(): string {
        $response = self::send([
            "do" => self::DO_START_SESSION
        ]);

        if (!isset($response["ok"]) || !$response["ok"]) {
            return "";
        }

        if (!isset($response["data"]) || !is_array($response["data"])) {
            return "";
        }

        if (!isset($response["data"]["token"])) {
            return "";
        }

        return (string)$response["data"]["token"];
    }

    private static function sendInstruction(array $body): array {
        $token = self::getToken();

        if ($token == "") {
            return self::error("Could not start server session.");
        }

        $body["tmp_token"] = $token;

        return self::send($body);
    }

    /**
     * checks server availability
     *
     * @return bool
     */
    public static function ping(): bool {
        $res = self::sendInstruction([
            "do" => "ping"
        ]);

        return isset($res["ok"]) && $res["ok"] === true;
    }

    /**
     * returns second-servers db driver
     * @return array
     */
    public static function driver(): array {
        return self::sendInstruction([
            "do" => "get_engine_driver"
        ]);
    }

    /**
     * starts a job
     *
     * @param string $job
     * @param array $parameters
     * @return array
     */
    public static function startJob(string $job, array $parameters = []): array {
        return self::sendInstruction([
            "do" => "start_job",
            "job" => $job,
            "parameters" => $parameters
        ]);
    }

    /**
     * lists all jobs
     * @return array
     */
    public static function listJobs(): array {
        return self::sendInstruction([
            "do" => "list_jobs"
        ]);
    }

    /**
     * registers a new user
     *
     * @param array $data
     * @return array
     */
    public static function registerUser(array $data): array {
        return self::sendInstruction([
            "do" => "register_user",
            "user_data" => $data
        ]);
    }

    /**
     * logs in a user
     *
     * @param string $user
     * @param string $password
     * @return array
     */
    public static function login(string $user, string $password): array {
        return self::sendInstruction([
            "do" => "login_user",
            "user_data" => [
                "user" => $user,
                "password" => $password
            ]
        ]);
    }

    /**
     * logs in a user with raw user data
     *
     * @param array $data
     * @return array
     */
    public static function loginUser(array $data): array {
        return self::sendInstruction([
            "do" => "login_user",
            "user_data" => $data
        ]);
    }

    /**
     * logs out a user
     *
     * @return array
     */
    public static function logout(): array {
        return self::sendInstruction([
            "do" => "logout_user"
        ]);
    }

    /**
     * initializes auth system
     *
     * @return array
     */
    public static function initAuth(): array {
        return self::sendInstruction([
            "do" => "init"
        ]);
    }

    /**
     * verifies email token
     *
     * @param string $token
     * @return array
     */
    public static function verifyEmail(string $token): array {
        return self::sendInstruction([
            "do" => "verify_email",
            "tmp_token" => $token
        ]);
    }

    /**
     * verifies 2fa code
     *
     * @param string|int $code
     * @return array
     */
    public static function verify2fa(string|int $code): array {
        return self::sendInstruction([
            "do" => "verify_2fa_code",
            "code" => (string)$code
        ]);
    }

    /**
     * deletes a user
     *
     * @param string $uid
     * @return array
     */
    public static function deleteUser(string $uid): array {
        return self::sendInstruction([
            "do" => "delete_user",
            "uid" => $uid
        ]);
    }

    /**
     * edits a user
     *
     * @param string $uid
     * @param array $data
     * @return array
     */
    public static function editUser(string $uid, array $data): array {
        return self::sendInstruction([
            "do" => "edit_user",
            "uid" => $uid,
            "user_data" => $data
        ]);
    }

    /**
     * returns a user by uid
     *
     * @param string $uid
     * @return array
     */
    public static function getUser(string $uid): array {
        return self::sendInstruction([
            "do" => "get_user",
            "uid" => $uid
        ]);
    }

    /**
     * returns a user by jwt
     *
     * @param string $jwt
     * @return array
     */
    public static function getJwtUser(string $jwt): array {
        return self::sendInstruction([
            "do" => "get_jwt_user",
            "jwt" => $jwt
        ]);
    }

    /**
     * returns users
     *
     * @param int $limit
     * @return array
     */
    public static function getUsers(int $limit = 10000000): array {
        return self::sendInstruction([
            "do" => "get_users",
            "limit" => $limit
        ]);
    }

    /**
     * runs auth action by name
     *
     * @param string $do
     * @param array $data
     * @return array
     */
    public static function userAuth(string $do, array $data = []): array {
        if ($do == "register_user") {
            return self::registerUser($data);
        }

        if ($do == "login_user") {
            return self::loginUser($data);
        }

        if ($do == "logout_user") {
            return self::logout();
        }

        if ($do == "init") {
            return self::initAuth();
        }

        if ($do == "verify_email") {
            return self::verifyEmail((string)($data["token"] ?? ""));
        }

        if ($do == "verify_2fa_code") {
            return self::verify2fa((string)($data["code"] ?? ""));
        }

        if ($do == "delete_user") {
            return self::deleteUser((string)($data["uid"] ?? ""));
        }

        if ($do == "edit_user") {
            return self::editUser(
                (string)($data["uid"] ?? ""),
                is_array($data["user_data"] ?? null) ? $data["user_data"] : $data
            );
        }

        if ($do == "get_user") {
            return self::getUser((string)($data["uid"] ?? ""));
        }

        if ($do == "get_jwt_user") {
            return self::getJwtUser((string)($data["jwt"] ?? ""));
        }

        if ($do == "get_users") {
            return self::getUsers((int)($data["limit"] ?? 10000000));
        }

        return self::error("Unknown auth action: " . $do);
    }
}

?>