---
paths:
  - app/Http/Controllers/Admin/CommunityEventController.php
---

# Admin

## Community events have thumbnails and auth bookings
Community events support optional thumbnail uploads (stored on public disk under community-events/). Frontend community RSVP requires authentication only (auth middleware); capacity is enforced server-side. Admin event show exposes bookings with name, email, booked_at, and user_id.

## Event bookings expose user_id
Admin event show bookings include user_id so the UI can link each booking row to admin.customers.show.

## Event index search and stored status
Admin events index supports `search` and `status` (opening|ongoing|closed) query params against the `community_events.status` column. Admins change status via PATCH `admin.events.status`; new events default to opening. Titles/locations use translated() for locale. CommunityEvent `$translatable` is title, description, location only.

## Event status is a stored column
Community events store status (opening|ongoing|closed) on community_events.status. Index filters and row badges use that column. Do not derive filter status from starts_at.

## Event translations: auto DeepL + manual override
On create/update in NL, Dutch source fields queue TranslateModelJob (never sync DeepL in HTTP). When NL source text changes, existing translation rows are deleted and re-queued. Edit in EN/FR uses the regular update route: shared fields update community_events; text fields replace that locale's rows in translations (delete then insert). Event show Vertalingen dialog saves one target locale at a time via PUT `admin.events.translations.update` with target_locale; per-field DeepL via POST `admin.events.translate-column` (runs synchronously); bulk re-queue via POST `admin.events.translate`. Flash error when DEEPL_API_KEY is missing.
