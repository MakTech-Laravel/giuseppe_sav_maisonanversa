<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use App\Support\BrandsMaisonMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SoldOutNotice extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
        $this->locale($subscriber->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Heritage No.001 is uitverkocht'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.sold-out-notice',
            with: $this->maisonBrandData($this->subscriber->locale),
        );
    }
}
