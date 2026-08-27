<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $home = $user instanceof User
            ? $this->redirects->intendedUrlFor($user, $request)
            : route('maison.home', [
                'locale' => $this->redirects->resolveLocale($request),
            ], absolute: false);

        $separator = str_contains($home, '?') ? '&' : '?';

        return redirect()->to($home.$separator.'verified=1');
    }
}
