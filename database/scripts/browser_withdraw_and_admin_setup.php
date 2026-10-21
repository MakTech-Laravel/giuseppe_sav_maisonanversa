<?php

use App\Enums\ReturnReason;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\Returns\ReturnService;

$service = app(ReturnService::class);

// Withdraw MA-R-0002 (order 4 / user1)
$withdraw = OrderReturn::query()->findOrFail(2);
$user1 = User::query()->where('email', 'user1@dev.com')->firstOrFail();
$service->cancel($withdraw, $user1);
$withdraw->refresh();
$piece4 = EditionPiece::query()->find(Order::query()->find(4)->edition_piece_id);
echo "WITHDRAW: {$withdraw->status->value} piece={$piece4?->status->value}\n";

// Create returns for admin decline (order 5) and partial (order 6)
$user2 = User::query()->where('email', 'user2@dev.com')->firstOrFail();

$decline = $service->request(Order::query()->findOrFail(5), $user2, ReturnReason::Damaged, 'Browser decline fixture', true);
echo "DECLINE_RETURN: {$decline->number} id={$decline->id}\n";

$partial = $service->request(Order::query()->findOrFail(6), $user2, ReturnReason::Other, null, true);
$service->addTracking($partial, $user2, 'DHL', 'PARTIAL-TRACK-1');
echo "PARTIAL_RETURN: {$partial->fresh()->number} id={$partial->id} status={$partial->fresh()->status->value}\n";

echo "OPEN_RETURNS:\n";
foreach (OrderReturn::query()->orderBy('id')->get(['id', 'number', 'status', 'order_id', 'maison_pays_return_shipping']) as $row) {
    echo "{$row->id} {$row->number} {$row->status->value} order={$row->order_id} pays_ship=".($row->maison_pays_return_shipping ? '1' : '0')."\n";
}
