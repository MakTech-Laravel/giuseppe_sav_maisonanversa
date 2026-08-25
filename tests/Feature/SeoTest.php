<?php

use App\Enums\FaqContext;
use App\Models\Faq;
use App\Support\Journal;
use App\Support\Seo\MaisonSeo;
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

test('the sitemap includes lastmod for journal articles', function () {
    $slug = Journal::slugs()[0];

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)
        ->toContain(url('/nl/journal/'.$slug))
        ->toContain('<lastmod>')
        ->toMatch('/<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}<\/lastmod>/');
});

test('the sitemap includes lastmod for static public pages', function () {
    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)
        ->toContain(url('/nl'))
        ->toContain('<lastmod>')
        ->toMatch('/<loc>'.preg_quote(url('/nl'), '/').'<\/loc>[\s\S]*?<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}<\/lastmod>/');
});

test('the sitemap omits private and transactional urls', function () {
    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)
        ->not->toContain('/admin')
        ->not->toContain('/member')
        ->not->toContain('/checkout')
        ->not->toContain('/login');
});

test('every public page mounts the shared SEO head component', function (string $componentPath) {
    $source = File::get(resource_path("js/pages/maison/{$componentPath}.tsx"));

    expect($source)
        ->toContain('MaisonSeoHead')
        ->not->toContain('<Head ');
})->with([
    'home',
    'house',
    'products/index',
    'products/show',
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
    'checkout-success',
    'checkout-cancel',
    'verify',
    'newsletter-unsubscribed',
]);

test('the SEO head component publishes canonical and hreflang links', function () {
    $source = File::get(
        resource_path('js/components/maison/seo/maison-seo-head.tsx')
    );

    expect($source)
        ->toContain('rel="canonical"')
        ->toContain('hrefLang=')
        ->toContain('property="og:title"')
        ->toContain('name="twitter:card"')
        ->toContain('titleTemplate="%s"')
        ->toContain('og:image:width')
        ->toContain('application/ld+json');
});

test('the Inertia title callback does not append a Laravel suffix', function () {
    expect(File::get(resource_path('js/app.tsx')))
        ->toContain('title: (title) => title || appName')
        ->not->toContain('${title} - ${appName}');

    expect(File::get(resource_path('js/ssr.tsx')))
        ->toContain('title: (title) => title || appName')
        ->not->toContain('${title} - ${appName}');
});

test('member admin and auth layouts are marked noindex', function () {
    expect(File::get(resource_path('js/layouts/member-layout.tsx')))
        ->toContain('noindex, nofollow');
    expect(File::get(resource_path('js/layouts/app-layout.tsx')))
        ->toContain('noindex, nofollow');
    expect(File::get(resource_path('js/layouts/auth-layout.tsx')))
        ->toContain('noindex, nofollow');
});

test('the sitemap route is registered outside the locale prefix', function () {
    $route = Route::getRoutes()->getByName('sitemap');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('sitemap.xml');
});

test('robots.txt advertises the absolute sitemap and disallows private areas', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $body = $response->getContent();
    $origin = rtrim((string) config('app.url'), '/');

    expect($body)
        ->toContain('Sitemap: '.$origin.'/sitemap.xml')
        ->toContain('Disallow: /login')
        ->toContain('Disallow: /nl/admin')
        ->toContain('Disallow: /en/member')
        ->toContain('Disallow: /fr/settings')
        ->toContain('Disallow: /nl/checkout')
        ->not->toContain('Disallow: /build')
        ->not->toContain('Disallow: /images');
});

test('staging robots.txt disallows all indexing', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->headers->get('X-Robots-Tag'))->toContain('noindex');

    $body = $response->getContent();

    expect($body)
        ->toContain('Disallow: /')
        ->not->toContain('Sitemap:');
});

test('configured page slugs match the named maison routes', function () {
    foreach (config('maison.pages') as $page => $slug) {
        $route = Route::getRoutes()->getByName("maison.{$page}");

        expect($route)->not->toBeNull();

        $expectedUri = $slug === '' ? '{locale}' : "{locale}/{$slug}";

        expect($route->uri())->toBe($expectedUri);
    }
});

test('public pages share unique indexable seo documents', function () {
    $titles = [];

    foreach (config('maison.pages') as $slug) {
        $path = $slug === '' ? '/nl' : "/nl/{$slug}";
        $this->get($path)->assertOk()->assertInertia(function ($page) use (&$titles): void {
            $page->where('seo.robots', null);
            $titles[] = $page->toArray()['props']['seo']['title'];
        });
    }

    expect($titles)->toHaveCount(14)
        ->and(count(array_unique($titles)))->toBe(14);
});

test('the home seo document uses the canonical locale url', function () {
    $this->get('/nl')->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/home')
        ->where('seo.canonical', url('/nl'))
        ->where('seo.robots', null)
        ->where('seo.title', 'Maison Anversa — Europees erfgoedhuis voor sport en lifestyle')
    );
});

test('a journal article canonical includes the slug and is unique from the index', function () {
    $slug = 'waarom-antwerpen-luxewereld';

    $this->get("/nl/journal/{$slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/journal/show')
        ->where('seo.canonical', url("/nl/journal/{$slug}"))
        ->where('seo.ogType', 'article')
        ->where('seo.robots', null)
    );

    $this->get('/nl/journal')->assertOk()->assertInertia(fn ($page) => $page
        ->where('seo.canonical', url('/nl/journal'))
        ->where('seo.ogType', 'website')
    );
});

test('checkout pages are noindex and do not reuse the home canonical', function () {
    $this->get(localized('maison.checkout.success'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/checkout-success')
            ->where('seo.robots', 'noindex, nofollow')
            ->where('seo.canonical', localized('maison.checkout.success'))
        );

    $this->get(localized('maison.checkout.cancel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seo.robots', 'noindex, nofollow')
            ->where('seo.canonical', localized('maison.checkout.cancel'))
        );
});

test('the product page includes product and faq structured data', function () {
    $this->get('/nl/products/heritage-no-001')->assertOk()->assertInertia(function ($page): void {
        $page->where('seo.robots', null);

        $graphs = $page->toArray()['props']['seo']['jsonLd'][0]['@graph'];
        $types = array_column($graphs, '@type');

        expect($types)->toContain('Organization')
            ->toContain('WebSite')
            ->toContain('Product')
            ->toContain('FAQPage');
    });
});

test('the contact page includes faq structured data from the visible questions', function () {
    $expectedCount = Faq::publishedFor(FaqContext::Contact)->count();

    $this->get('/nl/contact')->assertOk()->assertInertia(function ($page) use ($expectedCount): void {
        $graphs = $page->toArray()['props']['seo']['jsonLd'][0]['@graph'];
        $types = array_column($graphs, '@type');
        $faq = collect($graphs)->firstWhere('@type', 'FAQPage');

        expect($types)->toContain('FAQPage')
            ->and($faq['mainEntity'])->toHaveCount($expectedCount);
    });
});

test('draft faqs are excluded from contact faq structured data', function () {
    Faq::factory()->contact()->draft()->create([
        'question' => 'Draft SEO vraag',
        'answer' => 'Draft SEO antwoord',
    ]);

    $publishedCount = Faq::publishedFor(FaqContext::Contact)->count();

    $this->get('/nl/contact')->assertOk()->assertInertia(function ($page) use ($publishedCount): void {
        $graphs = $page->toArray()['props']['seo']['jsonLd'][0]['@graph'];
        $faq = collect($graphs)->firstWhere('@type', 'FAQPage');
        $questions = collect($faq['mainEntity'])->pluck('name');

        expect($faq['mainEntity'])->toHaveCount($publishedCount)
            ->and($questions)->not->toContain('Draft SEO vraag');
    });
});

test('community remains indexable as a public doorway', function () {
    $this->get('/nl/community')->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/community')
        ->where('seo.robots', null)
        ->where('seo.canonical', url('/nl/community'))
    );
});

test('maison seo documents unique titles for every public page', function () {
    $document = MaisonSeo::document();

    expect($document)->toHaveKeys([
        'title',
        'description',
        'canonical',
        'robots',
        'hreflang',
        'jsonLd',
    ]);
});
