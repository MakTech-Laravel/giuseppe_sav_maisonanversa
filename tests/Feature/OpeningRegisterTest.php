<?php

use App\Enums\EditionPieceStatus;
use App\Enums\ProductStatus;
use App\Models\EditionPiece;
use App\Models\OpeningOffer;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use App\Services\Edition\LimitedEditionLedger;
use App\Support\OpeningRegisterPresenter;
use Inertia\Testing\AssertableInertia as Assert;

/** Replaces the seeded founding offer with one for a fresh numbered product. */
function offerFor(int $total = 12, array $product = []): OpeningOffer
{
    OpeningOffer::query()->delete();

    $model = Product::factory()->limitedEdition($total)->create($product);
    app(LimitedEditionLedger::class)->sync($model);

    return OpeningOffer::factory()->create(['product_id' => $model->id]);
}

function presented(): ?array
{
    return app(OpeningRegisterPresenter::class)->forUser(null);
}

function setPiece(Product $product, int $number, EditionPieceStatus $status, ?DateTimeInterface $until = null): void
{
    EditionPiece::query()
        ->where('product_id', $product->id)
        ->get()
        ->first(fn (EditionPiece $piece): bool => $piece->sequenceNumber() === $number)
        ->update(['status' => $status, 'reserved_until' => $until]);
}

test('the seeded opening offers the founding product with its real numbers', function () {
    $register = presented();

    expect($register)->not->toBeNull()
        ->and($register['product']['productSlug'])->toBe(Product::FOUNDING_SLUG)
        ->and($register['tiles'])->toHaveCount(100)
        // The founding product archives number 1, so that tile is not available.
        ->and($register['tiles'][0]['available'])->toBeFalse()
        ->and($register['available'])->toBe(count(array_filter($register['tiles'], fn ($t) => $t['available'])));
});

test('the tiles follow the chosen product, never a fixed 100', function () {
    $offer = offerFor(12);

    $register = presented();

    expect($register['tiles'])->toHaveCount(12)
        ->and($register['total'])->toBe(12)
        ->and($register['available'])->toBe(12)
        ->and($register['name'])->toBe($offer->product->name)
        ->and($register['product']['productId'])->toBe($offer->product_id)
        ->and($register['product']['productSlug'])->toBe($offer->product->slug);

    $other = offerFor(30);

    expect(presented()['tiles'])->toHaveCount(30)
        ->and(presented()['product']['productSlug'])->toBe($other->product->slug);
});

test('the register hands the page every number, however many: paging is the page\'s job', function (int $total) {
    offerFor($total);

    $register = presented();

    expect($register['tiles'])->toHaveCount($total)
        ->and($register['total'])->toBe($total)
        ->and($register['available'])->toBe($total);
})->with([
    'exactly one page' => 100,
    'one more than a page' => 101,
    'three pages' => 250,
]);

test('the edition name is the chosen product\'s, in the visitor\'s language', function () {
    $offer = offerFor(12, ['name' => 'Heritage Nº 002 · Second Edition']);

    $offer->product->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'name'],
        ['value' => 'Heritage Nº 002 · Second Edition (EN)', 'source_hash' => $offer->product->translationSourceHash('name')],
    );

    expect(presented()['name'])->toBe('Heritage Nº 002 · Second Edition');

    app()->setLocale('en');

    expect(presented()['name'])->toBe('Heritage Nº 002 · Second Edition (EN)');
});

test('only pieces that can be bought print as available', function () {
    $offer = offerFor(12);
    $product = $offer->product;

    setPiece($product, 1, EditionPieceStatus::Allocated);
    setPiece($product, 2, EditionPieceStatus::Archive);
    setPiece($product, 3, EditionPieceStatus::Reserved, now()->addMinutes(10));
    setPiece($product, 4, EditionPieceStatus::Reserved, now()->subMinute());
    setPiece($product, 5, EditionPieceStatus::Returning);

    Cache::forget(OpeningRegisterPresenter::cacheKey($product));

    $tiles = collect(presented()['tiles']);

    expect($tiles->take(5)->pluck('available')->all())->toBe([false, false, false, true, false])
        ->and($tiles->skip(5)->every(fn (array $tile): bool => $tile['available']))->toBeTrue()
        ->and(presented()['available'])->toBe(8)
        ->and(presented()['tiles'])->toHaveCount(12);
});

test('the tile labels are the picker labels, with the product affixes, and carry no sku', function () {
    $offer = offerFor(12, ['edition_number_prefix' => 'EXC', 'edition_number_postfix' => 'ST']);
    app(LimitedEditionLedger::class)->sync($offer->product->fresh());
    Cache::forget(OpeningRegisterPresenter::cacheKey($offer->product));

    $tile = presented()['tiles'][0];

    expect($tile['label'])->toBe('EXC001ST')
        ->and(array_keys($tile))->toBe(['id', 'label', 'available']);
});

test('nothing is printed when no offer is active or the product cannot be offered', function () {
    $offer = offerFor(12);

    expect(presented())->not->toBeNull();

    // Not published.
    $offer->product->update(['is_published' => false]);
    expect(presented())->toBeNull();
    $offer->product->update(['is_published' => true]);

    // Not active any more.
    $offer->product->update(['status' => ProductStatus::ComingSoon]);
    expect(presented())->toBeNull();
    $offer->product->update(['status' => ProductStatus::Active]);

    // Early access that has not opened yet, for an ordinary visitor.
    $offer->product->update(['public_at' => now()->addWeek()]);
    expect(presented())->toBeNull();
    $offer->product->update(['public_at' => null]);

    expect(presented())->not->toBeNull();

    // The offer itself is switched off.
    $offer->update(['is_active' => false]);
    expect(presented())->toBeNull();
});

test('activating an offer deactivates the others, so the opening shows one product', function () {
    $first = offerFor(12);
    $second = OpeningOffer::factory()->inactive()->create();

    expect($first->fresh()->is_active)->toBeTrue();

    $second->update(['is_active' => true]);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->is_active)->toBeTrue()
        ->and(OpeningOffer::query()->active()->count())->toBe(1)
        ->and(OpeningOffer::current()->is($second))->toBeTrue();
});

test('a sale or a hold refreshes the tiles straight away', function () {
    $offer = offerFor(12);

    expect(presented()['available'])->toBe(12);

    setPiece($offer->product, 7, EditionPieceStatus::Allocated);
    // Still cached for a moment...
    expect(presented()['available'])->toBe(12);

    // ...until the inventory is busted, which every hold, sale and release does.
    app(EditionInventory::class)->bust($offer->product);

    expect(presented()['available'])->toBe(11);
});

test('the home page carries the register for the opening', function () {
    $offer = offerFor(12);

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->has('opening.tiles', 12)
            ->where('opening.product.productSlug', $offer->product->slug)
            ->where('opening.product.productId', $offer->product_id)
            ->where('opening.available', 12)
        );

    $offer->update(['is_active' => false]);

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->where('opening', null)
        );
});
