<?php

use App\Enums\EditionPieceStatus;
use App\Enums\UserGender;
use App\Models\CommerceSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\ProductCheckout;
use App\Support\StorefrontGates;
use Laravel\Fortify\Features;
use Mockery\MockInterface;

test('storefront gates default to open', function () {
    expect(StorefrontGates::purchasesEnabled())->toBeTrue()
        ->and(StorefrontGates::registrationEnabled())->toBeTrue();
});

test('purchases require both env and admin toggles', function () {
    CommerceSetting::current()->update(['purchases_enabled' => true]);
    config(['maison.checkout.enabled' => true]);
    expect(StorefrontGates::purchasesEnabled())->toBeTrue();

    config(['maison.checkout.enabled' => false]);
    expect(StorefrontGates::purchasesEnabled())->toBeFalse();

    config(['maison.checkout.enabled' => true]);
    CommerceSetting::current()->update(['purchases_enabled' => false]);
    expect(StorefrontGates::purchasesEnabled())->toBeFalse();
});

test('registration requires both env and admin toggles', function () {
    CommerceSetting::current()->update(['registration_enabled' => true]);
    config(['maison.registration.enabled' => true]);
    expect(StorefrontGates::registrationEnabled())->toBeTrue();

    config(['maison.registration.enabled' => false]);
    expect(StorefrontGates::registrationEnabled())->toBeFalse();

    config(['maison.registration.enabled' => true]);
    CommerceSetting::current()->update(['registration_enabled' => false]);
    expect(StorefrontGates::registrationEnabled())->toBeFalse();
});

test('shared props report purchase and registration enablement', function () {
    CommerceSetting::current()->update([
        'purchases_enabled' => false,
        'registration_enabled' => false,
    ]);

    $this->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('checkout.enabled', false)
            ->where('registration.enabled', false));
});

test('checkout is rejected when admin pauses purchases', function () {
    CommerceSetting::current()->update(['purchases_enabled' => false]);

    actingAsCheckoutUser();

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('create');
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload())
        ->assertSessionHasErrors('checkout');

    expect(Order::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

test('edition hold is rejected while purchases are paused', function () {
    CommerceSetting::current()->update(['purchases_enabled' => false]);

    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = $product->editionPieces()
        ->where('status', EditionPieceStatus::Available)
        ->firstOrFail();

    $this->postJson(localized('maison.products.editions.hold', [
        'product' => $product->slug,
        'editionPiece' => $piece->id,
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors('checkout');

    expect($piece->fresh()->reserved_by_user_id)->toBeNull();
    expect($user)->not->toBeNull();
});

test('registration is rejected when paused', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    CommerceSetting::current()->update(['registration_enabled' => false]);

    $this->post(route('register.store'), [
        'name' => 'Paused User',
        'email' => 'paused-register@example.com',
        'gender' => UserGender::Male->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->where('email', 'paused-register@example.com')->exists())->toBeFalse();
});

test('registration is rejected when only the env kill switch is off', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    CommerceSetting::current()->update(['registration_enabled' => true]);
    config(['maison.registration.enabled' => false]);

    $this->post(route('register.store'), [
        'name' => 'Env Paused User',
        'email' => 'env-paused-register@example.com',
        'gender' => UserGender::Female->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->where('email', 'env-paused-register@example.com')->exists())->toBeFalse();

    $this->get(route('register'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login');

    $this->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('registration.enabled', false)
            ->where('checkout.enabled', true));
});

test('admin purchase toggle cannot override an env kill switch', function () {
    CommerceSetting::current()->update(['purchases_enabled' => true]);
    config(['maison.checkout.enabled' => false]);

    $this->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('checkout.enabled', false));
});

test('register deep link opens login when registration is paused', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    CommerceSetting::current()->update(['registration_enabled' => false]);

    $this->get(route('register'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login');
});
