<?php

namespace App\Http\Responses;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $home = $this->isStaff($user)
            ? route('dashboard')
            : route('member.dashboard');

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($home);
    }

    private function isStaff(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::EDITOR->value,
            RoleEnum::AUTHOR->value,
            RoleEnum::VIEWER->value,
        ]);
    }
}
