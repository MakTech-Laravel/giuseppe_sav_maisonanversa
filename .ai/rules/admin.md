---
paths:
  - app/Http/Controllers/Admin/CommunityEventController.php
---

# Admin

## Community events have thumbnails and auth bookings
Community events support optional thumbnail uploads (stored on public disk under community-events/). Frontend community RSVP requires authentication only (auth middleware); capacity is enforced server-side. Admin event show exposes bookings with name, email, and booked_at.

## Event bookings expose user_id
Admin event show bookings include user_id so the UI can link each booking row to admin.customers.show.
