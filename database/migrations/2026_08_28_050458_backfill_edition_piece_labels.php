<?php

use App\Enums\ProductType;
use App\Models\EditionPiece;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Rewrite numeric edition_number values to full formatted labels.
     */
    public function up(): void
    {
        EditionPiece::query()
            ->with('product')
            ->orderBy('id')
            ->each(function (EditionPiece $piece): void {
                if ($piece->product === null || $piece->product->type !== ProductType::LimitedEdition) {
                    return;
                }

                if (! ctype_digit((string) $piece->edition_number)) {
                    return;
                }

                $label = $piece->product->formatEditionLabel((int) $piece->edition_number);

                if ($label === $piece->edition_number) {
                    return;
                }

                $exists = EditionPiece::query()
                    ->where('product_id', $piece->product_id)
                    ->where('edition_number', $label)
                    ->whereKeyNot($piece->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                $piece->update(['edition_number' => $label]);
            });
    }

    /**
     * Reverse is not supported — labels cannot be safely reverted to integers.
     */
    public function down(): void
    {
        //
    }
};
