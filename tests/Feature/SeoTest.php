<?php

use App\Support\Journal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

test('the sitemap lists every public page in every locale', function () {
    $locales = config('maison.locales');
    $pages = config('maison.pages');

    expect($pages)->toHaveCount(14);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('xml');

    $body = $response->getContent();

    expect($body)->toContain('<urlset');
    expect($body)->toContain('xmlns:xhtml="http://www.w3.org/1999/xhtml"');

    foreach ($pages as $page => $slug) {
        foreach ($locales as $locale) {
            $path = $slug === '' ? "/{$locale}" : "/{$locale}/{$slug}";

            expect($body)->toContain(url($path));
        }
    }

    expect(substr_count($body, '<loc>'))->toBe(
        (14 + count(Journal::slugs())) * count($locales),
    );
});

test('every public page mounts the shared SEO head component', function (string $componentPath) {
    $source = File::get(resource_path("js/pages/maison/{$componentPath}.tsx"));

    expect($source)
        ->toContain('MaisonSeoHead')
        ->not->toContain('<Head ');
})->with([
    'home',
    'house',
    'product',
    'story',
    'circle',
    'dressing',
    'journal',
    'journal/show',
    'community',
    'corner',
    'contact',
    'legal/privacy',
    'legal/terms',
    'legal/shipping',
    'legal/care',
]);

test('the SEO head component publishes canonical and hreflang links', function () {
    $source = File::get(
        resource_path('js/components/maison/seo/maison-seo-head.tsx')
    );

    expect($source)
        ->toContain('rel="canonical"')
        ->toContain('hrefLang=')
        ->toContain('hrefLang="x-default"')
        ->toContain('property="og:title"')
        ->toContain('name="twitter:card"')
        ->toContain('titleTemplate="%s"');
});

test('the sitemap route is registered outside the locale prefix', function () {
    $route = Route::getRoutes()->getByName('sitemap');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('sitemap.xml');
});

test('configured page slugs match the named maison routes', function () {
    foreach (config('maison.pages') as $page => $slug) {
        $route = Route::getRoutes()->getByName("maison.{$page}");

        expect($route)->not->toBeNull();

        $expectedUri = $slug === '' ? '{locale}' : "{locale}/{$slug}";

        expect($route->uri())->toBe($expectedUri);
    }
});
