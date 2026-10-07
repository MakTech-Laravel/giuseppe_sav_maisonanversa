---
paths:
  - 'resources/js/pages/maison/home.tsx,resources/js/components/maison/home/**,app/Support/SessionFeed.php,app/Http/Controllers/MaisonController.php'
---

# Controllers

## Home surfaces joinable sessions with auth-gated Join
MaisonController::home passes upcomingSessions from SessionFeed::joinable (limit 3, not full) plus openSessionsThisWeek. SessionFeed::toCard/present accept ?User and redact names for guests. HomeSessions mounts after HomeStory with HomeSessionCard (PDF copy). Guest Join stores pendingSessionJoinId and openAuth('login'), then useResumePendingSessionJoin POSTs community.sessions.join. Keep the sessions index auth-only.
