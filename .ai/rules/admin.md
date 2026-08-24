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

## Event translations: same pattern as FAQ
Community events follow the FAQ translation flow. Source text lives on community_events (title, description, location). Create/update queues TranslateModelJob for all locales (nl, en, fr) via TranslatesWithDeepL with auto-detect. Edit form always shows source columns. Vertalingen dialog on show saves all locales via PUT `admin.events.translations.update`; single-locale or bulk DeepL retranslate via POST `admin.events.translate` with optional `target_locale`.
