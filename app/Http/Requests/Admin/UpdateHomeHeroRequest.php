<?php

namespace App\Http\Requests\Admin;

use App\Enums\HomeHeroAction;
use App\Models\HomeHero;
use App\Support\HomeHeroPresenter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomeHeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_counter' => $this->boolean('show_counter'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['sometimes', 'boolean'],
            'eyebrow' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:80'],
            'title_accent' => ['required', 'string', 'max:80'],
            'tagline' => ['required', 'string', 'max:255'],
            'show_counter' => ['required', 'boolean'],
            'counter_line_one' => ['required', 'string', 'max:80'],
            'counter_line_two' => ['required', 'string', 'max:80'],
        ];

        foreach (HomeHero::SLOTS as $slot) {
            $rules["{$slot}_label"] = ['nullable', 'string', 'max:80'];
            $rules["{$slot}_action"] = ['required', Rule::enum(HomeHeroAction::class)];
            $rules["{$slot}_target"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (HomeHero::SLOTS as $slot) {
                    $action = HomeHeroAction::tryFrom((string) $this->input("{$slot}_action"));

                    if ($action === null) {
                        continue;
                    }

                    $label = trim((string) $this->input("{$slot}_label", ''));
                    $target = trim((string) $this->input("{$slot}_target", ''));

                    if ($action !== HomeHeroAction::Hidden && $label === '') {
                        $validator->errors()->add(
                            "{$slot}_label",
                            __('Een zichtbare knop heeft een label nodig.'),
                        );
                    }

                    if ($action === HomeHeroAction::MaisonPage && ! array_key_exists($target, HomeHeroPresenter::PAGES)) {
                        $validator->errors()->add(
                            "{$slot}_target",
                            __('Kies een Maison-pagina.'),
                        );
                    }

                    if ($action === HomeHeroAction::External && ! $this->isHttpsUrl($target)) {
                        $validator->errors()->add(
                            "{$slot}_target",
                            __('Een externe knop vereist een https-adres.'),
                        );
                    }
                }
            },
        ];
    }

    private function isHttpsUrl(string $target): bool
    {
        return str_starts_with($target, 'https://')
            && filter_var($target, FILTER_VALIDATE_URL) !== false;
    }
}
