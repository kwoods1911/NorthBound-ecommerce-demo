<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\Data\AiToolCall;
use App\Services\Ai\Data\ToolDefinition;

/**
 * The chatbot's fixed toolset — exactly two tools, wired here. ChatService asks
 * this for the definitions to advertise and dispatches the model's calls back
 * through it.
 */
final class ToolRegistry
{
    /** @var array<string, ChatTool> */
    private array $tools;

    public function __construct(SearchProductsTool $searchProducts, CheckStockTool $checkStock)
    {
        $this->tools = [
            $searchProducts->definition()->name => $searchProducts,
            $checkStock->definition()->name => $checkStock,
        ];
    }

    /**
     * @return array<int, ToolDefinition>
     */
    public function definitions(): array
    {
        return array_map(fn (ChatTool $tool) => $tool->definition(), array_values($this->tools));
    }

    /**
     * @return array<string, mixed>
     */
    public function dispatch(AiToolCall $call): array
    {
        $tool = $this->tools[$call->name] ?? null;

        if ($tool === null) {
            return ['error' => "Unknown tool: {$call->name}"];
        }

        return $tool->handle($call->arguments);
    }
}
