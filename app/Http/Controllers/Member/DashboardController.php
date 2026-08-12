<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Support\MemberDemo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class DashboardController extends Controller
{
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
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('member.profile');
    }

    public function security(Request $request, string $locale): Response
    {
        return Inertia::render('member/security', [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'twoFactorEnabled' => Features::canManageTwoFactorAuthentication()
                ? $request->user()->hasEnabledTwoFactorAuthentication()
                : false,
        ]);
    }

    public function destroy(Request $request, string $locale): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
