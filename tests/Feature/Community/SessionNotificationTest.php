<?php

use App\Enums\RoleEnum;
use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionNotice;
use App\Enums\SessionSport;
use App\Mail\SessionNoticeMail;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Support\SessionCalendar;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    Mail::fake();
    Queue::fake();

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();

    $this->otherAdmin = User::factory()->admin()->create();

    $this->host = User::factory()->create(['name' => 'Host Name']);
    $this->player = User::factory()->create(['name' => 'First Player']);

    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'capacity' => 4,
        'starts_at' => now()->addDays(3),
    ]);

    addSessionPlayers($this->session, [$this->host, $this->player]);
});

/**
 * @param  list<User>  $users
 */
function addSessionPlayers(CommunitySession $session, array $users): void
{
    foreach ($users as $user) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $session->id,
            'user_id' => $user->id,
        ]);
    }
}

/**
 * Notices received in-app, in order.
 *
 * @return list<string>
 */
function inAppNotices(User $user): array
{
    return $user->userNotifications()
        ->with('notification')
        ->orderBy('id')
        ->get()
        ->map(fn ($row): string => $row->notification->data['notice'])
        ->all();
}

function mailedNotice(User $user, SessionNotice $notice): bool
{
    return Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === $notice && $mail->hasTo($user->email),
    )->isNotEmpty();
}

function mailsTo(User $user): int
{
    return Mail::queued(SessionNoticeMail::class, fn (SessionNoticeMail $mail): bool => $mail->hasTo($user->email))->count();
}

function joinSession(User $user, CommunitySession $session): void
{
    test()->actingAs($user)
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();
}

function approveRequest(User $host, User $requester, CommunitySession $session): void
{
    test()->actingAs($host)
        ->patch(route('community.sessions.requests.approve', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $requester->id,
        ]))
        ->assertRedirect();
}

test('a join request notifies only the organiser, nobody is confirmed yet', function () {
    $joiner = User::factory()->create(['name' => 'New Player']);

    joinSession($joiner, $this->session);

    expect(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value])
        ->and(mailedNotice($this->host, SessionNotice::JoinRequested))->toBeTrue()
        ->and(inAppNotices($joiner))->toBe([])
        ->and(inAppNotices($this->player))->toBe([])
        ->and(inAppNotices($this->admin))->toBe([])
        ->and(mailsTo($this->admin))->toBe(0);

    $row = $this->host->userNotifications()->with('notification')->first();
    expect($row->notification->message)->toContain('New Player')
        ->and($row->notification->data['session_id'])->toBe($this->session->id);
});

test('E2: approving a request confirms it to the player, not to the organiser', function () {
    $joiner = User::factory()->create();
    joinSession($joiner, $this->session);

    approveRequest($this->host, $joiner, $this->session);

    expect(inAppNotices($joiner))->toBe([SessionNotice::JoinConfirmed->value])
        ->and(mailedNotice($joiner, SessionNotice::JoinConfirmed))->toBeTrue()
        ->and(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value])
        ->and(inAppNotices($this->player))->toBe([]);
});

test('E3: the approval that fills the session tells every other player and the admins', function () {
    $this->session->update(['capacity' => 3]);
    $joiner = User::factory()->create();
    joinSession($joiner, $this->session);

    approveRequest($this->host, $joiner, $this->session);

    expect(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value, SessionNotice::SessionFull->value])
        ->and(inAppNotices($this->player))->toBe([SessionNotice::SessionFull->value])
        ->and(mailedNotice($this->player, SessionNotice::SessionFull))->toBeTrue()
        ->and(inAppNotices($joiner))->toBe([SessionNotice::JoinConfirmed->value])
        ->and(inAppNotices($this->admin))->toBe([SessionNotice::AdminFull->value])
        ->and(mailedNotice($this->otherAdmin, SessionNotice::AdminFull))->toBeTrue();
});

test('rejecting a request tells only the requester', function () {
    $joiner = User::factory()->create();
    joinSession($joiner, $this->session);

    $this->actingAs($this->host)
        ->patch(route('community.sessions.requests.reject', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
            'user' => $joiner->id,
        ]))
        ->assertRedirect();

    expect(inAppNotices($joiner))->toBe([SessionNotice::RequestRejected->value])
        ->and(mailedNotice($joiner, SessionNotice::RequestRejected))->toBeTrue()
        ->and(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value])
        ->and(inAppNotices($this->player))->toBe([]);
});

test('withdrawing a pending request tells only the organiser', function () {
    $joiner = User::factory()->create();
    joinSession($joiner, $this->session);

    $this->actingAs($joiner)
        ->delete(route('community.sessions.leave', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    expect(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value, SessionNotice::RequestWithdrawn->value])
        ->and(inAppNotices($joiner))->toBe([])
        ->and(inAppNotices($this->player))->toBe([]);
});

test('joining a session twice sends nothing the second time', function () {
    joinSession($this->player, $this->session);

    Mail::assertNothingQueued();
    expect(inAppNotices($this->host))->toBe([]);
});

test('E4: a leave tells only the organiser while the session was not full', function () {
    $stayer = User::factory()->create();
    addSessionPlayers($this->session, [$stayer]);

    $this->actingAs($this->player)
        ->delete(route('community.sessions.leave', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    expect(inAppNotices($this->host))->toBe([SessionNotice::SpotOpened->value])
        ->and(mailedNotice($this->host, SessionNotice::SpotOpened))->toBeTrue()
        ->and(inAppNotices($stayer))->toBe([])
        ->and(inAppNotices($this->player))->toBe([])
        ->and(inAppNotices($this->admin))->toBe([]);
});

test('E4: a leave from a full session also tells the other players', function () {
    $this->session->update(['capacity' => 3]);
    $stayer = User::factory()->create();
    addSessionPlayers($this->session, [$stayer]);

    $this->actingAs($this->player)
        ->delete(route('community.sessions.leave', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    expect(inAppNotices($this->host))->toBe([SessionNotice::SpotOpened->value])
        ->and(inAppNotices($stayer))->toBe([SessionNotice::SpotOpened->value])
        ->and(inAppNotices($this->player))->toBe([]);
});

test('the organiser removing a player tells that player, not the organiser', function () {
    $this->actingAs($this->host)
        ->delete(route('community.sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
            'user' => $this->player->id,
        ]))
        ->assertRedirect();

    expect(inAppNotices($this->player))->toBe([SessionNotice::Removed->value])
        ->and(mailedNotice($this->player, SessionNotice::Removed))->toBeTrue()
        ->and(inAppNotices($this->host))->toBe([])
        ->and(mailsTo($this->host))->toBe(0);
});

test('an admin removing a player tells that player and the organiser', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.community-sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
            'user' => $this->player->id,
        ]))
        ->assertRedirect();

    expect(inAppNotices($this->player))->toBe([SessionNotice::Removed->value])
        ->and(inAppNotices($this->host))->toBe([SessionNotice::SpotOpened->value])
        ->and(inAppNotices($this->admin))->toBe([])
        ->and(inAppNotices($this->otherAdmin))->toBe([]);
});

test('creating a session notifies only the admins', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('community.sessions.store', ['locale' => 'nl']), [
            'sport' => SessionSport::Padel->value,
            'club_id' => Club::factory()->create()->id,
            'starts_at' => now()->addDays(2)->toDateTimeString(),
            'duration_minutes' => 90,
            'court_status' => SessionCourtStatus::Booked->value,
            'level' => SessionLevel::OpenToAll->value,
            'gender' => SessionGender::Everyone->value,
            'capacity' => 4,
        ])
        ->assertRedirect();

    expect(inAppNotices($this->admin))->toBe([SessionNotice::AdminCreated->value])
        ->and(mailedNotice($this->otherAdmin, SessionNotice::AdminCreated))->toBeTrue()
        ->and(inAppNotices($creator))->toBe([])
        ->and(mailsTo($creator))->toBe(0);
});

test('E6: the organiser cancels with a message and every player receives it', function () {
    $secondPlayer = User::factory()->create();
    addSessionPlayers($this->session, [$secondPlayer]);

    $this->actingAs($this->host)
        ->delete(route('community.sessions.destroy', ['locale' => 'nl', 'communitySession' => $this->session->id]), [
            'message' => 'Baan is onder water gelopen.',
        ])
        ->assertRedirect();

    expect($this->session->fresh()->cancellation_message)->toBe('Baan is onder water gelopen.')
        ->and(inAppNotices($this->player))->toBe([SessionNotice::Cancelled->value])
        ->and(inAppNotices($secondPlayer))->toBe([SessionNotice::Cancelled->value])
        ->and(inAppNotices($this->host))->toBe([])
        ->and(inAppNotices($this->admin))->toBe([SessionNotice::AdminCancelled->value]);

    Mail::assertQueued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->hasTo($this->player->email)
            && str_contains($mail->render(), 'Baan is onder water gelopen.'),
    );
});

test('an admin cancelling tells the organiser and every player, linking members to the public page', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.community-sessions.cancel', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    expect(inAppNotices($this->host))->toBe([SessionNotice::Cancelled->value])
        ->and(inAppNotices($this->player))->toBe([SessionNotice::Cancelled->value])
        ->and(inAppNotices($this->admin))->toBe([])
        ->and(inAppNotices($this->otherAdmin))->toBe([SessionNotice::AdminCancelled->value]);

    $adminRow = $this->otherAdmin->userNotifications()->with('notification')->first();
    $memberRow = $this->player->userNotifications()->with('notification')->first();

    expect($adminRow->notification->data['link_url'])
        ->toBe(route('admin.community-sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->and($memberRow->notification->data['link_url'])
        ->toBe(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $this->session->id]));
});

test('cancelling a session that is already cancelled sends nothing', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->host)
        ->delete(route('community.sessions.destroy', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->patch(route('admin.community-sessions.cancel', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    Mail::assertNothingQueued();
    expect(inAppNotices($this->player))->toBe([]);
});

test('members who switched session emails off still get the in-app notification', function () {
    $this->host->update(['session_emails' => false]);

    joinSession(User::factory()->create(), $this->session);

    expect(inAppNotices($this->host))->toBe([SessionNotice::JoinRequested->value])
        ->and(mailsTo($this->host))->toBe(0);
});

test('in-app notifications are written in the recipient\'s language', function () {
    $this->player->update(['locale' => 'fr']);
    $this->host->update(['locale' => 'en']);

    $this->actingAs($this->admin)
        ->patch(route('admin.community-sessions.cancel', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    $french = $this->player->userNotifications()->with('notification')->first()->notification;
    $english = $this->host->userNotifications()->with('notification')->first()->notification;

    expect($french->title)->toBe('Session annulée')
        ->and($french->data['link_url'])->toContain('/fr/')
        ->and($english->title)->toBe('Session cancelled');

    Mail::assertQueued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->hasTo($this->player->email) && $mail->locale === 'fr',
    );
});

test('E5: the reminder goes to every player once, only within 24 hours of the start', function () {
    $this->session->update(['starts_at' => now()->addHours(20)]);

    $later = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addHours(30),
    ]);

    $this->artisan('sessions:send-reminders')->assertSuccessful();
    $this->artisan('sessions:send-reminders')->assertSuccessful();

    expect(inAppNotices($this->host))->toBe([SessionNotice::Reminder->value])
        ->and(inAppNotices($this->player))->toBe([SessionNotice::Reminder->value])
        ->and(mailedNotice($this->player, SessionNotice::Reminder))->toBeTrue()
        ->and($this->session->fresh()->reminder_sent_at)->not->toBeNull()
        ->and($later->fresh()->reminder_sent_at)->toBeNull();
});

test('the reminder reaches confirmed players and the organiser by mail, never pending or rejected users', function () {
    $this->session->update(['starts_at' => now()->addHours(20)]);

    $pending = User::factory()->create();
    $rejected = User::factory()->create();
    CommunitySessionParticipant::factory()->pending()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $pending->id,
    ]);
    CommunitySessionParticipant::factory()->rejected()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $rejected->id,
    ]);

    $this->artisan('sessions:send-reminders')->assertSuccessful();

    expect(mailedNotice($this->host, SessionNotice::Reminder))->toBeTrue()
        ->and(mailedNotice($this->player, SessionNotice::Reminder))->toBeTrue()
        ->and(inAppNotices($pending))->toBe([])
        ->and(inAppNotices($rejected))->toBe([])
        ->and(mailsTo($pending))->toBe(0)
        ->and(mailsTo($rejected))->toBe(0);
});

test('the reminder skips cancelled sessions', function () {
    $this->session->update(['starts_at' => now()->addHours(20), 'cancelled_at' => now()]);

    $this->artisan('sessions:send-reminders')->assertSuccessful();

    expect(inAppNotices($this->player))->toBe([]);
});

test('the confirmation and full mails carry the session details and a calendar file', function () {
    $club = Club::factory()->create(['name' => 'Padel Club Antwerpen', 'lat' => 51.2, 'lng' => 4.4]);
    $this->session->update(['club_id' => $club->id]);

    $mail = new SessionNoticeMail(
        session: $this->session->fresh(),
        notice: SessionNotice::SessionFull,
        subjectName: 'New Player',
        players: ['Host Name', 'First Player'],
        freePlaces: 0,
        sessionUrl: 'https://example.test/session',
        ctaUrl: 'https://example.test/session',
        locale: 'en',
    );

    $html = $mail->render();

    expect($html)->toContain('Padel Club Antwerpen')
        ->and($html)->toContain('Host Name · First Player')
        ->and($html)->toContain('https://www.google.com/maps/dir/?api=1&amp;destination=51.2%2C4.4"')
        ->and($mail->attachments())->toHaveCount(1);

    $ics = SessionCalendar::make($this->session->fresh(), 'https://example.test/session');

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->and($ics)->toContain('UID:session-'.$this->session->id.'@maisonanversa')
        ->and($ics)->toContain('DTSTART:'.$this->session->starts_at->copy()->utc()->format('Ymd\THis\Z'));

    $reminder = new SessionNoticeMail(
        session: $this->session->fresh(),
        notice: SessionNotice::Reminder,
        subjectName: 'Host Name',
        players: [],
        freePlaces: 2,
        sessionUrl: 'https://example.test/session',
        ctaUrl: 'https://example.test/session',
        locale: 'en',
    );

    expect($reminder->attachments())->toBe([]);

    $reminder->assertHasSubject('Tomorrow on the court');
});

function confirmationMailTo(User $user): ?SessionNoticeMail
{
    return Mail::queued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->notice === SessionNotice::JoinConfirmed && $mail->hasTo($user->email),
    )->first();
}

function latestInAppMessage(User $user): string
{
    return (string) $user->userNotifications()->with('notification')->latest('id')->first()?->notification->message;
}

test('E2 approved on the day of the session says it is today', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->setTime(9, 0));
    $this->session->update(['starts_at' => now()->setTime(18, 30)]);
    $joiner = User::factory()->create(['locale' => 'nl']);

    joinSession($joiner, $this->session);
    approveRequest($this->host, $joiner, $this->session);

    $mail = confirmationMailTo($joiner);

    expect($mail->timingNote)->toBe('Uw sessie is vandaag om 18:30.')
        ->and($mail->render())->toContain('Uw sessie is vandaag om 18:30.')
        ->and($mail->attachments())->toHaveCount(1)
        ->and(latestInAppMessage($joiner))->toContain('Uw sessie is vandaag om 18:30.');
});

test('E2 approved the evening before says it is tomorrow', function () {
    $this->travelTo(now()->setDate(2026, 10, 14)->setTime(20, 0));
    $this->session->update(['starts_at' => now()->addDay()->setTime(10, 0)]);
    $joiner = User::factory()->create(['locale' => 'nl']);

    joinSession($joiner, $this->session);
    approveRequest($this->host, $joiner, $this->session);

    expect(confirmationMailTo($joiner)->timingNote)->toBe('Uw sessie is morgen om 10:00.');
});

test('E2 more than 24 hours ahead carries no timing line', function () {
    $joiner = User::factory()->create();

    joinSession($joiner, $this->session);
    approveRequest($this->host, $joiner, $this->session);

    expect(confirmationMailTo($joiner)->timingNote)->toBeNull()
        ->and(latestInAppMessage($joiner))->not->toContain('morgen om');
});

test('the E2 timing line is written in the player\'s own language', function () {
    $this->travelTo(now()->setDate(2026, 10, 14)->setTime(20, 0));
    $this->session->update(['starts_at' => now()->addDay()->setTime(10, 0)]);
    $joiner = User::factory()->create(['locale' => 'en']);

    joinSession($joiner, $this->session);
    approveRequest($this->host, $joiner, $this->session);

    $mail = confirmationMailTo($joiner);

    expect($mail->locale)->toBe('en')
        ->and($mail->timingNote)->toBe('Your session is tomorrow at 10:00.')
        ->and(latestInAppMessage($joiner))->toContain('Your session is tomorrow at 10:00.');
});

test('a player approved after the 24-hour reminder gets E2 with the details, never E5', function () {
    $this->travelTo(now()->setDate(2026, 10, 14)->setTime(20, 0));
    $this->session->update(['starts_at' => now()->addDay()->setTime(10, 0)]);
    $joiner = User::factory()->create(['locale' => 'nl']);
    joinSession($joiner, $this->session);

    $this->artisan('sessions:send-reminders')->assertSuccessful();

    approveRequest($this->host, $joiner, $this->session);

    $this->artisan('sessions:send-reminders')->assertSuccessful();

    $mail = confirmationMailTo($joiner);

    expect(inAppNotices($joiner))->toBe([SessionNotice::JoinConfirmed->value])
        ->and(mailedNotice($joiner, SessionNotice::Reminder))->toBeFalse()
        ->and(mailedNotice($this->player, SessionNotice::Reminder))->toBeTrue()
        ->and($mail->attachments())->toHaveCount(1)
        ->and($mail->timingNote)->toBe('Uw sessie is morgen om 10:00.');
});

test('a member without a supported language gets session notices in english', function () {
    $this->player->forceFill(['locale' => 'xx'])->save();

    $this->actingAs($this->host)
        ->delete(route('community.sessions.destroy', ['locale' => 'nl', 'communitySession' => $this->session->id]))
        ->assertRedirect();

    $notification = $this->player->userNotifications()->with('notification')->first()->notification;

    expect($notification->title)->toBe('Session cancelled');

    Mail::assertQueued(
        SessionNoticeMail::class,
        fn (SessionNoticeMail $mail): bool => $mail->hasTo($this->player->email) && $mail->locale === 'en',
    );
});
