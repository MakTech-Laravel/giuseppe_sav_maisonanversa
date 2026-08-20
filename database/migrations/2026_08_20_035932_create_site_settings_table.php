<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->default('+32400000000');
            $table->string('whatsapp')->default('32400000000');
            $table->string('email_hello')->default('hello@maisonanversa.com');
            $table->string('email_press')->default('press@maisonanversa.com');
            $table->string('instagram_url')->default('https://www.instagram.com/');
            $table->decimal('boutique_lat', 10, 7)->default(51.220);
            $table->decimal('boutique_lng', 10, 7)->default(4.404);
            $table->string('announcement_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
