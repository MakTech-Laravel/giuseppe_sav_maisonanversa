---
paths:
  - 'app/Http/Controllers/Maison/GuestOrderReturnController.php,app/Services/Returns/GuestReturnLink.php'
---

# Services Returns

## Guest return signed links
Guest checkout orders (orders.user_id null) start returns via temporary signed URLs (maison.guest-returns.*), TTL from config maison.returns.guest_link_ttl_hours. Signed create seeds a session for POST store; show/tracking stay signed with email query. Never issue guest links for account orders — those use member returns.
