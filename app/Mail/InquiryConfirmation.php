<?php

namespace App\Mail;

use App\Models\Inquiry;
use App\Support\BrandsMaisonMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryConfirmation extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry)
    {
        $this->locale($inquiry->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __($this->inquiry->type->confirmationSubject()),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.inquiry-confirmation',
            with: $this->maisonBrandData($this->inquiry->locale),
        );
    }
}
