<?php

namespace App\Services\FoundingCircle;

use App\Models\FoundingCircleRegisterEntry;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use App\Support\FoundingCircleRegisterPresenter;
use Illuminate\Support\Facades\Cache;

/**
 * Shared counters for the menu, homepage, product page, and footer.
 * The full 100-place ledger is built per request so the logged-in "YOU" row stays private.
 */
class FoundingCircleRegisterSnapshot
{
    public const CACHE_KEY = 'founding-circle.register.snapshot';

    public function __construct(
        private EditionInventory $inventory,
        private FoundingCircleRegisterPresenter $presenter,
    ) {}

    /**
     * @return array{
     *     inscribed_count: int,
     *     remaining_count: int,
     *     places_total: int,
     *     latest_entry: array{number: string, label: string}|null
     * }
     */
    public function share(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $product = Product::founding();
            $total = (int) ($product?->edition_total ?? 100);
            $inscribed = FoundingCircleRegisterEntry::query()
                ->whereNotNull('edition_number')
                ->count();
            $remaining = $product !== null
                ? (int) $this->inventory->snapshot($product)['available']
                : max(0, $total - $inscribed);

            $latest = FoundingCircleRegisterEntry::query()
                ->with('user')
                ->whereNotNull('edition_number')
                ->latest('joined_at')
                ->first();

            return [
                'inscribed_count' => $inscribed,
                'remaining_count' => $remaining,
                'places_total' => $total,
                'latest_entry' => $latest !== null ? $this->presenter->latestEntry($latest) : null,
            ];
        });
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);

        $product = Product::founding();

        if ($product !== null) {
            $this->inventory->bust($product);
        }
    }
}
