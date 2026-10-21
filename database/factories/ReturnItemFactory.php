<?php

namespace Database\Factories;

use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnItem>
 */
class ReturnItemFactory extends Factory
{
    protected $model = ReturnItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_return_id' => OrderReturn::factory(),
            'product_id' => Product::query()->where('slug', Product::FOUNDING_SLUG)->value('id')
                ?? Product::factory(),
            'edition_piece_id' => null,
            'quantity' => 1,
            'unit_amount' => '249.00',
        ];
    }
}
