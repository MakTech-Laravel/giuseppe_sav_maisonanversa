<?php

use App\Models\User;
use App\Services\Stripe\StripeCustomerGuard;
use Stripe\Exception\InvalidRequestException;

test('stripe customer guard clears a missing customer id', function () {
    $user = User::factory()->create([
        'stripe_id' => 'cus_missing_local',
        'pm_type' => 'card',
        'pm_last_four' => '4242',
    ]);

    $partial = Mockery::mock($user)->makePartial();
    $partial->shouldReceive('asStripeCustomer')
        ->once()
        ->andThrow(InvalidRequestException::factory(
            "No such customer: 'cus_missing_local'",
            404,
            '{}',
            ['error' => ['message' => "No such customer: 'cus_missing_local'"]],
            null,
            'resource_missing',
        ));

    app(StripeCustomerGuard::class)->forgetIfMissing($partial);

    $user->refresh();

    expect($user->stripe_id)->toBeNull()
        ->and($user->pm_type)->toBeNull()
        ->and($user->pm_last_four)->toBeNull();
});

test('stripe customer guard leaves unrelated stripe errors alone', function () {
    $user = User::factory()->create([
        'stripe_id' => 'cus_rate_limited',
    ]);

    $partial = Mockery::mock($user)->makePartial();
    $partial->shouldReceive('asStripeCustomer')
        ->once()
        ->andThrow(InvalidRequestException::factory(
            'Rate limit exceeded',
            429,
            '{}',
            ['error' => ['message' => 'Rate limit exceeded']],
            null,
            'rate_limit',
        ));

    expect(fn () => app(StripeCustomerGuard::class)->forgetIfMissing($partial))
        ->toThrow(InvalidRequestException::class);

    expect($user->fresh()->stripe_id)->toBe('cus_rate_limited');
});

test('stripe customer guard skips users without a stripe id', function () {
    $user = User::factory()->create([
        'stripe_id' => null,
    ]);

    $partial = Mockery::mock($user)->makePartial();
    $partial->shouldNotReceive('asStripeCustomer');

    app(StripeCustomerGuard::class)->forgetIfMissing($partial);

    expect($user->fresh()->stripe_id)->toBeNull();
});
