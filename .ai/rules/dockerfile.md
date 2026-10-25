---
paths:
  - Dockerfile
---

# Dockerfile

## Dockerfile layer cache + HEALTHCHECK for Coolify
Install composer (composer.json/lock) and npm (package.json/package-lock.json) in separate layers before COPY . ., and declare VITE_* ARGs only after the app COPY so Coolify rebuilds skip dependency installs. Use BuildKit cache mounts for Composer/npm. HEALTHCHECK must curl http://127.0.0.1/up (Laravel via nginx→php-fpm), with start-period covering start.sh migrate + cache. Never add a static nginx /up 200 — exact location = /up already proxies to PHP.
