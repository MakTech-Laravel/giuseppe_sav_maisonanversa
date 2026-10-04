<?php

use App\Enums\HomeHeroAction;
use App\Enums\RoleEnum;
use App\Models\HomeHero;
use App\Models\Product;
use App\Models\User;
use App\Support\HomeHeroPresenter;
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

test('heritage staff can open the home hero editor', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('heritage.view');

    $this->actingAs($staff)
        ->get(route('admin.home-hero.edit', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/home-hero/edit')
            ->where('hero.title', 'Maison')
            ->has('actions', count(HomeHeroAction::cases()))
            ->has('pages', count(HomeHeroPresenter::PAGES))
            ->where('actions', function ($actions) {
                $values = collect($actions)->pluck('value');

                return $values->contains('founding_circle')
                    && $values->contains('register_founding_circle')
                    && $values->contains('community')
                    && $values->contains('club_corner')
                    && $values->contains('journal');
            })
        );
});

test('the seeded hero renders on the homepage', function () {
    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->where('hero.eyebrow', 'Antwerpen, België — Founding Edition 2026')
            ->where('hero.title', 'Maison')
            ->where('hero.titleAccent', 'Anversa')
            ->where('hero.showCounter', true)
            ->where('hero.counterLines.0', 'Nummers nog')
            ->where('hero.counterLines.1', 'beschikbaar')
            ->where('hero.imageUrl', null)
            ->has('hero.buttons', 3)
            ->where('hero.buttons.0.label', 'Ontdek Heritage No.001 →')
            ->where('hero.buttons.0.kind', 'link')
            ->where('hero.buttons.0.href', route('maison.products.show', [
                'locale' => 'nl',
                'product' => Product::FOUNDING_SLUG,
            ]))
            ->where('hero.buttons.1.href', route('maison.house', ['locale' => 'nl']))
            ->where('hero.buttons.2.kind', 'newsletter')
            ->where('hero.buttons.2.href', null)
        );
});

test('admin update changes copy, image, and a button, and the homepage follows the locale', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.home-hero.update', ['locale' => 'nl']), [
            '_method' => 'PUT',
            ...homeHeroPayload([
                'eyebrow' => 'Antwerpen, het huis',
                'primary_action' => HomeHeroAction::External->value,
                'primary_target' => 'https://maisonanversa.com/atelier',
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ]),
        ])
        ->assertRedirect();

    $hero = HomeHero::current()->fresh();

    expect($hero->eyebrow)->toBe('Antwerpen, het huis')
        ->and($hero->primary_action)->toBe(HomeHeroAction::External)
        ->and($hero->image_path)->toStartWith('home-heroes/');

    $hero->translations()->create([
        'locale' => 'en',
        'column' => 'eyebrow',
        'value' => 'Antwerp, the house',
        'source_hash' => $hero->translationSourceHash('eyebrow'),
    ]);

    $this->get('/nl')->assertInertia(fn (Assert $page) => $page
        ->where('hero.eyebrow', 'Antwerpen, het huis')
        ->where('hero.buttons.0.kind', 'link')
        ->where('hero.buttons.0.href', 'https://maisonanversa.com/atelier')
        ->where('hero.imageUrl', fn ($url) => is_string($url) && str_contains($url, 'home-heroes/'))
    );

    $this->get('/en')->assertInertia(fn (Assert $page) => $page
        ->where('hero.eyebrow', 'Antwerp, the house')
        ->where('hero.buttons.0.href', 'https://maisonanversa.com/atelier')
    );
});

test('an unknown action and an insecure external url are rejected', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.home-hero.edit', ['locale' => 'nl']))
        ->put(route('admin.home-hero.update', ['locale' => 'nl']), homeHeroPayload([
            'primary_action' => 'teleport',
            'tertiary_action' => HomeHeroAction::External->value,
            'tertiary_target' => 'http://example.com',
        ]))
        ->assertRedirect(route('admin.home-hero.edit', ['locale' => 'nl']))
        ->assertSessionHasErrors(['primary_action', 'tertiary_target']);

    expect(HomeHero::current()->primary_action)->toBe(HomeHeroAction::FoundingProduct);
});

test('founding circle, community, and register destinations resolve to maison routes', function () {
    HomeHero::current()->update([
        'primary_action' => HomeHeroAction::FoundingCircle,
        'primary_label' => 'Founding Circle',
        'primary_target' => null,
        'secondary_action' => HomeHeroAction::Community,
        'secondary_label' => 'Gemeenschap',
        'secondary_target' => null,
        'tertiary_action' => HomeHeroAction::RegisterFoundingCircle,
        'tertiary_label' => 'Registreer',
        'tertiary_target' => null,
    ]);

    $this->get('/nl')->assertInertia(fn (Assert $page) => $page
        ->has('hero.buttons', 3)
        ->where('hero.buttons.0.href', route('maison.circle', ['locale' => 'nl']))
        ->where('hero.buttons.1.href', route('maison.community', ['locale' => 'nl']))
        ->where('hero.buttons.2.href', route('maison.register', ['locale' => 'nl']))
    );
});

test('a newsletter button opens the letter modal without a href', function () {
    HomeHero::current()->update([
        'primary_action' => HomeHeroAction::Newsletter,
        'primary_label' => 'Heritage Letter',
        'primary_target' => null,
    ]);

    $this->get('/nl')->assertInertia(fn (Assert $page) => $page
        ->where('hero.buttons.0.kind', 'newsletter')
        ->where('hero.buttons.0.href', null)
        ->where('hero.buttons.0.label', 'Heritage Letter')
    );
});

test('a hidden button slot is omitted from the homepage', function () {
    HomeHero::current()->update([
        'secondary_action' => HomeHeroAction::Hidden,
        'secondary_label' => 'Betreed het Huis',
    ]);

    $this->get('/nl')->assertInertia(fn (Assert $page) => $page
        ->has('hero.buttons', 2)
        ->where('hero.buttons', function ($buttons) {
            $labels = collect($buttons)->pluck('label');

            return $labels->doesntContain('Betreed het Huis')
                && $labels->contains('Ontdek Heritage No.001 →')
                && $labels->contains('Heritage Letter');
        })
    );
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function homeHeroPayload(array $overrides = []): array
{
    $hero = HomeHero::current();

    return [
        'eyebrow' => $hero->eyebrow,
        'title' => $hero->title,
        'title_accent' => $hero->title_accent,
        'tagline' => $hero->tagline,
        'show_counter' => $hero->show_counter,
        'counter_line_one' => $hero->counter_line_one,
        'counter_line_two' => $hero->counter_line_two,
        'primary_label' => $hero->primary_label,
        'primary_action' => $hero->primary_action->value,
        'primary_target' => $hero->primary_target,
        'secondary_label' => $hero->secondary_label,
        'secondary_action' => $hero->secondary_action->value,
        'secondary_target' => $hero->secondary_target,
        'tertiary_label' => $hero->tertiary_label,
        'tertiary_action' => $hero->tertiary_action->value,
        'tertiary_target' => $hero->tertiary_target,
        ...$overrides,
    ];
}
