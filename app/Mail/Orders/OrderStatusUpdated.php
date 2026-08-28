<?php

namespace App\Mail\Orders;

use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Support\MailLocale;
use App\Support\OrderPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public string $statusLabel;

    public function __construct(
        public Order $order,
        public OrderStatusEvent $event,
    ) {
        $this->locale(MailLocale::resolve($order->locale));
        $this->statusLabel = app(OrderPresenter::class)->statusLabel($event->status);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Bestelupdate — :reference', [
                'reference' => $this->order->reference(),
            ]),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('product');

        return new Content(
            markdown: 'emails.orders.status-updated',
            with: [
                'statusLabel' => $this->statusLabel,
            ],
        );
    }
}
