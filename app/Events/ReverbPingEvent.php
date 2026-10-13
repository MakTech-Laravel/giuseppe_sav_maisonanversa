<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Public diagnostic broadcast used by the admin Realtime console.
 */
class ReverbPingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public const CHANNEL = 'reverb-ping';

    public const BROADCAST_AS = 'ping';

    public function __construct(
        public readonly string $message,
        public readonly string $triggeredAt,
        public readonly string $source = 'admin-console',
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(self::CHANNEL)];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array{message: string, triggered_at: string, source: string}
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'triggered_at' => $this->triggeredAt,
            'source' => $this->source,
        ];
    }
}
