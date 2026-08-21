<?php

namespace App\Support\Seo;

use App\Models\JournalArticle;

/**
 * Single source of truth for indexable URLs and disallowed paths.
 *
 * Consumed by the sitemap builder and the robots.txt generator so
 * they cannot drift apart.
 *
 * @phpstan-type StaticPage array{page: string, routeName: string, lastmod: string|null}
 * @phpstan-type JournalEntry array{slug: string, lastmod: string|null}
 * @phpstan-type Alternate array{hreflang: string, href: string}
 */
final class SeoUrlRegistry
{
    /**
     * @return list<StaticPage>
     */
    public function staticPages(): array
    {
        $pages = [];

        foreach (config('maison.pages') as $page => $slug) {
            $pages[] = [
                'page' => $page,
                'routeName' => 'maison.'.$page,
                'lastmod' => $this->lastmodForPage($page),
            ];
        }

        return $pages;
    }

    /**
     * @return list<JournalEntry>
     */
    public function journalEntries(): array
    {
        return JournalArticle::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['slug', 'updated_at', 'published_at'])
            ->map(fn (JournalArticle $article): array => [
                'slug' => $article->slug,
                'lastmod' => ($article->updated_at ?? $article->published_at)?->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * Hreflang alternates for a given route, including x-default.
     *
     * @param  array<string, string>  $parameters
     * @return list<Alternate>
     */
    public function alternates(string $routeName, array $parameters = []): array
    {
        $locales = config('maison.locales');
        $defaultLocale = config('maison.default_locale');

        $alternates = [];

        foreach ($locales as $locale) {
            $alternates[] = [
                'hreflang' => $locale,
                'href' => route($routeName, ['locale' => $locale, ...$parameters]),
            ];
        }

        $alternates[] = [
            'hreflang' => 'x-default',
            'href' => route($routeName, ['locale' => $defaultLocale, ...$parameters]),
        ];

        return $alternates;
    }

    /**
     * Paths that robots should not crawl (production robots.txt).
     *
     * @return list<string>
     */
    public function disallowedPaths(): array
    {
        $paths = config('maison.seo.disallow_paths', []);

        foreach (config('maison.locales') as $locale) {
            foreach (config('maison.seo.disallow_locale_segments', []) as $segment) {
                $paths[] = '/'.$locale.'/'.$segment;
            }
        }

        return $paths;
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
