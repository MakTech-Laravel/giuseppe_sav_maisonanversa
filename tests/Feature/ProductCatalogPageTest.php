<?php

use App\Enums\ProductStatus;
use App\Models\Product;

test('the catalog index paginates published products', function () {
    Product::factory()->count(15)->create();

    $this->get(localized('maison.products'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/products/index')
            ->has('products.data', 12)
            ->where('products.total', 16)
            ->has('filters'));
});

test('the catalog index excludes unpublished products', function () {
    Product::factory()->create(['name' => 'Hidden Product', 'is_published' => false]);

    $this->get(localized('maison.products'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($data) => collect($data)->pluck('name')->doesntContain('Hidden Product')));
});

test('the catalog index can be searched by name', function () {
    Product::factory()->create(['name' => 'Padel Bag Onyx']);
    Product::factory()->create(['name' => 'Wristband Set']);

    $this->get(localized('maison.products', ['search' => 'Onyx']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($data) => collect($data)->pluck('name')->contains('Padel Bag Onyx')
                && ! collect($data)->pluck('name')->contains('Wristband Set')));
});

test('the catalog index can be filtered by status', function () {
    Product::factory()->create(['name' => 'Soon Arriving', 'status' => ProductStatus::ComingSoon]);
    Product::factory()->create(['name' => 'Ready Now', 'status' => ProductStatus::Active]);

    $this->get(localized('maison.products', ['status' => 'coming_soon']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.data', fn ($data) => collect($data)->pluck('name')->contains('Soon Arriving')
                && ! collect($data)->pluck('name')->contains('Ready Now')));
});

test('an unpublished product slug 404s on the detail page', function () {
    $product = Product::factory()->create(['is_published' => false]);

    $this->get(localized('maison.products.show', ['product' => $product->slug]))
        ->assertNotFound();
});

test('a published product detail page renders its own checkout context', function () {
    $product = Product::factory()->create([
        'name' => 'Accessory Pouch',
        'amount' => '39.00',
    ]);

    $this->get(localized('maison.products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/products/show')
            ->where('product.slug', $product->slug)
            ->where('productCheckout.productId', $product->id)
            ->where('productCheckout.amount', '39.00')
            ->has('productEdition')
            ->has('related'));
});
