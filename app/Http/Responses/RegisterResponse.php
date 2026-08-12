<?php

namespace App\Http\Responses;

use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): Response
    {
        $home = $this->redirects->urlFor($request->user(), $request);

        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended($home);
    }
}
