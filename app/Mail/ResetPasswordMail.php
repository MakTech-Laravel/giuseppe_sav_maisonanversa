<?php

namespace App\Mail;

use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $expireMinutes,
        ?string $locale = null,
    ) {
        $this->locale(MailLocale::resolve($locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Uw herstelcode voor Maison Anversa'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.reset-password',
            with: [
                ...$this->maisonBrandData(null, false),
                'otp' => $this->otp,
                'expireMinutes' => $this->expireMinutes,
            ],
        );
    }
}
