<?php

namespace Database\Factories;

use App\Models\JournalArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JournalArticle> */
class JournalArticleFactory extends Factory
{
    protected $model = JournalArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'title' => $title,
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'cover_path' => 'antwerp-cityscape',
            'category' => 'Erfgoed',
            'author' => 'Maison Anversa',
            'date_label' => 'Juni 2026',
            'published_at' => now()->subDay(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
        ]);
    }
}
