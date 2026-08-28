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

test('empty product seo fields fall back to name description and gallery on the storefront', function () {
    $product = Product::factory()->create([
        'name' => 'Fallback Pouch',
        'description' => 'Catalog description for fallback.',
        'gallery' => ['heritage-001-front'],
    ]);

    $this->get(localized('maison.products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Fallback Pouch — Maison Anversa')
            ->where('seo.description', 'Catalog description for fallback.')
            ->where('seo.keywords', null)
            ->where('seo.ogImage', url('/images/product/heritage-001-front.png'))
            ->where('seo.ogImageWidth', null)
            ->where('seo.ogImageHeight', null));
});

test('custom product seo fields are used on the storefront without copying into empty keywords', function () {
    $product = Product::factory()->create([
        'name' => 'Named Pouch',
        'description' => 'Should not appear in meta.',
        'meta_title' => 'Buy the Named Pouch',
        'meta_description' => 'Custom pouch meta description',
        'meta_keywords' => 'pouch, accessory',
        'gallery' => ['heritage-001-front'],
        'og_image' => 'products/custom-og.jpg',
    ]);

    $this->get(localized('maison.products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Buy the Named Pouch')
            ->where('seo.description', 'Custom pouch meta description')
            ->where('seo.keywords', 'pouch, accessory')
            ->where('seo.ogImage', url('/storage/products/custom-og.jpg')));
});

test('a product without seo or gallery uses the house default open graph image', function () {
    $product = Product::factory()->create([
        'name' => 'Plain Product',
        'description' => 'No gallery.',
        'gallery' => [],
    ]);

    $this->get(localized('maison.products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Plain Product — Maison Anversa')
            ->where('seo.keywords', null)
            ->where('seo.ogImage', url((string) config('maison.seo.image')))
            ->where('seo.ogImageWidth', 1024)
            ->where('seo.ogImageHeight', 682));
});
