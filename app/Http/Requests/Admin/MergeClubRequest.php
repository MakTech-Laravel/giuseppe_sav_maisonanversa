<?php

namespace App\Http\Requests\Admin;

use App\Enums\SessionSport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeClubRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const SOURCEABLE_FIELDS = [
        'name',
        'street',
        'postal_code',
        'city',
        'country',
        'lat',
        'lng',
        'website',
        'phone',
        'status',
        'corner_pipeline_status',
        'corner_title',
        'corner_body',
        'corner_location',
        'sort_order',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sourceRules = [];

        foreach (self::SOURCEABLE_FIELDS as $field) {
            $sourceRules["sources.{$field}"] = ['required', Rule::in(['survivor', 'duplicate'])];
        }

        return [
            'target_id' => [
                'required',
                'integer',
                Rule::exists('clubs', 'id'),
                Rule::notIn([$this->route('club')?->id]),
            ],
            ...$sourceRules,
            'image_source' => ['required', Rule::in(['survivor', 'duplicate', 'none'])],
            'is_partner' => ['required', 'boolean'],
            'is_session_venue' => ['required', 'boolean'],
            'has_corner' => ['required', 'boolean'],
            'corner_published' => ['required', 'boolean'],
            'show_on_corner_page' => ['required', 'boolean'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [Rule::enum(SessionSport::class)],
        ];
    }

    /**
     * @return array{
     *     sources: array<string, 'survivor'|'duplicate'>,
     *     image_source: 'survivor'|'duplicate'|'none',
     *     is_partner: bool,
     *     is_session_venue: bool,
     *     has_corner: bool,
     *     corner_published: bool,
     *     show_on_corner_page: bool,
     *     sports: list<string>
     * }
     */
    public function mergePayload(): array
    {
        /** @var array<string, 'survivor'|'duplicate'> $sources */
        $sources = $this->validated('sources');

        return [
            'sources' => $sources,
            'image_source' => $this->validated('image_source'),
            'is_partner' => $this->boolean('is_partner'),
            'is_session_venue' => $this->boolean('is_session_venue'),
            'has_corner' => $this->boolean('has_corner'),
            'corner_published' => $this->boolean('corner_published'),
            'show_on_corner_page' => $this->boolean('show_on_corner_page'),
            'sports' => array_values($this->validated('sports')),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_partner' => $this->boolean('is_partner'),
            'is_session_venue' => $this->boolean('is_session_venue'),
            'has_corner' => $this->boolean('has_corner'),
            'corner_published' => $this->boolean('corner_published'),
            'show_on_corner_page' => $this->boolean('show_on_corner_page'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_id.not_in' => __('Een club kan niet met zichzelf worden samengevoegd.'),
        ];
    }
}
