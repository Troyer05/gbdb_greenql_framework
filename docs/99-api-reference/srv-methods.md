# SRV / SecondServer Public Methods

### `Srv`

File: `SRV/Srv.php`

| Method | Signature | Typical use |
|---|---|---|
| `ensureInstance()` | `public static function ensureInstance()` | See class section; use when the method name matches the required operation. |
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `setInstance()` | `public static function setInstance(string $instance)` | Write/change storage or configuration; validate input and permissions first. |
| `respond()` | `public static function respond(int $status, mixed $data)` | See class section; use when the method name matches the required operation. |
| `testParams()` | `public static function testParams(array $params)` | See class section; use when the method name matches the required operation. |

### `SrvFunctions`

File: `SRV/SrvFunctions.php`

| Method | Signature | Typical use |
|---|---|---|
| `setInstance()` | `public static function setInstance(string $instance)` | Write/change storage or configuration; validate input and permissions first. |
| `ping()` | `public static function ping()` | See class section; use when the method name matches the required operation. |
| `getData()` | `public static function getData()` | Read/list data without changing storage. |
| `registerUser()` | `public static function registerUser()` | Authentication/user workflows. |
| `login()` | `public static function login()` | Authentication/user workflows. |
| `logout()` | `public static function logout()` | See class section; use when the method name matches the required operation. |
| `init_auth()` | `public static function init_auth()` | Authentication/user workflows. |
| `verifyEmail()` | `public static function verifyEmail()` | See class section; use when the method name matches the required operation. |
| `verify2fa()` | `public static function verify2fa()` | See class section; use when the method name matches the required operation. |
| `deleteUser()` | `public static function deleteUser()` | Write/change storage or configuration; validate input and permissions first. |
| `editUser()` | `public static function editUser()` | Write/change storage or configuration; validate input and permissions first. |
| `getUser()` | `public static function getUser()` | Read/list data without changing storage. |
| `getJwtUser()` | `public static function getJwtUser()` | Read/list data without changing storage. |
| `getUsers()` | `public static function getUsers()` | Read/list data without changing storage. |
| `driver()` | `public static function driver()` | See class section; use when the method name matches the required operation. |

### `SrvAuth`

File: `SRV/srv_auth.php`

| Method | Signature | Typical use |
|---|---|---|
| `userRegistration()` | `public static function userRegistration(string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "")` | Authentication/user workflows. |
| `login()` | `public static function login(string $usernameOrEmail, string $passwordAsPlainText)` | Authentication/user workflows. |
| `logout()` | `public static function logout(string $error = "")` | See class section; use when the method name matches the required operation. |
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `verify_2fa_code()` | `public static function verify_2fa_code(string|int $code)` | See class section; use when the method name matches the required operation. |
| `verify_email()` | `public static function verify_email(string $token)` | See class section; use when the method name matches the required operation. |
| `editUser()` | `public static function editUser(string $uid, string $username, string $email, string $passwordAsPlain = "", bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "", string $text = "")` | Write/change storage or configuration; validate input and permissions first. |
| `deleteUser()` | `public static function deleteUser(string $uid)` | Write/change storage or configuration; validate input and permissions first. |
| `getUser()` | `public static function getUser(string $uid)` | Read/list data without changing storage. |
| `getJwtUser()` | `public static function getJwtUser(string $jwt)` | Read/list data without changing storage. |
| `getUsers()` | `public static function getUsers(int $limit = 10000000)` | Read/list data without changing storage. |

### `for, SRVJob`

File: `SRV/srv_modules/SRVJob.php`

| Method | Signature | Typical use |
|---|---|---|
| `setLogfile()` | `public static function setLogfile(string $filename)` | Write/change storage or configuration; validate input and permissions first. |
| `log()` | `public static function log(string $log)` | See class section; use when the method name matches the required operation. |
| `setParams()` | `public static function setParams(array $params)` | Write/change storage or configuration; validate input and permissions first. |
| `getParams()` | `public static function getParams()` | Read/list data without changing storage. |
| `hasParam()` | `public static function hasParam(string $param)` | See class section; use when the method name matches the required operation. |
| `getParam()` | `public static function getParam(string $param, mixed $default = null)` | Read/list data without changing storage. |
| `getString()` | `public static function getString(string $param, string $default = "")` | Read/list data without changing storage. |
| `getInt()` | `public static function getInt(string $param, int $default = 0)` | Read/list data without changing storage. |
| `getBool()` | `public static function getBool(string $param, bool $default = false)` | Read/list data without changing storage. |
| `returnOk()` | `public static function returnOk(string $message, array $data = [])` | See class section; use when the method name matches the required operation. |
| `returnError()` | `public static function returnError(string $message, array $data = [])` | See class section; use when the method name matches the required operation. |

### `SRVJob_Scripts`

File: `SRV/srv_modules/run_script.php`

| Method | Signature | Typical use |
|---|---|---|
| `__start_job()` | `public static function __start_job(array $params = [])` | See class section; use when the method name matches the required operation. |

### `name, SRVJob, SRVJob_Test`

File: `SRV/srv_modules/test_job.php`

| Method | Signature | Typical use |
|---|---|---|
| `__start_job()` | `public static function __start_job()` | See class section; use when the method name matches the required operation. |

### `SecondServer`

File: `core/srvp.php`

| Method | Signature | Typical use |
|---|---|---|
| `ping()` | `public static function ping()` | See class section; use when the method name matches the required operation. |
| `driver()` | `public static function driver()` | See class section; use when the method name matches the required operation. |
| `startJob()` | `public static function startJob(string $job, array $parameters = [])` | See class section; use when the method name matches the required operation. |
| `listJobs()` | `public static function listJobs()` | See class section; use when the method name matches the required operation. |
| `registerUser()` | `public static function registerUser(array $data)` | Authentication/user workflows. |
| `login()` | `public static function login(string $user, string $password)` | Authentication/user workflows. |
| `loginUser()` | `public static function loginUser(array $data)` | Authentication/user workflows. |
| `logout()` | `public static function logout()` | See class section; use when the method name matches the required operation. |
| `initAuth()` | `public static function initAuth()` | Authentication/user workflows. |
| `verifyEmail()` | `public static function verifyEmail(string $token)` | See class section; use when the method name matches the required operation. |
| `verify2fa()` | `public static function verify2fa(string|int $code)` | See class section; use when the method name matches the required operation. |
| `deleteUser()` | `public static function deleteUser(string $uid)` | Write/change storage or configuration; validate input and permissions first. |
| `editUser()` | `public static function editUser(string $uid, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `getUser()` | `public static function getUser(string $uid)` | Read/list data without changing storage. |
| `getJwtUser()` | `public static function getJwtUser(string $jwt)` | Read/list data without changing storage. |
| `getUsers()` | `public static function getUsers(int $limit = 10000000)` | Read/list data without changing storage. |
| `userAuth()` | `public static function userAuth(string $do, array $data = [])` | Authentication/user workflows. |
