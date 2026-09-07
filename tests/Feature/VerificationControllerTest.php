<?php

use App\Enums\EditionPieceStatus;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('a valid verification token shows the piece details with order holder', function () {
    $user = User::factory()->create([
        'name' => 'Yusuf Savran',
        'username' => 'yusuf_savran',
    ]);
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'name' => 'Yusuf Savran',
    ]);

    /*
     * EditionPieceSeeder (run for every Feature test) already provisions
     * numbered pieces 001-100 for the founding product, so the piece under
     * test is fetched and allocated rather than freshly created.
     */
    $piece = EditionPiece::query()
        ->where('product_id', $order->product_id)
        ->where('edition_number', $order->product->formatEditionLabel(7))
        ->firstOrFail();
    $piece->update([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'allocated_at' => now(),
    ]);

    $this->get(localized('maison.verify', ['token' => $piece->verification_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/verify')
            ->where('piece.editionNumber', $piece->formattedNumber())
            ->where('piece.status', EditionPieceStatus::Allocated->value)
            ->where('piece.statusLabel', 'Toegewezen')
            ->where('piece.holder.name', 'Yusuf Savran')
            ->where('piece.holder.username', 'yusuf_savran')
            ->where('piece.holder.isFoundingCircle', true)
            ->where('piece.holder.source', 'order')
        );
});

test('a claim-allocated piece shows the founding circle holder', function () {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'username' => 'ada_lovelace',
    ]);
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $piece = EditionPiece::query()
        ->where('status', EditionPieceStatus::Available)
        ->where('edition_number', '042')
        ->firstOrFail();

    $piece->update([
        'status' => EditionPieceStatus::Allocated,
        'allocated_at' => now(),
    ]);

    FoundingCircleClaim::factory()->approved()->create([
        'user_id' => $user->id,
        'product_id' => $piece->product_id,
        'edition_piece_id' => $piece->id,
        'edition_number' => 42,
        'reviewed_at' => now(),
    ]);

    $this->get(localized('maison.verify', ['token' => $piece->verification_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/verify')
            ->where('piece.editionNumber', $piece->fresh()->formattedNumber())
            ->where('piece.status', EditionPieceStatus::Allocated->value)
            ->where('piece.holder.name', 'Ada Lovelace')
            ->where('piece.holder.username', 'ada_lovelace')
            ->where('piece.holder.isFoundingCircle', true)
            ->where('piece.holder.source', 'claim')
            ->where('piece.holder.memberSince', now()->translatedFormat('j F Y'))
        );
});

test('an unknown verification token returns a 404', function () {
    $this->get(localized('maison.verify', ['token' => 'does-not-exist']))
        ->assertNotFound();
});

test('an archive-status piece is shown as not for sale', function () {
    $piece = EditionPiece::query()
        ->where('status', EditionPieceStatus::Archive)
        ->firstOrFail();

    $this->get(localized('maison.verify', ['token' => $piece->verification_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/verify')
            ->where('piece.status', EditionPieceStatus::Archive->value)
            ->where('piece.statusLabel', 'Gearchiveerd')
            ->where('piece.holder', null)
        );
});

test('verification page localizes allocated dates for the active locale', function () {
    $order = Order::factory()->create(['name' => 'Claire Dubois']);

    $piece = EditionPiece::query()
        ->where('product_id', $order->product_id)
        ->where('edition_number', $order->product->formatEditionLabel(11))
        ->firstOrFail();
    $piece->update([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'allocated_at' => now()->startOfMonth(),
    ]);

    $this->get(route('maison.verify', [
        'locale' => 'fr',
        'token' => $piece->verification_token,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/verify')
            ->where('piece.allocatedAt', now()->startOfMonth()->locale('fr')->translatedFormat('j F Y'))
            ->where('piece.holder.name', 'Claire Dubois')
        );
});
