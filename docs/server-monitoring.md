# Servers & Maintenance (Coolify monitoring)

Self-contained monitoring for the **main application** and **Laravel Reverb** Coolify resources.

## Deploy

1. Set Coolify and probe variables from `.env.example` (`COOLIFY_*`, `MONITORING_*`).
2. Deploy this application as usual (Dockerfile / Coolify).
3. Run migrations (`start.sh` already runs `migrate --force`).
4. Seed permissions/roles so admins receive `servers-maintenance.restart` (or rely on `MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS`).
5. Confirm the scheduler is running inside the app container (`schedule:run` via supervisord). Checks run every 30 seconds via `monitoring:run-checks`.

## TLS / Reverb unreachable

If Reverb shows **unreachable** with a TLS/certificate error, the WS hostname certificate chain is not trusted by PHP. Prefer fixing the Coolify/Let’s Encrypt cert for the Reverb FQDN. For local diagnostics only, set `MONITORING_TLS_VERIFY=false` (keep `true` in production).

## Verify

- Open **Admin → Reverb Server**.
- Click **Run checks now** and confirm main-app HTTP `/up` and Reverb WebSocket handshake results.
- Without Coolify credentials, restart actions return “not configured” (503) — expected.
- With Coolify credentials, restart **main-app** or **reverb** independently; confirm the other service is untouched.

## Rollback

1. Remove or disable schedule entries for `monitoring:run-checks` / `monitoring:prune-history` if needed.
2. Hide the nav item or revert the thin wiring in `routes/web.php`, `app-sidebar.tsx`, and permission enums.
3. Drop monitoring tables only if you accept losing history:

```sql
DROP TABLE IF EXISTS monitoring_settings, monitoring_restarts, monitoring_incidents, monitoring_health_checks, monitoring_service_states;
```

Auto-recovery defaults to **off**. Never enable it in production until Coolify UUIDs are confirmed and restart behavior has been tested on a staging resource.
