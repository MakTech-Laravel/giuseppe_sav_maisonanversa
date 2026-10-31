<?php

namespace App\Services\Edition;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Home\OpeningRegisterSnapshot;
use Illuminate\Support\Facades\Cache;

class EditionInventory
{
    /**
     * @return array{
     *     total: int,
     *     archived: int,
     *     sellable: int,
     *     allocated: int,
     *     held: int,
     *     reserved: int,
     *     available: int,
     *     soldOut: bool,
     *     deliveryLabel: string|null,
     *     productName: string|null
     * }
     */
    public function snapshot(?Product $product = null): array
    {
        $product ??= Product::founding();

        if ($product === null) {
            return $this->emptySnapshot();
        }

        $snapshot = Cache::remember($this->cacheKey($product), now()->addMinutes(5), function () use ($product): array {
            if ($product->type === ProductType::Simple) {
                $available = max(0, (int) $product->stock_quantity);

                return [
                    'total' => $available,
                    'archived' => 0,
                    'sellable' => $available,
                    'allocated' => 0,
                    'held' => 0,
                    'reserved' => 0,
                    'available' => $available,
                    'soldOut' => $available === 0,
                    'deliveryLabel' => $product->expected_delivery_label,
                    'productName' => $product->name,
                ];
            }

            $total = (int) ($product->edition_total ?? 0);
            $pieces = EditionPiece::query()->where('product_id', $product->id);

            $archived = (clone $pieces)->where('status', EditionPieceStatus::Archive)->count();
            $allocated = (clone $pieces)->where('status', EditionPieceStatus::Allocated)->count();
            $returning = (clone $pieces)->where('status', EditionPieceStatus::Returning)->count();
            $held = (clone $pieces)->where('status', EditionPieceStatus::Reserved)->count();
            $available = (clone $pieces)->where('status', EditionPieceStatus::Available)->count();
            $sellable = max(0, $total - $archived);
            $owned = $allocated + $returning;

            return [
                'total' => $total,
                'archived' => $archived,
                'sellable' => $sellable,
                'allocated' => $owned,
                'held' => $held,
                'reserved' => $owned,
                'available' => $available,
                'soldOut' => $sellable === 0 || $owned >= $sellable,
                'deliveryLabel' => $product->expected_delivery_label,
                'productName' => $product->name,
            ];
        });

        $snapshot['productName'] = $product->translated('name');
        $deliveryLabel = $product->translated('expected_delivery_label')
            ?: $snapshot['deliveryLabel'];
        $snapshot['deliveryLabel'] = is_string($deliveryLabel) && $deliveryLabel !== ''
            ? __($deliveryLabel)
            : $deliveryLabel;

        return $snapshot;
    }

    public function bust(?Product $product = null): void
    {
        if ($product !== null) {
            Cache::forget($this->cacheKey($product));
            app(OpeningRegisterSnapshot::class)->bust($product);

            return;
        }

        $founding = Product::founding();

        if ($founding !== null) {
            Cache::forget($this->cacheKey($founding));
        }

        app(OpeningRegisterSnapshot::class)->bust();
    }

    public function cacheKey(Product $product): string
    {
        return 'maison.edition.snapshot.'.$product->id;
    }

    /**
     * @return array{
     *     total: int,
     *     archived: int,
     *     sellable: int,
     *     allocated: int,
     *     held: int,
     *     reserved: int,
     *     available: int,
     *     soldOut: bool,
     *     deliveryLabel: string|null,
     *     productName: string|null
     * }
     */
    private function emptySnapshot(): array
    {
        return [
            'total' => 0,
            'archived' => 0,
            'sellable' => 0,
            'allocated' => 0,
            'held' => 0,
            'reserved' => 0,
            'available' => 0,
            'soldOut' => true,
            'deliveryLabel' => null,
            'productName' => null,
        ];
    }
}
