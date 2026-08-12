<?php

namespace App\Http\Middleware;

use App\Services\Locale\LocalePreferenceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(public LocalePreferenceService $locales) {}

    /**
     * Apply the active Maison locale for the request.
     *
     * Locale-prefixed routes set (and persist) the locale from `{locale}`.
     * Fortify and other non-prefixed endpoints use the preference cookie,
     * session, Accept-Language, then the site default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->preferred($request);

        if ($request->route('locale') === $locale) {
            $this->locales->remember($request, $locale);
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
