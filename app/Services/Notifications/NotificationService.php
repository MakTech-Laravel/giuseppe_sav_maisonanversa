<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Jobs\FanOutNotificationJob;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for in-app notifications: one AppNotification row plus one
 * UserNotification row per recipient, then a realtime fan-out per recipient.
 */
class NotificationService
{
    /**
     * @param  iterable<User|int|string>  $userIds
     * @param  array<string, mixed>  $data  `link_url` / `link_label` render as a link button.
     * @param  bool  $queue  False fans out synchronously (diagnostics, no queue worker needed).
     */
    public function send(
        iterable $userIds,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        User|int|null $createdBy = null,
        bool $queue = true,
    ): ?AppNotification {
        $ids = $this->uniqueUserIds($userIds);

        if ($ids === []) {
            return null;
        }

        $createdById = $createdBy instanceof User ? $createdBy->id : $createdBy;

        $notification = DB::transaction(function () use ($ids, $type, $title, $message, $data, $createdById): AppNotification {
            $notification = AppNotification::query()->create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data === [] ? null : $data,
                'created_by' => $createdById,
            ]);

            $now = now();

            UserNotification::query()->insert(
                array_map(fn (int $userId): array => [
                    'app_notification_id' => $notification->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $ids),
            );

            return $notification;
        });

        if ($queue) {
            FanOutNotificationJob::dispatch($notification->id)->afterCommit();
        } else {
            FanOutNotificationJob::dispatchSync($notification->id);
        }

        return $notification;
    }

    /**
     * @param  iterable<User|int|string>  $userIds
     * @return list<int>
     */
    private function uniqueUserIds(iterable $userIds): array
    {
        $ids = [];

        foreach ($userIds as $userId) {
            $id = $userId instanceof User ? (int) $userId->id : (int) $userId;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
