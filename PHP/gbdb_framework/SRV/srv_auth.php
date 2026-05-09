<?php

class SrvAuth {
    private static function run(callable $callback): array {
        Auth::setSrvUsage(true);

        try {
            return $callback();
        } finally {
            Auth::setSrvUsage(false);
        }
    }

    /**
     * registers a new user through srv mode
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
    public static function userRegistration(
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
        return self::run(function () use (
            $username,
            $email,
            $passwordAsPlain,
            $active,
            $tfa,
            $role,
            $firstname,
            $lastname,
            $adress,
            $telephone,
            $mobile,
            $gender,
            $image
        ): array {
            return Auth::user_registration(
                $username,
                $email,
                $passwordAsPlain,
                $active,
                $tfa,
                $role,
                $firstname,
                $lastname,
                $adress,
                $telephone,
                $mobile,
                $gender,
                $image
            );
        });
    }

    /**
     * logs in a user through srv mode
     *
     * @param string $usernameOrEmail
     * @param string $passwordAsPlainText
     * @return array
     */
    public static function login(string $usernameOrEmail, string $passwordAsPlainText): array {
        return self::run(function () use ($usernameOrEmail, $passwordAsPlainText): array {
            return Auth::login($usernameOrEmail, $passwordAsPlainText);
        });
    }

    /**
     * logs out the current user through srv mode
     *
     * @param string $error
     * @return array
     */
    public static function logout(string $error = ""): array {
        return self::run(function () use ($error): array {
            return Auth::logout($error);
        });
    }

    /**
     * initializes auth through srv mode
     *
     * @return array
     */
    public static function init(): array {
        return self::run(function (): array {
            return Auth::init();
        });
    }

    /**
     * verifies a 2fa code through srv mode
     *
     * @param string|int $code
     * @return array
     */
    public static function verify_2fa_code(string|int $code): array {
        return self::run(function () use ($code): array {
            return Auth::verify_2fa_code($code);
        });
    }

    /**
     * verifies an email through srv mode
     *
     * @param string $token
     * @return array
     */
    public static function verify_email(string $token): array {
        return self::run(function () use ($token): array {
            return Auth::verify_email($token);
        });
    }

    /**
     * edits a user through srv mode
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
    public static function editUser(
        string $uid,
        string $username,
        string $email,
        string $passwordAsPlain = "",
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
        return self::run(function () use (
            $uid,
            $username,
            $email,
            $passwordAsPlain,
            $active,
            $tfa,
            $role,
            $firstname,
            $lastname,
            $adress,
            $telephone,
            $mobile,
            $gender,
            $image,
            $text
        ): array {
            return Auth::edit_user(
                $uid,
                $username,
                $email,
                $passwordAsPlain,
                $active,
                $tfa,
                $role,
                $firstname,
                $lastname,
                $adress,
                $telephone,
                $mobile,
                $gender,
                $image,
                $text
            );
        });
    }

    /**
     * deletes a user through srv mode
     *
     * @param string $uid
     * @return array
     */
    public static function deleteUser(string $uid): array {
        return self::run(function () use ($uid): array {
            return Auth::delete_user($uid);
        });
    }

    /**
     * returns a user through srv mode
     *
     * @param string $uid
     * @return array
     */
    public static function getUser(string $uid): array {
        return self::run(function () use ($uid): array {
            return Auth::get_user($uid);
        });
    }

    /**
     * returns a user by jwt through srv mode
     *
     * @param string $jwt
     * @return array
     */
    public static function getJwtUser(string $jwt): array {
        return self::run(function () use ($jwt): array {
            return Auth::get_jwt_user($jwt);
        });
    }

    /**
     * returns users through srv mode
     *
     * @param int $limit
     * @return array
     */
    public static function getUsers(int $limit = 10000000): array {
        return self::run(function () use ($limit): array {
            return Auth::getUsers($limit);
        });
    }
}

?>