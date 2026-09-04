<?php

use App\Models\CommunityCourt;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the legacy courts tab redirects to the club directory', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'nl', 'tab' => 'courts']))
        ->assertRedirect(route('community.clubs.index', ['locale' => 'nl']));
});

test('authenticated members no longer receive courts props on the community page', function () {
    $user = User::factory()->create();

    CommunityCourt::factory()->create(['is_published' => true]);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/community')
            ->missing('courts')
            ->missing('tab')
        );
});
