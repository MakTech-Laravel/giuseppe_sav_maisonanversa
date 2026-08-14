<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale($order->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Bestelling bevestigd — :product', [
                'product' => $this->order->product?->name ?? __('Product'),
            ]),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('product');

        return new Content(
            markdown: 'emails.order-confirmation',
        );
    }
}
