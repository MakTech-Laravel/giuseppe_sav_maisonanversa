<?php

namespace Database\Factories;

use App\Models\OrderReturn;
use App\Models\ReturnPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnPhoto>
 */
class ReturnPhotoFactory extends Factory
{
    protected $model = ReturnPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_return_id' => OrderReturn::factory(),
            'path' => 'returns/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }
}
