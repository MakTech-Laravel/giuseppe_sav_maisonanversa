<?php

namespace App\Mail;

use App\Models\CommunitySession;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionJoinedMail extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(
        public CommunitySession $session,
        public string $memberName,
        ?string $locale = null,
    ) {
        $this->locale(MailLocale::resolve($locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Iemand heeft zich aangemeld voor uw sessie'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.session-joined',
            with: [
                ...$this->maisonBrandData(),
                'memberName' => $this->memberName,
                'when' => $this->session->starts_at->translatedFormat('j F Y H:i'),
                'location' => $this->session->location,
            ],
        );
    }
}
