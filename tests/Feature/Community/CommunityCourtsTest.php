<?php

use App\Models\CommunityCourt;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated members receive published courts on the community page', function () {
    $user = User::factory()->create();

    CommunityCourt::factory()->create([
        'title' => 'Padel Club Antwerpen',
        'body' => 'Founding Club Corner',
        'location' => 'Antwerpen, België',
        'lat' => 51.2194,
        'lng' => 4.4025,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    CommunityCourt::factory()->unpublished()->create([
        'title' => 'Hidden Court',
        'is_published' => false,
    ]);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/community')
            ->has('courts', 1)
            ->where('courts.0.title', 'Padel Club Antwerpen')
            ->where('courts.0.location', 'Antwerpen, België')
            ->where('courts.0.coming', false)
            ->has('courts.0.pin_top')
            ->has('courts.0.pin_left')
        );
});

test('courts without coordinates are marked coming soon', function () {
    $user = User::factory()->create();

    CommunityCourt::factory()->comingSoon()->create([
        'title' => 'Amsterdam Padel Club',
        'is_published' => true,
    ]);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('courts.0.coming', true)
            ->where('courts.0.pin_top', null)
            ->where('courts.0.pin_left', null)
        );
});

test('community courts are translated for the request locale', function () {
    fakeDeepLTranslations();

    $user = User::factory()->create();

    CommunityCourt::factory()->create([
        'title' => 'Padel Club Antwerpen',
        'body' => 'Founding Club Corner',
        'location' => 'Antwerpen, België',
        'lat' => 51.2194,
        'lng' => 4.4025,
        'is_published' => true,
    ]);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('courts.0.title', 'EN Padel Club Antwerpen')
            ->where('courts.0.body', 'EN Founding Club Corner')
            ->where('courts.0.location', 'EN Antwerpen, België')
        );
});

test('guests do not receive courts props', function () {
    CommunityCourt::factory()->create(['is_published' => true]);

    $this->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/community')
            ->missing('courts')
        );
});

test('authenticated members can open club corners via the tab query', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'nl', 'tab' => 'courts']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/community')
            ->where('tab', 'courts')
        );
});
