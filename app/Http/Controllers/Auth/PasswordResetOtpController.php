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
            'message' => __('Als dit e-mailadres bij ons bekend is, ontvangt u binnen enkele minuten een code.'),
        ]);

        return back()->with('password_reset_otp_sent', true);
    }

    public function reset(ResetPasswordWithOtpRequest $request): RedirectResponse
    {
        $this->otps->reset($request->validated());

        $locale = $this->redirects->resolveLocale($request);

        return redirect()
            ->route('maison.home', ['locale' => $locale])
            ->with('open_auth_modal', 'login')
            ->with('status', __('Uw wachtwoord is opnieuw ingesteld. U kunt nu inloggen.'));
    }
}
