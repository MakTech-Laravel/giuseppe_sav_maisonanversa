---
paths:
  - 'resources/js/pages/admin/legal-pages/**/*.tsx'
---

# Legal Pages

## Legal editor: Tiptap default, HTML toggle
Legal CMS edit is Tiptap visual by default (immediatelyRender: false for Inertia SSR). The toolbar Kop dropdown covers H1–H6 plus paragraph. Color, background fill, font family/size, line height, sub/sup, expandable details, and table edits must survive LegalHtml. Authored script, event handlers, and javascript: URLs stay stripped. A toolbar HTML toggle round-trips via getHTML()/setContent() through sanitizeLegalHtml(); stay on HTML mode if sanitize is blank. The editor surface is cream/light regardless of the admin theme. Inline Voorbeeld is a read-only LegalHtml view. Full storefront preview plus editable NL/EN/FR copy live on admin/legal-pages/show (Vertalingen dialog). Dutch source is edited here; translations are not inline in the editor. Do not chain useForm().transform().
