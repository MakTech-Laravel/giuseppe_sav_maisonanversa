<?php

namespace App\Http\Controllers\Maison;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Returns\GuestReturnLink;
use App\Services\Returns\ReturnEligibility;
use App\Services\Returns\ReturnService;
use App\Support\OrderPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GuestOrderReturnController extends Controller
{
    public function create(
        Request $request,
        string $locale,
        Order $order,
        GuestReturnLink $guestLinks,
        ReturnEligibility $eligibility,
        OrderPresenter $presenter,
    ): Response {
        $this->assertGuestOrderAccess($request, $order, $guestLinks);
        abort_unless($eligibility->canRequestReturn($order), 403);

        $request->session()->put($guestLinks->sessionKey($order), [
            'email' => $order->email,
            'expires_at' => now()->addHours((int) config('maison.returns.guest_link_ttl_hours'))->getTimestamp(),
        ]);

        $order->loadMissing('product');

        return Inertia::render('maison/guest-returns/create', [
            'order' => $presenter->detail($order),
            'reasons' => collect(ReturnReason::cases())->map(fn (ReturnReason $reason): array => [
                'value' => $reason->value,
                'label' => $reason->label(),
                'maison_pays_shipping' => $reason->maisonPaysReturnShipping(),
            ])->values(),
            'returnAddress' => (string) config('maison.returns.return_address'),
            'expectedRefund' => (string) $order->amount,
            'storeUrl' => route('maison.guest-returns.store', [
                'locale' => $locale,
                'order' => $order->id,
            ]),
        ]);
    }

    public function store(
        Request $request,
        string $locale,
        Order $order,
        GuestReturnLink $guestLinks,
        ReturnService $returns,
    ): RedirectResponse {
        $this->assertGuestSession($request, $order, $guestLinks);

        $data = $request->validate([
            'reason' => ['nullable', Rule::enum(ReturnReason::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
            'confirmed' => ['accepted'],
            'photos' => ['nullable', 'array', 'max:'.(int) config('maison.returns.max_photos')],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $reason = $data['reason'] ?? null;

        if (is_string($reason)) {
            $reason = ReturnReason::from($reason);
        }

        $return = $returns->request(
            $order,
            null,
            $reason,
            $data['comment'] ?? null,
            true,
            $request->file('photos', []) ?? [],
        );

        $request->session()->forget($guestLinks->sessionKey($order));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Je retouraanvraag is ontvangen.'),
        ]);

        return redirect()->to($guestLinks->showUrl($return, $locale));
    }

    public function show(
        Request $request,
        string $locale,
        OrderReturn $orderReturn,
        GuestReturnLink $guestLinks,
    ): Response {
        $orderReturn->loadMissing(['order.product', 'items.product', 'photos']);
        $this->assertGuestReturnAccess($request, $orderReturn, $guestLinks);

        return Inertia::render('maison/guest-returns/show', [
            'return' => [
                'id' => $orderReturn->id,
                'number' => $orderReturn->number,
                'status' => $orderReturn->status->value,
                'status_label' => $orderReturn->status->label(),
                'ship_by' => $orderReturn->ship_by?->toDateString(),
                'return_address' => (string) config('maison.returns.return_address'),
                'can_add_tracking' => in_array(
                    $orderReturn->status,
                    [ReturnStatus::Requested, ReturnStatus::InTransit],
                    true,
                ),
                'carrier' => $orderReturn->carrier,
                'tracking_number' => $orderReturn->tracking_number,
                'order' => [
                    'id' => $orderReturn->order_id,
                    'reference' => $orderReturn->order?->reference(),
                ],
                'trackingUrl' => $guestLinks->trackingUrl($orderReturn, $locale),
            ],
        ]);
    }

    public function tracking(
        Request $request,
        string $locale,
        OrderReturn $orderReturn,
        GuestReturnLink $guestLinks,
        ReturnService $returns,
    ): RedirectResponse {
        $orderReturn->loadMissing('order');
        $this->assertGuestReturnAccess($request, $orderReturn, $guestLinks);

        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:120'],
        ]);

        $returns->addTracking(
            $orderReturn,
            null,
            $data['carrier'],
            $data['tracking_number'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trackingnummer toegevoegd.'),
        ]);

        return redirect()->to($guestLinks->showUrl($orderReturn->fresh(), $locale));
    }

    private function assertGuestOrderAccess(Request $request, Order $order, GuestReturnLink $guestLinks): void
    {
        abort_unless($order->user_id === null, 403);
        abort_unless($guestLinks->emailMatches($order, $request->query('email')), 403);
    }

    private function assertGuestSession(Request $request, Order $order, GuestReturnLink $guestLinks): void
    {
        abort_unless($order->user_id === null, 403);

        /** @var array{email?: string, expires_at?: int}|null $session */
        $session = $request->session()->get($guestLinks->sessionKey($order));

        abort_unless(is_array($session), 403);
        abort_unless(
            isset($session['expires_at']) && (int) $session['expires_at'] >= now()->getTimestamp(),
            403,
        );
        abort_unless($guestLinks->emailMatches($order, $session['email'] ?? null), 403);
    }

    private function assertGuestReturnAccess(
        Request $request,
        OrderReturn $orderReturn,
        GuestReturnLink $guestLinks,
    ): void {
        $order = $orderReturn->order;
        abort_unless($order !== null, 404);
        abort_unless($order->user_id === null && $orderReturn->user_id === null, 403);
        abort_unless($guestLinks->emailMatches($order, $request->query('email')), 403);
    }
}
