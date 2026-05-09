<?php

class SrvFunctions {
    public static string $instance = "";

    private const AUTH_FIELDS = [
        "user",
        "uid",
        "jwt",
        "username",
        "email",
        "password",
        "2fa",
        "active",
        "role",
        "firstname",
        "lastname",
        "adress",
        "telephone",
        "mobile",
        "gender",
        "image",
        "text"
    ];

    private const REGISTER_FIELDS = [
        "username",
        "email",
        "password"
    ];

    private const LOGIN_FIELDS = [
        "user",
        "password"
    ];

    private static function authParams(array $required = [], bool $requireAtLeastOne = true): array {
        Srv::testParams(["user_data"]);

        if (!is_array(Srv::$body["user_data"])) {
            Srv::respond(400, "user_data must be an array");
        }

        $userData = Srv::$body["user_data"];

        foreach ($userData as $key => $value) {
            if (!in_array($key, self::AUTH_FIELDS, true)) {
                Srv::respond(400, "Invalid user_data param: " . $key);
            }
        }

        foreach ($required as $param) {
            if (!in_array($param, self::AUTH_FIELDS, true)) {
                Srv::respond(400, "Invalid required auth param: " . $param);
            }

            if (!array_key_exists($param, $userData)) {
                Srv::respond(400, "Missing user_data param: " . $param);
            }
        }

        if ($requireAtLeastOne && empty($required) && empty($userData)) {
            Srv::respond(400, "user_data must not be empty");
        }

        return $userData;
    }

    private static function str(array $data, string $key, string $default = ""): string {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return trim((string)$data[$key]);
    }

    private static function bool(array $data, string $key, bool $default = false): bool {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        if (is_bool($data[$key])) {
            return $data[$key];
        }

        $value = strtolower(trim((string)$data[$key]));

        if (in_array($value, ["1", "true", "yes", "on"], true)) {
            return true;
        }

        if (in_array($value, ["0", "false", "no", "off"], true)) {
            return false;
        }

        return $default;
    }

    /**
     * sets active server instance
     *
     * @param string $instance
     * @return void
     */
    public static function setInstance(string $instance): void {
        if (!GBDB::existsInstance($instance)) {
            Srv::respond(403, "Instance " . $instance . " not found");
        }

        self::$instance = $instance;

        GBDB::setInstance($instance);
        Srv::setInstance($instance);

        Srv::respond(200, "Instance set");
    }

    /**
     * returns ping status
     *
     * @return void
     */
    public static function ping(): void {
        Srv::respond(200, "ok");
    }

    /**
     * returns table data
     *
     * @return void
     */
    public static function getData(): void {
        Srv::testParams(["base", "table"]);

        $data = GBDB::get(
            (string)Srv::$body["base"],
            (string)Srv::$body["table"]
        );

        Srv::respond(200, $data);
    }

    /**
     * registers a new user
     *
     * @return void
     */
    public static function registerUser(): void {
        $userData = self::authParams(self::REGISTER_FIELDS);

        $regRes = SrvAuth::userRegistration(
            self::str($userData, "username"),
            self::str($userData, "email"),
            self::str($userData, "password"),
            self::bool($userData, "active", true),
            self::bool($userData, "2fa", false),
            self::str($userData, "role", "user"),
            self::str($userData, "firstname"),
            self::str($userData, "lastname"),
            self::str($userData, "adress"),
            self::str($userData, "telephone"),
            self::str($userData, "mobile"),
            self::bool($userData, "gender", false),
            self::str($userData, "image")
        );

        Srv::respond(200, $regRes);
    }

    /**
     * logs a user in
     *
     * @return void
     */
    public static function login(): void {
        $userData = self::authParams(self::LOGIN_FIELDS);

        $logRes = SrvAuth::login(
            self::str($userData, "user"),
            self::str($userData, "password")
        );

        Srv::respond(200, $logRes);
    }

    /**
     * logs a user out
     *
     * @return void
     */
    public static function logout(): void {
        $logoutRes = SrvAuth::logout();

        Srv::respond(200, $logoutRes);
    }

    /**
     * initializes auth system
     *
     * @return void
     */
    public static function init_auth(): void {
        $initRes = SrvAuth::init();

        Srv::respond(200, $initRes);
    }

    /**
     * verifies email token
     *
     * @return void
     */
    public static function verifyEmail(): void {
        Srv::testParams(["tmp_token"]);

        $verifyRes = SrvAuth::verify_email((string)Srv::$body["tmp_token"]);

        Srv::respond(200, $verifyRes);
    }

    /**
     * verifies 2fa code
     *
     * @return void
     */
    public static function verify2fa(): void {
        Srv::testParams(["code"]);

        $verifyRes = SrvAuth::verify_2fa_code((string)Srv::$body["code"]);

        Srv::respond(200, $verifyRes);
    }

    /**
     * deletes user by uid
     *
     * @return void
     */
    public static function deleteUser(): void {
        Srv::testParams(["uid"]);

        $delRes = SrvAuth::deleteUser((string)Srv::$body["uid"]);

        Srv::respond(200, $delRes);
    }

    /**
     * edits user data
     *
     * @return void
     */
    public static function editUser(): void {
        Srv::testParams(["uid"]);

        $userData = self::authParams([], false);

        $editRes = SrvAuth::editUser(
            (string)Srv::$body["uid"],
            self::str($userData, "username"),
            self::str($userData, "email"),
            self::str($userData, "password"),
            self::bool($userData, "active", true),
            self::bool($userData, "2fa", false),
            self::str($userData, "role", "user"),
            self::str($userData, "firstname"),
            self::str($userData, "lastname"),
            self::str($userData, "adress"),
            self::str($userData, "telephone"),
            self::str($userData, "mobile"),
            self::bool($userData, "gender", false),
            self::str($userData, "image"),
            self::str($userData, "text")
        );

        Srv::respond(200, $editRes);
    }

    /**
     * returns user data by uid
     *
     * @return void
     */
    public static function getUser(): void {
        Srv::testParams(["uid"]);

        $user = SrvAuth::getUser((string)Srv::$body["uid"]);

        Srv::respond(200, $user);
    }

    /**
     * returns user data by jwt
     *
     * @return void
     */
    public static function getJwtUser(): void {
        Srv::testParams(["jwt"]);

        $user = SrvAuth::getJwtUser((string)Srv::$body["jwt"]);

        Srv::respond(200, $user);
    }

    /**
     * returns all users
     *
     * @return void
     */
    public static function getUsers(): void {
        $limit = 10000000;

        if (isset(Srv::$body["limit"])) {
            $limit = (int)Srv::$body["limit"];
        }

        $users = SrvAuth::getUsers($limit);

        Srv::respond(200, $users);
    }

    public static function driver(): void {
        Srv::respond(200, Vars::db_arch());
    }
}

?>