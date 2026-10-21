<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Returns\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderCancelController extends Controller
{
    public function __invoke(Request $request, string $locale, Order $order, ReturnService $returns): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        $returns->cancelOrderBeforeShipping($order, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bestelling geannuleerd. De terugbetaling is gestart.'),
        ]);

        return redirect()->route('member.orders.show', [
            'locale' => $locale,
            'order' => $order->id,
        ]);
    }
}
