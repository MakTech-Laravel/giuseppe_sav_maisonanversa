<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthModalRedirectController extends Controller
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function login(Request $request): RedirectResponse
    {
        return $this->redirectWithModal($request, 'login');
    }

    public function register(Request $request): RedirectResponse
    {
        return $this->redirectWithModal($request, 'register');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        return $this->redirectWithModal($request, 'forgot');
    }

    public function twoFactor(Request $request): RedirectResponse
    {
        return $this->redirectWithModal($request, 'two-factor');
    }

    private function redirectWithModal(Request $request, string $view): RedirectResponse
    {
        $locale = $this->redirects->resolveLocale($request);

        return redirect()
            ->route('maison.home', ['locale' => $locale])
            ->with('open_auth_modal', $view);
    }
}
