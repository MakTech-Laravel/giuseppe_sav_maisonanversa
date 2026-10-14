---
paths:
  - app/Services/Community/ClubMerger.php
  - app/Services/Community/SessionNotificationService.php
---

# Services Community

## Club merge is hard delete
ClubMerger hard-merges into the survivor id: apply field sources + OR features + sports union from the admin payload, remapping community_sessions and corner_* translations, then permanently delete the duplicate. Never write ClubStatus::Merged for new merges; Merged remains only as a historical enum case.

## Session activity recipients
Every session event goes through SessionNotificationService (SessionNotice enum; in-app via notify_users + SessionNoticeMail, rendered per recipient locale). Join notifies the host (NewPlayer) and the joiner (JoinConfirmed, with .ics); a join that fills the session notifies everyone (SessionFull, with .ics). Leave/remove notify the host, or everyone when the session was full (SpotOpened); remove also notifies the removed player. Cancel notifies everyone with the optional organiser message. sessions:send-reminders sends Reminder ~24h before start once (reminder_sent_at). Admins (UserType::Admin) only get created, full and cancelled. The actor is never notified. Mail respects users.session_emails; in-app always sends. Call it after the write commits, and skip it when nothing changed (repeat join, already-cancelled session, zero rows deleted). Admin hard delete sends nothing.
