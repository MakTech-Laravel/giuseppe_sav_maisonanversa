<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Locale\LocalePreferenceService;
use Illuminate\Http\Request;

class PostLoginRedirectService
{
    public function __construct(public LocalePreferenceService $locales) {}

    /**
     * Resolve the post-authentication redirect URL for the given user.
     */
    public function urlFor(User $user, ?Request $request = null): string
    {
        $locale = $this->resolveLocale($request);

        if ($user->isAdmin()) {
            return route('admin.dashboard', ['locale' => $locale]);
        }

        return route('member.dashboard', ['locale' => $locale]);
    }

    /**
     * Resolve the dashboard URL without an intended redirect check.
     */
    public function dashboardUrlFor(User $user, ?Request $request = null): string
    {
        return $this->urlFor($user, $request);
    }

    /**
     * Resolve locale from route, cookie, session, Accept-Language, or default.
     */
    public function resolveLocale(?Request $request = null): string
    {
        return $this->locales->preferred($request ?? request());
    }
}
