<?php

use App\Enums\ProductType;
use App\Enums\RoleEnum;
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

test('staff can view a product detail page with translation props', function () {
    $product = Product::factory()->create([
        'name' => 'Detail Product',
        'eyebrow' => 'Eyebrow copy',
        'hero_eyebrow' => 'Hero eyebrow copy',
        'hero_subtitle' => 'Hero subtitle copy',
        'description' => 'Description copy',
        'expected_delivery_label' => 'Q1 2027',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.show', ['locale' => 'nl', 'product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/show')
            ->has('auth.user')
            ->where('product.id', $product->id)
            ->where('product.name', 'Detail Product')
            ->has('locales', 3)
            ->has('translations.nl')
            ->has('translations.en')
            ->has('translations.fr')
            ->has('translationStatus.nl')
            ->has('translationStatus.en')
            ->has('translationStatus.fr')
            ->has('faqTranslations')
            ->has('faqTranslationStatus')
            ->has('sectionTranslations.nl')
            ->has('sectionTranslationStatus.nl')
        );
});

test('product detail page shows translated content for the active locale', function () {
    $product = Product::factory()->create([
        'name' => 'English source name',
        'eyebrow' => 'English eyebrow',
        'hero_eyebrow' => 'English hero eyebrow',
        'hero_subtitle' => 'English hero subtitle',
        'description' => 'English description',
        'expected_delivery_label' => 'Q1 2027',
    ]);

    foreach ([
        'name' => 'Nom source en français',
        'eyebrow' => 'Accroche française',
        'hero_eyebrow' => 'Accroche héros française',
        'hero_subtitle' => 'Sous-titre héros français',
        'description' => 'Description française',
        'expected_delivery_label' => 'T1 2027',
    ] as $column => $value) {
        $product->translations()->updateOrCreate(
            ['locale' => 'fr', 'column' => $column],
            ['value' => $value, 'source_hash' => $product->translationSourceHash($column)],
        );
    }

    $this->actingAs($this->admin)
        ->get(route('admin.products.show', ['locale' => 'fr', 'product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/show')
            ->where('product.name', 'Nom source en français')
            ->where('product.eyebrow', 'Accroche française')
            ->where('product.hero_eyebrow', 'Accroche héros française')
            ->where('product.hero_subtitle', 'Sous-titre héros français')
            ->where('product.description', 'Description française')
            ->where('product.expected_delivery_label', 'T1 2027')
        );
});

test('creating a product stores deepl translations for all six fields and all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.products.store', ['locale' => 'nl']), [
            'name' => 'DeepL Product Naam',
            'slug' => 'deepl-product-naam',
            'type' => ProductType::Simple->value,
            'amount' => '59.00',
            'stock_quantity' => 5,
            'is_published' => true,
            'grants_founding_circle' => false,
            'eyebrow' => 'DeepL Eyebrow',
            'hero_eyebrow' => 'DeepL Hero Eyebrow',
            'hero_subtitle' => 'DeepL Hero Subtitle',
            'description' => 'DeepL Description',
            'expected_delivery_label' => 'DeepL Levering',
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'deepl-product-naam')->firstOrFail();

    expect($product->translations()->count())->toBe(18)
        ->and($product->translated('name', 'nl'))->toBe('NL DeepL Product Naam')
        ->and($product->translated('name', 'en'))->toBe('EN DeepL Product Naam')
        ->and($product->translated('description', 'fr'))->toBe('FR DeepL Description');
});

test('creating a product in english auto-detects and translates to all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.products.store', ['locale' => 'nl']), [
            'name' => 'Autumn Collection Piece',
            'slug' => 'autumn-collection-piece',
            'type' => ProductType::Simple->value,
            'amount' => '75.00',
            'stock_quantity' => 3,
            'is_published' => true,
            'grants_founding_circle' => false,
            'description' => 'A limited seasonal piece crafted in-house.',
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'autumn-collection-piece')->firstOrFail();

    expect($product->translated('name', 'nl'))->toBe('NL Autumn Collection Piece')
        ->and($product->translated('name', 'en'))->toBe('EN Autumn Collection Piece')
        ->and($product->translated('name', 'fr'))->toBe('FR Autumn Collection Piece');
});

test('staff can manually update product translations', function () {
    $product = Product::factory()->create([
        'name' => 'Bron naam',
        'eyebrow' => 'Bron eyebrow',
        'hero_eyebrow' => 'Bron hero eyebrow',
        'hero_subtitle' => 'Bron hero subtitle',
        'description' => 'Bron beschrijving',
        'expected_delivery_label' => 'Bron levering',
    ]);

    $localeCopy = fn (string $prefix): array => [
        'name' => "{$prefix} name",
        'eyebrow' => "{$prefix} eyebrow",
        'hero_eyebrow' => "{$prefix} hero eyebrow",
        'hero_subtitle' => "{$prefix} hero subtitle",
        'description' => "{$prefix} description",
        'expected_delivery_label' => "{$prefix} delivery",
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.products.translations.update', ['locale' => 'nl', 'product' => $product->id]), [
            'nl' => $localeCopy('Custom NL'),
            'en' => $localeCopy('Custom EN'),
            'fr' => $localeCopy('Custom FR'),
        ])
        ->assertRedirect(route('admin.products.show', ['locale' => 'nl', 'product' => $product->id]));

    $product->refresh();

    expect($product->name)->toBe('Bron naam')
        ->and($product->translated('name', 'nl'))->toBe('Custom NL name')
        ->and($product->translated('name', 'en'))->toBe('Custom EN name')
        ->and($product->translated('description', 'fr'))->toBe('Custom FR description')
        ->and($product->translated('expected_delivery_label', 'en'))->toBe('Custom EN delivery');
});

test('editing product source requeues deepl for all locales', function () {
    fakeDeepLTranslations();

    $product = Product::factory()->create([
        'name' => 'Original source name',
        'slug' => 'original-source-name',
        'description' => 'Original description',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', ['locale' => 'nl', 'product' => $product->id]), [
            'name' => 'Updated source name',
            'slug' => 'original-source-name',
            'type' => ProductType::Simple->value,
            'amount' => (string) $product->amount,
            'stock_quantity' => $product->stock_quantity,
            'is_published' => true,
            'grants_founding_circle' => false,
            'description' => 'Updated description',
            'gallery_keep' => [],
        ])
        ->assertRedirect(route('admin.products.show', ['locale' => 'nl', 'product' => $product->id]));

    $product->refresh();

    expect($product->name)->toBe('Updated source name')
        ->and($product->translated('name', 'en'))->toBe('EN Updated source name')
        ->and($product->translated('description', 'fr'))->toBe('FR Updated description');
});

test('staff can retranslate a single product locale from source', function () {
    fakeDeepLTranslations();

    $product = Product::factory()->create([
        'name' => 'Single locale source name',
        'description' => 'Single locale source description',
    ]);

    foreach (['name', 'eyebrow', 'hero_eyebrow', 'hero_subtitle', 'description', 'expected_delivery_label'] as $column) {
        $product->translations()->updateOrCreate(
            ['locale' => 'en', 'column' => $column],
            ['value' => "Keep EN {$column}", 'source_hash' => $product->translationSourceHash($column)],
        );
        $product->translations()->updateOrCreate(
            ['locale' => 'fr', 'column' => $column],
            ['value' => "Keep FR {$column}", 'source_hash' => $product->translationSourceHash($column)],
        );
    }

    $this->actingAs($this->admin)
        ->post(route('admin.products.translate', ['locale' => 'nl', 'product' => $product->id]), [
            'target_locale' => 'nl',
        ])
        ->assertRedirect(route('admin.products.show', ['locale' => 'nl', 'product' => $product->id]));

    $product->refresh();

    expect($product->translated('name', 'nl'))->toBe('NL Single locale source name')
        ->and($product->translated('description', 'nl'))->toBe('NL Single locale source description')
        ->and($product->translated('name', 'en'))->toBe('Keep EN name')
        ->and($product->translated('name', 'fr'))->toBe('Keep FR name');
});

test('staff can queue deepl retranslation for all product locales', function () {
    fakeDeepLTranslations();

    $product = Product::factory()->create([
        'name' => 'Hervertaal naam',
        'eyebrow' => 'Hervertaal eyebrow',
        'hero_eyebrow' => 'Hervertaal hero eyebrow',
        'hero_subtitle' => 'Hervertaal hero subtitle',
        'description' => 'Hervertaal beschrijving',
        'expected_delivery_label' => 'Hervertaal levering',
    ]);

    $product->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.products.translate', ['locale' => 'nl', 'product' => $product->id]))
        ->assertRedirect(route('admin.products.show', ['locale' => 'nl', 'product' => $product->id]));

    expect($product->fresh()->translations()->count())->toBe(18);
});
