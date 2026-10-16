<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lightweight unread bump for members who are not currently watching the
 * session chat channel. Payload refreshes shared chat.unreadTotal badges.
 */
class SessionChatActivity implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'session.chat.activity';

    public function __construct(
        public int $userId,
        public int $sessionId,
        public int $unreadTotal,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array{session_id: int, unread_total: int}
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'unread_total' => $this->unreadTotal,
        ];
    }
}
