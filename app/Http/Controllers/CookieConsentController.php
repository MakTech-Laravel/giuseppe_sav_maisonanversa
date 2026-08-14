<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class CookieConsentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'necessary' => ['required', 'boolean'],
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        $payload = json_encode([
            ...$data,
            'necessary' => true,
            'at' => now()->toIso8601String(),
        ]);

        Cookie::queue('maison_consent', $payload, 60 * 24 * 365);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cookievoorkeuren opgeslagen.')]);

        return back();
    }
}
