<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PostLoginRedirectService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RememberIntendedUrlController extends Controller
{
    public function __construct(public PostLoginRedirectService $redirects) {}

    /**
     * Remember where a guest should return after the Maison auth modal login.
     *
     * Public pages open the modal without hitting auth middleware, so they never
     * get url.intended from redirect()->guest(). The shell posts the current
     * path here before showing the modal.
     *
     * `resume` is sent when the guest was in the middle of a reservation: the
     * localized home is then remembered too, so login returns there and the
     * reservation carries on.
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'resume' => ['sometimes', 'boolean'],
        ]);

        $this->redirects->remember(
            $request,
            $validated['url'],
            allowHome: (bool) ($validated['resume'] ?? false),
        );

        return response()->noContent();
    }
}
