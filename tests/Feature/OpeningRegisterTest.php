<?php

use App\Enums\EditionPieceStatus;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\HomeHero;
use App\Models\Product;
use App\Models\User;
use App\Services\Home\OpeningRegisterSnapshot;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('the homepage shares a live opening register for the founding product by default', function () {
    $product = Product::founding();
    expect($product)->not->toBeNull();

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->where('openingRegister.product.productId', $product->id)
            ->where('openingRegister.product.productSlug', $product->slug)
            ->has('openingRegister.places')
            ->where('openingRegister.available_count', fn ($count) => is_int($count) && $count >= 0)
        );
});

test('admin can assign an opening product and the homepage uses it', function () {
    $product = Product::factory()->limitedEdition(5)->create([
        'is_published' => true,
        'slug' => 'atelier-limited',
        'name' => 'Atelier Limited',
    ]);

    EditionPiece::query()->where('product_id', $product->id)->delete();

    foreach (range(1, 5) as $number) {
        EditionPiece::factory()->create([
            'product_id' => $product->id,
            'edition_number' => $product->formatEditionLabel($number),
            'status' => EditionPieceStatus::Available,
        ]);
    }

    HomeHero::current()->update(['opening_product_id' => $product->id]);
    app(OpeningRegisterSnapshot::class)->bust($product);

    expect(HomeHero::current()->fresh()->opening_product_id)->toBe($product->id);

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('openingRegister.product.productId', $product->id)
            ->where('openingRegister.product.productSlug', 'atelier-limited')
            ->where('openingRegister.product.editionTotal', 5)
            ->has('openingRegister.places', 5)
            ->where('openingRegister.available_count', 5)
        );
});

test('opening register prints exactly edition_total boxes even when extra pieces exist', function () {
    $product = Product::factory()->limitedEdition(3)->create([
        'is_published' => true,
        'slug' => 'opening-three',
        'name' => 'Opening Three',
        'edition_total' => 3,
    ]);

    EditionPiece::query()->where('product_id', $product->id)->delete();

    foreach (range(1, 5) as $number) {
        EditionPiece::factory()->create([
            'product_id' => $product->id,
            'edition_number' => $product->formatEditionLabel($number),
            'status' => EditionPieceStatus::Available,
        ]);
    }

    HomeHero::current()->update(['opening_product_id' => $product->id]);
    app(OpeningRegisterSnapshot::class)->bust($product);

    $snapshot = app(OpeningRegisterSnapshot::class)->forViewer(null);

    expect($snapshot['product']['editionTotal'])->toBe(3)
        ->and($snapshot['places'])->toHaveCount(3)
        ->and(collect($snapshot['places'])->pluck('sequence')->all())->toBe([1, 2, 3]);
});

test('admin cannot hold an opening register edition', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('status', EditionPieceStatus::Available)
        ->firstOrFail();

    $this->actingAs($admin)
        ->postJson(localized('maison.products.editions.hold', [
            'product' => $product->slug,
            'editionPiece' => $piece->id,
        ]))
        ->assertForbidden();
});

test('opening register marks held pieces reserved except for the holder', function () {
    $product = Product::founding();
    $holder = actingAsCheckoutUser();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(12))
        ->firstOrFail();

    $piece->update([
        'status' => EditionPieceStatus::Reserved,
        'reserved_by_user_id' => $holder->id,
        'reserved_until' => now()->addMinutes(15),
    ]);

    app(OpeningRegisterSnapshot::class)->bust($product);

    $guestSnapshot = app(OpeningRegisterSnapshot::class)->forViewer(null);
    $holderSnapshot = app(OpeningRegisterSnapshot::class)->forViewer($holder);

    $guestPlace = collect($guestSnapshot['places'])->firstWhere('sequence', 12);
    $holderPlace = collect($holderSnapshot['places'])->firstWhere('sequence', 12);

    expect($guestPlace['reserved'])->toBeTrue()
        ->and($holderPlace['reserved'])->toBeFalse()
        ->and($holderPlace['edition_piece_id'])->toBe($piece->id);
});

test('claim hold for an opening register piece uses the existing edition hold endpoint', function () {
    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(15))
        ->firstOrFail();

    $this->postJson(localized('maison.products.editions.hold', [
        'product' => $product->slug,
        'editionPiece' => $piece->id,
    ]))->assertOk()
        ->assertJsonPath('id', $piece->id)
        ->assertJsonPath('status', 'reserved');

    expect($piece->fresh()->isHeldBy($user))->toBeTrue();
});
