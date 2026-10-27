<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Returns\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $urgencyDays = (int) config('maison.returns.urgency_days');

        $returns = OrderReturn::query()
            ->with(['order.product', 'user', 'items.product'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($order) => $order->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->latest('requested_at')
            ->paginate((int) $request->integer('per_page', 20))
            ->withQueryString()
            ->through(fn (OrderReturn $return): array => $this->listRow($return, $urgencyDays));

        return Inertia::render('admin/returns/index', [
            'returns' => $returns,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'statuses' => collect(ReturnStatus::cases())->map(fn (ReturnStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function show(string $locale, OrderReturn $orderReturn): Response
    {
        $orderReturn->load(['order.product', 'user', 'items.product', 'photos', 'events.user']);

        return Inertia::render('admin/returns/show', [
            'return' => $this->detail($orderReturn),
        ]);
    }

    public function markReceived(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $returns->markReceived($orderReturn, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gemarkeerd als ontvangen.')]);

        return back();
    }

    public function refundFull(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $returns->refundFull($orderReturn, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Volledige terugbetaling uitgevoerd.')]);

        return back();
    }

    public function refundPartial(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $orderReturn->loadMissing('order');
        $maxAmount = (string) ($orderReturn->expected_refund_amount ?? $orderReturn->order?->amount ?? '0');

        $data = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$maxAmount],
            'refund_reason' => ['required', 'string', 'max:2000'],
        ]);

        $returns->refundPartial(
            $orderReturn,
            $request->user(),
            (string) $data['refund_amount'],
            $data['refund_reason'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gedeeltelijke terugbetaling uitgevoerd.')]);

        return back();
    }

    public function decline(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'refund_reason' => ['required', 'string', 'max:2000'],
        ]);

        $returns->decline($orderReturn, $request->user(), $data['refund_reason']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retour afgewezen.')]);

        return back();
    }

    public function note(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $returns->addNote($orderReturn, $request->user(), $data['note']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notitie toegevoegd.')]);

        return back();
    }

    public function tracking(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:120'],
        ]);

        $returns->addTracking($orderReturn, $request->user(), $data['carrier'], $data['tracking_number']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tracking bijgewerkt.')]);

        return back();
    }

    public function extendDeadline(Request $request, string $locale, OrderReturn $orderReturn): RedirectResponse
    {
        $data = $request->validate([
            'return_deadline_at' => ['required', 'date', 'after:today'],
        ]);

        /** @var Order $order */
        $order = $orderReturn->order()->firstOrFail();
        $order->fill([
            'return_deadline_at' => $data['return_deadline_at'],
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retourtermijn verlengd.')]);

        return back();
    }

    public function cancel(Request $request, string $locale, OrderReturn $orderReturn, ReturnService $returns): RedirectResponse
    {
        $returns->cancel($orderReturn, $request->user(), 'Geannuleerd door beheerder.');
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retour geannuleerd.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(OrderReturn $return, int $urgencyDays): array
    {
        $dueSoon = $return->refund_due_by !== null
            && $return->refund_due_by->lessThanOrEqualTo(now()->addDays($urgencyDays));

        return [
            'id' => $return->id,
            'number' => $return->number,
            'status' => $return->status->value,
            'status_label' => $return->status->label(),
            'customer' => $return->user?->name ?? $return->order?->name,
            'order_reference' => $return->order?->reference(),
            'items' => $return->items
                ->map(fn ($item) => $item->product?->name)
                ->filter()
                ->values()
                ->whenEmpty(fn () => collect([$return->order?->product?->name])->filter()->values())
                ->values()
                ->all(),
            'requested_at' => $return->requested_at?->format('Y-m-d H:i'),
            'refund_due_by' => $return->refund_due_by?->toDateString(),
            'due_soon' => $dueSoon && $return->status->isOpen(),
            'maison_pays_return_shipping' => $return->maison_pays_return_shipping,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(OrderReturn $return): array
    {
        $urgencyDays = (int) config('maison.returns.urgency_days');

        return [
            ...$this->listRow($return, $urgencyDays),
            'reason' => $return->reason?->label(),
            'comment' => $return->comment,
            'expected_refund_amount' => $return->expected_refund_amount,
            'refund_amount' => $return->refund_amount,
            'refund_reason' => $return->refund_reason,
            'stripe_refund_id' => $return->stripe_refund_id,
            'carrier' => $return->carrier,
            'tracking_number' => $return->tracking_number,
            'ship_by' => $return->ship_by?->toDateString(),
            'order_id' => $return->order_id,
            'order_deadline' => $return->order?->return_deadline_at?->toDateString(),
            'photos' => $return->photos->map(fn ($photo): array => [
                'id' => $photo->id,
                'url' => $photo->url(),
            ])->values(),
            'events' => $return->events->map(fn ($event): array => [
                'id' => $event->id,
                'from' => $event->from_status?->label(),
                'to' => $event->to_status->label(),
                'note' => $event->note,
                'user' => $event->user?->name,
                'at' => $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            ])->values(),
            'payment_method' => __('Stripe'),
            'resolved_at' => $return->resolved_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'can_receive' => in_array($return->status, [ReturnStatus::Requested, ReturnStatus::InTransit], true),
            'can_refund' => in_array($return->status, [ReturnStatus::InTransit, ReturnStatus::Received], true),
            'can_decline' => $return->status->isOpen(),
            'can_cancel' => $return->status === ReturnStatus::Requested,
        ];
    }
}
