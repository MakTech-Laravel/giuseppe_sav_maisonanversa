---
paths:
  - 'resources/js/components/maison/home/**'
---

# Home

## Home sessions match Phase 1 PDF briefing
HomeSessions sits immediately after HomeStory on Warm Ivory (`bg-cream`). Use `HomeSessionCard`, not the community `SessionCard`. Props: `upcomingSessions` from `SessionFeed::joinable()` (limit 3, not full) and `openSessionsThisWeek`. Guest cards redact player names (initials only). Copy follows the PDF NL/EN/FR table (`Na de koffie, de baan.`, `Doe mee`, `Je speelt mee ✓`). Storefront/home Dutch copy is informal "je/jouw" (client decision, Nov 2026; French stays "vous") — except `/corner`, which uses formal "u/uw" per the Club Corner PDF. Guest Join still uses pendingSessionJoinId + openAuth login.

## Homepage verhaalteksten (revision_2 PDF)
Home intro/story/antwerp/manifesto/unboxing founder-letter/content-grid founder card follow the Homepage verhaalteksten NL/EN/FR PDF. No personal name (Yusuf) on the home page. Manifesto closing line stays English in all locales: `Every legacy begins with a first chapter.` Do not edit StoryPage CMS in that pass (client: story page already updated).

## Home sessions empty state is a dashed right panel
When upcomingSessions is empty, keep the Warm Ivory two-column shell: left still shows Community · Sessies / Na de koffie, de baan. / intro (no Plan/Browse/week counter). Right replaces cards with a dashed #DDD0C2 / #FBF8F4 panel containing the empty label, Plan de eerste… title, body, and Plan CTA only. Do not center-stack empty copy in place of the split.
