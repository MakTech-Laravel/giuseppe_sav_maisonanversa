---
paths:
  - 'app/Support/Html/**'
---

# Html

## Legal HTML allowlist sanitizer
Legal page bodies are HTML. Sanitize with LegalHtml (allowlist tags/attrs, drop script/iframe/on*/javascript:/style except text-align) on save, DeepL in/out, and public render. Legacy markdown (no tags) is converted to HTML inside sanitize() so old rows still render. Client sanitizeLegalHtml() is defense in depth only. Never dangerouslySetInnerHTML unsanitized markup.
