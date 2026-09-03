<?php

namespace App\Services\Ai\Data;

/**
 * The model's reply to one AiChatRequest: either free text, or one or more
 * tool calls to run before asking again.
 */
final class AiChatResponse
{
    /**
     * @param  array<int, AiToolCall>  $toolCalls
     */
    public function __construct(
        public readonly ?string $text = null,
        public readonly array $toolCalls = [],
        public readonly ?string $finishReason = null,
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
