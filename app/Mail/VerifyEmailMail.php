<?php

namespace App\Mail;

use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(
        public string $verificationUrl,
        ?string $locale = null,
    ) {
        $this->locale(MailLocale::resolve($locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('E-mailadres verifiëren'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.verify-email',
            with: [
                ...$this->maisonBrandData(null, false),
                'ctaUrl' => $this->verificationUrl,
                'ctaLabel' => __('E-mailadres verifiëren'),
            ],
        );
    }
}
