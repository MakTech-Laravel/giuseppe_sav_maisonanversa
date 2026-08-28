<?php

use App\Enums\ProductSectionKey;
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
            ->where('filters.search', '')
            ->where('filters.type', '')
            ->where('filters.status', '')
            ->where('filters.founding_circle', '')
            ->where('filters.per_page', 15)
            ->has('perPageOptions', 5)
        );
});

test('staff can filter the product catalog by search type status and founding circle', function () {
    Product::factory()->create([
        'name' => 'Studio Cloth',
        'slug' => 'studio-cloth',
        'type' => ProductType::Simple,
        'is_published' => false,
        'grants_founding_circle' => false,
    ]);

    Product::factory()->create([
        'name' => 'Circle Bundle',
        'slug' => 'circle-bundle',
        'type' => ProductType::Simple,
        'is_published' => true,
        'grants_founding_circle' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['search' => 'Circle']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/index')
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'circle-bundle')
            ->where('filters.search', 'Circle')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', [
            'type' => ProductType::LimitedEdition->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.type', ProductType::LimitedEdition->value)
            ->where('filters.type', ProductType::LimitedEdition->value)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'studio-cloth')
            ->where('filters.status', 'draft')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['founding_circle' => 'yes']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 2)
            ->where('filters.founding_circle', 'yes')
            ->where('products.data', fn ($rows) => collect($rows)->pluck('slug')->sort()->values()->all() === [
                'circle-bundle',
                'heritage-no-001',
            ])
        );

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', [
            'type' => ProductType::Simple->value,
            'founding_circle' => 'yes',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'circle-bundle')
        );
});

test('staff can change catalog page size within the whitelist', function () {
    Product::factory()->count(20)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 10)
            ->where('filters.per_page', 10)
            ->where('products.per_page', 10)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.products.index', ['per_page' => 999]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 15)
            ->where('products.per_page', 15)
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
        ->and(EditionPiece::query()
            ->where('product_id', $product->id)
            ->where('edition_number', $product->formatEditionLabel(1))
            ->first()?->status->value)
        ->toBe('archive')
        ->and(EditionPiece::query()
            ->where('product_id', $product->id)
            ->where('edition_number', $product->formatEditionLabel(3))
            ->first()?->status->value)
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
        ->and(EditionPiece::query()
            ->where('product_id', $product->id)
            ->where('edition_number', $product->formatEditionLabel(5))
            ->first()?->status->value)
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
            ->where('pieces.data.0.sku', $product->formatEditionSku(1))
            ->where('pieces.data.0.label', $product->formatEditionLabel(1))
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
            ->where('product.primary_image.url', '/images/product/heritage-001-front.png')
            ->has('product.gallery_images', 3)
            ->where('product.gallery_images.0.url', '/images/product/heritage-001-detail-gravure.png')
            ->has('product.sections', 9)
            ->has('product.faqs', 4)
            ->has('sectionCatalogue', 9)
        );
});

test('staff can save product sections independently from the edit form', function () {
    $product = Product::factory()->create([
        'name' => 'Section Test Product',
        'slug' => 'section-test-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.products.sections.update', ['product' => $product->id]), [
            'sections' => [
                [
                    'key' => 'specs',
                    'eyebrow' => '',
                    'heading' => 'Specificaties',
                    'subheading' => '',
                    'intro' => '',
                    'image_key' => '',
                    'is_visible' => true,
                    'include_house_card' => true,
                    'sort_order' => 0,
                    'items' => [
                        ['number_label' => '', 'icon' => '', 'title' => 'Gewicht', 'body' => '340 gram'],
                    ],
                ],
                [
                    'key' => 'craft',
                    'eyebrow' => '',
                    'heading' => 'Vakmanschap',
                    'subheading' => '',
                    'intro' => 'Handgemaakt in Antwerpen.',
                    'image_key' => '',
                    'is_visible' => false,
                    'include_house_card' => true,
                    'sort_order' => 1,
                    'items' => [],
                ],
            ],
        ])
        ->assertRedirect();

    $product->refresh()->loadMissing('sections.items');

    $specs = $product->sections->firstWhere('key', 'specs');
    $craft = $product->sections->firstWhere('key', 'craft');

    expect($specs)->not->toBeNull()
        ->and($specs->heading)->toBe('Specificaties')
        ->and($specs->items)->toHaveCount(1)
        ->and($specs->items->first()->title)->toBe('Gewicht')
        ->and($craft)->not->toBeNull()
        ->and($craft->is_visible)->toBeFalse();
});

test('section intro text is limited to 120 characters', function () {
    $product = Product::factory()->create([
        'name' => 'Intro Limit Product',
        'slug' => 'intro-limit-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.products.edit', ['product' => $product->id]))
        ->post(route('admin.products.sections.update', ['product' => $product->id]), [
            'sections' => [
                [
                    'key' => 'craft',
                    'eyebrow' => '',
                    'heading' => '',
                    'subheading' => '',
                    'intro' => str_repeat('a', 121),
                    'image_key' => '',
                    'is_visible' => true,
                    'include_house_card' => true,
                    'sort_order' => 0,
                    'items' => [],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('sections.0.intro');
});

test('staff can save product faqs independently from the edit form', function () {
    $product = Product::factory()->create([
        'name' => 'Faq Test Product',
        'slug' => 'faq-test-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.products.faqs.update', ['product' => $product->id]), [
            'faqs' => [
                [
                    'question' => 'Kan ik ruilen?',
                    'answer' => 'Ja, binnen 30 dagen.',
                    'is_published' => true,
                ],
                [
                    'question' => 'Nog een concept vraag?',
                    'answer' => 'Nog niet gepubliceerd.',
                    'is_published' => false,
                ],
            ],
        ])
        ->assertRedirect();

    $product->refresh()->loadMissing('faqs');

    expect($product->faqs)->toHaveCount(2)
        ->and($product->faqs->firstWhere('question', 'Kan ik ruilen?')?->is_published)->toBeTrue()
        ->and($product->faqs->firstWhere('question', 'Nog een concept vraag?')?->is_published)->toBeFalse()
        ->and($product->publishedFaqShare())->toHaveCount(1);
});

test('staff can save product media independently from the edit form', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'name' => 'Media Test Product',
        'slug' => 'media-test-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
        'gallery' => [],
    ]);

    $galleryImage = UploadedFile::fake()->image('gallery.jpg', 800, 1000);

    $this->actingAs($this->admin)
        ->post(route('admin.products.media.update', ['product' => $product->id]), [
            'gallery_images' => [$galleryImage],
            'gallery_keep' => [],
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->gallery)->toHaveCount(1);
    Storage::disk('public')->assertExists($product->gallery[0]);
});

test('product display media urls resolve imagery asset keys for admin previews', function () {
    expect(Product::resolveMediaUrl('heritage-001-front'))
        ->toBe('heritage-001-front')
        ->and(Product::resolveDisplayMediaUrl('heritage-001-front'))
        ->toBe('/images/product/heritage-001-front.png');
});

test('viewers cannot create products', function () {
    // The temporary AdminTypePermissionBypass grants every staff account full
    // access while granular permission UI is hidden; disable it here so this
    // test exercises real permission enforcement for the viewer role.
    config(['maison.admin_type_grants_all_permissions' => false]);

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

test('staff can save lucide icon keys on guarantee section items', function () {
    $product = Product::factory()->create([
        'name' => 'Icon Test Product',
        'slug' => 'icon-test-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.products.sections.update', ['product' => $product->id]), [
            'sections' => [
                [
                    'key' => 'guarantees',
                    'eyebrow' => '',
                    'heading' => '',
                    'subheading' => '',
                    'intro' => '',
                    'image_key' => '',
                    'is_visible' => true,
                    'include_house_card' => true,
                    'sort_order' => 0,
                    'items' => [
                        [
                            'number_label' => '',
                            'icon' => 'shield-check',
                            'title' => 'Levenslange garantie',
                            'body' => '',
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $product->refresh()->loadMissing('sections.items');
    $guarantees = $product->sections->firstWhere('key', 'guarantees');

    expect($guarantees)->not->toBeNull()
        ->and($guarantees->items)->toHaveCount(1)
        ->and($guarantees->items->first()->icon)->toBe('shield-check')
        ->and($guarantees->items->first()->title)->toBe('Levenslange garantie');
});

test('staff can upload a craft section image into image_path', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'name' => 'Craft Image Product',
        'slug' => 'craft-image-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $image = UploadedFile::fake()->image('atelier.jpg', 1200, 800);

    $this->actingAs($this->admin)
        ->post(route('admin.products.sections.update', ['product' => $product->id]), [
            'sections' => [
                [
                    'key' => 'craft',
                    'eyebrow' => 'Vakmanschap',
                    'heading' => 'Materieel',
                    'subheading' => '',
                    'intro' => 'Handwerk.',
                    'image_key' => '',
                    'remove_image' => false,
                    'is_visible' => true,
                    'include_house_card' => true,
                    'sort_order' => 0,
                    'items' => [],
                    'image' => $image,
                ],
            ],
        ])
        ->assertRedirect();

    $product->refresh()->loadMissing('sections');
    $craft = $product->sections->firstWhere('key', 'craft');

    expect($craft)->not->toBeNull()
        ->and($craft->image_path)->not->toBeNull()
        ->and($craft->resolvedImage())->not->toBeNull();

    Storage::disk('public')->assertExists($craft->image_path);
});

test('craft section ignores invalid legacy image_key values', function () {
    $product = Product::factory()->create([
        'name' => 'Invalid Craft Key Product',
        'slug' => 'invalid-craft-key-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $section = $product->sections()->create([
        'key' => 'craft',
        'eyebrow' => 'Eyebrow',
        'heading' => 'Heading copy pasted by mistake',
        'image_key' => 'Heading copy pasted by mistake',
        'is_visible' => true,
        'include_house_card' => true,
        'sort_order' => 0,
    ]);

    expect($section->resolvedImage())->toBeNull();
});

test('craft section upload clears invalid legacy image_key values', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'name' => 'Craft Upload Clears Key Product',
        'slug' => 'craft-upload-clears-key-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $product->sections()->create([
        'key' => 'craft',
        'eyebrow' => 'Vakmanschap',
        'heading' => 'Materieel',
        'image_key' => 'Heading copy pasted by mistake',
        'is_visible' => true,
        'include_house_card' => true,
        'sort_order' => 4,
    ]);

    $image = UploadedFile::fake()->image('craft-cover.jpg', 1200, 800);

    $sections = collect(ProductSectionKey::catalogue())
        ->map(fn (array $entry): array => [
            'key' => $entry['key'],
            'eyebrow' => '',
            'heading' => '',
            'subheading' => '',
            'intro' => '',
            'image_key' => '',
            'remove_image' => false,
            'is_visible' => true,
            'include_house_card' => true,
            'sort_order' => $entry['sort_order'],
            'items' => [],
        ])
        ->all();

    $sections[4]['key'] = 'craft';
    $sections[4]['eyebrow'] = 'Vakmanschap';
    $sections[4]['image'] = $image;

    $this->actingAs($this->admin)
        ->post(route('admin.products.sections.update', ['product' => $product->id]), [
            'sections' => $sections,
        ])
        ->assertRedirect();

    $craft = $product->refresh()->sections->firstWhere('key', 'craft');

    expect($craft)->not->toBeNull()
        ->and($craft->image_path)->not->toBeNull()
        ->and($craft->image_key)->toBeNull()
        ->and($craft->resolvedImage())->not->toBeNull();

    Storage::disk('public')->assertExists($craft->image_path);
});

test('staff can manually store product faq translations', function () {
    $product = Product::factory()->create([
        'name' => 'Faq Translation Product',
        'slug' => 'faq-translation-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $faq = $product->faqs()->create([
        'question' => 'Nederlandse vraag?',
        'answer' => 'Nederlands antwoord.',
        'is_published' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.faqs.translations.update', [
            'locale' => 'nl',
            'product' => $product->id,
            'faq' => $faq->id,
        ]), [
            'nl' => [
                'question' => 'Nederlandse vraag?',
                'answer' => 'Nederlands antwoord.',
            ],
            'en' => [
                'question' => 'English question?',
                'answer' => 'English answer.',
            ],
            'fr' => [
                'question' => 'Question française ?',
                'answer' => 'Réponse française.',
            ],
        ])
        ->assertRedirect(route('admin.products.show', [
            'locale' => 'nl',
            'product' => $product->id,
        ]));

    $faq->refresh()->loadMissing('translations');

    expect($faq->translated('question', 'en'))->toBe('English question?')
        ->and($faq->translated('answer', 'fr'))->toBe('Réponse française.');
});

test('staff can manually store product section translations', function () {
    $product = Product::factory()->create([
        'name' => 'Section Translation Product',
        'slug' => 'section-translation-product',
        'type' => ProductType::Simple->value,
        'amount' => '49.00',
        'is_published' => true,
    ]);

    $section = $product->sections()->create([
        'key' => 'service',
        'eyebrow' => 'Service eyebrow',
        'heading' => 'Service titel',
        'subheading' => 'Tweede regel',
        'intro' => 'Intro tekst',
        'is_visible' => true,
        'include_house_card' => true,
        'sort_order' => 0,
    ]);

    $item = $section->items()->create([
        'number_label' => '',
        'icon' => 'truck',
        'title' => 'Verzending',
        'body' => 'Wereldwijd',
        'sort_order' => 0,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.sections.translations.update', [
            'locale' => 'nl',
            'product' => $product->id,
        ]), [
            'nl' => [
                'sections' => [
                    (string) $section->id => [
                        'eyebrow' => 'Service eyebrow',
                        'heading' => 'Service titel',
                        'subheading' => 'Tweede regel',
                        'intro' => 'Intro tekst',
                        'items' => [
                            (string) $item->id => [
                                'title' => 'Verzending',
                                'body' => 'Wereldwijd',
                            ],
                        ],
                    ],
                ],
            ],
            'en' => [
                'sections' => [
                    (string) $section->id => [
                        'eyebrow' => 'Service eyebrow EN',
                        'heading' => 'Service title',
                        'subheading' => 'Second line',
                        'intro' => 'Intro text',
                        'items' => [
                            (string) $item->id => [
                                'title' => 'Shipping',
                                'body' => 'Worldwide',
                            ],
                        ],
                    ],
                ],
            ],
            'fr' => [
                'sections' => [
                    (string) $section->id => [
                        'eyebrow' => 'Service eyebrow FR',
                        'heading' => 'Titre service',
                        'subheading' => 'Deuxième ligne',
                        'intro' => 'Texte intro',
                        'items' => [
                            (string) $item->id => [
                                'title' => 'Livraison',
                                'body' => 'Mondial',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('admin.products.show', [
            'locale' => 'nl',
            'product' => $product->id,
        ]));

    $section->refresh()->loadMissing('translations', 'items.translations');
    $item->refresh()->loadMissing('translations');

    expect($section->translated('heading', 'en'))->toBe('Service title')
        ->and($item->translated('title', 'fr'))->toBe('Livraison')
        ->and($item->translated('body', 'en'))->toBe('Worldwide');
});
