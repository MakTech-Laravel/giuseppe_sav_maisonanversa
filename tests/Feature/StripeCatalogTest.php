<?php

use App\Contracts\StripeCatalogGateway;
use App\Models\Product;
use App\Services\Stripe\StripeCatalog;
use Tests\Support\FakeStripeCatalogGateway;

function bindFakeStripeCatalog(): FakeStripeCatalogGateway
{
    $gateway = new FakeStripeCatalogGateway;
    app()->instance(StripeCatalogGateway::class, $gateway);
    config(['cashier.secret' => 'sk_test_fake']);

    return $gateway;
}

test('sync creates a stripe product and price when ids are missing', function () {
    $gateway = bindFakeStripeCatalog();
    $product = Product::founding();

    $synced = app(StripeCatalog::class)->sync($product);

    expect($synced->stripe_product_id)->toBe('prod_fake_1')
        ->and($synced->stripe_price_id)->toBe('price_fake_1')
        ->and($gateway->prices['price_fake_1']['amount'])->toBe(24900)
        ->and($gateway->prices['price_fake_1']['currency'])->toBe('eur');
});

test('creating a product provisions stripe ids when keys are present', function () {
    $gateway = bindFakeStripeCatalog();

    $product = Product::factory()->create([
        'name' => 'Atelier Visit',
        'amount' => '99.99',
    ]);

    $product->refresh();

    expect($product->amount)->toBe('99.99')
        ->and($product->stripe_product_id)->toBe('prod_fake_1')
        ->and($product->stripe_price_id)->toBe('price_fake_1')
        ->and($gateway->prices['price_fake_1']['amount'])->toBe(9999);
});

test('changing the amount archives the old stripe price and stores a new id', function () {
    $gateway = bindFakeStripeCatalog();
    $product = Product::founding();

    app(StripeCatalog::class)->sync($product);
    $product->refresh();

    $product->update(['amount' => '299.00']);
    $product->refresh();

    expect($product->amount)->toBe('299.00')
        ->and($product->stripe_price_id)->toBe('price_fake_2')
        ->and($gateway->archived)->toContain('price_fake_1')
        ->and($gateway->prices['price_fake_2']['amount'])->toBe(29900);
});

test('changing only the name updates stripe without creating a new price', function () {
    $gateway = bindFakeStripeCatalog();
    $product = Product::founding();

    app(StripeCatalog::class)->sync($product);
    $product->refresh();

    $product->update(['name' => 'Heritage No.001 — Revised']);
    $product->refresh();

    expect($product->stripe_price_id)->toBe('price_fake_1')
        ->and($gateway->products[$product->stripe_product_id])->toBe('Heritage No.001 — Revised')
        ->and($gateway->archived)->toBeEmpty();
});

test('a missing stripe price id is recreated on sync', function () {
    $gateway = bindFakeStripeCatalog();
    $product = Product::founding();

    app(StripeCatalog::class)->sync($product);
    $product->refresh();

    unset($gateway->prices[$product->stripe_price_id]);

    $synced = app(StripeCatalog::class)->sync($product);

    expect($synced->stripe_price_id)->toBe('price_fake_2');
});

test('sync is skipped when stripe is not configured', function () {
    config(['cashier.secret' => null]);

    $product = Product::founding();
    $synced = app(StripeCatalog::class)->sync($product);

    expect($synced->stripe_product_id)->toBeNull()
        ->and($synced->stripe_price_id)->toBeNull();
});
