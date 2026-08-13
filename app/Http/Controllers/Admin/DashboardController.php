<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request, string $locale): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                [
                    'label' => 'Customers',
                    'value' => (string) User::query()->where('type', UserType::Customer)->count(),
                    'hint' => 'Member accounts',
                ],
                [
                    'label' => 'Admins',
                    'value' => (string) User::query()->where('type', UserType::Admin)->count(),
                    'hint' => 'Staff accounts',
                ],
                [
                    'label' => 'Roles',
                    'value' => (string) Role::query()->count(),
                    'hint' => 'Access roles',
                ],
                [
                    'label' => 'Posts',
                    'value' => (string) Post::query()->count(),
                    'hint' => 'Content entries',
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
