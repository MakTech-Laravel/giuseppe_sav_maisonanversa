<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(localized('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(localized('profile.update'), [
            'name' => 'Test User',
            'username' => 'test_user',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(localized('profile.edit', absolute: false));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->username)->toBe('test_user');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(localized('profile.update'), [
            'name' => 'Test User',
            'username' => $user->username,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(localized('profile.edit', absolute: false));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile avatar can be uploaded and removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(localized('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(localized('profile.edit', absolute: false));

    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);

    $this->actingAs($user)
        ->patch(localized('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'remove_avatar' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(localized('profile.edit', absolute: false));

    expect($user->fresh()->avatar)->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(localized('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(localized('profile.edit'))
        ->delete(localized('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(localized('profile.edit', absolute: false));

    expect($user->fresh())->not->toBeNull();
});
