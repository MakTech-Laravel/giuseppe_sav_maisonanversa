<?php

namespace App\Http\Requests\Role;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        $nameRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('roles', 'name')->ignore($role->id),
        ];

        // Reserve the super-admin name for the super-admin role only.
        if ($role->name !== RoleEnum::SUPER_ADMIN->value) {
            $nameRules[] = Rule::notIn([RoleEnum::SUPER_ADMIN->value]);
        }

        return [
            'name' => $nameRules,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.not_in' => 'This role name is reserved.',
            'permissions.*.exists' => 'One of the selected permissions is invalid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Role name',
            'permissions' => 'Permissions',
        ];
    }
}
