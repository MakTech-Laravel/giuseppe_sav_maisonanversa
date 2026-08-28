---
paths:
  - 'resources/js/components/admin/**'
---

# Components Admin

## Legal Tiptap editor sanitizes paste and HTML
LegalRichTextEditor is Tiptap-first with an HTML source toggle and a separate Voorbeeld preview. Paste and HTML apply go through sanitizeLegalHtml. immediatelyRender must stay false for Inertia SSR.
