<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShippingNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale($order->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Uw Heritage No.001 is onderweg'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shipping-notification',
        );
    }
}
