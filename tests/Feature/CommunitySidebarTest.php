<?php

use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('community sidebar is hydrated for authenticated members', function () {
    $product = Product::founding();
    $member = User::factory()->create();

    Order::factory()->forUser($member)->paid()->create([
        'product_id' => $product?->id,
        'edition_number' => 12,
    ]);

    CommunityPost::factory()->create([
        'author_id' => $member->id,
        'status' => 'published',
    ]);

    User::factory()->count(4)->create()->each(function (User $user) use ($product): void {
        Order::factory()->forUser($user)->paid()->create([
            'product_id' => $product?->id,
            'edition_number' => fake()->numberBetween(20, 90),
        ]);
    });

    CommunityEvent::factory()->create([
        'title' => 'Founding Circle Padel Morning',
        'starts_at' => now()->addDays(5),
    ]);

    $this->actingAs($member)
        ->get(localized('maison.community'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sidebar.profile.editionNumber', '012')
            ->has('sidebar.recentMembers')
            ->where('sidebar.nextEvent.title', 'Founding Circle Padel Morning'));
});
