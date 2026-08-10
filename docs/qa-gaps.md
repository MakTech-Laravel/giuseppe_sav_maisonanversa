# Final QA gaps

Recorded on `mahfuz/final-qa` after route/locale/smoke test expansion. Pint on
dirty PHP passes; the items below remain open on this stack.

## Typography

- **Baskervville faux-bold:** Google Fonts ships Baskervville at weight 400 only
  (`vite.config.ts`). Headings that request `font-medium` / `font-semibold`
  (500+) are browser-synthesised faux-bold. Accept for prototype parity or swap
  to a multi-weight serif before launch.

## Internationalisation

- **Truncated dictionary orphans:** `lang/en.json` and `lang/fr.json` still carry
  prefix keys cut mid-sentence (for example the Anvers story string ending in
  `…Enge`). Components use the full Dutch source keys; orphans are dead entries
  but can confuse audits. See also `docs/i18n-gaps.md` for cut-off translations.
- **Locale parity drift:** canonical key `Q2 2027` exists in EN but not FR
  (covered by `TranslationTest`).
- **Fallback behaviour:** missing keys correctly render Dutch source copy on
  `/en` and `/fr`; truncated orphans that remain in JSON resolve to truncated
  target strings instead of Dutch.

## Tooling / smoke

- **Pest browser:** `pest-plugin-browser` is not installed; HTTP feature smoke
  covers 14 routes × 3 locales instead of `assertNoJavaScriptErrors()`.
- **Frontend CI:** `npm run lint:check` (32 ESLint issues), `npm run
  format:check` (9 Prettier files), and `npm run types:check` (17 TypeScript
  errors) fail on pre-existing Maison React code — not introduced by the test
  branch. Fix in a dedicated polish pass.
