<?php

namespace App\Console\Commands\Community;

use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Services\Community\SessionNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 48 hours before the start, the organiser of a session that still has free
 * places hears about it, with a share link and a cancel button. Sessions are
 * never cancelled automatically. Once registration has closed the alert is
 * skipped, because sharing no longer helps.
 */
#[Signature('sessions:send-open-spots-alerts')]
#[Description('Tell organisers 48 hours ahead when their session still has free places')]
class SendOpenSpotsAlerts extends Command
{
    public function handle(SessionNotificationService $notifier): int
    {
        $sent = 0;

        CommunitySession::query()
            ->whereNull('cancelled_at')
            ->whereNull('open_spots_alert_sent_at')
            ->where('starts_at', '>', now()->addHours(CommunitySession::REGISTRATION_CLOSES_HOURS))
            ->where('starts_at', '<=', now()->addHours(CommunitySession::OPEN_SPOTS_ALERT_HOURS))
            ->whereRaw(
                '(select count(*) from community_session_participants where community_session_participants.community_session_id = community_sessions.id and community_session_participants.status = ?) < community_sessions.capacity',
                [SessionParticipantStatus::Confirmed->value],
            )
            ->each(function (CommunitySession $session) use ($notifier, &$sent): void {
                $claimed = CommunitySession::query()
                    ->whereKey($session->id)
                    ->whereNull('open_spots_alert_sent_at')
                    ->update(['open_spots_alert_sent_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                $notifier->openSpots($session);
                $sent++;
            });

        $this->info("Sent open-spots alerts for {$sent} sessions.");

        return self::SUCCESS;
    }
}
