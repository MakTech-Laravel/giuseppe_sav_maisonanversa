<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductSectionKey;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\TranslateProductFaqRequest;
use App\Http\Requests\Admin\TranslateProductRequest;
use App\Http\Requests\Admin\TranslateProductSectionsRequest;
use App\Http\Requests\Admin\UpdateProductFaqsRequest;
use App\Http\Requests\Admin\UpdateProductFaqTranslationsRequest;
use App\Http\Requests\Admin\UpdateProductMediaRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Admin\UpdateProductSectionsRequest;
use App\Http\Requests\Admin\UpdateProductSectionTranslationsRequest;
use App\Http\Requests\Admin\UpdateProductTranslationsRequest;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductSection;
use App\Models\ProductSectionItem;
use App\Services\Edition\EditionInventory;
use App\Support\Imagery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /** @var list<int> */
    public const INVENTORY_PER_PAGE_OPTIONS = [25, 50, 75, 100, 150, 200, 300];

    public const INVENTORY_PER_PAGE_DEFAULT = 75;

    /** @var list<int> */
    public const CATALOG_PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const CATALOG_PER_PAGE_DEFAULT = 15;

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = [
        'name',
        'eyebrow',
        'hero_eyebrow',
        'hero_subtitle',
        'description',
        'expected_delivery_label',
    ];

    private const FAQ_TRANSLATION_COLUMNS = [
        'question',
        'answer',
    ];

    private const SECTION_TRANSLATION_COLUMNS = [
        'eyebrow',
        'heading',
        'subheading',
        'intro',
    ];

    private const SECTION_ITEM_TRANSLATION_COLUMNS = [
        'title',
        'body',
    ];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

    public function index(Request $request, string $locale): Response
    {
        $filters = $this->catalogFilters($request);

        $products = Product::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] !== '', function (Builder $query) use ($filters): void {
                $query->where('type', $filters['type']);
            })
            ->when($filters['status'] === 'published', function (Builder $query): void {
                $query->where('is_published', true);
            })
            ->when($filters['status'] === 'draft', function (Builder $query): void {
                $query->where('is_published', false);
            })
            ->when($filters['founding_circle'] === 'yes', function (Builder $query): void {
                $query->where('grants_founding_circle', true);
            })
            ->when($filters['founding_circle'] === 'no', function (Builder $query): void {
                $query->where('grants_founding_circle', false);
            })
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->translated('name'),
                'slug' => $product->slug,
                'type' => $product->type->value,
                'amount' => (string) $product->amount,
                'is_published' => $product->is_published,
                'grants_founding_circle' => $product->grants_founding_circle,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => [
                'search' => $filters['search'],
                'type' => $filters['type'],
                'status' => $filters['status'],
                'founding_circle' => $filters['founding_circle'],
                'per_page' => $filters['per_page'],
            ],
            'perPageOptions' => self::CATALOG_PER_PAGE_OPTIONS,
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/products/create', [
            'sectionCatalogue' => ProductSectionKey::catalogue(),
        ]);
    }

    public function show(string $locale, Product $product): Response
    {
        $product->loadMissing([
            'translations',
            'sections.items.translations',
            'sections.translations',
            'faqs.translations',
        ]);

        return Inertia::render('admin/products/show', [
            'product' => $this->translatedProduct($product, $locale),
            'locales' => $this->translationLocales(),
            'translations' => $this->translationBundle($product),
            'translationStatus' => $this->translationStatus($product),
            'faqTranslations' => $this->faqTranslationBundles($product),
            'faqTranslationStatus' => $this->faqTranslationStatuses($product),
            'sectionTranslations' => $this->sectionTranslationBundle($product),
            'sectionTranslationStatus' => $this->sectionTranslationStatus($product),
            'sectionCatalogue' => ProductSectionKey::catalogue(),
        ]);
    }

    public function store(StoreProductRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated, $request): Product {
            $payload = $this->payload($validated);
            $payload['gallery'] = $this->syncGallery([], $validated, $request);

            $product = Product::query()->create($payload);

            $this->syncSections($product, $validated['sections'] ?? []);
            $this->syncFaqs($product, $validated['faqs'] ?? []);

            return $product;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product aangemaakt.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function edit(string $locale, Product $product): Response
    {
        $product->loadMissing('sections.items', 'faqs');

        return Inertia::render('admin/products/edit', [
            'product' => $this->formProduct($product),
            'sectionCatalogue' => ProductSectionKey::catalogue(),
        ]);
    }

    public function update(UpdateProductRequest $request, string $locale, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $product): void {
            $payload = $this->payload($validated);

            // Tabbed edit screen saves media separately; leave the gallery
            // untouched when this request carries no media fields at all.
            if ($this->carriesMedia($request, $validated)) {
                $payload['gallery'] = $this->syncGallery($product->gallery ?? [], $validated, $request);
            }

            $product->update($payload);

            if (array_key_exists('sections', $validated)) {
                $this->syncSections($product, $validated['sections'] ?? []);
            }

            if (array_key_exists('faqs', $validated)) {
                $this->syncFaqs($product, $validated['faqs'] ?? []);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product bijgewerkt.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    /**
     * Media tab of the edit screen: gallery only, so nothing else is touched.
     */
    public function updateMedia(UpdateProductMediaRequest $request, string $locale, Product $product): RedirectResponse
    {
        $product->update([
            'gallery' => $this->syncGallery($product->gallery ?? [], $request->validated(), $request),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Afbeeldingen bijgewerkt.')]);

        return back();
    }

    public function updateSections(
        UpdateProductSectionsRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        DB::transaction(fn () => $this->syncSections($product, $request->validated('sections') ?? []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Secties opgeslagen.')]);

        return back();
    }

    public function updateFaqs(
        UpdateProductFaqsRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        DB::transaction(fn () => $this->syncFaqs($product, $request->validated('faqs') ?? []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Veelgestelde vragen opgeslagen.')]);

        return back();
    }

    /**
     * Replaces the product's sections with the submitted set. Sections keep
     * their row (and therefore their translations) when the key already
     * exists; items are rewritten because they carry no stable client id.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function syncSections(Product $product, array $sections): void
    {
        $keptKeys = [];

        foreach (array_values($sections) as $index => $section) {
            $key = ProductSectionKey::from((string) $section['key']);
            $keptKeys[] = $key->value;

            /** @var ProductSection $model */
            $model = $product->sections()->updateOrCreate(
                ['key' => $key->value],
                [
                    'eyebrow' => $section['eyebrow'] ?? null,
                    'heading' => $section['heading'] ?? null,
                    'subheading' => $section['subheading'] ?? null,
                    'intro' => $section['intro'] ?? null,
                    'is_visible' => (bool) ($section['is_visible'] ?? true),
                    'include_house_card' => (bool) ($section['include_house_card'] ?? true),
                    'sort_order' => (int) ($section['sort_order'] ?? $index),
                ],
            );

            $this->syncSectionImage($model, $section, $index);
            $this->syncSectionItems($model, $section['items'] ?? []);
        }

        $product->sections()
            ->whereNotIn('key', $keptKeys)
            ->get()
            ->each(function (ProductSection $section): void {
                $this->deleteStoredMedia($section->image_path);
                $section->delete();
            });
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function syncSectionImage(ProductSection $model, array $section, int $index): void
    {
        $remove = (bool) ($section['remove_image'] ?? false);

        if ($remove) {
            $this->deleteStoredMedia($model->image_path);
            $model->update(['image_path' => null]);

            return;
        }

        $file = $this->sectionImageUpload($section, $index);

        if ($file instanceof UploadedFile) {
            $this->deleteStoredMedia($model->image_path);

            $updates = [
                'image_path' => $file->store('products/sections', 'public'),
            ];

            if (filled($model->image_key) && Imagery::assetPath((string) $model->image_key) === null) {
                $updates['image_key'] = null;
            }

            $model->update($updates);
        }
    }

    /**
     * Resolve a section image upload by index, falling back to a key match so
     * reordered section arrays still bind the file to the correct row.
     *
     * @param  array<string, mixed>  $section
     */
    private function sectionImageUpload(array $section, int $index): ?UploadedFile
    {
        $file = request()->file("sections.{$index}.image");

        if ($file instanceof UploadedFile) {
            return $file;
        }

        $key = (string) ($section['key'] ?? '');

        if ($key === '') {
            return null;
        }

        foreach (array_values(request()->input('sections', [])) as $at => $row) {
            if (! is_array($row) || (string) ($row['key'] ?? '') !== $key) {
                continue;
            }

            $candidate = request()->file("sections.{$at}.image");

            if ($candidate instanceof UploadedFile) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncSectionItems(ProductSection $section, array $items): void
    {
        $existing = $section->items()->get()->values();

        foreach (array_values($items) as $index => $item) {
            $attributes = [
                'number_label' => $item['number_label'] ?? null,
                'icon' => $item['icon'] ?? null,
                'title' => $item['title'] ?? null,
                'body' => $item['body'] ?? null,
                'sort_order' => $index,
            ];

            $current = $existing->get($index);

            if ($current instanceof ProductSectionItem) {
                $current->update($attributes);

                continue;
            }

            $section->items()->create($attributes);
        }

        $existing->slice(count($items))
            ->each(fn (ProductSectionItem $item) => $item->delete());
    }

    /**
     * @param  array<int, array<string, mixed>>  $faqs
     */
    private function syncFaqs(Product $product, array $faqs): void
    {
        $existing = $product->faqs()->get()->values();

        foreach (array_values($faqs) as $index => $faq) {
            $attributes = [
                'question' => (string) $faq['question'],
                'answer' => (string) $faq['answer'],
                'is_published' => (bool) ($faq['is_published'] ?? true),
                'sort_order' => $index,
            ];

            $current = $existing->get($index);

            if ($current instanceof ProductFaq) {
                $current->update($attributes);

                continue;
            }

            $product->faqs()->create($attributes);
        }

        $existing->slice(count($faqs))
            ->each(fn (ProductFaq $faq) => $faq->delete());
    }

    public function updateTranslations(
        UpdateProductTranslationsRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $product->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $product->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function translate(TranslateProductRequest $request, string $locale, Product $product): RedirectResponse
    {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $product->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $product->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $product->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $product->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function updateFaqTranslations(
        UpdateProductFaqTranslationsRequest $request,
        string $locale,
        Product $product,
        ProductFaq $faq,
    ): RedirectResponse {
        abort_unless($faq->product_id === $product->id, 404);

        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::FAQ_TRANSLATION_COLUMNS as $column) {
                $faq->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $faq->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function translateFaq(
        TranslateProductFaqRequest $request,
        string $locale,
        Product $product,
        ProductFaq $faq,
    ): RedirectResponse {
        abort_unless($faq->product_id === $product->id, 404);

        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $faq->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::FAQ_TRANSLATION_COLUMNS)
                ->delete();

            $faq->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $faq->translations()
                ->whereIn('column', self::FAQ_TRANSLATION_COLUMNS)
                ->delete();

            $faq->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function updateSectionTranslations(
        UpdateProductSectionTranslationsRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        $data = $request->validated();
        $product->loadMissing('sections.items');

        foreach ($this->translationLocales() as $targetLocale) {
            $sectionsPayload = $data[$targetLocale]['sections'] ?? [];

            if (! is_array($sectionsPayload)) {
                continue;
            }

            foreach ($product->sections as $section) {
                $sectionData = $sectionsPayload[(string) $section->id] ?? null;

                if (! is_array($sectionData)) {
                    continue;
                }

                foreach (self::SECTION_TRANSLATION_COLUMNS as $column) {
                    if (! array_key_exists($column, $sectionData)) {
                        continue;
                    }

                    $section->translations()->updateOrCreate(
                        [
                            'locale' => $targetLocale,
                            'column' => $column,
                        ],
                        [
                            'value' => (string) ($sectionData[$column] ?? ''),
                            'source_hash' => $section->translationSourceHash($column),
                        ],
                    );
                }

                $itemsPayload = is_array($sectionData['items'] ?? null)
                    ? $sectionData['items']
                    : [];

                foreach ($section->items as $item) {
                    $itemData = $itemsPayload[(string) $item->id] ?? null;

                    if (! is_array($itemData)) {
                        continue;
                    }

                    foreach (self::SECTION_ITEM_TRANSLATION_COLUMNS as $column) {
                        if (! array_key_exists($column, $itemData)) {
                            continue;
                        }

                        $item->translations()->updateOrCreate(
                            [
                                'locale' => $targetLocale,
                                'column' => $column,
                            ],
                            [
                                'value' => (string) ($itemData[$column] ?? ''),
                                'source_hash' => $item->translationSourceHash($column),
                            ],
                        );
                    }
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function translateSections(
        TranslateProductSectionsRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        $targetLocale = $request->validated('target_locale');
        $product->loadMissing('sections.items');

        foreach ($product->sections as $section) {
            if (filled($targetLocale)) {
                $section->translations()
                    ->where('locale', $targetLocale)
                    ->whereIn('column', self::SECTION_TRANSLATION_COLUMNS)
                    ->delete();

                $section->dispatchDeepLTranslation([$targetLocale]);
            } else {
                $section->translations()
                    ->whereIn('column', self::SECTION_TRANSLATION_COLUMNS)
                    ->delete();

                $section->dispatchDeepLTranslation();
            }

            foreach ($section->items as $item) {
                if (filled($targetLocale)) {
                    $item->translations()
                        ->where('locale', $targetLocale)
                        ->whereIn('column', self::SECTION_ITEM_TRANSLATION_COLUMNS)
                        ->delete();

                    $item->dispatchDeepLTranslation([$targetLocale]);
                } else {
                    $item->translations()
                        ->whereIn('column', self::SECTION_ITEM_TRANSLATION_COLUMNS)
                        ->delete();

                    $item->dispatchDeepLTranslation();
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.products.show', [
            'locale' => $locale,
            'product' => $product->id,
        ]);
    }

    public function destroy(string $locale, Product $product): RedirectResponse
    {
        if ($product->orders()->exists()) {
            return back()->withErrors([
                'product' => __('Dit product heeft bestellingen en kan niet worden verwijderd. Depubliceer het in plaats daarvan.'),
            ]);
        }

        foreach ($product->gallery ?? [] as $path) {
            $this->deleteStoredMedia(is_string($path) ? $path : null);
        }

        $product->editionPieces()->delete();
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product verwijderd.')]);

        return redirect()->route('admin.products.index');
    }

    public function inventory(Request $request, string $locale, Product $product, EditionInventory $inventory): Response
    {
        abort_unless($product->isLimitedEdition(), 404);

        return $this->renderInventory($request, $product, $inventory);
    }

    public function renderInventory(Request $request, Product $product, EditionInventory $inventory): Response
    {
        $snapshot = $inventory->snapshot($product);
        $filters = $this->inventoryFilters($request);

        $pieces = EditionPiece::query()
            ->where('product_id', $product->id)
            ->with(['order:id,name,email,user_id,created_at'])
            ->tap(fn ($query) => $this->applyInventoryFilters($query, $product, $filters))
            ->orderBy('edition_number')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (EditionPiece $piece) => [
                'sku' => $product->formatEditionSkuForLabel($piece->edition_number),
                'label' => $piece->edition_number,
                'status' => $piece->status->value,
                'status_key' => $piece->status->value,
                'notes' => $piece->notes ?? '',
                'order_id' => $piece->order_id,
                'order_reference' => $piece->order?->reference(),
                'purchaser_user_id' => $piece->order?->user_id,
                'purchaser_name' => $piece->order?->name,
                'purchaser_email' => $piece->order?->email,
            ]);

        return Inertia::render('admin/heritage/index', [
            'product' => $this->formProduct($product),
            'catalog' => Product::query()
                ->where('type', ProductType::LimitedEdition)
                ->orderBy('name')
                ->get()
                ->map(fn (Product $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                ]),
            'inventory' => [
                'product_name' => $product->name,
                'total' => $snapshot['total'],
                'reserved' => $snapshot['reserved'],
                'available' => $snapshot['available'],
            ],
            'pieces' => $pieces,
            'filters' => [
                'search' => $filters['search'],
                'number_from' => $filters['number_from'] !== null ? (string) $filters['number_from'] : '',
                'number_to' => $filters['number_to'] !== null ? (string) $filters['number_to'] : '',
                'status' => $filters['status']?->value ?? '',
                'per_page' => $filters['per_page'],
            ],
            'perPageOptions' => self::INVENTORY_PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * @return array{search: string, type: string, status: string, founding_circle: string, per_page: int}
     */
    private function catalogFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));
        $foundingCircle = trim((string) $request->query('founding_circle', ''));

        if (ProductType::tryFrom($type) === null) {
            $type = '';
        }

        if (! in_array($status, ['published', 'draft'], true)) {
            $status = '';
        }

        if (! in_array($foundingCircle, ['yes', 'no'], true)) {
            $foundingCircle = '';
        }

        return [
            'search' => $search,
            'type' => $type,
            'status' => $status,
            'founding_circle' => $foundingCircle,
            'per_page' => $this->catalogPerPage($request),
        ];
    }

    private function catalogPerPage(Request $request): int
    {
        $perPage = $this->nullablePositiveInt($request->query('per_page'));

        if ($perPage !== null && in_array($perPage, self::CATALOG_PER_PAGE_OPTIONS, true)) {
            return $perPage;
        }

        return self::CATALOG_PER_PAGE_DEFAULT;
    }

    /**
     * @return array{search: string, number_from: int|null, number_to: int|null, status: EditionPieceStatus|null, per_page: int}
     */
    private function inventoryFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $statusValue = trim((string) $request->query('status', ''));
        $numberFrom = $this->nullablePositiveInt($request->query('number_from'));
        $numberTo = $this->nullablePositiveInt($request->query('number_to'));

        if ($numberFrom !== null && $numberTo !== null && $numberFrom > $numberTo) {
            [$numberFrom, $numberTo] = [$numberTo, $numberFrom];
        }

        return [
            'search' => $search,
            'number_from' => $numberFrom,
            'number_to' => $numberTo,
            'status' => EditionPieceStatus::tryFrom($statusValue),
            'per_page' => $this->inventoryPerPage($request),
        ];
    }

    private function inventoryPerPage(Request $request): int
    {
        $perPage = $this->nullablePositiveInt($request->query('per_page'));

        if ($perPage !== null && in_array($perPage, self::INVENTORY_PER_PAGE_OPTIONS, true)) {
            return $perPage;
        }

        return self::INVENTORY_PER_PAGE_DEFAULT;
    }

    /**
     * @param  Builder<EditionPiece>  $query
     * @param  array{search: string, number_from: int|null, number_to: int|null, status: EditionPieceStatus|null, per_page: int}  $filters
     */
    private function applyInventoryFilters($query, Product $product, array $filters): void
    {
        $query
            ->when(
                $filters['number_from'] !== null,
                fn ($inner) => $inner->where(
                    'edition_number',
                    '>=',
                    $product->formatEditionLabel($filters['number_from']),
                ),
            )
            ->when(
                $filters['number_to'] !== null,
                fn ($inner) => $inner->where(
                    'edition_number',
                    '<=',
                    $product->formatEditionLabel($filters['number_to']),
                ),
            )
            ->when($filters['status'] !== null, fn ($inner) => $inner->where(
                'status',
                $filters['status']->value,
            ))
            ->when($filters['search'] !== '', function ($inner) use ($filters, $product): void {
                $search = strtoupper(trim($filters['search']));

                $skuLabels = EditionPiece::query()
                    ->where('product_id', $product->id)
                    ->pluck('edition_number')
                    ->filter(function (string $label) use ($product, $search): bool {
                        return str_starts_with(
                            strtoupper($product->formatEditionSkuForLabel($label)),
                            $search,
                        );
                    })
                    ->values()
                    ->all();

                $inner->where(function ($group) use ($search, $skuLabels): void {
                    $group->where('notes', 'like', "%{$search}%")
                        ->orWhere('edition_number', 'like', $search.'%');

                    if ($skuLabels !== []) {
                        $group->orWhereIn('edition_number', $skuLabels);
                    }

                    $status = EditionPieceStatus::tryFrom(strtolower($search));

                    if ($status !== null) {
                        $group->orWhere('status', $status->value);
                    }
                });
            });
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        $type = ProductType::from($validated['type']);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'type' => $type,
            'status' => ProductStatus::from($validated['status'] ?? ProductStatus::Active->value),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'amount' => $validated['amount'],
            'currency' => 'eur',
            'edition_total' => $type === ProductType::LimitedEdition ? $validated['edition_total'] : null,
            'edition_number_prefix' => $type === ProductType::LimitedEdition
                ? ($validated['edition_number_prefix'] ?? null)
                : null,
            'edition_number_postfix' => $type === ProductType::LimitedEdition
                ? ($validated['edition_number_postfix'] ?? null)
                : null,
            'archive_edition_numbers' => $type === ProductType::LimitedEdition
                ? ($validated['archive_edition_numbers'] ?? [])
                : [],
            'stock_quantity' => $type === ProductType::Simple ? $validated['stock_quantity'] : null,
            'is_published' => $validated['is_published'],
            'grants_founding_circle' => $validated['grants_founding_circle'],
            'expected_delivery_label' => $validated['expected_delivery_label'] ?? null,
            'eyebrow' => $validated['eyebrow'] ?? null,
            'hero_eyebrow' => $validated['hero_eyebrow'] ?? null,
            'hero_subtitle' => $validated['hero_subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'sold_out_behavior' => 'keep_page',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function carriesMedia(Request $request, array $validated): bool
    {
        return $request->hasFile('primary_image')
            || $request->hasFile('gallery_images')
            || $request->has('gallery_keep')
            || ($validated['remove_primary_image'] ?? false) === true;
    }

    /**
     * @param  list<string>  $currentGallery
     * @param  array<string, mixed>  $validated
     * @return list<string>
     */
    private function syncGallery(array $currentGallery, array $validated, Request $request): array
    {
        $primary = $currentGallery[0] ?? null;
        $extras = array_values(array_slice($currentGallery, 1));

        if (($validated['remove_primary_image'] ?? false) === true) {
            $this->deleteStoredMedia($primary);
            $primary = null;
        }

        if ($request->hasFile('primary_image')) {
            $this->deleteStoredMedia($primary);
            $primary = $request->file('primary_image')?->store('products', 'public');
        }

        /** @var list<string>|null $keep */
        $keep = $validated['gallery_keep'] ?? null;

        if (is_array($keep)) {
            $removed = array_values(array_diff($extras, $keep));

            foreach ($removed as $path) {
                $this->deleteStoredMedia($path);
            }

            $extras = array_values(array_filter(
                $extras,
                fn (string $path): bool => in_array($path, $keep, true),
            ));
        }

        if ($request->hasFile('gallery_images')) {
            /** @var list<UploadedFile|null> $files */
            $files = $request->file('gallery_images') ?? [];

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $extras[] = $file->store('products', 'public');
                }
            }
        }

        return array_values(array_filter(
            [$primary, ...$extras],
            fn (mixed $path): bool => is_string($path) && $path !== '',
        ));
    }

    private function deleteStoredMedia(?string $path): void
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formProduct(Product $product): array
    {
        $gallery = $product->gallery ?? [];
        $primaryPath = $gallery[0] ?? null;
        $extraPaths = array_values(array_slice($gallery, 1));

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type->value,
            'status' => ($product->status ?? ProductStatus::Active)->value,
            'sort_order' => $product->sort_order ?? 0,
            'amount' => (string) $product->amount,
            'currency' => $product->currency,
            'edition_total' => $product->edition_total,
            'edition_number_prefix' => $product->edition_number_prefix ?? '',
            'edition_number_postfix' => $product->edition_number_postfix ?? '',
            'archive_edition_numbers' => $product->archiveEditionNumberList(),
            'stock_quantity' => $product->stock_quantity,
            'is_published' => $product->is_published,
            'grants_founding_circle' => $product->grants_founding_circle,
            'expected_delivery_label' => $product->expected_delivery_label,
            'eyebrow' => $product->eyebrow ?? '',
            'hero_eyebrow' => $product->hero_eyebrow ?? '',
            'hero_subtitle' => $product->hero_subtitle ?? '',
            'description' => $product->description ?? '',
            'stripe_price_id' => $product->stripe_price_id,
            'primary_image' => $this->existingMediaFile($primaryPath),
            'gallery_images' => array_values(array_filter(array_map(
                fn (string $path): ?array => $this->existingMediaFile($path),
                $extraPaths,
            ))),
            'sections' => $this->formSections($product),
            'faqs' => $this->formFaqs($product),
        ];
    }

    /**
     * Source-language section rows for the admin form. Content is never
     * localized here: the form edits the Dutch source that DeepL translates.
     *
     * @return list<array<string, mixed>>
     */
    private function formSections(Product $product): array
    {
        $product->loadMissing('sections.items');

        return $product->sections
            ->map(fn (ProductSection $section): array => [
                'id' => $section->id,
                'key' => $section->key->value,
                'eyebrow' => $section->eyebrow ?? '',
                'heading' => $section->heading ?? '',
                'subheading' => $section->subheading ?? '',
                'intro' => $section->intro ?? '',
                'image_key' => $section->image_key ?? '',
                'existing_image' => filled($section->image_path)
                    ? Product::resolveDisplayMediaUrl((string) $section->image_path)
                    : null,
                'is_visible' => (bool) $section->is_visible,
                'include_house_card' => (bool) $section->include_house_card,
                'sort_order' => $section->sort_order ?? 0,
                'items' => $section->items
                    ->map(fn (ProductSectionItem $item): array => [
                        'uid' => 'item-'.$item->id,
                        'number_label' => $item->number_label ?? '',
                        'icon' => $item->icon ?? '',
                        'title' => $item->title ?? '',
                        'body' => $item->body ?? '',
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formFaqs(Product $product): array
    {
        $product->loadMissing('faqs');

        return $product->faqs
            ->map(fn (ProductFaq $faq): array => [
                'uid' => 'faq-'.$faq->id,
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'is_published' => (bool) $faq->is_published,
            ])
            ->values()
            ->all();
    }

    /**
     * Locale-resolved copy for the show page: same shape as formProduct(),
     * with the translatable text columns swapped for the active locale's value.
     *
     * @return array<string, mixed>
     */
    private function translatedProduct(Product $product, string $locale): array
    {
        $data = $this->formProduct($product);

        foreach (self::TRANSLATION_COLUMNS as $column) {
            $data[$column] = $product->translated($column, $locale);
        }

        return $data;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translationBundle(Product $product): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = collect(self::TRANSLATION_COLUMNS)
                ->mapWithKeys(fn (string $column): array => [
                    $column => $product->translated($column, $targetLocale),
                ])
                ->all();
        }

        return $bundle;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function translationStatus(Product $product): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = collect(self::TRANSLATION_COLUMNS)
                ->mapWithKeys(fn (string $column): array => [
                    $column => $product->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === $column,
                    ),
                ])
                ->all();
        }

        return $status;
    }

    /**
     * @return array<int, array<string, array{question: string, answer: string}>>
     */
    private function faqTranslationBundles(Product $product): array
    {
        $bundles = [];

        foreach ($product->faqs as $faq) {
            $faq->loadMissing('translations');
            $bundle = [];

            foreach ($this->translationLocales() as $targetLocale) {
                $bundle[$targetLocale] = [
                    'question' => $faq->translated('question', $targetLocale),
                    'answer' => $faq->translated('answer', $targetLocale),
                ];
            }

            $bundles[$faq->id] = $bundle;
        }

        return $bundles;
    }

    /**
     * @return array<int, array<string, array{question: bool, answer: bool}>>
     */
    private function faqTranslationStatuses(Product $product): array
    {
        $statuses = [];

        foreach ($product->faqs as $faq) {
            $faq->loadMissing('translations');
            $status = [];

            foreach ($this->translationLocales() as $targetLocale) {
                $status[$targetLocale] = [
                    'question' => $faq->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'question',
                    ),
                    'answer' => $faq->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'answer',
                    ),
                ];
            }

            $statuses[$faq->id] = $status;
        }

        return $statuses;
    }

    /**
     * @return array<string, array{sections: array<string, array<string, mixed>>}>
     */
    private function sectionTranslationBundle(Product $product): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $sections = [];

            foreach ($product->sections as $section) {
                $section->loadMissing('translations', 'items.translations');

                $items = [];

                foreach ($section->items as $item) {
                    $items[(string) $item->id] = [
                        'title' => $item->translated('title', $targetLocale),
                        'body' => $item->translated('body', $targetLocale),
                    ];
                }

                $sections[(string) $section->id] = [
                    'key' => $section->key->value,
                    'eyebrow' => $section->translated('eyebrow', $targetLocale),
                    'heading' => $section->translated('heading', $targetLocale),
                    'subheading' => $section->translated('subheading', $targetLocale),
                    'intro' => $section->translated('intro', $targetLocale),
                    'items' => $items,
                ];
            }

            $bundle[$targetLocale] = ['sections' => $sections];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{sections: array<string, array<string, mixed>>}>
     */
    private function sectionTranslationStatus(Product $product): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $sections = [];

            foreach ($product->sections as $section) {
                $section->loadMissing('translations', 'items.translations');

                $items = [];

                foreach ($section->items as $item) {
                    $items[(string) $item->id] = [
                        'title' => $item->translations->contains(
                            fn ($translation): bool => $translation->locale === $targetLocale
                                && $translation->column === 'title',
                        ),
                        'body' => $item->translations->contains(
                            fn ($translation): bool => $translation->locale === $targetLocale
                                && $translation->column === 'body',
                        ),
                    ];
                }

                $sections[(string) $section->id] = [
                    'eyebrow' => $section->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'eyebrow',
                    ),
                    'heading' => $section->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'heading',
                    ),
                    'subheading' => $section->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'subheading',
                    ),
                    'intro' => $section->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'intro',
                    ),
                    'items' => $items,
                ];
            }

            $status[$targetLocale] = ['sections' => $sections];
        }

        return $status;
    }

    /**
     * @return array{id: string, path: string, url: string, mime_type: string, name: string}|null
     */
    private function existingMediaFile(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        return [
            'id' => $path,
            'path' => $path,
            'url' => Product::resolveDisplayMediaUrl($path),
            'mime_type' => 'image/*',
            'name' => basename($path),
        ];
    }
}
