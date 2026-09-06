---
paths:
  - app/Services/Community/ClubMerger.php
---

# Services Community

## Club merge is hard delete
ClubMerger hard-merges into the survivor id: apply field sources + OR features + sports union from the admin payload, remapping community_sessions and corner_* translations, then permanently delete the duplicate. Never write ClubStatus::Merged for new merges; Merged remains only as a historical enum case.
