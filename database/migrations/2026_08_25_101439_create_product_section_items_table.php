<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_section_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_section_id')->constrained()->cascadeOnDelete();
            $table->string('number_label')->nullable();
            $table->string('icon')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_section_items');
    }
};
