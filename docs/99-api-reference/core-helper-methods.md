# Core Helper Public Methods

### `Auth`

File: `core/auth.php`

| Method | Signature | Typical use |
|---|---|---|
| `setSrvUsage()` | `public static function setSrvUsage(bool $usage)` | Write/change storage or configuration; validate input and permissions first. |
| `setReturnInstance()` | `public static function setReturnInstance(string $instance)` | Write/change storage or configuration; validate input and permissions first. |
| `createStructure()` | `public static function createStructure()` | Write/change storage or configuration; validate input and permissions first. |
| `hashPass()` | `public static function hashPass(string $password)` | See class section; use when the method name matches the required operation. |
| `logout()` | `public static function logout(string $error = "")` | See class section; use when the method name matches the required operation. |
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `login()` | `public static function login(string $usernameOrEmail, string $passwordPlainText)` | Authentication/user workflows. |
| `user_registration()` | `public static function user_registration(string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "")` | Authentication/user workflows. |
| `verify_email()` | `public static function verify_email(string $token)` | See class section; use when the method name matches the required operation. |
| `verify_2fa_code()` | `public static function verify_2fa_code(string|int $code)` | See class section; use when the method name matches the required operation. |
| `edit_user()` | `public static function edit_user(string $uid, string $username, string $email, string $passwordAsPlain, bool $active = true, bool $tfa = false, string $role = "user", string $firstname = "", string $lastname = "", string $adress = "", string $telephone = "", string $mobile = "", bool $gender = false, string $image = "", string $text = "")` | Write/change storage or configuration; validate input and permissions first. |
| `delete_user()` | `public static function delete_user(string $uid)` | Write/change storage or configuration; validate input and permissions first. |
| `get_user()` | `public static function get_user(string $uid)` | Read/list data without changing storage. |
| `get_jwt_user()` | `public static function get_jwt_user(string $jwt)` | Read/list data without changing storage. |
| `getUsers()` | `public static function getUsers(int $limit = 10000000)` | Read/list data without changing storage. |

### `Cache`

File: `core/cache.php`

| Method | Signature | Typical use |
|---|---|---|
| `load()` | `public static function load(string $db, string $table, mixed $cache)` | See class section; use when the method name matches the required operation. |
| `update()` | `public static function update(string $db, string $table)` | Write/change storage or configuration; validate input and permissions first. |
| `clear()` | `public static function clear(string $table, ?string $db = null)` | See class section; use when the method name matches the required operation. |
| `flush()` | `public static function flush()` | See class section; use when the method name matches the required operation. |
| `exists()` | `public static function exists(string $table, ?string $db = null)` | See class section; use when the method name matches the required operation. |
| `get()` | `public static function get(string $db, string $table)` | Read/list data without changing storage. |
| `updateKey()` | `public static function updateKey(string $db, string $table)` | Write/change storage or configuration; validate input and permissions first. |
| `all()` | `public static function all()` | See class section; use when the method name matches the required operation. |
| `updates()` | `public static function updates()` | Write/change storage or configuration; validate input and permissions first. |

### `Converter`

File: `core/converter.php`

No public methods detected.

### `Cookie`

File: `core/cookies.php`

| Method | Signature | Typical use |
|---|---|---|
| `set()` | `public static function set(string $name, string $value, int $expiration = self::DUR)` | Write/change storage or configuration; validate input and permissions first. |
| `setSecure()` | `public static function setSecure(string $name, string $value, int $expiration = self::DUR)` | Write/change storage or configuration; validate input and permissions first. |
| `add()` | `public static function add(string $name, string $value)` | Write/change storage or configuration; validate input and permissions first. |
| `get()` | `public static function get(string $name)` | Read/list data without changing storage. |
| `delete()` | `public static function delete(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `edit()` | `public static function edit(string $name, string $value)` | Write/change storage or configuration; validate input and permissions first. |
| `refresh()` | `public static function refresh(int $thresholdSeconds = 3600)` | See class section; use when the method name matches the required operation. |
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `exists()` | `public static function exists(string $name)` | See class section; use when the method name matches the required operation. |

### `Crypt`

File: `core/crypt.php`

| Method | Signature | Typical use |
|---|---|---|
| `encode()` | `public static function encode(string $data)` | See class section; use when the method name matches the required operation. |
| `decode()` | `public static function decode(string $data)` | See class section; use when the method name matches the required operation. |

### `DatabaseBridge`

File: `core/database_bridge.php`

| Method | Signature | Typical use |
|---|---|---|
| `setDriver()` | `public static function setDriver(string $driver)` | Write/change storage or configuration; validate input and permissions first. |
| `setInstance()` | `public static function setInstance(string $instance)` | Write/change storage or configuration; validate input and permissions first. |
| `get()` | `public static function get(string $db, string $table, bool $filter = false, string $where = "", mixed $is = "")` | Read/list data without changing storage. |
| `insert()` | `public static function insert(string $db, string $table, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `delete()` | `public static function delete(string $db, string $table, string $where, mixed $is)` | Write/change storage or configuration; validate input and permissions first. |
| `update()` | `public static function update(string $db, string $table, string $where, mixed $is, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `createDatabase()` | `public static function createDatabase(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteDatabase()` | `public static function deleteDatabase(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `createTable()` | `public static function createTable(string $db, string $table, array $columns)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteTable()` | `public static function deleteTable(string $db, string $table)` | Write/change storage or configuration; validate input and permissions first. |
| `addColumn()` | `public static function addColumn(string $db, string $table, string $column, mixed $default = "")` | Write/change storage or configuration; validate input and permissions first. |
| `createIndex()` | `public static function createIndex(string $db, string $table, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `begin()` | `public static function begin()` | See class section; use when the method name matches the required operation. |
| `commit()` | `public static function commit()` | See class section; use when the method name matches the required operation. |
| `rollback()` | `public static function rollback()` | See class section; use when the method name matches the required operation. |

### `FileTool`

File: `core/file_tool.php`

| Method | Signature | Typical use |
|---|---|---|
| `exists()` | `public static function exists(string $path)` | See class section; use when the method name matches the required operation. |
| `read()` | `public static function read(string $path)` | See class section; use when the method name matches the required operation. |
| `write()` | `public static function write(string $path, string $content)` | See class section; use when the method name matches the required operation. |
| `readJson()` | `public static function readJson(string $path)` | See class section; use when the method name matches the required operation. |
| `writeJson()` | `public static function writeJson(string $path, array $data)` | See class section; use when the method name matches the required operation. |
| `delete()` | `public static function delete(string $path)` | Write/change storage or configuration; validate input and permissions first. |
| `copyDir()` | `public static function copyDir(string $src, string $dest)` | See class section; use when the method name matches the required operation. |
| `deleteOldFiles()` | `public static function deleteOldFiles(string $dir, int $days)` | Write/change storage or configuration; validate input and permissions first. |
| `dirSize()` | `public static function dirSize(string $dir)` | See class section; use when the method name matches the required operation. |
| `listFiles()` | `public static function listFiles(string $dir, string $ext = '')` | See class section; use when the method name matches the required operation. |
| `backupDir()` | `public static function backupDir(string $src, string $dest)` | Operations, diagnostics, backups or maintenance. |
| `listDirs()` | `public static function listDirs(string $path)` | See class section; use when the method name matches the required operation. |

### `Format`

File: `core/format.php`

| Method | Signature | Typical use |
|---|---|---|
| `dateForInput()` | `public static function dateForInput(mixed $date)` | See class section; use when the method name matches the required operation. |
| `timeForInput()` | `public static function timeForInput(mixed $time)` | See class section; use when the method name matches the required operation. |
| `dateToView()` | `public static function dateToView(mixed $date)` | See class section; use when the method name matches the required operation. |
| `shortString()` | `public static function shortString(string $string, int $maxLength = 14)` | See class section; use when the method name matches the required operation. |
| `cleanString()` | `public static function cleanString(string $string)` | See class section; use when the method name matches the required operation. |
| `newLineCode()` | `public static function newLineCode(string $string, bool $forHtml = true)` | See class section; use when the method name matches the required operation. |

### `GBDBQueryBuilder`

File: `core/gbdb_query_builder.php`

| Method | Signature | Typical use |
|---|---|---|
| `table()` | `public static function table(string $database, string $table)` | See class section; use when the method name matches the required operation. |
| `where()` | `public function where(string $column, mixed $value, string $operator = '=')` | See class section; use when the method name matches the required operation. |
| `orderBy()` | `public function orderBy(string $column, string $direction = 'ASC')` | See class section; use when the method name matches the required operation. |
| `limit()` | `public function limit(int $limit, int $offset = 0)` | See class section; use when the method name matches the required operation. |
| `get()` | `public function get()` | Read/list data without changing storage. |
| `first()` | `public function first()` | See class section; use when the method name matches the required operation. |
| `insert()` | `public function insert(array $row)` | Write/change storage or configuration; validate input and permissions first. |
| `update()` | `public function update(array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `delete()` | `public function delete()` | Write/change storage or configuration; validate input and permissions first. |

### `GBDBStorage`

File: `core/gbdb_storage.php`

| Method | Signature | Typical use |
|---|---|---|
| `atomicWrite()` | `public static function atomicWrite(string $file, string $payload)` | See class section; use when the method name matches the required operation. |
| `appendLine()` | `public static function appendLine(string $file, string $line)` | See class section; use when the method name matches the required operation. |
| `wal()` | `public static function wal(string $appendFile, array $op, string $state, string $tx)` | See class section; use when the method name matches the required operation. |
| `journalChecksum()` | `public static function journalChecksum(array $entry)` | See class section; use when the method name matches the required operation. |
| `journal()` | `public static function journal(string $file, array $entry)` | See class section; use when the method name matches the required operation. |
| `readJournal()` | `public static function readJournal(string $file)` | See class section; use when the method name matches the required operation. |
| `encodeLine()` | `public static function encodeLine(string $json)` | See class section; use when the method name matches the required operation. |
| `normalizeMeta()` | `public static function normalizeMeta(array $meta = [])` | See class section; use when the method name matches the required operation. |
| `touchMeta()` | `public static function touchMeta(array $meta, bool $bumpVersion = true)` | See class section; use when the method name matches the required operation. |
| `shouldCompact()` | `public static function shouldCompact(array $meta, string $appendFile)` | See class section; use when the method name matches the required operation. |
| `checksum()` | `public static function checksum(array $rows)` | See class section; use when the method name matches the required operation. |
| `indexFile()` | `public static function indexFile(string $dataFile, string $column)` | See class section; use when the method name matches the required operation. |
| `buildIndex()` | `public static function buildIndex(array $rows, string $column)` | See class section; use when the method name matches the required operation. |
| `writeIndex()` | `public static function writeIndex(string $dataFile, string $column, array $rows)` | See class section; use when the method name matches the required operation. |
| `deleteIndex()` | `public static function deleteIndex(string $dataFile, string $column)` | Write/change storage or configuration; validate input and permissions first. |
| `validateConstraints()` | `public static function validateConstraints(array $rows, array $candidate, array $constraints, ?int $excludeId = null)` | See class section; use when the method name matches the required operation. |
| `indexKey()` | `public static function indexKey(mixed $value)` | See class section; use when the method name matches the required operation. |
| `rebuildIndexes()` | `public static function rebuildIndexes(string $dataFile, array $meta, array $rows)` | See class section; use when the method name matches the required operation. |
| `snapshot()` | `public static function snapshot(string $dataFile, array $extraFiles = [], string $reason = "manual")` | See class section; use when the method name matches the required operation. |
| `deleteTableArtifacts()` | `public static function deleteTableArtifacts(string $dataFile)` | Write/change storage or configuration; validate input and permissions first. |
| `deleteDir()` | `public static function deleteDir(string $dir)` | Write/change storage or configuration; validate input and permissions first. |
| `decodeLine()` | `public static function decodeLine(string $line)` | See class section; use when the method name matches the required operation. |
| `readWal()` | `public static function readWal(string $appendFile)` | See class section; use when the method name matches the required operation. |
| `recoverWal()` | `public static function recoverWal(string $appendFile)` | See class section; use when the method name matches the required operation. |
| `restoreSnapshot()` | `public static function restoreSnapshot(string $dataFile, string $snapshotId)` | See class section; use when the method name matches the required operation. |
| `indexLookup()` | `public static function indexLookup(string $dataFile, string $column, mixed $value)` | See class section; use when the method name matches the required operation. |
| `advancedIndexDir()` | `public static function advancedIndexDir(string $dataFile)` | See class section; use when the method name matches the required operation. |
| `normalizeIndexDefinition()` | `public static function normalizeIndexDefinition(string $name, string $type, array $columns, array $options = [])` | See class section; use when the method name matches the required operation. |
| `indexName()` | `public static function indexName(string $type, array $columns)` | See class section; use when the method name matches the required operation. |
| `advancedIndexFile()` | `public static function advancedIndexFile(string $dataFile, string $name)` | See class section; use when the method name matches the required operation. |
| `advancedIndexMetaFile()` | `public static function advancedIndexMetaFile(string $dataFile)` | See class section; use when the method name matches the required operation. |
| `compositeIndexKey()` | `public static function compositeIndexKey(array $row, array $columns)` | See class section; use when the method name matches the required operation. |
| `tokenizeFulltext()` | `public static function tokenizeFulltext(string $text)` | See class section; use when the method name matches the required operation. |
| `buildAdvancedIndex()` | `public static function buildAdvancedIndex(array $rows, array $definition)` | See class section; use when the method name matches the required operation. |
| `writeAdvancedIndex()` | `public static function writeAdvancedIndex(string $dataFile, array $definition, array $rows)` | See class section; use when the method name matches the required operation. |
| `readAdvancedIndex()` | `public static function readAdvancedIndex(string $dataFile, string $name)` | See class section; use when the method name matches the required operation. |
| `writeAdvancedIndexMeta()` | `public static function writeAdvancedIndexMeta(string $dataFile, array $definitions)` | See class section; use when the method name matches the required operation. |
| `advancedIndexLookup()` | `public static function advancedIndexLookup(string $dataFile, array $definition, mixed $value)` | See class section; use when the method name matches the required operation. |
| `pageSizes()` | `public static function pageSizes()` | See class section; use when the method name matches the required operation. |
| `normalizePageSize()` | `public static function normalizePageSize(int $pageSize)` | See class section; use when the method name matches the required operation. |
| `storageDir()` | `public static function storageDir(string $dataFile)` | See class section; use when the method name matches the required operation. |
| `initStorage()` | `public static function initStorage(string $dataFile, array $rows, array $meta = [])` | See class section; use when the method name matches the required operation. |
| `syncStorage()` | `public static function syncStorage(string $dataFile, array $rows, array $meta = [], string $reason = "sync")` | See class section; use when the method name matches the required operation. |
| `appendStorageJournal()` | `public static function appendStorageJournal(string $dataFile, array $op)` | See class section; use when the method name matches the required operation. |
| `markTombstones()` | `public static function markTombstones(string $dataFile, array $ids)` | See class section; use when the method name matches the required operation. |
| `verifyStorage()` | `public static function verifyStorage(string $dataFile)` | See class section; use when the method name matches the required operation. |
| `repairStorage()` | `public static function repairStorage(string $dataFile, array $rows, array $meta = [])` | Operations, diagnostics, backups or maintenance. |
| `vacuumStorage()` | `public static function vacuumStorage(string $dataFile, array $rows, array $meta = [])` | See class section; use when the method name matches the required operation. |

### `GetForm`

File: `core/getForm.php`

| Method | Signature | Typical use |
|---|---|---|
| `getDropdown()` | `public static function getDropdown(mixed $dropdown)` | Read/list data without changing storage. |
| `upload()` | `public static function upload(mixed $file, string $path = "./", string $useName = "")` | See class section; use when the method name matches the required operation. |
| `check_required_fields()` | `public static function check_required_fields(mixed $post_data)` | See class section; use when the method name matches the required operation. |
| `createInput()` | `public static function createInput(string $name, string $type, mixed $form_data, string $placeholder = "", string $class = "", string $id = "")` | Write/change storage or configuration; validate input and permissions first. |
| `checkPost()` | `public static function checkPost()` | See class section; use when the method name matches the required operation. |

### `Http`

File: `core/http.php`

| Method | Signature | Typical use |
|---|---|---|
| `get()` | `public static function get(string $url, array $headers = [], int $timeout = 10)` | Read/list data without changing storage. |
| `post()` | `public static function post(string $url, array $data = [], array $headers = [], int $timeout = 10)` | See class section; use when the method name matches the required operation. |
| `sendMail()` | `public static function sendMail(array $mail)` | See class section; use when the method name matches the required operation. |
| `jsonResponse()` | `public static function jsonResponse(array $data, int $status = 200)` | See class section; use when the method name matches the required operation. |
| `redirect()` | `public static function redirect(string $url, int $status = 302)` | See class section; use when the method name matches the required operation. |
| `getHeaders()` | `public static function getHeaders()` | Read/list data without changing storage. |
| `method()` | `public static function method()` | See class section; use when the method name matches the required operation. |
| `isJson()` | `public static function isJson()` | See class section; use when the method name matches the required operation. |
| `jsonInput()` | `public static function jsonInput()` | See class section; use when the method name matches the required operation. |

### `Json`

File: `core/json.php`

| Method | Signature | Typical use |
|---|---|---|
| `decode()` | `public static function decode(string $json, bool $assoc = false)` | See class section; use when the method name matches the required operation. |
| `encode()` | `public static function encode(mixed $data, bool $pretty = false)` | See class section; use when the method name matches the required operation. |
| `isJson()` | `public static function isJson(string $json)` | See class section; use when the method name matches the required operation. |
| `loop()` | `public static function loop(mixed $data, callable $callback)` | See class section; use when the method name matches the required operation. |
| `elementExists()` | `public static function elementExists(mixed $data, string $key)` | See class section; use when the method name matches the required operation. |
| `getElement()` | `public static function getElement(mixed $data, string $key)` | Read/list data without changing storage. |

### `ReCaptcha`

File: `core/recaptcha.php`

| Method | Signature | Typical use |
|---|---|---|
| `loadJsApi()` | `public function loadJsApi()` | See class section; use when the method name matches the required operation. |
| `postName()` | `public static function postName()` | See class section; use when the method name matches the required operation. |
| `checkBox()` | `public static function checkBox(string $callbackJs = "")` | See class section; use when the method name matches the required operation. |
| `verify()` | `public static function verify(?string $token)` | See class section; use when the method name matches the required operation. |

### `Ref`

File: `core/ref.php`

| Method | Signature | Typical use |
|---|---|---|
| `to()` | `public static function to(string $url)` | See class section; use when the method name matches the required operation. |
| `this_file()` | `public static function this_file()` | See class section; use when the method name matches the required operation. |

### `Route`

File: `core/route.php`

| Method | Signature | Typical use |
|---|---|---|
| `middleware()` | `public static function middleware(callable $mw)` | See class section; use when the method name matches the required operation. |
| `get()` | `public static function get(string $path, callable $callback)` | Read/list data without changing storage. |
| `post()` | `public static function post(string $path, callable $callback)` | See class section; use when the method name matches the required operation. |
| `put()` | `public static function put(string $path, callable $callback)` | See class section; use when the method name matches the required operation. |
| `delete()` | `public static function delete(string $path, callable $callback)` | Write/change storage or configuration; validate input and permissions first. |
| `patch()` | `public static function patch(string $path, callable $callback)` | See class section; use when the method name matches the required operation. |
| `notFound()` | `public static function notFound(callable $callback)` | See class section; use when the method name matches the required operation. |
| `methodNotAllowed()` | `public static function methodNotAllowed(callable $callback)` | See class section; use when the method name matches the required operation. |
| `dispatch()` | `public static function dispatch()` | See class section; use when the method name matches the required operation. |

### `Session`

File: `core/session.php`

| Method | Signature | Typical use |
|---|---|---|
| `handler()` | `public static function handler()` | See class section; use when the method name matches the required operation. |
| `init()` | `public static function init()` | See class section; use when the method name matches the required operation. |
| `get()` | `public static function get(string $name)` | Read/list data without changing storage. |
| `set()` | `public static function set(string $name, mixed $value)` | Write/change storage or configuration; validate input and permissions first. |
| `exists()` | `public static function exists(string $name)` | See class section; use when the method name matches the required operation. |
| `delete()` | `public static function delete(string $name)` | Write/change storage or configuration; validate input and permissions first. |
| `destroy()` | `public static function destroy()` | See class section; use when the method name matches the required operation. |

### `SQL`

File: `core/sql.php`

| Method | Signature | Typical use |
|---|---|---|
| `connect()` | `public static function connect()` | See class section; use when the method name matches the required operation. |
| `select()` | `public static function select(string $table, string $select = "*", string $where = "", mixed $is = "",)` | Read/list data without changing storage. |
| `insert()` | `public static function insert(string $table, array $data)` | Write/change storage or configuration; validate input and permissions first. |
| `update()` | `public static function update(string $table, array $data, string $where, mixed $is)` | Write/change storage or configuration; validate input and permissions first. |
| `delete()` | `public static function delete(string $table, string $where, mixed $is)` | Write/change storage or configuration; validate input and permissions first. |

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

### `Time`

File: `core/time.php`

| Method | Signature | Typical use |
|---|---|---|
| `timeAgo()` | `public static function timeAgo(mixed $timestamp)` | See class section; use when the method name matches the required operation. |

### `Tools`

File: `core/tools.php`

| Method | Signature | Typical use |
|---|---|---|
| `generatePassword()` | `public static function generatePassword(int $length)` | Authentication/user workflows. |
| `testPasswordStrength()` | `public static function testPasswordStrength(string $password)` | Authentication/user workflows. |
| `getDomainInfo()` | `public static function getDomainInfo(string $domain)` | Read/list data without changing storage. |
| `generateId()` | `public static function generateId()` | See class section; use when the method name matches the required operation. |
| `generateToken()` | `public static function generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4)` | See class section; use when the method name matches the required operation. |
| `getIpCountry()` | `public static function getIpCountry(string $ip)` | Read/list data without changing storage. |
| `ping4()` | `public static function ping4(string $ip)` | See class section; use when the method name matches the required operation. |
| `ping6()` | `public static function ping6(string $ip)` | See class section; use when the method name matches the required operation. |
| `qr()` | `public static function qr(string $value, int $width, int $height)` | See class section; use when the method name matches the required operation. |
| `bar()` | `public static function bar(string $value, int $width, int $height = 175)` | See class section; use when the method name matches the required operation. |

### `Validate`

File: `core/validate.php`

| Method | Signature | Typical use |
|---|---|---|
| `required()` | `public static function required(array $data, array $fields)` | See class section; use when the method name matches the required operation. |
| `email()` | `public static function email(string $value)` | See class section; use when the method name matches the required operation. |
| `number()` | `public static function number(string|int|float $value)` | See class section; use when the method name matches the required operation. |
| `minLength()` | `public static function minLength(string $value, int $min)` | See class section; use when the method name matches the required operation. |
| `maxLength()` | `public static function maxLength(string $value, int $max)` | See class section; use when the method name matches the required operation. |
| `regex()` | `public static function regex(string $value, string $pattern)` | See class section; use when the method name matches the required operation. |
| `between()` | `public static function between(float|int $value, float|int $min, float|int $max)` | See class section; use when the method name matches the required operation. |
| `in()` | `public static function in(string|int $value, array $allowed)` | See class section; use when the method name matches the required operation. |
| `match()` | `public static function match(string $a, string $b)` | See class section; use when the method name matches the required operation. |
| `validateArray()` | `public static function validateArray(array $data, array $rules)` | See class section; use when the method name matches the required operation. |
