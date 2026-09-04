<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founding_circle_register', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('edition_number')->nullable();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->index('edition_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founding_circle_register');
    }
};
