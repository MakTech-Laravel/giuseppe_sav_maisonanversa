<?php

use App\Enums\RoleEnum;
use App\Models\PartnerClub;
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

test('staff can create a partner club', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.partner-clubs.store', ['locale' => 'nl']), [
            'city' => 'Leuven',
            'country' => 'België',
            'status' => 'open',
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect(PartnerClub::query()->where('city', 'Leuven')->exists())->toBeTrue();
});

test('staff can update a partner club', function () {
    $club = PartnerClub::factory()->create(['city' => 'Gent']);

    $this->actingAs($this->admin)
        ->put(route('admin.partner-clubs.update', ['locale' => 'nl', 'partnerClub' => $club->id]), [
            'city' => 'Brugge',
            'country' => 'België',
            'status' => 'active',
            'sort_order' => 2,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect($club->fresh()->city)->toBe('Brugge')
        ->and($club->fresh()->status)->toBe('active');
});

test('staff can delete a partner club', function () {
    $club = PartnerClub::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.partner-clubs.destroy', ['locale' => 'nl', 'partnerClub' => $club->id]))
        ->assertRedirect(route('admin.partner-clubs.index', ['locale' => 'nl']));

    expect(PartnerClub::query()->whereKey($club->id)->exists())->toBeFalse();
});

test('partner clubs index exposes translation bundle and status', function () {
    fakeDeepLTranslations();

    $club = PartnerClub::factory()->create([
        'city' => 'Kortrijk',
        'country' => 'België',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.partner-clubs.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/partner-clubs/index')
            ->has('locales', 3)
            ->where("translations.{$club->id}.en.city", 'EN Kortrijk')
            ->where("translations.{$club->id}.fr.country", 'FR België')
            ->has("translationStatus.{$club->id}")
        );
});

test('creating a partner club stores translations immediately', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.partner-clubs.store', ['locale' => 'nl']), [
            'city' => 'Mechelen',
            'country' => 'België',
            'status' => 'open',
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertRedirect();

    $club = PartnerClub::query()->where('city', 'Mechelen')->first();

    expect($club)->not->toBeNull()
        ->and($club->translations()->count())->toBe(4)
        ->and($club->translated('city', 'en'))->toBe('EN Mechelen');
});

test('staff can update partner club translations manually', function () {
    $club = PartnerClub::factory()->create([
        'city' => 'Bron stad',
        'country' => 'Bron land',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.partner-clubs.translations.update', ['locale' => 'nl', 'partnerClub' => $club->id]), [
            'nl' => ['city' => 'NL stad', 'country' => 'NL land'],
            'en' => ['city' => 'EN city', 'country' => 'EN country'],
            'fr' => ['city' => 'FR ville', 'country' => 'FR pays'],
        ])
        ->assertRedirect(route('admin.partner-clubs.index', ['locale' => 'nl']));

    expect($club->fresh()->translated('city', 'en'))->toBe('EN city')
        ->and($club->fresh()->translated('country', 'fr'))->toBe('FR pays');
});

test('staff can queue partner club retranslation', function () {
    fakeDeepLTranslations();

    $club = PartnerClub::factory()->create([
        'city' => 'Hasselt',
        'country' => 'België',
    ]);

    $club->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.partner-clubs.translate', ['locale' => 'nl', 'partnerClub' => $club->id]), [
            'target_locale' => 'en',
        ])
        ->assertRedirect();

    expect($club->fresh()->translated('city', 'en'))->toBe('EN Hasselt')
        ->and($club->fresh()->translated('country', 'en'))->toBe('EN België');
});

test('a guest cannot manage partner clubs', function () {
    $club = PartnerClub::factory()->create();

    $this->delete(route('admin.partner-clubs.destroy', ['locale' => 'nl', 'partnerClub' => $club->id]))
        ->assertRedirect(localized('maison.home', absolute: false));
});
