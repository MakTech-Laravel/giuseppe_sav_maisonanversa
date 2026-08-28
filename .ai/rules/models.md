---
paths:
  - 'app/Models/*.php'
---

# Models

## Club must never use TranslatesWithDeepL
TranslatesWithDeepL auto-discovers string columns and translates them on every save. Club holds proper nouns (venue names, streets, cities) that must not be translated. tests/Feature/DeepLTranslationTest.php asserts this; do not add the trait.

## CommunitySession translates notes only
CommunitySession uses TranslatesWithDeepL with `$translatable = ['notes']`, auto-detect, and all maison locales (nl, en, fr), matching CommunityPost and FAQ. Display notes via `translated()` in SessionFeed and admin show. Do not auto-discover other columns, and never add the trait to Club.

## Session "past" reads the stored ends_at column
CommunitySession stores `ends_at` (indexed) and the model's `booted()` hook keeps it derived from `starts_at + duration_minutes` on every save. Scopes `upcoming()` and `past()`, `SessionFeed`, and the admin lifecycle filter all compare that column so the query stays an indexed comparison. Never re-derive an end time from `starts_at` in a query, and never write `ends_at` by hand.

## Only LegalPage translates HTML bodies
LegalPage is the only model that returns translationUsesHtml() true so DeepL uses tag_handling=html. Other translatable models stay plain text.
