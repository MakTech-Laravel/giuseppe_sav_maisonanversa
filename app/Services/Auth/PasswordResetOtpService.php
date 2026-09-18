<?php

namespace App\Services\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Support\MailLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetOtpService
{
    public const OTP_LENGTH = 6;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private ResetUserPassword $resetsPasswords) {}

    /**
     * Always succeed outwardly to avoid account enumeration.
     */
    public function send(string $email, ?string $locale = null): void
    {
        $email = $this->normalizeEmail($email);

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $otp = $this->generateOtp();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($otp),
                'attempts' => 0,
                'created_at' => now(),
            ],
        );

        $mailLocale = MailLocale::resolve($locale ?? $user->locale);

        Mail::to($user->email)->send(new ResetPasswordMail(
            otp: $otp,
            expireMinutes: $this->expireMinutes(),
            locale: $mailLocale,
        ));
    }

    /**
     * @param  array{email: string, otp: string, password: string, password_confirmation: string}  $input
     */
    public function reset(array $input): void
    {
        $email = $this->normalizeEmail($input['email']);
        $otp = preg_replace('/\D+/', '', (string) $input['otp']) ?? '';

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($row === null || $this->isExpired($row->created_at)) {
            $this->failInvalidOtp();
        }

        if ((int) $row->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $this->failTooManyAttempts();
        }

        if ($otp === '' || strlen($otp) !== self::OTP_LENGTH || ! Hash::check($otp, $row->token)) {
            $attempts = (int) $row->attempts + 1;

            DB::table('password_reset_tokens')->where('email', $email)->update([
                'attempts' => $attempts,
            ]);

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                $this->failTooManyAttempts();
            }

            $this->failInvalidOtp();
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $this->failInvalidOtp();
        }

        $this->resetsPasswords->reset($user, $input);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function expireMinutes(): int
    {
        return (int) config('auth.passwords.users.otp_expire', 10);
    }

    private function generateOtp(): string
    {
        $max = (10 ** self::OTP_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function isExpired(mixed $createdAt): bool
    {
        return now()->subMinutes($this->expireMinutes())->greaterThan($createdAt);
    }

    private function failInvalidOtp(): never
    {
        throw ValidationException::withMessages([
            'otp' => __('De code is ongeldig of verlopen.'),
        ]);
    }

    private function failTooManyAttempts(): never
    {
        throw ValidationException::withMessages([
            'otp' => __('Te veel pogingen. Vraag een nieuwe code aan.'),
        ]);
    }
}
