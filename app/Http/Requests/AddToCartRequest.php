<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function quantity(): int
    {
        return (int) ($this->input('quantity') ?? 1);
    }
}
