# Troubleshooting

## UI cannot write files

Check ownership and group write permissions for `GBDB_GQL/.DB`, `.logs` and `.config`.

## GreenQL ENV returns null

Check that `.config/.greenql.env.php` exists and returns an array.

## Jobs stay queued

Open Monitoring or call `GBDB::processDueJobs(50)`. `jobDashboard()` now auto-processes due local jobs before rendering.

## System instance is shown somewhere

Use `GBDB::listInstances()` for user-facing lists and `GBDB::listAllInstances()` only for internal diagnostics.
