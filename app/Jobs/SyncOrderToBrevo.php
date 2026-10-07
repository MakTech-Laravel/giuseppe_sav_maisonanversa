<?php

namespace App\Jobs;

use App\Contracts\BrevoContacts;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncOrderToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120];

    public function __construct(public Order $order) {}

    public function handle(BrevoContacts $brevo): void
    {
        $this->order->refresh();
        $this->order->loadMissing('user', 'product');

        $brevo->upsertOrderContact($this->order);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to sync paid order to Brevo.', [
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
