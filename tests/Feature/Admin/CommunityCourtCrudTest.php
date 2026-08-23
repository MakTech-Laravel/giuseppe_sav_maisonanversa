<?php

use App\Enums\RoleEnum;
use App\Models\CommunityCourt;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can create a community court', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.store', ['locale' => 'nl']), [
            'title' => 'Club Corner Antwerp',
            'body' => 'Founding club',
            'location' => 'Antwerpen',
            'lat' => 51.2194,
            'lng' => 4.4025,
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect(CommunityCourt::query()->where('title', 'Club Corner Antwerp')->exists())->toBeTrue();
});

test('staff can update a community court', function () {
    $court = CommunityCourt::factory()->create(['title' => 'Old Corner']);

    $this->actingAs($this->admin)
        ->put(route('admin.courts.update', ['locale' => 'nl', 'court' => $court->id]), [
            'title' => 'New Corner',
            'body' => 'Updated',
            'location' => 'Brussel',
            'lat' => 50.85,
            'lng' => 4.35,
            'sort_order' => 2,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect($court->fresh()->title)->toBe('New Corner')
        ->and($court->fresh()->location)->toBe('Brussel');
});

test('staff can delete a community court', function () {
    $court = CommunityCourt::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.courts.destroy', ['locale' => 'nl', 'court' => $court->id]))
        ->assertRedirect(route('admin.courts.index', ['locale' => 'nl']));

    expect(CommunityCourt::query()->whereKey($court->id)->exists())->toBeFalse();
});

test('courts index returns paginated courts with filters', function () {
    CommunityCourt::factory()->create([
        'title' => 'Heritage Court',
        'location' => 'Antwerpen',
        'is_published' => true,
    ]);

    CommunityCourt::factory()->create([
        'title' => 'Draft Court',
        'location' => 'Gent',
        'is_published' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index', [
            'locale' => 'nl',
            'search' => 'Heritage',
            'status' => 'published',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/courts/index')
            ->has('courts.data', 1)
            ->where('courts.data.0.title', 'Heritage Court')
            ->where('courts.data.0.is_published', true)
            ->where('filters.search', 'Heritage')
            ->where('filters.status', 'published')
        );
});

test('court show exposes stored translation bundle and locale preview', function () {
    fakeDeepLTranslations();

    $court = CommunityCourt::factory()->create([
        'title' => 'Padel Antwerpen',
        'body' => 'Founding club',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.courts.show', ['locale' => 'en', 'court' => $court->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('court.title', 'EN Padel Antwerpen')
            ->where('court.body', 'EN Founding club')
            ->where('court.location', 'EN Antwerpen')
            ->where('translations.en.title', 'EN Padel Antwerpen')
            ->where('translations.en.body', 'EN Founding club')
            ->where('translations.fr.location', 'FR Antwerpen')
            ->has('locales', 3)
            ->has('translationStatus')
        );
});

test('staff can update court translations manually', function () {
    $court = CommunityCourt::factory()->create([
        'title' => 'Bron titel',
        'body' => 'Bron body',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.courts.translations.update', ['locale' => 'nl', 'court' => $court->id]), [
            'nl' => ['title' => 'NL titel', 'body' => 'NL body', 'location' => 'NL loc'],
            'en' => ['title' => 'EN title', 'body' => 'EN body', 'location' => 'EN loc'],
            'fr' => ['title' => 'FR titre', 'body' => 'FR body', 'location' => 'FR loc'],
        ])
        ->assertRedirect(route('admin.courts.show', ['locale' => 'nl', 'court' => $court->id]));

    expect($court->fresh()->translated('title', 'en'))->toBe('EN title');
});

test('staff can queue court retranslation', function () {
    fakeDeepLTranslations();

    $court = CommunityCourt::factory()->create([
        'title' => 'Padel Antwerpen',
        'body' => 'Founding club',
        'location' => 'Antwerpen',
    ]);

    $court->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.courts.translate', ['locale' => 'nl', 'court' => $court->id]), [
            'target_locale' => 'en',
        ])
        ->assertRedirect();

    expect($court->fresh()->translated('title', 'en'))->toBe('EN Padel Antwerpen')
        ->and($court->fresh()->translated('body', 'en'))->toBe('EN Founding club');
});

test('creating a court stores translations immediately', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.courts.store', ['locale' => 'nl']), [
            'title' => 'Club Corner Antwerp',
            'body' => 'Founding club',
            'location' => 'Antwerpen',
            'lat' => 51.2194,
            'lng' => 4.4025,
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertRedirect();

    $court = CommunityCourt::query()->where('title', 'Club Corner Antwerp')->first();

    expect($court)->not->toBeNull()
        ->and($court->translations()->count())->toBe(9)
        ->and($court->translated('title', 'en'))->toBe('EN Club Corner Antwerp');
});
