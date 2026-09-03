<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Free-text catalogue search. Backs the chatbot's two tools and is the single
 * seam for "find me products like…" queries. All query logic lives in Product
 * scopes; this class only composes them.
 */
class ProductSearchService
{
    public const DEFAULT_LIMIT = 6;

    /**
     * @return Collection<int, Product>
     */
    public function search(
        ?string $query,
        ?string $category = null,
        ?int $maxPriceCents = null,
        int $limit = self::DEFAULT_LIMIT,
    ): Collection {
        return Product::query()
            ->with('variants')
            ->when(
                $query !== null && $query !== '',
                fn (Builder $q) => $q->search($query),
            )
            ->when(
                $category !== null && $category !== '',
                fn (Builder $q) => $q->inCategoryLike($category),
            )
            ->when(
                $maxPriceCents !== null && $maxPriceCents > 0,
                fn (Builder $q) => $q->maxPrice($maxPriceCents),
            )
            ->orderByDesc('sales_count')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array{name: string, stock_quantity: int, in_stock: bool}|null
     */
    public function stockFor(int $productId): ?array
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            return null;
        }

        return [
            'name' => $product->name,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->stock_quantity > 0,
        ];
    }
}
