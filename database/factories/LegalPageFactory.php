<?php

namespace Database\Factories;

use App\Models\LegalPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalPage>
 */
class LegalPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'body' => '<p>'.e(fake()->paragraphs(4, true)).'</p>',
            'is_published' => true,
        ];
    }
}
