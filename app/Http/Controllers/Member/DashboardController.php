<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateLetterPreferencesRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use App\Services\Newsletter\HeritageLetterSubscription;
use App\Support\OrderPresenter;
use App\Support\PassportPresenter;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $middleware = [
            new Middleware('founding-circle', only: ['heritage', 'passport', 'circle']),
        ];

        if (Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $middleware[] = new Middleware('password.confirm', only: ['security']);
        }

        return $middleware;
    }

    public function index(Request $request, string $locale, PassportPresenter $passport, OrderPresenter $orders): Response
    {
        $user = $request->user();
        $heritage = $passport->heritageOrder($user);
        $number = $heritage?->edition_number !== null
            ? str_pad((string) $heritage->edition_number, 3, '0', STR_PAD_LEFT)
            : '—';

        return Inertia::render('member/dashboard', [
            'member' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'editionNumber' => $number,
                'orderStatus' => $heritage ? $orders->statusLabel($heritage->status) : __('Geen editie'),
                'reservedAt' => $heritage?->created_at?->translatedFormat('j F Y') ?? '',
            ],
            'stats' => [
                ['label' => __('Editie'), 'value' => $number === '—' ? '—' : 'No.'.$number, 'hint' => __('Founding Edition')],
                ['label' => __('Bestelling'), 'value' => $heritage ? $orders->statusLabel($heritage->status) : __('Geen'), 'hint' => $heritage?->created_at?->toDateString() ?? ''],
                ['label' => __('Circle'), 'value' => $user->isFoundingCircle() ? __('Lid') : __('Nog niet'), 'hint' => __('Founding Circle')],
            ],
        ]);
    }

    public function heritage(Request $request, string $locale, PassportPresenter $passport): Response
    {
        $order = $passport->heritageOrder($request->user());
        abort_if($order === null, 404);

        $order->loadMissing('product');
        $number = str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT);

        return Inertia::render('member/heritage', [
            'heritage' => [
                'productName' => $order->product?->translated('name'),
                'editionTotal' => $order->product?->edition_total,
                'editionNumber' => $number,
                'status' => $order->status->value,
                'deliveryWindow' => $order->product?->translated('expected_delivery_label')
                    ?? $order->shipped_at?->toDateString()
                    ?? __('In productie'),
                'certificate' => __('Gekoppeld aan No. :number', ['number' => $number]),
                'passport' => __('Vier pagina\'s · gekoppeld aan No. :number', ['number' => $number]),
            ],
        ]);
    }

    public function orders(Request $request, string $locale, OrderPresenter $presenter): Response
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->get()
            ->map(fn (Order $order) => $presenter->summary($order))
            ->values();

        return Inertia::render('member/orders', [
            'orders' => $orders,
        ]);
    }

    public function orderShow(Request $request, string $locale, Order $order, OrderPresenter $presenter): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return Inertia::render('member/order-show', [
            'order' => $presenter->detail($order),
        ]);
    }

    public function passport(Request $request, string $locale, PassportPresenter $presenter): Response
    {
        $passport = $presenter->forUser($request->user());
        abort_if($passport === null, 404);

        return Inertia::render('member/passport', [
            'passport' => $passport,
        ]);
    }

    public function circle(Request $request, string $locale, PassportPresenter $presenter): Response
    {
        $card = $presenter->circleCard($request->user());
        abort_if($card === null, 404);

        return Inertia::render('member/circle', [
            'card' => $card,
        ]);
    }

    public function letter(Request $request, string $locale, HeritageLetterSubscription $subscription): Response
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('email', Str::lower($request->user()->email))
            ->first();

        return Inertia::render('member/letter', [
            'preferences' => $subscriber
                ? $subscription->normalizePreferences($subscriber->preferences ?? [])
                : [
                    'heritageLetter' => false,
                    'productUpdates' => false,
                    'events' => false,
                ],
            'status' => $subscriber?->status->value,
        ]);
    }

    public function updateLetter(
        UpdateLetterPreferencesRequest $request,
        string $locale,
        HeritageLetterSubscription $subscription,
    ): RedirectResponse {
        $subscription->applyPreferences(
            $request->user(),
            $request->validated(),
            $request,
            $locale,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Voorkeuren opgeslagen.')]);

        return back();
    }

    public function profile(Request $request, string $locale): Response
    {
        return Inertia::render('member/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function updateProfile(
        ProfileUpdateRequest $request,
        string $locale,
        HeritageLetterSubscription $subscription,
    ): RedirectResponse {
        $user = $request->user();
        $previousEmail = $user->email;

        $user->fill($request->safe()->only(['name', 'email', 'username']));

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
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

        if ($emailChanged) {
            $subscription->rebindEmail($previousEmail, $user);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profiel bijgewerkt.')]);

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
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword');
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
