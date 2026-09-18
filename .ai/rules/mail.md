---
paths:
  - 'app/Mail/**'
  - 'app/Services/Auth/PasswordResetOtpService.php'
---

# Mail

## Auth emails use Maison branded shell
Email verification and SessionJoined mail go through Maison HTML mailables (VerifyEmailMail, SessionJoinedMail) with emails/layouts/maison and logo-icon.jpg. Wire VerifyEmail::toMailUsing in AppServiceProvider; always set ->to() on returned mailables. Do not revert to Laravel Markdown MailMessage.

## Password reset uses OTP, not links
Password reset emails send a 6-digit OTP via ResetPasswordMail (no reset URL). Codes are hashed in password_reset_tokens, expire in otp_expire minutes (default 10), and are limited to MAX_ATTEMPTS. Routes: password.email (send) and password.update (verify+reset). Never put the OTP or reset token in a tracked link.
