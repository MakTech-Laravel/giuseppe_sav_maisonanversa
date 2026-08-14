<?php

namespace Database\Factories;

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'locale' => fake()->randomElement(['nl', 'en', 'fr']),
            'source' => SubscriberSource::Home,
            'status' => SubscriberStatus::Subscribed,
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
            'consent_user_agent' => 'Pest',
            'unsubscribe_token' => (string) Str::uuid(),
            'preferences' => [
                'heritageLetter' => true,
                'productUpdates' => true,
                'events' => false,
            ],
        ];
    }
}
