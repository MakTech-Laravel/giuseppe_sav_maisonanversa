<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, string $locale): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                [
                    'key' => 'Klanten',
                    'value' => (string) User::query()->where('type', UserType::Customer)->count(),
                    'hintKey' => 'Lid-accounts',
                ],
                [
                    'key' => 'Beheerders',
                    'value' => (string) User::query()->where('type', UserType::Admin)->count(),
                    'hintKey' => 'Personeelsaccounts',
                ],
                [
                    'key' => 'Posts',
                    'value' => (string) Post::query()->count(),
                    'hintKey' => 'Contentitems',
                ],
            ],
            'recentCustomers' => User::query()
                ->where('type', UserType::Customer)
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'username', 'created_at'])
                ->map(fn (User $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'username' => $customer->username,
                    'created_at' => $customer->created_at?->toDateString(),
                ]),
            'staffName' => $request->user()?->name ?? '',
        ]);
    }
}
