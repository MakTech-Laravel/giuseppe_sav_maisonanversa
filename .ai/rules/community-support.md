---
paths:
  - 'app/Services/Locale/**,app/Services/Community/SessionNotificationService.php,app/Support/MailLocale.php,config/maison.php'
---

# Community Support

## Site language syncs users.locale for mail
LocalePreferenceService::remember persists cookie/session and, for authenticated customers, users.locale when the route locale differs. Session notices and digests still group by MailLocale::resolve(users.locale). maison.notification_fallback_locale is nl (Belgian default), not en.
