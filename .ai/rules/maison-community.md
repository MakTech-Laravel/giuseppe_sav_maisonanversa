---
paths:
  - 'resources/js/components/maison/community/**'
---

# Maison Community

## Community tabs are URL-based
Feed, Club Corners, Sessions, and Events must be Inertia Links, not local tab state. Club Corners is maison.community?tab=courts. Session and event pages reuse CommunityTabs instead of breadcrumbs. Do not reintroduce useState tab switching on the community layout.
