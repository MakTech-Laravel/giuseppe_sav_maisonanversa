---
paths:
  - app/Http/Controllers/Maison/VerificationController.php
  - app/Http/Controllers/Maison/SessionShareController.php
  - app/Http/Controllers/Maison/GuestReturnLinkRequestController.php
---

# Controllers Maison

## Verify page resolves holder from order or claim
Public maison.verify shows authenticity for an EditionPiece token. Holder comes from the linked order (name + user) or, when order_id is null, the latest approved Founding Circle claim user. Never expose email; pass Dutch statusLabel keys for t(), and use translatedFormat for allocated/member-since dates. productName stays product->translated('name').

## Session share links are public by token, never by id
community_sessions.share_token (32 chars, set on create) is the only key for the public /{locale}/s/{token} page and card PNG routes. Anyone with the link sees the roster, the result and a Copy link button; only the organiser and confirmed players (CommunitySessionPolicy::shareCard) get the card preview, Share and Download. The PNG itself stays public because og:image crawlers are logged out; `?download=1` needs shareCard or a signed URL (the match card mail uses a 30-day URL::temporarySignedRoute). The mail/in-app CTA goes to community.sessions.show (login wall) so logged-out players land on the full card. Cards print initials + first name only (users.show_first_name_on_cards can hide the first name); last names never reach MatchCardPresenter output. Cancelled sessions keep the page but the card 404s.

## Guest returns request link by order reference
Public POST maison.returns.guest-link (throttle 5/hour) accepts MA-YYYY-#### + email. GuestReturnLink::findEligibleGuestOrder matches guest checkout only; queues GuestReturnLinkMail with signed create URL. Always flash the same success toast.
