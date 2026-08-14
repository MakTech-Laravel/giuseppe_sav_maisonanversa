<?php

namespace App\Console\Commands\Editions;

use App\Services\Edition\EditionAllocator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('editions:release-expired-holds')]
#[Description('Release expired Founding Edition checkout holds')]
class ReleaseExpiredHolds extends Command
{
    public function handle(EditionAllocator $allocator): int
    {
        $released = $allocator->releaseExpiredHolds();

        $this->info("Released {$released} expired edition holds.");

        return self::SUCCESS;
    }
}
