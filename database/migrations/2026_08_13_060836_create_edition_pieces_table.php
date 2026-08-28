<?php

use App\Enums\EditionPieceStatus;
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
        Schema::create('edition_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('edition_number')->index();
            $table->string('status')->default(EditionPieceStatus::Available->value)->index();
            $table->unsignedBigInteger('order_id')->nullable()->unique();
            $table->timestamp('reserved_until')->nullable()->index();
            $table->timestamp('allocated_at')->nullable();
            $table->uuid('verification_token')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'edition_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edition_pieces');
    }
};
