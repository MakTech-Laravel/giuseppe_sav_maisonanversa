<?php

namespace App\Providers;

use App\Contracts\BrevoContacts;
use App\Contracts\StripeCatalogGateway;
use App\Enums\RoleEnum;
use App\Listeners\StripeEventListener;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\JournalArticle;
use App\Models\User;
use App\Observers\JournalArticleObserver;
use App\Services\Brevo\HttpBrevoContacts;
use App\Services\Brevo\NullBrevoContacts;
use App\Services\Stripe\CashierStripeCatalogGateway;
use App\Support\AdminTypePermissionBypass;
use App\Support\MailLocale;
use App\Support\Seo\MaisonSeo;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        $this->configureSeoViewData();
        $this->configureBrandedAuthMail();

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
     * Brand Fortify auth emails (reset password + verify email) with the Maison shell.
     */
    protected function configureBrandedAuthMail(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return url(route('password.reset', [
                'locale' => MailLocale::resolve($notifiable->locale ?? null),
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $locale = MailLocale::resolve($notifiable->locale ?? null);

            return (new ResetPasswordMail(
                resetUrl: url(route('password.reset', [
                    'locale' => $locale,
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)),
                expireMinutes: (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                locale: $locale,
            ))->to($notifiable->getEmailForPasswordReset());
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new VerifyEmailMail(
                verificationUrl: $url,
                locale: $notifiable->locale ?? null,
            ))->to($notifiable->getEmailForVerification());
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
