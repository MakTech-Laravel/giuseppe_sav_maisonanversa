---
paths:
  - resources/js/pages/maison/house.tsx
  - resources/js/pages/maison/community.tsx
---

# Maison

## House floorplan on every viewport
The SVG elevation on /huis is the room navigation at every breakpoint. Do not hide it below 640px. MaisonRoomList stays commented in house.tsx as a fallback.

## Club Corner frozen as marketing only in V1
Keep public `/corner`, `partner_clubs` CMS, and corner inquiries. Do not expose `community_courts` as a member Community tab or product surface. Legacy `?tab=courts` redirects to `community.clubs.index`. Hide admin courts from the sidebar; do not add new Club Corner digital features in V1. Partner-fee / sell-alongside wording is copy only.

## Club Corner copy (revision_2 PDF)
`/corner` NL uses formal "u/uw"; FR uses "vous". Package item 03 is Heritage brochures (not Passports — passports stay for the 100 owners). Network cities: Antwerp, Ghent, Brussels, Amsterdam, Paris (status key `OPEN` / FR `OUVERT`, not the button key `Open`), plus a frontend "Your city?" CTA tile. Temporary impressie: `public/images/corner/club-corner-impressie.png` via imagery asset `club-corner-impressie`.
