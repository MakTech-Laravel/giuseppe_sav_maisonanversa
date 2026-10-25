<?php

use App\Enums\SessionNotice;
use App\Enums\SessionParticipantStatus;
use App\Mail\SessionNoticeMail;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Queue::fake();

    $this->host = User::factory()->create(['locale' => 'nl']);
    $this->player = User::factory()->create(['locale' => 'en']);

    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'club_id' => Club::factory()->create(['name' => 'Padel Club Zuid'])->id,
        'starts_at' => now()->subMinutes(120),
        'duration_minutes' => 90,
    ]);

    foreach ([$this->host, $this->player] as $user) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $this->session->id,
            'user_id' => $user->id,
        ]);
    }
});

function matchCardMailTo(User $user): ?SessionNoticeMail
{
    return Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === SessionNotice::MatchCardReady && $mail->hasTo($user->email),
    )->first();
}

/**
 * @return list<string>
 */
function matchCardNotices(User $user): array
{
    return $user->userNotifications()
        ->with('notification')
        ->get()
        ->map(fn ($row): string => $row->notification->data['notice'])
        ->filter(fn (string $notice): bool => $notice === SessionNotice::MatchCardReady->value)
        ->values()
        ->all();
}

test('every player hears once that the match card is ready after the session ends', function () {
    $this->artisan('sessions:send-match-cards')->assertSuccessful();
    $this->artisan('sessions:send-match-cards')->assertSuccessful();

    $notification = $this->player->userNotifications()->with('notification')->first()->notification;

    expect(matchCardNotices($this->host))->toHaveCount(1)
        ->and(matchCardNotices($this->player))->toHaveCount(1)
        ->and($notification->message)->toBe('Your match card from Padel Club Zuid is ready.')
        ->and($notification->data['link_url'])->toBe(route('community.sessions.show', ['locale' => 'en', 'communitySession' => $this->session->id]))
        ->and(matchCardMailTo($this->host))->not->toBeNull()
        ->and(matchCardMailTo($this->player))->not->toBeNull()
        ->and(Mail::queued(SessionNoticeMail::class))->toHaveCount(2)
        ->and($this->session->fresh()->match_card_sent_at)->not->toBeNull();
});

test('the mail opens the session page and downloads through a signed link', function () {
    $this->artisan('sessions:send-match-cards')->assertSuccessful();

    $dutch = matchCardMailTo($this->host)->render();
    $english = matchCardMailTo($this->player)->render();
    $sessionUrl = route('community.sessions.show', ['locale' => 'en', 'communitySession' => $this->session->id]);
    $download = route('maison.sessions.card', [
        'locale' => 'en',
        'shareToken' => $this->session->share_token,
        'format' => 'story',
        'download' => 1,
    ]);

    preg_match('/href="([^"]*card\/story\.png[^"]*)"/', $english, $match);
    $signedDownload = html_entity_decode($match[1] ?? '');

    expect($dutch)->toContain('Deel uw kaart')
        ->and($dutch)->toContain('Bedankt voor de sessie bij Padel Club Zuid.')
        ->and($english)->toContain('Share your card')
        ->and($english)->toContain('href="'.$sessionUrl.'"')
        ->and($signedDownload)->toStartWith($download.'&expires=')
        ->and($signedDownload)->toContain('signature=');

    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        file_put_contents($process->command[2], 'PNG');

        return Process::result();
    });

    $this->get($signedDownload)->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('players who turned session mails off still get the in-app notice', function () {
    $this->player->update(['session_emails' => false]);

    $this->artisan('sessions:send-match-cards')->assertSuccessful();

    expect(matchCardNotices($this->player))->toHaveCount(1)
        ->and(matchCardMailTo($this->player))->toBeNull()
        ->and(matchCardMailTo($this->host))->not->toBeNull();
});

test('no match card is sent for sessions that do not qualify', function (Closure $setup) {
    $setup($this->session);

    $this->artisan('sessions:send-match-cards')->assertSuccessful();

    expect(Mail::queued(SessionNoticeMail::class))->toHaveCount(0)
        ->and(matchCardNotices($this->host))->toBe([]);
})->with([
    'a single confirmed player' => [fn (CommunitySession $session) => $session->participantRecords()
        ->where('user_id', '!=', $session->host_id)
        ->update(['status' => SessionParticipantStatus::Pending->value])],
    'cancelled' => [fn (CommunitySession $session) => $session->update(['cancelled_at' => now()])],
    'already sent' => [fn (CommunitySession $session) => $session->update(['match_card_sent_at' => now()->subHour()])],
    'not yet ended' => [fn (CommunitySession $session) => $session->update(['starts_at' => now()->subMinutes(30)])],
    'ended long ago' => [fn (CommunitySession $session) => $session->update(['starts_at' => now()->subDays(5)])],
]);
