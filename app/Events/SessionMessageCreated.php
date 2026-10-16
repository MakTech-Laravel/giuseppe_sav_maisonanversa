<?php

namespace App\Events;

use App\Models\CommunitySessionMessage;
use App\Services\Community\SessionChatService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionMessageCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'message.created';

    public function __construct(public CommunitySessionMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(self::channelName($this->message->community_session_id)),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return app(SessionChatService::class)->present($this->message);
    }

    public static function channelName(int|string $sessionId): string
    {
        return "community-session.{$sessionId}.chat";
    }
}
