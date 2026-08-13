<?php

namespace App\Actions\Fortify;

use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirect;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfTwoFactorAuthenticatable extends FortifyRedirect
{
    /**
     * Redirect back to the public site with the auth modal open on the 2FA step.
     *
     * @param  Request  $request
     * @param  mixed  $user
     */
    protected function twoFactorChallengeResponse($request, $user): Response
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['two_factor' => true]);
        }

        $redirects = app(PostLoginRedirectService::class);
        $locale = $redirects->resolveLocale($request);

        return redirect()
            ->route('maison.home', ['locale' => $locale])
            ->with('open_auth_modal', 'two-factor');
    }
}
