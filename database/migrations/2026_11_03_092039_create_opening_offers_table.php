<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The product the homepage opening offers for reservation. Rows are managed in
     * the admin; exactly one active row is shown (activating one deactivates the rest).
     */
    public function up(): void
    {
        Schema::create('opening_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Continuity: until an admin chooses otherwise, the opening keeps offering the
        // founding product it has always offered.
        $foundingId = DB::table('products')->where('slug', 'heritage-no-001')->value('id');

        if ($foundingId !== null) {
            DB::table('opening_offers')->insert([
                'product_id' => $foundingId,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_offers');
    }
};
