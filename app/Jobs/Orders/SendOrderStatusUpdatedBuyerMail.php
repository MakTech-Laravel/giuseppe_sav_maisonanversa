<?php

namespace App\Jobs\Orders;

use App\Mail\Orders\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Support\MailLocale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderStatusUpdatedBuyerMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public Order $order,
        public OrderStatusEvent $event,
    ) {}

    public function handle(): void
    {
        Mail::to($this->order->email)
            ->locale(MailLocale::resolve($this->order->locale))
            ->send(new OrderStatusUpdated($this->order, $this->event));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to send order status update mail.', [
            'order_id' => $this->order->id,
            'event_id' => $this->event->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
