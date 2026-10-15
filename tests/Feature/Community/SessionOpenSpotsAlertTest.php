<?php

use App\Enums\SessionNotice;
use App\Enums\SessionParticipantStatus;
use App\Mail\SessionNoticeMail;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();

    $this->host = User::factory()->create(['name' => 'Host Name', 'locale' => 'nl']);
    $this->player = User::factory()->create();

    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'capacity' => 4,
        'starts_at' => now()->addHours(40),
    ]);

    foreach ([$this->host, $this->player] as $user) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $this->session->id,
            'user_id' => $user->id,
        ]);
    }
});

/**
 * @return list<string>
 */
function openSpotsNotices(User $user): array
{
    return $user->userNotifications()
        ->with('notification')
        ->orderBy('id')
        ->get()
        ->map(fn ($row): string => $row->notification->data['notice'])
        ->all();
}

function openSpotsMailTo(User $user): ?SessionNoticeMail
{
    return Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === SessionNotice::OpenSpotsReminder && $mail->hasTo($user->email),
    )->first();
}

test('48 hours ahead the organiser hears once that free places remain', function () {
    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();
    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    $notification = $this->host->userNotifications()->with('notification')->first()->notification;

    expect(openSpotsNotices($this->host))->toBe([SessionNotice::OpenSpotsReminder->value])
        ->and($notification->message)->toContain('nog 2 vrije plaatsen')
        ->and(openSpotsNotices($this->player))->toBe([])
        ->and(openSpotsMailTo($this->host))->not->toBeNull()
        ->and(Mail::queued(SessionNoticeMail::class))->toHaveCount(1)
        ->and($this->session->fresh()->open_spots_alert_sent_at)->not->toBeNull()
        ->and($this->session->fresh()->cancelled_at)->toBeNull();
});

test('the alert mail carries the share link and a cancel button', function () {
    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    $mail = openSpotsMailTo($this->host);
    $html = $mail->render();
    $sessionUrl = route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]);

    expect($html)->toContain('Uw sessie heeft nog 2 vrije plaatsen. Deel ze, of annuleer ze.')
        ->and($html)->toContain('Deel deze link')
        ->and($html)->toContain($this->session->shareUrl('nl'))
        ->and($html)->toContain('Sessie annuleren')
        ->and($html)->toContain(e($sessionUrl.'?cancel=1'));
});

test('pending requests do not count as filled places', function () {
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => User::factory()->create()->id,
        'status' => SessionParticipantStatus::Pending,
    ]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([SessionNotice::OpenSpotsReminder->value]);
});

test('no alert for a full session', function () {
    $this->session->update(['capacity' => 2]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([]);
    Mail::assertNothingQueued();
});

test('no alert for a cancelled session', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([]);
});

test('no alert more than 48 hours ahead or once registration has closed', function () {
    $this->session->update(['starts_at' => now()->addHours(49)]);
    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    $this->session->update(['starts_at' => now()->addMinutes(90)]);
    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([])
        ->and($this->session->fresh()->open_spots_alert_sent_at)->toBeNull();
});

test('a session already alerted is not alerted again', function () {
    $this->session->update(['open_spots_alert_sent_at' => now()->subHour()]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([]);
});

test('the alert follows the organiser\'s language, english when none is set', function () {
    $this->host->update(['locale' => 'fr']);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    $french = openSpotsMailTo($this->host);

    expect($french->locale)->toBe('fr');
    $french->assertHasSubject('Votre session a encore des places libres');

    $noLanguageHost = User::factory()->create();
    $noLanguageHost->forceFill(['locale' => ''])->save();

    $other = CommunitySession::factory()->create([
        'host_id' => $noLanguageHost->id,
        'capacity' => 4,
        'starts_at' => now()->addHours(30),
    ]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    $english = openSpotsMailTo($other->host);

    expect($english->locale)->toBe('en')
        ->and($other->host->userNotifications()->with('notification')->first()->notification->title)
        ->toBe('Your session still has free places');
    $english->assertHasSubject('Your session still has free places');
});

test('an organiser who switched session emails off still gets the in-app alert', function () {
    $this->host->update(['session_emails' => false]);

    $this->artisan('sessions:send-open-spots-alerts')->assertSuccessful();

    expect(openSpotsNotices($this->host))->toBe([SessionNotice::OpenSpotsReminder->value])
        ->and(openSpotsMailTo($this->host))->toBeNull();
});
