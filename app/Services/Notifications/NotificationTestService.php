<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Events\RealtimePrivateTestEvent;
use App\Events\UserNotificationCreated;
use App\Models\User;

/**
 * Sends real inbox notifications from the admin test page, so the full path
 * (database rows, fan-out, Reverb, Echo, bell) can be audited by hand.
 */
class NotificationTestService
{
    public const TARGETS = ['self', 'admins', 'user'];

    public const MODES = ['queue', 'sync'];

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @param  array{target: string, email?: string|null, type: string, title: string, message: string, link_url?: string|null, link_label?: string|null, mode: string}  $validated
     * @return array<string, mixed>
     */
    public function send(User $sender, array $validated): array
    {
        $recipients = $this->recipients($sender, $validated);
        $queued = $validated['mode'] === 'queue';

        $data = array_filter([
            'link_url' => $validated['link_url'] ?? null,
            'link_label' => $validated['link_label'] ?? null,
            'source' => 'admin-test',
        ], fn (?string $value): bool => $value !== null && $value !== '');

        $notification = $this->notifications->send(
            $recipients,
            NotificationType::from($validated['type']),
            $validated['title'],
            $validated['message'],
            $data,
            $sender,
            queue: $queued,
        );

        return [
            'notification_id' => $notification?->id,
            'recipients' => count($recipients),
            'recipient_ids' => array_slice($recipients, 0, 20),
            'mode' => $validated['mode'],
            'queue_connection' => $queued ? (string) config('queue.default') : 'sync',
            'broadcast_connection' => (string) config('broadcasting.default'),
            'event' => UserNotificationCreated::BROADCAST_AS,
            'channels' => array_map(
                fn (int $id): string => 'private-'.RealtimePrivateTestEvent::channelName($id),
                array_slice($recipients, 0, 5),
            ),
        ];
    }

    /**
     * @param  array{target: string, email?: string|null}  $validated
     * @return list<int>
     */
    private function recipients(User $sender, array $validated): array
    {
        return match ($validated['target']) {
            'admins' => User::query()
                ->where('type', UserType::Admin)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            'user' => User::query()
                ->where('email', $validated['email'] ?? '')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            default => [$sender->id],
        };
    }
}
