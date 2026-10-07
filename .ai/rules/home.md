---
paths:
  - 'resources/js/components/maison/home/**'
---

# Home

## Home sessions match Phase 1 PDF briefing
HomeSessions sits immediately after HomeStory on Warm Ivory (`bg-cream`). Use `HomeSessionCard`, not the community `SessionCard`. Props: `upcomingSessions` from `SessionFeed::joinable()` (limit 3, not full) and `openSessionsThisWeek`. Guest cards redact player names (initials only). Copy follows the PDF NL/EN/FR table (`Na de koffie, de baan.`, `Doe mee`, `Je speelt mee ✓`). Guest Join still uses pendingSessionJoinId + openAuth login.
