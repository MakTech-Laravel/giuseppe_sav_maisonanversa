<?php

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;

test('a valid verification token shows the piece details', function () {
    $order = Order::factory()->create(['name' => 'Yusuf Savran']);

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
            ->where('piece.owner', 'Yusuf Savran')
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
            ->where('piece.owner', null)
        );
});
