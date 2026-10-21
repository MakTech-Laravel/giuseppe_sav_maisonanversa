<?php

namespace App\Console\Commands\Returns;

use App\Enums\ReturnStatus;
use App\Mail\Returns\AdminReturnDigestMail;
use App\Models\OrderReturn;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('returns:send-admin-digest')]
#[Description('Email Yusuf a daily summary of urgent or stale return requests')]
class SendAdminReturnDigestCommand extends Command
{
    public function handle(): int
    {
        $email = config('maison.order_admin_email');

        if (! filled($email)) {
            $this->info('No order admin email configured.');

            return self::SUCCESS;
        }

        $urgencyDays = (int) config('maison.returns.urgency_days');
        $staleDays = (int) config('maison.returns.stale_days');

        $returns = OrderReturn::query()
            ->with(['order'])
            ->whereIn('status', [
                ReturnStatus::Requested->value,
                ReturnStatus::InTransit->value,
                ReturnStatus::Received->value,
            ])
            ->where(function ($query) use ($urgencyDays, $staleDays): void {
                $query->where(function ($inner) use ($urgencyDays): void {
                    $inner->whereNotNull('refund_due_by')
                        ->where('refund_due_by', '<=', now()->addDays($urgencyDays));
                })->orWhere('updated_at', '<=', now()->subDays($staleDays));
            })
            ->orderBy('refund_due_by')
            ->get();

        if ($returns->isEmpty()) {
            $this->info('No urgent returns.');

            return self::SUCCESS;
        }

        $rows = $returns->map(fn (OrderReturn $return): array => [
            'number' => $return->number,
            'order' => $return->order?->reference() ?? (string) $return->order_id,
            'due' => $return->refund_due_by?->toDateString() ?? '—',
            'reason' => $return->status->label(),
        ]);

        Mail::to($email)->send(new AdminReturnDigestMail($rows));
        $this->info('Digest sent for '.$rows->count().' returns.');

        return self::SUCCESS;
    }
}
