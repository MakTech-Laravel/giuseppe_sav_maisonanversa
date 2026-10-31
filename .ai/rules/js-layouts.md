---
paths:
  - 'app/Services/Auth/**,bootstrap/app.php,app/Http/Controllers/Auth/**,resources/js/layouts/frontend-layout.tsx'
---

# Js Layouts

## Post-login intended URLs stay relative
Never rely on redirect()->guest() alone for Maison auth: it stores absolute url.intended via UrlGenerator::full(), which breaks Inertia redirects when APP_URL host ≠ browser host. AuthenticationException rendering must call PostLoginRedirectService::rememberFromRequest() and redirect with relative paths. LoginResponse uses intendedUrlFor() which normalizes to relative and rejects external hosts. Casual modal openAuth posts to auth.intended without force (localized home is not stored → role dashboard). Mid-action flows (checkout requireAuth, opening Claim) post force=true so home can be intended; never overwrite a non-home intended with home. Staff intendedUrlFor only honors admin paths; public/member intended → admin dashboard.
