<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class NullBrevoContacts implements BrevoContacts
{
    public function upsertHeritageLetterContact(NewsletterSubscriber $subscriber): ?string
    {
        Log::info('Brevo contact upsert skipped (no API key).', [
            'email' => $subscriber->email,
            'locale' => $subscriber->locale,
        ]);

        return null;
    }

    public function unsubscribeHeritageLetterContact(NewsletterSubscriber $subscriber): void
    {
        Log::info('Brevo contact unsubscribe skipped (no API key).', [
            'email' => $subscriber->email,
        ]);
    }

    public function upsertOrderContact(Order $order): void
    {
        $order->loadMissing('user');

        Log::info('Brevo order contact upsert skipped (no API key).', [
            'order_id' => $order->id,
            'email' => $order->user?->email ?: $order->email,
        ]);
    }
}
