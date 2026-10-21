<?php

namespace App\Jobs\Returns;

use App\Mail\Returns\ReturnDecisionMail;
use App\Mail\Returns\ReturnParcelReceivedMail;
use App\Mail\Returns\ReturnRefundIssuedMail;
use App\Mail\Returns\ReturnRequestReceivedMail;
use App\Models\OrderReturn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendReturnCustomerMail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public OrderReturn $orderReturn,
        public string $template,
    ) {}

    public function handle(): void
    {
        $this->orderReturn->loadMissing('order', 'user');
        $email = $this->orderReturn->order?->email ?: $this->orderReturn->user?->email;

        if (! filled($email)) {
            return;
        }

        $mailable = match ($this->template) {
            'r1' => new ReturnRequestReceivedMail($this->orderReturn),
            'r2' => new ReturnParcelReceivedMail($this->orderReturn),
            'r3' => new ReturnRefundIssuedMail($this->orderReturn),
            'r4' => new ReturnDecisionMail($this->orderReturn),
            default => null,
        };

        if ($mailable === null) {
            return;
        }

        Mail::to($email)->send($mailable);
    }
}
