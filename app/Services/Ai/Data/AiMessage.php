<?php

namespace App\Services\Ai\Data;

use App\Enums\ChatRole;

/**
 * One turn in a conversation, in a shape no provider knows about. Assistant
 * turns may carry tool calls; tool turns carry the result of one call.
 */
final class AiMessage
{
    /**
     * @param  array<int, AiToolCall>  $toolCalls
     * @param  array<string, mixed>|null  $toolResult
     */
    public function __construct(
        public readonly ChatRole $role,
        public readonly ?string $content = null,
        public readonly array $toolCalls = [],
        public readonly ?string $toolName = null,
        public readonly ?array $toolResult = null,
        public readonly ?string $toolCallId = null,
    ) {}

    public static function user(string $content): self
    {
        return new self(ChatRole::User, $content);
    }

    /**
     * @param  array<int, AiToolCall>  $toolCalls
     */
    public static function assistant(?string $content, array $toolCalls = []): self
    {
        return new self(ChatRole::Assistant, $content, $toolCalls);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public static function tool(string $toolName, array $result, ?string $toolCallId = null): self
    {
        return new self(ChatRole::Tool, null, [], $toolName, $result, $toolCallId);
    }
}
