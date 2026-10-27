<?php

namespace App\Services\Returns;

use App\Enums\NotificationType;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Jobs\Returns\SendReturnAdminRequestMail;
use App\Jobs\Returns\SendReturnCustomerMail;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnEvent;
use App\Models\ReturnItem;
use App\Models\ReturnPhoto;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Support\ReturnRefundCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private ReturnEligibility $eligibility,
        private ReturnRefundCalculator $calculator,
        private FoundingCircleRegistrar $registrar,
        private OrderFulfillment $fulfillment,
    ) {}

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function request(
        Order $order,
        ?User $actor,
        ?ReturnReason $reason,
        ?string $comment,
        bool $confirmed,
        array $photos = [],
    ): OrderReturn {
        if (! $confirmed) {
            throw ValidationException::withMessages([
                'confirmed' => __('Ik bevestig dat ik deze artikelen wil retourneren.'),
            ]);
        }

        if (! $this->eligibility->canRequestReturn($order)) {
            throw ValidationException::withMessages([
                'order' => __('De retourtermijn voor deze bestelling is voorbij.'),
            ]);
        }

        if ($reason === ReturnReason::Damaged && count($photos) < 1) {
            throw ValidationException::withMessages([
                'photos' => __('Upload minstens één foto van de schade.'),
            ]);
        }

        if (count($photos) > (int) config('maison.returns.max_photos')) {
            throw ValidationException::withMessages([
                'photos' => __('Je mag maximaal :count foto\'s uploaden.', [
                    'count' => (int) config('maison.returns.max_photos'),
                ]),
            ]);
        }

        $order->loadMissing('product');

        $return = DB::transaction(function () use ($order, $actor, $reason, $comment, $photos): OrderReturn {
            $requestedAt = now();
            $amount = $this->calculator->expectedAmount(
                $order,
                collect([[
                    'unit_amount' => $order->amount,
                    'quantity' => 1,
                ]]),
                wholeOrder: true,
            );

            $return = OrderReturn::query()->create([
                'number' => $this->nextNumber(),
                'order_id' => $order->id,
                'user_id' => $actor?->id,
                'status' => ReturnStatus::Requested,
                'reason' => $reason,
                'comment' => $comment,
                'maison_pays_return_shipping' => $reason?->maisonPaysReturnShipping() ?? false,
                'expected_refund_amount' => $amount,
                'requested_at' => $requestedAt,
                'ship_by' => $requestedAt->copy()->addDays((int) config('maison.returns.ship_within_days')),
                'refund_due_by' => $requestedAt->copy()->addDays((int) config('maison.returns.refund_due_days')),
            ]);

            ReturnItem::query()->create([
                'order_return_id' => $return->id,
                'product_id' => $order->product_id,
                'edition_piece_id' => $order->edition_piece_id,
                'quantity' => 1,
                'unit_amount' => $order->amount,
            ]);

            foreach (array_values($photos) as $index => $photo) {
                $path = $photo->store('returns/'.$return->id, 'public');
                ReturnPhoto::query()->create([
                    'order_return_id' => $return->id,
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }

            // Store Dutch source keys; translate on display so locale switches stay correct.
            $this->recordEvent($return, null, ReturnStatus::Requested, $actor, 'Retouraanvraag ingediend.');

            if ($return->includesFoundingRacket()) {
                $this->registrar->markReturningForOrder($order);
            }

            return $return->refresh()->load(['items.product', 'photos', 'order']);
        });

        SendReturnCustomerMail::dispatch($return, 'r1');
        SendReturnAdminRequestMail::dispatch($return);
        $this->notifyBuyer(
            $return,
            'Retour aangevraagd',
            'Uw retouraanvraag is ingediend.',
        );

        return $return;
    }

    public function addTracking(OrderReturn $return, ?User $actor, string $carrier, string $trackingNumber): OrderReturn
    {
        if (! in_array($return->status, [ReturnStatus::Requested, ReturnStatus::InTransit], true)) {
            throw ValidationException::withMessages([
                'tracking' => __('Tracking kan niet meer worden toegevoegd.'),
            ]);
        }

        $from = $return->status;

        $return->fill([
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'status' => ReturnStatus::InTransit,
        ])->save();

        $this->recordEvent($return, $from, ReturnStatus::InTransit, $actor, 'Trackingnummer toegevoegd.');

        return $return->refresh();
    }

    public function cancel(OrderReturn $return, ?User $actor, ?string $note = null): OrderReturn
    {
        if ($return->status !== ReturnStatus::Requested) {
            throw ValidationException::withMessages([
                'return' => __('Deze retour kan niet meer worden geannuleerd.'),
            ]);
        }

        return $this->transition(
            $return,
            ReturnStatus::Cancelled,
            $actor,
            $note ?? 'Retour geannuleerd.',
            restoreRegister: true,
        );
    }

    public function markReceived(OrderReturn $return, User $actor): OrderReturn
    {
        if (! in_array($return->status, [ReturnStatus::Requested, ReturnStatus::InTransit], true)) {
            throw ValidationException::withMessages([
                'return' => __('Deze retour kan niet als ontvangen worden gemarkeerd.'),
            ]);
        }

        $from = $return->status;
        $return->fill([
            'status' => ReturnStatus::Received,
            'received_at' => now(),
        ])->save();

        $this->recordEvent($return, $from, ReturnStatus::Received, $actor, 'Pakket ontvangen.');
        SendReturnCustomerMail::dispatch($return->refresh(), 'r2');

        return $return->refresh();
    }

    public function refundFull(OrderReturn $return, User $actor): OrderReturn
    {
        $this->assertRefundable($return);

        $result = $this->fulfillment->refund(
            $return->order,
            null,
            'return-full-'.$return->id,
        );

        $from = $return->status;
        $return->fill([
            'status' => ReturnStatus::Refunded,
            'refund_amount' => $return->expected_refund_amount ?? $return->order->amount,
            'stripe_refund_id' => $result['stripe_refund_id'],
            'resolved_at' => now(),
        ])->save();

        $this->recordEvent($return, $from, ReturnStatus::Refunded, $actor, 'Volledige terugbetaling uitgevoerd.');
        SendReturnCustomerMail::dispatch($return->refresh(), 'r3');
        $this->notifyBuyer($return, 'Retour terugbetaald', 'Uw retour is terugbetaald.');

        return $return->refresh();
    }

    public function refundPartial(OrderReturn $return, User $actor, string $amount, string $reason): OrderReturn
    {
        $this->assertRefundable($return);

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'refund_reason' => __('Geef een reden op voor de gedeeltelijke terugbetaling.'),
            ]);
        }

        $cents = $this->calculator->toCents($amount);
        $maxCents = $this->calculator->toCents((string) ($return->expected_refund_amount ?? $return->order->amount));

        if ($cents < 1 || $cents > $maxCents) {
            throw ValidationException::withMessages([
                'refund_amount' => __('Het terugbetalingsbedrag is ongeldig.'),
            ]);
        }

        $result = $this->fulfillment->refund(
            $return->order,
            $cents,
            'return-partial-'.$return->id.'-'.$cents,
        );

        if ($return->includesFoundingRacket()) {
            $this->registrar->releaseForOrder($return->order);
        }

        $from = $return->status;
        $return->fill([
            'status' => ReturnStatus::PartiallyRefunded,
            'refund_amount' => number_format($cents / 100, 2, '.', ''),
            'refund_reason' => $reason,
            'stripe_refund_id' => $result['stripe_refund_id'],
            'resolved_at' => now(),
        ])->save();

        $this->recordEvent($return, $from, ReturnStatus::PartiallyRefunded, $actor, $reason);
        SendReturnCustomerMail::dispatch($return->refresh(), 'r4');
        $this->notifyBuyer($return, 'Retour terugbetaald', 'Uw retour is terugbetaald.');

        return $return->refresh();
    }

    public function decline(OrderReturn $return, User $actor, string $reason): OrderReturn
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'refund_reason' => __('Geef een reden op voor de afwijzing.'),
            ]);
        }

        if ($return->status->isTerminal()) {
            throw ValidationException::withMessages([
                'return' => __('Deze retour is al afgerond.'),
            ]);
        }

        $updated = $this->transition(
            $return,
            ReturnStatus::Declined,
            $actor,
            $reason,
            restoreRegister: true,
        );

        $updated->fill([
            'refund_reason' => $reason,
            'resolved_at' => now(),
        ])->save();

        SendReturnCustomerMail::dispatch($updated->refresh(), 'r4');

        return $updated->refresh();
    }

    public function addNote(OrderReturn $return, User $actor, string $note): OrderReturn
    {
        $note = trim($note);

        if ($note === '') {
            throw ValidationException::withMessages([
                'note' => __('Voeg een notitie toe.'),
            ]);
        }

        $this->recordEvent($return, $return->status, $return->status, $actor, $note);

        return $return->refresh();
    }

    public function cancelOrderBeforeShipping(Order $order, User $actor): Order
    {
        if (! $this->eligibility->canCancel($order)) {
            throw ValidationException::withMessages([
                'order' => __('Deze bestelling kan niet meer worden geannuleerd.'),
            ]);
        }

        return $this->fulfillment->refund($order, null, 'order-cancel-'.$order->id)['order'];
    }

    private function assertRefundable(OrderReturn $return): void
    {
        if (! in_array($return->status, [ReturnStatus::InTransit, ReturnStatus::Received], true)) {
            throw ValidationException::withMessages([
                'return' => __('Terugbetaling kan pas na tracking of ontvangst.'),
            ]);
        }
    }

    private function transition(
        OrderReturn $return,
        ReturnStatus $to,
        ?User $actor,
        string $note,
        bool $restoreRegister = false,
    ): OrderReturn {
        $from = $return->status;
        $return->fill([
            'status' => $to,
            'resolved_at' => $to->isTerminal() ? now() : $return->resolved_at,
        ])->save();

        $this->recordEvent($return, $from, $to, $actor, $note);

        if ($restoreRegister && $return->includesFoundingRacket()) {
            $this->registrar->restoreAllocatedForOrder($return->order);
        }

        return $return->refresh();
    }

    private function notifyBuyer(OrderReturn $return, string $title, string $message): void
    {
        $userId = $return->user_id ?? $return->order?->user_id;

        if ($userId === null) {
            return;
        }

        $locale = $return->order?->locale ?? config('app.locale');

        notify_user(
            $userId,
            NotificationType::Order,
            $title,
            $message,
            [
                'link_url' => route('member.returns.show', [
                    'locale' => $locale,
                    'orderReturn' => $return->id,
                ]),
                'link_label' => 'Bekijk retour',
            ],
        );
    }

    private function recordEvent(
        OrderReturn $return,
        ?ReturnStatus $from,
        ReturnStatus $to,
        ?User $actor,
        ?string $note,
    ): void {
        ReturnEvent::query()->create([
            'order_return_id' => $return->id,
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function nextNumber(): string
    {
        $prefix = (string) config('maison.returns.number_prefix');
        $latest = OrderReturn::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
