<?php

namespace App\Jobs\Returns;

use App\Mail\Returns\AdminReturnRequestMail;
use App\Models\OrderReturn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendReturnAdminRequestMail implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrderReturn $orderReturn) {}

    public function handle(): void
    {
        $email = config('maison.order_admin_email');

        if (! filled($email)) {
            return;
        }

        Mail::to($email)->send(new AdminReturnRequestMail($this->orderReturn));
    }
}
