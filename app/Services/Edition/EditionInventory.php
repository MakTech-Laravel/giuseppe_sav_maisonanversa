<?php

namespace App\Services\Edition;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use Illuminate\Support\Facades\Cache;

class EditionInventory
{
    public const CACHE_KEY = 'maison.edition.snapshot';

    /**
     * @return array{
     *     total: int,
     *     archived: int,
     *     sellable: int,
     *     allocated: int,
     *     held: int,
     *     reserved: int,
     *     available: int,
     *     soldOut: bool
     * }
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $total = (int) config('maison.edition.total', 100);

            $archived = EditionPiece::query()->where('status', EditionPieceStatus::Archive)->count();
            $allocated = EditionPiece::query()->where('status', EditionPieceStatus::Allocated)->count();
            $held = EditionPiece::query()->where('status', EditionPieceStatus::Reserved)->count();
            $available = EditionPiece::query()->where('status', EditionPieceStatus::Available)->count();
            $sellable = max(0, $total - $archived);

            return [
                'total' => $total,
                'archived' => $archived,
                'sellable' => $sellable,
                'allocated' => $allocated,
                'held' => $held,
                'reserved' => $allocated,
                'available' => $available,
                'soldOut' => $allocated >= $sellable,
            ];
        });
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
