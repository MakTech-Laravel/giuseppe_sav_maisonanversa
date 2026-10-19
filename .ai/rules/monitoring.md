---
paths:
  - 'app/Monitoring/**'
---

# Monitoring

## Server monitoring is a self-contained module
Coolify/Reverb monitoring lives under app/Monitoring/**, Admin/ServerMonitoring controllers, config/server_monitoring.php, and admin/servers-maintenance UI. Do not fold this into RealtimeController or docker/supervisord. Logical service IDs (main-app, reverb) resolve only via server-side registry/env UUIDs.

## Reverb-only auto restart
Only Reverb is actively monitored (`MonitoredServiceId::monitored()`). Auto restart is simple: if global + Reverb toggles are on and status is unhealthy/unreachable/degraded, call Coolify restart. Skip when `restart_in_progress` or RestartService rejects (overlap/cooldown). No failure threshold or attempt window. Overlap locks: `monitoring:run-checks`, `monitoring:auto-recovery`, `monitoring:restart:reverb`.

## Reverb probe TLS
`MONITORING_TLS_VERIFY` defaults to true. Local Windows may fail certificate verification against the public WS host; set `MONITORING_TLS_VERIFY=false` for local diagnostics only. Prefer fixing the Coolify/Let’s Encrypt chain for the Reverb FQDN in production. A 503 from the WS host means the Reverb container/proxy is down, not a TLS config bug.
