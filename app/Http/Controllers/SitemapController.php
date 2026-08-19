<?php

namespace App\Http\Controllers;

use App\Support\Journal;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Publish every public page — and every Journal piece — in every locale,
     * with hreflang alternates.
     */
    public function __invoke(): Response
    {
        $locales = config('maison.locales');
        $defaultLocale = config('maison.default_locale');
        $urls = [];

        foreach (config('maison.pages') as $page => $slug) {
            $urls = [...$urls, ...$this->localizedUrls(
                'maison.'.$page,
                $locales,
                $defaultLocale,
                lastmod: $this->lastmodForPage($page),
            )];
        }

        foreach (Journal::sitemapArticles() as $article) {
            $urls = [...$urls, ...$this->localizedUrls(
                'maison.journal.show',
                $locales,
                $defaultLocale,
                ['slug' => $article['slug']],
                $article['lastmod'],
            )];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @param  list<string>  $locales
     * @param  array<string, string>  $parameters
     * @return list<array{loc: string, lastmod: string|null, alternates: list<array{hreflang: string, href: string}>}>
     */
    private function localizedUrls(
        string $routeName,
        array $locales,
        string $defaultLocale,
        array $parameters = [],
        ?string $lastmod = null,
    ): array {
        $alternates = collect($locales)
            ->map(fn (string $locale) => [
                'hreflang' => $locale,
                'href' => route($routeName, ['locale' => $locale, ...$parameters]),
            ])
            ->push([
                'hreflang' => 'x-default',
                'href' => route($routeName, ['locale' => $defaultLocale, ...$parameters]),
            ])
            ->all();

        return collect($locales)
            ->map(fn (string $locale) => [
                'loc' => route($routeName, ['locale' => $locale, ...$parameters]),
                'lastmod' => $lastmod,
                'alternates' => $alternates,
            ])
            ->all();
    }

    private function lastmodForPage(string $page): ?string
    {
        $relative = in_array($page, ['privacy', 'terms', 'shipping', 'care'], true)
            ? "legal/{$page}.tsx"
            : "{$page}.tsx";

        $path = resource_path("js/pages/maison/{$relative}");

        if (! is_file($path)) {
            return null;
        }

        return gmdate('Y-m-d', filemtime($path));
    }
}
