<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
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
        Log::info('Brevo order contact upsert skipped (no API key).', [
            'order_id' => $order->id,
            'email' => $order->user?->email ?: $order->email,
        ]);
    }

    public function ensureProductList(Product $product): ?int
    {
        Log::info('Brevo product list create skipped (no API key).', [
            'product_id' => $product->id,
        ]);

        return null;
    }

    public function deleteProductList(int $listId): void
    {
        Log::info('Brevo product list delete skipped (no API key).', [
            'list_id' => $listId,
        ]);
    }
}
