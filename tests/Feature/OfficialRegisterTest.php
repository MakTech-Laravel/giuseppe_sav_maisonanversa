<?php

use App\Enums\EditionPieceStatus;
use App\Enums\RegisterVisibility;
use App\Enums\RoleEnum;
use App\Mail\OrderConfirmation;
use App\Models\EditionPiece;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\LegalPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\FoundingCircle\FoundingCircleRegisterSnapshot;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('the public register always has one hundred places and hides a private name', function () {
    $member = User::factory()->create([
        'name' => 'Ada Lovelace',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    app(FoundingCircleRegistrar::class)->assign($member, 8);

    $this->get(route('maison.register', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/founding-circle/register')
            ->has('places', 100)
            ->where('places.7.number', '008')
            ->where('places.7.state', 'inscribed')
            ->where('places.7.name', null)
            ->where('places.7.label_key', 'Privélid')
            ->where('places.7.you', false)
            ->where('places.0.label_key', 'Niet te koop')
            ->where('places.1.label_key', 'Beschikbaar')
            ->where('foundingRegister.inscribed_count', 1)
            ->where('foundingRegister.places_total', 100)
        );
});

test('the inscribed member sees YOU on their own row', function () {
    $member = User::factory()->create();
    app(FoundingCircleRegistrar::class)->assign($member, 8);

    $this->actingAs($member)
        ->get(route('maison.register', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('places.7.you', true)
            ->where('places.7.name', null)
        );
});

test('a public listing requires consent and private clears it', function () {
    $member = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'name' => 'Ada Lovelace',
    ]);
    app(FoundingCircleRegistrar::class)->assign($member, 9);

    $this->actingAs($member)
        ->from(route('member.register-listing', ['locale' => 'nl']))
        ->patch(route('member.register-listing.update', ['locale' => 'nl']), [
            'visibility' => RegisterVisibility::Full->value,
            'consent' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('consent');

    $this->actingAs($member)
        ->patch(route('member.register-listing.update', ['locale' => 'nl']), [
            'visibility' => RegisterVisibility::Full->value,
            'consent' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $member->id)->first();

    expect($entry->register_visibility)->toBe(RegisterVisibility::Full)
        ->and($entry->register_consent_at)->not->toBeNull();

    $this->get(route('maison.register', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('places.8.name', 'Ada Lovelace')
        );

    $this->actingAs($member)
        ->patch(route('member.register-listing.update', ['locale' => 'nl']), [
            'visibility' => RegisterVisibility::Private->value,
        ])
        ->assertSessionHasNoErrors();

    expect($entry->fresh()->register_consent_at)->toBeNull()
        ->and($entry->fresh()->register_visibility)->toBe(RegisterVisibility::Private);
});

test('the selected listing option uses dark type on the cream card', function () {
    $page = File::get(resource_path('js/pages/member/register-listing.tsx'));

    expect($page)
        ->toContain("? 'text-choc'")
        ->toContain(": 'text-cream'")
        ->toContain("? 'text-choc3'")
        ->toContain(": 'text-sand'")
        ->not->toContain('text-cream uppercase');
});

test('a non member cannot open the register listing', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('member.register-listing', ['locale' => 'nl']))
        ->assertForbidden();
});

test('admin hide forces a private label and a duplicate or archived number is rejected', function () {
    $member = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'name' => 'Ada Lovelace',
    ]);
    $entry = app(FoundingCircleRegistrar::class)->assign($member, 11);
    $entry->update([
        'register_visibility' => RegisterVisibility::Full,
        'register_consent_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.circle.register.hide', [
            'locale' => 'nl',
            'entry' => $entry->id,
        ]), [
            'hidden' => true,
        ])
        ->assertRedirect();

    $this->get(route('maison.register', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('places.10.label_key', 'Privélid')
            ->where('places.10.name', null)
        );

    $other = User::factory()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.circle.index', ['locale' => 'nl']))
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $other->email,
            'edition_number' => 11,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('edition_number');

    $this->actingAs($this->admin)
        ->from(route('admin.circle.index', ['locale' => 'nl']))
        ->post(route('admin.circle.assign', ['locale' => 'nl']), [
            'email' => $other->email,
            'edition_number' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('edition_number');

    expect($other->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});

test('the shared snapshot cache refreshes after an inscription', function () {
    Cache::flush();

    $snapshot = app(FoundingCircleRegisterSnapshot::class);

    expect($snapshot->share()['inscribed_count'])->toBe(0);

    $member = User::factory()->create();
    app(FoundingCircleRegistrar::class)->assign($member, 15);

    expect($snapshot->share()['inscribed_count'])->toBe(1)
        ->and($snapshot->share()['latest_entry']['number'])->toBe('015');
});

test('homepage shares register counters and the public page is noindex', function () {
    $this->get(route('maison.home', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/home')
            ->where('foundingRegister.places_total', 100)
            ->has('foundingRegister.inscribed_count')
            ->has('foundingRegister.remaining_count')
        );

    expect(file_get_contents(resource_path('js/pages/maison/founding-circle/register.tsx')))
        ->toContain('noIndex')
        ->and(file_get_contents(resource_path('js/pages/maison/home.tsx')))->toContain('HomeRegister')
        ->and(file_get_contents(resource_path('js/lib/maison-navigation.ts')))->toContain('/founding-circle/register')
        ->and(file_get_contents(resource_path('js/components/maison/shell/site-nav.tsx')))->toContain('foundingRegister.inscribed_count');
});

test('the founding order email points members at their listing and privacy mentions the register', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('status', EditionPieceStatus::Available)
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->paid()->create([
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => $piece->sequenceNumber(),
    ]);

    $html = (new OrderConfirmation($order))->render();

    expect($html)->toContain('privé-archief')
        ->and($html)->toContain(route('member.register-listing', ['locale' => 'nl']));

    $privacy = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    expect($privacy->body)->toContain('Publiek register');
});
