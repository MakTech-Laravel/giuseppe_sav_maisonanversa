<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PresenceController extends Controller
{
    /**
     * Lightweight heartbeat so chat digests can treat the member as active.
     */
    public function __invoke(Request $request): Response
    {
        $request->user()?->forceFill(['last_seen_at' => now()])->save();

        return response()->noContent();
    }
}
