<?php

namespace App\Http\Controllers\Member;

use App\Enums\ReturnReason;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Returns\ReturnEligibility;
use App\Services\Returns\ReturnService;
use App\Support\OrderPresenter;
use App\Support\ReturnAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderReturnController extends Controller
{
    public function create(
        Request $request,
        string $locale,
        Order $order,
        ReturnEligibility $eligibility,
        OrderPresenter $presenter,
    ): Response {
        abort_unless($order->user_id === $request->user()?->id, 403);
        abort_unless($eligibility->canRequestReturn($order), 403);

        $order->loadMissing('product');

        return Inertia::render('member/returns/create', [
            'order' => $presenter->detail($order),
            'eligibility' => $eligibility->forOrder($order),
            'reasons' => collect(ReturnReason::cases())->map(fn (ReturnReason $reason): array => [
                'value' => $reason->value,
                'label' => $reason->label(),
                'maison_pays_shipping' => $reason->maisonPaysReturnShipping(),
            ])->values(),
            'returnAddress' => ReturnAddress::resolve(),
            'expectedRefund' => (string) $order->amount,
        ]);
    }

    public function store(Request $request, string $locale, Order $order, ReturnService $returns): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

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
            $request->user(),
            $reason,
            $data['comment'] ?? null,
            true,
            $request->file('photos', []) ?? [],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Uw retouraanvraag is ontvangen.'),
        ]);

        return redirect()->route('member.returns.show', [
            'locale' => $locale,
            'orderReturn' => $return->id,
        ]);
    }

    public function show(Request $request, string $locale, OrderReturn $orderReturn): Response
    {
        abort_unless($orderReturn->user_id === $request->user()?->id, 403);
        $orderReturn->load(['order.product', 'items.product', 'photos', 'events']);

        return Inertia::render('member/returns/show', [
            'return' => $this->payload($orderReturn),
        ]);
    }

    public function tracking(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        abort_unless($orderReturn->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:120'],
        ]);

        $returns->addTracking(
            $orderReturn,
            $request->user(),
            $data['carrier'],
            $data['tracking_number'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Trackingnummer toegevoegd.'),
        ]);

        return back();
    }

    public function destroy(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        abort_unless($orderReturn->user_id === $request->user()?->id, 403);

        $returns->cancel($orderReturn, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Retour geannuleerd.'),
        ]);

        return redirect()->route('member.orders.show', [
            'locale' => $locale,
            'order' => $orderReturn->order_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(OrderReturn $return): array
    {
        return [
            'id' => $return->id,
            'number' => $return->number,
            'status' => $return->status->value,
            'status_label' => $return->status->label(),
            'reason' => $return->reason?->label(),
            'comment' => $return->comment,
            'maison_pays_return_shipping' => $return->maison_pays_return_shipping,
            'expected_refund_amount' => $return->expected_refund_amount,
            'refund_amount' => $return->refund_amount,
            'carrier' => $return->carrier,
            'tracking_number' => $return->tracking_number,
            'requested_at' => $return->requested_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'resolved_at' => $return->resolved_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'ship_by' => $return->ship_by?->toDateString(),
            'refund_due_by' => $return->refund_due_by?->toDateString(),
            'payment_method' => __('Stripe'),
            'is_refunded' => in_array($return->status->value, ['refunded', 'partially_refunded'], true),
            'show_ship_instructions' => in_array($return->status->value, ['requested', 'in_transit', 'received'], true),
            'can_add_tracking' => in_array($return->status->value, ['requested', 'in_transit'], true),
            'can_cancel' => $return->status->value === 'requested',
            'return_address' => ReturnAddress::resolve(),
            'order' => [
                'id' => $return->order_id,
                'reference' => $return->order?->reference(),
                'label' => $return->order?->product?->translated('name'),
            ],
            'photos' => $return->photos->map(fn ($photo): array => [
                'id' => $photo->id,
                'url' => $photo->url(),
            ])->values(),
        ];
    }
}
