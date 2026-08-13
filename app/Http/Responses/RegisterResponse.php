<?php

namespace App\Http\Responses;

use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): Response
    {
        $home = $this->redirects->urlFor($request->user(), $request);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Uw account is aangemaakt.'),
        ]);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->intended($home);
        }

        return new JsonResponse('', 201);
    }
}
