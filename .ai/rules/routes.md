---
paths:
  - routes/web.php
---

# Routes

## Gate admin and member areas by UserType
Wrap admin prefixes with the `admin` middleware (User::isAdmin()) and member prefixes with the `customer` middleware (User::isCustomer()). Spatie permission checks stay for staff granularity inside admin; they are not the staff-versus-member wall. After login, use PostLoginRedirectService::intendedUrlFor() so customers never land on /admin and staff never land on /member.

## Public community clubs need index and show
Community ClubController exposes index and show for the member directory. Register GET community.clubs.index and community.clubs.show (keep clubs/search before clubs/{club}). CommunityTabs and maison/clubs pages call clubRoutes.index.url — omitting those routes makes Wayfinder omit index and crashes CommunityTabs with Cannot read properties of undefined (reading 'url').
