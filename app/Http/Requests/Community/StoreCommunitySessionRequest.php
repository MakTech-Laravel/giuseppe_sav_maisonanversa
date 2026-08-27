<?php

namespace App\Http\Requests\Community;

use App\Enums\ClubStatus;
use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\CommunitySession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunitySessionRequest extends FormRequest
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
            'sport' => ['required', Rule::enum(SessionSport::class)],
            'club_id' => [
                'required',
                Rule::exists('clubs', 'id')->where('status', ClubStatus::Approved->value),
            ],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', Rule::in(CommunitySession::DURATION_MINUTES)],
            'court_status' => ['required', Rule::enum(SessionCourtStatus::class)],
            'level' => ['required', Rule::enum(SessionLevel::class)],
            'gender' => ['required', Rule::enum(SessionGender::class)],
            'capacity' => ['required', 'integer', 'min:2', 'max:8'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'club_id.required' => __('Kies een club of corner.'),
            'club_id.exists' => __('Deze club is nog niet goedgekeurd.'),
            'starts_at.after' => __('Kies een tijdstip in de toekomst.'),
        ];
    }
}
