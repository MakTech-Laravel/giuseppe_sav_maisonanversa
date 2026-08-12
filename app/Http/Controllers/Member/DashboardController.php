<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Support\MemberDemo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class DashboardController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
                ? [new Middleware('password.confirm', only: ['security'])]
                : [];
    }

    public function index(Request $request, string $locale): Response
    {
        $user = $request->user();

        return Inertia::render('member/dashboard', [
            'member' => MemberDemo::member($user),
            'stats' => MemberDemo::dashboardStats($user),
        ]);
    }

    public function heritage(Request $request, string $locale): Response
    {
        return Inertia::render('member/heritage', [
            'heritage' => MemberDemo::heritage($request->user()),
        ]);
    }

    public function orders(string $locale): Response
    {
        return Inertia::render('member/orders', [
            'orders' => MemberDemo::orders(),
        ]);
    }

    public function orderShow(string $locale, string $order): Response
    {
        $detail = MemberDemo::order($order);

        abort_if($detail === null, 404);

        return Inertia::render('member/order-show', [
            'order' => $detail,
        ]);
    }

    public function passport(Request $request, string $locale): Response
    {
        return Inertia::render('member/passport', [
            'passport' => MemberDemo::passport($request->user()),
        ]);
    }

    public function circle(Request $request, string $locale): Response
    {
        return Inertia::render('member/circle', [
            'card' => MemberDemo::circleCard($request->user()),
        ]);
    }

    public function letter(string $locale): Response
    {
        return Inertia::render('member/letter', [
            'preferences' => MemberDemo::letterPreferences(),
        ]);
    }

    public function profile(Request $request, string $locale): Response
    {
        return Inertia::render('member/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request, string $locale): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email', 'username']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user);
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteAvatar($user);
            $user->avatar = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('member.profile');
    }

    public function security(TwoFactorAuthenticationRequest $request, string $locale): Response
    {
        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('member/security', $props);
    }

    public function destroy(Request $request, string $locale): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $this->deleteAvatar($user);

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
