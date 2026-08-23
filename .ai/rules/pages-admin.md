---
paths:
  - 'resources/js/pages/admin/**/*.tsx'
---

# Pages Admin

## Never chain useForm transform()
Inertia React transform() does not return the form. Call form.transform(...) then form.submit()/post() as separate statements. For file updates, initialize useForm with Wayfinder update.form() (POST + ?_method=PUT) and submit with forceFormData: true — PHP does not parse multipart PUT bodies.

## Never chain useForm.transform()
Inertia React transform() does not return the form. Call form.transform(...) then form.submit()/post() as separate statements. For file updates, use Wayfinder update.form() (POST + ?_method=PUT) with forceFormData: true — PHP does not parse multipart PUT bodies.
