<?php

use App\Enums\ProductType;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\Order;
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

test('staff can view the product catalog', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/index')
            ->has('products.data', 1)
        );
});

test('staff can create a limited edition product and provision pieces', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Atelier Visit',
            'slug' => 'atelier-visit',
            'type' => ProductType::LimitedEdition->value,
            'amount' => '149.00',
            'edition_total' => 5,
            'archive_edition_numbers' => '1',
            'is_published' => true,
            'grants_founding_circle' => false,
            'expected_delivery_label' => 'Autumn 2027 — subject to production',
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'atelier-visit')->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe(ProductType::LimitedEdition)
        ->and($product->edition_total)->toBe(5)
        ->and(EditionPiece::query()->where('product_id', $product->id)->count())->toBe(5)
        ->and(EditionPiece::query()->where('product_id', $product->id)->where('edition_number', 1)->first()?->status->value)
        ->toBe('archive');
});

test('staff can create a simple product with stock', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Canvas Cloth',
            'type' => ProductType::Simple->value,
            'amount' => '35.00',
            'stock_quantity' => 12,
            'is_published' => true,
            'grants_founding_circle' => false,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'canvas-cloth')->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe(ProductType::Simple)
        ->and($product->stock_quantity)->toBe(12)
        ->and(EditionPiece::query()->where('product_id', $product->id)->count())->toBe(0);
});

test('staff can open inventory for a limited product', function () {
    $product = Product::founding();

    $this->actingAs($this->admin)
        ->get(route('admin.products.inventory', ['product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->where('inventory.total', 100)
            ->where('inventory.rows.0.sku', $product->skuPrefix().'-001')
        );
});

test('viewers cannot create products', function () {
    $viewer = User::factory()->admin()->create();
    $viewer->assignRole(RoleEnum::VIEWER->value);
    $viewer->syncTypeFromRoles();

    $this->actingAs($viewer)
        ->post(route('admin.products.store'), [
            'name' => 'Blocked',
            'type' => ProductType::Simple->value,
            'amount' => '10.00',
            'stock_quantity' => 1,
            'is_published' => true,
            'grants_founding_circle' => false,
        ])
        ->assertForbidden();
});

test('staff can delete a product without orders', function () {
    $product = Product::factory()->create([
        'name' => 'Disposable Cloth',
        'slug' => 'disposable-cloth',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.products.destroy', ['product' => $product->id]))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::query()->find($product->id))->toBeNull();
});

test('staff cannot delete a product that has orders', function () {
    $product = Product::founding();

    Order::factory()->create(['product_id' => $product->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.products.index'))
        ->delete(route('admin.products.destroy', ['product' => $product->id]))
        ->assertRedirect()
        ->assertSessionHasErrors('product');

    expect(Product::query()->find($product->id))->not->toBeNull();
});
