<?php

use App\Enums\UserGender;
use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Support\Header;

test('guests redirected from a protected page keep that url as a relative intended path', function () {
    $target = localized('member.orders', absolute: false);

    $this->get($target)
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login')
        ->assertSessionHas('url.intended', $target);
});

test('login after a guest hit on a protected page returns to that page', function () {
    $user = User::factory()->create();
    $target = localized('member.orders', absolute: false);

    $this->get($target)
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('url.intended', $target);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('inertia login after a guest hit on a nested member page returns there', function () {
    $user = User::factory()->create();
    $target = localized('member.profile', absolute: false);

    $this->get($target)
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('url.intended', $target);

    $this->withHeaders([
        'Accept' => 'text/html, application/xhtml+xml',
        'X-Requested-With' => 'XMLHttpRequest',
        Header::INERTIA => 'true',
        Header::VERSION => 'test',
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('login after a guest hit on a public intended page returns there', function () {
    $user = User::factory()->create();
    $target = localized('maison.story', absolute: false);

    $this->withSession([
        'url.intended' => $target,
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('following the guest redirect to home does not wipe the intended url', function () {
    $user = User::factory()->create();
    $target = localized('member.orders', absolute: false);

    $guest = $this->get($target)
        ->assertRedirect(localized('maison.home', absolute: false));

    expect(session('url.intended'))->toBe($target);

    $this->get($guest->headers->get('Location'))
        ->assertOk()
        ->assertSessionHas('url.intended', $target);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('absolute intended member urls are normalized to relative paths', function () {
    $user = User::factory()->create();
    $target = localized('member.orders', absolute: false);

    $this->withSession(['url.intended' => url($target)])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect($target);
});

test('external intended urls are rejected as open redirects', function () {
    $user = User::factory()->create();

    $this->withSession(['url.intended' => 'https://evil.example/phish'])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(localized('member.dashboard', absolute: false));
});

test('inertia guest visit to a protected community page keeps intended through login', function () {
    $user = User::factory()->create();
    $target = localized('community.sessions.index', absolute: false);

    $this->withHeaders([
        'Accept' => 'text/html, application/xhtml+xml',
        'X-Requested-With' => 'XMLHttpRequest',
        Header::INERTIA => 'true',
        Header::VERSION => 'test',
    ])->get($target)
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('url.intended', $target);

    $this->withHeaders([
        'Accept' => 'text/html, application/xhtml+xml',
        'X-Requested-With' => 'XMLHttpRequest',
        Header::INERTIA => 'true',
        Header::VERSION => 'test',
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('guest visit to login does not replace an existing intended url', function () {
    $target = localized('member.profile', absolute: false);

    $this->withSession(['url.intended' => $target])
        ->get(route('login'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login')
        ->assertSessionHas('url.intended', $target);
});

test('auth intended endpoint stores a public page for modal login', function () {
    $user = User::factory()->create();
    $target = localized('maison.community', absolute: false);

    $this->post(route('auth.intended'), ['url' => $target])
        ->assertNoContent()
        ->assertSessionHas('url.intended', $target);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($target);
});

test('auth intended endpoint refuses to remember the localized home', function () {
    $home = localized('maison.home', absolute: false);

    $this->withSession(['url.intended' => localized('member.orders', absolute: false)])
        ->post(route('auth.intended'), ['url' => $home])
        ->assertNoContent()
        ->assertSessionHas('url.intended', localized('member.orders', absolute: false));
});

test('a guest who starts a reservation on the home page is sent back to it after login', function () {
    $user = User::factory()->create();
    $home = localized('maison.home', absolute: false);

    $this->post(route('auth.intended'), ['url' => $home, 'resume' => true])
        ->assertNoContent()
        ->assertSessionHas('url.intended', $home);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($home);
});

test('a guest who registers in the middle of a reservation is sent back to the home page too', function () {
    $home = localized('maison.home', absolute: false);

    $this->post(route('auth.intended'), ['url' => $home, 'resume' => true])
        ->assertSessionHas('url.intended', $home);

    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'gender' => UserGender::Female->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect($home);
});

test('the resume flag opens nothing but the home page: auth entries and other hosts are still refused', function () {
    $this->withSession(['url.intended' => localized('member.orders', absolute: false)])
        ->post(route('auth.intended'), ['url' => '/login', 'resume' => true])
        ->assertSessionHas('url.intended', localized('member.orders', absolute: false));

    $this->withSession(['url.intended' => localized('member.orders', absolute: false)])
        ->post(route('auth.intended'), ['url' => 'https://evil.example/nl', 'resume' => true])
        ->assertSessionHas('url.intended', localized('member.orders', absolute: false));

    $this->post(route('auth.intended'), ['url' => localized('maison.home', absolute: false), 'resume' => 'nope'])
        ->assertSessionHasErrors('resume');
});

test('without the resume flag the home page is still never remembered', function () {
    $home = localized('maison.home', absolute: false);

    $this->withSession(['url.intended' => localized('member.orders', absolute: false)])
        ->post(route('auth.intended'), ['url' => $home, 'resume' => false])
        ->assertNoContent()
        ->assertSessionHas('url.intended', localized('member.orders', absolute: false));
});

test('bare admin urls remember a localized admin destination for staff', function () {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole('admin');
    $admin->syncTypeFromRoles();

    $this->get('/admin/customers')
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login')
        ->assertSessionHas('url.intended', '/'.defaultLocale().'/admin/customers');

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/'.defaultLocale().'/admin/customers');
});

test('post login redirect service normalizes absolute app urls', function () {
    $redirects = app(PostLoginRedirectService::class);
    $request = request();
    $target = localized('member.orders', absolute: false);

    expect($redirects->normalizeToRelativePath(url($target), $request))->toBe($target)
        ->and($redirects->normalizeToRelativePath('https://evil.example/x', $request))->toBeNull();
});
