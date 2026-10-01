---
paths:
  - routes/web.php
---

# Routes

## Gate admin and member areas by UserType
Wrap admin prefixes with the `admin` middleware (User::isAdmin()) and member prefixes with the `customer` middleware (User::isCustomer()). Spatie permission checks stay for staff granularity inside admin; they are not the staff-versus-member wall. After login, use PostLoginRedirectService::intendedUrlFor() so customers never land on /admin and staff never land on /member.

## Public community clubs need index and show
Community ClubController exposes index and show for the member directory. Register GET community.clubs.index and community.clubs.show (keep clubs/search before clubs/{club}). CommunityTabs and maison/clubs pages call clubRoutes.index.url — omitting those routes makes Wayfinder omit index and crashes CommunityTabs with Cannot read properties of undefined (reading 'url').

## Unprefixed paths need an outer fallback
Public routes live under /{locale}. A URL whose first segment is not nl, en, or fr never enters that group, so a missing outer Route::fallback() skips the web middleware and the 404 Inertia page is rendered with only the seo share. The public shell then crashes (SiteNav reads foundingRegister). Keep the outer fallback that abort(404)s, and keep bare /admin and /admin/login redirects: guests go to the home login modal, staff go to the localized admin path.

## Stripe webhook stays unprefixed
Cashier serves POST /stripe/webhook (cashier.webhook) outside the {locale} group. Never nest Cashier under /{locale}. Local stripe listen must forward to http://127.0.0.1:8000/stripe/webhook. On PowerShell, quote --events or omit the filter; an unquoted comma list becomes invalid and forwards nothing. CSRF already excludes stripe/*.
