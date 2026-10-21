<?php

namespace App\Mail\Returns;

use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AdminReturnDigestMail extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    /**
     * @param  Collection<int, array{number: string, order: string, due: string, reason: string}>  $rows
     */
    public function __construct(public Collection $rows)
    {
        $this->locale(MailLocale::resolve(config('maison.notification_fallback_locale')));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Returns needing attention ('.$this->rows->count().')',
        );
    }

    public function content(): Content
    {
        $locale = config('maison.default_locale', 'nl');

        return new Content(
            html: 'emails.returns.admin-digest',
            with: [
                ...$this->maisonBrandData($locale),
                'indexUrl' => route('admin.returns.index', ['locale' => $locale]),
            ],
        );
    }
}
