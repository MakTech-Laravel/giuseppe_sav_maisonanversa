---
paths:
  - 'app/Services/Newsletter/**'
---

# Newsletter

## Heritage Letter writes go through HeritageLetterSubscription
Public join, member topic preferences, signed unsubscribe, and profile email rebind all go through HeritageLetterSubscription — never write newsletter_subscribers from a controller. Any of the three topics on means list membership (subscribed + Brevo upsert); all off means unsubscribed + Brevo remove. SyncSubscriberToBrevo sends HERITAGE_LETTER / PRODUCT_UPDATES / EVENTS attributes on the single Heritage Letter list. Do not add a campaign composer. Never show Brevo, API keys, or provider sync status on member or public Heritage Letter UI; that notice is admin-only.
