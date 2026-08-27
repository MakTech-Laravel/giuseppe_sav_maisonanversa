<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('U bent succesvol ingelogd.'),
        ]);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->to($this->redirects->intendedUrlFor($user, $request));
        }

        return new JsonResponse('', 204);
    }
}
