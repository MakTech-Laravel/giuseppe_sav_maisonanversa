<?php

namespace App\Providers;

use App\Contracts\BrevoContacts;
use App\Contracts\StripeCatalogGateway;
use App\Enums\RoleEnum;
use App\Listeners\StripeEventListener;
use App\Models\User;
use App\Services\Brevo\HttpBrevoContacts;
use App\Services\Brevo\NullBrevoContacts;
use App\Services\Stripe\CashierStripeCatalogGateway;
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
        $this->app->bind(BrevoContacts::class, function (): BrevoContacts {
            return filled(config('services.brevo.api_key'))
                ? new HttpBrevoContacts
                : new NullBrevoContacts;
        });

        $this->app->bind(StripeCatalogGateway::class, CashierStripeCatalogGateway::class);
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

            // TEMPORARY — see AdminTypePermissionBypass.
            if (AdminTypePermissionBypass::allowsAbility($user, $ability)) {
                return true;
            }

            return null;
        });
    }
}
