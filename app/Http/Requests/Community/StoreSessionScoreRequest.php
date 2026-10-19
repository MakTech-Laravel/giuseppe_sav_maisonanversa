<?php

namespace App\Http\Requests\Community;

use App\Enums\SessionMatchFormat;
use App\Enums\SessionMatchType;
use App\Enums\SessionSport;
use App\Models\CommunitySession;
use App\Support\MatchScore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSessionScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submitScore', $this->communitySession()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $session = $this->communitySession();
        $playerIds = $session->participants()->pluck('user_id')->all();
        $perSide = $this->matchType()?->playersPerSide() ?? 2;

        return [
            'match_type' => [
                'required',
                Rule::in(array_map(
                    static fn (SessionMatchType $type): string => $type->value,
                    $session->sport->matchTypes(),
                )),
            ],
            'format' => ['required', Rule::enum(SessionMatchFormat::class)],
            'team_a' => ['required', 'array', "size:{$perSide}"],
            'team_a.*' => ['required', 'integer', 'distinct', Rule::in($playerIds)],
            'team_b' => ['required', 'array', "size:{$perSide}"],
            'team_b.*' => ['required', 'integer', 'distinct', Rule::in($playerIds)],
            'sets' => ['required', 'array', 'min:1', 'max:3'],
            'sets.*.a' => ['required', 'integer', 'min:0', 'max:99'],
            'sets.*.b' => ['required', 'integer', 'min:0', 'max:99'],
            'sets.*.tiebreak_a' => ['nullable', 'integer', 'min:0', 'max:99'],
            'sets.*.tiebreak_b' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $overlap = array_intersect(
                    array_map('intval', (array) $this->input('team_a', [])),
                    array_map('intval', (array) $this->input('team_b', [])),
                );

                if ($overlap !== []) {
                    $validator->errors()->add('team_b', __('Een speler kan maar in één team staan.'));
                }
            },
            function (Validator $validator): void {
                $hasShapeErrors = collect($validator->errors()->keys())->contains(
                    fn (string $key): bool => $key === 'format' || str_starts_with($key, 'sets'),
                );

                if ($hasShapeErrors) {
                    return;
                }

                $format = SessionMatchFormat::from((string) $this->input('format'));

                foreach (MatchScore::errors($format, $this->normalizedSets()) as $key => $message) {
                    $validator->errors()->add($key, $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $perSide = $this->matchType()?->playersPerSide() ?? 2;
        $sideMessage = $perSide === 1
            ? __('Kies één speler per kant.')
            : __('Kies twee spelers per team.');

        return [
            'match_type.in' => $this->communitySession()->sport === SessionSport::Padel
                ? __('Padel wordt altijd met twee tegen twee gespeeld.')
                : __('Kies enkel of dubbel.'),
            'team_a.required' => $sideMessage,
            'team_b.required' => $sideMessage,
            'team_a.size' => $sideMessage,
            'team_b.size' => $sideMessage,
            'team_a.*.in' => __('Alleen bevestigde spelers kunnen in een team staan.'),
            'team_b.*.in' => __('Alleen bevestigde spelers kunnen in een team staan.'),
            'sets.required' => __('Vul minstens één set in.'),
        ];
    }

    /**
     * @return array{match_type: SessionMatchType, format: SessionMatchFormat, team_a: list<int>, team_b: list<int>, sets: list<array{a: int, b: int, tiebreak_a: int|null, tiebreak_b: int|null}>}
     */
    public function scoreData(): array
    {
        $validated = $this->validated();

        return [
            'match_type' => SessionMatchType::from($validated['match_type']),
            'format' => SessionMatchFormat::from($validated['format']),
            'team_a' => array_values(array_map('intval', $validated['team_a'])),
            'team_b' => array_values(array_map('intval', $validated['team_b'])),
            'sets' => $this->normalizedSets(),
        ];
    }

    /**
     * @return list<array{a: int, b: int, tiebreak_a: int|null, tiebreak_b: int|null}>
     */
    private function normalizedSets(): array
    {
        return array_values(array_map(
            static fn (array $set): array => [
                'a' => (int) $set['a'],
                'b' => (int) $set['b'],
                'tiebreak_a' => isset($set['tiebreak_a']) && $set['tiebreak_a'] !== '' ? (int) $set['tiebreak_a'] : null,
                'tiebreak_b' => isset($set['tiebreak_b']) && $set['tiebreak_b'] !== '' ? (int) $set['tiebreak_b'] : null,
            ],
            array_filter((array) $this->input('sets', []), 'is_array'),
        ));
    }

    private function matchType(): ?SessionMatchType
    {
        return SessionMatchType::tryFrom((string) $this->input('match_type'));
    }

    private function communitySession(): CommunitySession
    {
        /** @var CommunitySession $session */
        $session = $this->route('communitySession');

        return $session;
    }
}
