<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_line1')->nullable()->after('phone');
            $table->string('shipping_line2')->nullable()->after('shipping_line1');
            $table->string('shipping_city')->nullable()->after('shipping_line2');
            $table->string('shipping_postal_code')->nullable()->after('shipping_city');
            $table->string('shipping_country', 2)->nullable()->after('shipping_postal_code');
            $table->timestamp('processing_at')->nullable()->after('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_postal_code',
                'shipping_country',
                'processing_at',
            ]);
        });
    }
};
