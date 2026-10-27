---
paths:
  - 'resources/js/hooks/use-locale.ts,resources/js/hooks/use-flash-toast.ts,resources/js/components/i18n-provider.tsx'
---

# Components

## Switch i18n language before locale navigation
useLocale.switchLocale must await switchInstanceLocale(i18n, next) before router.visit — loadDictionary alone leaves i18n.language on the previous locale, so httpException/flash toasts during the visit speak the old language. Flash toasts resolve lng from the pending visit URL when that bundle is already loaded.
