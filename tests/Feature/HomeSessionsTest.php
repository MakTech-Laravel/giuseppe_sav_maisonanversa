<?php

use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page includes upcoming joinable sessions for guests', function () {
    $host = User::factory()->create(['name' => 'Hidden Host']);
    $open = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDays(2),
        'capacity' => 4,
    ]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $open->id,
        'user_id' => $host->id,
    ]);

    CommunitySession::factory()->cancelled()->create([
        'starts_at' => now()->addDays(3),
    ]);
    CommunitySession::factory()->past()->create();

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->has('upcomingSessions', 1)
            ->where('upcomingSessions.0.id', (string) $open->id)
            ->where('upcomingSessions.0.can_join', true)
            ->where('upcomingSessions.0.is_host', false)
            ->where('upcomingSessions.0.is_joined', false)
            ->where('upcomingSessions.0.host.name', '')
            ->where('upcomingSessions.0.players.0.name', '')
            ->where('upcomingSessions.0.players.0.initials', 'HI')
            ->where('openSessionsThisWeek', 1)
        );
});

test('the home page omits full sessions and caps the list at three', function () {
    CommunitySession::factory()->count(5)->create([
        'starts_at' => now()->addDay(),
        'capacity' => 4,
    ]);

    $full = CommunitySession::factory()->create([
        'starts_at' => now()->addHours(6),
        'capacity' => 2,
    ]);
    $players = User::factory()->count(2)->create();
    foreach ($players as $player) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $full->id,
            'user_id' => $player->id,
        ]);
    }

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->has('upcomingSessions', 3)
            ->where('openSessionsThisWeek', 5)
            ->where(
                'upcomingSessions',
                fn ($sessions): bool => collect($sessions)
                    ->pluck('id')
                    ->doesntContain((string) $full->id),
            )
        );
});

test('openSessionsThisWeek only counts joinable sessions in the next seven days', function () {
    CommunitySession::factory()->create([
        'starts_at' => now()->addDays(3),
        'capacity' => 4,
    ]);
    CommunitySession::factory()->create([
        'starts_at' => now()->addDays(10),
        'capacity' => 4,
    ]);

    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('openSessionsThisWeek', 1)
            ->has('upcomingSessions', 2)
        );
});

test('verified members can join a session listed on the home page', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $club = Club::factory()->create();

    $session = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'club_id' => $club->id,
        'starts_at' => now()->addDay(),
        'capacity' => 4,
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);

    $this->actingAs($guest)
        ->post(route('community.sessions.join', [
            'locale' => 'nl',
            'communitySession' => $session->id,
        ]))
        ->assertRedirect();

    expect($session->participants()->where('user_id', $guest->id)->exists())->toBeTrue();
});

test('the home sessions section matches the PDF placement copy and card', function () {
    $homeSessions = file_get_contents(resource_path('js/components/maison/home/home-sessions.tsx'));
    $homeCard = file_get_contents(resource_path('js/components/maison/home/home-session-card.tsx'));
    $homePage = file_get_contents(resource_path('js/pages/maison/home.tsx'));
    $sessionCard = file_get_contents(resource_path('js/components/maison/community/sessions/session-card.tsx'));

    expect($homePage)
        ->toContain('HomeSessions')
        ->toContain('upcomingSessions')
        ->toContain('openSessionsThisWeek')
        ->toContain('<HomeStory />')
        ->toContain('<HomeSessions');

    expect(strpos($homePage, '<HomeStory />'))
        ->toBeLessThan(strpos($homePage, '<HomeSessions'));

    expect(strpos($homePage, '<HomeSessions'))
        ->toBeLessThan(strpos($homePage, '<HomeAntwerp'));

    expect($homeSessions)
        ->toContain('HomeSessionCard')
        ->toContain('Na de koffie, de baan.')
        ->toContain('Community · Sessies')
        ->toContain('+ Plan een sessie')
        ->toContain('Bekijk alle sessies')
        ->toContain('useResumePendingSessionJoin')
        ->not->toContain("from '@/components/maison/community/sessions/session-card'");

    expect($homeCard)
        ->toContain('Doe mee')
        ->toContain('Je speelt mee ✓')
        ->toContain('storePendingSessionJoin')
        ->toContain("openAuth('login')");

    expect($sessionCard)
        ->toContain('storePendingSessionJoin')
        ->toContain('useOptionalShellActions');
});
