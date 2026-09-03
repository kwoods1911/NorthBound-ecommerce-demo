<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\Data\ToolDefinition;
use App\Services\ProductSearchService;

/**
 * `checkStock(productId)` — availability for one product. Delegates to
 * ProductSearchService.
 */
final class CheckStockTool implements ChatTool
{
    public function __construct(private readonly ProductSearchService $search) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'checkStock',
            description: 'Check whether one specific product is in stock and how many units remain. Pass the numeric product id from an earlier searchProducts result.',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'productId' => [
                        'type' => 'integer',
                        'description' => 'The product id returned by searchProducts.',
                    ],
                ],
                'required' => ['productId'],
            ],
        );
    }

    public function handle(array $arguments): array
    {
        $stock = $this->search->stockFor((int) ($arguments['productId'] ?? 0));

        if ($stock === null) {
            return ['found' => false];
        }

        return ['found' => true, ...$stock];
    }
}
