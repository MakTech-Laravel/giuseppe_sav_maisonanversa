<?php

use App\Console\Commands\FoundingCircle\PromoteRegisterInscriptionsCommand;
use App\Enums\EditionPieceStatus;
use App\Enums\RoleEnum;
use App\Mail\FoundingCircle\InscriptionMail;
use App\Models\EditionPiece;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Support\FoundingCircleRegisterPresenter;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

function heritageReservedOrder(User $user, int $edition = 12): Order
{
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel($edition))
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->paid()->create([
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => $edition,
        'amount' => '249.00',
        'delivered_on' => null,
        'delivered_at' => null,
        'shipped_at' => null,
    ]);

    $piece->fill([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'allocated_at' => now(),
    ])->save();

    return $order;
}

test('payment creates a reserved register place without a public name', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::USER->value);
    $order = heritageReservedOrder($user, 12);

    $entry = app(FoundingCircleRegistrar::class)->inscribeFromOrder($order);

    expect($entry)->not->toBeNull()
        ->and($entry->inscribed_at)->toBeNull()
        ->and($user->fresh()->isFoundingCircle())->toBeTrue();

    $place = collect(app(FoundingCircleRegisterPresenter::class)->ledger())
        ->firstWhere('sequence', 12);

    expect($place['state'])->toBe('reserved')
        ->and($place['label_key'])->toBe('Gereserveerd')
        ->and($place['name'])->toBeNull();
});

test('daily promotion after delivery window inscribes and mails the member', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'inscribe@example.com', 'locale' => 'nl']);
    $order = heritageReservedOrder($user, 15);
    $order->update([
        'delivered_on' => now()->subDays(20)->toDateString(),
    ]);

    app(FoundingCircleRegistrar::class)->inscribeFromOrder($order);

    $this->artisan(PromoteRegisterInscriptionsCommand::class)
        ->assertSuccessful();

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->first();

    expect($entry?->inscribed_at)->not->toBeNull();

    Mail::assertQueued(InscriptionMail::class);

    $place = collect(app(FoundingCircleRegisterPresenter::class)->ledger())
        ->firstWhere('sequence', 15);

    expect($place['state'])->toBe('inscribed')
        ->and($place['label_key'])->toBe('Privélid');
});

test('promotion waits until the withdrawal deadline passes', function () {
    Mail::fake();

    $user = User::factory()->create();
    $order = heritageReservedOrder($user, 18);
    $order->update([
        'delivered_on' => now()->subDays(3)->toDateString(),
    ]);

    app(FoundingCircleRegistrar::class)->inscribeFromOrder($order);

    $this->artisan(PromoteRegisterInscriptionsCommand::class)
        ->assertSuccessful();

    expect(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->value('inscribed_at'))
        ->toBeNull();

    Mail::assertNothingQueued();
});

test('listing updates are blocked while the place is still reserved', function () {
    $user = User::factory()->create();
    $order = heritageReservedOrder($user, 22);
    app(FoundingCircleRegistrar::class)->inscribeFromOrder($order);

    $this->actingAs($user)
        ->patch(localized('member.register-listing.update'), [
            'visibility' => 'full',
            'consent' => true,
        ])
        ->assertSessionHasErrors('visibility');
});
