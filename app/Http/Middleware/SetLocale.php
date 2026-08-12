<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the active Maison locale for the request.
     *
     * Locale-prefixed routes set (and persist) the locale from `{locale}`.
     * Fortify and other non-prefixed endpoints fall back to the session value
     * written on the last localized visit, so auth errors match the UI language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('maison.locales');
        $routeLocale = $request->route('locale');

        if (is_string($routeLocale) && in_array($routeLocale, $supported, true)) {
            if ($request->hasSession()) {
                $request->session()->put('locale', $routeLocale);
            }

            return $routeLocale;
        }

        $sessionLocale = $request->hasSession()
            ? $request->session()->get('locale')
            : null;

        if (is_string($sessionLocale) && in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        return config('maison.default_locale');
    }
}
