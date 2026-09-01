---
paths:
  - app/Support/PassportPresenter.php
---

# Support

## Founding Circle / Heritage Passport is a composite, not a model
"Founding Circle" and "Heritage Passport" have no dedicated tables. They're derived at read time from: the Spatie `founding-circle` role on `User` (checked via `User::isFoundingCircle()`), the `grants_founding_circle` flag on `Product` (seeded `heritage-no-001`), and `PassportPresenter::heritageOrder()` which finds the user's latest paid/shipped/delivered `Order` with a non-null `edition_number`. Enrollment happens automatically in `OrderFulfillment::markPaidFromSession()` when a qualifying order is paid, or manually via `admin.circle.assign`/`remove` (`OpsController`). Don't build a `FoundingCircleMember` model or migration for this — extend the presenter/role/flag trio instead.

Deliberately out of scope, don't "fix" these: no CMS editor for the Passport's 4 page bodies (hardcoded in `PassportPresenter::fromOrder()`); no tiers/points/loyalty mechanics (copy explicitly says "Dit is geen loyaliteitsprogramma" — binary membership is intentional); member dashboard nav intentionally omits Heritage & Passport links (`MemberDashboardTest` asserts this).

An admin-assigned `founding-circle` role holder with no qualifying order yet passes `EnsureFoundingCircle` middleware but has no passport/heritage/card data — `DashboardController::heritage/passport/circle` return a null prop (not a 404) and the React pages (`member/heritage.tsx`, `member/passport.tsx`, `member/circle.tsx`) render `MemberEmptyState` for that case.

## Founding Circle / Passport __() and t() keys use Dutch source text
lang/en.json and lang/fr.json are keyed by the Dutch source string (i18next fallback pattern), so __()/t() calls here must pass the exact Dutch phrase, not an English gloss. OpsController::circleShow() benefits use __('Digitaal Heritage Passport') and __('Founding Circle-kaart'); pdf/passport.blade.php uses __('Heritage Paspoort'). Passing the English text as the key silently falls back to Dutch in every locale with no error — ShellTranslationKeysTest scans resources/js/pages/member/** for this class of bug on the frontend, but there is no equivalent static check for backend __() calls, so double-check new backend strings against the dictionary keys by hand.
