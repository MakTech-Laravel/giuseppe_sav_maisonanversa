<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();
            $table->text('intro')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_key')->nullable();
            $table->boolean('is_visible')->default(true);
            // Related section only: appends the editorial "Het Huis" card.
            $table->boolean('include_house_card')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'key']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sections');
    }
};
