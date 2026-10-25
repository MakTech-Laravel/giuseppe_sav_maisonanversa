<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Enums\SessionScoreStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\CommunitySessionScore;
use App\Models\Order;
use App\Models\User;
use App\Services\Community\MatchCardRenderer;
use App\Support\MatchCardPresenter;
use App\Support\SessionFeed;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Storage::fake('local');

    $this->host = User::factory()->create(['first_name' => 'Sofia', 'last_name' => 'Verbeke']);
    $this->player = User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Peeters']);

    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => Carbon::parse('2026-10-06 12:30'),
    ]);

    foreach ([$this->host, $this->player] as $user) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $this->session->id,
            'user_id' => $user->id,
        ]);
    }
});

function fakeMatchCardProcess(): void
{
    Process::fake(function (PendingProcess $process) {
        file_put_contents($process->command[2], 'PNG:'.sha1((string) $process->input));

        return Process::result();
    });
}

function shareUrlFor(CommunitySession $session, array $parameters = []): string
{
    return route('maison.sessions.share', ['locale' => 'nl', 'shareToken' => $session->share_token, ...$parameters]);
}

function cardUrlFor(CommunitySession $session, string $format = 'post', array $parameters = []): string
{
    return route('maison.sessions.card', [
        'locale' => 'nl',
        'shareToken' => $session->share_token,
        'format' => $format,
        ...$parameters,
    ]);
}

test('every session gets a hard to guess share token', function () {
    $other = CommunitySession::factory()->create();

    expect($this->session->share_token)->toHaveLength(32)
        ->and($this->session->share_token)->not->toBe($other->share_token);
});

test('the card prints initials and first names, organiser first, never last names', function () {
    $payload = app(MatchCardPresenter::class)->payload($this->session, 'nl', 'story');

    expect($payload['players'])->toBe([
        ['initials' => 'SV', 'first_name' => 'Sofia', 'edition' => null],
        ['initials' => 'JP', 'first_name' => 'Jan', 'edition' => null],
    ])
        ->and(json_encode($payload))->not->toContain('Verbeke')
        ->and(json_encode($payload))->not->toContain('Peeters')
        ->and($payload['width'])->toBe(1080)
        ->and($payload['height'])->toBe(1920);
});

test('members who hide their first name show initials only', function () {
    $this->player->update(['show_first_name_on_cards' => false]);

    $nl = app(MatchCardPresenter::class)->payload($this->session->fresh(), 'nl', 'story');
    $en = app(MatchCardPresenter::class)->payload($this->session->fresh(), 'en', 'story');

    expect($nl['players'][1])->toBe(['initials' => 'JP', 'first_name' => null, 'edition' => null])
        ->and($en['players'][1]['first_name'])->toBeNull()
        ->and($en['players'][1]['initials'])->toBe('JP');
});

test('the share page hides the first name when privacy is off', function (string $locale) {
    $this->player->update(['show_first_name_on_cards' => false]);

    $this->get(shareUrlFor($this->session, ['locale' => $locale]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('players.1.first_name', null)
            ->where('players.1.initials', 'JP')
            ->where('players.0.first_name', 'Sofia')
        );
})->with(['nl', 'en', 'fr']);

test('for_chat_only seats are excluded from the match card roster', function () {
    $staff = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'Staff']);

    CommunitySessionParticipant::factory()->forChatOnly()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $staff->id,
    ]);

    $players = app(MatchCardPresenter::class)->players($this->session->fresh());

    expect(collect($players)->pluck('initials')->all())->toBe(['SV', 'JP'])
        ->and(count($players))->toBe(2);
});

test('session feed and match card share the same initials', function () {
    $user = User::factory()->create([
        'name' => 'User Something',
        'first_name' => 'User',
        'last_name' => 'User',
    ]);

    expect(SessionFeed::player($user)['initials'])->toBe('UU')
        ->and(app(MatchCardPresenter::class)->player($user)['initials'])->toBe('UU');
});

test('founding circle members carry their edition number', function () {
    $this->player->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    Order::factory()->forUser($this->player)->create([
        'edition_number' => 12,
        'status' => OrderStatus::Paid,
    ]);

    $players = app(MatchCardPresenter::class)->players($this->session->fresh());

    expect($players[1]['edition'])->toBe('No. 012');
});

test('the result only appears once the official score is approved', function () {
    $presenter = app(MatchCardPresenter::class);

    CommunitySessionScore::factory()->create(['community_session_id' => $this->session->id]);

    expect($presenter->payload($this->session->fresh(), 'nl', 'post')['result'])->toBeNull();

    CommunitySessionScore::query()->update(['status' => SessionScoreStatus::Approved->value, 'reviewed_at' => now()]);

    $session = $this->session->fresh();

    expect($presenter->payload($session, 'nl', 'post')['result'])
        ->toBe(['label' => 'Uitslag', 'line' => $session->officialScore->setsLine()]);
});

test('the date line follows the reader language', function (string $locale, string $expected) {
    $payload = app(MatchCardPresenter::class)->payload($this->session, $locale, 'post');

    expect($payload['date_line'])->toBe($expected)
        ->and($payload['locale'])->toBe($locale);
})->with([
    ['nl', 'Dinsdag 6 oktober · 12.30'],
    ['en', 'Tuesday 6 October · 12.30'],
    ['fr', 'Mardi 6 octobre · 12h30'],
]);

test('the renderer caches each card and renders again when the result changes', function () {
    fakeMatchCardProcess();
    $renderer = app(MatchCardRenderer::class);

    $first = $renderer->render($this->session, 'nl', 'post');
    $again = $renderer->render($this->session->fresh(), 'nl', 'post');

    Process::assertRanTimes(fn (PendingProcess $process) => true, 1);
    expect($again)->toBe($first)
        ->and(Storage::disk('local')->files('match-cards/'.$this->session->id))->toHaveCount(1);

    CommunitySessionScore::factory()->approved()->create(['community_session_id' => $this->session->id]);

    $scored = $renderer->render($this->session->fresh(), 'nl', 'post');

    Process::assertRanTimes(fn (PendingProcess $process) => true, 2);
    expect($scored['hash'])->not->toBe($first['hash'])
        ->and(Storage::disk('local')->files('match-cards/'.$this->session->id))
        ->toBe(['match-cards/'.$this->session->id.'/post-nl-'.$scored['hash'].'.png']);
});

test('a failed render surfaces as an exception', function () {
    Process::fake(fn () => Process::result(errorOutput: 'boom', exitCode: 1));

    app(MatchCardRenderer::class)->render($this->session, 'nl', 'story');
})->throws(RuntimeException::class, 'boom');

test('the real renderer draws story and post cards at their exact sizes', function (string $format, int $width, int $height) {
    $result = app(MatchCardRenderer::class)->render($this->session, 'fr', $format);
    $size = getimagesizefromstring($result['png']);

    expect($size[0])->toBe($width)
        ->and($size[1])->toBe($height)
        ->and($size['mime'])->toBe('image/png');
})->with([
    ['story', 1080, 1920],
    ['post', 1080, 1350],
])->skip(
    fn () => ! is_dir(base_path('node_modules/satori')) || ! Process::run([config('maison.match_cards.node_binary'), '--version'])->successful(),
    'Node with satori is not available',
);

test('guests see who plays and a link to copy, but not the card', function () {
    $this->get(shareUrlFor($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/share')
            ->where('session.id', (string) $this->session->id)
            ->has('players', 2)
            ->where('players.0.first_name', 'Sofia')
            ->where('result', null)
            ->where('card', null)
            ->where('shareUrl', shareUrlFor($this->session))
            ->where('memberUrl', null)
        )
        ->assertSee(e(cardUrlFor($this->session)), false)
        ->assertDontSee('Verbeke');
});

test('guests see how many places are still open and can ask to join', function () {
    $this->session->update(['starts_at' => now()->addDays(3)]);
    CommunitySessionParticipant::factory()->pending()->create([
        'community_session_id' => $this->session->id,
    ]);

    $this->get(shareUrlFor($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.capacity', 4)
            ->where('session.participants_count', 2)
            ->where('session.pending_count', 1)
            ->where('session.open_slots', 1)
            ->where('session.can_join', true)
        );
});

test('players open the share page with the card to share and download', function (string $who) {
    $this->actingAs($this->{$who})
        ->get(shareUrlFor($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('card.share_url', shareUrlFor($this->session))
            ->where('card.post_url', cardUrlFor($this->session))
            ->where('card.download_story_url', cardUrlFor($this->session, 'story', ['download' => 1]))
            ->where('card.has_result', false)
        );
})->with(['host', 'player']);

test('members outside the session get the roster, a link back and no card', function () {
    $this->actingAs(User::factory()->create())
        ->get(shareUrlFor($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('card', null)
            ->where('shareUrl', shareUrlFor($this->session))
            ->where(
                'memberUrl',
                route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]),
            )
        );
});

test('pending players do not get the card yet', function () {
    $pending = User::factory()->create();
    CommunitySessionParticipant::factory()->pending()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $pending->id,
    ]);

    $this->actingAs($pending)
        ->get(shareUrlFor($this->session))
        ->assertInertia(fn (Assert $page) => $page->where('card', null));
});

test('an unknown share token is not found', function () {
    $this->get(route('maison.sessions.share', ['locale' => 'nl', 'shareToken' => str_repeat('a', 32)]))
        ->assertNotFound();
});

test('cancelled sessions keep their page but lose the card', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->host)
        ->get(shareUrlFor($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('card', null)
            ->where('shareUrl', null)
        );

    $this->get(cardUrlFor($this->session))->assertNotFound();
});

test('the card image stays public for link previews', function () {
    fakeMatchCardProcess();

    $this->get(cardUrlFor($this->session, 'story'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeaderMissing('Content-Disposition');
});

test('only players download the card, with a dated filename', function () {
    fakeMatchCardProcess();

    $this->get(cardUrlFor($this->session, 'post', ['download' => 1]))->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(cardUrlFor($this->session, 'post', ['download' => 1]))
        ->assertForbidden();

    $this->actingAs($this->player)
        ->get(cardUrlFor($this->session, 'post', ['download' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="maison-anversa-padel-2026-10-06-post.png"');
});

test('a signed download link works without signing in', function () {
    fakeMatchCardProcess();

    $signed = URL::temporarySignedRoute('maison.sessions.card', now()->addDay(), [
        'locale' => 'nl',
        'shareToken' => $this->session->share_token,
        'format' => 'story',
        'download' => 1,
    ]);

    $this->get($signed)
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="maison-anversa-padel-2026-10-06-story.png"');

    $this->get($signed.'x')->assertForbidden();
});

test('an unchanged card answers 304 to a matching etag', function () {
    fakeMatchCardProcess();

    $etag = $this->get(cardUrlFor($this->session))->headers->get('ETag');

    $this->withHeaders(['If-None-Match' => $etag])
        ->get(cardUrlFor($this->session))
        ->assertStatus(304);
});

test('unknown card formats are not routed', function () {
    $this->get(route('maison.sessions.share', ['locale' => 'nl', 'shareToken' => $this->session->share_token]).'/card/square.png')
        ->assertNotFound();
});

test('the member session page offers the match card to players', function () {
    $this->actingAs($this->player)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('matchCard.share_url', shareUrlFor($this->session))
            ->where('matchCard.story_url', cardUrlFor($this->session, 'story'))
            ->where('shareUrl', shareUrlFor($this->session))
        );

    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->player)
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('matchCard', null)
            ->where('shareUrl', null)
        );
});

test('members outside the session only get the link on the session page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('matchCard', null)
            ->where('shareUrl', shareUrlFor($this->session))
        );
});

test('members choose whether their first name appears on match cards', function () {
    $this->actingAs($this->player)
        ->get(localized('member.profile'))
        ->assertInertia(fn (Assert $page) => $page->where('showFirstNameOnCards', true));

    $this->actingAs($this->player)
        ->patch(localized('member.profile.update'), [
            'first_name' => 'Jan',
            'last_name' => 'Peeters',
            'email' => $this->player->email,
            'gender' => $this->player->gender->value,
            'show_first_name_on_cards' => '0',
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect($this->player->fresh()->show_first_name_on_cards)->toBeFalse();
});
