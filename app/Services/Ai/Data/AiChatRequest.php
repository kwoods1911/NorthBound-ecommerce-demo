<?php

namespace App\Services\Ai\Data;

/**
 * Everything a provider needs for one completion: the system instruction, the
 * running transcript, and the tools the model is allowed to call.
 */
final class AiChatRequest
{
    /**
     * @param  array<int, AiMessage>  $messages
     * @param  array<int, ToolDefinition>  $tools
     */
    public function __construct(
        public readonly string $systemPrompt,
        public readonly array $messages,
        public readonly array $tools = [],
    ) {}
}
