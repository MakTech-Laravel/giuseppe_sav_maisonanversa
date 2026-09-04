<?php

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\User;

test('members can submit a club with only name and sports', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('community.sessions.create', ['locale' => 'nl']))
        ->post(route('community.clubs.store', ['locale' => 'nl']), [
            'name' => 'Nieuwe Corner',
            'sports' => [SessionSport::Padel->value],
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Nieuwe Corner')->firstOrFail();

    expect($club->status)->toBe(ClubStatus::Pending)
        ->and($club->city)->toBeNull()
        ->and($club->street)->toBeNull()
        ->and($club->postal_code)->toBeNull()
        ->and($club->website)->toBeNull()
        ->and($club->is_session_venue)->toBeTrue()
        ->and($club->submitted_by_id)->toBe($user->id);
});

test('blank optional club fields are stored as null', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('community.sessions.create', ['locale' => 'nl']))
        ->post(route('community.clubs.store', ['locale' => 'nl']), [
            'name' => 'Blank Optionals Club',
            'sports' => [SessionSport::Tennis->value],
            'street' => '   ',
            'postal_code' => '',
            'city' => '',
            'website' => '',
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Blank Optionals Club')->firstOrFail();

    expect($club->street)->toBeNull()
        ->and($club->postal_code)->toBeNull()
        ->and($club->city)->toBeNull()
        ->and($club->website)->toBeNull();
});
