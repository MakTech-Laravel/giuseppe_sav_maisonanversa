<?php

namespace App\Console\Commands\Community;

use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Services\Community\SessionNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Once a session's end time has passed, every player of a session with at
 * least two confirmed players hears that the match card is ready (in-app +
 * mail with share and download buttons). Sessions that ended longer ago than
 * the notify window are skipped, so a deploy never mails old games.
 */
#[Signature('sessions:send-match-cards')]
#[Description('Tell players their match card is ready once the session has ended')]
class SendMatchCards extends Command
{
    public function handle(SessionNotificationService $notifier): int
    {
        $sent = 0;

        CommunitySession::query()
            ->whereNull('cancelled_at')
            ->whereNull('match_card_sent_at')
            ->where('ends_at', '<=', now())
            ->where('ends_at', '>', now()->subHours((int) config('maison.match_cards.notify_window_hours')))
            ->whereRaw(
                '(select count(*) from community_session_participants where community_session_participants.community_session_id = community_sessions.id and community_session_participants.status = ?) >= ?',
                [SessionParticipantStatus::Confirmed->value, (int) config('maison.match_cards.min_players')],
            )
            ->each(function (CommunitySession $session) use ($notifier, &$sent): void {
                $claimed = CommunitySession::query()
                    ->whereKey($session->id)
                    ->whereNull('match_card_sent_at')
                    ->update(['match_card_sent_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                $notifier->matchCardReady($session);
                $sent++;
            });

        $this->info("Sent match cards for {$sent} sessions.");

        return self::SUCCESS;
    }
}
