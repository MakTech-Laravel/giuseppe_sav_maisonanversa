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
        Schema::create('home_heroes', function (Blueprint $table) {
            $table->id();
            $table->string('image_path')->nullable();
            $table->string('eyebrow', 160);
            $table->string('title', 80);
            $table->string('title_accent', 80);
            $table->string('tagline');
            $table->boolean('show_counter')->default(true);
            $table->string('counter_line_one', 80);
            $table->string('counter_line_two', 80);
            $table->string('primary_label', 80);
            $table->string('primary_action', 32);
            $table->string('primary_target', 500)->nullable();
            $table->string('secondary_label', 80);
            $table->string('secondary_action', 32);
            $table->string('secondary_target', 500)->nullable();
            $table->string('tertiary_label', 80);
            $table->string('tertiary_action', 32);
            $table->string('tertiary_target', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_heroes');
    }
};
