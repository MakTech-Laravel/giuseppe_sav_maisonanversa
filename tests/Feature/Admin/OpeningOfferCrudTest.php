<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\RoleEnum;
use App\Models\OpeningOffer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

function offerRoute(string $name, array $parameters = []): string
{
    return route("admin.opening-offers.{$name}", ['locale' => 'nl', ...$parameters]);
}

function numberedProduct(int $total = 10, array $attributes = []): Product
{
    return Product::factory()->limitedEdition($total)->create($attributes);
}

test('staff can see which product the opening offers, and how many numbers are left', function () {
    $this->actingAs($this->admin)
        ->get(offerRoute('index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/opening-offers/index')
            ->has('offers', 1)
            ->where('offers.0.product.slug', Product::FOUNDING_SLUG)
            ->where('offers.0.is_active', true)
            ->where('offers.0.offerable', true)
            ->has('offers.0.total')
            ->has('offers.0.available')
        );
});

test('only live numbered products can be chosen', function () {
    $good = numberedProduct();
    $simple = Product::factory()->create(['type' => ProductType::Simple]);
    $unpublished = numberedProduct(attributes: ['is_published' => false]);
    $soon = numberedProduct(attributes: ['status' => ProductStatus::ComingSoon]);

    $this->actingAs($this->admin)
        ->get(offerRoute('create'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($good, $simple, $unpublished, $soon) {
            $page->component('admin/opening-offers/create');

            $ids = collect($page->toArray()['props']['products'])->pluck('id');

            expect($ids)->toContain($good->id)
                ->not->toContain($simple->id)
                ->not->toContain($unpublished->id)
                ->not->toContain($soon->id);
        });
});

test('staff can add an offer, which becomes the one the opening shows', function () {
    $product = numberedProduct(12);
    $founding = OpeningOffer::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(offerRoute('store'), [
            'product_id' => $product->id,
            'is_active' => true,
            'sort_order' => 3,
        ])
        ->assertRedirect(offerRoute('index'));

    $offer = OpeningOffer::query()->where('product_id', $product->id)->firstOrFail();

    expect($offer->is_active)->toBeTrue()
        ->and($offer->sort_order)->toBe(3)
        ->and($founding->fresh()->is_active)->toBeFalse()
        ->and(OpeningOffer::current()->is($offer))->toBeTrue();
});

test('an offer cannot point at a product that is not a live numbered edition', function (array $attributes) {
    $product = Product::factory()->create($attributes);

    $this->actingAs($this->admin)
        ->post(offerRoute('store'), ['product_id' => $product->id, 'is_active' => true])
        ->assertSessionHasErrors('product_id');

    expect(OpeningOffer::query()->where('product_id', $product->id)->exists())->toBeFalse();
})->with([
    'a simple product' => [['type' => ProductType::Simple]],
    'an unpublished edition' => [['type' => ProductType::LimitedEdition, 'edition_total' => 5, 'is_published' => false]],
    'an edition that is coming soon' => [['type' => ProductType::LimitedEdition, 'edition_total' => 5, 'status' => ProductStatus::ComingSoon]],
    'an archived edition' => [['type' => ProductType::LimitedEdition, 'edition_total' => 5, 'status' => ProductStatus::Archived]],
]);

test('a product can only be offered once, and an unknown product is refused', function () {
    $this->actingAs($this->admin)
        ->post(offerRoute('store'), ['product_id' => OpeningOffer::query()->value('product_id'), 'is_active' => true])
        ->assertSessionHasErrors('product_id');

    $this->actingAs($this->admin)
        ->post(offerRoute('store'), ['product_id' => 999999, 'is_active' => true])
        ->assertSessionHasErrors('product_id');

    $this->actingAs($this->admin)
        ->post(offerRoute('store'), ['is_active' => true])
        ->assertSessionHasErrors('product_id');
});

test('staff can edit an offer, and the edit page lists the current product', function () {
    $offer = OpeningOffer::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->get(offerRoute('edit', ['openingOffer' => $offer->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/opening-offers/edit')
            ->where('offer.product_id', $offer->product_id)
            ->where('offer.is_active', true)
        );

    $this->actingAs($this->admin)
        ->put(offerRoute('update', ['openingOffer' => $offer->id]), [
            'product_id' => $offer->product_id,
            'is_active' => false,
            'sort_order' => 7,
        ])
        ->assertRedirect(offerRoute('index'));

    expect($offer->fresh()->is_active)->toBeFalse()
        ->and($offer->fresh()->sort_order)->toBe(7)
        ->and(OpeningOffer::current())->toBeNull();
});

test('an offer can switch to another product, but not to one that cannot be offered', function () {
    $offer = OpeningOffer::query()->firstOrFail();
    $next = numberedProduct(8);
    $simple = Product::factory()->create(['type' => ProductType::Simple]);

    $this->actingAs($this->admin)
        ->put(offerRoute('update', ['openingOffer' => $offer->id]), [
            'product_id' => $simple->id,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('product_id');

    $this->actingAs($this->admin)
        ->put(offerRoute('update', ['openingOffer' => $offer->id]), [
            'product_id' => $next->id,
            'is_active' => true,
        ])
        ->assertRedirect(offerRoute('index'));

    expect($offer->fresh()->product_id)->toBe($next->id);
});

test('an offer whose product stopped being offerable can still be edited, and is flagged', function () {
    $offer = OpeningOffer::query()->firstOrFail();
    $offer->product->update(['is_published' => false]);

    $this->actingAs($this->admin)
        ->get(offerRoute('index'))
        ->assertInertia(fn (Assert $page) => $page->where('offers.0.offerable', false));

    $this->actingAs($this->admin)
        ->put(offerRoute('update', ['openingOffer' => $offer->id]), [
            'product_id' => $offer->product_id,
            'is_active' => true,
            'sort_order' => 4,
        ])
        ->assertRedirect(offerRoute('index'));

    expect($offer->fresh()->sort_order)->toBe(4);
});

test('staff can remove an offer', function () {
    $offer = OpeningOffer::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(offerRoute('destroy', ['openingOffer' => $offer->id]))
        ->assertRedirect(offerRoute('index'));

    expect(OpeningOffer::query()->count())->toBe(0)
        ->and(Product::founding())->not->toBeNull();
});

test('only staff with the heritage permission can manage the opening offers', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $offer = OpeningOffer::query()->firstOrFail();

    $this->actingAs($staff)->get(offerRoute('index'))->assertForbidden();
    $this->actingAs($staff)->post(offerRoute('store'), ['product_id' => 1, 'is_active' => true])->assertForbidden();
    $this->actingAs($staff)->delete(offerRoute('destroy', ['openingOffer' => $offer->id]))->assertForbidden();

    $staff->givePermissionTo('heritage.view');

    $this->actingAs($staff)->get(offerRoute('index'))->assertOk();
});
