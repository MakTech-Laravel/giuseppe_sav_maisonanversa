<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Illuminate\Http\Request;

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
        //
    })->create();
