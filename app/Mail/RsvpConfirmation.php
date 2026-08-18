<?php

namespace App\Mail;

use App\Models\CommunityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RsvpConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CommunityEvent $event)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('RSVP bevestigd — :title', [
                'title' => $this->event->translated('title'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.rsvp-confirmation',
        );
    }
}
