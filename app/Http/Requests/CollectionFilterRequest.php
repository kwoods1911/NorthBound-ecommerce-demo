<?php

namespace App\Http\Requests;

use App\Enums\ProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CollectionFilterRequest extends FormRequest
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
        $buckets = array_column(config('shop.price_buckets'), 'key');

        return [
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string', 'max:50'],
            'price' => ['nullable', Rule::in($buckets)],
            'sale' => ['nullable', 'boolean'],
            'sort' => ['nullable', new Enum(ProductSort::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Treat blank query params ("?sort=") as absent so `nullable` applies.
        $blankToNull = [];
        foreach (['category', 'price', 'sort'] as $key) {
            if ($this->has($key) && $this->query($key) === '') {
                $blankToNull[$key] = null;
            }
        }

        if ($blankToNull !== []) {
            $this->merge($blankToNull);
        }
    }
}
