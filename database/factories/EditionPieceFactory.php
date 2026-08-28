<?php

namespace Database\Factories;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\Product;
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
        $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first()
            ?? Product::factory()->founding()->create();

        $number = fake()->unique()->numberBetween(2, 100);

        return [
            'product_id' => $product->id,
            'edition_number' => $product->formatEditionLabel($number),
            'status' => EditionPieceStatus::Available,
            'verification_token' => (string) Str::uuid(),
        ];
    }

    public function archive(): static
    {
        return $this->state(function (array $attributes): array {
            $product = Product::query()->find($attributes['product_id']);

            return [
                'edition_number' => $product?->formatEditionLabel(1) ?? '001',
                'status' => EditionPieceStatus::Archive,
                'notes' => 'Maison Anversa Archive — not for sale',
            ];
        });
    }

    public function allocated(): static
    {
        return $this->state(fn (): array => [
            'status' => EditionPieceStatus::Allocated,
            'allocated_at' => now(),
        ]);
    }
}
