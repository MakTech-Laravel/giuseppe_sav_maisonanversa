# Translation gaps

Generated from the prototype dictionary (`prototype/i18n.js`) by
`node prototype/convert-i18n.mjs`. Regenerate after any change to that file.

Of 1650 translations (825 Dutch source strings x EN + FR),
4 are unusable: 3 cut off mid-sentence, 1 empty.
Each one falls back to the Dutch source text, so a page never renders half a
sentence. These need a human translation before launch.

The remaining 1646 translations are complete.

## EN (3)

- **Dutch source:** ziet
  - **Missing:** _(empty)_
- **Dutch source:** Eerste sessie vandaag met Heritage No.001. Het verschil in gevoel met een standaard racket is onmiddellijk merkbaar. De 
  - **Cut off:** First session today with Heritage No.001. The difference in feel with a standard racket is immediately noticeable. The
- **Dutch source:** Stuur ons een bericht en wij nemen binnen 48 uur persoonlijk contact op. Geen automatische responses — een echt gesprek 
  - **Cut off:** Send us a message and we will personally contact you within 48 hours. No automated responses — a real conversation

## FR (1)

- **Dutch source:** Stuur ons een bericht en wij nemen binnen 48 uur persoonlijk contact op. Geen automatische responses — een echt gesprek 
  - **Cut off:** Envoyez-nous un message et nous vous contacterons personnellement sous 48 heures. Pas de réponses automatiques — une vraie discussion

## Keys added outside the prototype dictionary (6)

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

Because `convert-i18n.mjs` writes `lang/en.json` and `lang/fr.json` from the
prototype alone, rerunning it drops these six keys. Re-add them if that happens.

## Copy the dictionary does not carry: the intro slides

The seven intro rooms name themselves in `resources/js/lib/maison-intro.ts`
rather than through `t()`. That mirrors the prototype, which assembled the slide
copy in script from an array carrying its own `_en` and `_fr` fields — the words
were never in the page, so the dictionary never saw them. Translating a room
means editing that file, not this dictionary.
