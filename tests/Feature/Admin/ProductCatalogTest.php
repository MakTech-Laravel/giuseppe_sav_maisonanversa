<?php

use App\Enums\ProductType;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'archive_edition_numbers' => [1, 3],
            'is_published' => true,
            'grants_founding_circle' => false,
            'expected_delivery_label' => 'Autumn 2027 — subject to production',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', __('Product aangemaakt.'));

    $product = Product::query()->where('slug', 'atelier-visit')->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe(ProductType::LimitedEdition)
        ->and($product->edition_total)->toBe(5)
        ->and($product->archiveEditionNumberList())->toBe([1, 3])
        ->and(EditionPiece::query()->where('product_id', $product->id)->count())->toBe(5)
        ->and(EditionPiece::query()->where('product_id', $product->id)->where('edition_number', 1)->first()?->status->value)
        ->toBe('archive')
        ->and(EditionPiece::query()->where('product_id', $product->id)->where('edition_number', 3)->first()?->status->value)
        ->toBe('archive');
});

test('staff can create a product with primary and gallery images', function () {
    Storage::fake('public');

    $primary = UploadedFile::fake()->image('cover.jpg', 800, 1000);
    $galleryA = UploadedFile::fake()->image('gallery-a.jpg', 800, 1000);
    $galleryB = UploadedFile::fake()->image('gallery-b.jpg', 800, 1000);

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Heritage Studio Pack',
            'slug' => 'heritage-studio-pack',
            'type' => ProductType::Simple->value,
            'amount' => '89.00',
            'stock_quantity' => 4,
            'is_published' => true,
            'grants_founding_circle' => false,
            'primary_image' => $primary,
            'gallery_images' => [$galleryA, $galleryB],
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'heritage-studio-pack')->first();

    expect($product)->not->toBeNull()
        ->and($product->gallery)->toHaveCount(3);

    Storage::disk('public')->assertExists($product->gallery[0]);
    Storage::disk('public')->assertExists($product->gallery[1]);
    Storage::disk('public')->assertExists($product->gallery[2]);
});

test('staff can update archive numbers via the product edit form', function () {
    $product = Product::factory()->create([
        'name' => 'Edition Set',
        'slug' => 'edition-set',
        'type' => ProductType::LimitedEdition,
        'edition_total' => 5,
        'archive_edition_numbers' => [1],
        'amount' => '120.00',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', ['product' => $product->id]), [
            'name' => 'Edition Set',
            'slug' => 'edition-set',
            'type' => ProductType::LimitedEdition->value,
            'amount' => '120.00',
            'edition_total' => 5,
            'archive_edition_numbers' => [1, 5],
            'is_published' => true,
            'grants_founding_circle' => false,
            'gallery_keep' => [],
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->archiveEditionNumberList())->toBe([1, 5])
        ->and(EditionPiece::query()->where('product_id', $product->id)->where('edition_number', 5)->first()?->status->value)
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
            ->where('inventory.rows.0.sku', $product->formatEditionSku(1))
            ->where('inventory.rows.0.label', $product->formatEditionLabel(1))
        );
});

test('staff can set edition number prefix and postfix', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Numbered Series',
            'slug' => 'numbered-series',
            'type' => ProductType::LimitedEdition->value,
            'amount' => '199.00',
            'edition_total' => 12,
            'edition_number_prefix' => 'MA-',
            'edition_number_postfix' => '-A',
            'archive_edition_numbers' => [1],
            'is_published' => true,
            'grants_founding_circle' => false,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'numbered-series')->first();

    expect($product)->not->toBeNull()
        ->and($product->edition_number_prefix)->toBe('MA-')
        ->and($product->edition_number_postfix)->toBe('-A')
        ->and($product->formatEditionLabel(1))->toBe('MA-001-A')
        ->and($product->formatEditionLabel(12))->toBe('MA-012-A');
});

test('staff can create more than five hundred edition pieces', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'name' => 'Large Edition',
            'slug' => 'large-edition',
            'type' => ProductType::LimitedEdition->value,
            'amount' => '99.00',
            'edition_total' => 1002,
            'archive_edition_numbers' => [1, 1002],
            'is_published' => true,
            'grants_founding_circle' => false,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'large-edition')->first();

    expect($product)->not->toBeNull()
        ->and($product->edition_total)->toBe(1002)
        ->and(EditionPiece::query()->where('product_id', $product->id)->count())->toBe(1002)
        ->and($product->formatEditionLabel(1002))->toBe('1002');
});

test('staff can view a product details page', function () {
    $product = Product::founding();

    $this->actingAs($this->admin)
        ->get(route('admin.products.show', ['product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/show')
            ->where('product.id', $product->id)
            ->where('product.slug', $product->slug)
            ->has('product.edition_number_prefix')
            ->has('product.edition_number_postfix')
            ->has('product.archive_edition_numbers')
            ->has('product.primary_image')
            ->has('product.gallery_images')
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
