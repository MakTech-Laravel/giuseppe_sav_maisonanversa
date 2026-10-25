<?php

namespace App\Mail;

use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bulk “new messages” digest — one mail per inactive member with unread chat,
 * never one mail per message.
 */
class SessionChatDigestMail extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(
        public int $unreadCount,
        public string $ctaUrl,
        ?string $locale = null,
    ) {
        $this->locale(MailLocale::resolve($locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Nieuwe berichten'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.session-chat-digest',
            with: [
                ...$this->maisonBrandData($this->locale, withHomeCta: false),
                'title' => __('Nieuwe berichten'),
                'intro' => trans_choice(
                    'U heeft :count ongelezen chatbericht.|U heeft :count ongelezen chatberichten.',
                    $this->unreadCount,
                    ['count' => $this->unreadCount],
                ),
                'ctaUrl' => $this->ctaUrl,
                'ctaLabel' => __('Bekijk berichten'),
            ],
        );
    }
}
