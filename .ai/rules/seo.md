---
paths:
  - 'app/Support/Seo/**'
---

# Seo

## Product SEO fallbacks live only in MaisonSeo
On product show pages, empty meta_title becomes `{name} — Maison Anversa`, empty meta_description uses the product description, empty og_image uses the first gallery image then config maison.seo.image, and empty keywords omit the meta tag. Do not persist those fallbacks on the product.

## Page SeoMeta titles go through __() after DeepL
`MaisonSeo::copy()` prefers `SeoMeta::translated()` (DeepL). Wrap the result in `__()` so seeded Dutch defaults still resolve via `lang/{en,fr}.json` when a locale row is missing — otherwise EN/FR document titles leak NL.
