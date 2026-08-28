---
paths:
  - 'app/Services/Translation/**'
---

# Translation

## DeepL host must include https scheme
DEEPL_API_HOST must be an absolute URL (https://api-free.deepl.com). A bare hostname like api-free.deepl.com makes TranslateModelJob fail with "URI must include a scheme and host". DeepLTranslator::host() now prepends https:// when the scheme is missing.

## LegalPage DeepL uses HTML tag handling
Only LegalPage sets translationUsesHtml() true. TranslateModelJob sanitizes HTML before and after DeepL, and DeepLTranslator::translateMany(..., html: true) must send tag_handling=html so tags, attributes, and classes are not translated. Journal, FAQ, and posts stay plain text.
