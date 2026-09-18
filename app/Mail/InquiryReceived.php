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

class InquiryReceived extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry)
    {
        //
    }

    public function envelope(): Envelope
    {
        $subject = $this->inquiry->subject
            ?: __('Nieuwe :type aanvraag', [
                'type' => $this->inquiry->type->value,
            ]);

        return new Envelope(
            subject: $subject,
            replyTo: [$this->inquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.inquiry-received',
            with: $this->maisonBrandData(withHomeCta: false),
        );
    }
}
