<?php

namespace App\Support;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\OpeningOffer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage opening's register: the edition numbers of the product an admin has
 * chosen, each marked available, reserved (held/sold/returning), or archive.
 *
 * The purchase itself is the site's usual flow, so this only carries what the opening
 * needs: the checkout context for that product (handed to `openPurchase()`), and one
 * small tile list so the page does not have to page through the edition picker.
 */
class OpeningRegisterPresenter
{
    /** The tiles are cached briefly; every hold, sale and release busts them. */
    public const CACHE_SECONDS = 30;

    public static function cacheKey(Product $product): string
    {
        return 'maison.opening.tiles.'.$product->id;
    }

    /**
     * Null when no offer is active, its product can no longer be offered, or the
     * viewer may not see the product yet (early access).
     *
     * @return array{
     *     product: array<string, mixed>,
     *     name: string,
     *     total: int,
     *     available: int,
     *     tiles: list<array{id: int, label: string, available: bool, status: 'available'|'reserved'|'archive'}>
     * }|null
     */
    public function forUser(?User $user): ?array
    {
        $offer = OpeningOffer::current();

        if ($offer === null || ! $offer->product->isVisibleTo($user)) {
            return null;
        }

        $product = $offer->product;

        /** @var list<array{id: int, label: string, available: bool, status: 'available'|'reserved'|'archive'}> $tiles */
        $tiles = Cache::remember(
            self::cacheKey($product),
            now()->addSeconds(self::CACHE_SECONDS),
            fn (): array => $this->tiles($product),
        );

        return [
            'product' => Product::checkoutShare($product),
            'name' => $product->translated('name'),
            'total' => (int) ($product->edition_total ?? count($tiles)),
            'available' => count(array_filter($tiles, fn (array $tile): bool => $tile['available'])),
            'tiles' => $tiles,
        ];
    }

    /**
     * One tile per edition piece, in number order. A piece is available when it is
     * for sale, or when its hold has run out; archived pieces carry status
     * "archive"; held, sold and returning pieces carry "reserved". Labels are the
     * picker's own, and never carry an SKU.
     *
     * @return list<array{id: int, label: string, available: bool, status: 'available'|'reserved'|'archive'}>
     */
    private function tiles(Product $product): array
    {
        return EditionPiece::query()
            ->where('product_id', $product->id)
            ->get(['id', 'product_id', 'edition_number', 'status', 'reserved_until'])
            ->each(fn (EditionPiece $piece) => $piece->setRelation('product', $product))
            ->sortBy(fn (EditionPiece $piece): int => $piece->sequenceNumber())
            ->map(function (EditionPiece $piece): array {
                $available = $this->isAvailable($piece);

                return [
                    'id' => $piece->id,
                    'label' => $piece->edition_number,
                    'available' => $available,
                    'status' => $this->tileStatus($piece, $available),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return 'available'|'reserved'|'archive'
     */
    private function tileStatus(EditionPiece $piece, bool $available): string
    {
        if ($piece->status === EditionPieceStatus::Archive) {
            return 'archive';
        }

        return $available ? 'available' : 'reserved';
    }

    private function isAvailable(EditionPiece $piece): bool
    {
        if ($piece->status === EditionPieceStatus::Available) {
            return true;
        }

        return $piece->status === EditionPieceStatus::Reserved
            && $piece->reserved_until !== null
            && $piece->reserved_until->isPast();
    }
}
