<?php

use App\Enums\FoundingCircleClaimSource;
use App\Enums\FoundingCircleClaimStatus;
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
        Schema::create('founding_circle_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_piece_id')->nullable()->constrained('edition_pieces')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('edition_number');
            $table->string('status')->default(FoundingCircleClaimStatus::Pending->value)->index();
            $table->string('source')->default(FoundingCircleClaimSource::Manual->value);
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'edition_number', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('founding_circle_claims');
    }
};
