<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
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
        return [
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['required', 'string', 'max:32'],
            'email_hello' => ['required', 'email', 'max:255'],
            'email_press' => ['required', 'email', 'max:255'],
            'instagram_url' => ['required', 'url', 'max:255'],
            'boutique_lat' => ['required', 'numeric', 'between:-90,90'],
            'boutique_lng' => ['required', 'numeric', 'between:-180,180'],
            'announcement_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
