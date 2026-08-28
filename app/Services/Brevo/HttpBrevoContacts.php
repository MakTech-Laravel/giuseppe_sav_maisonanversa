<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
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
}
