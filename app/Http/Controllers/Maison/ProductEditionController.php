<?php

namespace App\Http\Controllers\Maison;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Exceptions\EditionUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Edition\EditionAllocator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductEditionController extends Controller
{
    public const PER_PAGE = 30;

    /**
     * Paginated edition pieces for the purchase picker (all statuses).
     */
    public function index(
        Request $request,
        string $locale,
        Product $product,
        EditionAllocator $allocator,
    ): JsonResponse {
        abort_unless(
            $product->is_published && $product->type === ProductType::LimitedEdition,
            404,
        );

        $allocator->releaseExpiredHolds();

        $search = strtoupper(trim((string) $request->query('search', '')));

        $baseQuery = EditionPiece::query()->where('product_id', $product->id);

        $minLabel = (clone $baseQuery)->orderBy('edition_number')->value('edition_number');
        $maxLabel = (clone $baseQuery)->orderByDesc('edition_number')->value('edition_number');

        $start = $minLabel !== null
            ? $product->parseEditionSequence($minLabel)
            : 1;
        $end = $maxLabel !== null
            ? $product->parseEditionSequence($maxLabel)
            : (int) ($product->edition_total ?: 1);

        $user = $request->user();

        $pieces = (clone $baseQuery)
            ->with('product')
            ->when($search !== '', fn ($query) => $this->applyEditionSearch($query, $product, $search))
            ->orderBy('edition_number')
            ->paginate(self::PER_PAGE)
            ->through(fn (EditionPiece $piece) => [
                'id' => $piece->id,
                'edition_number' => $piece->sequenceNumber(),
                'label' => $piece->edition_number,
                'status' => $piece->status->value,
                'selectable' => $piece->status === EditionPieceStatus::Available
                    || $piece->isHeldBy($user),
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
                    'start_label' => $minLabel ?? $product->formatEditionLabel($start),
                    'end_label' => $maxLabel ?? $product->formatEditionLabel($end),
                ],
            ],
            'links' => [
                'next' => $pieces->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Reserve the chosen edition for 15 minutes so another customer cannot select it.
     */
    public function hold(
        Request $request,
        string $locale,
        Product $product,
        EditionPiece $editionPiece,
        EditionAllocator $allocator,
    ): JsonResponse {
        abort_unless(
            $product->is_published && $product->type === ProductType::LimitedEdition,
            404,
        );

        abort_unless($editionPiece->product_id === $product->id, 404);

        $user = $request->user();

        abort_unless($user !== null, 401);

        $allocator->releaseExpiredHolds();

        try {
            $piece = $allocator->holdForUser($user, $product, $editionPiece->id);
        } catch (EditionUnavailableException) {
            throw ValidationException::withMessages([
                'edition_piece_id' => __('Dit editienummer is niet meer beschikbaar. Kies een ander nummer.'),
            ]);
        }

        return response()->json([
            'id' => $piece->id,
            'edition_number' => $piece->sequenceNumber(),
            'label' => $piece->edition_number,
            'status' => $piece->status->value,
            'reserved_until' => $piece->reserved_until?->toIso8601String(),
        ]);
    }

    /**
     * @param  Builder<EditionPiece>  $query
     */
    private function applyEditionSearch(Builder $query, Product $product, string $search): void
    {
        $labels = $this->matchingEditionLabels($product, $search);

        if ($labels === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('edition_number', $labels);
    }

    /**
     * @return list<string>
     */
    private function matchingEditionLabels(Product $product, string $search): array
    {
        $search = strtoupper(trim($search));

        if ($search === '') {
            return [];
        }

        $digits = preg_replace('/\D+/', '', $search) ?? '';
        $total = max(1, (int) $product->edition_total);
        $matches = [];

        for ($number = 1; $number <= $total; $number++) {
            $label = strtoupper($product->formatEditionLabel($number));

            if (str_starts_with($label, $search)) {
                $matches[] = $label;

                continue;
            }

            if ($digits === '') {
                continue;
            }

            $sequence = (string) $number;
            $padded = str_pad($sequence, $product->editionNumberPadWidth(), '0', STR_PAD_LEFT);

            if (
                str_starts_with($sequence, $digits)
                || str_starts_with($padded, $digits)
                || str_contains($label, $digits)
            ) {
                $matches[] = $label;
            }
        }

        return $matches;
    }
}
