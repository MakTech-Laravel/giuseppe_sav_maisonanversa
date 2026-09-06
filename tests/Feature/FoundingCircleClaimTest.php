<?php

use App\Enums\EditionPieceStatus;
use App\Enums\FoundingCircleClaimStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\FoundingCircle\FoundingCircleClaimService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('paying for a founding product creates a pending claim without granting the role', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $session = (object) [
        'id' => 'cs_test_claim_pending',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_claim_pending',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    app(OrderFulfillment::class)->markPaidFromSession($session);

    $claim = FoundingCircleClaim::query()->where('order_id', $order->id)->first();

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($order->fresh()->edition_number)->not->toBeNull()
        ->and($claim)->not->toBeNull()
        ->and($claim->status)->toBe(FoundingCircleClaimStatus::Pending);
});

test('admin can approve a pending claim and activate founding circle', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_claim_approve',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_claim_approve',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    $claim = FoundingCircleClaim::query()->where('order_id', $order->id)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.circle.claims.approve', ['locale' => 'nl', 'claim' => $claim->id]))
        ->assertRedirect();

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and($claim->fresh()->status)->toBe(FoundingCircleClaimStatus::Approved);
});

test('admin can reject a pending claim and the serial becomes reclaimable', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $product = Product::founding();
    expect($product)->not->toBeNull();

    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('status', EditionPieceStatus::Available)
        ->orderBy('edition_number')
        ->firstOrFail();

    $editionNumber = $piece->sequenceNumber();

    $user = User::factory()->create();
    $claim = FoundingCircleClaim::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => $editionNumber,
        'status' => FoundingCircleClaimStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.circle.claims.reject', ['locale' => 'nl', 'claim' => $claim->id]), [
            'admin_note' => 'Bewijs onvoldoende',
        ])
        ->assertRedirect();

    expect($claim->fresh()->status)->toBe(FoundingCircleClaimStatus::Rejected)
        ->and($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();

    $other = User::factory()->create();
    $service = app(FoundingCircleClaimService::class);
    $reclaim = $service->createManual($other, $piece->formattedNumber().'/100');

    expect($reclaim->edition_number)->toBe($editionNumber)
        ->and($reclaim->status)->toBe(FoundingCircleClaimStatus::Pending);
});

test('members can submit a manual racket serial registration', function () {
    $product = Product::founding();
    expect($product)->not->toBeNull();

    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('status', EditionPieceStatus::Available)
        ->orderByDesc('edition_number')
        ->firstOrFail();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('member.racket-registration.store', ['locale' => 'nl']), [
            'serial' => $piece->formattedNumber().'/'.$product->edition_total,
        ])
        ->assertRedirect();

    $claim = FoundingCircleClaim::query()->where('user_id', $user->id)->first();

    expect($claim)->not->toBeNull()
        ->and($claim->edition_number)->toBe($piece->sequenceNumber())
        ->and($claim->status)->toBe(FoundingCircleClaimStatus::Pending)
        ->and($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});

test('a serial cannot be claimed twice while an active claim exists', function () {
    $product = Product::founding();
    expect($product)->not->toBeNull();

    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('status', EditionPieceStatus::Available)
        ->orderBy('edition_number')
        ->skip(1)
        ->firstOrFail();

    $first = User::factory()->create();
    FoundingCircleClaim::factory()->create([
        'user_id' => $first->id,
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => $piece->sequenceNumber(),
        'status' => FoundingCircleClaimStatus::Pending,
    ]);

    $second = User::factory()->create();

    $this->actingAs($second)
        ->post(route('member.racket-registration.store', ['locale' => 'nl']), [
            'serial' => $piece->formattedNumber(),
        ])
        ->assertSessionHasErrors('serial');
});

test('refunding an order rejects its pending founding circle claim', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $fulfillment = app(OrderFulfillment::class);
    $fulfillment->markPaidFromSession((object) [
        'id' => 'cs_test_claim_refund',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_claim_refund',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    $claim = FoundingCircleClaim::query()->where('order_id', $order->id)->firstOrFail();

    $fulfillment->markRefunded($order->fresh());

    expect($claim->fresh()->status)->toBe(FoundingCircleClaimStatus::Rejected)
        ->and($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});

test('member racket registration page renders for customers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('member.racket-registration', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/racket-registration')
            ->has('claims')
            ->where('canSubmit', true)
        );
});

test('admin claims queue lists pending registrations', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $claim = FoundingCircleClaim::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.circle.claims', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/claims')
            ->has('claims', 1)
            ->where('claims.0.id', (string) $claim->id)
            ->where('pendingCount', 1)
        );
});
