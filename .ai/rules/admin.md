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
On create/update, Dutch source fields queue TranslateModelJob (never sync DeepL in HTTP). Event show exposes a Vertalingen dialog: manual EN/FR edits via PUT `admin.events.translations.update`; re-queue via POST `admin.events.translate`. Edit form stays Dutch-only (source of truth).
