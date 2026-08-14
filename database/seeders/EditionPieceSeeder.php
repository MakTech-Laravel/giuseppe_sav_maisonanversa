<?php

namespace Database\Seeders;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use Illuminate\Database\Seeder;

class EditionPieceSeeder extends Seeder
{
    /**
     * Seed Heritage No.001 pieces. Number 001 is permanently archived.
     */
    public function run(): void
    {
        $total = (int) config('maison.edition.total', 100);

        for ($number = 1; $number <= $total; $number++) {
            EditionPiece::query()->firstOrCreate(
                ['edition_number' => $number],
                [
                    'status' => $number === 1
                        ? EditionPieceStatus::Archive
                        : EditionPieceStatus::Available,
                    'notes' => $number === 1
                        ? 'Maison Anversa Archive — not for sale'
                        : null,
                ],
            );
        }
    }
}
