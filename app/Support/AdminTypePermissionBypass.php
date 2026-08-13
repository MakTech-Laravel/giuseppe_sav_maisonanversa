<?php

namespace App\Support;

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

    public static function grantsAll(User $user): bool
    {
        return self::enabled() && $user->isAdmin();
    }
}
