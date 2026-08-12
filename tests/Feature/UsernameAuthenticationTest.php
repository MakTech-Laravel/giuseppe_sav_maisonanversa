<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration creates an auto-generated username', function () {
    $this->post(route('register.store'), [
        'name' => 'Yusuf Savran',
        'email' => 'yusuf@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('member.dashboard', absolute: false));

    $user = User::where('email', 'yusuf@example.com')->sole();

    expect($user->username)->not->toBeEmpty()
        ->and($user->username)->toStartWith('yusuf_savran');
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
    ])->assertRedirect(route('member.dashboard', absolute: false));

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
    ])->assertRedirect(route('member.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('profile username updates must stay unique', function () {
    $owner = User::factory()->create(['username' => 'owner_one']);
    User::factory()->create(['username' => 'taken_name']);

    $this->actingAs($owner)
        ->patch(route('profile.update'), [
            'name' => $owner->name,
            'email' => $owner->email,
            'username' => 'taken_name',
        ])
        ->assertSessionHasErrors('username');

    $this->actingAs($owner)
        ->patch(route('profile.update'), [
            'name' => $owner->name,
            'email' => $owner->email,
            'username' => 'owner_two',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($owner->fresh()->username)->toBe('owner_two');
});
