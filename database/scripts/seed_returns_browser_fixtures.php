<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\FoundingCircle\FoundingCircleRegistrar;

$product = Product::founding();
$customer = User::where('email', 'customer@dev.com')->firstOrFail();
$user1 = User::where('email', 'user1@dev.com')->firstOrFail();
$user2 = User::where('email', 'user2@dev.com')->firstOrFail();
$registrar = app(FoundingCircleRegistrar::class);

$make = function (User $user, int $edition, array $attrs = []) use ($product, $registrar): Order {
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel($edition))
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->paid()->create(array_merge([
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => $edition,
        'amount' => '249.00',
        'name' => $user->name,
        'email' => $user->email,
        'stripe_payment_intent_id' => null,
    ], $attrs));

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Paid,
        'stripe_payment_intent_id' => null,
        'amount' => $order->amount,
    ]);

    $piece->fill([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'allocated_at' => now(),
    ])->save();

    $registrar->inscribeFromOrder($order);

    return $order->fresh();
};

$o1 = $make($customer, 21);
echo "CANCEL: {$o1->id} edition 21 for customer@dev.com\n";

$o2 = $make($customer, 22, [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(5),
    'delivered_at' => now()->subDays(2),
    'delivered_on' => now()->subDays(2)->toDateString(),
]);
echo "RETURN: {$o2->id} edition 22 for customer@dev.com\n";

$o3 = $make($user1, 23, [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(20),
    'delivered_at' => now()->subDays(16),
    'delivered_on' => now()->subDays(16)->toDateString(),
]);
echo "ENDED: {$o3->id} edition 23 for user1@dev.com\n";

$o4 = $make($user1, 24, [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(4),
    'delivered_at' => now()->subDays(1),
    'delivered_on' => now()->subDays(1)->toDateString(),
]);
echo "WITHDRAW: {$o4->id} edition 24 for user1@dev.com\n";

$o5 = $make($user2, 25, [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(6),
    'delivered_at' => now()->subDays(3),
    'delivered_on' => now()->subDays(3)->toDateString(),
]);
echo "DECLINE: {$o5->id} edition 25 for user2@dev.com\n";

$o6 = $make($user2, 26, [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(7),
    'delivered_at' => now()->subDays(2),
    'delivered_on' => now()->subDays(2)->toDateString(),
]);
echo "PARTIAL: {$o6->id} edition 26 for user2@dev.com\n";

$o7 = $make($customer, 27, [
    'status' => OrderStatus::Shipped,
    'shipped_at' => now()->subDays(3),
]);
echo "SHIPPED: {$o7->id} edition 27 for customer@dev.com\n";

echo "DONE\n";
