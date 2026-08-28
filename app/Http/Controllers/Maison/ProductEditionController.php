<?php

namespace App\Http\Controllers\Maison;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\EditionPiece;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductEditionController extends Controller
{
    public const PER_PAGE = 30;

    /**
     * Paginated edition pieces for the purchase picker (all statuses).
     */
    public function index(Request $request, string $locale, Product $product): JsonResponse
    {
        abort_unless(
            $product->is_published && $product->type === ProductType::LimitedEdition,
            404,
        );

        $search = preg_replace('/\D+/', '', (string) $request->query('search', '')) ?? '';

        $baseQuery = EditionPiece::query()->where('product_id', $product->id);

        $min = (clone $baseQuery)->min('edition_number');
        $max = (clone $baseQuery)->max('edition_number');

        $start = $min !== null ? (int) $min : 1;
        $end = $max !== null
            ? (int) $max
            : (int) ($product->edition_total ?: 1);

        $pieces = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('edition_number', 'like', $search.'%');
            })
            ->orderBy('edition_number')
            ->paginate(self::PER_PAGE)
            ->through(fn (EditionPiece $piece) => [
                'id' => $piece->id,
                'edition_number' => $piece->edition_number,
                'label' => $product->formatEditionLabel($piece->edition_number),
                'sku' => $product->formatEditionSku($piece->edition_number),
                'status' => $piece->status->value,
                'selectable' => $piece->status === EditionPieceStatus::Available,
            ]);

        return response()->json([
            'data' => $pieces->items(),
            'meta' => [
                'current_page' => $pieces->currentPage(),
                'last_page' => $pieces->lastPage(),
                'per_page' => $pieces->perPage(),
                'total' => $pieces->total(),
                'range' => [
                    'start' => $start,
                    'end' => $end,
                    'start_label' => $product->formatEditionLabel($start),
                    'end_label' => $product->formatEditionLabel($end),
                ],
            ],
            'links' => [
                'next' => $pieces->nextPageUrl(),
            ],
        ]);
    }
}
