<?php

use App\Jobs\SyncOrderToBrevo;
use App\Models\Order;
use App\Models\Product;
use App\Services\Brevo\HttpBrevoContacts;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('creating a product does not create a brevo list', function () {
    Http::fake();

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_folder_id' => 4,
    ]);

    $product = Product::factory()->create([
        'name' => 'Atelier Coat',
    ]);

    expect($product->fresh()->brevo_list_id)->toBeNull();

    Http::assertNotSent(function (Request $request): bool {
        return str_contains($request->url(), 'api.brevo.com');
    });
});

test('the first purchase creates the product list and stores the buyer only there', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts/lists' => Http::response(['id' => 90]),
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 44]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
        'services.brevo.list_folder_id' => 4,
        'services.brevo.list_heritage_letter' => 12,
    ]);

    $product = Product::factory()->create([
        'name' => 'Atelier Coat',
    ]);
    $order = Order::factory()->create([
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    expect($product->fresh()->brevo_list_id)->toBe(90);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/contacts/lists'
            && $request->data()['name'] === 'Atelier Coat'
            && $request->data()['folderId'] === 4;
    });
    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $payload['listIds'] === [90]
            && ! in_array(2, $payload['listIds'], true)
            && ! in_array(12, $payload['listIds'], true)
            && $payload['attributes']['PRODUCT'] === 'Atelier Coat';
    });
});

test('the first purchase creates a folder when none is configured', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts/folders*' => function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response(['folders' => [], 'count' => 0]);
            }

            return Http::response(['id' => 3]);
        },
        'https://api.brevo.com/v3/contacts/lists' => Http::response(['id' => 91]),
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 46]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_folder_id' => '',
    ]);

    $product = Product::factory()->create([
        'name' => 'Atelier Coat',
    ]);
    $order = Order::factory()->create([
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    expect($product->fresh()->brevo_list_id)->toBe(91);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/contacts/folders'
            && $request->data()['name'] === 'Products';
    });
    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/contacts/lists'
            && $request->data()['folderId'] === 3;
    });
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request->data()['listIds'] === [91];
    });
});

test('a later purchase reuses the stored product list', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 45]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_orders' => 2,
        'services.brevo.list_folder_id' => 4,
    ]);

    $product = Product::factory()->create([
        'name' => 'Atelier Coat',
        'brevo_list_id' => 90,
    ]);
    $order = Order::factory()->create([
        'product_id' => $product->id,
        'email' => 'buyer@example.com',
    ]);

    (new SyncOrderToBrevo($order))->handle(new HttpBrevoContacts);

    expect($product->fresh()->brevo_list_id)->toBe(90);

    Http::assertNotSent(function (Request $request): bool {
        return str_contains($request->url(), '/v3/contacts/lists');
    });
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request->data()['listIds'] === [90];
    });
});

test('deleting an unsold product deletes only its brevo list', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts/lists/*' => Http::response(null, 204),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
    ]);

    $product = Product::factory()->create([
        'brevo_list_id' => 77,
    ]);

    $product->delete();

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'DELETE'
            && $request->url() === 'https://api.brevo.com/v3/contacts/lists/77';
    });
    Http::assertNotSent(function (Request $request): bool {
        return str_contains($request->url(), '/v3/contacts')
            && ! str_contains($request->url(), '/v3/contacts/lists');
    });
});

test('a missing brevo api key makes no list request', function () {
    Http::fake();

    config([
        'services.brevo.api_key' => '',
        'services.brevo.list_folder_id' => 4,
    ]);

    $product = Product::factory()->create();

    expect($product->fresh()->brevo_list_id)->toBeNull();

    Http::assertNotSent(function (Request $request): bool {
        return str_contains($request->url(), 'api.brevo.com');
    });
});
