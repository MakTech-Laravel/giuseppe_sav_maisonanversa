<?php

use App\Models\User;

beforeEach(function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    // Channels bind to the broadcaster resolved at boot (null in phpunit.xml).
    require base_path('routes/channels.php');
});

function authorizeChannel(string $channel)
{
    return test()->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => $channel,
    ]);
}

test('guests cannot authorize a private user channel', function () {
    authorizeChannel('private-App.Models.User.1')->assertForbidden();
});

test('users can authorize their own private channel', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    authorizeChannel("private-App.Models.User.{$user->id}")
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('users cannot authorize another user private channel', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user);

    authorizeChannel("private-App.Models.User.{$other->id}")->assertForbidden();
});

test('the private channel signature is signed with the reverb key', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $auth = authorizeChannel("private-App.Models.User.{$user->id}")->json('auth');

    expect($auth)->toStartWith('test-key:');
});
