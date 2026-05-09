<?php

class Auth {
    private const MAIL_VERIFY = "verify_mail.html";
    private const MAIL_2FA = "tfa_mail.html";
    private const COOKIE_NAME = "jwt";
    private const KEYS = ["jwt", "pwf", "2fa", "everify"];

    private static string $return_to_instance = "";
    private static bool $srv = false;

    /**
     * sets srv response mode
     *
     * @param bool $usage
     * @return void
     */
    public static function setSrvUsage(bool $usage): void {
        self::$srv = $usage;
    }

    private static function instance(): string {
        return Vars::auth_instance();
    }

    private static function switchInstance(string $to = ""): void {
        if ($to == "") {
            GBDB::setInstance(self::instance());

            return;
        }

        if (self::$return_to_instance != "") {
            GBDB::setInstance(self::$return_to_instance);

            return;
        }

        if (Vars::main_instance() != "") {
            GBDB::setInstance(Vars::main_instance());
        }
    }

    private static function cookie(string $do, string $value = ""): mixed {
        if ($do == "get") {
            return Cookie::get(self::COOKIE_NAME);
        }

        if ($do == "exists") {
            return Cookie::exists(self::COOKIE_NAME);
        }

        if ($do == "delete") {
            Cookie::delete(self::COOKIE_NAME);

            return true;
        }

        if ($do == "set") {
            Cookie::set(self::COOKIE_NAME, $value);

            return true;
        }

        return false;
    }

    private static function ok(string $message = "", array $data = []): array {
        return [
            "ok" => true,
            "message" => $message,
            "data" => $data
        ];
    }

    private static function error(string $message = "", array $data = []): array {
        return [
            "ok" => false,
            "message" => $message,
            "data" => $data
        ];
    }

    private static function exp(): array {
        return [
            "jwt" => Vars::jwt_exp(),
            "pwf" => Vars::pwf_exp(),
            "2fa" => Vars::code_2fa_exp(),
            "everify" => Vars::mail_verify_exp()
        ];
    }

    private static function expires(string $key): string {
        if (!in_array($key, self::KEYS, true)) {
            throw new RuntimeException("exp-key " . $key . " not found in Auth::expires()");
        }

        $expiresInMinutes = (int)self::exp()[$key];

        if ($expiresInMinutes <= 0) {
            $expiresInMinutes = 1;
        }

        return date("Y-m-d H:i:s", time() + ($expiresInMinutes * 60));
    }

    private static function expired(string $exp, string $ref = "jwt"): bool {
        if (!in_array($ref, self::KEYS, true)) {
            return true;
        }

        if ($exp == "") {
            return true;
        }

        $time = strtotime($exp);

        if ($time === false) {
            return true;
        }

        return $time < time();
    }

    private static function isWhitelisted(): bool {
        $file = Vars::this_file();
        $fileWithoutExt = pathinfo($file, PATHINFO_FILENAME);

        foreach (Vars::auth_whitelist() as $entry) {
            if ($entry == $file || $entry == $fileWithoutExt) {
                return true;
            }
        }

        return false;
    }

    private static function randomToken(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }

    private static function uniqueToken(string $table, string $column = "token", int $bytes = 32): string {
        do {
            $retry = false;
            $token = self::randomToken($bytes);
            $exists = GBDB::get("tokens", $table, true, $column, $token);

            if (!empty($exists)) {
                $retry = true;
            }
        } while ($retry);

        return $token;
    }

    private static function uniqueUid(): string {
        do {
            $retry = false;
            $uid = self::randomToken(32);
            $user = GBDB::get("main", "users", true, "uid", $uid);

            if (!empty($user)) {
                $retry = true;
            }
        } while ($retry);

        return $uid;
    }

    private static function verifyPass(string $password, string $hash): bool {
        if ($hash == "") {
            return false;
        }

        return password_verify($password, $hash);
    }

    private static function createSession(string $uid): string {
        GBDB::delete("tokens", "jwt", "uid", $uid, true);

        $jwt = self::uniqueToken("jwt", "token", 32);

        $obj = [
            "uid" => $uid,
            "token" => $jwt,
            "exp" => self::expires("jwt")
        ];

        GBDB::insert("tokens", "jwt", $obj);
        self::cookie("set", $jwt);

        return $jwt;
    }

    private static function readMailTemplate(string $file): string {
        $file = dirname(__DIR__) . "/public/includes/mail_templates/" . $file;

        if (!is_file($file)) {
            return "";
        }

        $content = file_get_contents($file);

        if ($content === false) {
            return "";
        }

        return $content;
    }

    private static function cleanMailValue(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }

    private static function parse_email(string $mailAsHtml, string $linkOrCode, array $user, array $meta): string {
        $safeLink = htmlspecialchars($linkOrCode, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");

        $replace = [
            "<first_name/>" => self::cleanMailValue($meta["firstname"] ?? ""),
            "<last_name/>" => self::cleanMailValue($meta["lastname"] ?? ""),
            "<link/>" => '<a href="' . $safeLink . '">' . $safeLink . '</a>',
            "<username/>" => self::cleanMailValue($user["username"] ?? ""),
            "<email/>" => self::cleanMailValue($user["email"] ?? ""),
            "<telephone/>" => self::cleanMailValue($meta["telephone"] ?? ""),
            "<mobile/>" => self::cleanMailValue($meta["mobile"] ?? ""),
            "<adress/>" => self::cleanMailValue($meta["adress"] ?? ""),
            "<date/>" => date(Vars::date_format()),
            "<now/>" => date("H:i"),
            "<2fa_code/>" => self::cleanMailValue($linkOrCode)
        ];

        $tmp = str_replace(array_keys($replace), array_values($replace), $mailAsHtml);

        if (Vars::auth_email_config()["nl2br"]) {
            $tmp = nl2br($tmp);
        }

        return $tmp;
    }

    private static function delete_old_tokens(string $table): void {
        if (!in_array($table, self::KEYS, true)) {
            return;
        }

        $tokens = GBDB::get("tokens", $table);

        if (empty($tokens) || !is_array($tokens)) {
            return;
        }

        foreach ($tokens as $t) {
            if (!is_array($t)) {
                continue;
            }

            if (isset($t["id"]) && (int)$t["id"] === -1) {
                continue;
            }

            if (!isset($t["exp"])) {
                continue;
            }

            if (!self::expired((string)$t["exp"], $table)) {
                continue;
            }

            if (isset($t["token"]) && $t["token"] != "") {
                GBDB::delete("tokens", $table, "token", $t["token"], true);

                continue;
            }

            if (isset($t["code"]) && $t["code"] != "") {
                GBDB::delete("tokens", $table, "code", $t["code"], true);

                continue;
            }

            if (isset($t["uid"]) && $t["uid"] != "") {
                GBDB::delete("tokens", $table, "uid", $t["uid"], true);
            }
        }
    }

    private static function send_verify_email(array $user, array $meta): string {
        if (Vars::verify_email()) {
            GBDB::delete("tokens", "everify", "uid", $user["uid"], true);

            $token = self::uniqueToken("everify", "token", 16);

            $everify = [
                "uid" => $user["uid"],
                "token" => $token,
                "exp" => self::expires("everify")
            ];

            $link = Vars::auth_email_config()["verify_link"] . $token;
            $mail_as_html = self::readMailTemplate(self::MAIL_VERIFY);
            $mail_content = self::parse_email($mail_as_html, $link, $user, $meta);

            $mail_object = [
                "to_name" => $user["username"],
                "to_email" => $user["email"],
                "from_name" => Vars::auth_email_config()["from_name"],
                "from_email" => Vars::auth_email_config()["from_email"],
                "subject" => Vars::auth_email_config()["subject_verify"],
                "mail_content" => $mail_content
            ];

            Http::sendMail($mail_object);

            GBDB::insert("tokens", "everify", $everify);
            self::delete_old_tokens("everify");

            return "Please verify your email to be able to login.";
        }

        return "You can now login.";
    }

    private static function start_2fa(string $uid): array {
        $user = GBDB::get("main", "users", true, "uid", $uid);

        if (empty($user)) {
            return self::error("User not found.");
        }

        do {
            $retry = false;
            $code = (string)random_int(100000, 999999);
            $cexists = GBDB::get("tokens", "2fa", true, "code", $code);

            if (!empty($cexists)) {
                $retry = true;
            }
        } while ($retry);

        GBDB::delete("tokens", "2fa", "uid", $uid, true);

        $obj = [
            "uid" => $uid,
            "code" => $code,
            "exp" => self::expires("2fa")
        ];

        $mail_as_html = self::readMailTemplate(self::MAIL_2FA);
        $meta = GBDB::get("main", "meta", true, "uid", $uid);

        if (empty($meta)) {
            $meta = [];
        }

        $mail_content = self::parse_email($mail_as_html, $code, $user, $meta);

        $mail_object = [
            "to_name" => $user["username"],
            "to_email" => $user["email"],
            "from_name" => Vars::auth_email_config()["from_name"],
            "from_email" => Vars::auth_email_config()["from_email"],
            "subject" => Vars::auth_email_config()["subject_2fa"],
            "mail_content" => $mail_content
        ];

        Http::sendMail($mail_object);

        GBDB::insert("tokens", "2fa", $obj);
        self::delete_old_tokens("2fa");

        if (!self::$srv) {
            Ref::to(Vars::ref_2fa());
        }

        return self::ok("2FA code sent. Redirecting ....", [
            "redirect" => Vars::ref_2fa()
        ]);
    }

    /**
     * sets the instance to return after auth operations
     *
     * @param string $instance
     * @return void
     */
    public static function setReturnInstance(string $instance): void {
        self::$return_to_instance = $instance;
    }

    /**
     * creates and initializes the auth structure
     *
     * @return array
     */
    public static function createStructure(): array {
        if (!GBDB::existsInstance(self::instance())) {
            GBDB::runScript("init_auth.gql", [
                "instance" => self::instance(),
                "username" => Vars::auth_root_user()["username"],
                "email" => Vars::auth_root_user()["email"],
                "password" => self::hashPass(Vars::auth_root_user()["password"]),
                "active" => Vars::auth_root_user()["active"],
                "2fa" => Vars::auth_root_user()["2fa"],
                "role" => Vars::auth_root_user()["role"],
                "firstname" => Vars::auth_root_user()["firstname"],
                "lastname" => Vars::auth_root_user()["lastname"],
                "adress" => Vars::auth_root_user()["adress"],
                "telephone" => Vars::auth_root_user()["telephone"],
                "mobile" => Vars::auth_root_user()["mobile"],
                "gender" => Vars::auth_root_user()["gender"],
                "image" => Vars::auth_root_user()["image"],
                "text" => Vars::auth_root_user()["text"]
            ]);

            if (!self::$srv) {
                Ref::this_file();

                return [];
            }

            return self::ok("Structure created.");
        }

        return [];
    }

    /**
     * hashes a password in auth style
     *
     * @param string $password
     * @return string
     */
    public static function hashPass(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * logs out the current user
     *
     * @param string $error
     * @return array
     */
    public static function logout(string $error = ""): array {
        self::switchInstance();

        $jwt = "";

        if (self::cookie("exists")) {
            $jwt = (string)self::cookie("get");

            if ($jwt != "") {
                GBDB::delete("tokens", "jwt", "token", $jwt, true);
            }

            self::cookie("delete");
        }

        $err = "";

        if ($error != "") {
            $err = "?error=" . urlencode($error);
        }

        self::switchInstance("back");

        if (!self::$srv) {
            Ref::to(Vars::logout_ref() . $err);

            return [];
        }

        return self::ok("User logged out", [
            "jwt" => $jwt
        ]);
    }

    /**
     * initializes auth and returns the current user
     *
     * @return array
     */
    public static function init(): array {
        self::createStructure();

        if (self::isWhitelisted()) {
            return [];
        }

        if (!self::cookie("exists")) {
            self::logout("jwt_not_found");

            exit;
        }

        $jwt = (string)self::cookie("get");

        if ($jwt == "") {
            self::logout("jwt_not_found");

            exit;
        }

        self::switchInstance();

        $token = GBDB::get("tokens", "jwt", true, "token", $jwt);

        if (empty($token)) {
            self::switchInstance("back");
            self::logout("session_terminated");

            exit;
        }

        if (self::expired((string)$token["exp"], "jwt")) {
            GBDB::delete("tokens", "jwt", "token", $jwt, true);
            self::cookie("delete");
            self::switchInstance("back");
            self::logout("session_expired");

            exit;
        }

        $user = GBDB::get("main", "users", true, "uid", $token["uid"]);

        if (empty($user)) {
            self::switchInstance("back");
            self::logout("user_not_found");

            exit;
        }

        if (!$user["active"]) {
            self::switchInstance("back");
            self::logout("user_deactivated");

            exit;
        }

        self::switchInstance("back");

        return $user;
    }

    /**
     * logs in a user
     *
     * @param string $usernameOrEmail
     * @param string $passwordPlainText
     * @return array
     */
    public static function login(string $usernameOrEmail, string $passwordPlainText): array {
        self::switchInstance();

        self::delete_old_tokens("jwt");
        self::delete_old_tokens("2fa");
        self::delete_old_tokens("pwf");
        self::delete_old_tokens("everify");

        $usernameOrEmail = trim($usernameOrEmail);

        if ($usernameOrEmail == "") {
            self::switchInstance("back");

            return self::error("Username or email is required.");
        }

        if ($passwordPlainText == "") {
            self::switchInstance("back");

            return self::error("Password is required.");
        }

        $user = GBDB::get("main", "users", true, "username", $usernameOrEmail);

        if (empty($user)) {
            $user = GBDB::get("main", "users", true, "email", $usernameOrEmail);

            if (empty($user)) {
                self::switchInstance("back");

                return self::error("User with this username- or email not found.");
            }
        }

        if (!self::verifyPass($passwordPlainText, (string)$user["password"])) {
            self::switchInstance("back");

            return self::error("Password incorrect.");
        }

        if (!$user["active"]) {
            self::switchInstance("back");

            return self::error("This user is deactivated.");
        }

        $verifyed = GBDB::get("tokens", "everify", true, "uid", $user["uid"]);

        if (!empty($verifyed)) {
            if (self::expired((string)$verifyed["exp"], "everify")) {
                GBDB::delete("tokens", "everify", "uid", $user["uid"], true);
            } else {
                self::switchInstance("back");

                return self::error("Users email is not verified.");
            }
        }

        if ($user["tfa"]) {
            $result = self::start_2fa($user["uid"]);

            self::switchInstance("back");

            return $result;
        }

        self::createSession($user["uid"]);
        self::switchInstance("back");

        if (!self::$srv) {
            Ref::to(Vars::after_login());

            return [];
        }

        return self::ok("Logged in! Redirecting ....", [
            "uid" => $user["uid"],
            "username" => $user["username"],
            "role" => $user["role"] ?? "",
            "redirect" => Vars::after_login()
        ]);
    }

    /**
     * registers a new user
     *
     * @param string $username
     * @param string $email
     * @param string $passwordAsPlain
     * @param bool $active
     * @param bool $tfa
     * @param string $role
     * @param string $firstname
     * @param string $lastname
     * @param string $adress
     * @param string $telephone
     * @param string $mobile
     * @param bool $gender
     * @param string $image
     * @return array
     */
    public static function user_registration(
        string $username,
        string $email,
        string $passwordAsPlain,
        bool $active = true,
        bool $tfa = false,
        string $role = "user",
        string $firstname = "",
        string $lastname = "",
        string $adress = "",
        string $telephone = "",
        string $mobile = "",
        bool $gender = false,
        string $image = ""
    ): array {
        self::switchInstance();

        $username = trim($username);
        $email = trim($email);

        if ($username == "") {
            self::switchInstance("back");

            return self::error("Username is required.");
        }

        if ($email == "") {
            self::switchInstance("back");

            return self::error("Email is required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::switchInstance("back");

            return self::error("Email is invalid.");
        }

        if ($passwordAsPlain == "") {
            self::switchInstance("back");

            return self::error("Password is required.");
        }

        $user = GBDB::get("main", "users", true, "username", $username);

        if (!empty($user)) {
            self::switchInstance("back");

            return self::error("User with this username already exists.");
        }

        $user = GBDB::get("main", "users", true, "email", $email);

        if (!empty($user)) {
            self::switchInstance("back");

            return self::error("User with this email already exists.");
        }

        $uid = self::uniqueUid();

        $user = [
            "uid" => $uid,
            "username" => $username,
            "email" => $email,
            "password" => self::hashPass($passwordAsPlain),
            "active" => $active,
            "tfa" => $tfa,
            "role" => $role,
            "date" => date(Vars::date_format())
        ];

        $meta = [
            "uid" => $uid,
            "firstname" => $firstname,
            "lastname" => $lastname,
            "adress" => $adress,
            "telephone" => $telephone,
            "mobile" => $mobile,
            "gender" => $gender,
            "image" => $image,
            "text" => ""
        ];

        GBDB::insert("main", "users", $user);
        GBDB::insert("main", "meta", $meta);

        $zusatz = self::send_verify_email($user, $meta);

        self::switchInstance("back");

        return self::ok("You are successfully registrated. " . $zusatz, [
            "uid" => $uid,
            "username" => $username,
            "email" => $email,
            "verify_email" => Vars::verify_email()
        ]);
    }

    /**
     * verifies an email through a token
     *
     * @param string $token
     * @return array
     */
    public static function verify_email(string $token): array {
        self::switchInstance();

        $token = trim($token);
        $uid = GBDB::get("tokens", "everify", true, "token", $token);

        if (empty($uid)) {
            self::switchInstance("back");

            return self::error("Email verification failed.");
        }

        if (self::expired((string)$uid["exp"], "everify")) {
            GBDB::delete("tokens", "everify", "token", $token, true);
            self::switchInstance("back");

            return self::error("Email verification token expired.");
        }

        $user = GBDB::get("main", "users", true, "uid", $uid["uid"]);

        GBDB::delete("tokens", "everify", "uid", $uid["uid"], true);

        if (empty($user)) {
            self::switchInstance("back");

            return self::error("Email verification failed.");
        }

        self::delete_old_tokens("everify");
        self::switchInstance("back");

        return self::ok("Email verified. You can now login.", [
            "uid" => $uid["uid"]
        ]);
    }

    /**
     * verifies a 2fa code
     *
     * @param string|int $code
     * @return array
     */
    public static function verify_2fa_code(string|int $code): array {
        self::switchInstance();

        $code = trim((string)$code);
        $token = GBDB::get("tokens", "2fa", true, "code", $code);

        if (empty($token)) {
            self::switchInstance("back");

            return self::error("2FA code is invalid.");
        }

        if (self::expired((string)$token["exp"], "2fa")) {
            GBDB::delete("tokens", "2fa", "uid", $token["uid"], true);
            self::switchInstance("back");

            return self::error("2FA code expired.");
        }

        $user = GBDB::get("main", "users", true, "uid", $token["uid"]);

        GBDB::delete("tokens", "2fa", "uid", $token["uid"], true);

        if (empty($user)) {
            self::switchInstance("back");

            return self::error("User not found.");
        }

        if (!$user["active"]) {
            self::switchInstance("back");

            return self::error("This user is deactivated.");
        }

        self::createSession($user["uid"]);
        self::delete_old_tokens("2fa");
        self::switchInstance("back");

        return self::ok("2FA verified. Logged in.", [
            "uid" => $user["uid"],
            "username" => $user["username"],
            "role" => $user["role"] ?? ""
        ]);
    }

    /**
     * edits a user
     *
     * @param string $uid
     * @param string $username
     * @param string $email
     * @param string $passwordAsPlain
     * @param bool $active
     * @param bool $tfa
     * @param string $role
     * @param string $firstname
     * @param string $lastname
     * @param string $adress
     * @param string $telephone
     * @param string $mobile
     * @param bool $gender
     * @param string $image
     * @param string $text
     * @return array
     */
    public static function edit_user(
        string $uid,
        string $username,
        string $email,
        string $passwordAsPlain,
        bool $active = true,
        bool $tfa = false,
        string $role = "user",
        string $firstname = "",
        string $lastname = "",
        string $adress = "",
        string $telephone = "",
        string $mobile = "",
        bool $gender = false,
        string $image = "",
        string $text = ""
    ): array {
        self::switchInstance();

        $uid = trim($uid);
        $username = trim($username);
        $email = trim($email);

        $user = GBDB::get("main", "users", true, "uid", $uid);

        if (empty($user)) {
            self::switchInstance("back");

            return self::error("User not found.");
        }

        if ($username == "") {
            self::switchInstance("back");

            return self::error("Username is required.");
        }

        if ($email == "") {
            self::switchInstance("back");

            return self::error("Email is required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::switchInstance("back");

            return self::error("Email is invalid.");
        }

        $sameUsername = GBDB::get("main", "users", true, "username", $username);

        if (!empty($sameUsername) && (string)$sameUsername["uid"] !== $uid) {
            self::switchInstance("back");

            return self::error("User with this username already exists.");
        }

        $sameEmail = GBDB::get("main", "users", true, "email", $email);

        if (!empty($sameEmail) && (string)$sameEmail["uid"] !== $uid) {
            self::switchInstance("back");

            return self::error("User with this email already exists.");
        }

        $userObj = [
            "username" => $username,
            "email" => $email,
            "active" => $active,
            "tfa" => $tfa,
            "role" => $role
        ];

        if ($passwordAsPlain != "") {
            $userObj["password"] = self::hashPass($passwordAsPlain);
        }

        $metaObj = [
            "firstname" => $firstname,
            "lastname" => $lastname,
            "adress" => $adress,
            "telephone" => $telephone,
            "mobile" => $mobile,
            "gender" => $gender,
            "image" => $image,
            "text" => $text
        ];

        $meta = GBDB::get("main", "meta", true, "uid", $uid);

        GBDB::edit("main", "users", "uid", $uid, $userObj);

        if (empty($meta)) {
            $metaObj["uid"] = $uid;

            GBDB::insert("main", "meta", $metaObj);
        } else {
            GBDB::edit("main", "meta", "uid", $uid, $metaObj);
        }

        self::switchInstance("back");

        return self::ok("User updated", [
            "uid" => $uid,
            "main_data" => $userObj,
            "meta_data" => $metaObj
        ]);
    }

    /**
     * deletes a user
     *
     * @param string $uid
     * @return array
     */
    public static function delete_user(string $uid): array {
        self::switchInstance();

        $uid = trim($uid);
        $user = GBDB::get("main", "users", true, "uid", $uid);

        if (empty($user)) {
            self::switchInstance("back");

            return self::error("User not found.");
        }

        GBDB::delete("main", "users", "uid", $uid, true);
        GBDB::delete("main", "meta", "uid", $uid, true);
        GBDB::delete("tokens", "jwt", "uid", $uid, true);
        GBDB::delete("tokens", "2fa", "uid", $uid, true);
        GBDB::delete("tokens", "everify", "uid", $uid, true);
        GBDB::delete("tokens", "pwf", "uid", $uid, true);

        self::switchInstance("back");

        return self::ok("User deleted", [
            "uid" => $uid,
            "deleted_at" => date("Y-m-d H:i:s")
        ]);
    }

    /**
     * returns a user by uid
     *
     * @param string $uid
     * @return array
     */
    public static function get_user(string $uid): array {
        self::switchInstance();

        $uid = trim($uid);

        if ($uid == "") {
            self::switchInstance("back");

            return [];
        }

        $user = GBDB::get("main", "users", true, "uid", $uid);
        $meta = GBDB::get("main", "meta", true, "uid", $uid);

        self::switchInstance("back");

        if (empty($user)) {
            return [];
        }

        if (empty($meta)) {
            $meta = [];
        }

        return [
            "main_data" => $user,
            "meta_data" => $meta
        ];
    }

    /**
     * returns a user by jwt
     *
     * @param string $jwt
     * @return array
     */
    public static function get_jwt_user(string $jwt): array {
        self::switchInstance();

        $jwt = trim($jwt);

        if ($jwt == "") {
            self::switchInstance("back");

            return [];
        }

        $token = GBDB::get("tokens", "jwt", true, "token", $jwt);

        if (empty($token)) {
            self::switchInstance("back");

            return [];
        }

        if (self::expired((string)$token["exp"], "jwt")) {
            GBDB::delete("tokens", "jwt", "token", $jwt, true);

            self::switchInstance("back");

            return [];
        }

        $user = GBDB::get("main", "users", true, "uid", $token["uid"]);
        $meta = GBDB::get("main", "meta", true, "uid", $token["uid"]);

        self::switchInstance("back");

        if (empty($user)) {
            return [];
        }

        if (empty($meta)) {
            $meta = [];
        }

        return [
            "main_data" => $user,
            "meta_data" => $meta
        ];
    }

    /**
     * returns users
     *
     * @param int $limit
     * @return array
     */
    public static function getUsers(int $limit = 10000000): array {
        self::switchInstance();

        if ($limit <= 0) {
            $limit = 100;
        }

        if ($limit > 10000000) {
            $limit = 10000000;
        }

        $gql = "
        USE INSTANCE " . self::instance() . ";
        ROOT main;

        PICK * FROM users LIMIT " . $limit . ";
        ";

        $users = GBDB::query($gql);

        self::switchInstance("back");

        return $users;
    }
}

?>