# 06 — Authentication

The framework has two authentication concepts:

1. **Application Auth** through `core/auth.php` (`Auth` class).
2. **GBDB UI Auth** through `GreenQLUIv2Helper` for the admin interface.

## Application Auth

`Auth` is intended for application users. It uses the configured auth instance and creates/uses tables for users, JWT tokens, email verification, password reset, 2FA and metadata.

Typical workflow:

```php
<?php

require_once __DIR__ . "/gbdb_framework/autoloader.php";

Auth::init();

$created = Auth::newUser([
    "username" => "max",
    "email" => "max@example.com",
    "password" => "secret",
    "active" => "1",
    "role" => "user"
]);

$login = Auth::login("max", "secret");
$user = Auth::me();
```

## Important Auth methods

| Method | Use |
|---|---|
| `Auth::init()` | Ensure auth storage exists. |
| `Auth::hashPass($password)` | Hash password consistently with the framework. |
| `Auth::newUser($data)` | Create a user. |
| `Auth::login($user, $password)` | Login with username/email and password. |
| `Auth::logout()` | Remove/expire current JWT. |
| `Auth::me()` | Get the currently logged-in user. |
| `Auth::check()` | Check whether the current request is authenticated. |
| `Auth::user($uid)` | Get one user. |
| `Auth::get()` | Get users/listing depending on implementation. |
| `Auth::editUser($uid, $data)` | Update a user. |
| `Auth::delete($uid)` | Delete/deactivate a user. |
| `Auth::verifyEmail($token)` | Complete email verification. |
| `Auth::verify2FaCode($code)` | Complete 2FA step. |

## JWT and cookies

The Auth class stores a JWT-like token in a cookie, usually named `jwt`. Token records are also stored in the GBDB auth tables with expiration timestamps. The cookie/session behavior is controlled by `Vars::init_cookies()`, `Vars::init_session()` and auth expiration settings.

## 2FA and email verification

The framework includes mail templates:

```text
gbdb_framework/public/includes/mail_templates/verify_mail.html
gbdb_framework/public/includes/mail_templates/tfa_mail.html
```

Use 2FA for admin users and sensitive apps. For public/simple apps, email verification plus strong passwords may be enough.

## UI Auth

`GreenQLUIv2Helper` manages users for the GBDB UI. It stores users in a system instance and includes roles, language, allowed instances/bases and permission checks.

Important concepts:

| Concept | Meaning |
|---|---|
| `admin` | Full UI/system access. |
| write permission | Can mutate data. |
| structure permission | Can create/drop schema objects. |
| tool permission | Can use specific admin tools/pages. |
| instance/base allow lists | Restrict a user to selected workspaces. |

Do not mix application users and GBDB UI users unless you intentionally build a bridge. The UI auth store is a framework/admin concern.

## Security recommendations

- Use strong passwords for UI admins.
- Enable 2FA for admin accounts when available.
- Keep JWT expiration reasonable.
- Regenerate/expire tokens on password reset.
- Never expose auth tables through the public API without strict rights.
- Do not store plaintext passwords.
- Protect mail verification and password reset tokens with expiration.
