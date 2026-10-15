---
paths:
  - app/Services/Community/ClubMerger.php
  - app/Services/Community/SessionNotificationService.php
---

# Services Community

## Club merge is hard delete
ClubMerger hard-merges into the survivor id: apply field sources + OR features + sports union from the admin payload, remapping community_sessions and corner_* translations, then permanently delete the duplicate. Never write ClubStatus::Merged for new merges; Merged remains only as a historical enum case.

## Session activity recipients
Every session event goes through SessionNotificationService (SessionNotice enum; in-app via notify_users + SessionNoticeMail, rendered per recipient locale via MailLocale, fallback maison.notification_fallback_locale = en). Join creates a pending request (confirmed + pending <= capacity) and notifies the host (JoinRequested). Host approve notifies the player (JoinConfirmed, .ics); an approval that fills the session notifies everyone (SessionFull, .ics) and admins. Reject -> RequestRejected to the player; withdraw of a pending request -> RequestWithdrawn to the host. Leave/remove of confirmed players notify the host, or everyone when the session was full (SpotOpened); remove also notifies the removed player. Cancel notifies everyone with the optional organiser message. Scores: participant submit -> ScoreSubmitted to host; approve (or host self-submit) -> ScoreApproved to all players; reject -> ScoreRejected to the submitter. Finish -> SessionFinished with the result to both teams AND the host (the only exception to 'the actor is never notified'). sessions:send-reminders sends Reminder ~24h before start once (reminder_sent_at) to confirmed players + host only, never pending/rejected. Admins (UserType::Admin) only get created, full and cancelled. Mail respects users.session_emails; in-app always sends. Call it after the write commits, and skip it when nothing changed. Admin hard delete sends nothing. NewPlayer remains only for historical notifications.

## Session deadlines and the 48h organiser alert
Sessions are never auto-cancelled. sessions:send-open-spots-alerts (every 15 min, atomic open_spots_alert_sent_at claim) sends OpenSpotsReminder to the host only, in-app + mail with the share link and a cancel button (session page ?cancel=1 opens CancelSessionDialog), when starts_at is within 48h but more than 2h away and confirmed < capacity; pending requests do not fill places. Registration closes at starts_at - REGISTRATION_CLOSES_HOURS (2): acceptsRequests() and join refuse, cards expose registration_closed. Players leave and the host decides earlier requests until starts_at (hasStarted), not ends_at; leave still sends E4. No extra mail for late joiners: E5 only reaches players confirmed when it fires; E2 (JoinConfirmed) adds a 'today/tomorrow at HH:MM' line (SessionNotice::startsSoonLine) when the start is within 24h. Notice locale = users.locale via MailLocale, falling back to maison.notification_fallback_locale (en), not the site default nl; members set it on the profile page.
