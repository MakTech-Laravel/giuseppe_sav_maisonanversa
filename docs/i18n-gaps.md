# Translation gaps

Generated from the prototype dictionary (`prototype/i18n.js`) by
`node prototype/convert-i18n.mjs`. Regenerate after any change to that file.

Of 1650 translations (825 Dutch source strings x EN + FR), 1646 are complete.

**Resolved (Full-Site Completion Plan, Phase 0):** two truncated Dutch-source
keys — "Eerste sessie vandaag met Heritage No.001. ... onmiddellijk merkbaar. De "
and "Stuur ons een bericht en wij nemen binnen 48 uur ... een echt gesprek " —
were dead extraction artifacts of `convert-i18n.mjs` cutting mid-sentence. No
component ever called `t()` with the truncated text (the full, untruncated
sentence keys exist separately and are the ones actually rendered), so both
truncated key/value pairs were removed from `lang/en.json` and `lang/fr.json`
instead of translated. The canonical `Q2 2027` key (used as literal seeded copy
in `CommunityCourtSeeder`) was missing its French translation; added as
`"T2 2027"` matching the existing `Q1 2027` → `T1 2027` convention.

One truncated orphan remains **intentionally**: the "Anvers/Enge" key in
`tests/Feature/TranslationTest.php` (`TRUNCATED_ANVERS_ORPHAN_KEY`) is a
deliberate test fixture proving the Dutch-source fallback mechanism works for
an incomplete dictionary entry. Do not delete it or its translations.

## Keys added outside the prototype dictionary (7)

The prototype gave its icon-only controls no accessible names, so screen readers
announced them as bare "button". Adding those names meant adding six keys the
prototype dictionary never had. They are interface affordances rather than brand
copy, so they are translated here directly — flagged for review in case the house
prefers different wording.

| Key | EN | FR |
| --- | --- | --- |
| `Menu` | Menu | Menu |
| `Sluiten` | Close | Fermer |
| `Taal` | Language | Langue |
| `Maison Anversa — hulp` | Maison Anversa — help | Maison Anversa — aide |
| `Vorige kamer` | Previous room | Salle précédente |
| `Volgende kamer` | Next room | Salle suivante |
| `Editienummer (bijv. 7)` | Edition number (e.g. 7) | Numéro d'édition (ex. 7) |

Because `convert-i18n.mjs` writes `lang/en.json` and `lang/fr.json` from the
prototype alone, rerunning it drops these keys. Re-add them if that happens.

Four more keys (`Vul uw editienummer en e-mailadres in.`, `Editienummer moet
tussen 1 en 100 liggen.`, `Link gekopieerd — deel hem met een vriend.`,
`Uitgenodigd door Founding Member`) covered alerts/placeholders in the legacy
`resources/js/lib/founding-circle.ts` localStorage prototype. That file was
removed once the real Spatie-role-based Founding Circle/Passport system
replaced it, and those four keys had no other call site, so they were removed
too.

## Copy the dictionary does not carry: the intro slides

The seven intro rooms name themselves in `resources/js/lib/maison-intro.ts`
rather than through `t()`. That mirrors the prototype, which assembled the slide
copy in script from an array carrying its own `_en` and `_fr` fields — the words
were never in the page, so the dictionary never saw them. Translating a room
means editing that file, not this dictionary.
