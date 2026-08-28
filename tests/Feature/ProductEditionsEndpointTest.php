<?php

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Edition\LimitedEditionLedger;

test('editions endpoint returns all pieces paginated by 30 with range meta', function () {
    $product = Product::factory()->limitedEdition(40)->create([
        'is_published' => true,
        'slug' => 'picker-product',
    ]);
    app(LimitedEditionLedger::class)->sync($product);

    EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(1))
        ->update(['status' => EditionPieceStatus::Archive]);

    $response = $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]))->assertOk()
        ->assertJsonPath('meta.per_page', 30)
        ->assertJsonPath('meta.total', 40)
        ->assertJsonPath('meta.range.start', 1)
        ->assertJsonPath('meta.range.end', 40);

    $data = collect($response->json('data'));

    expect($data)->toHaveCount(30)
        ->and($data->firstWhere('edition_number', 1))
        ->toMatchArray([
            'status' => 'archive',
            'selectable' => false,
        ])
        ->and($data->firstWhere('edition_number', 2))
        ->toMatchArray([
            'status' => 'available',
            'selectable' => true,
        ]);
});

test('editions endpoint search returns matching pieces with status labels data', function () {
    $product = Product::factory()->limitedEdition(20)->create([
        'is_published' => true,
        'slug' => 'picker-search',
    ]);
    app(LimitedEditionLedger::class)->sync($product);

    EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(12))
        ->update(['status' => EditionPieceStatus::Allocated]);

    EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(1))
        ->update(['status' => EditionPieceStatus::Archive]);

    $sold = $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]).'?search=12')
        ->assertOk()
        ->json('data');

    expect($sold)->toHaveCount(1)
        ->and($sold[0])->toMatchArray([
            'edition_number' => 12,
            'status' => 'allocated',
            'selectable' => false,
        ]);

    $archived = $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]).'?search=1')
        ->assertOk()
        ->json('data');

    expect(collect($archived)->pluck('edition_number')->all())
        ->toContain(1)
        ->and(collect($archived)->firstWhere('edition_number', 1))
        ->toMatchArray([
            'status' => 'archive',
            'selectable' => false,
        ]);
});

test('editions endpoint search matches full label and padded prefix for affixed products', function () {
    $product = Product::factory()->limitedEdition(10)->create([
        'is_published' => true,
        'slug' => 'picker-affix-search',
        'edition_number_prefix' => 'EXC',
        'edition_number_postfix' => 'ST',
    ]);
    app(LimitedEditionLedger::class)->sync($product);

    $labelOne = $product->formatEditionLabel(1);

    expect($labelOne)->toBe('EXC001ST');

    $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]).'?search=EXC001ST')
        ->assertOk()
        ->assertJsonPath('data.0.edition_number', 1)
        ->assertJsonPath('data.0.label', 'EXC001ST');

    $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]).'?search=001')
        ->assertOk()
        ->assertJsonPath('data.0.edition_number', 1);

    $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]).'?search=EXC')
        ->assertOk()
        ->assertJsonCount(10, 'data');
});

test('editions endpoint returns not found for simple products', function () {
    $product = Product::factory()->create([
        'type' => ProductType::Simple,
        'is_published' => true,
        'slug' => 'simple-no-editions',
    ]);

    $this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]))->assertNotFound();
});
