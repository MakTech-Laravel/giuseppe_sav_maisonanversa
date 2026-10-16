<?php

namespace App\Console\Commands\Community;

use App\Services\Community\SessionChatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sessions:prune-chat-messages')]
#[Description('Hard-delete session chat messages seven days after the session closed')]
class PruneSessionChatMessagesCommand extends Command
{
    public function handle(SessionChatService $chat): int
    {
        $deleted = $chat->pruneExpired();

        $this->info("Pruned {$deleted} session chat messages.");

        return self::SUCCESS;
    }
}
