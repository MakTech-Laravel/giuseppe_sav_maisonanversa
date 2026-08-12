<?php

namespace App\Services\Locale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as CookieContract;

class LocalePreferenceService
{
    /**
     * Resolve the preferred Maison locale for this request.
     *
     * Priority: route segment → preference cookie → session → Accept-Language → default.
     */
    public function preferred(Request $request): string
    {
        return $this->fromRoute($request)
            ?? $this->fromCookie($request)
            ?? $this->fromSession($request)
            ?? $this->fromAcceptLanguage($request)
            ?? $this->defaultLocale();
    }

    /**
     * Persist the visitor's locale choice across logout and bare `/` visits.
     */
    public function remember(Request $request, string $locale): void
    {
        if (! $this->isSupported($locale)) {
            return;
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        Cookie::queue($this->cookie($locale));
    }

    public function cookie(string $locale): CookieContract
    {
        return cookie(
            name: $this->cookieName(),
            value: $locale,
            minutes: (int) config('maison.locale_cookie_minutes'),
            path: '/',
            secure: config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: config('session.same_site', 'lax'),
        );
    }

    public function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supported(), true);
    }

    /**
     * @return list<string>
     */
    public function supported(): array
    {
        return config('maison.locales');
    }

    public function defaultLocale(): string
    {
        return config('maison.default_locale');
    }

    public function cookieName(): string
    {
        return config('maison.locale_cookie');
    }

    private function fromRoute(Request $request): ?string
    {
        $locale = $request->route('locale');

        return is_string($locale) && $this->isSupported($locale) ? $locale : null;
    }

    private function fromCookie(Request $request): ?string
    {
        $locale = $request->cookie($this->cookieName());

        return is_string($locale) && $this->isSupported($locale) ? $locale : null;
    }

    private function fromSession(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $locale = $request->session()->get('locale');

        return is_string($locale) && $this->isSupported($locale) ? $locale : null;
    }

    private function fromAcceptLanguage(Request $request): ?string
    {
        $locale = $request->getPreferredLanguage($this->supported());

        return is_string($locale) && $this->isSupported($locale) ? $locale : null;
    }
}
