<?php

namespace App\Mail\Orders;

use App\Models\Order;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewOrderNotification extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale(MailLocale::resolve(null));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Nieuwe bestelling — :reference', [
                'reference' => $this->order->reference(),
            ]),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('product');

        return new Content(
            html: 'emails.orders.admin-new-order',
            with: $this->maisonBrandData(withHomeCta: false),
        );
    }
}
