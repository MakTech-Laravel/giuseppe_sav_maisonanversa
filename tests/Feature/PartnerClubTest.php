<?php

use App\Enums\CornerPipelineStatus;
use App\Models\Club;
use Database\Seeders\ClubSeeder;

test('corner page exposes dynamic clubs and form options', function () {
    $this->seed(ClubSeeder::class);

    $this->get(localized('maison.corner'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs')
            ->has('cornerFormOptions.court_options')
            ->has('cornerFormOptions.format_options')
            ->where('clubs.0.city', fn ($city) => is_string($city) && $city !== '')
            ->where('clubs.0.status', fn ($status) => in_array($status, [
                CornerPipelineStatus::InDiscussion->value,
                CornerPipelineStatus::Open->value,
                CornerPipelineStatus::Active->value,
            ], true)));
});

test('corner page only lists clubs marked for the corner page', function () {
    Club::factory()->cornerPage()->create([
        'city' => 'Antwerpen',
        'name' => 'Antwerpen Corner',
        'corner_pipeline_status' => CornerPipelineStatus::InDiscussion,
    ]);

    Club::factory()->create([
        'city' => 'Hidden City',
        'show_on_corner_page' => false,
    ]);

    $this->get(localized('maison.corner'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.city', 'Antwerpen')
            ->where('clubs.0.status', CornerPipelineStatus::InDiscussion->value)
            ->where('clubs.0.status_label', CornerPipelineStatus::InDiscussion->label()));
});
