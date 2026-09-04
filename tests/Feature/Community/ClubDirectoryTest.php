<?php

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\User;

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

test('club search hides clubs that are not session venues', function () {
    $user = User::factory()->create();

    Club::factory()->create([
        'name' => 'Marketing Only',
        'city' => 'Gent',
        'is_session_venue' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Marketing']))
        ->assertOk()
        ->assertJsonCount(0, 'clubs');
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
        ->and($club->is_session_venue)->toBeTrue()
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
