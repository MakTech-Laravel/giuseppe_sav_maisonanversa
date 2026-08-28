---
paths:
  - 'resources/js/pages/admin/site-settings/**'
  - 'resources/js/components/maison/shell/**'
  - 'resources/js/lib/maison-navigation.ts'
  - 'app/Support/Seo/**'
  - 'app/Models/SiteSetting.php'
---

# Site Settings

## Site settings are contact and atelier only
Site settings store contact channels, Instagram, and atelier coordinates for the contact-page map. The house has an atelier in Antwerp, not a retail boutique — do not use "boutique" in UI copy. Do not add a CMS announcement or TranslatesWithDeepL on SiteSetting; the topbar uses the i18n key `Eerste Editie — Beperkt tot 100 Stuks`. The admin form uses AdminResourceShell at full width like legal and SEO edit pages.

## Storefront reads SiteSetting, not placeholders
Footer Instagram and Pers resolve from `site.instagramUrl` and `site.emailPressHref` via channel markers. Organization JSON-LD uses telephone, hello email, and Instagram `sameAs` from `SiteSetting::current()`. Do not hardcode `press@` or Instagram URLs as the live hrefs.
