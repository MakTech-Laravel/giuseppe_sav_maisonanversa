<?php

use App\Http\Middleware\EnsureFoundingCircle;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Services\Auth\PostLoginRedirectService;
use App\Services\Locale\LocalePreferenceService;
use App\Support\Seo\MaisonSeo;
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
        ]);

        $middleware->alias([
            'locale' => SetLocale::class,
            // Spatie middleware aliases
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'founding-circle' => EnsureFoundingCircle::class,
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
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 404 || $request->expectsJson()) {
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

            Inertia::share('seo', MaisonSeo::document($request));

            return Inertia::render('errors/404')
                ->toResponse($request)
                ->setStatusCode(404);
        });
    })->create();
