---
paths:
  - 'resources/js/components/maison/home/**'
---

# Home

## Home sessions match Phase 1 PDF briefing
HomeSessions sits immediately after HomeStory on Warm Ivory (`bg-cream`). Use `HomeSessionCard`, not the community `SessionCard`. Props: `upcomingSessions` from `SessionFeed::joinable()` (limit 3, not full) and `openSessionsThisWeek`. Guest cards redact player names (initials only). Copy follows the PDF NL/EN/FR table (`Na de koffie, de baan.`, `Doe mee`, `Je speelt mee ✓`). Guest Join still uses pendingSessionJoinId + openAuth login.

## Home sessions empty state is a dashed right panel
When upcomingSessions is empty, keep the Warm Ivory two-column shell: left still shows Community · Sessies / Na de koffie, de baan. / intro (no Plan/Browse/week counter). Right replaces cards with a dashed #DDD0C2 / #FBF8F4 panel containing the empty label, Plan de eerste… title, body, and Plan CTA only. Do not center-stack empty copy in place of the split.
