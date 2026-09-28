<?php

use App\Jobs\SyncOrderToBrevo;
use App\Models\Order;
use App\Models\User;
use App\Services\Brevo\HttpBrevoContacts;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('the order sync job upserts the login email onto brevo list 2', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 44]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
        'services.brevo.list_heritage_letter' => 12,
    ]);

    $member = User::factory()->create([
        'email' => 'member@example.com',
    ]);
    $order = Order::factory()->forUser($member)->create([
        'name' => 'Ada Lovelace',
        'email' => 'guest@example.com',
        'phone' => '+32470123456',
        'gift_message' => 'For the archive',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    Http::assertSent(function (Request $request) use ($order): bool {
        $payload = $request->data();
        $note = $payload['attributes']['NOTE'] ?? '';

        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $payload['email'] === 'member@example.com'
            && $payload['listIds'] === [2]
            && ! in_array(12, $payload['listIds'], true)
            && $payload['attributes']['FIRSTNAME'] === 'Ada Lovelace'
            && $payload['attributes']['SMS'] === '+32470123456'
            && str_contains($note, $order->reference())
            && str_contains($note, 'Heritage No.001')
            && str_contains($note, 'For the archive')
            && $payload['updateEnabled'] === true;
    });
});

test('the order sync job omits sms when the order has no phone', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 45]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
    ]);

    $order = Order::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => null,
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $payload['email'] === 'ada@example.com'
            && $payload['listIds'] === [2]
            && ! array_key_exists('SMS', $payload['attributes']);
    });
});
