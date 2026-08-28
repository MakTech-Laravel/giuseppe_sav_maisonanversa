<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('orders')
            ->whereNotNull('stripe_checkout_session_id')
            ->orderBy('id')
            ->get(['id', 'status', 'amount', 'currency', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'created_at', 'updated_at']);

        foreach ($orders as $order) {
            $exists = DB::table('payments')
                ->where('stripe_checkout_session_id', $order->stripe_checkout_session_id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('payments')->insert([
                'order_id' => $order->id,
                'status' => $this->paymentStatusFromOrderStatus((string) $order->status),
                'amount' => $order->amount,
                'currency' => $order->currency ?: 'eur',
                'provider' => 'stripe',
                'stripe_checkout_session_id' => $order->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('payments')->delete();
    }

    private function paymentStatusFromOrderStatus(string $status): string
    {
        return match ($status) {
            OrderStatus::Incomplete->value => PaymentStatus::Pending->value,
            OrderStatus::Canceled->value => PaymentStatus::Canceled->value,
            OrderStatus::Failed->value => PaymentStatus::Failed->value,
            OrderStatus::Refunded->value => PaymentStatus::Refunded->value,
            OrderStatus::Paid->value,
            OrderStatus::Processing->value,
            OrderStatus::Shipped->value,
            OrderStatus::Delivered->value => PaymentStatus::Paid->value,
            default => PaymentStatus::Pending->value,
        };
    }
};
