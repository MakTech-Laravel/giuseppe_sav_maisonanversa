<?php

namespace App\Jobs\Orders;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Support\MailLocale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderPaidBuyerMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        Mail::to($this->order->email)
            ->locale(MailLocale::resolve($this->order->locale))
            ->send(new OrderConfirmation($this->order));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to send order paid buyer mail.', [
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
