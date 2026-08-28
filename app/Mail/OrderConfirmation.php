<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale(MailLocale::resolve($order->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Bestelling bevestigd — :product', [
                'product' => $this->translatedProductName(),
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

    private function translatedProductName(): string
    {
        $this->order->loadMissing('product');

        $locale = MailLocale::resolve($this->order->locale);

        return $this->order->product?->translated('name', $locale) ?? __('Product');
    }
}
