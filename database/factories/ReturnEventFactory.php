<?php

namespace Database\Factories;

use App\Enums\ReturnStatus;
use App\Models\OrderReturn;
use App\Models\ReturnEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnEvent>
 */
class ReturnEventFactory extends Factory
{
    protected $model = ReturnEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_return_id' => OrderReturn::factory(),
            'user_id' => null,
            'from_status' => null,
            'to_status' => ReturnStatus::Requested,
            'note' => null,
        ];
    }
}
