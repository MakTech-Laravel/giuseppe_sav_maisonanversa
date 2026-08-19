<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    /**
     * Allow public content and assets; keep private application areas out of the crawl.
     */
    public function __invoke(): Response
    {
        $origin = rtrim((string) config('app.url'), '/');
        $lines = [
            'User-agent: *',
        ];

        foreach ($this->disallowedPaths() as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$origin.'/sitemap.xml';
        $lines[] = '';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string>
     */
    private function disallowedPaths(): array
    {
        $paths = [
            '/login',
            '/register',
            '/forgot-password',
            '/two-factor-challenge',
            '/telescope',
        ];

        foreach (config('maison.locales') as $locale) {
            foreach ([
                'admin',
                'member',
                'settings',
                'checkout',
                'file-upload-demo',
                'verify',
            ] as $segment) {
                $paths[] = '/'.$locale.'/'.$segment;
            }
        }

        return $paths;
    }
}
