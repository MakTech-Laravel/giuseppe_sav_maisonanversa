<?php

namespace App\Mail\Returns;

use App\Models\Order;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestReturnLinkMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $returnUrl,
    ) {
        $this->locale(MailLocale::resolve($order->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Start je retour · :reference', ['reference' => $this->order->reference()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.returns.guest-link',
            with: [
                ...$this->maisonBrandData($this->order->locale),
                'returnUrl' => $this->returnUrl,
                'orderReference' => $this->order->reference(),
                'customerName' => $this->order->name ?: __('klant'),
            ],
        );
    }
}
