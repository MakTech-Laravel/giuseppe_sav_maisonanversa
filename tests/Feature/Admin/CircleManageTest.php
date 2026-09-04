<?php

use App\Enums\RoleEnum;
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

test('staff can assign founding circle by email', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
        ])
        ->assertRedirect();

    expect($member->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue();
});

test('staff can assign founding circle by user id', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'user_id' => $member->id,
        ])
        ->assertRedirect();

    expect($member->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue();
});

test('staff can remove founding circle role', function () {
    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($this->admin)
        ->delete(route('admin.circle.remove', ['locale' => 'nl', 'member' => $member->id]))
        ->assertRedirect();

    expect($member->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});

test('circle member detail shows translated benefits in every locale', function (string $locale, string $passport, string $card) {
    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($this->admin)
        ->get(route('admin.circle.show', ['locale' => $locale, 'member' => $member->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/show')
            ->where('member.benefits', [$passport, $card])
        );
})->with([
    'nl' => ['nl', 'Digitaal Heritage Passport', 'Founding Circle-kaart'],
    'en' => ['en', 'Digital Heritage Passport', 'Founding Circle card'],
    'fr' => ['fr', 'Heritage Passport numérique', 'Carte Founding Circle'],
]);

test('circle index includes edition status and joined_at keys', function () {
    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($this->admin)
        ->get(route('admin.circle.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/index')
            ->has('members', 1)
            ->where('members.0.id', (string) $member->id)
            ->where('members.0.email', $member->email)
            ->has('members.0.edition')
            ->has('members.0.status')
            ->has('members.0.joined_at')
        );
});
