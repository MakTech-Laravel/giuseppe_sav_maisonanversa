---
paths:
  - 'app/Support/SessionFeed.php,resources/js/lib/session-format.ts,config/app.php,app/Http/Controllers/Community/SessionController.php'
---

# Controllers Community

## Session times are Europe/Brussels
APP_TIMEZONE defaults to Europe/Brussels. All JS session formatters in session-format.ts force timeZone Europe/Brussels (never the browser TZ). `combineDateAndTime()` must embed the Brussels UTC offset so draft preview does not reinterpret form wall clock in the browser TZ. Server ISO from SessionFeed already includes the offset. PHP mail/card/notice formatters use app TZ Carbon. Do not display session starts_at with raw toLocaleString().
