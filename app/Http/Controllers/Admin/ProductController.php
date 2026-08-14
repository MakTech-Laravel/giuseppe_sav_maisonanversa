<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(StoreProductRequest $request, string $locale): RedirectResponse
    {
        $product = Product::query()->create($this->payload($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product aangemaakt.')]);

        return redirect()->route('admin.products.edit', ['product' => $product]);
    }

    public function edit(string $locale, Product $product): Response
    {
        return Inertia::render('admin/products/edit', [
            'product' => $this->formProduct($product),
        ]);
    }

    public function update(UpdateProductRequest $request, string $locale, Product $product): RedirectResponse
    {
        $product->update($this->payload($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product bijgewerkt.')]);

        return redirect()->route('admin.products.edit', ['product' => $product]);
    }

    public function inventory(string $locale, Product $product, EditionInventory $inventory): Response
    {
        abort_unless($product->isLimitedEdition(), 404);

        return $this->renderInventory($product, $inventory);
    }

    public function renderInventory(Product $product, EditionInventory $inventory): Response
    {
        $snapshot = $inventory->snapshot($product);
        $pieces = EditionPiece::query()
            ->where('product_id', $product->id)
            ->orderBy('edition_number')
            ->get();
        $prefix = $product->skuPrefix();

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
                'rows' => $pieces->map(fn (EditionPiece $piece) => [
                    'sku' => $prefix.'-'.$piece->formattedNumber(),
                    'label' => 'No.'.$piece->formattedNumber(),
                    'status' => $piece->status->value,
                    'status_key' => $piece->status->value,
                    'notes' => $piece->notes ?? '',
                ]),
            ],
            'heritageConnected' => true,
        ]);
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
     * @return array<string, mixed>
     */
    private function formProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type->value,
            'amount' => (string) $product->amount,
            'currency' => $product->currency,
            'edition_total' => $product->edition_total,
            'archive_edition_numbers' => implode(', ', $product->archiveEditionNumberList()),
            'stock_quantity' => $product->stock_quantity,
            'is_published' => $product->is_published,
            'grants_founding_circle' => $product->grants_founding_circle,
            'expected_delivery_label' => $product->expected_delivery_label,
            'stripe_price_id' => $product->stripe_price_id,
        ];
    }
}
