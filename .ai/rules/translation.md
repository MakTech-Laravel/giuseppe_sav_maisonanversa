---
paths:
  - 'app/Services/Translation/**'
---

# Translation

## DeepL host must include https scheme
DEEPL_API_HOST must be an absolute URL (https://api-free.deepl.com). A bare hostname like api-free.deepl.com makes TranslateModelJob fail with "URI must include a scheme and host". DeepLTranslator::host() now prepends https:// when the scheme is missing.
