---
paths:
  - 'app/Services/Newsletter/**'
  - 'app/Http/Controllers/Member/DashboardController.php'
  - 'resources/js/pages/member/letter.tsx'
  - 'resources/js/pages/member/email-preferences.tsx'
---

# Newsletter

## Heritage Letter writes go through HeritageLetterSubscription
Public join, member topic preferences, signed unsubscribe, and profile email rebind all go through HeritageLetterSubscription — never write newsletter_subscribers from a controller. Any of the three topics on means list membership (subscribed + Brevo upsert); all off means unsubscribed + Brevo remove. SyncSubscriberToBrevo sends HERITAGE_LETTER / PRODUCT_UPDATES / EVENTS attributes on the single Heritage Letter list. Do not add a campaign composer. Never show Brevo, API keys, or provider sync status on member or public Heritage Letter UI; that notice is admin-only.

## Member Heritage Letter is subscription history
The member Heritage Letter page (`/member/letter`) lists every newsletter_subscribers row linked to the account: `user_id` equals the member, or the row email equals the account email. Topic checkboxes live on Email preferences (`/member/email-preferences`) and write only the account-email row. Guest signups stay email-only until register or login claims an unowned matching row; a logged-in customer may attach additional emails they submit, but must not steal a row already owned by another user.
