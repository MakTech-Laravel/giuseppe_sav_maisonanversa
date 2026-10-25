<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureFoundingCircle;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Services\Auth\PostLoginRedirectService;
use App\Services\Locale\LocalePreferenceService;
use App\Support\Seo\MaisonSeo;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->encryptCookies(except: [
            'sidebar_state',
            'maison_locale',
            'maison_consent',
        ]);

        $middleware->preventRequestForgery(except: [
            'stripe/*',
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->alias([
            'locale' => SetLocale::class,
            // Spatie middleware aliases
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'founding-circle' => EnsureFoundingCircle::class,
            'admin' => EnsureUserIsAdmin::class,
            'customer' => EnsureUserIsCustomer::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            $redirects = app(PostLoginRedirectService::class);
            $locale = $redirects->resolveLocale($request);

            if ($request->hasSession()) {
                $request->session()->flash('open_auth_modal', 'login');
            }

            return route('maison.home', ['locale' => $locale], absolute: false);
        });

        $middleware->redirectUsersTo(function ($request) {
            $redirects = app(PostLoginRedirectService::class);
            $user = $request->user();

            if ($user === null) {
                return route('maison.home', [
                    'locale' => $redirects->resolveLocale($request),
                ], absolute: false);
            }

            return $redirects->urlFor($user, $request);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Store a relative url.intended and redirect without redirect()->guest().
         * guest() persists an absolute URL via UrlGenerator::full(), which breaks
         * post-login Inertia redirects when APP_URL host ≠ the browser host
         * (localhost vs 127.0.0.1, Docker published ports, etc.).
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }

            app(PostLoginRedirectService::class)->rememberFromRequest($request);

            $redirectTo = $e->redirectTo($request);

            if (! is_string($redirectTo) || $redirectTo === '') {
                $redirects = app(PostLoginRedirectService::class);

                $redirectTo = route('maison.home', [
                    'locale' => $redirects->resolveLocale($request),
                ], absolute: false);
            }

            return redirect()->to($redirectTo);
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404], true) || $request->expectsJson()) {
                return $response;
            }

            $locales = app(LocalePreferenceService::class);
            $segment = $request->segment(1);
            $locale = is_string($segment) && $locales->isSupported($segment)
                ? $segment
                : $locales->preferred($request);

            app()->setLocale($locale);
            URL::defaults(['locale' => $locale]);
            $request->attributes->set('maison_seo_error', true);

            /*
             * A missing route-model binding throws before HandleInertiaRequests
             * runs, so the shell props SiteNav and the footer read would be absent.
             */
            Inertia::share(app(HandleInertiaRequests::class)->share($request));
            Inertia::share('seo', MaisonSeo::document($request));

            return Inertia::render($status === 403 ? 'errors/403' : 'errors/404')
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
