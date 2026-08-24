---
paths:
  - 'resources/js/pages/admin/**/*.tsx'
---

# Pages Admin

## Never chain useForm transform()
Inertia React transform() does not return the form. Call form.transform(...) then form.submit()/post() as separate statements. For file updates, initialize useForm with Wayfinder update.form() (POST + ?_method=PUT) and submit with forceFormData: true — PHP does not parse multipart PUT bodies. Regenerate form helpers after route changes: `php artisan wayfinder:generate --with-form`.

## Editorial content lives in Journal
Do not restore the demo admin Posts CRUD (title + attachments). Public and admin editorial articles use JournalArticle. The Post model remains only for the FileUpload demo and PostAttachmentController.
