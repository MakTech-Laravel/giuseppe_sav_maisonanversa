<?php

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\User;

test('members can browse the approved club directory', function () {
    $user = User::factory()->create();

    $partner = Club::factory()->create([
        'name' => 'Partner Padel',
        'city' => 'Antwerpen',
        'is_partner' => true,
    ]);
    Club::factory()->pending()->create(['name' => 'Hidden Club']);

    $this->actingAs($user)
        ->get(route('community.clubs.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/clubs/index')
            ->has('clubs.data', 1)
            ->where('clubs.data.0.name', 'Partner Padel')
            ->where('clubs.data.0.is_partner', true)
        );
});

test('members can view an approved club profile with upcoming sessions', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create([
        'name' => 'Padel Ganda',
        'is_partner' => true,
    ]);

    $this->actingAs($user)
        ->get(route('community.clubs.show', ['locale' => 'nl', 'club' => $club->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/clubs/show')
            ->where('club.name', 'Padel Ganda')
            ->where('club.is_partner', true)
            ->has('club.upcoming_sessions')
        );
});

test('pending clubs are hidden from the directory show page', function () {
    $user = User::factory()->create();
    $club = Club::factory()->pending()->create();

    $this->actingAs($user)
        ->get(route('community.clubs.show', ['locale' => 'nl', 'club' => $club->id]))
        ->assertNotFound();
});

test('guests cannot browse the club directory', function () {
    Club::factory()->create();

    $this->get(route('community.clubs.index', ['locale' => 'nl']))
        ->assertRedirect();

    $club = Club::factory()->create();

    $this->get(route('community.clubs.show', ['locale' => 'nl', 'club' => $club->id]))
        ->assertRedirect();
});

test('club search matches name, city and postal code', function () {
    $user = User::factory()->create();

    Club::factory()->create([
        'name' => 'Padel Ganda',
        'city' => 'Gent',
        'postal_code' => '9000',
    ]);
    Club::factory()->create([
        'name' => 'Royal Brussels Padel',
        'city' => 'Brussel',
        'postal_code' => '1050',
    ]);

    foreach (['Ganda', 'Gent', '9000'] as $term) {
        $clubs = $this->actingAs($user)
            ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => $term]))
            ->assertOk()
            ->json('clubs');

        expect($clubs)->toHaveCount(1)
            ->and($clubs[0]['name'])->toBe('Padel Ganda');
    }
});

test('club search hides clubs that are not approved', function () {
    $user = User::factory()->create();

    Club::factory()->pending()->create(['name' => 'Pending Padel', 'city' => 'Gent']);
    Club::factory()->rejected()->create(['name' => 'Rejected Padel', 'city' => 'Gent']);

    $this->actingAs($user)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel']))
        ->assertOk()
        ->assertJsonCount(0, 'clubs');
});

test('club search can be narrowed to a single sport', function () {
    $user = User::factory()->create();

    Club::factory()->create([
        'name' => 'Tennis Only Club',
        'sports' => [SessionSport::Tennis->value],
    ]);
    Club::factory()->create([
        'name' => 'Padel Only Club',
        'sports' => [SessionSport::Padel->value],
    ]);

    $clubs = $this->actingAs($user)
        ->getJson(route('community.clubs.search', [
            'locale' => 'nl',
            'q' => 'Club',
            'sport' => SessionSport::Padel->value,
        ]))
        ->assertOk()
        ->json('clubs');

    expect($clubs)->toHaveCount(1)
        ->and($clubs[0]['name'])->toBe('Padel Only Club');
});

test('a member submitted club lands in the pending queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('community.sessions.create', ['locale' => 'nl']))
        ->post(route('community.clubs.store', ['locale' => 'nl']), [
            'name' => 'Padel Zuid',
            'sports' => [SessionSport::Padel->value],
            'street' => 'Zuidlaan 4',
            'postal_code' => '2000',
            'city' => 'Antwerpen',
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Padel Zuid')->firstOrFail();

    expect($club->status)->toBe(ClubStatus::Pending)
        ->and($club->submitted_by_id)->toBe($user->id)
        ->and($club->slug)->toBe('padel-zuid')
        ->and($club->approved_at)->toBeNull();
});

test('member submitted clubs get a unique slug', function () {
    $user = User::factory()->create();

    Club::factory()->create(['name' => 'Padel Zuid', 'slug' => 'padel-zuid']);

    $this->actingAs($user)
        ->from(route('community.sessions.create', ['locale' => 'nl']))
        ->post(route('community.clubs.store', ['locale' => 'nl']), [
            'name' => 'Padel Zuid',
            'sports' => [SessionSport::Padel->value],
            'city' => 'Antwerpen',
        ])
        ->assertRedirect();

    expect(Club::query()->where('slug', 'padel-zuid-2')->exists())->toBeTrue();
});

test('club search ignores queries shorter than two characters', function () {
    $user = User::factory()->create();

    Club::factory()->create(['name' => 'Padel Ganda', 'city' => 'Gent']);

    $this->actingAs($user)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'P']))
        ->assertOk()
        ->assertJsonCount(0, 'clubs');
});

test('guests cannot search or submit clubs', function () {
    $this->get(route('community.clubs.search', ['locale' => 'nl', 'q' => 'padel']))
        ->assertRedirect();

    $this->post(route('community.clubs.store', ['locale' => 'nl']), [
        'name' => 'Padel Zuid',
        'sports' => [SessionSport::Padel->value],
        'city' => 'Antwerpen',
    ])->assertRedirect();

    expect(Club::query()->count())->toBe(0);
});
