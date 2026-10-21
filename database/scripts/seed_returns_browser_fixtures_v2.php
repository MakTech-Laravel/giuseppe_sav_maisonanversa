<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnReason;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Services\Returns\ReturnService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$product = Product::founding();
$customer = User::where('email', 'customer@dev.com')->firstOrFail();
$user1 = User::where('email', 'user1@dev.com')->firstOrFail();
$user2 = User::where('email', 'user2@dev.com')->firstOrFail();
$registrar = app(FoundingCircleRegistrar::class);
$returns = app(ReturnService::class);

$make = function (User $user, int $edition, array $attrs = []) use ($product, $registrar): Order {
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel($edition))
        ->firstOrFail();

    if ($piece->status !== EditionPieceStatus::Available) {
        throw new RuntimeException("Edition {$edition} not available: {$piece->status->value}");
    }

    // Null PI so local browser QA can refund without calling Stripe.
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

$available = EditionPiece::query()
    ->where('product_id', $product->id)
    ->where('status', EditionPieceStatus::Available)
    ->orderBy('id')
    ->limit(8)
    ->get();

if ($available->count() < 8) {
    throw new RuntimeException('Need 8 available pieces, have '.$available->count());
}

$nums = $available->map(fn ($p) => (int) preg_replace('/\D/', '', $p->edition_number))->values();

$oCancel = $make($customer, $nums[0]);
echo "CANCEL: {$oCancel->id} edition {$nums[0]} customer@dev.com\n";

$oReturn = $make($customer, $nums[1], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(5),
    'delivered_at' => now()->subDays(2),
    'delivered_on' => now()->subDays(2)->toDateString(),
]);
echo "RETURN: {$oReturn->id} edition {$nums[1]} customer@dev.com\n";

$oEnded = $make($user1, $nums[2], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(20),
    'delivered_at' => now()->subDays(16),
    'delivered_on' => now()->subDays(16)->toDateString(),
]);
echo "ENDED: {$oEnded->id} edition {$nums[2]} user1@dev.com\n";

$oWithdraw = $make($user1, $nums[3], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(4),
    'delivered_at' => now()->subDays(1),
    'delivered_on' => now()->subDays(1)->toDateString(),
]);
$withdrawReturn = $returns->request($oWithdraw, $user1, ReturnReason::PreferNotToSay, 'Withdraw fixture', true); // prefer_not_to_say
echo "WITHDRAW: order={$oWithdraw->id} return={$withdrawReturn->number} user1@dev.com\n";

$oDecline = $make($user2, $nums[4], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(6),
    'delivered_at' => now()->subDays(3),
    'delivered_on' => now()->subDays(3)->toDateString(),
]);
Storage::fake('public');
$photo = UploadedFile::fake()->image('damage.jpg', 800, 600);
$declineReturn = $returns->request($oDecline, $user2, ReturnReason::Damaged, 'Decline fixture', true, [$photo]);
echo "DECLINE: order={$oDecline->id} return={$declineReturn->number} user2@dev.com\n";

$oPartial = $make($user2, $nums[5], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(7),
    'delivered_at' => now()->subDays(2),
    'delivered_on' => now()->subDays(2)->toDateString(),
]);
$partialReturn = $returns->request($oPartial, $user2, ReturnReason::Other, null, true);
$returns->addTracking($partialReturn, $user2, 'DHL', 'PARTIAL-V2-1');
echo "PARTIAL: order={$oPartial->id} return={$partialReturn->fresh()->number} user2@dev.com\n";

$oReceive = $make($customer, $nums[6], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(5),
    'delivered_at' => now()->subDays(2),
    'delivered_on' => now()->subDays(2)->toDateString(),
]);
$receiveReturn = $returns->request($oReceive, $customer, ReturnReason::NotExpected, null, true);
$returns->addTracking($receiveReturn, $customer, 'BPOST', 'RECV-V2-1');
echo "RECEIVE: order={$oReceive->id} return={$receiveReturn->fresh()->number} customer@dev.com\n";

$oExtend = $make($customer, $nums[7], [
    'status' => OrderStatus::Delivered,
    'shipped_at' => now()->subDays(12),
    'delivered_at' => now()->subDays(10),
    'delivered_on' => now()->subDays(10)->toDateString(),
]);
echo "EXTEND: {$oExtend->id} edition {$nums[7]} customer@dev.com (near end of window)\n";

echo "DONE\n";
