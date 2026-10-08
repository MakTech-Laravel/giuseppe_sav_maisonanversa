<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('story_pages', function (Blueprint $table) {
            $table->string('hero_image_path')->nullable();
            $table->string('city_image_path')->nullable();
            $table->string('name_image_path')->nullable();
            $table->string('make_image_one_path')->nullable();
            $table->string('make_image_two_path')->nullable();
            $table->string('make_image_three_path')->nullable();
            $table->string('founder_image_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('story_pages', function (Blueprint $table) {
            $table->dropColumn([
                'hero_image_path',
                'city_image_path',
                'name_image_path',
                'make_image_one_path',
                'make_image_two_path',
                'make_image_three_path',
                'founder_image_path',
            ]);
        });
    }
};
