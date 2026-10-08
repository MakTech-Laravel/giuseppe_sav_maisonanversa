<?php

use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\StoryPage;
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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function storyPagePayload(array $overrides = []): array
{
    return [
        ...StoryPage::defaults(),
        ...$overrides,
    ];
}

test('heritage staff can open the story page editor', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('heritage.view');

    $this->actingAs($staff)
        ->get(route('admin.story-page.edit', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/story-page/edit')
            ->where('page.hero_title', 'Alles begint bij')
            ->where('page.hero_visible', true)
            ->where('page.name_title', 'Anversa')
            ->has('locales', 3)
        );
});

test('the seeded story renders every section', function () {
    $this->get('/nl/story')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/story')
            ->where('story.hero.eyebrow', 'Ons verhaal')
            ->where('story.hero.titleAccent', 'een koffie.')
            ->where('story.city.title', 'Antwerpen')
            ->where('story.city.imageUrl', null)
            ->where('story.hero.imageUrl', null)
            ->where('story.make.imageUrls.0', null)
            ->where('story.ritual.steps.0.number', '01')
            ->where('story.ritual.steps.3.title', 'De baan')
            ->where('story.name.title', 'Anversa')
            ->where('story.name.pronunciation', '/anˈvɛrsa/')
            ->where('story.make.title', 'Heritage No.001')
            ->where('story.make.buttonHref', route('maison.products.show', [
                'locale' => 'nl',
                'product' => Product::FOUNDING_SLUG,
            ]))
            ->where('story.quote.line', 'Padel is ons begin. Niet ons einde.')
            ->where('story.founder.signature', 'Yusuf')
            ->where('story.closing.place', 'Antwerpen, België · Est. 2026')
        );
});

test('a hidden section is absent from the storefront', function () {
    StoryPage::current()->update(['city_visible' => false]);

    $this->get('/nl/story')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('story.city', null)
            ->where('story.hero.eyebrow', 'Ons verhaal')
        );
});

test('admin update changes dutch copy and the english page reads the translation row', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.story-page.update', ['locale' => 'nl']), storyPagePayload([
            'hero_eyebrow' => 'Het huis',
            'city_visible' => false,
        ]))
        ->assertRedirect();

    $page = StoryPage::current()->fresh();

    expect($page->hero_eyebrow)->toBe('Het huis')
        ->and($page->city_visible)->toBeFalse()
        ->and($page->name_title)->toBe('Anversa');

    $page->translations()->create([
        'locale' => 'en',
        'column' => 'hero_eyebrow',
        'value' => 'The house',
        'source_hash' => $page->translationSourceHash('hero_eyebrow'),
    ]);

    $this->get('/nl/story')->assertInertia(fn (Assert $inertia) => $inertia
        ->where('story.hero.eyebrow', 'Het huis')
        ->where('story.city', null)
    );

    $this->get('/en/story')->assertInertia(fn (Assert $inertia) => $inertia
        ->where('story.hero.eyebrow', 'The house')
        ->where('story.name.title', 'Anversa')
    );
});

test('the english editor reads english copy and saving it keeps the dutch source', function () {
    $page = StoryPage::current();
    $page->translations()->create([
        'locale' => 'en',
        'column' => 'hero_title',
        'value' => 'Everything starts with',
        'source_hash' => $page->translationSourceHash('hero_title'),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.story-page.edit', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->where('page.hero_title', 'Alles begint bij')
            ->where('translations.en.hero_title', 'Everything starts with')
            ->where('translations.nl.hero_title', 'Alles begint bij')
        );

    $this->actingAs($this->admin)
        ->put(route('admin.story-page.update', ['locale' => 'en']), storyPagePayload([
            'hero_title' => 'Everything begins with',
            'hero_eyebrow' => 'Our story',
        ]))
        ->assertRedirect();

    $fresh = StoryPage::current()->fresh();

    expect($fresh->hero_title)->toBe('Alles begint bij')
        ->and($fresh->hero_eyebrow)->toBe('Ons verhaal')
        ->and($fresh->translated('hero_title', 'en'))->toBe('Everything begins with')
        ->and($fresh->translated('hero_eyebrow', 'en'))->toBe('Our story')
        ->and($fresh->name_title)->toBe('Anversa');
});

test('an uploaded photograph replaces the brand image and can be removed', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.story-page.update', ['locale' => 'nl']), [
            '_method' => 'PUT',
            ...storyPagePayload([
                'city_image' => UploadedFile::fake()->image('city.jpg'),
            ]),
        ])
        ->assertRedirect();

    $page = StoryPage::current()->fresh();

    expect($page->city_image_path)->toStartWith('story-pages/')
        ->and($page->hero_image_path)->toBeNull();

    $this->get('/nl/story')->assertInertia(fn (Assert $inertia) => $inertia
        ->where('story.city.imageUrl', fn ($url) => is_string($url) && str_contains($url, 'story-pages/'))
        ->where('story.hero.imageUrl', null)
    );

    $this->actingAs($this->admin)
        ->put(route('admin.story-page.update', ['locale' => 'nl']), storyPagePayload([
            'city_remove_image' => true,
        ]))
        ->assertRedirect();

    expect(StoryPage::current()->fresh()->city_image_path)->toBeNull();
});

test('proper nouns stay off the deepl whitelist', function () {
    $columns = StoryPage::current()->translatableColumns();

    expect($columns)
        ->not->toContain('name_title')
        ->not->toContain('name_pronunciation')
        ->not->toContain('make_title')
        ->not->toContain('founder_name')
        ->not->toContain('founder_signature')
        ->not->toContain('hero_visible')
        ->toContain('hero_eyebrow')
        ->toContain('name_body');
});
