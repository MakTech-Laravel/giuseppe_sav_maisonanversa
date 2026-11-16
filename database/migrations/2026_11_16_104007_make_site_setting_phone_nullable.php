<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * There is no phone contact for now: the phone number becomes optional and the
 * seeded placeholder number is removed so it never reaches the contact page or
 * the Organization structured data.
 */
return new class extends Migration
{
    public const PLACEHOLDER_PHONE = '+32400000000';

    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('phone')->nullable()->default(null)->change();
        });

        DB::table('site_settings')
            ->where('phone', self::PLACEHOLDER_PHONE)
            ->update(['phone' => null]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->whereNull('phone')
            ->update(['phone' => self::PLACEHOLDER_PHONE]);

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('phone')->nullable(false)->default(self::PLACEHOLDER_PHONE)->change();
        });
    }
};
