<?php

use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Mail\OrderConfirmation;
use App\Mail\Orders\AdminNewOrderNotification;
use App\Mail\Orders\OrderStatusUpdated;
use App\Mail\PostDeliveryFollowUp;
use App\Mail\RsvpConfirmation;
use App\Mail\ShippingNotification;
use App\Mail\SoldOutNotice;
use App\Mail\WaitlistConfirmation;
use App\Models\CommunityEvent;
use App\Models\Inquiry;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use Illuminate\Mail\Mailable;

test('branded maison mailables include the site logo', function (string $key) {
    $mailable = brandedMailable($key);

    $html = $mailable->render();

    expect($html)
        ->toContain('images/logos/logo-icon.jpg')
        ->toContain('alt="Maison Anversa"')
        ->toContain('#F3EBE3');
})->with([
    'order-confirmation',
    'shipping-notification',
    'sold-out-notice',
    'waitlist-confirmation',
    'inquiry-confirmation',
    'inquiry-received',
    'post-delivery-follow-up',
    'rsvp-confirmation',
    'admin-new-order',
    'status-updated',
]);

function brandedMailable(string $key): Mailable
{
    return match ($key) {
        'order-confirmation' => new OrderConfirmation(Order::factory()->create()),
        'shipping-notification' => new ShippingNotification(Order::factory()->create(['edition_number' => 7])),
        'sold-out-notice' => new SoldOutNotice(NewsletterSubscriber::factory()->create()),
        'waitlist-confirmation' => new WaitlistConfirmation(NewsletterSubscriber::factory()->create()),
        'inquiry-confirmation' => new InquiryConfirmation(Inquiry::factory()->create()),
        'inquiry-received' => new InquiryReceived(Inquiry::factory()->create()),
        'post-delivery-follow-up' => new PostDeliveryFollowUp(Order::factory()->create(['edition_number' => 12])),
        'rsvp-confirmation' => new RsvpConfirmation(CommunityEvent::factory()->create()),
        'admin-new-order' => new AdminNewOrderNotification(Order::factory()->create()),
        'status-updated' => (function (): OrderStatusUpdated {
            $order = Order::factory()->create(['edition_number' => 3]);
            $event = OrderStatusEvent::factory()->forOrder($order)->create();

            return new OrderStatusUpdated($order, $event);
        })(),
        default => throw new InvalidArgumentException("Unknown mailable key [{$key}]."),
    };
}
