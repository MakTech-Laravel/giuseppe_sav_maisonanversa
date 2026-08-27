<?php

use App\Models\SeoMeta;
use App\Support\Seo\MaisonSeo;
use Illuminate\Http\Request;

test('public maison pages receive unique titles and indexable robots', function () {
    $titles = [];

    foreach (array_keys(config('maison.pages')) as $page) {
        $this->get(localized('maison.'.$page))
            ->assertOk()
            ->assertInertia(function ($inertia) use (&$titles): void {
                $seo = $inertia->toArray()['props']['seo'];

                expect($seo['robots'])->toBeNull()
                    ->and($seo['title'])->not->toBeEmpty()
                    ->and($seo['canonical'])->toStartWith(rtrim((string) config('app.url'), '/'));

                $titles[] = $seo['title'];
            });
    }

    expect($titles)->toHaveCount(14)
        ->and($titles)->toHaveCount(count(array_unique($titles)));
});

test('english house copy is translated in the seo document', function () {
    // Every page has an admin-editable SeoMeta override (seeded with the
    // Dutch default), which takes precedence over the static __() copy and
    // is translated per-locale through the DeepL pipeline, not Laravel's
    // translation files.
    fakeDeepLTranslations();

    $meta = SeoMeta::query()->where('page_key', 'house')->firstOrFail();
    $meta->update(['title' => 'Het Huis — vertaald']);

    $this->get('/en/huis')->assertOk()->assertInertia(fn ($page) => $page
        ->where('seo.title', 'EN Het Huis — vertaald')
        ->where('seo.canonical', url('/en/huis'))
    );
});

test('a journal article includes article and breadcrumb structured data', function () {
    $this->get('/nl/journal/waarom-antwerpen-luxewereld')
        ->assertOk()
        ->assertInertia(function ($page): void {
            $graphs = $page->toArray()['props']['seo']['jsonLd'][0]['@graph'];
            $types = array_column($graphs, '@type');

            expect($types)->toContain('Organization')
                ->toContain('WebSite')
                ->toContain('Article')
                ->toContain('BreadcrumbList');
        });
});

test('the seo document for a journal article includes article metadata', function () {
    $this->get('/nl/journal/waarom-antwerpen-luxewereld')
        ->assertOk()
        ->assertInertia(function ($page): void {
            $seo = $page->toArray()['props']['seo'];

            expect($seo['ogType'])->toBe('article')
                ->and($seo['canonical'])->toEndWith('/nl/journal/waarom-antwerpen-luxewereld')
                ->and($seo['title'])->toContain('Maison Anversa')
                ->and($seo['articlePublishedTime'])->not->toBeNull();
        });
});

test('maison seo can be resolved from the current request', function () {
    $this->get('/nl/story')->assertOk();

    $document = MaisonSeo::document(Request::create('/nl/story', 'GET'));

    expect($document['title'])->toBeString()->not->toBeEmpty()
        ->and($document['description'])->toBeString()->not->toBeEmpty();
});

test('seo documents include known open graph image dimensions', function () {
    $this->get('/nl')->assertOk()->assertInertia(fn ($page) => $page
        ->where('seo.ogImageWidth', 1024)
        ->where('seo.ogImageHeight', 682)
    );
});
