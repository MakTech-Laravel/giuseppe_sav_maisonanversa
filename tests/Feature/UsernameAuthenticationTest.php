<?php

use App\Enums\UserGender;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration creates an auto-generated username', function () {
    $this->post(route('register.store'), [
        'name' => 'Yusuf Savran',
        'email' => 'yusuf@example.com',
        'gender' => UserGender::Male->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(localized('member.dashboard', absolute: false));

    $user = User::where('email', 'yusuf@example.com')->sole();

    expect($user->username)->not->toBeEmpty()
        ->and($user->username)->toStartWith('yusuf_savran')
        ->and($user->gender)->toBe(UserGender::Male);
});

test('users can authenticate with their email', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'username' => 'maison_member',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(localized('member.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users can authenticate with their username', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'username' => 'maison_member',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => 'maison_member',
        'password' => 'password',
    ])->assertRedirect(localized('member.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('profile username stays unchanged when profile is saved', function () {
    $owner = User::factory()->create([
        'username' => 'owner_one',
        'gender' => UserGender::Male,
    ]);

    $this->actingAs($owner)
        ->patch(localized('profile.update'), [
            'name' => $owner->name,
            'email' => $owner->email,
            'gender' => UserGender::Female->value,
            'username' => 'attempted_change',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(localized('profile.edit', absolute: false));

    expect($owner->fresh()->username)->toBe('owner_one')
        ->and($owner->fresh()->gender)->toBe(UserGender::Female);
});
