<?php

use App\Enums\RegisterVisibility;
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
        Schema::table('founding_circle_register', function (Blueprint $table) {
            $table->dropIndex('founding_circle_register_edition_number_index');
            $table->string('register_visibility')->default(RegisterVisibility::Private->value)->after('joined_at');
            $table->timestamp('register_consent_at')->nullable()->after('register_visibility');
            $table->boolean('register_hidden_by_admin')->default(false)->after('register_consent_at');
            $table->unique('edition_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('founding_circle_register', function (Blueprint $table) {
            $table->dropUnique(['edition_number']);
            $table->dropColumn([
                'register_visibility',
                'register_consent_at',
                'register_hidden_by_admin',
            ]);
            $table->index('edition_number');
        });
    }
};
