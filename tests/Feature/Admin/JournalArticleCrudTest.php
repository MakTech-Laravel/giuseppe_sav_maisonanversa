<?php

use App\Enums\RoleEnum;
use App\Models\JournalArticle;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view the journal index with filters and pagination', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/journal/index')
            ->has('articles.data')
            ->where('filters.search', '')
            ->where('filters.category', '')
            ->where('filters.publication', '')
            ->where('filters.per_page', 15)
            ->has('perPageOptions', 5)
            ->has('categories')
        );
});

test('staff can filter journal articles by search category and publication', function () {
    JournalArticle::factory()->create([
        'title' => 'Unique Heritage Story',
        'slug' => 'unique-heritage-story',
        'category' => 'Heritage',
        'author' => 'Maison',
        'published_at' => now()->subDay(),
    ]);
    JournalArticle::factory()->draft()->create([
        'title' => 'Unique Draft Story',
        'slug' => 'unique-draft-story',
        'category' => 'Drafts',
        'author' => 'Maison',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', [
            'locale' => 'nl',
            'search' => 'Unique Heritage',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/journal/index')
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Unique Heritage Story')
            ->where('filters.search', 'Unique Heritage')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', [
            'locale' => 'nl',
            'category' => 'Drafts',
            'publication' => 'draft',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.category', 'Drafts')
            ->where('filters.publication', 'draft')
            ->where('articles.data', fn ($articles) => collect($articles)->contains(
                fn ($article) => $article['title'] === 'Unique Draft Story' && $article['is_published'] === false,
            ))
        );
});

test('staff can paginate journal articles with a whitelisted per page value', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', [
            'locale' => 'nl',
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 10)
            ->where('articles.per_page', 10)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', [
            'locale' => 'nl',
            'per_page' => 999,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 15)
        );
});

test('staff can create a journal article with a cover image', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->admin)
        ->post(route('admin.journal.store', ['locale' => 'nl']), [
            'slug' => 'nieuw-artikel',
            'title' => 'Nieuw artikel',
            'excerpt' => 'Korte intro',
            'body' => "Eerste alinea.\n\nTweede alinea.",
            'cover_path' => 'antwerp-cityscape',
            'category' => 'Erfgoed',
            'author' => 'Maison Anversa',
            'date_label' => 'Augustus 2026',
            'published_at' => now()->toDateTimeString(),
            'sort_order' => 99,
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ])
        ->assertRedirect();

    $article = JournalArticle::query()->where('slug', 'nieuw-artikel')->first();

    expect($article)->not->toBeNull()
        ->and($article->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($article->image_path);

    $response->assertRedirect(route('admin.journal.show', [
        'locale' => 'nl',
        'article' => $article->id,
    ]));
});

test('staff can update a journal article', function () {
    Storage::fake('public');

    $article = JournalArticle::factory()->create([
        'title' => 'Oud',
        'image_path' => null,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.journal.update', ['locale' => 'nl', 'article' => $article->id]), [
            '_method' => 'PUT',
            'slug' => $article->slug,
            'title' => 'Nieuw',
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'cover_path' => $article->cover_path,
            'category' => $article->category,
            'author' => $article->author,
            'date_label' => $article->date_label,
            'published_at' => now()->toDateTimeString(),
            'sort_order' => 1,
            'image' => UploadedFile::fake()->image('new-cover.jpg'),
        ])
        ->assertRedirect(route('admin.journal.show', ['locale' => 'nl', 'article' => $article->id]));

    $article->refresh();

    expect($article->title)->toBe('Nieuw')
        ->and($article->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($article->image_path);
});

test('staff can remove a journal article image', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('existing.jpg')->store('journal', 'public');

    $article = JournalArticle::factory()->create([
        'image_path' => $path,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.journal.update', ['locale' => 'nl', 'article' => $article->id]), [
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'cover_path' => $article->cover_path,
            'category' => $article->category,
            'author' => $article->author,
            'date_label' => $article->date_label,
            'published_at' => $article->published_at?->toDateTimeString(),
            'sort_order' => $article->sort_order,
            'remove_image' => true,
        ])
        ->assertRedirect();

    expect($article->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('staff can delete a journal article', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('existing.jpg')->store('journal', 'public');

    $article = JournalArticle::factory()->create([
        'image_path' => $path,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.journal.destroy', ['locale' => 'nl', 'article' => $article->id]))
        ->assertRedirect(route('admin.journal.index', ['locale' => 'nl']));

    expect(JournalArticle::query()->whereKey($article->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});

test('staff can view a journal detail page with translation props', function () {
    $article = JournalArticle::factory()->create([
        'title' => 'Detail artikel',
        'excerpt' => 'Detail intro',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.journal.show', ['locale' => 'nl', 'article' => $article->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/journal/show')
            ->where('article.id', (string) $article->id)
            ->where('article.title', 'Detail artikel')
            ->where('article.excerpt', 'Detail intro')
            ->has('locales', 3)
            ->has('translations.nl')
            ->has('translations.en')
            ->has('translations.fr')
            ->has('translationStatus.nl')
            ->has('translationStatus.en')
            ->has('translationStatus.fr')
        );
});

test('journal detail page shows translated content for the active locale', function () {
    $article = JournalArticle::factory()->create([
        'title' => 'English source title',
        'excerpt' => 'English excerpt',
        'body' => "English body one.\n\nEnglish body two.",
        'category' => 'Heritage',
        'date_label' => 'June 2026',
    ]);

    foreach ([
        'title' => 'Titre français',
        'excerpt' => 'Résumé français',
        'body' => "Corps français un.\n\nCorps français deux.",
        'category' => 'Patrimoine',
        'date_label' => 'Juin 2026',
    ] as $column => $value) {
        $article->translations()->updateOrCreate(
            ['locale' => 'fr', 'column' => $column],
            ['value' => $value, 'source_hash' => $article->translationSourceHash($column)],
        );
    }

    $this->actingAs($this->admin)
        ->get(route('admin.journal.show', ['locale' => 'fr', 'article' => $article->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('article.title', 'Titre français')
            ->where('article.excerpt', 'Résumé français')
            ->where('article.category', 'Patrimoine')
            ->where('article.date_label', 'Juin 2026')
        );
});

test('creating a journal article stores deepl translations for all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.journal.store', ['locale' => 'nl']), [
            'slug' => 'deepl-journal',
            'title' => 'DeepL Journal Titel',
            'excerpt' => 'DeepL Journal Excerpt',
            'body' => 'DeepL Journal Body',
            'category' => 'Erfgoed',
            'author' => 'Maison Anversa',
            'date_label' => 'Augustus 2026',
            'published_at' => now()->toDateTimeString(),
            'sort_order' => 0,
        ])
        ->assertRedirect();

    $article = JournalArticle::query()->where('slug', 'deepl-journal')->firstOrFail();

    expect($article->translations()->count())->toBe(15)
        ->and($article->translated('title', 'nl'))->toBe('NL DeepL Journal Titel')
        ->and($article->translated('title', 'en'))->toBe('EN DeepL Journal Titel')
        ->and($article->translated('body', 'fr'))->toBe('FR DeepL Journal Body');
});

test('staff can manually update journal translations', function () {
    $article = JournalArticle::factory()->create([
        'title' => 'Bron titel',
        'excerpt' => 'Bron excerpt',
        'body' => 'Bron body',
        'category' => 'Erfgoed',
        'date_label' => 'Augustus 2026',
    ]);

    $localeCopy = fn (string $prefix): array => [
        'title' => "{$prefix} title",
        'excerpt' => "{$prefix} excerpt",
        'body' => "{$prefix} body",
        'category' => "{$prefix} category",
        'date_label' => "{$prefix} date",
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.journal.translations.update', ['locale' => 'nl', 'article' => $article->id]), [
            'nl' => $localeCopy('Custom NL'),
            'en' => $localeCopy('Custom EN'),
            'fr' => $localeCopy('Custom FR'),
        ])
        ->assertRedirect(route('admin.journal.show', ['locale' => 'nl', 'article' => $article->id]));

    $article->refresh();

    expect($article->title)->toBe('Bron titel')
        ->and($article->translated('title', 'nl'))->toBe('Custom NL title')
        ->and($article->translated('title', 'en'))->toBe('Custom EN title')
        ->and($article->translated('body', 'fr'))->toBe('Custom FR body');
});

test('staff can retranslate all journal locales from source', function () {
    fakeDeepLTranslations();

    $article = JournalArticle::factory()->create([
        'title' => 'Hervertaal titel',
        'excerpt' => 'Hervertaal excerpt',
        'body' => 'Hervertaal body',
        'category' => 'Erfgoed',
        'date_label' => 'Augustus 2026',
    ]);

    $article->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.journal.translate', ['locale' => 'nl', 'article' => $article->id]))
        ->assertRedirect(route('admin.journal.show', ['locale' => 'nl', 'article' => $article->id]));

    expect($article->fresh()->translations()->count())->toBe(15);
});
