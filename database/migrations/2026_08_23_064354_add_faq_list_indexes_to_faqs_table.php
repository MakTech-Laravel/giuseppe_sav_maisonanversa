<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table): void {
            $table->index(['context', 'is_published', 'sort_order'], 'faqs_context_published_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table): void {
            $table->dropIndex('faqs_context_published_sort_index');
        });
    }
};
