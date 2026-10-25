---
paths:
  - app/Http/Controllers/Community/ClubController.php
  - app/Http/Controllers/Community/SessionController.php
---

# Community

## Club search requires two characters
Club autocomplete returns an empty list until the query is at least two characters. Keep the frontend ClubSearchField in sync: do not fetch until trim().length >= 2, and put results in a fixed-height ScrollArea.

## Host toggle requests_required on create
StoreCommunitySessionRequest accepts requests_required (boolean, default false). Create form checkbox Aanvragen goedkeuren; omit or false means instant join. Join path branches on CommunitySession::requiresJoinRequests().
