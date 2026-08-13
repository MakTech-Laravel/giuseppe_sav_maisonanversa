<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Support\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->where('type', UserType::Admin)
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/admins/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
            'superAdminCount' => SuperAdmin::count(),
            'stats' => Inertia::optional(fn (): array => [
                'total' => User::query()->where('type', UserType::Admin)->count(),
                'verified' => User::query()
                    ->where('type', UserType::Admin)
                    ->whereNotNull('email_verified_at')
                    ->count(),
            ]),
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/admins/create');
    }

    public function store(StoreUserRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['type'] = UserType::Admin;
        $data['username'] = User::generateUsername($data['name']);

        $user = User::create(collect($data)->except(['roles', 'remove_avatar'])->all());
        $user->syncRoles([RoleEnum::ADMIN->value]);
        $user->syncTypeFromRoles();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrator created successfully.']);

        return redirect()->route('admin.admins.index');
    }

    public function show(string $locale, User $user): Response
    {
        $this->ensureAdmin($user);

        return Inertia::render('admin/admins/show', [
            'user' => $user,
        ]);
    }

    public function edit(string $locale, User $user): Response
    {
        $this->ensureAdmin($user);
        $this->authorize('update', $user);

        return Inertia::render('admin/admins/edit', [
            'user' => $user,
            'isLastSuperAdmin' => SuperAdmin::isLast($user),
        ]);
    }

    public function update(UpdateUserRequest $request, string $locale, User $user): RedirectResponse
    {
        $this->ensureAdmin($user);
        $this->authorize('update', $user);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteAvatar($user);
            $data['avatar'] = null;
        }

        $user->update(collect($data)->except(['roles', 'remove_avatar'])->all());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrator updated successfully.']);

        return redirect()->route('admin.admins.index');
    }

    public function destroy(string $locale, User $user): RedirectResponse
    {
        $this->ensureAdmin($user);
        $this->authorize('delete', $user);

        if (SuperAdmin::isLast($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'The last super administrator account cannot be deleted.',
            ]);

            return redirect()->back();
        }

        $this->deleteAvatar($user);
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrator deleted successfully.']);

        return redirect()->back();
    }

    private function ensureAdmin(User $user): void
    {
        abort_unless($user->isAdmin(), 404);
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
