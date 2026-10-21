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

class AdminReturnRequestMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public OrderReturn $orderReturn)
    {
        $this->locale(MailLocale::resolve(config('maison.notification_fallback_locale')));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New return request '.$this->orderReturn->number,
        );
    }

    public function content(): Content
    {
        $this->orderReturn->loadMissing(['order.product', 'user', 'items.product']);
        $locale = config('maison.default_locale', 'nl');

        return new Content(
            html: 'emails.returns.a1-admin-request',
            with: [
                ...$this->maisonBrandData($locale),
                'adminUrl' => route('admin.returns.show', [
                    'locale' => $locale,
                    'orderReturn' => $this->orderReturn->id,
                ]),
            ],
        );
    }
}
