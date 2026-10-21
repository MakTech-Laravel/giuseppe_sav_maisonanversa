<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\RoleEnum;
use App\Jobs\Returns\SendReturnAdminRequestMail;
use App\Jobs\Returns\SendReturnCustomerMail;
use App\Mail\OrderConfirmation;
use App\Models\EditionPiece;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Services\Returns\ReturnEligibility;
use App\Services\Returns\ReturnService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Stripe\Service\RefundService;
use Stripe\StripeClient;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    config(['cashier.secret' => 'sk_test_fake']);
});

function fakeStripeRefund(?string $id = 're_test_123'): void
{
    $refunds = Mockery::mock(RefundService::class);
    $refunds->shouldReceive('create')
        ->andReturn((object) ['id' => $id]);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->refunds = $refunds;
    app()->bind(StripeClient::class, fn () => $stripe);
}

function heritagePaidOrder(User $user, int $edition = 12): Order
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
        'stripe_payment_intent_id' => 'pi_test_return_'.$edition,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Paid,
        'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
        'amount' => $order->amount,
    ]);

    $piece->fill([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'allocated_at' => now(),
    ])->save();

    app(FoundingCircleRegistrar::class)->inscribeFromOrder($order);

    return $order->fresh();
}

test('unshipped paid orders can be cancelled with a full refund and freed edition', function () {
    Queue::fake();
    fakeStripeRefund();

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 15);

    $this->actingAs($user)
        ->post(route('member.orders.cancel', ['locale' => 'nl', 'order' => $order->id]))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Available);
});

test('return items is visible within the withdrawal window and gone after day 15', function () {
    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 16);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(10),
        'delivered_at' => now()->subDays(3),
        'delivered_on' => now()->subDays(3)->toDateString(),
    ]);

    $eligibility = app(ReturnEligibility::class)->forOrder($order->fresh());

    expect($eligibility['can_return'])->toBeTrue()
        ->and($eligibility['request_deadline'])->toBe(now()->subDays(3)->addDays(14)->toDateString());

    $order->update([
        'delivered_on' => now()->subDays(15)->toDateString(),
        'delivered_at' => now()->subDays(15),
    ]);

    $ended = app(ReturnEligibility::class)->forOrder($order->fresh());

    expect($ended['can_return'])->toBeFalse()
        ->and($ended['period_ended'])->toBeTrue();
});

test('full racket return flow marks returning then available after refund', function () {
    Queue::fake();
    fakeStripeRefund('re_full_1');
    Storage::fake('public');

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 17);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(5),
        'delivered_at' => now()->subDays(2),
        'delivered_on' => now()->subDays(2)->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('member.orders.returns.store', ['locale' => 'nl', 'order' => $order->id]), [
            'confirmed' => '1',
            'reason' => ReturnReason::NotExpected->value,
        ])
        ->assertRedirect();

    $return = OrderReturn::query()->where('order_id', $order->id)->first();

    expect($return)->not->toBeNull()
        ->and($return->status)->toBe(ReturnStatus::Requested)
        ->and(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Returning);

    Queue::assertPushed(SendReturnCustomerMail::class);
    Queue::assertPushed(SendReturnAdminRequestMail::class);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::ADMIN->value);

    app(ReturnService::class)->addTracking($return, $user, 'Bpost', 'TRACK123');
    app(ReturnService::class)->markReceived($return->fresh(), $admin);
    app(ReturnService::class)->refundFull($return->fresh(), $admin);

    expect($return->fresh()->status)->toBe(ReturnStatus::Refunded)
        ->and($return->fresh()->stripe_refund_id)->toBe('re_full_1')
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Available);
});

test('partial refund stores amount and frees the founding number', function () {
    Queue::fake();
    fakeStripeRefund('re_partial_1');

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 18);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(4),
        'delivered_at' => now()->subDays(2),
        'delivered_on' => now()->subDays(2)->toDateString(),
    ]);

    $return = app(ReturnService::class)->request(
        $order,
        $user,
        ReturnReason::Other,
        null,
        true,
    );

    $admin = User::factory()->admin()->create();
    app(ReturnService::class)->addTracking($return, $user, 'DHL', 'DHL1');
    app(ReturnService::class)->refundPartial($return->fresh(), $admin, '200.00', 'Loss in value');

    expect($return->fresh()->status)->toBe(ReturnStatus::PartiallyRefunded)
        ->and((string) $return->fresh()->refund_amount)->toBe('200.00')
        ->and($return->fresh()->refund_reason)->toBe('Loss in value')
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('declined return restores the inscribed edition', function () {
    Queue::fake();

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 19);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(4),
        'delivered_at' => now()->subDays(1),
        'delivered_on' => now()->subDays(1)->toDateString(),
    ]);

    $return = app(ReturnService::class)->request($order, $user, null, null, true);
    expect(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Returning);

    $admin = User::factory()->admin()->create();
    app(ReturnService::class)->decline($return->fresh(), $admin, 'Not received after 30 days');

    expect($return->fresh()->status)->toBe(ReturnStatus::Declined)
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Allocated);
});

test('customer can withdraw a requested return and restore the register', function () {
    Queue::fake();

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 20);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(3),
        'delivered_at' => now()->subDay(),
        'delivered_on' => now()->subDay()->toDateString(),
    ]);

    $return = app(ReturnService::class)->request($order, $user, null, null, true);

    $this->actingAs($user)
        ->delete(route('member.returns.destroy', ['locale' => 'nl', 'orderReturn' => $return->id]))
        ->assertRedirect();

    expect($return->fresh()->status)->toBe(ReturnStatus::Cancelled)
        ->and(EditionPiece::query()->find($order->edition_piece_id)?->status)->toBe(EditionPieceStatus::Allocated);
});

test('damaged reason requires photos and sets maison pays shipping', function () {
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 21);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(3),
        'delivered_at' => now()->subDay(),
        'delivered_on' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('member.orders.returns.store', ['locale' => 'nl', 'order' => $order->id]), [
            'confirmed' => '1',
            'reason' => ReturnReason::Damaged->value,
        ])
        ->assertSessionHasErrors('photos');

    $this->actingAs($user)
        ->post(route('member.orders.returns.store', ['locale' => 'nl', 'order' => $order->id]), [
            'confirmed' => '1',
            'reason' => ReturnReason::Damaged->value,
            'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])
        ->assertRedirect();

    $return = OrderReturn::query()->where('order_id', $order->id)->first();

    expect($return?->maison_pays_return_shipping)->toBeTrue()
        ->and($return?->photos)->toHaveCount(1);
});

test('a second return is blocked while a consuming return exists', function () {
    Queue::fake();

    $user = User::factory()->create();
    $order = heritagePaidOrder($user, 22);
    $order->update([
        'status' => OrderStatus::Delivered,
        'shipped_at' => now()->subDays(3),
        'delivered_at' => now()->subDay(),
        'delivered_on' => now()->subDay()->toDateString(),
    ]);

    app(ReturnService::class)->request($order, $user, null, null, true);

    expect(app(ReturnEligibility::class)->canRequestReturn($order->fresh()))->toBeFalse();
});

test('order confirmation mail includes the returns link', function () {
    $order = Order::factory()->paid()->create();

    $html = (new OrderConfirmation($order))->render();

    expect($html)->toContain(__('Van gedachten veranderd? Je kunt binnen 14 dagen retourneren via je account.'))
        ->and($html)->toContain(route('maison.returns', ['locale' => 'nl']));
});

test('public returns page and locale aliases resolve', function () {
    $this->get(route('maison.returns', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('maison/returns'));

    $this->get(route('maison.returns.retour', ['locale' => 'nl']))
        ->assertRedirect(route('maison.returns', ['locale' => 'nl']));

    $this->get(route('maison.returns.retours', ['locale' => 'fr']))
        ->assertRedirect(route('maison.returns', ['locale' => 'fr']));
});

test('admin returns index requires returns.manage', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::ADMIN->value);

    $this->actingAs($admin)
        ->get(route('admin.returns.index', ['locale' => 'nl']))
        ->assertOk();
});
