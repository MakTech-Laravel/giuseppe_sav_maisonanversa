<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $search = trim((string) $request->query('search', ''));

        $customers = User::query()
            ->where('type', UserType::Customer)
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
            ],
            'stats' => Inertia::optional(fn (): array => [
                'total' => User::query()->where('type', UserType::Customer)->count(),
                'verified' => User::query()
                    ->where('type', UserType::Customer)
                    ->whereNotNull('email_verified_at')
                    ->count(),
            ]),
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/customers/create');
    }

    public function store(StoreUserRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['type'] = UserType::Customer;
        $data['username'] = User::generateUsername($data['name']);

        $customer = User::create(collect($data)->except(['roles', 'remove_avatar'])->all());
        $customer->syncRoles([RoleEnum::USER->value]);
        $customer->syncTypeFromRoles();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer created successfully.']);

        return redirect()->route('admin.customers.index');
    }

    public function show(string $locale, User $user): Response
    {
        $this->ensureCustomer($user);

        $user->load('roles:id,name');

        return Inertia::render('admin/customers/show', [
            'customer' => $user,
        ]);
    }

    public function edit(string $locale, User $user): Response
    {
        $this->ensureCustomer($user);
        $this->authorize('update', $user);

        return Inertia::render('admin/customers/edit', [
            'customer' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, string $locale, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);
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
        $user->syncRoles([RoleEnum::USER->value]);
        $user->syncTypeFromRoles();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer updated successfully.']);

        return redirect()->route('admin.customers.index');
    }

    public function destroy(string $locale, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);
        $this->authorize('delete', $user);

        $this->deleteAvatar($user);
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer deleted successfully.']);

        return redirect()->back();
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->isCustomer(), 404);
    }

    private function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
