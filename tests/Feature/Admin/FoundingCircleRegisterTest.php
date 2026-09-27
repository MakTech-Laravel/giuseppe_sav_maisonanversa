<?php

use App\Enums\OrderStatus;
use App\Enums\RegisterVisibility;
use App\Enums\RoleEnum;
use App\Models\FoundingCircleClaim;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('paying for heritage no.001 writes a private register row and no claim', function () {
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

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->edition_number)->toBe($order->fresh()->edition_number)
        ->and($entry->register_visibility)->toBe(RegisterVisibility::Private)
        ->and(FoundingCircleClaim::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('a refund frees the number and drops the founding circle role', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $fulfillment = app(OrderFulfillment::class);
    $fulfillment->markPaidFromSession((object) [
        'id' => 'cs_test_register_refund',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_register_refund',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    $number = $order->fresh()->edition_number;

    $fulfillment->markRefunded($order->fresh());

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(FoundingCircleRegisterEntry::query()->where('edition_number', $number)->exists())->toBeFalse();
});

test('manually assigning a member requires a free edition number', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
            'edition_number' => 18,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->first();

    expect($member->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue()
        ->and($entry)->not->toBeNull()
        ->and($entry->edition_number)->toBe(18)
        ->and($entry->name)->toBe($member->name);
});

test('assigning the same member twice does not duplicate the register entry', function () {
    $member = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
            'edition_number' => 19,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
            'edition_number' => 19,
        ])
        ->assertRedirect();

    expect(FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->count())->toBe(1);
});

test('admin register page lists all one hundred places', function () {
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
            ->has('entries', 100)
            ->where('entries.6.number', '007')
            ->where('entries.6.member_name', $member->name)
            ->where('entries.6.state', 'inscribed')
        );
});

test('a guest cannot view the admin founding circle register', function () {
    $this->get(route('admin.circle.register', ['locale' => 'nl']))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('the stored snapshot name stays historical while the public name follows the account', function () {
    $member = User::factory()->create([
        'name' => 'Original Name',
        'first_name' => 'Original',
        'last_name' => 'Name',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $member->email,
            'edition_number' => 21,
        ])
        ->assertRedirect();

    $member->update([
        'name' => 'Changed Name',
        'first_name' => 'Changed',
        'last_name' => 'Name',
    ]);

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->first();
    $entry->update([
        'register_visibility' => RegisterVisibility::Full,
        'register_consent_at' => now(),
    ]);

    expect($entry->fresh()->name)->toBe('Original Name');

    $this->get(route('maison.register', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('places.20.name', 'Changed Name')
            ->where('places.20.label_key', null)
        );
});

test('claim and racket registration routes are no longer registered', function () {
    expect(Route::has('member.racket-registration'))->toBeFalse()
        ->and(Route::has('member.racket-registration.store'))->toBeFalse()
        ->and(Route::has('admin.circle.claims'))->toBeFalse()
        ->and(Route::has('admin.circle.claims.approve'))->toBeFalse();
});
