<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['community_session_id', 'created_at'], 'csm_session_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_session_messages');
    }
};
