<?php

namespace App\Support\Seo;

/**
 * Generates the robots.txt body and any extra headers.
 *
 * Production allows crawling with explicit disallow rules;
 * every other environment blocks all indexing.
 *
 * @phpstan-type RobotsResponse array{body: string, headers: array<string, string>}
 */
final class RobotsTxtGenerator
{
    public function __construct(
        private readonly SeoUrlRegistry $registry,
    ) {}

    /**
     * @return RobotsResponse
     */
    public function generate(): array
    {
        if (! app()->isProduction()) {
            return [
                'body' => "User-agent: *\nDisallow: /\n",
                'headers' => [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ],
            ];
        }

        $origin = rtrim((string) config('app.url'), '/');
        $lines = ['User-agent: *'];

        foreach ($this->registry->disallowedPaths() as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$origin.'/sitemap.xml';
        $lines[] = '';

        return [
            'body' => implode("\n", $lines),
            'headers' => [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ],
        ];
    }
}
