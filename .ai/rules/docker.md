---
paths:
  - 'docker/**'
---

# Docker

## Docker production process model
Single Coolify/app container runs php-fpm, nginx, queue:work (default), and schedule:run via supervisord. start.sh migrates, storage:link --force, ensures storage/app/private (and siblings) exist and are owned by www-data (Coolify volume mounts), then config/route/view/event cache before supervisord. Nginx /up must proxy to PHP (Laravel health), not a static 200. Stripe webhooks stay at POST /stripe/webhook with no locale prefix. Schedule tasks use withoutOverlapping + onOneServer (shared database/redis cache). Image also keeps Node 20 + node_modules for match-card PNGs (fontconfig, resvg smoke check in Dockerfile).
