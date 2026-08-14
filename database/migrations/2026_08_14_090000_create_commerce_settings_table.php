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
        Schema::create('commerce_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('shipping_estimate_min', 10, 2)->default('12.00');
            $table->decimal('shipping_estimate_max', 10, 2)->default('18.00');
            $table->boolean('shipping_eu_included')->default(true);
            $table->string('default_expected_delivery_label')->nullable();
            $table->boolean('prices_include_tax')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_settings');
    }
};
