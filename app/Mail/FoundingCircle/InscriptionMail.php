<?php

namespace App\Mail\FoundingCircle;

use App\Models\FoundingCircleRegisterEntry;
use App\Support\BrandsMaisonMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscriptionMail extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public FoundingCircleRegisterEntry $entry)
    {
        $this->entry->loadMissing('user', 'order');
        $this->locale($this->entry->order?->locale ?? $this->entry->user?->locale);
    }

    public function envelope(): Envelope
    {
        $number = str_pad((string) $this->entry->edition_number, 3, '0', STR_PAD_LEFT);

        return new Envelope(
            subject: __('Nº :number is nu van u — Founding Circle', ['number' => $number]),
        );
    }

    public function content(): Content
    {
        $number = str_pad((string) $this->entry->edition_number, 3, '0', STR_PAD_LEFT);
        $locale = $this->entry->order?->locale ?? $this->entry->user?->locale;

        return new Content(
            html: 'emails.founding-circle.inscription',
            with: array_merge($this->maisonBrandData($locale), [
                'number' => $number,
                'memberName' => $this->entry->user?->first_name ?? $this->entry->name,
                'listingUrl' => route('member.register-listing', [
                    'locale' => $locale ?? config('maison.default_locale'),
                ]),
            ]),
        );
    }
}
