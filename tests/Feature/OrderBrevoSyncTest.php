<?php

use App\Jobs\SyncOrderToBrevo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Brevo\HttpBrevoContacts;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('the order sync job upserts the login email onto the product list', function () {
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
    Product::query()->where('slug', Product::FOUNDING_SLUG)->update([
        'brevo_list_id' => 55,
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
            && $payload['listIds'] === [55]
            && ! in_array(2, $payload['listIds'], true)
            && ! in_array(12, $payload['listIds'], true)
            && $payload['attributes']['FIRSTNAME'] === 'Ada Lovelace'
            && $payload['attributes']['SMS'] === '+32470123456'
            && $payload['attributes']['PRODUCT'] === 'Heritage No.001 — Founding Edition'
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

    Product::query()->where('slug', Product::FOUNDING_SLUG)->update([
        'brevo_list_id' => 55,
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
            && $payload['listIds'] === [55]
            && ! array_key_exists('SMS', $payload['attributes']);
    });
});

test('the order sync job skips brevo when the order has no product', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 46]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
    ]);

    $order = Order::factory()->create([
        'email' => 'ada@example.com',
        'product_id' => null,
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    Http::assertNothingSent();
});

test('the order sync job uses only the stored product list', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 47]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
    ]);

    $product = Product::factory()->create([
        'name' => 'Atelier Coat',
        'brevo_list_id' => 55,
    ]);
    $order = Order::factory()->create([
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request->data()['listIds'] === [55];
    });
});

test('the order sync job retries without sms when brevo rejects the phone', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::sequence()
            ->push(['code' => 'invalid_parameter', 'message' => 'Invalid phone number'], 400)
            ->push(['id' => 48], 201),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
    ]);

    $product = Product::factory()->create([
        'brevo_list_id' => 55,
    ]);
    $order = Order::factory()->create([
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
        'phone' => 'not-a-phone',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && ! array_key_exists('SMS', $request->data()['attributes']);
    });
});
