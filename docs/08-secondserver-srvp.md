# 08 — SecondServer, SRV and SrvP

The SecondServer layer allows one installation to call another installation. It is useful for central services, remote auth, background jobs, device backends and distributed product deployments.

## Components

| Component | File | Purpose |
|---|---|---|
| `SecondServer` class | `core/srvp.php` | Client wrapper used by local code. |
| `Srv` class | `SRV/Srv.php` | Backend dispatcher/response helper. |
| `SrvFunctions` | `SRV/SrvFunctions.php` | Backend actions. |
| `backend_api.php` | `SRV/backend_api.php` | API bootstrap/dispatch. |
| `srv_auth.php` | `SRV/srv_auth.php` | Auth checks for SRV. |
| job modules | `SRV/srv_modules/` | Background/special jobs. |
| `PHP/backend.php` | top-level | Public backend entry. |

## Configuration

`Vars` controls remote settings:

```php
Vars::srvp_ip();
Vars::srvp_ssl();
Vars::srvp_static_key();
Vars::srvp_api_log();
Vars::srvp_log_path();
Vars::srvp_sys_instance();
```

Use a strong static key in production and keep the backend endpoint protected.

## Client usage

```php
$result = SecondServer::ping();
$driver = SecondServer::driver();

$login = SecondServer::login("max", "secret");
$user = SecondServer::getUser("uid123");

$job = SecondServer::startJob("test_job", ["x" => 1]);
$jobs = SecondServer::listJobs();
```

## Auth flow

The client requests a short-lived token using the configured static key, then sends instructions with that token. This reduces the exposure of the long-lived static secret on every instruction call.

## Supported client concepts

The current `SecondServer` client exposes:

- `ping()` and `driver()` diagnostics;
- job methods such as `startJob()` and `listJobs()`;
- remote user registration/login/logout;
- email verification and 2FA verification;
- user read/edit/delete workflows;
- generic `userAuth()` action forwarding.

## When to use SecondServer

Use it when:

- an installation must forward auth to a central server;
- one admin UI manages remote data;
- a device backend needs a stable central endpoint;
- background jobs should run on a different machine;
- local apps should not directly expose their own DB API.

Do not use SecondServer for local calls that can be normal `GBDB::...` operations. Avoid unnecessary network hops.

## Job modules

Job modules live under `SRV/srv_modules/`. They should accept structured parameters, return a clear status/result array and log enough information to debug failures.

## Security recommendations

- Use HTTPS when possible.
- Rotate the static key before production.
- Keep token expiration short.
- Log failed auth attempts.
- Restrict backend access by firewall/IP where possible.
- Never expose raw system operations to untrusted clients.
