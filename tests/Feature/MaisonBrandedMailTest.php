<?php

use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Mail\OrderConfirmation;
use App\Mail\Orders\AdminNewOrderNotification;
use App\Mail\Orders\OrderStatusUpdated;
use App\Mail\PostDeliveryFollowUp;
use App\Mail\ResetPasswordMail;
use App\Mail\RsvpConfirmation;
use App\Mail\SessionJoinedMail;
use App\Mail\ShippingNotification;
use App\Mail\SoldOutNotice;
use App\Mail\VerifyEmailMail;
use App\Mail\WaitlistConfirmation;
use App\Models\CommunityEvent;
use App\Models\CommunitySession;
use App\Models\Inquiry;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
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
    'reset-password',
    'verify-email',
    'session-joined',
]);

test('verify email notification sends the branded maison mailable', function () {
    $user = User::factory()->unverified()->create(['locale' => 'nl']);

    $mailable = (new VerifyEmail)->toMail($user);

    expect($mailable)->toBeInstanceOf(VerifyEmailMail::class);

    $html = $mailable->render();

    expect($html)
        ->toContain('images/logos/logo-icon.jpg')
        ->toContain('Maison Anversa')
        ->toContain('#291C18');
});

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
        'reset-password' => new ResetPasswordMail(
            otp: '847291',
            expireMinutes: 10,
            locale: 'en',
        ),
        'verify-email' => new VerifyEmailMail(
            verificationUrl: 'https://example.test/email/verify/1/hash',
            locale: 'nl',
        ),
        'session-joined' => new SessionJoinedMail(
            session: CommunitySession::factory()->create(['starts_at' => now()->addDay()]),
            memberName: 'Guest Member',
            locale: 'nl',
        ),
        default => throw new InvalidArgumentException("Unknown mailable key [{$key}]."),
    };
}
