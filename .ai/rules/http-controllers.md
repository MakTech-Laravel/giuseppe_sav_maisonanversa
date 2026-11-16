---
paths:
  - 'resources/js/hooks/use-flash-toast.ts,resources/js/components/notifications/**,app/Http/Controllers/**/*Controller.php'
---

# Http Controllers

## Toast copy uses English source keys
Flash toasts use English source strings via __() (lang/nl.json|fr.json). showFlashToast and validation/error toasts run translate() on the client. Notification toasts t() title/message/type_label/link_label. Keys are the English copy (e.g. __('Profile updated.')). Never invent Dutch/French in code — only dictionary values.

## No Dutch literals as translation keys
lang/nl.json and lang/fr.json are keyed by English text and English has no dictionary, so a Dutch literal passed to __() or t() (also via data arrays, enum labels, label_key props, mail subjects and Blade mails) renders Dutch on every locale except nl. Always pass the English source string and put the Dutch and French values in the dictionaries. Storefront Dutch is informal "je/jouw" except Club Corner marketing copy, which is formal "u/uw" per the client PDF. TranslationCoverageTest enforces this for app/, resources/views and the public Inertia pages. Corner pipeline Open status uses the key `OPEN` (FR `OUVERT`) so it does not collide with the button key `Open`.
