<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class PostLoginRedirectService
{
    /**
     * Resolve the post-authentication redirect URL for the given user.
     */
    public function urlFor(User $user, ?Request $request = null): string
    {
        $locale = $this->resolveLocale($request);

        if ($user->isAdmin()) {
            return route('admin.dashboard', ['locale' => $locale]);
        }

        return route('member.dashboard', ['locale' => $locale]);
    }

    /**
     * Resolve the dashboard URL without an intended redirect check.
     */
    public function dashboardUrlFor(User $user, ?Request $request = null): string
    {
        return $this->urlFor($user, $request);
    }

    /**
     * Resolve locale from route param, session, or application default.
     */
    public function resolveLocale(?Request $request = null): string
    {
        $request ??= request();

        $routeLocale = $request->route('locale');

        if (is_string($routeLocale) && in_array($routeLocale, config('maison.locales'), true)) {
            return $routeLocale;
        }

        $sessionLocale = $request->hasSession()
            ? $request->session()->get('locale')
            : null;

        if (is_string($sessionLocale) && in_array($sessionLocale, config('maison.locales'), true)) {
            return $sessionLocale;
        }

        return config('maison.default_locale');
    }
}
