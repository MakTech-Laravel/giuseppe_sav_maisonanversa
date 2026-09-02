---
paths:
  - 'app/Models/*.php'
  - app/Models/Inquiry.php
  - app/Models/Product.php
  - app/Models/Club.php
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

## Product SEO is never auto-filled
meta_title, meta_description, meta_keywords, and og_image are optional. Never copy name, description, or gallery into those columns on save. MaisonSeo applies fallbacks at render time only: title from name, description from catalog copy, OG image from the first gallery image then the house default, and omitted keywords.

## Product SEO is never auto-filled
Product SEO columns stay empty when unset. Do not copy name, description, or gallery into them. MaisonSeo applies fallbacks at render time only.

## Inquiries are not DeepL-translated
Contact booking, consult, and feedback share the Inquiry model. Do not add TranslatesWithDeepL. Store a stable Dutch subject from InquiryType, not the translated heading. Cap is 2 submissions per type per 24 hours, matching IP or device cookie or user_id.

## Product public_at is Founding Circle early access
Nullable products.public_at is the public release timestamp. Published products with a future public_at are storefront-visible and checkoutable only via Product::visibleTo() / isVisibleTo() for Founding Circle members and staff. Null public_at means published equals public. Do not treat is_published alone as the storefront gate.

## Founding Circle inquiries carry a 12h SLA
Contact and corner inquiries from users with the founding-circle role set Inquiry.priority. slaDueAt() is created_at plus Inquiry::SLA_HOURS (12). isSlaBreached() is true only while unseen and past due. Admin inbox sorts priority first and shows the countdown badge.

## Product public_at is early access
Nullable products.public_at is the public release time. Until then, published products are visible and buyable only through Product::visibleTo / isVisibleTo for Founding Circle members and staff. Null public_at keeps published equal to public.

## One club database with partner badge
Session venues live in `clubs` only. Partner status is `clubs.is_partner` with the UI badge copy `Maison Anversa Partner Club`. Do not treat `partner_clubs` (Club Corner marketing CMS) as session venues or merge it into `clubs`. Club must never use TranslatesWithDeepL.
