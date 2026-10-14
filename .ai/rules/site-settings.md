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

## Mobile auth is the first item in the opened drawer
On viewports below `ma-lg`, the SiteNav header puts the logo and hamburger on one line, then NL/EN/FR on the next line, aligned right. AuthMenu (Log in, or the signed-in initial and name) is the first child of `#maison-nav-links`, in the gap before the links. The dropdown opens downward. The language switcher in that drawer is desktop-only (`ma-lg`). Hide that AuthMenu at `ma-lg`. Desktop auth stays in SiteTopbar. Mobile wordmark size is `--nav-logo-size` / `--nav-logo-tracking` / `--nav-logo-icon` in `resources/css/app.css` (defaults 14px / 0.16em / 28px); desktop stays `text-[20px]`. Mobile `--nav-h` is 118px so the second row fits; from 1100px it returns to 90px.

## Shared props read by the shell use Inertia::always
Any shared prop the shell reads on every render (SiteNav reads `foundingRegister`) must be `Inertia::always()` in HandleInertiaRequests. Realtime bells call `router.reload({ only: ['notifications'] })`, and a plain lazy shared prop is then missing from the response and from `sharedProps`, so the nav crashes with "Cannot read properties of undefined".

## The branded 404 shares the full shell props
A missing route-model binding (e.g. a deleted session id) throws before HandleInertiaRequests runs. The 404 renderer in bootstrap/app.php must call `Inertia::share(app(HandleInertiaRequests::class)->share($request))` before rendering `errors/404`, otherwise FrontendLayout renders without `foundingRegister`, `auth`, or `site` and SiteNav crashes.

## Close auth modal after login on public pages
AuthMenu swaps Log in for the gold profile initial when auth.user is set. FrontendLayout must clear userModal auth (and not auto-open auth) once authenticated, because login can return to a public Maison URL while the layout stays mounted.
