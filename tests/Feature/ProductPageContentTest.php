<?php

use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;

test('product page uses dynamic content and faq props', function () {
    Artisan::call('app:import-static-content-command');

    $this->get(localized('maison.products.show', ['product' => Product::FOUNDING_SLUG]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.slug', Product::FOUNDING_SLUG)
            ->where('product.name', 'Heritage No.001 — Founding Edition')
            ->where('product.eyebrow', 'Maison Anversa · Founding Edition')
            ->where('product.hero_eyebrow', 'Founding Edition · 100 Stuks Wereldwijd')
            ->where('product.description', 'Heritage No.001 is niet zomaar een padelracket. Het is het eerste object van een huis dat wordt gebouwd voor de lange termijn. Elk van de 100 stuks is individueel genummerd en wordt vergezeld van een volledige Heritage ervaring.')
            ->has('product.gallery', 4)
            // Sections are ordered by sort_order (ProductSectionKey::defaultSortOrder()):
            // specs=0, includes=1, guarantees=2, unboxing=3, craft=4, trust=5, service=6, faq=7, related=8.
            ->has('product.sections', 9)
            ->where('product.sections.0.key', 'specs')
            ->has('product.sections.0.items', 7)
            ->where('product.sections.3.key', 'unboxing')
            ->has('product.sections.3.items', 6)
            ->where('product.sections.4.key', 'craft')
            ->has('product.sections.4.items', 4)
            ->where('product.sections.5.key', 'trust')
            ->has('product.sections.5.items', 4)
            ->has('product.faqs')
            ->has('related'));
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
