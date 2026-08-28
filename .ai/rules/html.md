---
paths:
  - 'app/Support/Html/**'
---

# Html

## Legal HTML allowlist sanitizer
Legal page bodies are HTML. Sanitize with LegalHtml (allowlist tags/attrs, drop script/iframe/on*/javascript:) on save, DeepL in/out, and public render. Headings h1–h6, details/summary, and safe id attributes are allowed for structure and native interactions. Style is not open-ended: keep text-align, hex/rgb color and background-color, allowlisted font-family, bounded font-size, and unitless line-height 1–3. Strip named colors, url(), expression(), and !important. Marks may keep data-color when it is a safe hex/rgb value. sub/sup are allowed. Legacy markdown (no tags) is converted to HTML inside sanitize(). Client sanitizeLegalHtml() must mirror the same rules. Never dangerouslySetInnerHTML unsanitized markup. Never keep authored script, event handlers, or javascript: URLs.

## Legal HTML style allowlist
LegalHtml keeps text-align plus hex/rgb color, background-color, allowlisted font-family (Montserrat, Baskervville, Georgia, sans-serif, serif), bounded font-size (10-32px / 0.75-2.5em), and unitless line-height 1-3. Named colors, url(), expression(), and !important are stripped. Marks may keep data-color when it is a safe hex/rgb value. sub/sup are allowed. Mirror the same rules in resources/js/lib/legal-html.ts.

## Legal HTML style allowlist
LegalHtml keeps text-align plus hex/rgb color, background-color, allowlisted font-family, bounded font-size, and unitless line-height 1-3. Named colors, url(), expression(), and !important are stripped. Marks may keep data-color when it is a safe hex/rgb value. sub/sup are allowed. Mirror the same rules in resources/js/lib/legal-html.ts.

## Legal HTML style allowlist
LegalHtml keeps text-align plus hex or rgb color, background-color, allowlisted font-family, bounded font-size, and unitless line-height 1-3. Named colors, url(), expression(), and !important are stripped. Marks may keep data-color when it is a safe hex or rgb value. sub and sup are allowed. Mirror the same rules in resources/js/lib/legal-html.ts.
