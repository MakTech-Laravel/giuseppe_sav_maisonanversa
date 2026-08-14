<?php

namespace Database\Factories;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EditionPiece>
 */
class EditionPieceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_number' => fake()->unique()->numberBetween(2, 100),
            'status' => EditionPieceStatus::Available,
            'verification_token' => (string) Str::uuid(),
        ];
    }

    public function archive(): static
    {
        return $this->state(fn (): array => [
            'edition_number' => 1,
            'status' => EditionPieceStatus::Archive,
            'notes' => 'Maison Anversa Archive — not for sale',
        ]);
    }

    public function allocated(): static
    {
        return $this->state(fn (): array => [
            'status' => EditionPieceStatus::Allocated,
            'allocated_at' => now(),
        ]);
    }
}
