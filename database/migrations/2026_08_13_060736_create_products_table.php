<?php

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('simple');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('eur');
            $table->unsignedInteger('edition_total')->nullable();
            $table->json('archive_edition_numbers')->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('grants_founding_circle')->default(false);
            $table->string('expected_delivery_label')->nullable();
            $table->string('sold_out_behavior')->default('keep_page');
            $table->string('stripe_product_id')->nullable()->unique();
            $table->string('stripe_price_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
