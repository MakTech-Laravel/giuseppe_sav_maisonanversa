# Final QA gaps

Recorded after the Full-Site Completion Plan (Phases 0–9). Pint on dirty PHP
passes; `npm run types:check`, `lint:check`, and `format:check` are green; the
production Vite build succeeds; the Pest suite is green (978 tests).

## Typography

- **Baskervville faux-bold:** Google Fonts ships Baskervville at weight 400 only
  (`vite.config.ts`). Headings that request `font-medium` / `font-semibold`
  (500+) are browser-synthesised faux-bold. Accept for prototype parity or swap
  to a multi-weight serif before launch.

## Internationalisation

- **Truncated dictionary orphans:** `lang/en.json` and `lang/fr.json` carry one
  remaining prefix key cut mid-sentence intentionally — the Anvers story string
  ending in `…Enge` — kept as a deliberate `TranslationTest` fixture proving
  Dutch-source fallback works. Two other dead truncated keys ("Eerste sessie…De ",
  "Stuur ons…gesprek ") were removed in the Full-Site Completion Plan (Phase 0);
  see `docs/i18n-gaps.md`.
- ~~Locale parity drift: canonical key `Q2 2027` exists in EN but not FR~~ —
  fixed in Phase 0 (`"Q2 2027": "T2 2027"` added to `lang/fr.json`).
- **Fallback behaviour:** missing keys correctly render Dutch source copy on
  `/en` and `/fr`; the one remaining truncated orphan resolves to its (intentionally
  truncated) target string instead of Dutch, by design.

## Tooling / smoke

- **Pest browser:** `pest-plugin-browser` is not installed; HTTP feature smoke
  covers 14 routes × 3 locales instead of `assertNoJavaScriptErrors()`.
- ~~**Frontend CI:** `npm run lint:check`, `format:check`, and `types:check`
  failed on pre-existing Maison React code~~ — cleared in Phase 1. Re-run those
  three scripts before merge; they were green at the end of Phase 9.

## Dynamic CMS rollout

- **Admin UI completeness:** FAQ, Dressing, Partner Clubs, Sessions, Legal, and
  SEO all have CRUD plus translation dialogs. Remaining work is visual polish
  (density, empty states, mobile admin), not missing capabilities.

## Founding Circle promises (now implemented)

- **Name register:** append-only `founding_circle_register` ledger, written on
  paid Heritage checkout and admin assign. Admin → Circle → Naamregister.
- **12h support SLA:** inquiries from Founding Circle members are `priority`;
  the admin inbox shows remaining time / breach against `created_at + 12h`.
- **48h early access:** optional product `public_at`. Until that timestamp,
  published products are visible/buyable only to Founding Circle (and staff).
  Null `public_at` keeps the previous “published = public” behaviour.
