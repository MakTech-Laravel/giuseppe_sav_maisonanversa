<?php

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('password reset otp can be requested for an existing user', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('password_reset_otp_sent');

    Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user): bool {
        return $mail->hasTo($user->email)
            && strlen($mail->otp) === PasswordResetOtpService::OTP_LENGTH;
    });

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeTrue();
});

test('password reset otp request does not reveal missing accounts', function () {
    $this->post(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertRedirect()
        ->assertSessionHas('password_reset_otp_sent');

    Mail::assertNothingSent();
});

test('password can be reset with a valid otp', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);
    $otp = '483920';

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($otp),
        'attempts' => 0,
        'created_at' => now(),
    ]);

    $response = $this->post(route('password.update'), [
        'email' => $user->email,
        'otp' => $otp,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Hash::check('NewPassword1!', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse()
        ->and($response->headers->get('Location'))->toContain('/nl');

    $response->assertSessionMissing('open_auth_modal');
});

test('invalid otp is rejected and increments attempts', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('111111'),
        'attempts' => 0,
        'created_at' => now(),
    ]);

    $this->from(route('password.request'))
        ->post(route('password.update'), [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertSessionHasErrors('otp');

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->value('attempts'))->toBe(1);
});

test('otp expires after the configured window', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);
    $otp = '222333';

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($otp),
        'attempts' => 0,
        'created_at' => now()->subMinutes(11),
    ]);

    $this->post(route('password.update'), [
        'email' => $user->email,
        'otp' => $otp,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertSessionHasErrors('otp');
});

test('too many invalid otp attempts invalidate the code', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('555555'),
        'attempts' => PasswordResetOtpService::MAX_ATTEMPTS - 1,
        'created_at' => now(),
    ]);

    $this->post(route('password.update'), [
        'email' => $user->email,
        'otp' => '000000',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertSessionHasErrors('otp');

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();
});

test('reset password screen can be rendered', function () {
    $this->get(route('password.reset', ['locale' => 'en', 'email' => 'member@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/reset-password')
            ->where('email', 'member@example.com'));
});

test('branded reset password otp mail includes the site logo and code', function () {
    $mailable = new ResetPasswordMail(otp: '847291', expireMinutes: 10, locale: 'en');

    $html = $mailable->render();

    expect($html)
        ->toContain('images/logos/logo-icon.jpg')
        ->toContain('847291')
        ->toContain('#F3EBE3')
        ->not->toContain('reset-password/');
});
