<?php

namespace App\Http\Requests\Maison;

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Services\Edition\EditionInventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists((new Product)->getTable(), 'id')->where('is_published', true),
            ],
            'edition_piece_id' => ['nullable', 'integer', 'exists:edition_pieces,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'shipping_line1' => ['required', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_postal_code' => ['required', 'string', 'max:32'],
            'shipping_country' => ['required', 'string', 'size:2'],
            'gift_wrap' => ['sometimes', 'boolean'],
            'gift_message' => ['nullable', 'string', 'max:500'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please enter your name and email address.'),
            'email.required' => __('Please enter your name and email address.'),
            'shipping_line1.required' => __('Please enter your shipping address.'),
            'shipping_city.required' => __('Please enter your shipping address.'),
            'shipping_postal_code.required' => __('Please enter your shipping address.'),
            'shipping_country.required' => __('Please enter your shipping address.'),
            'edition_piece_id.required' => __('Please choose an available edition number.'),
            'accept_terms.accepted' => __('I have read the Terms & Conditions and the Return Policy and agree to them.'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = $this->resolveProduct();

            if ($product === null) {
                return;
            }

            if (! $product->isVisibleTo($this->user())) {
                $validator->errors()->add(
                    'product_id',
                    __('This product is not available for checkout yet.'),
                );

                return;
            }

            if (! $product->isLimitedEdition()) {
                return;
            }

            $editionPieceId = $this->integer('edition_piece_id') ?: null;

            $piece = $editionPieceId === null
                ? null
                : EditionPiece::query()
                    ->whereKey($editionPieceId)
                    ->where('product_id', $product->id)
                    ->first();

            $heldByBuyer = $piece?->isHeldBy($this->user()) ?? false;
            $available = app(EditionInventory::class)->snapshot($product)['available'];

            if ($available === 0 && ! $heldByBuyer) {
                $validator->errors()->add(
                    'checkout',
                    __(':product is sold out.', [
                        'product' => $product->translated('name'),
                    ]),
                );

                return;
            }

            if ($editionPieceId === null) {
                $validator->errors()->add(
                    'edition_piece_id',
                    __('Please choose an available edition number.'),
                );

                return;
            }

            if ($piece === null || ($piece->status !== EditionPieceStatus::Available && ! $heldByBuyer)) {
                $validator->errors()->add(
                    'edition_piece_id',
                    __('This edition number is no longer available. Please choose another.'),
                );
            }
        });
    }

    public function resolveProduct(): ?Product
    {
        $productId = $this->integer('product_id') ?: null;

        if ($productId !== null) {
            return Product::query()
                ->whereKey($productId)
                ->where('is_published', true)
                ->first();
        }

        return Product::founding();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('shipping_country') && is_string($this->input('shipping_country'))) {
            $this->merge([
                'shipping_country' => strtoupper(trim($this->input('shipping_country'))),
            ]);
        }
    }
}
