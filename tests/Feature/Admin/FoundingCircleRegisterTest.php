<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\FoundingCircleClaim;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\FoundingCircle\FoundingCircleClaimService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('paying for a founding-circle product creates a pending claim without a register entry', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $session = (object) [
        'id' => 'cs_test_register',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_register',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    app(OrderFulfillment::class)->markPaidFromSession($session);

    expect(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(FoundingCircleClaim::query()->where('order_id', $order->id)->exists())->toBeTrue();
});

test('the register survives role removal and refund after approval', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $session = (object) [
        'id' => 'cs_test_register_refund',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_register_refund',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    $fulfillment = app(OrderFulfillment::class);
    $fulfillment->markPaidFromSession($session);

    $claim = FoundingCircleClaim::query()->where('order_id', $order->id)->firstOrFail();
    app(FoundingCircleClaimService::class)->approve($claim, $this->admin);

    expect(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->count())->toBe(1);

    $fulfillment->markRefunded($order->fresh());
    $user->removeRole(RoleEnum::FOUNDING_CIRCLE->value);

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('manually assigning a member via admin also writes a register entry', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
        ])
        ->assertRedirect();

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->name)->toBe($member->name)
        ->and($entry->edition_number)->toBeNull();
});

test('assigning the same member twice does not duplicate the register entry', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), ['email' => $member->email])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), ['email' => $member->email])
        ->assertRedirect();

    expect(FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->count())->toBe(1);
});

test('admin register page lists entries independent of current membership', function () {
    $member = User::factory()->create();
    FoundingCircleRegisterEntry::factory()->create([
        'user_id' => $member->id,
        'name' => $member->name,
        'edition_number' => 7,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.circle.register', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/register')
            ->has('entries', 1)
            ->where('entries.0.name', $member->name)
            ->where('entries.0.edition_number', '007')
            ->where('entries.0.still_member', false)
        );
});

test('a guest cannot view the founding circle register', function () {
    $this->get(route('admin.circle.register', ['locale' => 'nl']))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('the register keeps the original name after a profile change', function () {
    $member = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
        ])
        ->assertRedirect();

    $member->update(['name' => 'Changed Name']);

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->name)->toBe('Original Name');
});
