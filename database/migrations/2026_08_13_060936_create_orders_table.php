<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(OrderStatus::Incomplete->value)->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->unsignedSmallInteger('edition_number')->nullable();
            $table->string('monogram', 3)->nullable();
            $table->boolean('gift_wrap')->default(false);
            $table->text('gift_message')->nullable();
            $table->string('currency', 3)->default('eur');
            $table->unsignedInteger('amount');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
