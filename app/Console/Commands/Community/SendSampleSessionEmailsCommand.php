<?php

namespace App\Console\Commands\Community;

use App\Enums\SessionNotice;
use App\Mail\SessionChatDigestMail;
use App\Mail\SessionNoticeMail;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\User;
use App\Services\Community\SessionNotificationService;
use App\Support\MailLocale;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Staging QA: send one sample of each client-named session email to an inbox.
 */
#[Signature('sessions:send-sample-emails {email : Inbox that receives the samples} {--locale=nl : Mail locale (nl|en|fr)}')]
#[Description('Send sample New player, Reminder, Session full, Cancelled, Match card ready, and chat digest mails')]
class SendSampleSessionEmailsCommand extends Command
{
    /**
     * @var list<SessionNotice>
     */
    private const NOTICES = [
        SessionNotice::NewPlayer,
        SessionNotice::Reminder,
        SessionNotice::SessionFull,
        SessionNotice::Cancelled,
        SessionNotice::MatchCardReady,
    ];

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $locale = MailLocale::resolve($this->option('locale'));

        $session = CommunitySession::query()
            ->with(['club', 'host'])
            ->whereNull('cancelled_at')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();

        if ($session === null) {
            $host = User::factory()->create();
            $club = Club::factory()->create();
            $session = CommunitySession::factory()->create([
                'host_id' => $host->id,
                'club_id' => $club->id,
                'starts_at' => now()->addDays(2)->setTime(10, 30),
            ]);
            $session->load(['club', 'host']);
        }

        $subjectName = (string) ($session->host?->name ?? 'Sofia');
        $players = [$subjectName, 'Jan Peeters'];
        $freePlaces = max(0, $session->capacity - count($players));
        $sessionUrl = SessionNotificationService::sessionUrl($session, false, $locale);

        app()->setLocale($locale);

        foreach (self::NOTICES as $notice) {
            Mail::to($email)->send(new SessionNoticeMail(
                session: $session,
                notice: $notice,
                subjectName: $subjectName,
                players: $players,
                freePlaces: $freePlaces,
                sessionUrl: $sessionUrl,
                ctaUrl: $sessionUrl,
                locale: $locale,
                timingNote: $notice->startsSoonLine($session->starts_at),
            ));

            $this->line("Sent {$notice->value} → {$email}");
        }

        Mail::to($email)->send(new SessionChatDigestMail(
            unreadCount: 1,
            ctaUrl: route('member.chat.index', ['locale' => $locale]),
            locale: $locale,
        ));

        $this->line("Sent chat digest → {$email}");
        $this->info('Six sample session emails queued/sent.');

        return self::SUCCESS;
    }
}
