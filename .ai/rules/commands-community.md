---
paths:
  - 'app/Console/Commands/Community/**'
---

# Commands Community

## Match card notice only after the session, 2+ players
sessions:send-match-cards (every 15 min) notifies host + confirmed players once (atomic claim on match_card_sent_at) after ends_at, only for non-cancelled sessions with >= maison.match_cards.min_players confirmed players and ended within notify_window_hours (48h) so deploys never mail old games. No notice before the match; the pre-match card is just available on the share page. session_emails=false skips the mail, in-app always goes out.
