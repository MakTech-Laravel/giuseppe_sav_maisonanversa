<?php

use App\Models\JournalArticle;
use App\Support\Journal;

test('the journal catalog uses six articles per page', function () {
    expect(Journal::PER_PAGE)->toBe(6)
        ->and(Journal::slugs())->toHaveCount(count(array_unique(Journal::slugs())));
});

test('the first journal page renders paginated article cards', function () {
    $this->get('/nl/journal')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/journal')
            ->has('articles.data', 6)
            ->where('articles.per_page', 6)
            ->where('articles.current_page', 1)
            ->where('articles.total', JournalArticle::query()->published()->count())
            ->where('articles.data.0.slug', fn ($slug) => is_string($slug) && $slug !== '')
            ->where('articles.data.0.asset', fn ($asset) => is_string($asset) && $asset !== '')
            ->where('articles.data.0.image_url', null)
            ->where('articles.data.0.title', fn ($title) => is_string($title) && $title !== '')
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
            ->where('articles.data.0.slug', fn ($slug) => is_string($slug) && $slug !== '')
        );
});

test('the third journal page returns the last seeded page', function () {
    $this->get('/nl/journal?page=3')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('articles.data', 6)
            ->where('articles.current_page', 3)
            ->where('articles.data.0.slug', fn ($slug) => is_string($slug) && $slug !== '')
        );
});

test('a journal article page renders the localised body', function () {
    $this->get('/nl/journal/waarom-antwerpen-luxewereld')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/journal/show')
            ->where('article.slug', 'waarom-antwerpen-luxewereld')
            ->where('article.title', 'Waarom Antwerpen de meest ondervertegenwoordigde stad in de luxewereld is')
            ->where('article.image_url', null)
            ->has('article.body', 3)
            ->has('related', 3)
            ->where('related.0.slug', fn ($slug) => is_string($slug) && $slug !== 'waarom-antwerpen-luxewereld')
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

test('public journal uses uploaded cover images when present', function () {
    $article = JournalArticle::factory()->create([
        'slug' => 'uploaded-cover-story',
        'title' => 'Uploaded cover story',
        'image_path' => 'journal/example.jpg',
        'cover_path' => null,
    ]);

    $this->get("/nl/journal/{$article->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('article.slug', 'uploaded-cover-story')
            ->where('article.image_url', config('app.url').'/storage/journal/example.jpg')
            ->where('article.asset', 'antwerp-cityscape')
        );
});

test('related journal stories prefer the same category', function () {
    $primary = JournalArticle::factory()->create([
        'slug' => 'heritage-primary',
        'category' => 'Heritage',
    ]);
    JournalArticle::factory()->create([
        'slug' => 'heritage-related-one',
        'category' => 'Heritage',
    ]);
    JournalArticle::factory()->create([
        'slug' => 'heritage-related-two',
        'category' => 'Heritage',
    ]);
    JournalArticle::factory()->create([
        'slug' => 'design-related-one',
        'category' => 'Design',
    ]);

    $this->get("/nl/journal/{$primary->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('related.0.category', 'Heritage')
            ->where('related.1.category', 'Heritage')
        );
});

test('the journal article image keeps its natural proportions in the reading column', function () {
    $source = file_get_contents(resource_path('js/pages/maison/journal/show.tsx'));

    expect($source)
        ->toContain('max-w-180')
        ->toContain('article.image_url')
        ->not->toContain('aspect-21/9');
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
