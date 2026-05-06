# SecondServer / SrvP

The SecondServer module separates a client from a server-side GBDB runtime through JSON calls.

## Components

- `SrvP`: client class in `core/srvp.php`
- `backend.php`: server dispatcher
- `Srv`: service/job runtime
- `srv_modules/`: service modules such as mail jobs

## Use cases

- Remote GBDB operations
- Remote auth operations
- Service jobs
- Separated deployments where the main app should not directly access storage
