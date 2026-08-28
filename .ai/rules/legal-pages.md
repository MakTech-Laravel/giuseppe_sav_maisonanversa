---
paths:
  - 'resources/js/pages/admin/legal-pages/**/*.tsx'
---

# Legal Pages

## Legal editor: Tiptap default, HTML toggle
Legal CMS edit is Tiptap visual by default (immediatelyRender: false for Inertia SSR). A toolbar HTML toggle round-trips via getHTML()/setContent() through sanitizeLegalHtml(); stay on HTML mode if sanitize is blank. The editor surface is cream/light regardless of the admin theme. Inline Voorbeeld is a read-only LegalHtml view. Full storefront preview plus editable NL/EN/FR copy live on admin/legal-pages/show (Vertalingen dialog). Do not chain useForm().transform().
