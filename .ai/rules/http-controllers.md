---
paths:
  - 'resources/js/hooks/use-flash-toast.ts,resources/js/components/notifications/**,app/Http/Controllers/**/*Controller.php'
---

# Http Controllers

## Toast copy uses Dutch source keys
Flash toasts use Dutch source strings via __() (lang/en.json|fr.json). showFlashToast and validation/error toasts run translate() on the client. Notification toasts t() title/message/type_label/link_label. Never flash raw English like "Customer created successfully." or English __() keys like __('Profile updated.').
