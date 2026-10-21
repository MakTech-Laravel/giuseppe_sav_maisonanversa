<?php

namespace App\Mail\Returns;

use App\Models\OrderReturn;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnRequestReceivedMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public OrderReturn $orderReturn)
    {
        $this->locale(MailLocale::resolve($orderReturn->order?->locale ?? $orderReturn->user?->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Je retouraanvraag · :rma', ['rma' => $this->orderReturn->number]),
        );
    }

    public function content(): Content
    {
        $this->orderReturn->loadMissing('order', 'user');
        $locale = MailLocale::resolve($this->orderReturn->order?->locale);

        return new Content(
            html: 'emails.returns.r1-request-received',
            with: [
                ...$this->maisonBrandData($this->orderReturn->order?->locale),
                'returnUrl' => route('member.returns.show', [
                    'locale' => $locale,
                    'orderReturn' => $this->orderReturn->id,
                ]),
                'returnAddress' => (string) config('maison.returns.return_address'),
            ],
        );
    }
}
