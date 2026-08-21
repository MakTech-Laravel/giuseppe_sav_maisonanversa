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
        return $this->redirectAuthenticatedOrModal($request, 'login');
    }

    public function register(Request $request): RedirectResponse
    {
        return $this->redirectAuthenticatedOrModal($request, 'register');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        return $this->redirectAuthenticatedOrModal($request, 'forgot');
    }

    public function twoFactor(Request $request): RedirectResponse
    {
        return $this->redirectWithModal($request, 'two-factor');
    }

    private function redirectAuthenticatedOrModal(Request $request, string $view): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            return redirect()->to($this->redirects->urlFor($user, $request));
        }

        return $this->redirectWithModal($request, $view);
    }

    private function redirectWithModal(Request $request, string $view): RedirectResponse
    {
        $locale = $this->redirects->resolveLocale($request);

        $redirect = redirect()
            ->route('maison.home', ['locale' => $locale])
            ->with('open_auth_modal', $view);

        if ($request->hasSession() && $request->session()->has('status')) {
            $redirect->with('status', $request->session()->get('status'));
        }

        return $redirect;
    }
}
