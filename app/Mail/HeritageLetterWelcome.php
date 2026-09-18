<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class HeritageLetterWelcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
        $this->locale($subscriber->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Welkom bij de Heritage Letter.'),
        );
    }

    public function content(): Content
    {
        $locale = $this->subscriber->locale;

        return new Content(
            html: 'emails.heritage-letter-welcome',
            with: [
                'logoUrl' => asset('images/logos/logo-icon.jpg'),
                'homeUrl' => route('maison.home', ['locale' => $locale]),
                'unsubscribeUrl' => URL::signedRoute('maison.heritage-letter.unsubscribe', [
                    'locale' => $locale,
                    'subscriber' => $this->subscriber,
                ]),
            ],
        );
    }
}
