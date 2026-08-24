<?php

use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;

test('product page uses dynamic content and faq props', function () {
    Artisan::call('app:import-static-content-command');

    $this->get(localized('maison.product'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.slug', Product::FOUNDING_SLUG)
            ->where('product.name', 'Heritage No.001 — Founding Edition')
            ->where('product.eyebrow', 'Maison Anversa · Founding Edition')
            ->where('product.hero_eyebrow', 'Founding Edition · 100 Stuks Wereldwijd')
            ->where('product.description', 'Heritage No.001 is niet zomaar een padelracket. Het is het eerste object van een huis dat wordt gebouwd voor de lange termijn. Elk van de 100 stuks is individueel genummerd en wordt vergezeld van een volledige Heritage ervaring.')
            ->has('product.gallery', 4)
            ->has('product.specs', 7)
            ->has('product.materials', 4)
            ->has('product.unboxing_steps', 6)
            ->has('product.trust_badges', 4)
            ->has('related')
            ->has('faqs'));
});

test('staff can update product storefront copy fields', function () {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    Artisan::call('app:import-static-content-command');

    $product = Product::founding();

    $this->actingAs($admin)
        ->put(route('admin.products.update', [
            'locale' => 'nl',
            'product' => $product->id,
        ]), [
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type->value,
            'amount' => (string) $product->amount,
            'edition_total' => $product->edition_total,
            'edition_number_prefix' => $product->edition_number_prefix,
            'edition_number_postfix' => $product->edition_number_postfix,
            'archive_edition_numbers' => $product->archiveEditionNumberList(),
            'is_published' => true,
            'grants_founding_circle' => true,
            'expected_delivery_label' => 'Q2 2027 — UPDATED',
            'eyebrow' => 'Maison Anversa · Updated Label',
            'hero_eyebrow' => 'Updated Hero Eyebrow',
            'hero_subtitle' => 'Updated hero subtitle copy.',
            'description' => 'Updated product description for the storefront.',
            'gallery_keep' => [],
            'remove_primary_image' => false,
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->eyebrow)->toBe('Maison Anversa · Updated Label')
        ->and($product->hero_eyebrow)->toBe('Updated Hero Eyebrow')
        ->and($product->hero_subtitle)->toBe('Updated hero subtitle copy.')
        ->and($product->description)->toBe('Updated product description for the storefront.')
        ->and($product->expected_delivery_label)->toBe('Q2 2027 — UPDATED');
});
