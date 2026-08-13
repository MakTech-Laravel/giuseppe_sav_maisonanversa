<?php

namespace App\Http\Controllers\Admin;

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
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $staffRoles = UserType::staffRoleValues();

        $users = User::query()
            ->where('type', UserType::Admin)
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function ($query) use ($role, $staffRoles): void {
                if (! in_array($role, $staffRoles, true)) {
                    return;
                }

                $query->whereHas('roles', fn ($query) => $query->where('name', $role));
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/admins/index', [
            'users' => $users,
            'roles' => Role::query()
                ->whereIn('name', $staffRoles)
                ->orderBy('name')
                ->pluck('name'),
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'superAdminCount' => SuperAdmin::count(),
            'stats' => Inertia::optional(fn (): array => [
                'total' => User::query()->where('type', UserType::Admin)->count(),
                'verified' => User::query()
                    ->where('type', UserType::Admin)
                    ->whereNotNull('email_verified_at')
                    ->count(),
                'roles' => Role::query()->whereIn('name', $staffRoles)->count(),
            ]),
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/admins/create', [
            'roles' => Role::query()
                ->whereIn('name', UserType::staffRoleValues())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();
        $roles = $this->staffRolesOnly($request->validated('roles', []));

        if ($roles === []) {
            return back()->withErrors(['roles' => 'Assign at least one staff role.'])->withInput();
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['type'] = UserType::Admin;
        $data['username'] = User::generateUsername($data['name']);

        $user = User::create(collect($data)->except(['roles', 'remove_avatar'])->all());
        $user->syncRoles($roles);
        $user->syncTypeFromRoles();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Admin created successfully.']);

        return redirect()->route('admin.admins.index');
    }

    public function show(string $locale, User $user): Response
    {
        $this->ensureAdmin($user);

        $user->load('roles:id,name', 'permissions:id,name');

        return Inertia::render('admin/admins/show', [
            'user' => $user,
        ]);
    }

    public function edit(string $locale, User $user): Response
    {
        $this->ensureAdmin($user);
        $this->authorize('update', $user);

        $user->load('roles:id,name');

        return Inertia::render('admin/admins/edit', [
            'user' => $user,
            'roles' => Role::query()
                ->whereIn('name', UserType::staffRoleValues())
                ->orderBy('name')
                ->get(['id', 'name']),
            'userRoles' => $user->roles->pluck('name'),
            'isLastSuperAdmin' => SuperAdmin::isLast($user),
        ]);
    }

    public function update(UpdateUserRequest $request, string $locale, User $user): RedirectResponse
    {
        $this->ensureAdmin($user);
        $this->authorize('update', $user);

        $data = $request->validated();
        $roles = $request->exists('roles')
            ? $this->staffRolesOnly($request->validated('roles', []))
            : $user->getRoleNames()->all();

        if ($roles === []) {
            return back()->withErrors(['roles' => 'Assign at least one staff role.'])->withInput();
        }

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
        $user->syncRoles($roles);
        $user->syncTypeFromRoles();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Admin updated successfully.']);

        return redirect()->route('admin.admins.index');
    }

    public function destroy(string $locale, User $user): RedirectResponse
    {
        $this->ensureAdmin($user);
        $this->authorize('delete', $user);

        if (SuperAdmin::isLast($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'You must assign the super-admin role to another user before deleting the last super administrator.',
            ]);

            return redirect()->back();
        }

        $this->deleteAvatar($user);
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Admin deleted successfully.']);

        return redirect()->back();
    }

    private function ensureAdmin(User $user): void
    {
        abort_unless($user->isAdmin(), 404);
    }

    /**
     * @param  list<string>|array<int, string>  $roles
     * @return list<string>
     */
    private function staffRolesOnly(array $roles): array
    {
        return array_values(array_intersect($roles, UserType::staffRoleValues()));
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
