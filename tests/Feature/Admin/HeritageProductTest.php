<?php

use App\Contracts\StripeCatalogGateway;
use App\Enums\EditionPieceStatus;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakeStripeCatalogGateway;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can update the founding product name and amount', function () {
    $gateway = new FakeStripeCatalogGateway;
    app()->instance(StripeCatalogGateway::class, $gateway);
    config(['cashier.secret' => 'sk_test_fake']);

    $product = Product::founding();

    $this->actingAs($this->admin)
        ->from(route('admin.heritage.index'))
        ->patch(route('admin.heritage.update', ['product' => $product->id]), [
            'name' => 'Heritage No.001 — Atelier',
            'amount' => '99.99',
            'edition_number_prefix' => 'MA-',
            'edition_number_postfix' => '',
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Heritage No.001 — Atelier')
        ->and($product->amount)->toBe('99.99')
        ->and($product->edition_number_prefix)->toBe('MA-')
        ->and($product->formatEditionSku(1))->toBe('MA-001')
        ->and($product->stripe_price_id)->toBe('price_fake_1')
        ->and($gateway->prices['price_fake_1']['amount'])->toBe(9999);
});

test('viewers cannot update the heritage product', function () {
    $viewer = User::factory()->admin()->create();
    $viewer->assignRole(RoleEnum::VIEWER->value);
    $viewer->syncTypeFromRoles();

    $product = Product::founding();

    $this->actingAs($viewer)
        ->patch(route('admin.heritage.update', ['product' => $product->id]), [
            'name' => 'Blocked',
            'amount' => '100.00',
        ])
        ->assertForbidden();
});

test('staff can filter heritage inventory by range search and status', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index', [
            'number_from' => 10,
            'number_to' => 12,
            'status' => EditionPieceStatus::Available->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->has('pieces.data', 3)
            ->where('pieces.total', 3)
            ->where('pieces.per_page', 75)
            ->where('filters.number_from', '10')
            ->where('filters.number_to', '12')
            ->where('filters.status', 'available')
            ->where('filters.per_page', 75)
            ->where('pieces.data.0.label', '010')
            ->where('pieces.data.2.label', '012')
        );
});

test('staff can set heritage inventory per page size', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index', ['per_page' => 25]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->has('pieces.data', 25)
            ->where('pieces.per_page', 25)
            ->where('filters.per_page', 25)
            ->where('perPageOptions', [25, 50, 75, 100, 150, 200, 300])
        );

    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index', ['per_page' => 999]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pieces.per_page', 75)
            ->where('filters.per_page', 75)
        );
});

test('staff can search heritage inventory by sku digits and notes', function () {
    $product = Product::founding();

    EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', 1)
        ->update(['notes' => 'Maison Anversa Archive — not for sale']);

    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index', ['search' => 'Archive']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->has('pieces.data', 1)
            ->where('pieces.data.0.label', '001')
            ->where('filters.search', 'Archive')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index', ['search' => 'HE-042']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->has('pieces.data', 1)
            ->where('pieces.data.0.label', '042')
        );
});
