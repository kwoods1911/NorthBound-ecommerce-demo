<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\Data\ToolDefinition;

/**
 * A tool the chatbot can call. Implementations delegate straight to a storefront
 * service — they hold no query logic of their own.
 */
interface ChatTool
{
    public function definition(): ToolDefinition;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed> result passed back to the model (and, for
     *                              product results, rendered as cards in the UI)
     */
    public function handle(array $arguments): array;
}
