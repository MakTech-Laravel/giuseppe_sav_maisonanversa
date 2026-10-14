<?php

namespace App\Console\Commands\Community;

use App\Models\CommunitySession;
use App\Services\Community\SessionNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * E5: "Tomorrow on the court". Sessions starting within the next hour are
 * skipped; a reminder that late is noise rather than help.
 */
#[Signature('sessions:send-reminders')]
#[Description('Send the 24-hour reminder to every player of upcoming sessions')]
class SendSessionReminders extends Command
{
    public function handle(SessionNotificationService $notifier): int
    {
        $sent = 0;

        CommunitySession::query()
            ->whereNull('cancelled_at')
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now()->addHour())
            ->where('starts_at', '<=', now()->addDay())
            ->each(function (CommunitySession $session) use ($notifier, &$sent): void {
                $claimed = CommunitySession::query()
                    ->whereKey($session->id)
                    ->whereNull('reminder_sent_at')
                    ->update(['reminder_sent_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                $notifier->remind($session);
                $sent++;
            });

        $this->info("Sent reminders for {$sent} sessions.");

        return self::SUCCESS;
    }
}
