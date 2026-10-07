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

## Mobile auth lives inside the opened drawer
On viewports below `ma-lg`, the SiteNav header is logo + hamburger only so the wordmark is not squeezed. AuthMenu (Log in / profile initial) sits inside `#maison-nav-links` with the language switcher. Desktop auth stays in SiteTopbar. Mobile wordmark size is `--nav-logo-size` / `--nav-logo-tracking` / `--nav-logo-icon` in `resources/css/app.css` (defaults 14px / 0.16em / 28px); desktop stays `text-[20px]`.

## Close auth modal after login on public pages
AuthMenu swaps Log in for the gold profile initial when auth.user is set. FrontendLayout must clear userModal auth (and not auto-open auth) once authenticated, because login can return to a public Maison URL while the layout stays mounted.
