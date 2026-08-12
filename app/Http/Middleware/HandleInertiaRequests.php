<?php

namespace App\Http\Middleware;

use App\Services\Auth\PostLoginRedirectService;
use App\Support\Imagery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $user?->load('roles', 'permissions');

        $redirects = app(PostLoginRedirectService::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()
                        ->pluck('name'),
                    'is_super_admin' => $user->hasRole('super-admin'),
                    'type' => $user->type->value,
                    'is_admin' => $user->isAdmin(),
                    'dashboard_url' => $redirects->dashboardUrlFor($user, $request),
                    'avatar_url' => $user->avatar
                        ? Storage::disk('public')->url($user->avatar)
                        : null,
                ]) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            /*
             * Resolved lazily: Inertia collects shared data before route
             * middleware runs, so reading the locale eagerly would capture the
             * application default rather than the one SetLocale applies.
             */
            'locale' => fn () => app()->getLocale(),
            'availableLocales' => config('maison.locales'),
            'appUrl' => config('app.url'),
            'seoImage' => config('maison.seo.image'),

            /*
             * Which of the site's photographs exist yet. Everything else falls
             * back to a placeholder rather than requesting a missing file.
             */
            'availableImages' => fn () => Imagery::existingPaths(),
            'flash' => [
                'open_auth_modal' => fn () => $request->session()->get('open_auth_modal'),
            ],
        ];
    }
}
