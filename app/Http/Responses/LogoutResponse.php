<?php

namespace App\Http\Responses;

use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    public function toResponse($request): RedirectResponse
    {
        $locale = $this->redirects->resolveLocale($request);

        return redirect()->route('maison.home', ['locale' => $locale]);
    }
}
