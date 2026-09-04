<?php

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\RoleEnum;
use App\Enums\SessionSport;
use App\Models\Club;
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

test('staff can create a club with corner attributes', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.store', ['locale' => 'nl']), [
            'name' => 'Club Corner Antwerp',
            'sports' => [SessionSport::Padel->value],
            'city' => 'Antwerpen',
            'status' => ClubStatus::Approved->value,
            'is_session_venue' => false,
            'has_corner' => true,
            'corner_published' => true,
            'corner_title' => 'Club Corner Antwerp',
            'corner_body' => 'Founding club',
            'corner_location' => 'Antwerpen',
            'lat' => 51.2194,
            'lng' => 4.4025,
            'sort_order' => 1,
            'show_on_corner_page' => true,
            'corner_pipeline_status' => CornerPipelineStatus::Open->value,
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Club Corner Antwerp')->first();

    expect($club)->not->toBeNull()
        ->and($club->has_corner)->toBeTrue()
        ->and($club->corner_published)->toBeTrue()
        ->and($club->show_on_corner_page)->toBeTrue()
        ->and($club->corner_title)->toBe('Club Corner Antwerp')
        ->and($club->is_session_venue)->toBeFalse();
});

test('staff can update club corner fields', function () {
    fakeDeepLTranslations();

    $club = Club::factory()->publishedCorner()->create([
        'name' => 'Old Corner',
        'corner_title' => 'Old Corner',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.clubs.update', ['locale' => 'nl', 'club' => $club->id]), [
            'name' => 'New Corner',
            'sports' => [SessionSport::Padel->value],
            'city' => 'Brussel',
            'status' => ClubStatus::Approved->value,
            'is_session_venue' => true,
            'has_corner' => true,
            'corner_published' => true,
            'corner_title' => 'New Corner',
            'corner_body' => 'Updated',
            'corner_location' => 'Brussel',
            'lat' => 50.85,
            'lng' => 4.35,
            'sort_order' => 2,
        ])
        ->assertRedirect();

    expect($club->fresh()->name)->toBe('New Corner')
        ->and($club->fresh()->corner_location)->toBe('Brussel')
        ->and($club->fresh()->corner_title)->toBe('New Corner');
});

test('staff can filter clubs by corner flag', function () {
    Club::factory()->publishedCorner()->create(['name' => 'Heritage Court']);
    Club::factory()->create(['name' => 'Plain Club', 'has_corner' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', [
            'locale' => 'nl',
            'flag' => 'corner',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/clubs/index')
            ->has('clubs.data', 1)
            ->where('clubs.data.0.name', 'Heritage Court')
        );
});

test('staff can update club corner translations', function () {
    fakeDeepLTranslations();

    $club = Club::factory()->publishedCorner()->create([
        'corner_title' => 'Club Corner Antwerp',
        'corner_body' => 'Founding',
        'corner_location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.clubs.translations.update', ['locale' => 'nl', 'club' => $club->id]), [
            'nl' => [
                'corner_title' => 'NL Club Corner Antwerp',
                'corner_body' => 'NL Founding',
                'corner_location' => 'NL Antwerpen',
            ],
            'en' => [
                'corner_title' => 'EN Club Corner Antwerp',
                'corner_body' => 'EN Founding',
                'corner_location' => 'EN Antwerpen',
            ],
            'fr' => [
                'corner_title' => 'FR Club Corner Antwerp',
                'corner_body' => 'FR Founding',
                'corner_location' => 'FR Antwerpen',
            ],
        ])
        ->assertRedirect();

    $club->refresh()->load('translations');

    expect($club->translated('corner_title', 'en'))->toBe('EN Club Corner Antwerp')
        ->and($club->translated('corner_body', 'fr'))->toBe('FR Founding');
});
