<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/products/create');
    }

    public function show(string $locale, Product $product): Response
    {
        return Inertia::render('admin/products/show', [
            'product' => $this->formProduct($product),
        ]);
    }

    public function store(StoreProductRequest $request, string $locale): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->payload($validated);
        $payload['gallery'] = $this->syncGallery([], $validated, $request);

        Product::query()->create($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product aangemaakt.')]);

        return redirect()->route('admin.products.index', ['locale' => $locale]);
    }

    public function edit(string $locale, Product $product): Response
    {
        return Inertia::render('admin/products/edit', [
            'product' => $this->formProduct($product),
        ]);
    }

    public function update(UpdateProductRequest $request, string $locale, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->payload($validated);
        $payload['gallery'] = $this->syncGallery($product->gallery ?? [], $validated, $request);

        $product->update($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product bijgewerkt.')]);

        return redirect()->route('admin.products.edit', ['product' => $product]);
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
            ->tap(fn ($query) => $this->applyInventoryFilters($query, $filters))
            ->orderBy('edition_number')
            ->paginate(100)
            ->withQueryString()
            ->through(fn (EditionPiece $piece) => [
                'sku' => $product->formatEditionSku($piece->edition_number),
                'label' => $product->formatEditionLabel($piece->edition_number),
                'status' => $piece->status->value,
                'status_key' => $piece->status->value,
                'notes' => $piece->notes ?? '',
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
            ],
        ]);
    }

    /**
     * @return array{search: string, number_from: int|null, number_to: int|null, status: EditionPieceStatus|null}
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
        ];
    }

    /**
     * @param  Builder<EditionPiece>  $query
     * @param  array{search: string, number_from: int|null, number_to: int|null, status: EditionPieceStatus|null}  $filters
     */
    private function applyInventoryFilters($query, array $filters): void
    {
        $query
            ->when($filters['number_from'] !== null, fn ($inner) => $inner->where(
                'edition_number',
                '>=',
                $filters['number_from'],
            ))
            ->when($filters['number_to'] !== null, fn ($inner) => $inner->where(
                'edition_number',
                '<=',
                $filters['number_to'],
            ))
            ->when($filters['status'] !== null, fn ($inner) => $inner->where(
                'status',
                $filters['status']->value,
            ))
            ->when($filters['search'] !== '', function ($inner) use ($filters): void {
                $search = $filters['search'];

                $inner->where(function ($group) use ($search): void {
                    $group->where('notes', 'like', "%{$search}%");

                    $digits = preg_replace('/\D+/', '', $search) ?? '';

                    if ($digits !== '') {
                        $group->orWhere('edition_number', (int) $digits);
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
            'sold_out_behavior' => 'keep_page',
        ];
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
            'stripe_price_id' => $product->stripe_price_id,
            'primary_image' => $this->existingMediaFile($primaryPath),
            'gallery_images' => array_values(array_filter(array_map(
                fn (string $path): ?array => $this->existingMediaFile($path),
                $extraPaths,
            ))),
        ];
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
            'url' => Product::resolveMediaUrl($path),
            'mime_type' => 'image/*',
            'name' => basename($path),
        ];
    }
}
