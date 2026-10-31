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
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $this->redirects->remember(
            $request,
            $validated['url'],
            (bool) ($validated['force'] ?? false),
        );

        return response()->noContent();
    }
}
