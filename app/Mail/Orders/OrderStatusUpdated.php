<?php

namespace App\Mail\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Services\Returns\GuestReturnLink;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use App\Support\OrderPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use BrandsMaisonMail, Queueable, SerializesModels;

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
        $locale = MailLocale::resolve($this->order->locale);
        $showReturns = $this->event->status === OrderStatus::Delivered
            || $this->order->status === OrderStatus::Delivered;
        $returnsUrl = null;

        if ($showReturns) {
            $returnsUrl = app(GuestReturnLink::class)->createUrl($this->order, $locale)
                ?? ($this->order->user_id
                    ? route('member.orders', ['locale' => $locale])
                    : route('maison.returns', ['locale' => $locale]));
        }

        return new Content(
            html: 'emails.orders.status-updated',
            with: [
                ...$this->maisonBrandData($this->order->locale),
                'statusLabel' => $this->statusLabel,
                'returnsUrl' => $returnsUrl,
            ],
        );
    }
}
