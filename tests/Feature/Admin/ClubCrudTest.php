<?php

use App\Enums\ClubStatus;
use App\Enums\RoleEnum;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunitySession;
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

test('staff create clubs that are approved right away', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.clubs.store', ['locale' => 'nl']), [
            'name' => 'Padel Noord',
            'sports' => [SessionSport::Padel->value],
            'street' => 'Noordlaan 1',
            'postal_code' => '2030',
            'city' => 'Antwerpen',
            'status' => ClubStatus::Approved->value,
            'is_partner' => true,
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Padel Noord')->firstOrFail();

    expect($club->status)->toBe(ClubStatus::Approved)
        ->and($club->is_partner)->toBeTrue()
        ->and($club->approved_by_id)->toBe($this->admin->id)
        ->and($club->approved_at)->not->toBeNull();
});

test('the index surfaces the pending queue first', function () {
    Club::factory()->create(['name' => 'Approved Club']);
    Club::factory()->pending()->create(['name' => 'Pending Club']);

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/clubs/index')
            ->where('pendingCount', 1)
            ->where('clubs.data.0.name', 'Pending Club')
        );
});

test('the index can be filtered by status', function () {
    Club::factory()->create(['name' => 'Approved Club']);
    Club::factory()->pending()->create(['name' => 'Pending Club']);

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', ['locale' => 'nl', 'status' => ClubStatus::Pending->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clubs.data', 1)
            ->where('clubs.data.0.name', 'Pending Club')
        );
});

test('approving a member submitted club makes it searchable', function () {
    $member = User::factory()->create();
    $club = Club::factory()->pending()->create(['name' => 'Padel Zuid', 'city' => 'Antwerpen']);

    $this->actingAs($this->admin)
        ->from(route('admin.clubs.index', ['locale' => 'nl']))
        ->patch(route('admin.clubs.approve', ['locale' => 'nl', 'club' => $club->id]))
        ->assertRedirect();

    expect($club->fresh()->status)->toBe(ClubStatus::Approved);

    $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->assertJsonCount(1, 'clubs');
});

test('rejecting a club keeps it out of member search', function () {
    $member = User::factory()->create();
    $club = Club::factory()->pending()->create(['name' => 'Padel Zuid']);

    $this->actingAs($this->admin)
        ->from(route('admin.clubs.index', ['locale' => 'nl']))
        ->patch(route('admin.clubs.reject', ['locale' => 'nl', 'club' => $club->id]))
        ->assertRedirect();

    expect($club->fresh()->status)->toBe(ClubStatus::Rejected);

    $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->assertJsonCount(0, 'clubs');
});

test('merging duplicates repoints sessions onto the surviving club', function () {
    $duplicate = Club::factory()->partner()->create(['name' => 'Padel Zuid Antwerpen']);
    $survivor = Club::factory()->create(['name' => 'Padel Zuid']);

    $session = CommunitySession::factory()->create(['club_id' => $duplicate->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), [
            'target_id' => $survivor->id,
        ])
        ->assertRedirect(route('admin.clubs.show', ['locale' => 'nl', 'club' => $survivor->id]));

    $duplicate->refresh();

    expect($session->fresh()->club_id)->toBe($survivor->id)
        ->and($duplicate->status)->toBe(ClubStatus::Merged)
        ->and($duplicate->merged_into_id)->toBe($survivor->id)
        ->and($duplicate->is_partner)->toBeFalse()
        ->and($survivor->fresh()->is_partner)->toBeTrue();
});

test('a merged club disappears from member search', function () {
    $member = User::factory()->create();
    $duplicate = Club::factory()->create(['name' => 'Padel Zuid Antwerpen']);
    $survivor = Club::factory()->create(['name' => 'Padel Zuid']);

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), [
            'target_id' => $survivor->id,
        ]);

    $clubs = $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->json('clubs');

    expect($clubs)->toHaveCount(1)
        ->and($clubs[0]['name'])->toBe('Padel Zuid');
});

test('a club cannot be merged into itself', function () {
    $club = Club::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $club->id]), [
            'target_id' => $club->id,
        ])
        ->assertSessionHasErrors('target_id');
});

test('members cannot reach the admin club dashboard', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('admin.clubs.index', ['locale' => 'nl']))
        ->assertForbidden();
});
