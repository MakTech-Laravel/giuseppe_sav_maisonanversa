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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('edition_piece_id')->nullable()->unique()->after('user_id')->constrained('edition_pieces')->nullOnDelete();
            $table->string('locale', 5)->default('nl')->after('email');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edition_piece_id');
            $table->dropColumn(['locale', 'shipped_at', 'delivered_at', 'follow_up_sent_at']);
        });
    }
};
