<?php

use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;

test('session share OG description includes club date and free places', function () {
    $host = User::factory()->create();
    $club = Club::factory()->create(['name' => 'Royal Antwerp TC']);
    $session = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'club_id' => $club->id,
        'capacity' => 4,
        'starts_at' => now()->addDays(2)->setTime(10, 30),
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);

    $this->get(route('maison.sessions.share', [
        'locale' => 'nl',
        'shareToken' => $session->share_token,
    ]))
        ->assertOk()
        ->assertInertia(function ($page) use ($club): void {
            $seo = $page->toArray()['props']['seo'];

            expect($seo['description'])
                ->toContain($club->name)
                ->toContain('plaatsen vrij');
        });
});
