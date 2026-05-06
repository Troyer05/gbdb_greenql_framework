# Jobs and Queue Monitoring

Jobs are stored in `.DB/.system/admin_ops/queue/jobs.json`.

## API

```php
$id = GBDB::enqueueJob('ui.demo', ['source' => 'docs']);
GBDB::processDueJobs(50);
GBDB::jobDashboard(80);
GBDB::retryFailedJobs(100);
```

`jobDashboard()` auto-processes due local jobs before displaying the queue so UI-created jobs do not stay permanently in `queued` when no external worker is running.

## Status values

- `queued`
- `running`
- `done`
- `failed`
