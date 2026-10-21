<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShippingNotification extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

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
        $locale = MailLocale::resolve($this->order->locale);

        return new Content(
            html: 'emails.shipping-notification',
            with: [
                ...$this->maisonBrandData($this->order->locale),
                'returnsUrl' => route('maison.returns', ['locale' => $locale]),
            ],
        );
    }
}
