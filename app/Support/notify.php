<?php

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\Notifications\NotificationService;

if (! function_exists('notify_users')) {
    /**
     * @param  iterable<User|int|string>  $userIds
     * @param  array<string, mixed>  $data
     */
    function notify_users(
        iterable $userIds,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        User|int|null $createdBy = null,
    ): ?AppNotification {
        return app(NotificationService::class)->send($userIds, $type, $title, $message, $data, $createdBy);
    }
}

if (! function_exists('notify_user')) {
    /**
     * @param  array<string, mixed>  $data
     */
    function notify_user(
        User|int $user,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        User|int|null $createdBy = null,
    ): ?AppNotification {
        return notify_users([$user], $type, $title, $message, $data, $createdBy);
    }
}
