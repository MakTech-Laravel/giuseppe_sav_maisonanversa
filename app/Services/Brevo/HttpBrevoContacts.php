<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
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
        $apiKey = (string) config('services.brevo.api_key');

        $order->loadMissing('user', 'product');
        $email = $this->orderContactEmail($order);
        $listIds = $this->orderListIds($order);

        if ($listIds === []) {
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

        $productName = $this->orderProductName($order);

        if (filled($productName)) {
            $attributes['PRODUCT'] = $productName;
        }

        $payload = [
            'email' => $email,
            'attributes' => $attributes,
            'listIds' => $listIds,
            'updateEnabled' => true,
        ];

        try {
            $this->postContact($apiKey, $payload);
        } catch (RequestException $exception) {
            if (isset($attributes['SMS']) && $this->phoneWasRejected($exception)) {
                unset($attributes['SMS']);
                $payload['attributes'] = $attributes;

                try {
                    $this->postContact($apiKey, $payload);

                    return;
                } catch (RequestException $retry) {
                    $exception = $retry;
                }
            }

            Log::warning('Brevo order contact upsert failed.', [
                'order_id' => $order->id,
                'email' => $email,
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postContact(string $apiKey, array $payload): void
    {
        Http::withHeaders($this->brevoHeaders($apiKey))
            ->timeout(15)
            ->retry(2, 200, function ($exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response?->serverError() ?? false));
            }, throw: false)
            ->post('https://api.brevo.com/v3/contacts', $payload)
            ->throw();
    }

    private function phoneWasRejected(RequestException $exception): bool
    {
        $message = strtolower((string) $exception->response?->json('message'));

        return $exception->response?->status() === 400
            && str_contains($message, 'phone');
    }

    public function ensureProductList(Product $product): ?int
    {
        if (filled($product->brevo_list_id)) {
            return (int) $product->brevo_list_id;
        }

        $apiKey = (string) config('services.brevo.api_key');

        if ($apiKey === '') {
            return null;
        }

        $folderId = $this->productListFolderId($apiKey);

        if ($folderId <= 0) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
            ])
                ->timeout(15)
                ->retry(2, 200)
                ->post('https://api.brevo.com/v3/contacts/lists', [
                    'name' => $product->name,
                    'folderId' => $folderId,
                ]);

            $response->throw();
        } catch (RequestException $exception) {
            Log::warning('Brevo product list create failed.', [
                'product_id' => $product->id,
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }

        $listId = (int) $response->json('id');

        if ($listId <= 0) {
            return null;
        }

        $product->forceFill(['brevo_list_id' => $listId])->save();

        return $listId;
    }

    public function deleteProductList(int $listId): void
    {
        $apiKey = (string) config('services.brevo.api_key');

        if ($apiKey === '' || $listId <= 0) {
            return;
        }

        try {
            Http::withHeaders([
                'api-key' => $apiKey,
                'accept' => 'application/json',
            ])
                ->timeout(15)
                ->retry(2, 200)
                ->delete('https://api.brevo.com/v3/contacts/lists/'.$listId)
                ->throw();
        } catch (RequestException $exception) {
            Log::warning('Brevo product list delete failed.', [
                'list_id' => $listId,
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return list<int>
     */
    private function orderListIds(Order $order): array
    {
        if ($order->product === null) {
            return [];
        }

        $productListId = $this->ensureProductList($order->product);

        if ($productListId === null || $productListId <= 0) {
            return [];
        }

        return [$productListId];
    }

    private function productListFolderId(string $apiKey): int
    {
        $configured = (int) config('services.brevo.list_folder_id');

        if ($configured > 0) {
            return $configured;
        }

        try {
            $listed = Http::withHeaders($this->brevoHeaders($apiKey))
                ->timeout(15)
                ->retry(2, 200)
                ->get('https://api.brevo.com/v3/contacts/folders', [
                    'limit' => 50,
                    'offset' => 0,
                ]);

            $listed->throw();
        } catch (RequestException $exception) {
            Log::warning('Brevo product folder lookup failed.', [
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }

        foreach ($listed->json('folders') ?? [] as $folder) {
            if (($folder['name'] ?? '') === 'Products') {
                return (int) ($folder['id'] ?? 0);
            }
        }

        try {
            $created = Http::withHeaders($this->brevoHeaders($apiKey))
                ->timeout(15)
                ->retry(2, 200)
                ->post('https://api.brevo.com/v3/contacts/folders', [
                    'name' => 'Products',
                ]);

            $created->throw();
        } catch (RequestException $exception) {
            Log::warning('Brevo product folder create failed.', [
                'status' => $exception->response?->status(),
            ]);

            throw $exception;
        }

        return (int) $created->json('id');
    }

    /**
     * @return array{api-key: string, accept: string}
     */
    private function brevoHeaders(string $apiKey): array
    {
        return [
            'api-key' => $apiKey,
            'accept' => 'application/json',
        ];
    }

    private function orderContactEmail(Order $order): string
    {
        return $order->user?->email ?: $order->email;
    }

    private function orderProductName(Order $order): string
    {
        return (string) ($order->product?->translated('name', $order->locale) ?? '');
    }

    private function orderNote(Order $order): string
    {
        $parts = [$order->reference()];
        $productName = $this->orderProductName($order);

        if (filled($productName)) {
            $parts[] = $productName;
        }

        if (filled($order->gift_message)) {
            $parts[] = $order->gift_message;
        }

        return implode(' · ', $parts);
    }
}
