<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Private diagnostic broadcast to the acting user's own channel, which proves
 * /broadcasting/auth and the signed publish both work end to end.
 */
class RealtimePrivateTestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'realtime.test';

    public function __construct(
        public readonly User $user,
        public readonly string $message,
        public readonly string $triggeredAt,
    ) {}

    public static function channelName(int $userId): string
    {
        return 'App.Models.User.'.$userId;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::channelName($this->user->id))];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array{message: string, triggered_at: string, user_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'triggered_at' => $this->triggeredAt,
            'user_id' => $this->user->id,
        ];
    }
}
