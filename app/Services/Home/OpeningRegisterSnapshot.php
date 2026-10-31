<?php

namespace App\Services\Home;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Models\EditionPiece;
use App\Models\HomeHero;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use Illuminate\Support\Facades\Cache;

class OpeningRegisterSnapshot
{
    public function __construct(
        private EditionAllocator $allocator,
    ) {}

    /**
     * @return array{
     *     product: array{
     *         productId: int|null,
     *         productSlug: string|null,
     *         currency: string,
     *         amount: string,
     *         displayAmount: string,
     *         productName: string,
     *         deliveryLabel: string|null,
     *         productType: string,
     *         editionTotal: int
     *     },
     *     available_count: int,
     *     places: list<array{
     *         sequence: int,
     *         label: string,
     *         edition_piece_id: int|null,
     *         reserved: bool
     *     }>
     * }
     */
    public function forViewer(?User $viewer = null): array
    {
        $product = $this->resolveProduct();

        if ($product === null) {
            return $this->empty();
        }

        $this->allocator->releaseExpiredHolds();

        // Always build from edition_total + live piece statuses so the grid box
        // count matches the product and Claim never offers a stale "free" number.
        $structure = $this->buildStructure($product);
        $statuses = $this->freshStatuses($structure);

        $places = [];
        $availableCount = 0;

        foreach ($structure as $row) {
            $pieceId = $row['edition_piece_id'];
            $status = $pieceId !== null
                ? ($statuses[$pieceId]['status'] ?? EditionPieceStatus::Archive->value)
                : EditionPieceStatus::Archive->value;
            $reservedBy = $pieceId !== null
                ? ($statuses[$pieceId]['reserved_by_user_id'] ?? null)
                : null;

            $selectable = $status === EditionPieceStatus::Available->value
                || (
                    $viewer !== null
                    && $status === EditionPieceStatus::Reserved->value
                    && (int) ($reservedBy ?? 0) === (int) $viewer->id
                );

            if ($selectable) {
                $availableCount++;
            }

            $places[] = [
                'sequence' => $row['sequence'],
                'label' => $row['label'],
                'edition_piece_id' => $pieceId,
                'reserved' => ! $selectable,
            ];
        }

        return [
            'product' => array_merge(Product::checkoutShare($product), [
                'editionTotal' => max(0, (int) ($product->edition_total ?? 0)),
            ]),
            'available_count' => $availableCount,
            'places' => $places,
        ];
    }

    public function bust(?Product $product = null): void
    {
        if ($product !== null) {
            Cache::forget($this->cacheKey($product));

            return;
        }

        Product::query()
            ->where('type', ProductType::LimitedEdition)
            ->pluck('id')
            ->each(function (int $id): void {
                Cache::forget($this->cacheKeyById($id));
            });
    }

    public function resolveProduct(?HomeHero $hero = null): ?Product
    {
        $hero ??= HomeHero::current()->loadMissing('openingProduct');
        $assigned = $hero->openingProduct;

        if (
            $assigned !== null
            && $assigned->is_published
            && $assigned->type === ProductType::LimitedEdition
        ) {
            return $assigned;
        }

        $founding = Product::founding();

        if (
            $founding !== null
            && $founding->is_published
            && $founding->type === ProductType::LimitedEdition
        ) {
            return $founding;
        }

        return Product::query()
            ->where('is_published', true)
            ->where('type', ProductType::LimitedEdition)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * One place per product edition_total (not every row that happens to exist).
     *
     * @return list<array{
     *     sequence: int,
     *     label: string,
     *     edition_piece_id: int|null
     * }>
     */
    private function buildStructure(Product $product): array
    {
        $total = max(0, (int) ($product->edition_total ?? 0));
        $pieces = EditionPiece::query()
            ->where('product_id', $product->id)
            ->get()
            ->keyBy(fn (EditionPiece $piece): int => $piece->sequenceNumber());

        $rows = [];

        for ($sequence = 1; $sequence <= $total; $sequence++) {
            $piece = $pieces->get($sequence);

            $rows[] = [
                'sequence' => $sequence,
                'label' => $piece?->edition_number ?? $product->formatEditionLabel($sequence),
                'edition_piece_id' => $piece?->id,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{sequence: int, label: string, edition_piece_id: int|null}>  $structure
     * @return array<int, array{status: string, reserved_by_user_id: int|null}>
     */
    private function freshStatuses(array $structure): array
    {
        $ids = collect($structure)
            ->pluck('edition_piece_id')
            ->filter()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return EditionPiece::query()
            ->whereKey($ids)
            ->get(['id', 'status', 'reserved_by_user_id'])
            ->mapWithKeys(fn (EditionPiece $piece): array => [
                $piece->id => [
                    'status' => $piece->status->value,
                    'reserved_by_user_id' => $piece->reserved_by_user_id,
                ],
            ])
            ->all();
    }

    /**
     * @return array{
     *     product: array{
     *         productId: int|null,
     *         productSlug: string|null,
     *         currency: string,
     *         amount: string,
     *         displayAmount: string,
     *         productName: string,
     *         deliveryLabel: string|null,
     *         productType: string,
     *         editionTotal: int
     *     },
     *     available_count: int,
     *     places: list<array{
     *         sequence: int,
     *         label: string,
     *         edition_piece_id: int|null,
     *         reserved: bool
     *     }>
     * }
     */
    private function empty(): array
    {
        return [
            'product' => array_merge(Product::checkoutShare(null), [
                'editionTotal' => 0,
            ]),
            'available_count' => 0,
            'places' => [],
        ];
    }

    private function cacheKey(Product $product): string
    {
        return $this->cacheKeyById((int) $product->id);
    }

    private function cacheKeyById(int $productId): string
    {
        return "opening-register:{$productId}";
    }
}
