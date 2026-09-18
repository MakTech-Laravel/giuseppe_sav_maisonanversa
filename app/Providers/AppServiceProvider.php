<?php

namespace App\Providers;

use App\Contracts\BrevoContacts;
use App\Contracts\StripeCatalogGateway;
use App\Enums\RoleEnum;
use App\Listeners\StripeEventListener;
use App\Mail\VerifyEmailMail;
use App\Models\JournalArticle;
use App\Models\User;
use App\Observers\JournalArticleObserver;
use App\Services\Brevo\HttpBrevoContacts;
use App\Services\Brevo\NullBrevoContacts;
use App\Services\Stripe\CashierStripeCatalogGateway;
use App\Support\AdminTypePermissionBypass;
use App\Support\Seo\MaisonSeo;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        $this->configureSeoViewData();
        $this->configureBrandedAuthMail();
        $this->configurePasswordResetRateLimiting();

        JournalArticle::observe(JournalArticleObserver::class);
    }

    /**
     * Blade fallback meta for first paint when the SSR process is down.
     */
    protected function configureSeoViewData(): void
    {
        View::composer('app', function ($view): void {
            $view->with('seo', MaisonSeo::document(request()));
        });
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
     * Brand Fortify auth emails that still use Laravel notifications.
     */
    protected function configureBrandedAuthMail(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new VerifyEmailMail(
                verificationUrl: $url,
                locale: $notifiable->locale ?? null,
            ))->to($notifiable->getEmailForVerification());
        });
    }

    protected function configurePasswordResetRateLimiting(): void
    {
        RateLimiter::for('password-reset-send', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(1)->by($email.'|'.$request->ip()),
                Limit::perHour(5)->by($email),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-reset-attempt', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(10)->by($email.'|'.$request->ip());
        });
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
