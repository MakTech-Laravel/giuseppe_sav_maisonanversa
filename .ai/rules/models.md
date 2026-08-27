---
paths:
  - 'app/Models/*.php'
---

# Models

## Club and CommunitySession must never use TranslatesWithDeepL
TranslatesWithDeepL auto-discovers string columns and translates them on every save. Club holds proper nouns (venue names, streets, cities) that must not be translated, and CommunitySession notes are ephemeral member content that would burn DeepL quota on every edit. Both models deliberately omit the trait. tests/Feature/DeepLTranslationTest.php asserts this; do not add the trait to either model.

## Session "past" reads the stored ends_at column
CommunitySession stores `ends_at` (indexed) and the model's `booted()` hook keeps it derived from `starts_at + duration_minutes` on every save. Scopes `upcoming()` and `past()`, `SessionFeed`, and the admin lifecycle filter all compare that column so the query stays an indexed comparison. Never re-derive an end time from `starts_at` in a query, and never write `ends_at` by hand.
