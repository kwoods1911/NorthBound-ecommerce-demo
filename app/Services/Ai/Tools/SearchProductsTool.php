<?php

namespace App\Services\Ai\Tools;

use App\Http\Resources\ProductCardResource;
use App\Services\Ai\Data\ToolDefinition;
use App\Services\ProductSearchService;

/**
 * `searchProducts(query, category, maxPrice)` — free-text catalogue search.
 * Delegates entirely to ProductSearchService.
 */
final class SearchProductsTool implements ChatTool
{
    public function __construct(private readonly ProductSearchService $search) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'searchProducts',
            description: 'Search the travel gear catalogue. Use this for any question about what the store sells, product suggestions, prices, colours or materials. Never describe or price a product without calling this first.',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Free-text search terms, e.g. "carry-on", "packing cubes", "weekender bag".',
                    ],
                    'category' => [
                        'type' => 'string',
                        'description' => 'Optional category name or slug to narrow the search.',
                    ],
                    'maxPrice' => [
                        'type' => 'number',
                        'description' => 'Optional maximum price in US dollars.',
                    ],
                ],
                'required' => ['query'],
            ],
        );
    }

    public function handle(array $arguments): array
    {
        $query = isset($arguments['query']) ? trim((string) $arguments['query']) : '';
        $category = isset($arguments['category']) ? trim((string) $arguments['category']) : null;
        $maxPrice = isset($arguments['maxPrice']) && is_numeric($arguments['maxPrice'])
            ? (int) round(((float) $arguments['maxPrice']) * 100)
            : null;

        $products = $this->search->search(
            query: $query === '' ? null : $query,
            category: $category === '' ? null : $category,
            maxPriceCents: $maxPrice,
        );

        return [
            'count' => $products->count(),
            'products' => ProductCardResource::collection($products)->resolve(),
        ];
    }
}
