<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Listeners\StripeEventListener;
use App\Models\User;
use App\Support\AdminTypePermissionBypass;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSpatiePermissions();
        $this->configureCashierWebhooks();
    }

    /**
     * Listen for Cashier webhook payloads (guest checkout fulfillment).
     */
    protected function configureCashierWebhooks(): void
    {
        Event::listen(WebhookReceived::class, StripeEventListener::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }

    /**
     * Configure permissions.
     */
    protected function configureSpatiePermissions(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
                return true;
            }

            /*
             * TEMPORARY — see App\Support\AdminTypePermissionBypass.
             * Only short-circuit Spatie permission names (e.g. users.index),
             * never model policies (e.g. update, delete) so super-admin
             * account locks in UserPolicy still apply.
             */
            if (AdminTypePermissionBypass::grantsAll($user) && str_contains($ability, '.')) {
                return true;
            }

            return null;
        });
    }
}
