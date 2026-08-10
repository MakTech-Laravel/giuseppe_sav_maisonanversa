<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Publish every public page in every locale, with hreflang alternates.
     */
    public function __invoke(): Response
    {
        $locales = config('maison.locales');
        $defaultLocale = config('maison.default_locale');
        $urls = [];

        foreach (config('maison.pages') as $page => $slug) {
            $routeName = "maison.{$page}";

            $alternates = collect($locales)
                ->map(fn (string $locale) => [
                    'hreflang' => $locale,
                    'href' => route($routeName, ['locale' => $locale]),
                ])
                ->push([
                    'hreflang' => 'x-default',
                    'href' => route($routeName, ['locale' => $defaultLocale]),
                ])
                ->all();

            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => route($routeName, ['locale' => $locale]),
                    'alternates' => $alternates,
                ];
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
