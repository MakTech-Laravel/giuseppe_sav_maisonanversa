<?php

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\User;
use App\Services\Edition\EditionAllocator;

test('the purge command is a dry run unless told to execute', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create();
    $piece = app(EditionAllocator::class)->allocate($order);

    $this->artisan('maison:purge-test-data', ['--edition' => [$piece->edition_number], '--user' => [$user->email]])
        ->assertSuccessful();

    expect(Order::query()->whereKey($order->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($user->id)->exists())->toBeTrue()
        ->and($piece->fresh()->status)->toBe(EditionPieceStatus::Allocated);
});

test('executing the purge frees the number and removes the test order and account', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create();
    $piece = app(EditionAllocator::class)->allocate($order);

    $this->artisan('maison:purge-test-data', [
        '--edition' => [$piece->edition_number],
        '--user' => [$user->email],
        '--execute' => true,
    ])->assertSuccessful();

    expect(Order::query()->whereKey($order->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(EditionPiece::query()->find($piece->id)->status)->toBe(EditionPieceStatus::Available);
});

test('the purge command refuses to run with nothing to match', function () {
    $this->artisan('maison:purge-test-data')->assertFailed();
});
