<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationType;
use App\Services\Notifications\NotificationTestService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendTestNotificationRequest extends FormRequest
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
            'target' => ['required', Rule::in(NotificationTestService::TARGETS)],
            'email' => ['nullable', 'required_if:target,user', 'email', Rule::exists('users', 'email')],
            'type' => ['required', Rule::enum(NotificationType::class)],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'link_label' => ['nullable', 'string', 'max:60'],
            'mode' => ['required', Rule::in(NotificationTestService::MODES)],
        ];
    }
}
