<?php

namespace Database\Factories;

use App\Enums\FoundingCircleClaimSource;
use App\Enums\FoundingCircleClaimStatus;
use App\Models\FoundingCircleClaim;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoundingCircleClaim>
 */
class FoundingCircleClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::founding() ?? Product::factory()->founding()->create();

        return [
            'user_id' => User::factory(),
            'product_id' => $product->id,
            'edition_piece_id' => null,
            'order_id' => null,
            'edition_number' => fake()->numberBetween(2, 100),
            'status' => FoundingCircleClaimStatus::Pending,
            'source' => FoundingCircleClaimSource::Manual,
            'admin_note' => null,
            'reviewed_by_id' => null,
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => FoundingCircleClaimStatus::Pending,
            'reviewed_by_id' => null,
            'reviewed_at' => null,
            'admin_note' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => FoundingCircleClaimStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => FoundingCircleClaimStatus::Rejected,
            'reviewed_at' => now(),
            'admin_note' => 'Afgewezen in test',
        ]);
    }

    public function fromOrder(): static
    {
        return $this->state(fn (): array => [
            'source' => FoundingCircleClaimSource::Order,
        ]);
    }
}
