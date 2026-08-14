<?php

use App\Enums\SubscriberStatus;
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
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('locale', 5)->default('nl')->index();
            $table->string('source');
            $table->string('status')->default(SubscriberStatus::Pending->value)->index();
            $table->timestamp('consent_at');
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();
            $table->string('brevo_contact_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->uuid('unsubscribe_token')->unique();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
