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
        return $this->user() !== null;
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
            'email.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
            'shipping_line1.required' => __('Vul a.u.b. uw verzendadres in.'),
            'shipping_city.required' => __('Vul a.u.b. uw verzendadres in.'),
            'shipping_postal_code.required' => __('Vul a.u.b. uw verzendadres in.'),
            'shipping_country.required' => __('Vul a.u.b. uw verzendadres in.'),
            'edition_piece_id.required' => __('Kies a.u.b. een beschikbaar editienummer.'),
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

            if (! $product->isLimitedEdition()) {
                return;
            }

            $available = app(EditionInventory::class)->snapshot($product)['available'];

            if ($available === 0) {
                $validator->errors()->add(
                    'checkout',
                    __(':product is uitverkocht.', [
                        'product' => $product->translated('name'),
                    ]),
                );

                return;
            }

            $editionPieceId = $this->integer('edition_piece_id') ?: null;

            if ($editionPieceId === null) {
                $validator->errors()->add(
                    'edition_piece_id',
                    __('Kies a.u.b. een beschikbaar editienummer.'),
                );

                return;
            }

            $piece = EditionPiece::query()
                ->whereKey($editionPieceId)
                ->where('product_id', $product->id)
                ->where('status', EditionPieceStatus::Available)
                ->first();

            if ($piece === null) {
                $validator->errors()->add(
                    'edition_piece_id',
                    __('Dit editienummer is niet meer beschikbaar. Kies een ander nummer.'),
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
