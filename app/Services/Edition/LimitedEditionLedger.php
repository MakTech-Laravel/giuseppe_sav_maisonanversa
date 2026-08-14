<?php

namespace App\Services\Edition;

use App\Enums\EditionPieceStatus;
use App\Enums\ProductType;
use App\Models\EditionPiece;
use App\Models\Product;

class LimitedEditionLedger
{
    /**
     * Create missing numbered pieces and apply archive numbers. Never deletes
     * reserved or allocated pieces when the edition shrinks.
     */
    public function sync(Product $product): void
    {
        if ($product->type !== ProductType::LimitedEdition) {
            return;
        }

        $total = (int) $product->edition_total;

        if ($total < 1) {
            return;
        }

        $archivedNumbers = $product->archiveEditionNumberList();

        for ($number = 1; $number <= $total; $number++) {
            $piece = EditionPiece::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'edition_number' => $number,
                ],
                [
                    'status' => in_array($number, $archivedNumbers, true)
                        ? EditionPieceStatus::Archive
                        : EditionPieceStatus::Available,
                    'notes' => in_array($number, $archivedNumbers, true)
                        ? 'Maison Anversa Archive — not for sale'
                        : null,
                ],
            );

            if (
                in_array($number, $archivedNumbers, true)
                && $piece->status === EditionPieceStatus::Available
            ) {
                $piece->fill([
                    'status' => EditionPieceStatus::Archive,
                    'notes' => $piece->notes ?: 'Maison Anversa Archive — not for sale',
                ])->save();
            }
        }

        EditionPiece::query()
            ->where('product_id', $product->id)
            ->where('edition_number', '>', $total)
            ->where('status', EditionPieceStatus::Available)
            ->delete();

        app(EditionInventory::class)->bust($product);
    }
}
