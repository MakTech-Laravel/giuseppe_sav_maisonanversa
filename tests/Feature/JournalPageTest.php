<?php

use App\Support\Journal;

test('the journal catalog is eighteen pieces at six per page', function () {
    $slugs = Journal::slugs();

    expect($slugs)->toHaveCount(18)
        ->and($slugs)->toHaveCount(count(array_unique($slugs)))
        ->and(Journal::PER_PAGE)->toBe(6);
});

test('the first journal page still carries the six prototype articles', function () {
    $this->get('/nl/journal')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/journal')
            ->has('articles.data', 6)
            ->where('articles.per_page', 6)
            ->where('articles.current_page', 1)
            ->where('articles.last_page', 3)
            ->where('articles.total', 18)
            ->where('articles.data.0.slug', 'waarom-antwerpen-luxewereld')
            ->where('articles.data.0.asset', 'antwerp-cityscape')
            ->where('articles.data.1.asset', 'heritage-001-lifestyle-court')
            ->where('articles.data.2.asset', 'atelier-workshop')
            ->where('articles.data.3.asset', 'heritage-001-detail-gravure')
            ->where('articles.data.4.asset', 'heritage-001-front')
            ->where('articles.data.5.asset', 'hero-mansion')
            ->where('articles.data.0.title', 'Waarom Antwerpen de meest ondervertegenwoordigde stad in de luxewereld is')
            ->missing('articles.data.0.body')
        );
});

test('the second journal page returns the next six articles', function () {
    $this->get('/nl/journal?page=2')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/journal')
            ->has('articles.data', 6)
            ->where('articles.current_page', 2)
            ->where('articles.data.0.slug', 'club-corner-derde-plek')
        );
});

test('the third journal page returns the last six articles', function () {
    $this->get('/nl/journal?page=3')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('articles.data', 6)
            ->where('articles.current_page', 3)
            ->where('articles.data.0.slug', 'wat-we-bewaren-wanneer-we-nummeren')
            ->where('articles.data.5.slug', 'est-2026-is-een-belofte')
        );
});

test('a journal page beyond the last is not found', function () {
    $this->get('/nl/journal?page=4')->assertNotFound();
});

test('a journal article page renders the localised body', function () {
    $this->get('/nl/journal/waarom-antwerpen-luxewereld')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/journal/show')
            ->where('article.slug', 'waarom-antwerpen-luxewereld')
            ->where('article.title', 'Waarom Antwerpen de meest ondervertegenwoordigde stad in de luxewereld is')
            ->has('article.body', 3)
            ->has('related', 3)
            ->where('related.0.slug', 'padel-meest-sociale-sport')
        );
});

test('a journal article localises into english', function () {
    $this->get('/en/journal/waarom-antwerpen-luxewereld')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('article.title', 'Why Antwerp is the most underrepresented city in the luxury world')
        );
});

test('an unknown journal slug is not found', function () {
    $this->get('/nl/journal/niet-bestaand')->assertNotFound();
});

test('the journal article image keeps its natural proportions in the reading column', function () {
    $source = file_get_contents(resource_path('js/pages/maison/journal/show.tsx'));

    expect($source)
        ->toContain('max-w-180')
        ->toContain('[&_img]:h-auto')
        ->toContain('[&_img]:object-contain')
        ->not->toContain('aspect-21/9')
        ->not->toContain('h-72 w-full overflow-hidden');
});

test('the sitemap lists every journal article in every locale', function () {
    $locales = config('maison.locales');
    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (Journal::slugs() as $slug) {
        foreach ($locales as $locale) {
            expect($body)->toContain(url("/{$locale}/journal/{$slug}"));
        }
    }
});
