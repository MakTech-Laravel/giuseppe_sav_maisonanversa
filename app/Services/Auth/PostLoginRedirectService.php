<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Locale\LocalePreferenceService;
use Illuminate\Http\Request;

class PostLoginRedirectService
{
    public function __construct(public LocalePreferenceService $locales) {}

    /**
     * Resolve the post-authentication redirect URL for the given user.
     *
     * Relative paths keep Inertia XHR redirects on the current origin even when
     * APP_URL does not match the browser host (e.g. localhost vs 127.0.0.1).
     */
    public function urlFor(User $user, ?Request $request = null): string
    {
        $locale = $this->resolveLocale($request);

        if ($user->isAdmin()) {
            return route('admin.dashboard', ['locale' => $locale], absolute: false);
        }

        return route('member.dashboard', ['locale' => $locale], absolute: false);
    }

    /**
     * Honor url.intended only when it belongs to this user's area.
     */
    public function intendedUrlFor(User $user, Request $request): string
    {
        $home = $this->urlFor($user, $request);

        if (! $request->hasSession()) {
            return $home;
        }

        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '' || ! $this->isAllowedDestination($user, $intended)) {
            return $home;
        }

        return $intended;
    }

    /**
     * Resolve the dashboard URL without an intended redirect check.
     */
    public function dashboardUrlFor(User $user, ?Request $request = null): string
    {
        return $this->urlFor($user, $request);
    }

    /**
     * Resolve locale from route, cookie, session, Accept-Language, or default.
     */
    public function resolveLocale(?Request $request = null): string
    {
        return $this->locales->preferred($request ?? request());
    }

    private function isAllowedDestination(User $user, string $intended): bool
    {
        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        $path = '/'.trim($path, '/');
        $locales = implode('|', array_map(
            static fn (string $locale): string => preg_quote($locale, '#'),
            (array) config('maison.locales'),
        ));

        if (preg_match('#^/('.$locales.')/dashboard$#', $path) === 1) {
            return false;
        }

        if (preg_match('#^/('.$locales.')/admin(?:/|$)#', $path) === 1) {
            return $user->isAdmin();
        }

        if (preg_match('#^/('.$locales.')/member(?:/|$)#', $path) === 1) {
            return $user->isCustomer();
        }

        return true;
    }
}
