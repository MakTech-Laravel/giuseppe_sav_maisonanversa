<?php

use App\Enums\SessionLifecycle;
use App\Enums\SessionMatchFormat;
use App\Enums\SessionMatchType;
use App\Enums\SessionNotice;
use App\Enums\SessionParticipantStatus;
use App\Enums\SessionScoreStatus;
use App\Mail\SessionNoticeMail;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\CommunitySessionScore;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();
    Queue::fake();

    $this->host = User::factory()->create(['name' => 'Host Name', 'locale' => '']);
    $this->alice = User::factory()->create(['name' => 'Alice', 'locale' => 'fr']);
    $this->bob = User::factory()->create(['name' => 'Bob', 'locale' => 'en']);
    $this->carol = User::factory()->create(['name' => 'Carol']);

    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'capacity' => 4,
        'starts_at' => now()->subHours(3),
        'duration_minutes' => 90,
    ]);

    foreach ([$this->host, $this->alice, $this->bob, $this->carol] as $player) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $this->session->id,
            'user_id' => $player->id,
        ]);
    }
});

/**
 * Sets are [games A, games B] with optional [tie-break A, tie-break B].
 *
 * @param  list<User>  $teamA
 * @param  list<User>  $teamB
 * @param  list<array{0: int, 1: int, 2?: int, 3?: int}>  $sets
 * @return array{match_type: string, format: string, team_a: list<int>, team_b: list<int>, sets: list<array<string, int>>}
 */
function scorePayload(array $teamA, array $teamB, array $sets = [[6, 4], [6, 3]], string $format = 'best_of_3'): array
{
    return [
        'match_type' => count($teamA) === 1 ? 'singles' : 'doubles',
        'format' => $format,
        'team_a' => array_map(fn (User $user): int => $user->id, $teamA),
        'team_b' => array_map(fn (User $user): int => $user->id, $teamB),
        'sets' => array_map(fn (array $set): array => array_filter([
            'a' => $set[0],
            'b' => $set[1],
            'tiebreak_a' => $set[2] ?? null,
            'tiebreak_b' => $set[3] ?? null,
        ], fn (?int $value): bool => $value !== null), $sets),
    ];
}

/**
 * The default padel line-up: Alice and Bob against the host and Carol.
 *
 * @param  list<array{0: int, 1: int, 2?: int, 3?: int}>  $sets
 */
function doublesPayload(array $sets = [[6, 4], [6, 3]], string $format = 'best_of_3'): array
{
    return scorePayload([test()->alice, test()->bob], [test()->host, test()->carol], $sets, $format);
}

function scoreRoute(string $name, CommunitySession $session, ?CommunitySessionScore $score = null): string
{
    return route($name, array_filter([
        'locale' => 'nl',
        'communitySession' => $session->id,
        'score' => $score?->id,
    ]));
}

function submitScore(User $user, CommunitySession $session, array $payload): TestResponse
{
    return test()->actingAs($user)->post(scoreRoute('community.sessions.scores.store', $session), $payload);
}

/**
 * @return list<string>
 */
function scoreboardNotices(User $user): array
{
    return $user->userNotifications()
        ->with('notification')
        ->orderBy('id')
        ->get()
        ->map(fn ($row): string => $row->notification->data['notice'])
        ->all();
}

test('the session is scheduled before its end time and held right after it', function () {
    $upcoming = CommunitySession::factory()->create(['starts_at' => now()->addHour(), 'duration_minutes' => 60]);

    expect($upcoming->lifecycle())->toBe(SessionLifecycle::Scheduled);

    $this->travelTo($upcoming->ends_at->copy()->addSecond());

    expect($upcoming->fresh()->lifecycle())->toBe(SessionLifecycle::Held)
        ->and($upcoming->fresh()->isScoreWindowOpen())->toBeTrue();
});

test('scores cannot be submitted before the session is held', function () {
    $upcoming = CommunitySession::factory()->create(['host_id' => $this->host->id]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $upcoming->id,
        'user_id' => $this->alice->id,
    ]);

    submitScore($this->alice, $upcoming, scorePayload([$this->alice], [$this->host]))->assertForbidden();

    expect($upcoming->scores()->count())->toBe(0);
});

test('the first valid submission creates the scoreboard as a pending score', function () {
    submitScore($this->alice, $this->session, scorePayload([$this->alice, $this->bob], [$this->host, $this->carol]))
        ->assertRedirect();

    $score = $this->session->scores()->sole();

    expect($score->status)->toBe(SessionScoreStatus::Pending)
        ->and($score->submitted_by)->toBe($this->alice->id)
        ->and($score->team_a)->toBe([$this->alice->id, $this->bob->id])
        ->and($score->match_type)->toBe(SessionMatchType::Doubles)
        ->and($score->format)->toBe(SessionMatchFormat::BestOfThree)
        ->and($score->sets)->toBe([
            ['a' => 6, 'b' => 4, 'tiebreak_a' => null, 'tiebreak_b' => null],
            ['a' => 6, 'b' => 3, 'tiebreak_a' => null, 'tiebreak_b' => null],
        ])
        ->and($this->session->officialScore()->exists())->toBeFalse()
        ->and(scoreboardNotices($this->host))->toBe([SessionNotice::ScoreSubmitted->value]);

    $this->actingAs($this->host)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard.lifecycle', 'held')
            ->where('scoreboard.official', null)
            ->has('scoreboard.submissions', 1)
            ->where('scoreboard.submissions.0.status', 'pending')
            ->where('scoreboard.can_review', true)
            ->where('session.lifecycle', 'held')
        );
});

test('participants can only submit within three days after the session ends; the host can still enter it later', function () {
    $this->travelTo($this->session->ends_at->copy()->addDays(3)->addMinute());

    submitScore($this->alice, $this->session, doublesPayload())->assertForbidden();

    submitScore($this->host, $this->session, doublesPayload())->assertRedirect();

    expect($this->session->officialScore()->first()?->submitted_by)->toBe($this->host->id);
});

test('pending, rejected and outside members cannot submit a score', function () {
    $pending = User::factory()->create();
    $rejected = User::factory()->create();
    $stranger = User::factory()->create();
    CommunitySessionParticipant::factory()->pending()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $pending->id,
    ]);
    CommunitySessionParticipant::factory()->rejected()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $rejected->id,
    ]);

    foreach ([$pending, $rejected, $stranger] as $user) {
        submitScore($user, $this->session, doublesPayload())->assertForbidden();
    }

    expect($this->session->scores()->count())->toBe(0);
});

test('teams must hold confirmed players only and cannot overlap', function () {
    $outsider = User::factory()->create();

    submitScore($this->alice, $this->session, scorePayload([$this->alice, $this->bob], [$this->bob, $this->carol]))
        ->assertSessionHasErrors('team_b');

    submitScore($this->alice, $this->session, scorePayload([$this->alice, $this->bob], [$outsider, $this->carol]))
        ->assertSessionHasErrors('team_b.0');

    submitScore($this->alice, $this->session, [...doublesPayload(), 'sets' => []])
        ->assertSessionHasErrors('sets');

    expect($this->session->scores()->count())->toBe(0);
});

test('later submissions stay pending and never replace the official score until approved', function () {
    $official = CommunitySessionScore::factory()->approved()->create([
        'community_session_id' => $this->session->id,
        'submitted_by' => $this->alice->id,
        'team_a' => [$this->alice->id, $this->bob->id],
        'team_b' => [$this->host->id, $this->carol->id],
    ]);

    submitScore($this->bob, $this->session, doublesPayload([[3, 6], [4, 6]]))
        ->assertRedirect();

    $pending = $this->session->scores()->where('status', SessionScoreStatus::Pending->value)->sole();

    expect($this->session->officialScore()->first()->id)->toBe($official->id);

    $this->actingAs($this->host)
        ->patch(scoreRoute('community.sessions.scores.approve', $this->session, $pending))
        ->assertRedirect();

    expect($this->session->officialScore()->first()->id)->toBe($pending->id)
        ->and($official->fresh()->status)->toBe(SessionScoreStatus::Superseded)
        ->and($this->session->scores()->where('status', SessionScoreStatus::Approved->value)->count())->toBe(1);
});

test('resubmitting replaces the member\'s own pending score but keeps it in the history', function () {
    submitScore($this->alice, $this->session, doublesPayload())->assertRedirect();
    submitScore($this->alice, $this->session, doublesPayload([[7, 5], [6, 2]]))->assertRedirect();

    expect($this->session->scores()->count())->toBe(2)
        ->and($this->session->scores()->where('status', SessionScoreStatus::Pending->value)->count())->toBe(1)
        ->and($this->session->scores()->where('status', SessionScoreStatus::Superseded->value)->count())->toBe(1);
});

test('the host rejecting a score tells the submitter and leaves no official result', function () {
    submitScore($this->alice, $this->session, doublesPayload())->assertRedirect();
    $score = $this->session->scores()->sole();

    $this->actingAs($this->host)
        ->patch(scoreRoute('community.sessions.scores.reject', $this->session, $score))
        ->assertRedirect();

    expect($score->fresh()->status)->toBe(SessionScoreStatus::Rejected)
        ->and($this->session->officialScore()->exists())->toBeFalse()
        ->and(scoreboardNotices($this->alice))->toBe([SessionNotice::ScoreRejected->value]);
});

test('only the host can approve scores and lock, unlock or finish the scoreboard', function () {
    submitScore($this->alice, $this->session, doublesPayload())->assertRedirect();
    $score = $this->session->scores()->sole();

    $this->actingAs($this->bob)->patch(scoreRoute('community.sessions.scores.approve', $this->session, $score))->assertForbidden();
    $this->actingAs($this->bob)->patch(scoreRoute('community.sessions.scores.reject', $this->session, $score))->assertForbidden();
    $this->actingAs($this->bob)->patch(scoreRoute('community.sessions.scoreboard.lock', $this->session))->assertForbidden();
    $this->actingAs($this->bob)->patch(scoreRoute('community.sessions.scoreboard.unlock', $this->session))->assertForbidden();
    $this->actingAs($this->bob)->patch(scoreRoute('community.sessions.finish', $this->session))->assertForbidden();

    expect($score->fresh()->status)->toBe(SessionScoreStatus::Pending)
        ->and($this->session->fresh()->isScoreboardLocked())->toBeFalse();
});

test('a locked scoreboard rejects direct participant submissions until the host unlocks it', function () {
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $this->session))->assertRedirect();

    submitScore($this->alice, $this->session, doublesPayload())->assertForbidden();
    submitScore($this->host, $this->session, doublesPayload())->assertForbidden();

    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.unlock', $this->session))->assertRedirect();

    submitScore($this->alice, $this->session, doublesPayload())->assertRedirect();

    expect($this->session->scores()->count())->toBe(1);
});

test('a score from another session cannot be reviewed through this session', function () {
    $other = CommunitySessionScore::factory()->create();

    $this->actingAs($this->host)
        ->patch(scoreRoute('community.sessions.scores.approve', $this->session, $other))
        ->assertNotFound();
});

test('a session without an approved score, with pending scores, or unlocked does not finish', function () {
    $finish = fn () => $this->actingAs($this->host)->patch(scoreRoute('community.sessions.finish', $this->session));

    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $this->session))->assertRedirect();
    $finish()->assertStatus(422);

    CommunitySessionScore::factory()->approved()->create(['community_session_id' => $this->session->id]);
    CommunitySessionScore::factory()->create(['community_session_id' => $this->session->id]);
    $finish()->assertStatus(422);

    $this->session->scores()->where('status', SessionScoreStatus::Pending->value)->update(['status' => SessionScoreStatus::Rejected->value]);
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.unlock', $this->session))->assertRedirect();
    $finish()->assertStatus(422);

    expect($this->session->fresh()->isFinished())->toBeFalse()
        ->and($this->session->fresh()->lifecycle())->toBe(SessionLifecycle::Held);
});

test('finishing an approved, locked scoreboard closes the session and freezes it', function () {
    submitScore($this->host, $this->session, scorePayload([$this->host, $this->alice], [$this->bob, $this->carol]))
        ->assertRedirect();

    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $this->session))->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.finish', $this->session))->assertRedirect();

    $session = $this->session->fresh();

    expect($session->isFinished())->toBeTrue()
        ->and($session->lifecycle())->toBe(SessionLifecycle::Finished);

    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.unlock', $session))->assertForbidden();
    submitScore($this->host, $session, doublesPayload())->assertForbidden();

    expect($session->scores()->count())->toBe(1);
});

test('closing the session sends the result to both teams and the host, in-app and by mail, in each recipient\'s language', function () {
    $this->carol->update(['session_emails' => false]);

    submitScore($this->host, $this->session, scorePayload([$this->host, $this->alice], [$this->bob, $this->carol]))
        ->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $this->session))->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.finish', $this->session))->assertRedirect();

    foreach ([$this->host, $this->alice, $this->bob, $this->carol] as $player) {
        expect(scoreboardNotices($player))->toContain(SessionNotice::SessionFinished->value);
    }

    $finishedMail = fn (User $user) => Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === SessionNotice::SessionFinished && $mail->hasTo($user->email),
    );

    expect($finishedMail($this->host))->toHaveCount(1)
        ->and($finishedMail($this->alice))->toHaveCount(1)
        ->and($finishedMail($this->bob))->toHaveCount(1)
        ->and($finishedMail($this->carol))->toHaveCount(0)
        ->and($finishedMail($this->alice)->first()->locale)->toBe('fr')
        ->and($finishedMail($this->bob)->first()->locale)->toBe('en')
        ->and($finishedMail($this->host)->first()->locale)->toBe('en');

    $notice = fn (User $user) => $user->userNotifications()
        ->with('notification')
        ->get()
        ->first(fn ($row): bool => $row->notification->data['notice'] === SessionNotice::SessionFinished->value)
        ->notification;

    expect($notice($this->alice)->title)->toBe('Session clôturée : le résultat')
        ->and($notice($this->alice)->message)->toContain('6-4, 6-3')
        ->and($notice($this->bob)->title)->toBe('Session closed: the result')
        ->and($notice($this->host)->title)->toBe('Session closed: the result');

    $html = $finishedMail($this->bob)->first()->render();

    expect($html)->toContain('Host Name · Alice')
        ->and($html)->toContain('Bob · Carol')
        ->and($html)->toContain('6-4, 6-3')
        ->and($html)->toContain('Format')
        ->and($html)->toContain('Doubles · Best of 3 sets')
        ->and($html)->toContain('Team A (Host Name · Alice)');
});

test('approving a score tells every player except the host', function () {
    submitScore($this->alice, $this->session, doublesPayload())->assertRedirect();
    $score = $this->session->scores()->sole();

    $this->actingAs($this->host)
        ->patch(scoreRoute('community.sessions.scores.approve', $this->session, $score))
        ->assertRedirect();

    expect(scoreboardNotices($this->alice))->toBe([SessionNotice::ScoreApproved->value])
        ->and(scoreboardNotices($this->bob))->toBe([SessionNotice::ScoreApproved->value])
        ->and(scoreboardNotices($this->host))->toBe([SessionNotice::ScoreSubmitted->value]);
});

test('the scoreboard is hidden while the session is still ahead', function () {
    $upcoming = CommunitySession::factory()->create();

    $this->actingAs($this->host)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $upcoming->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard', null)
            ->where('session.lifecycle', 'scheduled')
        );
});

test('a confirmed participant status stays separate from pending in the scoreboard player list', function () {
    $pending = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $pending->id,
        'status' => SessionParticipantStatus::Pending,
    ]);

    $this->actingAs($this->alice)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('scoreboard.players', 4)
            ->where('scoreboard.can_submit', true)
            ->where('scoreboard.can_review', false)
        );
});

/**
 * A held tennis session with the given confirmed players; the first one hosts.
 */
function heldTennisSession(User ...$players): CommunitySession
{
    $session = CommunitySession::factory()->tennis()->create([
        'host_id' => $players[0]->id,
        'capacity' => 4,
        'starts_at' => now()->subHours(3),
        'duration_minutes' => 90,
    ]);

    foreach ($players as $player) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $session->id,
            'user_id' => $player->id,
        ]);
    }

    return $session;
}

test('padel results are always doubles with two players per team', function () {
    submitScore($this->alice, $this->session, scorePayload([$this->alice], [$this->bob]))
        ->assertSessionHasErrors(['match_type' => 'Padel wordt altijd met twee tegen twee gespeeld.']);

    submitScore($this->alice, $this->session, [...scorePayload([$this->alice], [$this->bob]), 'match_type' => 'doubles'])
        ->assertSessionHasErrors(['team_a' => 'Kies twee spelers per team.', 'team_b']);

    expect($this->session->scores()->count())->toBe(0);
});

test('the scoreboard offers padel doubles only and tennis singles or doubles', function () {
    $this->actingAs($this->alice)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard.sport', 'padel')
            ->where('scoreboard.match_types.0.value', 'doubles')
            ->has('scoreboard.match_types', 1)
            ->has('scoreboard.formats', 4)
            ->where('scoreboard.default_format', 'best_of_3')
            ->where('scoreboard.can_record_match', true)
        );

    $tennis = heldTennisSession($this->host, $this->alice);

    $this->actingAs($this->alice)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $tennis->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard.sport', 'tennis')
            ->has('scoreboard.match_types', 2)
            ->where('scoreboard.min_players', 2)
            ->where('scoreboard.can_record_match', true)
        );
});

test('a tennis singles match with a tie-break and a super tie-break is stored with its notation', function () {
    $tennis = heldTennisSession($this->host, $this->alice);

    submitScore($this->alice, $tennis, scorePayload([$this->alice], [$this->host], [[6, 7, 5, 7], [6, 3], [10, 8]], 'super_tiebreak'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $score = $tennis->scores()->sole();

    expect($score->match_type)->toBe(SessionMatchType::Singles)
        ->and($score->format)->toBe(SessionMatchFormat::SuperTiebreak)
        ->and($score->setsLine())->toBe('6-7(5), 6-3, [10-8]')
        ->and($score->winner())->toBe('a');

    $this->actingAs($this->host)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $tennis->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard.submissions.0.match_type', 'singles')
            ->where('scoreboard.submissions.0.format', 'super_tiebreak')
            ->where('scoreboard.submissions.0.sets_line', '6-7(5), 6-3, [10-8]')
            ->where('scoreboard.submissions.0.sets.0.tiebreak_a', 5)
            ->where('scoreboard.submissions.0.sets.2.is_super_tiebreak', true)
        );
});

test('impossible set scores and undecided matches are rejected per set', function () {
    submitScore($this->alice, $this->session, doublesPayload([[6, 5], [6, 3]]))
        ->assertSessionHasErrors(['sets.0' => 'Geen geldige setscore.']);

    submitScore($this->alice, $this->session, doublesPayload([[6, 4, 7, 5], [6, 3]]))
        ->assertSessionHasErrors(['sets.0' => 'Tie-breakpunten horen alleen bij een set die op een tie-break eindigde.']);

    submitScore($this->alice, $this->session, doublesPayload([[6, 4], [3, 6]]))
        ->assertSessionHasErrors(['sets' => 'De wedstrijd is nog niet beslist.']);

    submitScore($this->alice, $this->session, doublesPayload([[6, 4], [6, 3], [6, 2]]))
        ->assertSessionHasErrors(['sets' => 'Na de beslissende set kan geen set meer volgen.']);

    submitScore($this->alice, $this->session, doublesPayload([[6, 4], [3, 6], [10, 9]], 'super_tiebreak'))
        ->assertSessionHasErrors(['sets.2']);

    submitScore($this->alice, $this->session, doublesPayload([[5, 4], [4, 1]], 'short_sets'))
        ->assertSessionHasNoErrors();

    expect($this->session->scores()->count())->toBe(1);
});

test('the result mail labels singles sides as players and names the format', function () {
    $tennis = heldTennisSession($this->host, $this->bob);

    submitScore($this->host, $tennis, scorePayload([$this->bob], [$this->host], [[7, 6, 7, 4]], 'one_set'))
        ->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $tennis))->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.finish', $tennis))->assertRedirect();

    $html = Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === SessionNotice::SessionFinished && $mail->hasTo($this->bob->email),
    )->first()->render();

    expect($html)->toContain('Singles · One set')
        ->and($html)->toContain('Player A')
        ->and($html)->toContain('Player B')
        ->and($html)->toContain('7-6(4)')
        ->and($html)->toContain('Player A (Bob)')
        ->and($html)->not->toContain('Team A');
});

test('a session with too few players for a match can be finished without a result', function () {
    $session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'capacity' => 4,
        'starts_at' => now()->subHours(3),
        'duration_minutes' => 90,
    ]);

    foreach ([$this->host, $this->alice] as $player) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $session->id,
            'user_id' => $player->id,
        ]);
    }

    $this->actingAs($this->host)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scoreboard.can_record_match', false)
            ->where('scoreboard.min_players', 4)
        );

    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.scoreboard.lock', $session))->assertRedirect();
    $this->actingAs($this->host)->patch(scoreRoute('community.sessions.finish', $session))->assertRedirect();

    expect($session->fresh()->isFinished())->toBeTrue()
        ->and($session->officialScore()->exists())->toBeFalse();
});
