<?php

use App\Models\Club;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated members receive published club corners on the community page', function () {
    $user = User::factory()->create();

    Club::factory()->publishedCorner()->create([
        'name' => 'Padel Club Antwerpen',
        'corner_title' => 'Padel Club Antwerpen',
        'corner_body' => 'Founding Club Corner',
        'corner_location' => 'Antwerpen, België',
    ]);

    Club::factory()->create([
        'name' => 'Hidden Court',
        'has_corner' => true,
        'corner_published' => false,
        'corner_title' => 'Hidden Court',
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

test('club corners without coordinates are marked coming soon', function () {
    $user = User::factory()->create();

    Club::factory()->comingSoonCorner()->create([
        'name' => 'Amsterdam Padel Club',
        'corner_title' => 'Amsterdam Padel Club',
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

test('community club corners are translated for the request locale', function () {
    fakeDeepLTranslations();

    $user = User::factory()->create();

    Club::factory()->publishedCorner()->create([
        'name' => 'Padel Club Antwerpen',
        'corner_title' => 'Padel Club Antwerpen',
        'corner_body' => 'Founding Club Corner',
        'corner_location' => 'Antwerpen, België',
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
    Club::factory()->publishedCorner()->create();

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
