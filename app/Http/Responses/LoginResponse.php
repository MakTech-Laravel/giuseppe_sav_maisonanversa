<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $home = $this->redirects->urlFor($user, $request);

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($home);
    }
}
