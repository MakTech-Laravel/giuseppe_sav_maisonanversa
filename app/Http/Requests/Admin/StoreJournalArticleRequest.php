<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalArticleRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('journal_articles', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string', 'max:5000'],
            'body' => ['required', 'string', 'max:100000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'date_label' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['slug'] = $data['slug'] ?? null;
        $data['published_at'] = $data['published_at'] ?? null;

        return $data;
    }
}
