<?php

declare(strict_types=1);

/** Atomic app-auth workflows over the active GBDB backend. */
class Auth extends GBDBAuthEngine {
    public static function createStructure() :array {
        return GBDBRuntime::run(static fn() => parent::createStructure());
    }

    public static function logout(string $error = '', string $jwt = '') :array {
        return GBDBRuntime::run(static fn() => parent::logout($error, $jwt));
    }

    public static function init() :array {
        return GBDBRuntime::run(static fn() => parent::init());
    }

    public static function login(string $usernameOrEmail, string $passwordPlainText) :array {
        return GBDBRuntime::run(static fn() => parent::login($usernameOrEmail, $passwordPlainText));
    }

    public static function user_registration(string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = 'user', string $firstname = '', string $lastname = '', string $adress = '', string $telephone = '', string $mobile = '', bool $gender = false, string $image = '', bool $verifyMail = true) :array {
        return GBDBRuntime::run(static fn() => parent::user_registration($username, $email, $passwordAsPlain, $active, $tfa, $role, $firstname, $lastname, $adress, $telephone, $mobile, $gender, $image, $verifyMail));
    }

    public static function verify_email(string $token) :array {
        return GBDBRuntime::run(static fn() => parent::verify_email($token));
    }

    public static function verify_2fa_code(string|int $code) :array {
        return GBDBRuntime::run(static fn() => parent::verify_2fa_code($code));
    }

    public static function edit_user(string $uid, string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = 'user', string $firstname = '', string $lastname = '', string $adress = '', string $telephone = '', string $mobile = '', bool $gender = false, string $image = '', string $text = '') :array {
        return GBDBRuntime::run(static fn() => parent::edit_user($uid, $username, $email, $passwordAsPlain, $active, $tfa, $role, $firstname, $lastname, $adress, $telephone, $mobile, $gender, $image, $text));
    }

    public static function delete_user(string $uid) :array {
        return GBDBRuntime::run(static fn() => parent::delete_user($uid));
    }

    public static function get_user(string $uid) :array {
        return GBDBRuntime::run(static fn() => parent::get_user($uid));
    }

    public static function get_jwt_user(string $jwt) :array {
        return GBDBRuntime::run(static fn() => parent::get_jwt_user($jwt));
    }

    public static function getUsers(int $limit = 10000000) :array {
        return GBDBRuntime::run(static fn() => parent::getUsers($limit));
    }
    public static function resendVerification(string $usernameOrEmail) :array {
        return GBDBRuntime::run(static fn() => parent::resendVerification($usernameOrEmail));
    }
}
