<?php

namespace App\Console\Commands\Mail;

use App\Enums\OrderStatus;
use App\Mail\PostDeliveryFollowUp;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('mail:send-post-delivery-follow-ups')]
#[Description('Send 30-day post-delivery follow-up emails')]
class SendPostDeliveryFollowUps extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereNotNull('delivered_at')
            ->whereNull('follow_up_sent_at')
            ->where('delivered_at', '<=', now()->subDays(30))
            ->each(function (Order $order) use (&$sent): void {
                Mail::to($order->email)->locale($order->locale)->queue(new PostDeliveryFollowUp($order));
                $order->update(['follow_up_sent_at' => now()]);
                $sent++;
            });

        $this->info("Queued {$sent} follow-up emails.");

        return self::SUCCESS;
    }
}
