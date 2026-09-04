<?php

namespace App\Http\Requests\Admin;

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [Rule::enum(SessionSport::class)],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(ClubStatus::class)->except(ClubStatus::Merged)],
            'is_partner' => ['sometimes', 'boolean'],
            'is_session_venue' => ['sometimes', 'boolean'],
            'show_on_corner_page' => ['sometimes', 'boolean'],
            'corner_pipeline_status' => ['nullable', Rule::enum(CornerPipelineStatus::class)],
            'has_corner' => ['sometimes', 'boolean'],
            'corner_published' => ['sometimes', 'boolean'],
            'corner_title' => ['nullable', 'string', 'max:255'],
            'corner_body' => ['nullable', 'string'],
            'corner_location' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Multipart method spoofing sends booleans as strings.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_partner' => $this->boolean('is_partner'),
            'is_session_venue' => $this->has('is_session_venue')
                ? $this->boolean('is_session_venue')
                : true,
            'show_on_corner_page' => $this->boolean('show_on_corner_page'),
            'has_corner' => $this->boolean('has_corner'),
            'corner_published' => $this->boolean('corner_published'),
            'corner_pipeline_status' => $this->filled('corner_pipeline_status')
                ? $this->input('corner_pipeline_status')
                : null,
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }
}
