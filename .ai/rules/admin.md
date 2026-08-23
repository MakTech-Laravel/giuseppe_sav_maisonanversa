---
paths:
  - app/Http/Controllers/Admin/CommunityEventController.php
---

# Admin

## Community events have thumbnails and auth bookings
Community events support optional thumbnail uploads (stored on public disk under community-events/). Frontend community RSVP requires authentication only (auth middleware); capacity is enforced server-side. Admin event show exposes bookings with name, email, and booked_at.
