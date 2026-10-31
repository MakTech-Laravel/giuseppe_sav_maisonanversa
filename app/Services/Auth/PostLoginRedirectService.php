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
     *
     * Always returns a relative path so login redirects stay on the browser origin.
     */
    public function intendedUrlFor(User $user, Request $request): string
    {
        $home = $this->urlFor($user, $request);

        if (! $request->hasSession()) {
            return $home;
        }

        $intended = $request->session()->pull('url.intended');
        $path = $this->normalizeToRelativePath(is_string($intended) ? $intended : null, $request);

        if ($path === null || ! $this->isAllowedDestination($user, $path)) {
            return $home;
        }

        return $path;
    }

    /**
     * Store a same-origin relative path as url.intended for post-login return.
     *
     * Used when auth middleware blocks a guest, and when the Maison auth modal
     * opens on a public page that never hit a protected route.
     *
     * Localized home is ignored for casual Log in (role dashboard after login).
     * Pass $forceHome when the guest was mid-action (Claim / checkout) so they
     * return to home; never overwrite a non-home intended with home.
     */
    public function remember(Request $request, ?string $url = null, bool $forceHome = false): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $candidate = $url ?? $this->requestUri($request);
        $path = $this->normalizeToRelativePath($candidate, $request);

        if ($path === null || $this->isAuthEntryPath($path)) {
            return;
        }

        if ($this->isLocalizedHomePath($path)) {
            if (! $forceHome) {
                return;
            }

            $existing = $request->session()->get('url.intended');
            $existingPath = $this->normalizeToRelativePath(
                is_string($existing) ? $existing : null,
                $request,
            );

            if (
                $existingPath !== null
                && ! $this->isLocalizedHomePath($existingPath)
            ) {
                return;
            }
        }

        $request->session()->put('url.intended', $path);
    }

    /**
     * Remember the URL the guest was trying to open (auth middleware path).
     */
    public function rememberFromRequest(Request $request): void
    {
        if (! $request->isMethodSafe()) {
            return;
        }

        $this->remember($request);
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

    /**
     * Turn an absolute or relative URL into a same-origin relative path (+ query).
     */
    public function normalizeToRelativePath(?string $intended, ?Request $request = null): ?string
    {
        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $intended = trim($intended);

        if (str_starts_with($intended, '//')) {
            return null;
        }

        $parts = parse_url($intended);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['host'])) {
            $request ??= request();
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $requestHost = $request instanceof Request ? $request->getHost() : null;

            if (
                ! is_string($parts['host'])
                || (
                    $parts['host'] !== $appHost
                    && $parts['host'] !== $requestHost
                )
            ) {
                return null;
            }
        }

        $path = $parts['path'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = '/'.trim($path, '/');

        if ($path === '/') {
            return null;
        }

        $query = isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== ''
            ? '?'.$parts['query']
            : '';

        return $path.$query;
    }

    private function requestUri(Request $request): string
    {
        $uri = $request->getRequestUri();

        return is_string($uri) && $uri !== '' ? $uri : '/';
    }

    private function isAuthEntryPath(string $path): bool
    {
        $pathOnly = $this->pathOnly($path);

        $locales = implode('|', array_map(
            static fn (string $locale): string => preg_quote($locale, '#'),
            (array) config('maison.locales'),
        ));

        return (bool) preg_match(
            '#^/(?:(?:'.$locales.')/)?(?:login|register|forgot-password|two-factor-challenge|reset-password)(?:/|$)#',
            $pathOnly,
        );
    }

    private function isLocalizedHomePath(string $path): bool
    {
        $pathOnly = $this->pathOnly($path);

        $locales = implode('|', array_map(
            static fn (string $locale): string => preg_quote($locale, '#'),
            (array) config('maison.locales'),
        ));

        return (bool) preg_match('#^/('.$locales.')$#', $pathOnly);
    }

    private function pathOnly(string $path): string
    {
        $pathOnly = parse_url($path, PHP_URL_PATH);

        return is_string($pathOnly) ? '/'.trim($pathOnly, '/') : $path;
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

        if ($this->isAuthEntryPath($path)) {
            return false;
        }

        if (preg_match('#^/('.$locales.')/dashboard$#', $path) === 1) {
            return false;
        }

        if (preg_match('#^/('.$locales.')/admin(?:/|$)#', $path) === 1) {
            return $user->isAdmin();
        }

        // Staff only resume admin destinations; public/member intended → admin dashboard.
        if ($user->isAdmin()) {
            return false;
        }

        if (preg_match('#^/('.$locales.')/member(?:/|$)#', $path) === 1) {
            return $user->isCustomer();
        }

        return true;
    }
}
