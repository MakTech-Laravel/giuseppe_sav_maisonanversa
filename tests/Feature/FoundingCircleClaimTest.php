<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\FoundingCircleClaim;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('paying for a founding product inscribes the buyer and does not open a claim', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_no_claim',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_no_claim',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue()
        ->and(FoundingCircleClaim::query()->count())->toBe(0);
});

test('the racket registration and claims screens are not routed', function () {
    expect(Route::has('member.racket-registration'))->toBeFalse()
        ->and(Route::has('admin.circle.claims'))->toBeFalse();
});
