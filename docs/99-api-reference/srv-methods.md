# SecondServer – Client, Backend und Aufträge

Anwendungen verwenden den Client SecondServer. Srv, SrvFunctions und SrvAuth sind Backend-Bausteine; SRVJob stellt Hilfen für Auftragsmodule bereit.

[Gesamtindex](all-public-methods.md) · [Handbuch](../README.md)

Automatisch aus PHP-Deklarationen erzeugt; Signaturen enthalten die im Quelltext angegebenen Parameter, Standardwerte und Rückgabetypen.

## SecondServer

Klasse · Quelle: [core/srvp.php](../../PHP/gbdb_framework/core/srvp.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `ping()` | `public static function ping() :bool` | [Zeile 81](../../PHP/gbdb_framework/core/srvp.php#L81) |
| `driver()` | `public static function driver() :array` | [Zeile 93](../../PHP/gbdb_framework/core/srvp.php#L93) |
| `startJob()` | `public static function startJob(string $job, array $parameters = []) :array` | [Zeile 106](../../PHP/gbdb_framework/core/srvp.php#L106) |
| `listJobs()` | `public static function listJobs() :array` | [Zeile 118](../../PHP/gbdb_framework/core/srvp.php#L118) |
| `registerUser()` | `public static function registerUser(array $data) :array` | [Zeile 130](../../PHP/gbdb_framework/core/srvp.php#L130) |
| `login()` | `public static function login(string $user, string $password) :array` | [Zeile 144](../../PHP/gbdb_framework/core/srvp.php#L144) |
| `loginUser()` | `public static function loginUser(array $data) :array` | [Zeile 160](../../PHP/gbdb_framework/core/srvp.php#L160) |
| `logout()` | `public static function logout(string $jwt = '') :array` | [Zeile 172](../../PHP/gbdb_framework/core/srvp.php#L172) |
| `initAuth()` | `public static function initAuth() :array` | [Zeile 184](../../PHP/gbdb_framework/core/srvp.php#L184) |
| `verifyEmail()` | `public static function verifyEmail(string $token) :array` | [Zeile 196](../../PHP/gbdb_framework/core/srvp.php#L196) |
| `verify2fa()` | `public static function verify2fa(string\|int $code) :array` | [Zeile 209](../../PHP/gbdb_framework/core/srvp.php#L209) |
| `deleteUser()` | `public static function deleteUser(string $uid) :array` | [Zeile 222](../../PHP/gbdb_framework/core/srvp.php#L222) |
| `editUser()` | `public static function editUser(string $uid, array $data) :array` | [Zeile 236](../../PHP/gbdb_framework/core/srvp.php#L236) |
| `getUser()` | `public static function getUser(string $uid) :array` | [Zeile 250](../../PHP/gbdb_framework/core/srvp.php#L250) |
| `getJwtUser()` | `public static function getJwtUser(string $jwt) :array` | [Zeile 263](../../PHP/gbdb_framework/core/srvp.php#L263) |
| `getUsers()` | `public static function getUsers(int $limit = 10000000) :array` | [Zeile 276](../../PHP/gbdb_framework/core/srvp.php#L276) |
| `userAuth()` | `public static function userAuth(string $do, array $data = []) :array` | [Zeile 290](../../PHP/gbdb_framework/core/srvp.php#L290) |

## Srv

Klasse · Quelle: [SRV/Srv.php](../../PHP/gbdb_framework/SRV/Srv.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `ensureInstance()` | `public static function ensureInstance() :void` | [Zeile 34](../../PHP/gbdb_framework/SRV/Srv.php#L34) |
| `init()` | `public static function init() :void` | [Zeile 51](../../PHP/gbdb_framework/SRV/Srv.php#L51) |
| `setInstance()` | `public static function setInstance(string $instance) :void` | [Zeile 135](../../PHP/gbdb_framework/SRV/Srv.php#L135) |
| `respond()` | `public static function respond(int $status, mixed $data) :void` | [Zeile 158](../../PHP/gbdb_framework/SRV/Srv.php#L158) |
| `testParams()` | `public static function testParams(array $params) :void` | [Zeile 177](../../PHP/gbdb_framework/SRV/Srv.php#L177) |

## SrvFunctions

Klasse · Quelle: [SRV/SrvFunctions.php](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `setInstance()` | `public static function setInstance(string $instance) :void` | [Zeile 105](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L105) |
| `ping()` | `public static function ping() :void` | [Zeile 123](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L123) |
| `getData()` | `public static function getData() :void` | [Zeile 132](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L132) |
| `registerUser()` | `public static function registerUser() :void` | [Zeile 148](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L148) |
| `login()` | `public static function login() :void` | [Zeile 175](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L175) |
| `logout()` | `public static function logout() :void` | [Zeile 191](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L191) |
| `init_auth()` | `public static function init_auth() :void` | [Zeile 202](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L202) |
| `verifyEmail()` | `public static function verifyEmail() :void` | [Zeile 213](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L213) |
| `verify2fa()` | `public static function verify2fa() :void` | [Zeile 226](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L226) |
| `deleteUser()` | `public static function deleteUser() :void` | [Zeile 239](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L239) |
| `editUser()` | `public static function editUser() :void` | [Zeile 252](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L252) |
| `getUser()` | `public static function getUser() :void` | [Zeile 283](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L283) |
| `getJwtUser()` | `public static function getJwtUser() :void` | [Zeile 296](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L296) |
| `getUsers()` | `public static function getUsers() :void` | [Zeile 309](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L309) |
| `driver()` | `public static function driver() :void` | [Zeile 321](../../PHP/gbdb_framework/SRV/SrvFunctions.php#L321) |

## SrvAuth

Klasse · Quelle: [SRV/srv_auth.php](../../PHP/gbdb_framework/SRV/srv_auth.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `userRegistration()` | `public static function userRegistration( string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "" ) :array` | [Zeile 32](../../PHP/gbdb_framework/SRV/srv_auth.php#L32) |
| `login()` | `public static function login(string $usernameOrEmail, string $passwordAsPlainText) :array` | [Zeile 87](../../PHP/gbdb_framework/SRV/srv_auth.php#L87) |
| `logout()` | `public static function logout(string $error = "", string $jwt = "") :array` | [Zeile 99](../../PHP/gbdb_framework/SRV/srv_auth.php#L99) |
| `init()` | `public static function init() :array` | [Zeile 110](../../PHP/gbdb_framework/SRV/srv_auth.php#L110) |
| `verify_2fa_code()` | `public static function verify_2fa_code(string\|int $code) :array` | [Zeile 122](../../PHP/gbdb_framework/SRV/srv_auth.php#L122) |
| `verify_email()` | `public static function verify_email(string $token) :array` | [Zeile 134](../../PHP/gbdb_framework/SRV/srv_auth.php#L134) |
| `editUser()` | `public static function editUser( string $uid, string $username, string $email, string $passwordAsPlain = "", bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "", string $text = "" ) :array` | [Zeile 160](../../PHP/gbdb_framework/SRV/srv_auth.php#L160) |
| `deleteUser()` | `public static function deleteUser(string $uid) :array` | [Zeile 220](../../PHP/gbdb_framework/SRV/srv_auth.php#L220) |
| `getUser()` | `public static function getUser(string $uid) :array` | [Zeile 232](../../PHP/gbdb_framework/SRV/srv_auth.php#L232) |
| `getJwtUser()` | `public static function getJwtUser(string $jwt) :array` | [Zeile 244](../../PHP/gbdb_framework/SRV/srv_auth.php#L244) |
| `getUsers()` | `public static function getUsers(int $limit = 10000000) :array` | [Zeile 256](../../PHP/gbdb_framework/SRV/srv_auth.php#L256) |

## SRVJob

Klasse · Quelle: [SRV/srv_modules/SRVJob.php](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L9)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `setLogfile()` | `public static function setLogfile(string $filename) :void` | [Zeile 45](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L45) |
| `log()` | `public static function log(string $log) :void` | [Zeile 65](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L65) |
| `setParams()` | `public static function setParams(array $params) :void` | [Zeile 79](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L79) |
| `getParams()` | `public static function getParams() :array` | [Zeile 88](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L88) |
| `hasParam()` | `public static function hasParam(string $param) :bool` | [Zeile 98](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L98) |
| `getParam()` | `public static function getParam(string $param, mixed $default = null) :mixed` | [Zeile 109](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L109) |
| `getString()` | `public static function getString(string $param, string $default = "") :string` | [Zeile 124](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L124) |
| `getInt()` | `public static function getInt(string $param, int $default = 0) :int` | [Zeile 135](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L135) |
| `getBool()` | `public static function getBool(string $param, bool $default = false) :bool` | [Zeile 146](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L146) |
| `returnOk()` | `public static function returnOk(string $message, array $data = []) :array` | [Zeile 175](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L175) |
| `returnError()` | `public static function returnError(string $message, array $data = []) :array` | [Zeile 192](../../PHP/gbdb_framework/SRV/srv_modules/SRVJob.php#L192) |

## SRVJob_Scripts

Klasse · Quelle: [SRV/srv_modules/run_script.php](../../PHP/gbdb_framework/SRV/srv_modules/run_script.php#L3)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__start_job()` | `public static function __start_job(array $params = []) :array` | [Zeile 4](../../PHP/gbdb_framework/SRV/srv_modules/run_script.php#L4) |

## SRVJob_Test

Klasse · Quelle: [SRV/srv_modules/test_job.php](../../PHP/gbdb_framework/SRV/srv_modules/test_job.php#L17)

| Methode / Funktion | Deklarierte Signatur | Quelle |
| --- | --- | --- |
| `__start_job()` | `public static function __start_job() :array` | [Zeile 18](../../PHP/gbdb_framework/SRV/srv_modules/test_job.php#L18) |

