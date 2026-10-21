<?php

use App\Enums\ReturnReason;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\Returns\ReturnService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$service = app(ReturnService::class);
$user2 = User::query()->where('email', 'user2@dev.com')->firstOrFail();

Storage::fake('public');
$photo = UploadedFile::fake()->image('damage.jpg', 800, 600);

$decline = $service->request(
    Order::query()->findOrFail(5),
    $user2,
    ReturnReason::Damaged,
    'Browser decline fixture',
    true,
    [$photo],
);
echo "DECLINE_RETURN: {$decline->number} id={$decline->id} pays={$decline->maison_pays_return_shipping}\n";

$partial = $service->request(
    Order::query()->findOrFail(6),
    $user2,
    ReturnReason::Other,
    null,
    true,
);
$service->addTracking($partial, $user2, 'DHL', 'PARTIAL-TRACK-1');
echo "PARTIAL_RETURN: {$partial->fresh()->number} id={$partial->id} status={$partial->fresh()->status->value}\n";

foreach (OrderReturn::query()->orderBy('id')->get(['id', 'number', 'status', 'order_id', 'maison_pays_return_shipping']) as $row) {
    echo "{$row->id} {$row->number} {$row->status->value} order={$row->order_id} pays_ship=".($row->maison_pays_return_shipping ? '1' : '0')."\n";
}
