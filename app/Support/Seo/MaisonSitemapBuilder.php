<?php

namespace App\Support\Seo;

use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Builds the XML sitemap using Spatie's typed Url tags.
 *
 * Static pages and journal articles are sourced from {@see SeoUrlRegistry}
 * so the sitemap and robots.txt stay in sync.
 */
final class MaisonSitemapBuilder
{
    public function __construct(
        private readonly SeoUrlRegistry $registry,
    ) {}

    /**
     * Rendered XML string, optionally served from cache.
     */
    public function render(): string
    {
        $ttl = (int) config('maison.seo.sitemap_cache_seconds', 0);

        if ($ttl > 0) {
            return Cache::remember('maison:sitemap:xml', $ttl, fn (): string => $this->build());
        }

        return $this->build();
    }

    /**
     * Force-build and cache the sitemap XML (used by the Artisan command).
     */
    public function warm(): string
    {
        $xml = $this->build();

        $ttl = (int) config('maison.seo.sitemap_cache_seconds', 0);

        if ($ttl > 0) {
            Cache::put('maison:sitemap:xml', $xml, $ttl);
        }

        return $xml;
    }

    private function build(): string
    {
        $sitemap = Sitemap::create();
        $locales = config('maison.locales');

        foreach ($this->registry->staticPages() as $page) {
            foreach ($locales as $locale) {
                $url = Url::create(route($page['routeName'], ['locale' => $locale]));

                if ($page['lastmod'] !== null) {
                    $url->setLastModificationDate(new \DateTimeImmutable($page['lastmod']));
                }

                foreach ($this->registry->alternates($page['routeName']) as $alt) {
                    $url->addAlternate($alt['href'], $alt['hreflang']);
                }

                $sitemap->add($url);
            }
        }

        foreach ($this->registry->journalEntries() as $entry) {
            $params = ['slug' => $entry['slug']];

            foreach ($locales as $locale) {
                $url = Url::create(route('maison.journal.show', ['locale' => $locale, ...$params]));

                if ($entry['lastmod'] !== null) {
                    $url->setLastModificationDate(new \DateTimeImmutable($entry['lastmod']));
                }

                foreach ($this->registry->alternates('maison.journal.show', $params) as $alt) {
                    $url->addAlternate($alt['href'], $alt['hreflang']);
                }

                $sitemap->add($url);
            }
        }

        return $sitemap->render();
    }
}
