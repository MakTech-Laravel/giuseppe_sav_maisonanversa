<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\OpeningOffer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpeningOfferRequest extends FormRequest
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
        /** @var OpeningOffer $offer */
        $offer = $this->route('openingOffer');

        // Keeping the same product is always allowed, so a sort order or the active
        // switch can still be edited after the product has stopped being offerable.
        $unchanged = (int) $this->input('product_id') === (int) $offer->product_id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::unique('opening_offers', 'product_id')->ignore($offer),
                $unchanged
                    ? Rule::exists('products', 'id')
                    : Rule::exists('products', 'id')->where(
                        fn (Builder $query): Builder => $query
                            ->where('type', ProductType::LimitedEdition->value)
                            ->where('is_published', true)
                            ->where('status', ProductStatus::Active->value),
                    ),
            ],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.unique' => __('This product is already in the opening.'),
            'product_id.exists' => __('Choose a published, active product with a numbered edition.'),
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
        $data['is_active'] = (bool) $data['is_active'];

        return $data;
    }
}
