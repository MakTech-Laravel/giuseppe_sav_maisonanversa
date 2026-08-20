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
        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('sold_out_behavior');
            $table->json('specs')->nullable()->after('gallery');
            $table->json('materials')->nullable()->after('specs');
            $table->json('unboxing_steps')->nullable()->after('materials');
            $table->json('includes')->nullable()->after('unboxing_steps');
            $table->json('guarantees')->nullable()->after('includes');
            $table->json('trust_badges')->nullable()->after('guarantees');
            $table->text('hero_subtitle')->nullable()->after('trust_badges');
            $table->string('status')->default('active')->after('hero_subtitle');
            $table->integer('sort_order')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'gallery',
                'specs',
                'materials',
                'unboxing_steps',
                'includes',
                'guarantees',
                'trust_badges',
                'hero_subtitle',
                'status',
                'sort_order',
            ]);
        });
    }
};
