<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpBrevoContacts implements BrevoContacts
{
    public function upsertHeritageLetterContact(NewsletterSubscriber $subscriber): ?string
    {
        $listId = (int) config('services.brevo.list_heritage_letter');
        $apiKey = (string) config('services.brevo.api_key');

        $preferences = $subscriber->topicPreferences();

        $payload = [
            'email' => $subscriber->email,
            'attributes' => [
                'LANGUAGE' => strtoupper($subscriber->locale),
                'FIRSTNAME' => $subscriber->name ?? '',
                'HERITAGE_LETTER' => $preferences['heritageLetter'],
                'PRODUCT_UPDATES' => $preferences['productUpdates'],
                'EVENTS' => $preferences['events'],
            ],
            'updateEnabled' => true,
        ];

        if ($listId > 0) {
            $payload['listIds'] = [$listId];
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
            ])
                ->timeout(15)
                ->retry(2, 200)
                ->post('https://api.brevo.com/v3/contacts', $payload);

            $response->throw();

            $id = $response->json('id');

            return $id !== null ? (string) $id : $subscriber->email;
        } catch (RequestException $exception) {
            Log::warning('Brevo contact upsert failed.', [
                'email' => $subscriber->email,
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }
    }

    public function unsubscribeHeritageLetterContact(NewsletterSubscriber $subscriber): void
    {
        $listId = (int) config('services.brevo.list_heritage_letter');
        $apiKey = (string) config('services.brevo.api_key');

        if ($listId <= 0) {
            return;
        }

        Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
        ])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/contacts/lists/'.$listId.'/contacts/remove', [
                'emails' => [$subscriber->email],
            ])
            ->throw();
    }

    public function upsertOrderContact(Order $order): void
    {
        $listId = (int) config('services.brevo.list_orders');
        $apiKey = (string) config('services.brevo.api_key');

        $order->loadMissing('user', 'product');
        $email = $this->orderContactEmail($order);

        if ($listId <= 0) {
            Log::info('Brevo order contact upsert skipped (no list id).', [
                'order_id' => $order->id,
                'email' => $email,
            ]);

            return;
        }

        $attributes = [
            'FIRSTNAME' => $order->name ?? '',
            'NOTE' => $this->orderNote($order),
        ];

        if (filled($order->phone)) {
            $attributes['SMS'] = $order->phone;
        }

        $payload = [
            'email' => $email,
            'attributes' => $attributes,
            'listIds' => [$listId],
            'updateEnabled' => true,
        ];

        try {
            Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
            ])
                ->timeout(15)
                ->retry(2, 200)
                ->post('https://api.brevo.com/v3/contacts', $payload)
                ->throw();
        } catch (RequestException $exception) {
            Log::warning('Brevo order contact upsert failed.', [
                'order_id' => $order->id,
                'email' => $email,
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }
    }

    private function orderContactEmail(Order $order): string
    {
        return $order->user?->email ?: $order->email;
    }

    private function orderNote(Order $order): string
    {
        $parts = [$order->reference()];
        $productName = $order->product?->translated('name', $order->locale);

        if (filled($productName)) {
            $parts[] = $productName;
        }

        if (filled($order->gift_message)) {
            $parts[] = $order->gift_message;
        }

        return implode(' · ', $parts);
    }
}
