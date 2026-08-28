<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use App\Services\Newsletter\HeritageLetterSubscription;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        public PostLoginRedirectService $redirects,
        public HeritageLetterSubscription $heritageLetter,
    ) {}

    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->heritageLetter->claimForUser($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('U bent succesvol ingelogd.'),
        ]);

        /*
         * Inertia visits send X-Inertia and expect a redirect to the next page.
         * Returning bare JSON leaves the session authenticated but the SPA stuck
         * on the login modal (no navigation, no toast).
         */
        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->to($this->redirects->intendedUrlFor($user, $request));
        }

        return new JsonResponse(['two_factor' => false], 200);
    }
}
