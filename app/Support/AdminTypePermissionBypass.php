<?php

namespace App\Support;

use App\Enums\PermissionEnum;
use App\Models\User;

/**
 * TEMPORARY STAFF BYPASS
 * ─────────────────────────────────────────────────────────────────────────────
 * While Access Control (fine-grained roles / permissions) is hidden from the
 * admin UI, any account with `users.type = admin` is treated as fully
 * privileged for Spatie permission checks and the Inertia permission list.
 *
 * How to remove when role-permission UX returns
 * ─────────────────────────────────────────────
 * 1. Set `MAISON_ADMIN_TYPE_GRANTS_ALL_PERMISSIONS=false` in `.env`, or
 * 2. Delete this class, remove the config key in `config/maison.php`,
 *    remove the Gate branch in `AppServiceProvider`, and the Inertia branch
 *    in `HandleInertiaRequests`.
 * 3. Delete `tests/Feature/Admin/AdminTypePermissionBypassTest.php`.
 */
final class AdminTypePermissionBypass
{
    public static function enabled(): bool
    {
        return (bool) config('maison.admin_type_grants_all_permissions', false);
    }

    public static function grants(User $user): bool
    {
        return self::enabled() && $user->isAdmin();
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        return array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            PermissionEnum::cases(),
        );
    }

    /**
     * Only Spatie permission ability names — never model policy methods
     * (`update`, `delete`, …) so UserPolicy can still protect super-admins.
     */
    public static function allowsAbility(User $user, string $ability): bool
    {
        if (! self::grants($user)) {
            return false;
        }

        return in_array($ability, self::allPermissionNames(), true);
    }
}
