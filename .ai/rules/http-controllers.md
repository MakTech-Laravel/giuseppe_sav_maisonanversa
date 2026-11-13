---
paths:
  - 'resources/js/hooks/use-flash-toast.ts,resources/js/components/notifications/**,app/Http/Controllers/**/*Controller.php'
---

# Http Controllers

## Toast copy uses English source keys
Flash toasts use English source strings via __() (lang/nl.json|fr.json). showFlashToast and validation/error toasts run translate() on the client. Notification toasts t() title/message/type_label/link_label. Keys are the English copy (e.g. __('Profile updated.')). Never invent Dutch/French in code — only dictionary values.
