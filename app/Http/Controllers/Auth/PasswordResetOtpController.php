<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordWithOtpRequest;
use App\Http\Requests\Auth\SendPasswordResetOtpRequest;
use App\Services\Auth\PasswordResetOtpService;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetOtpController extends Controller
{
    public function __construct(
        private PasswordResetOtpService $otps,
        private PostLoginRedirectService $redirects,
    ) {}

    public function create(Request $request, string $locale): Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->to($this->redirects->urlFor($request->user(), $request));
        }

        return Inertia::render('auth/reset-password', [
            'email' => (string) $request->string('email'),
            'passwordRules' => Password::defaults()?->toPasswordRulesString() ?? '',
        ]);
    }

    public function send(SendPasswordResetOtpRequest $request): RedirectResponse
    {
        $this->otps->send(
            email: $request->validated('email'),
            locale: $this->redirects->resolveLocale($request),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('If this email address is known to us, you will receive a code within a few minutes.'),
        ]);

        return back()->with('password_reset_otp_sent', true);
    }

    public function reset(ResetPasswordWithOtpRequest $request): RedirectResponse
    {
        $this->otps->reset($request->validated());

        $locale = $this->redirects->resolveLocale($request);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Password reset successfully. Please log in now.'),
        ]);

        return redirect()->route('maison.home', ['locale' => $locale]);
    }
}
