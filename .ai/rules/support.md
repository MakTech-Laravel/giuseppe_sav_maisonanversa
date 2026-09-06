---
paths:
  - app/Support/PassportPresenter.php
  - app/Support/ClubDirectory.php
---

# Support

## Founding Circle / Heritage Passport is a composite, not a model
"Founding Circle" and "Heritage Passport" have no dedicated passport tables. They're derived at read time from: the Spatie `founding-circle` role on `User` (checked via `User::isFoundingCircle()`), an approved `FoundingCircleClaim` and/or `PassportPresenter::heritageOrder()` (latest paid/shipped/delivered founding order with `edition_number`). Enrollment is **not** automatic on pay — `OrderFulfillment` creates a pending claim; Admin must approve (or use `admin.circle.assign` as ops override). Don't build a `FoundingCircleMember` or `HeritagePassport` model — keep the presenter/role/claim trio.

Deliberately out of scope, don't "fix" these: no CMS editor for the Passport's 4 page bodies (hardcoded in `PassportPresenter`); no tiers/points/loyalty mechanics (binary membership is intentional). Member nav shows **Racketregistratie** for all customers; Heritage, Passport, and Founding Circle card links stay Circle-only (`MemberDashboardTest` asserts this).

## Member Passport (Lidpaspoort) is separate from Heritage Passport
`MemberPassportPresenter` on `member.lidpaspoort` is the digital activity passport for all customers: membership status, derived badges, upcoming sessions/events. It must stay separate from `PassportPresenter` / `member.passport`, which is the Founding Circle Heritage artefact only. Nav shows **Lidpaspoort** for every member; **Digitaal Heritage Passport** stays Circle-only.

An admin-assigned `founding-circle` role holder with no qualifying order yet passes `EnsureFoundingCircle` middleware but has no passport/heritage/card data — `DashboardController::heritage/passport/circle` return a null prop (not a 404) and the React pages (`member/heritage.tsx`, `member/passport.tsx`, `member/circle.tsx`) render `MemberEmptyState` for that case.

## Founding Circle / Passport __() and t() keys use Dutch source text
lang/en.json and lang/fr.json are keyed by the Dutch source string (i18next fallback pattern), so __()/t() calls here must pass the exact Dutch phrase, not an English gloss. OpsController::circleShow() benefits use __('Digitaal Heritage Passport') and __('Founding Circle-kaart'); pdf/passport.blade.php uses __('Heritage Paspoort'). Passing the English text as the key silently falls back to Dutch in every locale with no error — ShellTranslationKeysTest scans resources/js/pages/member/** for this class of bug on the frontend, but there is no equivalent static check for backend __() calls, so double-check new backend strings against the dictionary keys by hand.

## No map APIs for club directory in V1
Member club directory is list + profile + search filters only. Do not add Mapbox/Google Maps or geocoding to the storefront club UX until the client approves API/cost. `clubs.lat/lng` may exist in the database but are not rendered on member pages in V1.
