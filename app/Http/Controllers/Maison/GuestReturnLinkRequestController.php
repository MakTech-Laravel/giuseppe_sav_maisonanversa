<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Mail\Returns\GuestReturnLinkMail;
use App\Services\Returns\GuestReturnLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class GuestReturnLinkRequestController extends Controller
{
    /**
     * Public self-serve: order reference + email → one-time signed guest
     * return link by mail. Always flashes the same success copy so callers
     * cannot probe whether an order exists.
     */
    public function __invoke(Request $request, string $locale, GuestReturnLink $guestLinks): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = $guestLinks->findEligibleGuestOrder(
            $validated['reference'],
            $validated['email'],
        );

        if ($order !== null) {
            $url = $guestLinks->createUrl($order, $locale);

            if ($url !== null) {
                Mail::to($order->email)->queue(new GuestReturnLinkMail($order, $url));
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('If we find a guest order matching these details, we will send you a return link shortly.'),
        ]);

        return back();
    }
}
