<?php

namespace App\Jobs;

use App\Events\UserNotificationCreated;
use App\Models\UserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Broadcasts one realtime event per recipient row, off the request cycle.
 */
class FanOutNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $notificationId) {}

    public function handle(): void
    {
        UserNotification::query()
            ->where('app_notification_id', $this->notificationId)
            ->with('notification')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    UserNotificationCreated::dispatch($row);
                }
            });
    }
}
