---
paths:
  - 'app/Mail/**'
---

# Mail

## Auth emails use Maison branded shell
Password reset, email verification, and SessionJoined mail go through Maison HTML mailables (ResetPasswordMail, VerifyEmailMail, SessionJoinedMail) with emails/layouts/maison and logo-icon.jpg. Wire Fortify defaults via ResetPassword::toMailUsing / VerifyEmail::toMailUsing in AppServiceProvider; always set ->to() on returned mailables. Do not revert to Laravel Markdown MailMessage.
