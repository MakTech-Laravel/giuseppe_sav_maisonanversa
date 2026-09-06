<?php

namespace App\Services\FoundingCircle;

use App\Enums\EditionPieceStatus;
use App\Enums\FoundingCircleClaimSource;
use App\Enums\FoundingCircleClaimStatus;
use App\Enums\GuardEnum;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class FoundingCircleClaimService
{
    public function __construct(
        private FoundingCircleRegistrar $registrar,
        private EditionInventory $inventory,
    ) {}

    /**
     * Create a pending claim after a founding product is paid (idempotent per order).
     */
    public function createFromOrder(Order $order): ?FoundingCircleClaim
    {
        $order->loadMissing('product', 'user', 'editionPiece');

        if ($order->user === null || ! $order->product?->grants_founding_circle) {
            return null;
        }

        if ($order->edition_number === null || $order->edition_piece_id === null) {
            return null;
        }

        $existing = FoundingCircleClaim::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return FoundingCircleClaim::query()->create([
            'user_id' => $order->user_id,
            'product_id' => $order->product_id,
            'edition_piece_id' => $order->edition_piece_id,
            'order_id' => $order->id,
            'edition_number' => (int) $order->edition_number,
            'status' => FoundingCircleClaimStatus::Pending,
            'source' => FoundingCircleClaimSource::Order,
        ]);
    }

    /**
     * Member submits a racket serial for verification.
     *
     * @throws ValidationException
     */
    public function createManual(User $user, string $serialInput): FoundingCircleClaim
    {
        return DB::transaction(function () use ($user, $serialInput): FoundingCircleClaim {
            $product = Product::founding();

            if ($product === null || ! $product->grants_founding_circle) {
                throw ValidationException::withMessages([
                    'serial' => __('Er is geen Founding Edition-product beschikbaar.'),
                ]);
            }

            $editionNumber = $this->parseSerial($serialInput, $product);

            if ($editionNumber < 1 || $editionNumber > (int) $product->edition_total) {
                throw ValidationException::withMessages([
                    'serial' => __('Ongeldig serienummer. Gebruik een nummer tussen 001 en :total.', [
                        'total' => str_pad((string) $product->edition_total, 3, '0', STR_PAD_LEFT),
                    ]),
                ]);
            }

            if (FoundingCircleClaim::query()
                ->where('user_id', $user->id)
                ->pending()
                ->exists()) {
                throw ValidationException::withMessages([
                    'serial' => __('U heeft al een registratie in afwachting. Wacht op goedkeuring of neem contact op met het team.'),
                ]);
            }

            $activeClaim = FoundingCircleClaim::query()
                ->where('product_id', $product->id)
                ->where('edition_number', $editionNumber)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($activeClaim !== null) {
                throw ValidationException::withMessages([
                    'serial' => __('Dit serienummer is al geregistreerd.'),
                ]);
            }

            $piece = EditionPiece::query()
                ->where('product_id', $product->id)
                ->where(function ($query) use ($product, $editionNumber): void {
                    $label = $product->formatEditionLabel($editionNumber);
                    $digits = $product->formatEditionDigits($editionNumber);
                    $query->where('edition_number', $label)
                        ->orWhere('edition_number', $digits)
                        ->orWhere('edition_number', (string) $editionNumber);
                })
                ->lockForUpdate()
                ->first();

            if ($piece === null) {
                throw ValidationException::withMessages([
                    'serial' => __('Dit serienummer bestaat niet.'),
                ]);
            }

            if ($piece->status === EditionPieceStatus::Archive) {
                throw ValidationException::withMessages([
                    'serial' => __('Dit serienummer behoort tot het archief en kan niet worden geregistreerd.'),
                ]);
            }

            if (in_array($piece->status, [EditionPieceStatus::Allocated, EditionPieceStatus::Reserved], true)
                && $piece->order_id !== null) {
                $ownerId = Order::query()->whereKey($piece->order_id)->value('user_id');

                if ($ownerId !== null && (int) $ownerId !== (int) $user->id) {
                    throw ValidationException::withMessages([
                        'serial' => __('Dit serienummer is al gekoppeld aan een andere eigenaar.'),
                    ]);
                }
            }

            return FoundingCircleClaim::query()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'edition_piece_id' => $piece->id,
                'order_id' => $piece->order_id,
                'edition_number' => $editionNumber,
                'status' => FoundingCircleClaimStatus::Pending,
                'source' => FoundingCircleClaimSource::Manual,
            ]);
        });
    }

    /**
     * Approve a pending claim: grant Founding Circle + naamregister.
     */
    public function approve(FoundingCircleClaim $claim, User $reviewer): FoundingCircleClaim
    {
        return DB::transaction(function () use ($claim, $reviewer): FoundingCircleClaim {
            /** @var FoundingCircleClaim $locked */
            $locked = FoundingCircleClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'claim' => __('Deze registratie is al behandeld.'),
                ]);
            }

            $locked->loadMissing('user', 'order', 'editionPiece', 'product');

            if ($locked->editionPiece !== null
                && $locked->editionPiece->status === EditionPieceStatus::Available) {
                $locked->editionPiece->fill([
                    'status' => EditionPieceStatus::Allocated,
                    'allocated_at' => now(),
                    'notes' => trim(($locked->editionPiece->notes ?? '')."\nAllocated via Founding Circle claim #{$locked->id}"),
                ])->save();

                $this->inventory->bust($locked->product);
            }

            Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
            $locked->user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

            $this->registrar->register(
                $locked->user,
                $locked->order,
                $locked->edition_number,
                $locked->product_id,
            );

            $locked->fill([
                'status' => FoundingCircleClaimStatus::Approved,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * Reject a pending claim; the serial becomes reclaimable.
     */
    public function reject(FoundingCircleClaim $claim, User $reviewer, ?string $note = null): FoundingCircleClaim
    {
        return DB::transaction(function () use ($claim, $reviewer, $note): FoundingCircleClaim {
            /** @var FoundingCircleClaim $locked */
            $locked = FoundingCircleClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'claim' => __('Deze registratie is al behandeld.'),
                ]);
            }

            $locked->fill([
                'status' => FoundingCircleClaimStatus::Rejected,
                'admin_note' => $note,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * Cancel pending claims when an order is refunded.
     */
    public function cancelPendingForOrder(Order $order): void
    {
        FoundingCircleClaim::query()
            ->where('order_id', $order->id)
            ->pending()
            ->update([
                'status' => FoundingCircleClaimStatus::Rejected,
                'admin_note' => __('Automatisch afgewezen na terugbetaling.'),
                'reviewed_at' => now(),
            ]);
    }

    /**
     * Parse "027", "27", "027/100", "27/100" into an integer sequence.
     */
    public function parseSerial(string $input, Product $product): int
    {
        $normalized = strtoupper(trim($input));
        $normalized = str_replace([' ', 'RACKET', 'NO.', 'NO', '#'], '', $normalized);

        if (preg_match('/^(\d+)\s*\/\s*\d+$/', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/^(\d+)$/', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        return $product->parseEditionSequence($normalized);
    }
}
