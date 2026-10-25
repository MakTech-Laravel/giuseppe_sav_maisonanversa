---
paths:
  - 'app/Services/Auth/**,bootstrap/app.php,app/Http/Controllers/Auth/**,resources/js/layouts/frontend-layout.tsx'
---

# Js Layouts

## Post-login intended URLs stay relative
Never rely on redirect()->guest() alone for Maison auth: it stores absolute url.intended via UrlGenerator::full(), which breaks Inertia redirects when APP_URL host ≠ browser host. AuthenticationException rendering must call PostLoginRedirectService::rememberFromRequest() and redirect with relative paths. LoginResponse uses intendedUrlFor() which normalizes to relative and rejects external hosts. Modal openAuth posts to auth.intended so public pages (community gate, checkout) also return after login; never remember localized home as intended.
