<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the `{locale}` route segment as the application locale.
     *
     * The route pattern already restricts the segment to the supported locales,
     * so an unsupported one 404s before reaching here. The guard below is kept
     * so the middleware is still safe if applied to an unconstrained route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (is_string($locale) && in_array($locale, config('maison.locales'), true)) {
            app()->setLocale($locale);

            /*
             * So `route('maison.product')` resolves without every caller having
             * to repeat the current locale. Canonical and hreflang generation
             * still passes one explicitly, because those need the other locales.
             */
            URL::defaults(['locale' => $locale]);
        }

        return $next($request);
    }
}
