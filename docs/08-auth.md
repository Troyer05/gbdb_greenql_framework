# Auth

`Auth` manages users, login, logout, JWT cookies, optional 2FA and email verification.

## Typical flow

```php
Auth::init();
$result = Auth::login($usernameOrEmail, $password);
$user = Auth::me();
Auth::logout();
```

User data is stored in the configured auth database from `Vars::AUTH()`.
