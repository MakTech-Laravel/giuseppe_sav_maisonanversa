<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_session_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->json('team_a');
            $table->json('team_b');
            $table->json('sets');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['community_session_id', 'status'], 'css_session_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_session_scores');
    }
};
