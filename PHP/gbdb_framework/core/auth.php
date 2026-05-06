<?php

class Auth {
    private const INSTANCE = "AUTH";
    private const USER_TABLE_SCHEMA = ["uid", "username", "email", "password", "active", "rolle", "datum", "tfa"];
    private const JWT_SCHEMA = ["uid", "token", "exp"];
    private const MAIL_VERIFY_SCHEMA = ["uid", "token", "exp"];
    private const PWF_SCHEMA = ["uid", "token", "exp"];
    private const TFA_SCHEMA = ["uid", "code", "exp"];
    private const USER_META_SCHEMA = ["uid", "vorname", "nachname", "telefon", "mobil", "adresse", "gender", "bio", "image"];

    /**
     * Liefert den Namen der Auth-Datenbank.
     * @return string Rückgabewert.
     */
    private static function db(): string {
        return Vars::AUTH()["main_db"];
    }

    /**
     * Liefert den Namen des JWT-Cookies.
     * @return string Rückgabewert.
     */
    private static function jwtCookie(): string {
        return Vars::AUTH()["jwt_cookie_name"];
    }

    /**
     * Startet eine Session, falls noch keine aktiv ist.
     * @return void Rückgabewert.
     */
    private static function session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Fügt neue Daten ein.
     * @param string $table Tabelle.
     * @param array $obj Daten.
     * @return void Rückgabewert.
     */
    private static function insert(string $table, array $obj): void {
        GBDB::insertData(self::db(), $table, $obj);
    }

    /**
     * Bearbeitet bestehende Daten.
     * @param string $table Tabelle.
     * @param string $where Suchfeld.
     * @param string $is Suchwert.
     * @param array $obj Daten.
     * @return void Rückgabewert.
     */
    private static function edit(string $table, string $where, string $is, array $obj): void {
        GBDB::editData(self::db(), $table, $where, $is, $obj);
    }

    /**
     * Leitet weiter, falls ein Ziel angegeben wurde.
     * @param string $file Ziel.
     * @return void Rückgabewert.
     */
    private static function redirect(string $file): void {
        if ($file == "") {
            return;
        }

        Ref::to($file);
    }

    /**
     * Prüft, ob ein Ablaufzeitpunkt abgelaufen ist.
     * @param string $exp Ablaufzeit.
     * @return bool Rückgabewert.
     */
    private static function expired(string $exp): bool {
        return $exp == "" || time() >= (int)$exp;
    }

    /**
     * Erzeugt einen Ablaufzeitpunkt für normale Tokens.
     * @return string Rückgabewert.
     */
    private static function expires(): string {
        $days = (int)(Vars::AUTH()["token_expires_days"] ?? 2);

        if ($days <= 0) {
            $days = 2;
        }

        return (string)(time() + ($days * 24 * 60 * 60));
    }

    /**
     * Erzeugt einen Ablaufzeitpunkt für 2FA-Codes.
     * @return string Rückgabewert.
     */
    private static function tfaExpires(): string {
        $minutes = (int)(Vars::AUTH()["tfa_expires_minutes"] ?? 10);

        if ($minutes <= 0) {
            $minutes = 10;
        }

        return (string)(time() + ($minutes * 60));
    }

    /**
     * Wandelt typische Werte in bool um.
     * @param mixed $value Wert.
     * @return bool Rückgabewert.
     */
    private static function boolValue(mixed $value): bool {
        return $value === true || $value === 1 || $value === "1" || $value === "true" || $value === "yes" || $value === "on";
    }

    /**
     * Prüft, ob ein Wert wie ein gespeicherter Hash aussieht.
     * @param string $pass Passwort oder Hash.
     * @return bool Rückgabewert.
     */
    private static function isHash(string $pass): bool {
        return strlen($pass) == 64 && ctype_xdigit($pass);
    }

    /**
     * Normalisiert ein Passwort für Speicherung.
     * @param string $pass Passwort oder Hash.
     * @return string Rückgabewert.
     */
    private static function passwordValue(string $pass): string {
        if ($pass == "") {
            return "";
        }

        if (self::isHash($pass)) {
            return $pass;
        }

        return self::hashPass($pass);
    }

    /**
     * Normalisiert ein GBDB-Ergebnis auf den ersten Datensatz.
     * @param array $data Daten.
     * @return array Rückgabewert.
     */
    private static function firstRow(array $data): array {
        if (empty($data)) {
            return [];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return $data;
    }

    /**
     * Liest eine HTML-Mail-Datei.
     * @param string $path_with_file Datei.
     * @return string Rückgabewert.
     */
    private static function readEmailHtmlFile(string $path_with_file): string {
        if (!is_file($path_with_file)) {
            throw new Exception("email html file not found: " . $path_with_file);
        }

        if (!is_readable($path_with_file)) {
            throw new Exception("email html file not readable: " . $path_with_file);
        }

        $content = file_get_contents($path_with_file);

        if ($content === false) {
            throw new Exception("error reading email html file: " . $path_with_file);
        }

        return $content;
    }

    /**
     * Holt Benutzer- und Meta-Daten zusammen.
     * @param string $uid Benutzer-ID.
     * @return array Rückgabewert.
     */
    private static function getUserFull(string $uid): array {
        if ($uid == "") {
            return [];
        }

        $user = self::firstRow(self::get("users", "uid", $uid));
        $meta = self::firstRow(self::get("meta", "uid", $uid));

        if (empty($user)) {
            return [];
        }

        return array_merge($meta, $user);
    }

    /**
     * Ersetzt Variablen in Mail-Vorlagen.
     * @param string $content Inhalt.
     * @param array $user Benutzer.
     * @param array $extra Zusätzliche Werte.
     * @return string Rückgabewert.
     */
    private static function replaceMailVars(string $content, array $user, array $extra = []): string {
        $vars = array_merge([
            "#vorname" => $user["vorname"] ?? "",
            "#nachname" => $user["nachname"] ?? "",
            "#username" => $user["username"] ?? "",
            "#email" => $user["email"] ?? "",
            "#telefon" => $user["telefon"] ?? "",
            "#mobil" => $user["mobil"] ?? "",
            "#adresse" => $user["adresse"] ?? "",
            "#datum" => date("d.m.Y")
        ], $extra);

        return str_replace(array_keys($vars), array_values($vars), $content);
    }

    /**
     * Versendet eine Mail über die Framework-Mailfunktion.
     * @param array $mail Mail-Daten.
     * @return void Rückgabewert.
     */
    private static function mail(array $mail): void {
        Http::sendMail([
            "to_name" => $mail["to_name"] ?? "",
            "to_email" => $mail["to_email"] ?? "",
            "from_name" => Vars::AUTH()["email_config"]["from_name"] ?? "",
            "from_email" => Vars::AUTH()["email_config"]["from_email"] ?? "",
            "subject" => $mail["subject"] ?? "",
            "mail_content" => $mail["mail_content"] ?? ""
        ]);
    }

    /**
     * Versendet eine Verifizierungs-Mail.
     * @param string $uid Benutzer-ID.
     * @return void Rückgabewert.
     */
    private static function sendVerifyMail(string $uid): void {
        $user = self::getUserFull($uid);

        if (empty($user)) {
            return;
        }

        $token = self::newVerifyToken();
        $link = (Vars::AUTH()["email_config"]["verify_link"] ?? "") . $token;

        self::insert("mailv", [
            "uid" => $uid,
            "token" => $token,
            "exp" => self::expires()
        ]);

        $content = self::readEmailHtmlFile(Vars::AUTH()["email_config"]["mail_verify"]);
        $content = self::replaceMailVars($content, $user, ["#link" => $link]);

        self::mail([
            "to_name" => trim(($user["vorname"] ?? "") . " " . ($user["nachname"] ?? "")),
            "to_email" => $user["email"] ?? "",
            "subject" => Vars::AUTH()["email_config"]["subject_verify"] ?? "E-Mail bestätigen",
            "mail_content" => $content
        ]);
    }

    /**
     * Versendet eine 2FA-Mail.
     * @param string $uid Benutzer-ID.
     * @return void Rückgabewert.
     */
    private static function send2FaMail(string $uid): void {
        $user = self::getUserFull($uid);

        if (empty($user)) {
            return;
        }

        foreach (self::get("tfa") as $t) {
            if (($t["uid"] ?? "") == $uid) {
                self::delete("tfa", "uid", $uid);
                break;
            }
        }

        $code = self::new2FaCode();

        self::insert("tfa", [
            "uid" => $uid,
            "code" => $code,
            "exp" => self::tfaExpires()
        ]);

        $content = self::readEmailHtmlFile(Vars::AUTH()["email_config"]["mail_2fa"]);
        $content = self::replaceMailVars($content, $user, ["#code" => $code]);

        self::mail([
            "to_name" => trim(($user["vorname"] ?? "") . " " . ($user["nachname"] ?? "")),
            "to_email" => $user["email"] ?? "",
            "subject" => Vars::AUTH()["email_config"]["subject_2fa"] ?? "2FA Code",
            "mail_content" => $content
        ]);
    }

    /**
     * Erzeugt einen eindeutigen 2FA-Code.
     * @return string Rückgabewert.
     */
    private static function new2FaCode(): string {
        do {
            $retry = false;
            $code = (string)random_int(100000, 999999);

            foreach (self::get("tfa") as $t) {
                if (self::expired($t["exp"] ?? "")) {
                    self::delete("tfa", "id", (string)($t["id"] ?? ""));
                    continue;
                }

                if (($t["code"] ?? "") == $code) {
                    $retry = true;
                    break;
                }
            }
        } while ($retry);

        return $code;
    }

    /**
     * Erzeugt einen eindeutigen Mail-Verifizierungstoken.
     * @return string Rückgabewert.
     */
    private static function newVerifyToken(): string {
        do {
            $retry = false;
            $token = bin2hex(random_bytes(32));

            foreach (self::get("mailv") as $m) {
                if (self::expired($m["exp"] ?? "")) {
                    self::delete("mailv", "id", (string)($m["id"] ?? ""));
                    continue;
                }

                if (($m["token"] ?? "") == $token) {
                    $retry = true;
                    break;
                }
            }
        } while ($retry);

        return $token;
    }

    /**
     * Erzeugt einen neuen JWT.
     * @param string $uid Benutzer-ID.
     * @return string Rückgabewert.
     */
    private static function newJWT(string $uid): string {
        do {
            $retry = false;
            $jwt = bin2hex(random_bytes(32));

            foreach (self::get("jwt") as $j) {
                if (self::expired($j["exp"] ?? "")) {
                    self::delete("jwt", "id", (string)($j["id"] ?? ""));
                    continue;
                }

                if (($j["uid"] ?? "") == $uid) {
                    self::delete("jwt", "uid", $uid);
                    continue;
                }

                if (($j["token"] ?? "") == $jwt) {
                    $retry = true;
                    break;
                }
            }
        } while ($retry);

        self::insert("jwt", [
            "uid" => $uid,
            "token" => $jwt,
            "exp" => self::expires()
        ]);

        return $jwt;
    }

    /**
     * Erzeugt eine eindeutige Benutzer-ID.
     * @return string Rückgabewert.
     */
    private static function newUID(): string {
        do {
            $retry = false;
            $uid = bin2hex(random_bytes(32));

            foreach (self::get("users") as $u) {
                if (($u["uid"] ?? "") == $uid) {
                    $retry = true;
                    break;
                }
            }
        } while ($retry);

        return $uid;
    }

    /**
     * Prüft, ob die aktuelle Datei ohne Login erreichbar ist.
     * @return bool Rückgabewert.
     */
    private static function isNoLoginFile(): bool {
        foreach ((Vars::AUTH()["files_no_login"] ?? []) as $file) {
            if ($file != "" && str_contains(Vars::this_file(), $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft die aktuelle lokale Authentifizierung.
     * @return array Rückgabewert.
     */
    private static function auth(): array {
        if (self::isNoLoginFile()) {
            return [];
        }

        if (!Cookie::exists(self::jwtCookie())) {
            self::logout();
            return [];
        }

        $jwt = Cookie::get(self::jwtCookie());
        $check = self::authByToken($jwt);

        if ($check["ok"]) {
            return $check["user"];
        }

        self::logout();
        return [];
    }

    /**
     * Prüft auf doppelte Benutzer.
     * @param string $username Benutzername.
     * @param string $email E-Mail.
     * @param string $uid Auszuschließende Benutzer-ID.
     * @return string Rückgabewert.
     */
    private static function doubleUser(string $username, string $email, string $uid = ""): string {
        foreach (self::get("users") as $u) {
            if ($uid != "" && ($u["uid"] ?? "") == $uid) {
                continue;
            }

            if ($username != "" && $username == ($u["username"] ?? "")) {
                return "Benutzername bereits vergeben";
            }

            if ($email != "" && $email == ($u["email"] ?? "")) {
                return "E-Mail Adresse bereits vorhanden";
            }
        }

        return "";
    }

    /**
     * Baut ein Benutzer-Objekt.
     * @param string $uid Benutzer-ID.
     * @param array $user_data Benutzerdaten.
     * @param bool $new Neuer Benutzer.
     * @return array Rückgabewert.
     */
    private static function userObj(string $uid, array $user_data, bool $new = false): array {
        $current = [];

        if (!$new) {
            $current = self::firstRow(self::get("users", "uid", $uid));
        }

        $obj = [
            "uid" => $uid,
            "username" => $user_data["username"] ?? ($current["username"] ?? ""),
            "email" => $user_data["email"] ?? ($current["email"] ?? ""),
            "active" => $user_data["active"] ?? ($current["active"] ?? false),
            "rolle" => $user_data["rolle"] ?? ($current["rolle"] ?? "user"),
            "datum" => $current["datum"] ?? ($user_data["datum"] ?? date("d.m.Y")),
            "tfa" => $user_data["tfa"] ?? ($current["tfa"] ?? false)
        ];

        if ($new || array_key_exists("password", $user_data)) {
            $obj["password"] = self::passwordValue((string)($user_data["password"] ?? ""));
        } else {
            $obj["password"] = $current["password"] ?? "";
        }

        return $obj;
    }

    /**
     * Baut ein Meta-Objekt.
     * @param string $uid Benutzer-ID.
     * @param array $user_meta Meta-Daten.
     * @param bool $new Neuer Benutzer.
     * @return array Rückgabewert.
     */
    private static function metaObj(string $uid, array $user_meta, bool $new = false): array {
        $current = [];

        if (!$new) {
            $current = self::firstRow(self::get("meta", "uid", $uid));
        }

        return [
            "uid" => $uid,
            "vorname" => $user_meta["vorname"] ?? ($current["vorname"] ?? ""),
            "nachname" => $user_meta["nachname"] ?? ($current["nachname"] ?? ""),
            "telefon" => $user_meta["telefon"] ?? ($current["telefon"] ?? ""),
            "mobil" => $user_meta["mobil"] ?? ($current["mobil"] ?? ""),
            "adresse" => $user_meta["adresse"] ?? ($current["adresse"] ?? ""),
            "gender" => $user_meta["gender"] ?? ($current["gender"] ?? ""),
            "bio" => $user_meta["bio"] ?? ($current["bio"] ?? ""),
            "image" => $user_meta["image"] ?? ($current["image"] ?? "")
        ];
    }

    /**
     * Legt benötigte Tabellen an.
     * @return void Rückgabewert.
     */
    private static function initTables(): void {
        if (!in_array(self::db(), GBDB::listDBs(), true)) {
            GBDB::createDatabase(self::db());
        }

        $tables = GBDB::listTables(self::db());

        if (!in_array("users", $tables, true)) {
            GBDB::createTable(self::db(), "users", self::USER_TABLE_SCHEMA);
        }

        if (!in_array("jwt", $tables, true)) {
            GBDB::createTable(self::db(), "jwt", self::JWT_SCHEMA);
        }

        if (!in_array("mailv", $tables, true)) {
            GBDB::createTable(self::db(), "mailv", self::MAIL_VERIFY_SCHEMA);
        }

        if (!in_array("pwf", $tables, true)) {
            GBDB::createTable(self::db(), "pwf", self::PWF_SCHEMA);
        }

        if (!in_array("tfa", $tables, true)) {
            GBDB::createTable(self::db(), "tfa", self::TFA_SCHEMA);
        }

        if (!in_array("meta", $tables, true)) {
            GBDB::createTable(self::db(), "meta", self::USER_META_SCHEMA);
        }

        $rootUser = Vars::AUTH()["root_user"] ?? [];
        $rootMeta = Vars::AUTH()["root_user_meta"] ?? [];

        if (!empty($rootUser["uid"]) && empty(self::get("users", "uid", $rootUser["uid"]))) {
            self::insert("users", self::userObj($rootUser["uid"], $rootUser, true));
        }

        if (!empty($rootMeta["uid"]) && empty(self::get("meta", "uid", $rootMeta["uid"]))) {
            self::insert("meta", self::metaObj($rootMeta["uid"], $rootMeta, true));
        }
    }

    /**
     * Prüft Login-Daten zentral für lokalen und remote Login.
     * @param string $username_or_email Benutzername oder E-Mail.
     * @param string $plain_text_password Klartext-Passwort.
     * @param bool $remote Remote-Modus.
     * @return array Rückgabewert.
     */
    private static function loginCore(string $username_or_email, string $plain_text_password, bool $remote = false): array {
        $err = "Keine Benutzer in der Datenbank";

        foreach (self::get("users") as $u) {
            $err = "Benutzer nicht gefunden";

            if (($u["username"] ?? "") != $username_or_email && ($u["email"] ?? "") != $username_or_email) {
                continue;
            }

            $err = "Passwort falsch";

            if (($u["password"] ?? "") != self::hashPass($plain_text_password)) {
                continue;
            }

            $err = "Benutzer deaktiviert oder E-Mail nicht verifiziert";

            if (!self::boolValue($u["active"] ?? false)) {
                continue;
            }

            if (self::boolValue($u["tfa"] ?? false)) {
                self::send2FaMail($u["uid"]);

                return [
                    "ok" => false,
                    "tfa" => true,
                    "uid" => $u["uid"],
                    "msg" => "2FA Code wurde versendet"
                ];
            }

            $jwt = self::newJWT($u["uid"]);

            return [
                "ok" => true,
                "tfa" => false,
                "msg" => "Login erfolgreich",
                "jwt" => $jwt,
                "user" => self::getUserFull($u["uid"])
            ];
        }

        return [
            "ok" => false,
            "tfa" => false,
            "msg" => $err
        ];
    }

    /**
     * Initialisiert die Klasse und prüft lokale Authentifizierung.
     * @return void Rückgabewert.
     */
    public static function init(): void {
        self::initTables();
        self::auth();
    }

    /**
     * Initialisiert Auth ohne lokale Weiterleitung für Remote-Nutzung.
     * @return array Rückgabewert.
     */
    public static function initRemote(): array {
        self::initTables();

        return [
            "ok" => true,
            "msg" => "Auth initialized"
        ];
    }

    /**
     * Erzeugt den Framework-Passwort-Hash.
     * @param string $pass Passwort.
     * @return string Rückgabewert.
     */
    public static function hashPass(string $pass): string {
        return hash("sha256", hash("adler32", hash("md5", hash("sha512", $pass))));
    }

    /**
     * Liest Daten aus der Auth-Datenbank.
     * @param string $table Tabelle.
     * @param string $where Suchfeld.
     * @param string $is Suchwert.
     * @return array Rückgabewert.
     */
    public static function get(string $table, string $where = "", string $is = ""): array {
        if ($where == "") {
            return GBDB::getData(self::db(), $table);
        }

        return GBDB::getData(self::db(), $table, true, $where, $is);
    }

    /**
     * Löscht Daten aus der Auth-Datenbank.
     * @param string $table Tabelle.
     * @param string $where Suchfeld.
     * @param string $is Suchwert.
     * @return void Rückgabewert.
     */
    public static function delete(string $table, string $where, string $is): void {
        if ($is == "") {
            return;
        }

        GBDB::deleteData(self::db(), $table, $where, $is);
    }

    /**
     * Beendet die aktuelle lokale Anmeldung.
     * @return void Rückgabewert.
     */
    public static function logout(): void {
        if (Cookie::exists(self::jwtCookie())) {
            $jwt = Cookie::get(self::jwtCookie());

            Cookie::delete(self::jwtCookie());
            self::delete("jwt", "token", $jwt);
        }

        self::redirect(Vars::AUTH()["logout_file"] ?? "");
    }

    /**
     * Prüft Login-Daten und startet die lokale Anmeldung.
     * @param string $username_or_email Benutzername oder E-Mail.
     * @param string $plain_text_password Klartext-Passwort.
     * @return string Rückgabewert.
     */
    public static function login(string $username_or_email, string $plain_text_password): string {
        $result = self::loginCore($username_or_email, $plain_text_password, false);

        if (($result["tfa"] ?? false) === true) {
            self::session();

            $_SESSION["auth_tfa_uid"] = $result["uid"];
            $_SESSION["auth_tfa_time"] = time();

            return $result["msg"];
        }

        if ($result["ok"]) {
            Cookie::set(self::jwtCookie(), $result["jwt"]);
            self::redirect(Vars::AUTH()["login_file"] ?? "");
            return "";
        }

        return $result["msg"];
    }

    /**
     * Prüft Login-Daten für Remote/API/Srv-Nutzung.
     * @param string $username_or_email Benutzername oder E-Mail.
     * @param string $plain_text_password Klartext-Passwort.
     * @return array Rückgabewert.
     */
    public static function loginRemote(string $username_or_email, string $plain_text_password): array {
        return self::loginCore($username_or_email, $plain_text_password, true);
    }

    /**
     * Schließt einen lokalen 2FA-Login ab.
     * @param string $code 2FA-Code.
     * @return string Rückgabewert.
     */
    public static function login2Fa(string $code): string {
        self::session();

        if (!isset($_SESSION["auth_tfa_uid"])) {
            return "Keine offene 2FA Anmeldung gefunden";
        }

        $uid = $_SESSION["auth_tfa_uid"];
        $result = self::login2FaRemote($uid, $code);

        if ($result["ok"]) {
            unset($_SESSION["auth_tfa_uid"]);
            unset($_SESSION["auth_tfa_time"]);

            Cookie::set(self::jwtCookie(), $result["jwt"]);
            self::redirect(Vars::AUTH()["login_file"] ?? "");

            return "";
        }

        return $result["msg"];
    }

    /**
     * Schließt einen Remote/API/Srv-2FA-Login ab.
     * @param string $uid Benutzer-ID.
     * @param string $code 2FA-Code.
     * @return array Rückgabewert.
     */
    public static function login2FaRemote(string $uid, string $code): array {
        foreach (self::get("tfa") as $t) {
            if (self::expired($t["exp"] ?? "")) {
                self::delete("tfa", "id", (string)($t["id"] ?? ""));
                continue;
            }

            if (($t["uid"] ?? "") == $uid && ($t["code"] ?? "") == $code) {
                self::delete("tfa", "code", $code);

                $jwt = self::newJWT($uid);

                return [
                    "ok" => true,
                    "msg" => "Login erfolgreich",
                    "jwt" => $jwt,
                    "user" => self::getUserFull($uid)
                ];
            }
        }

        return [
            "ok" => false,
            "msg" => "2FA Code ungültig oder abgelaufen"
        ];
    }

    /**
     * Prüft einen JWT.
     * @param string $jwt Token.
     * @return array Rückgabewert.
     */
    public static function authByToken(string $jwt): array {
        if ($jwt == "") {
            return [
                "ok" => false,
                "msg" => "Token fehlt"
            ];
        }

        foreach (self::get("jwt") as $j) {
            if (self::expired($j["exp"] ?? "")) {
                self::delete("jwt", "id", (string)($j["id"] ?? ""));
                continue;
            }

            if (($j["token"] ?? "") == $jwt) {
                $user = self::getUserFull($j["uid"] ?? "");

                if (empty($user)) {
                    return [
                        "ok" => false,
                        "msg" => "Benutzer nicht gefunden"
                    ];
                }

                return [
                    "ok" => true,
                    "user" => $user
                ];
            }
        }

        return [
            "ok" => false,
            "msg" => "Token ungültig oder abgelaufen"
        ];
    }

    /**
     * Gibt den aktuell eingeloggten Benutzer zurück.
     * @return array Rückgabewert.
     */
    public static function me(): array {
        if (!Cookie::exists(self::jwtCookie())) {
            return [];
        }

        $check = self::authByToken(Cookie::get(self::jwtCookie()));

        if (!$check["ok"]) {
            return [];
        }

        return $check["user"];
    }

    /**
     * Prüft, ob lokal ein Benutzer eingeloggt ist.
     * @return bool Rückgabewert.
     */
    public static function check(): bool {
        return !empty(self::me());
    }

    /**
     * Holt einen Benutzer anhand der UID.
     * @param string $uid Benutzer-ID.
     * @return array Rückgabewert.
     */
    public static function user(string $uid): array {
        return self::getUserFull($uid);
    }

    /**
     * Legt einen neuen Benutzer an.
     * @param array $user_data Benutzerdaten.
     * @param array $user_meta Meta-Daten.
     * @param bool $is_this_register Registrierung.
     * @return string Rückgabewert.
     */
    public static function newUser(array $user_data, array $user_meta, bool $is_this_register = false): string {
        $err = self::doubleUser($user_data["username"] ?? "", $user_data["email"] ?? "");

        if ($err != "") {
            return $err;
        }

        $uid = self::newUID();

        self::insert("users", self::userObj($uid, $user_data, true));
        self::insert("meta", self::metaObj($uid, $user_meta, true));

        if ($is_this_register) {
            self::sendVerifyMail($uid);
        }

        return "";
    }

    /**
     * Bearbeitet einen Benutzer.
     * @param string $uid Benutzer-ID.
     * @param array $user_data Benutzerdaten.
     * @param array $user_meta Meta-Daten.
     * @return string Rückgabewert.
     */
    public static function editUser(string $uid, array $user_data, array $user_meta = []): string {
        $current = self::firstRow(self::get("users", "uid", $uid));

        if (empty($current)) {
            return "Benutzer nicht gefunden";
        }

        $username = $user_data["username"] ?? ($current["username"] ?? "");
        $email = $user_data["email"] ?? ($current["email"] ?? "");

        $err = self::doubleUser($username, $email, $uid);

        if ($err != "") {
            return $err;
        }

        self::edit("users", "uid", $uid, self::userObj($uid, $user_data));

        if (!empty($user_meta)) {
            $meta = self::firstRow(self::get("meta", "uid", $uid));

            if (empty($meta)) {
                self::insert("meta", self::metaObj($uid, $user_meta, true));
            } else {
                self::edit("meta", "uid", $uid, self::metaObj($uid, $user_meta));
            }
        }

        return "";
    }

    /**
     * Verifiziert eine E-Mail-Adresse.
     * @param string $token Token.
     * @return bool Rückgabewert.
     */
    public static function verifyEmail(string $token): bool {
        $ok = false;

        foreach (self::get("mailv") as $m) {
            if (self::expired($m["exp"] ?? "")) {
                self::delete("mailv", "id", (string)($m["id"] ?? ""));
                continue;
            }

            if (($m["token"] ?? "") == $token) {
                self::delete("mailv", "token", $token);
                self::editUser($m["uid"], ["active" => true]);
                $ok = true;
            }
        }

        return $ok;
    }

    /**
     * Prüft einen 2FA-Code ohne Login-Abschluss.
     * @param string $code 2FA-Code.
     * @return bool Rückgabewert.
     */
    public static function verify2FaCode(string $code): bool {
        foreach (self::get("tfa") as $t) {
            if (self::expired($t["exp"] ?? "")) {
                self::delete("tfa", "id", (string)($t["id"] ?? ""));
                continue;
            }

            if (($t["code"] ?? "") == $code) {
                self::delete("tfa", "code", $code);
                return true;
            }
        }

        return false;
    }
}
