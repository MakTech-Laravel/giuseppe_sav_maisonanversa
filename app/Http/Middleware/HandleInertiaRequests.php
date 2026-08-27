<?php

namespace App\Http\Middleware;

use App\Models\CommerceSetting;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Auth\PostLoginRedirectService;
use App\Support\AdminTypePermissionBypass;
use App\Support\Imagery;
use App\Support\Seo\MaisonSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
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
            /*
             * Shell-critical props use always() so partial reloads and instant
             * visits cannot leave the admin/public chrome without auth, locale,
             * or imagery metadata.
             */
            'auth' => Inertia::always([
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames(),
                    // TEMPORARY — AdminTypePermissionBypass shares every permission
                    // name so the sidebar matches full staff access.
                    'permissions' => AdminTypePermissionBypass::grants($user)
                        ? AdminTypePermissionBypass::allPermissionNames()
                        : $user->getAllPermissions()->pluck('name'),
                    'is_super_admin' => $user->hasRole('super-admin'),
                    'type' => $user->type->value,
                    'is_admin' => $user->isAdmin(),
                    'is_founding_circle' => $user->isFoundingCircle(),
                    'dashboard_url' => $redirects->dashboardUrlFor($user, $request),
                    'avatar_url' => $user->avatar
                        ? Storage::disk('public')->url($user->avatar)
                        : null,
                ]) : null,
            ]),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            /*
             * Resolved lazily: Inertia collects shared data before route
             * middleware runs, so reading the locale eagerly would capture the
             * application default rather than the one SetLocale applies.
             */
            'locale' => Inertia::always(fn () => app()->getLocale()),
            'availableLocales' => Inertia::always(config('maison.locales')),
            'appUrl' => Inertia::always(config('app.url')),
            'seoImage' => config('maison.seo.image'),
            'seo' => Inertia::always(fn (): array => MaisonSeo::document($request)),
            'cookieConsent' => fn () => $request->cookie('maison_consent'),
            'checkout' => fn (): array => Product::checkoutShare(),
            'commerce' => fn (): array => CommerceSetting::current()->toShare(),
            'site' => Inertia::always(fn (): array => SiteSetting::current()->toShare()),

            /*
             * Which of the site's photographs exist yet. Everything else falls
             * back to a placeholder rather than requesting a missing file.
             */
            'availableImages' => Inertia::always(Imagery::existingPaths()),
            'flash' => [
                'open_auth_modal' => fn () => $request->session()->get('open_auth_modal'),
            ],
        ];
    }
}
