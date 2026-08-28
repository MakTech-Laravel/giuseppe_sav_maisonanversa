---
paths:
  - app/Jobs/TranslateModelJob.php
---

# Jobs

## Skip missing model classes in TranslateModelJob
TranslateModelJob must treat missing/invalid modelClass as a no-op (try/catch around class_exists + is_subclass_of Model) because Composer autoload warnings become ErrorExceptions under Laravel. Stale jobs for deleted models like ProductSection must not fail the worker.

## Sanitize HTML around DeepL translation
When the model translationUsesHtml(), sanitize with LegalHtml before sending text to DeepL and again before writing the translation row. Pass the html flag into translateMany so tag_handling=html is set.
