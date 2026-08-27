---
paths:
  - app/Http/Controllers/Community/ClubController.php
---

# Community

## Club search requires two characters
Club autocomplete returns an empty list until the query is at least two characters. Keep the frontend ClubSearchField in sync: do not fetch until trim().length >= 2, and put results in a fixed-height ScrollArea.
