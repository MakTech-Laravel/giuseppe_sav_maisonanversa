---
paths:
  - 'app/Models/*.php'
  - app/Models/Inquiry.php
---

# Models

## Club DeepL is corner copy only
Club uses TranslatesWithDeepL with an explicit `$translatable` whitelist of `corner_title`, `corner_body`, and `corner_location` only. Venue proper nouns (name, street, city, country, postal_code) must never be translated. PartnerClub and CommunityCourt were merged into Club — do not recreate those models.

## CommunitySession translates notes only
CommunitySession uses TranslatesWithDeepL with `$translatable = ['notes']`, auto-detect, and all maison locales (nl, en, fr), matching CommunityPost and FAQ. Display notes via `translated()` in SessionFeed and admin show. Do not auto-discover other columns, and never add venue columns to Club's DeepL whitelist.

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

## Unified Club filters replace PartnerClub and CommunityCourt
PartnerClub and CommunityCourt are retired. Use Club with is_session_venue, is_partner, show_on_corner_page + corner_pipeline_status, and has_corner + corner_published. Session search uses sessionVenues(); /corner uses cornerPage(); community map uses publishedCorners(). DeepL whitelist is corner_* only.

## Club merge is hard delete
ClubMerger merges into the survivor id: apply field sources + feature flags + sports from the admin payload, remapping community_sessions and corner_* translations, then permanently delete the duplicate. Never write ClubStatus::Merged for new merges; Merged remains only as a historical enum case.
