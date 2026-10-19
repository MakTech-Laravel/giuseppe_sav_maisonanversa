<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_service_states', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 32)->unique();
            $table->string('status', 32)->default('unknown');
            $table->string('container_status', 64)->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('restart_count')->default(0);
            $table->unsignedInteger('auto_restart_attempts_window')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_restart_at')->nullable();
            $table->timestamp('window_started_at')->nullable();
            $table->boolean('auto_recovery_enabled')->default(false);
            $table->boolean('restart_in_progress')->default(false);
            $table->unsignedInteger('last_latency_ms')->nullable();
            $table->string('last_error_category', 64)->nullable();
            $table->string('last_error_message', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('monitoring_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 32)->index();
            $table->string('status', 32);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('websocket_ok')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_category', 64)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            $table->index(['service_id', 'checked_at']);
        });

        Schema::create('monitoring_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 32)->index();
            $table->string('status', 32)->default('open');
            $table->string('summary', 255);
            $table->string('error_category', 64)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'status']);
            $table->index(['service_id', 'started_at']);
        });

        Schema::create('monitoring_restarts', function (Blueprint $table) {
            $table->id();
            $table->string('service_id', 32)->index();
            $table->string('trigger', 32);
            $table->string('outcome', 32);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('coolify_deployment_uuid', 64)->nullable();
            $table->string('reason', 255)->nullable();
            $table->boolean('health_verified')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'requested_at']);
        });

        Schema::create('monitoring_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_settings');
        Schema::dropIfExists('monitoring_restarts');
        Schema::dropIfExists('monitoring_incidents');
        Schema::dropIfExists('monitoring_health_checks');
        Schema::dropIfExists('monitoring_service_states');
    }
};
