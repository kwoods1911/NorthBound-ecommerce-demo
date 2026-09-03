<?php

namespace App\Services;

use App\DataObjects\CollectionFilters;
use App\Enums\CollectionType;
use App\Enums\ProductSort;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CollectionService
{
    /**
     * Products for a collection with the current filters applied.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(CollectionType $type, CollectionFilters $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($type)->with('variants');

        if ($type->showsCategoryFilter() && $filters->category !== null) {
            $query->inCategory($filters->category);
        }

        $query->havingColors($filters->colors);

        if ($bucket = $this->bucket($filters->priceBucket)) {
            $query->priceBucket($bucket['min_cents'], $bucket['max_cents']);
        }

        if ($this->onSaleRequired($type, $filters)) {
            $query->discounted();
        }

        $this->applyOrder($query, $type, $filters->sort);

        return $query
            ->paginate(config('shop.per_page'), page: $filters->page)
            ->withQueryString();
    }

    /**
     * Facet data for the filter UI, derived from the collection's base set
     * (before any user filters are applied).
     *
     * @return array<string, mixed>
     */
    public function filterOptions(CollectionType $type): array
    {
        $baseProducts = $this->baseQuery($type)->reorder();

        $options = [
            'colors' => ProductVariant::query()
                ->whereIn('product_id', (clone $baseProducts)->select('products.id'))
                ->whereNotNull('color')
                ->distinct()
                ->orderBy('color')
                ->pluck('color')
                ->values(),
            'priceBuckets' => array_map(
                fn (array $b) => ['key' => $b['key'], 'label' => $b['label']],
                config('shop.price_buckets'),
            ),
            'sorts' => ProductSort::options(),
            'showOnSaleFilter' => $type->showsOnSaleFilter(),
        ];

        if ($type->showsCategoryFilter()) {
            $options['categories'] = Category::query()
                ->whereIn('id', (clone $baseProducts)->select('products.category_id'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $c) => ['name' => $c->name, 'slug' => $c->slug])
                ->values();
        }

        return $options;
    }

    /**
     * The collection's base scope, before filters. Public-ish seam for tests.
     *
     * @return Builder<Product>
     */
    public function baseQuery(CollectionType $type): Builder
    {
        return match ($type) {
            CollectionType::NewArrivals => Product::query()->newArrivals(),
            CollectionType::BestSellers => Product::query()->bestSellers(),
            CollectionType::Accessories => Product::query()->inCategory(
                $type->categorySlug() ?? '__missing__'
            ),
            CollectionType::Deals => Product::query()->discounted(),
        };
    }

    /**
     * An explicit sort always wins; "Featured" resolves to the collection's
     * own default ordering.
     *
     * @param  Builder<Product>  $query
     */
    private function applyOrder(Builder $query, CollectionType $type, ProductSort $sort): void
    {
        $effective = $sort === ProductSort::Featured ? $type->defaultSort() : $sort;

        $query->reorder()->applySort($effective);
    }

    private function onSaleRequired(CollectionType $type, CollectionFilters $filters): bool
    {
        return ! $type->showsOnSaleFilter() || $filters->onSale;
    }

    /**
     * @return array{key: string, label: string, min_cents: ?int, max_cents: ?int}|null
     */
    private function bucket(?string $key): ?array
    {
        if ($key === null) {
            return null;
        }

        foreach (config('shop.price_buckets') as $bucket) {
            if ($bucket['key'] === $key) {
                return $bucket;
            }
        }

        return null;
    }
}
