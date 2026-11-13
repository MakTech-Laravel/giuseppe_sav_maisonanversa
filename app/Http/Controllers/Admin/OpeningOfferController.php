<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOpeningOfferRequest;
use App\Http\Requests\Admin\UpdateOpeningOfferRequest;
use App\Models\OpeningOffer;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Which product the homepage opening offers for reservation. Exactly one offer is
 * active at a time; the opening's number tiles are that product's edition numbers.
 */
class OpeningOfferController extends Controller
{
    public function index(string $locale, EditionInventory $inventory): Response
    {
        $offers = OpeningOffer::query()
            ->with('product')
            ->ordered()
            ->get()
            ->map(function (OpeningOffer $offer) use ($inventory): array {
                $stock = $inventory->snapshot($offer->product);

                return [
                    'id' => (string) $offer->id,
                    'product' => [
                        'id' => $offer->product->id,
                        'name' => $offer->product->name,
                        'slug' => $offer->product->slug,
                    ],
                    'is_active' => $offer->is_active,
                    'offerable' => $offer->isOfferable(),
                    'sort_order' => $offer->sort_order,
                    'total' => $stock['total'],
                    'available' => $stock['available'],
                ];
            })
            ->values()
            ->all();

        return Inertia::render('admin/opening-offers/index', [
            'offers' => $offers,
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/opening-offers/create', [
            'products' => $this->productOptions(),
        ]);
    }

    public function store(StoreOpeningOfferRequest $request, string $locale): RedirectResponse
    {
        OpeningOffer::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Opening offer created.')]);

        return redirect()->route('admin.opening-offers.index', ['locale' => $locale]);
    }

    public function edit(string $locale, OpeningOffer $openingOffer): Response
    {
        return Inertia::render('admin/opening-offers/edit', [
            'offer' => [
                'id' => (string) $openingOffer->id,
                'product_id' => $openingOffer->product_id,
                'is_active' => $openingOffer->is_active,
                'sort_order' => $openingOffer->sort_order,
            ],
            'products' => $this->productOptions($openingOffer),
        ]);
    }

    public function update(UpdateOpeningOfferRequest $request, string $locale, OpeningOffer $openingOffer): RedirectResponse
    {
        $openingOffer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Opening offer updated.')]);

        return redirect()->route('admin.opening-offers.index', ['locale' => $locale]);
    }

    public function destroy(string $locale, OpeningOffer $openingOffer): RedirectResponse
    {
        $openingOffer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Opening offer removed.')]);

        return redirect()->route('admin.opening-offers.index', ['locale' => $locale]);
    }

    /**
     * The products an offer can point at, plus the offer's current product so an
     * existing offer still shows its product after it stopped being offerable.
     *
     * @return list<array{id: int, name: string, slug: string, edition_total: int|null}>
     */
    private function productOptions(?OpeningOffer $current = null): array
    {
        return OpeningOffer::eligibleProducts()
            ->when($current !== null, fn ($query) => $query->orWhere('id', $current->product_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'edition_total' => $product->edition_total,
            ])
            ->values()
            ->all();
    }
}
