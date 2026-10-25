<?php

namespace App\Console\Commands\FoundingCircle;

use App\Models\FoundingCircleRegisterEntry;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Services\Returns\ReturnEligibility;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('founding-circle:promote-inscriptions')]
#[Description('Promote Reserved register places to Inscribed after delivery + withdrawal window')]
class PromoteRegisterInscriptionsCommand extends Command
{
    public function handle(FoundingCircleRegistrar $registrar, ReturnEligibility $eligibility): int
    {
        $promoted = 0;

        FoundingCircleRegisterEntry::query()
            ->with(['order', 'user'])
            ->whereNull('inscribed_at')
            ->whereNotNull('order_id')
            ->each(function (FoundingCircleRegisterEntry $entry) use ($registrar, $eligibility, &$promoted): void {
                $order = $entry->order;

                if ($order === null) {
                    return;
                }

                $deadline = $eligibility->requestDeadline($order);

                if ($deadline === null || $deadline->isFuture()) {
                    return;
                }

                if ($registrar->promoteToInscribed($entry)) {
                    $promoted++;
                }
            });

        $this->info("Promoted {$promoted} register place(s) to Inscribed.");

        return self::SUCCESS;
    }
}
