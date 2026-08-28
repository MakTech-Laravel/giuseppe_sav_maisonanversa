---
paths:
  - 'resources/js/components/admin/**'
---

# Components Admin

## Legal Tiptap editor sanitizes paste and HTML
LegalRichTextEditor is Tiptap-first with an HTML source toggle and a separate Voorbeeld preview. Headings (H1–H6), font family, font size, and line height use icon popovers (ToolbarChoicePopover) matching the other toolbar toggles—not raw Select dropdowns. Typography controls also include TextStyleKit color/fill plus subscript/superscript, expandable details, and table row/column actions. Paste and HTML apply go through sanitizeLegalHtml. Do not add images, iframes, authored scripts, arbitrary CSS, or unlisted font families. immediatelyRender must stay false for Inertia SSR.

## Legal Tiptap typography toolbar
LegalRichTextEditor uses TextStyleKit for text color, background fill, font family, font size, and line height, plus subscript/superscript and table row/column controls. Paste and HTML apply still go through sanitizeLegalHtml. Do not add images, iframes, arbitrary CSS, or unlisted font families.

## Legal Tiptap typography toolbar
LegalRichTextEditor uses TextStyleKit for text color, background fill, font family, font size, and line height, plus subscript/superscript and table row/column controls. Paste and HTML apply still go through sanitizeLegalHtml. Do not add images, iframes, arbitrary CSS, or unlisted font families.
