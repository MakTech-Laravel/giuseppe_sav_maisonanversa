<?php

namespace App\Jobs\Orders;

use App\Mail\Orders\AdminNewOrderNotification;
use App\Models\Order;
use App\Support\MailLocale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderPaidAdminMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $email = (string) config('maison.order_admin_email');

        if ($email === '') {
            return;
        }

        Mail::to($email)
            ->locale(MailLocale::resolve(null))
            ->send(new AdminNewOrderNotification($this->order));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to send order paid admin mail.', [
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
