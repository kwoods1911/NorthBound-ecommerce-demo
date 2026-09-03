<?php

namespace App\DataObjects;

use App\Enums\ProductSort;
use App\Http\Requests\CollectionFilterRequest;

/**
 * Normalised, validated collection-filter state. Built from the request, applied
 * by CollectionService, and echoed back to the frontend via toQueryArray() so
 * the UI and shareable URLs stay in sync.
 */
final class CollectionFilters
{
    /**
     * @param  array<int, string>  $colors
     */
    public function __construct(
        public readonly ?string $category = null,
        public readonly array $colors = [],
        public readonly ?string $priceBucket = null,
        public readonly bool $onSale = false,
        public readonly ProductSort $sort = ProductSort::Featured,
        public readonly int $page = 1,
    ) {
    }

    public static function fromRequest(CollectionFilterRequest $request): self
    {
        $colors = array_values(array_filter(
            (array) $request->input('colors', []),
            fn ($value) => is_string($value) && $value !== '',
        ));

        return new self(
            category: $request->filled('category') ? (string) $request->string('category') : null,
            colors: $colors,
            priceBucket: $request->filled('price') ? (string) $request->string('price') : null,
            onSale: $request->boolean('sale'),
            sort: ProductSort::tryFrom((string) $request->input('sort')) ?? ProductSort::Featured,
            page: max(1, (int) $request->input('page', 1)),
        );
    }

    /**
     * Non-default values only, for rebuilding the query string on the client.
     *
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        return array_filter([
            'category' => $this->category,
            'colors' => $this->colors,
            'price' => $this->priceBucket,
            'sale' => $this->onSale ?: null,
            'sort' => $this->sort === ProductSort::Featured ? null : $this->sort->value,
            'page' => $this->page > 1 ? $this->page : null,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
