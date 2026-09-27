<?php

namespace App\Services\FoundingCircle;

use App\Enums\EditionPieceStatus;
use App\Enums\GuardEnum;
use App\Enums\RegisterVisibility;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Inscribes Heritage No.001 buyers into the live Founding Circle register.
 *
 * Payment confirms the place the buyer picked. The public name stays private
 * until the member opts in. A refund frees the number. Admin assign is an
 * override that still requires a free, non-archived number.
 */
class FoundingCircleRegistrar
{
    public function __construct(
        private FoundingCircleRegisterSnapshot $snapshot,
        private EditionInventory $inventory,
    ) {}

    public function inscribeFromOrder(Order $order): ?FoundingCircleRegisterEntry
    {
        $order->loadMissing('product', 'user');
        $product = $order->product;

        if ($order->user === null || ! $this->isFoundingProduct($product) || $order->edition_number === null) {
            return null;
        }

        return DB::transaction(function () use ($order, $product): FoundingCircleRegisterEntry {
            $number = (int) $order->edition_number;
            $this->assertAssignable($product, $number, $order->user_id);

            $this->grantRole($order->user);

            $entry = $this->writePlace(
                $order->user,
                $product,
                $number,
                $order,
            );

            $this->snapshot->bust();

            return $entry;
        });
    }

    public function releaseForOrder(Order $order): void
    {
        if ($order->user_id === null) {
            return;
        }

        $entry = FoundingCircleRegisterEntry::query()
            ->where('user_id', $order->user_id)
            ->where(function ($query) use ($order): void {
                $query->where('order_id', $order->id);

                if ($order->edition_number !== null) {
                    $query->orWhere('edition_number', (int) $order->edition_number);
                }
            })
            ->first();

        if ($entry === null) {
            return;
        }

        $this->releaseEntry($entry);
    }

    public function assign(User $user, int $editionNumber): FoundingCircleRegisterEntry
    {
        $product = Product::founding();

        if ($product === null) {
            throw ValidationException::withMessages([
                'edition_number' => __('Er is geen Founding Edition-product beschikbaar.'),
            ]);
        }

        return DB::transaction(function () use ($user, $product, $editionNumber): FoundingCircleRegisterEntry {
            $piece = $this->assertAssignable($product, $editionNumber, $user->id);
            $this->holdPiece($piece);
            $this->grantRole($user);

            $entry = $this->writePlace($user, $product, $editionNumber, null);
            $this->inventory->bust($product);
            $this->snapshot->bust();

            return $entry;
        });
    }

    public function releaseMember(User $user): void
    {
        $entry = FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->first();

        if ($entry === null) {
            $user->removeRole(RoleEnum::FOUNDING_CIRCLE->value);
            $this->snapshot->bust();

            return;
        }

        $this->releaseEntry($entry);
    }

    public function changeNumber(FoundingCircleRegisterEntry $entry, int $editionNumber): FoundingCircleRegisterEntry
    {
        $entry->loadMissing('user', 'product', 'order');
        $product = $entry->product ?? Product::founding();

        if ($product === null || $entry->user === null) {
            throw ValidationException::withMessages([
                'edition_number' => __('Er is geen Founding Edition-product beschikbaar.'),
            ]);
        }

        if ((int) $entry->edition_number === $editionNumber) {
            return $entry;
        }

        return DB::transaction(function () use ($entry, $product, $editionNumber): FoundingCircleRegisterEntry {
            $piece = $this->assertAssignable($product, $editionNumber, $entry->user_id);
            $this->freePieceForEntry($entry);
            $this->holdPiece($piece);

            if ($entry->order !== null) {
                $entry->order->forceFill([
                    'edition_piece_id' => $piece->id,
                    'edition_number' => $editionNumber,
                ])->save();
            }

            $entry->fill([
                'product_id' => $product->id,
                'edition_number' => $editionNumber,
            ])->save();

            $this->inventory->bust($product);
            $this->snapshot->bust();

            return $entry->refresh();
        });
    }

    public function updateListing(User $user, RegisterVisibility $visibility, bool $consent): FoundingCircleRegisterEntry
    {
        $entry = FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->first();

        if ($entry === null) {
            throw ValidationException::withMessages([
                'visibility' => __('Er is nog geen Heritage-editie aan uw account gekoppeld.'),
            ]);
        }

        if ($visibility->isPublic() && ! $consent) {
            throw ValidationException::withMessages([
                'consent' => __('Vink de toestemming aan om uw naam in het publieke register te tonen.'),
            ]);
        }

        $entry->fill([
            'register_visibility' => $visibility,
            'register_consent_at' => $visibility->isPublic() ? ($entry->register_consent_at ?? now()) : null,
        ])->save();

        $this->snapshot->bust();

        return $entry->refresh();
    }

    public function setHidden(FoundingCircleRegisterEntry $entry, bool $hidden): FoundingCircleRegisterEntry
    {
        $entry->fill(['register_hidden_by_admin' => $hidden])->save();
        $this->snapshot->bust();

        return $entry->refresh();
    }

    private function writePlace(User $user, Product $product, int $editionNumber, ?Order $order): FoundingCircleRegisterEntry
    {
        $entry = FoundingCircleRegisterEntry::query()->firstOrNew(['user_id' => $user->id]);
        $creating = ! $entry->exists;

        $entry->fill([
            'product_id' => $product->id,
            'order_id' => $order?->id ?? $entry->order_id,
            'name' => $user->name,
            'edition_number' => $editionNumber,
        ]);

        if ($creating || $entry->joined_at === null) {
            $entry->joined_at = now();
            $entry->register_visibility = RegisterVisibility::Private;
            $entry->register_consent_at = null;
            $entry->register_hidden_by_admin = false;
        }

        $entry->save();

        return $entry->refresh();
    }

    private function releaseEntry(FoundingCircleRegisterEntry $entry): void
    {
        $entry->loadMissing('user', 'product', 'order');
        $this->freePieceForEntry($entry);
        $user = $entry->user;
        $product = $entry->product;
        $entry->delete();
        $user?->removeRole(RoleEnum::FOUNDING_CIRCLE->value);

        if ($product !== null) {
            $this->inventory->bust($product);
        }

        $this->snapshot->bust();
    }

    private function freePieceForEntry(FoundingCircleRegisterEntry $entry): void
    {
        if ($entry->edition_number === null || $entry->product_id === null) {
            return;
        }

        $product = $entry->product ?? Product::query()->find($entry->product_id);

        if ($product === null) {
            return;
        }

        $piece = $this->findPiece($product, (int) $entry->edition_number);

        if ($piece === null || $piece->status !== EditionPieceStatus::Allocated) {
            return;
        }

        $ownedByMember = $piece->order_id === null
            || ($entry->order_id !== null && (int) $piece->order_id === (int) $entry->order_id);

        if (! $ownedByMember) {
            return;
        }

        $piece->fill([
            'status' => EditionPieceStatus::Available,
            'order_id' => null,
            'reserved_until' => null,
            'allocated_at' => null,
        ])->save();

        if ($entry->order !== null && (int) $entry->order->edition_number === (int) $entry->edition_number) {
            $entry->order->forceFill([
                'edition_piece_id' => null,
                'edition_number' => null,
            ])->save();
        }
    }

    private function holdPiece(EditionPiece $piece): void
    {
        if ($piece->status === EditionPieceStatus::Available) {
            $piece->fill([
                'status' => EditionPieceStatus::Allocated,
                'allocated_at' => now(),
                'reserved_until' => null,
            ])->save();
        }
    }

    private function grantRole(User $user): void
    {
        Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
        $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    }

    private function isFoundingProduct(?Product $product): bool
    {
        return $product !== null && $product->slug === Product::FOUNDING_SLUG;
    }

    private function assertAssignable(Product $product, int $number, ?int $exceptUserId): EditionPiece
    {
        if ($number < 1 || $number > (int) $product->edition_total) {
            throw ValidationException::withMessages([
                'edition_number' => __('Kies een nummer tussen 001 en :total.', [
                    'total' => str_pad((string) $product->edition_total, 3, '0', STR_PAD_LEFT),
                ]),
            ]);
        }

        if (in_array($number, $product->archiveEditionNumberList(), true)) {
            throw ValidationException::withMessages([
                'edition_number' => __('Dit nummer behoort tot het archief en is niet te koop.'),
            ]);
        }

        $taken = FoundingCircleRegisterEntry::query()
            ->where('edition_number', $number)
            ->when($exceptUserId !== null, fn ($query) => $query->where('user_id', '!=', $exceptUserId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'edition_number' => __('Dit nummer is al ingeschreven.'),
            ]);
        }

        $piece = $this->findPiece($product, $number);

        if ($piece === null) {
            throw ValidationException::withMessages([
                'edition_number' => __('Dit nummer bestaat niet.'),
            ]);
        }

        if ($piece->status === EditionPieceStatus::Archive) {
            throw ValidationException::withMessages([
                'edition_number' => __('Dit nummer behoort tot het archief en is niet te koop.'),
            ]);
        }

        if (in_array($piece->status, [EditionPieceStatus::Allocated, EditionPieceStatus::Reserved], true)
            && $piece->order_id !== null) {
            $ownerId = Order::query()->whereKey($piece->order_id)->value('user_id');

            if ($ownerId !== null && $exceptUserId !== null && (int) $ownerId !== $exceptUserId) {
                throw ValidationException::withMessages([
                    'edition_number' => __('Dit nummer is al gekoppeld aan een andere eigenaar.'),
                ]);
            }

            if ($ownerId !== null && $exceptUserId === null) {
                throw ValidationException::withMessages([
                    'edition_number' => __('Dit nummer is al gekoppeld aan een andere eigenaar.'),
                ]);
            }
        }

        return $piece;
    }

    private function findPiece(Product $product, int $number): ?EditionPiece
    {
        $label = $product->formatEditionLabel($number);
        $digits = $product->formatEditionDigits($number);

        return EditionPiece::query()
            ->where('product_id', $product->id)
            ->where(function ($query) use ($label, $digits, $number): void {
                $query->where('edition_number', $label)
                    ->orWhere('edition_number', $digits)
                    ->orWhere('edition_number', (string) $number);
            })
            ->first();
    }
}
